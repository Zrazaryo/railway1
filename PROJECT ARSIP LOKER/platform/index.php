<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$error_message = '';
$success_message = '';

// Pastikan tabel platform_years sudah ada
try {
    $tableCheck = $db->fetch("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'platform_years'", [DB_NAME]);
    if (!$tableCheck || (int)$tableCheck['cnt'] === 0) {
        // Buat tabel jika belum ada
        $createTableSql = "CREATE TABLE IF NOT EXISTS platform_years (
            id INT AUTO_INCREMENT PRIMARY KEY,
            year INT NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->execute($createTableSql);
    }
} catch (Exception $e) {
    // Ignore jika tabel sudah ada
}

// Proses tambah tahun
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_year') {
    $year = (int)($_POST['year'] ?? 0);
    $description = sanitize_input($_POST['description'] ?? '');
    
    if ($year < 2000 || $year > 2100) {
        $error_message = 'Tahun harus antara 2000-2100';
    } else {
        try {
            $db->execute("INSERT INTO platform_years (year, description) VALUES (?, ?)", [$year, $description]);
            $success_message = 'Tahun berhasil ditambahkan';
            log_activity($_SESSION['user_id'], 'PLATFORM_ADD_YEAR', "Menambahkan tahun: $year");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error_message = 'Tahun ' . $year . ' sudah ada';
            } else {
                $error_message = 'Gagal menambahkan tahun: ' . $e->getMessage();
            }
        }
    }
}

// Proses edit tahun
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_year') {
    $year_id = (int)($_POST['year_id'] ?? 0);
    $year = (int)($_POST['year'] ?? 0);
    $description = sanitize_input($_POST['description'] ?? '');
    
    if ($year < 2000 || $year > 2100) {
        $error_message = 'Tahun harus antara 2000-2100';
    } elseif ($year_id <= 0) {
        $error_message = 'ID tahun tidak valid';
    } else {
        try {
            // Cek apakah tahun sudah digunakan oleh tahun lain
            $existing = $db->fetch("SELECT id FROM platform_years WHERE year = ? AND id != ?", [$year, $year_id]);
            if ($existing) {
                $error_message = 'Tahun ' . $year . ' sudah digunakan';
            } else {
                $db->execute("UPDATE platform_years SET year = ?, description = ? WHERE id = ?", [$year, $description, $year_id]);
                $success_message = 'Tahun berhasil diupdate';
                log_activity($_SESSION['user_id'], 'PLATFORM_EDIT_YEAR', "Mengedit tahun ID: $year_id menjadi $year");
            }
        } catch (Exception $e) {
            $error_message = 'Gagal mengupdate tahun: ' . $e->getMessage();
        }
    }
}

// Proses hapus tahun
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_year') {
    $year_id = (int)($_POST['year_id'] ?? 0);
    
    if ($year_id <= 0) {
        $error_message = 'ID tahun tidak valid';
    } else {
        try {
            // Get year info untuk log
            $year_info = $db->fetch("SELECT year FROM platform_years WHERE id = ?", [$year_id]);
            
            if ($year_info) {
                $db->execute("DELETE FROM platform_years WHERE id = ?", [$year_id]);
                $success_message = 'Tahun berhasil dihapus';
                log_activity($_SESSION['user_id'], 'PLATFORM_DELETE_YEAR', "Menghapus tahun: " . $year_info['year']);
            } else {
                $error_message = 'Tahun tidak ditemukan';
            }
        } catch (Exception $e) {
            $error_message = 'Gagal menghapus tahun: ' . $e->getMessage();
        }
    }
}

// Tampilkan pesan success dari URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Ambil daftar tahun
try {
    $years = $db->fetchAll("SELECT * FROM platform_years ORDER BY year DESC");
} catch (Exception $e) {
    $error_message = 'Error mengambil data tahun: ' . $e->getMessage();
    $years = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Upload Dokumen - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        Platform Upload Dokumen
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addYearModal">
                            <i class="fas fa-plus me-2"></i>TAMBAH TAHUN
                        </button>
                    </div>
                </div>
                
                <!-- Alert Messages -->
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo e($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Tabel Tahun -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">NO</th>
                                        <th>TAHUN</th>
                                        <th style="width: 250px;">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($years)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Belum ada tahun ditambahkan. Klik tombol "TAMBAH TAHUN" untuk menambahkan tahun baru.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($years as $year): ?>
                                            <tr>
                                                <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                                <td class="fw-semibold"><?php echo $year['year']; ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="documents.php?year=<?php echo $year['year']; ?>" 
                                                           class="btn btn-sm btn-primary" title="Lihat Dokumen">
                                                            <i class="fas fa-eye"></i> lihat
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-warning" 
                                                                onclick="editYear(<?php echo $year['id']; ?>, <?php echo $year['year']; ?>, '<?php echo e($year['description'] ?? ''); ?>')" 
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i> edit
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger" 
                                                                onclick="deleteYear(<?php echo $year['id']; ?>, <?php echo $year['year']; ?>)" 
                                                                title="Hapus">
                                                            <i class="fas fa-trash"></i> hapus
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal Tambah Tahun -->
    <div class="modal fade" id="addYearModal" tabindex="-1" aria-labelledby="addYearModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addYearModalLabel">
                        <i class="fas fa-plus me-2"></i>Tambah Tahun
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addYearForm">
                    <input type="hidden" name="action" value="add_year">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="year" class="form-label">
                                Tahun <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="year" 
                                   name="year" required min="2000" max="2100"
                                   placeholder="Contoh: 2024" value="<?php echo date('Y'); ?>">
                            <small class="text-muted">Masukkan tahun antara 2000-2100</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" placeholder="Masukkan deskripsi tahun (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit Tahun -->
    <div class="modal fade" id="editYearModal" tabindex="-1" aria-labelledby="editYearModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editYearModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Tahun
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editYearForm">
                    <input type="hidden" name="action" value="edit_year">
                    <input type="hidden" name="year_id" id="edit_year_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_year" class="form-label">
                                Tahun <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="edit_year" 
                                   name="year" required min="2000" max="2100"
                                   placeholder="Contoh: 2024">
                            <small class="text-muted">Masukkan tahun antara 2000-2100</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="edit_description" name="description" 
                                      rows="3" placeholder="Masukkan deskripsi tahun (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteYearModal" tabindex="-1" aria-labelledby="deleteYearModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteYearModalLabel">
                        <i class="fas fa-trash me-2"></i>Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus tahun <strong id="deleteYearValue"></strong>?</p>
                    <p class="text-danger small mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" id="deleteYearForm" style="display: inline;">
                        <input type="hidden" name="action" value="delete_year">
                        <input type="hidden" name="year_id" id="delete_year_id">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit tahun
        function editYear(id, year, description) {
            document.getElementById('edit_year_id').value = id;
            document.getElementById('edit_year').value = year;
            document.getElementById('edit_description').value = description || '';
            new bootstrap.Modal(document.getElementById('editYearModal')).show();
        }
        
        // Delete tahun
        function deleteYear(id, year) {
            document.getElementById('delete_year_id').value = id;
            document.getElementById('deleteYearValue').textContent = year;
            new bootstrap.Modal(document.getElementById('deleteYearModal')).show();
        }
    </script>
</body>
</html>
