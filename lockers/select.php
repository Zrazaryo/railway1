<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/search_template.php';

// Halaman ini bisa diakses admin & staff
require_login();

// Pastikan tabel lockers sudah ada. Jika belum, tampilkan pesan ramah.
try {
    // Cek apakah tabel lockers ada
    $tableCheck = $db->fetch("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'lockers'", [DB_NAME]);
    if (!$tableCheck || (int)$tableCheck['cnt'] === 0) {
        throw new Exception('Tabel \"lockers\" belum dibuat. Jalankan skrip SQL update_lockers_schema.sql di folder utama project.');
    }

    // Auto seed lemari A s/d Z jika tabel masih kosong
    // Catatan: Auto-seed dinonaktifkan untuk mencegah lemari muncul kembali setelah dihapus
    // Jika ingin membuat lemari, gunakan tombol "Tambah Lemari"
    // $existingCount = $db->fetch("SELECT COUNT(*) AS total FROM lockers");
    // if ((int)($existingCount['total'] ?? 0) === 0) {
    //     $letters = range('A', 'Z');
    //     foreach ($letters as $letter) {
    //         for ($i = 1; $i <= 10; $i++) {
    //             $code = $letter . str_pad($i, 2, '0', STR_PAD_LEFT); // Contoh: A01, A02
    //             $name = $letter . '.' . str_pad($i, 2, '0', STR_PAD_LEFT); // Contoh: A.01, A.02
    //             try {
    //                 $db->execute(
    //                     "INSERT INTO lockers (code, name, max_capacity) VALUES (?, ?, ?)",
    //                     [$code, $name, 600]
    //                 );
    //             } catch (Exception $e) {
    //             }
    //         }
    //     }
    // }

    // Ambil parameter search dan sort
    $search = sanitize_input($_GET['search'] ?? '');
    $sort_by = sanitize_input($_GET['sort'] ?? 'code_asc');
    
    // Parse sort parameter
    // Pengurutan baru: berdasarkan nomor rak dulu (1, 2, 3...), lalu huruf lemari (A, B, C...)
    // Contoh: A1, B1, C1, ..., Z1, A2, B2, C2, ..., Z2, dst
    // Ekstrak angka dari kode: ambil semua karakter setelah huruf pertama, hapus titik jika ada
    // Format kode: A1, A2, B1, B2, A01, A02, A.01, A.02, dll
    $order_by = "CAST(REPLACE(SUBSTRING(l.code, 2), '.', '') AS UNSIGNED) ASC, SUBSTRING(l.code, 1, 1) ASC";
    if ($sort_by === 'code_asc') {
        // Urutkan berdasarkan nomor rak dulu (angka setelah huruf pertama), lalu huruf lemari
        // SUBSTRING(l.code, 2) mengambil semua karakter mulai dari posisi 2
        // REPLACE menghapus titik jika ada (untuk format A.01)
        $order_by = "CAST(REPLACE(SUBSTRING(l.code, 2), '.', '') AS UNSIGNED) ASC, SUBSTRING(l.code, 1, 1) ASC";
    } elseif ($sort_by === 'code_desc') {
        // Urutkan terbalik: nomor rak DESC, lalu huruf DESC
        $order_by = "CAST(REPLACE(SUBSTRING(l.code, 2), '.', '') AS UNSIGNED) DESC, SUBSTRING(l.code, 1, 1) DESC";
    } elseif ($sort_by === 'name_asc') {
        $order_by = 'l.name ASC';
    } elseif ($sort_by === 'name_desc') {
        $order_by = 'l.name DESC';
    }
    
    // Build query dengan search dan sort
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(l.code LIKE ? OR l.name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Ambil data lemari + jumlah dokumen aktif yang memakai rak tsb
    // Juga ambil jumlah dokumen pemusnahan untuk validasi penghapusan
    $sql = "
        SELECT 
            l.id,
            l.code,
            l.name,
            l.max_capacity,
            COUNT(CASE WHEN d.status = 'active' THEN d.id END) AS used_count,
            COUNT(CASE WHEN d.status = 'deleted' THEN d.id END) AS deleted_count
        FROM lockers l
        LEFT JOIN documents d 
            -- Gunakan nama rak (l.name) sebagai identitas yang sama
            -- dengan kolom month_number di tabel documents
            ON d.month_number = l.name 
        $where_clause
        GROUP BY l.id, l.code, l.name, l.max_capacity
        ORDER BY $order_by
    ";
    $lockers = $db->fetchAll($sql, $params);
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $lockers = [];
} ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Lemari - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Pilih Lemari Dokumen</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (is_admin()): ?>
                        <a href="add.php" class="btn btn-primary me-2" title="Tambah Lemari Baru">
                            <i class="fas fa-plus"></i> Tambah Lemari
                        </a>
                    <?php endif; ?>
                    <?php if (is_superadmin()): ?>
                        <a href="../superadmin/dashboard.php" class="btn btn-outline-secondary" title="Kembali ke Dashboard">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()" title="Kembali">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message) && $error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo e($error_message); ?>
                </div>
            <?php else: ?>
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-archive me-2"></i>
                            Daftar Lemari (A s/d Z)
                        </h5>
                        <?php if (is_admin() || is_superadmin()): ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                <i class="fas fa-trash"></i> Hapus Terpilih
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Search dan Sort Section -->
                        <?php
                        render_search_form([
                            'search_placeholder' => 'Cari berdasarkan Kode Lemari atau Kode Rak...',
                            'search_value' => $search,
                            'sort_value' => $sort_by,
                            'show_category_filter' => false,
                            'show_advanced_search' => false,
                            'refresh_url' => 'select.php',
                            'sort_options' => [
                                'code_asc' => 'Urutkan: A1-Z1, A2-Z2, ...',
                                'code_desc' => 'Urutkan: Z2-A2, Z1-A1, ...',
                                'name_asc' => 'Urutkan berdasarkan Kode Rak A.01 - A.21',
                                'name_desc' => 'Urutkan berdasarkan Kode Rak A.21 - A.01'
                            ]
                        ]);
                        ?>
                        
                        <?php if (!empty($search)): ?>
                        <div class="mb-3">
                            <a href="select.php<?php echo !empty($sort_by) ? '?sort=' . urlencode($sort_by) : ''; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Hapus Filter
                            </a>
                            <small class="text-muted ms-2">
                                Hasil pencarian untuk "<?php echo e($search); ?>"
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($search)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Menampilkan hasil pencarian untuk: <strong><?php echo e($search); ?></strong>
                                <span class="badge bg-primary ms-2"><?php echo count($lockers); ?> hasil</span>
                            </div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <?php if (is_admin() || is_superadmin()): ?>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" title="Pilih Semua">
                                    </th>
                                    <?php endif; ?>
                                    <th style="width: 80px;">No</th>
                                            <th>Kode Lemari</th>
                                            <th>Nama Rak</th>
                                    <th>Max Capacity</th>
                                    <th>Used</th>
                                    <th style="width: 180px;">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($lockers)): ?>
                                    <tr>
                                        <td colspan="<?php echo (is_admin() || is_superadmin()) ? '7' : '6'; ?>" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <?php if (!empty($search)): ?>
                                                <p class="text-muted mb-0">Tidak ada lemari yang ditemukan untuk pencarian: <strong><?php echo e($search); ?></strong></p>
                                                <a href="select.php<?php echo !empty($sort_by) ? '?sort=' . urlencode($sort_by) : ''; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                                    <i class="fas fa-redo me-1"></i> Tampilkan Semua
                                                </a>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Data lemari belum tersedia.</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($lockers as $locker): ?>
                                        <?php
                                        $used = (int)$locker['used_count'];
                                        $deleted_count = (int)($locker['deleted_count'] ?? 0);
                                        $max = (int)$locker['max_capacity'];
                                        $percent_raw = $max > 0 ? ($used / $max) * 100 : 0;
                                        // Tampilkan minimal 1% kalau sudah terisi tapi masih di bawah 1%
                                        $percent_display = ($used > 0 && $percent_raw < 1) ? 1 : round($percent_raw);
                                        $is_full = $used >= $max;
                                        $has_documents = $used > 0; // Cek apakah ada dokumen aktif
                                        $has_pemusnahan = $deleted_count > 0; // Cek apakah ada dokumen pemusnahan
                                        $badge_class = 'bg-success';
                                        if ($percent_display >= 90) {
                                            $badge_class = 'bg-danger';
                                        } elseif ($percent_display >= 70) {
                                            $badge_class = 'bg-warning text-dark';
                                        }
                                        ?>
                                        <tr>
                                            <?php if (is_admin() || is_superadmin()): ?>
                                            <td>
                                                <input type="checkbox" class="locker-checkbox" value="<?php echo $locker['id']; ?>">
                                            </td>
                                            <?php endif; ?>
                                            <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                            <td class="fw-semibold"><?php echo e($locker['code']); ?></td>
                                            <td><?php echo e($locker['name']); ?></td>
                                            <td><?php echo number_format($max); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo number_format($used); ?> (<?php echo $percent_display; ?>%)
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($is_full): ?>
                                                    <!-- Lemari penuh: tombol Buat diganti jadi Penuh (disabled) -->
                                                    <button class="btn btn-sm btn-secondary" disabled title="Lemari penuh">
                                                        <i class="fas fa-lock"></i> Penuh
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Lemari belum penuh: tampilkan tombol Buat Dokumen -->
                                                    <?php if (!is_superadmin()): ?>
                                                        <a href="../documents/add.php?locker=<?php echo urlencode($locker['name']); ?>"
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-plus"></i> Buat
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <!-- Tombol Lihat selalu tersedia, baik lemari penuh maupun tidak -->
                                                <a href="detail.php?code=<?php echo urlencode($locker['name']); ?>"
                                                   class="btn btn-sm btn-info ms-1" title="Lihat Detail Lemari">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </a>
                                                <?php if (is_admin() || is_superadmin()): ?>
                                                    <?php if ($has_documents): ?>
                                                        <button class="btn btn-sm btn-danger ms-1" disabled 
                                                                title="Tidak bisa menghapus lemari karena masih ada dokumen aktif">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger ms-1"
                                                                title="Hapus Lemari"
                                                                onclick="confirmDeleteLocker(<?php echo $locker['id']; ?>, '<?php echo e($locker['name']); ?>')">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal Konfirmasi Penghapusan -->
<div class="modal fade" id="deleteLockerModal" tabindex="-1" aria-labelledby="deleteLockerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteLockerModalLabel">
                    <i class="fas fa-trash me-2"></i>Konfirmasi Penghapusan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteLockerMessage"></p>
                <div class="mb-3">
                    <label for="deleteAnswer" class="form-label">Jawaban Anda</label>
                    <input type="number" class="form-control" id="deleteAnswer" placeholder="Masukkan hasil penjumlahan">
                    <small class="form-text text-muted">Penghapusan hanya akan dilanjutkan jika jawaban benar.</small>
                </div>
                <input type="hidden" id="deleteLockerId">
                <input type="hidden" id="deleteLockerCorrectAnswer">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteLockerBtn">
                    <i class="fas fa-trash me-2"></i>Hapus Lemari
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Penghapusan Multiple -->
<div class="modal fade" id="deleteMultipleLockerModal" tabindex="-1" aria-labelledby="deleteMultipleLockerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteMultipleLockerModalLabel">
                    <i class="fas fa-trash me-2"></i>Konfirmasi Penghapusan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteMultipleLockerMessage"></p>
                <div class="mb-3">
                    <label for="deleteMultipleAnswer" class="form-label">Jawaban Anda</label>
                    <input type="number" class="form-control" id="deleteMultipleAnswer" placeholder="Masukkan hasil penjumlahan">
                    <small class="form-text text-muted">Penghapusan hanya akan dilanjutkan jika jawaban benar.</small>
                </div>
                <input type="hidden" id="deleteMultipleLockerIds">
                <input type="hidden" id="deleteMultipleLockerCorrectAnswer">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteMultipleLockerBtn">
                    <i class="fas fa-trash me-2"></i>Hapus Lemari
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.locker-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
    }

    function getSelectedLockerIds() {
        return Array.from(document.querySelectorAll('.locker-checkbox:checked'))
            .map(cb => parseInt(cb.value, 10))
            .filter(id => !isNaN(id));
    }

    let deleteLockerModal;
    let deleteMultipleLockerModal;
    
    document.addEventListener('DOMContentLoaded', function() {
        deleteLockerModal = new bootstrap.Modal(document.getElementById('deleteLockerModal'));
        deleteMultipleLockerModal = new bootstrap.Modal(document.getElementById('deleteMultipleLockerModal'));
    });

    function confirmDeleteLocker(lockerId, lockerName) {
        const num1 = Math.floor(Math.random() * 10) + 1;
        const num2 = Math.floor(Math.random() * 10) + 1;
        const correctAnswer = num1 + num2;
        
        document.getElementById('deleteLockerId').value = lockerId;
        document.getElementById('deleteLockerCorrectAnswer').value = correctAnswer;
        document.getElementById('deleteLockerMessage').innerHTML = 
            `Anda akan menghapus lemari <strong>${lockerName}</strong>.<br>` +
            `Untuk konfirmasi, jawab pertanyaan berikut:<br>` +
            `<span class="fw-bold">${num1} + ${num2} = ?</span>`;
        document.getElementById('deleteAnswer').value = '';
        
        if (deleteLockerModal) {
            deleteLockerModal.show();
        }
    }

    document.getElementById('confirmDeleteLockerBtn').addEventListener('click', function() {
        const answer = parseInt(document.getElementById('deleteAnswer').value, 10);
        const correctAnswer = parseInt(document.getElementById('deleteLockerCorrectAnswer').value, 10);
        const lockerId = document.getElementById('deleteLockerId').value;
        
        if (isNaN(answer) || answer !== correctAnswer) {
            alert('Jawaban salah! Penghapusan dibatalkan.');
            return;
        }
        
        window.location.href = `delete.php?id=${lockerId}`;
    });

    function deleteSelected() {
        const ids = getSelectedLockerIds();
        if (!ids.length) {
            alert('Pilih minimal satu lemari untuk dihapus!');
            return;
        }

        const num1 = Math.floor(Math.random() * 10) + 1;
        const num2 = Math.floor(Math.random() * 10) + 1;
        const correctAnswer = num1 + num2;
        
        document.getElementById('deleteMultipleLockerIds').value = JSON.stringify(ids);
        document.getElementById('deleteMultipleLockerCorrectAnswer').value = correctAnswer;
        document.getElementById('deleteMultipleLockerMessage').innerHTML = 
            `Anda akan menghapus <strong>${ids.length}</strong> lemari.<br>` +
            `Untuk konfirmasi, jawab pertanyaan berikut:<br>` +
            `<span class="fw-bold">${num1} + ${num2} = ?</span>`;
        document.getElementById('deleteMultipleAnswer').value = '';
        
        if (deleteMultipleLockerModal) {
            deleteMultipleLockerModal.show();
        }
    }

    document.getElementById('confirmDeleteMultipleLockerBtn').addEventListener('click', function() {
        const answer = parseInt(document.getElementById('deleteMultipleAnswer').value, 10);
        const correctAnswer = parseInt(document.getElementById('deleteMultipleLockerCorrectAnswer').value, 10);
        const idsJson = document.getElementById('deleteMultipleLockerIds').value;
        
        if (isNaN(answer) || answer !== correctAnswer) {
            alert('Jawaban salah! Penghapusan dibatalkan.');
            return;
        }
        
        const ids = JSON.parse(idsJson);
        
        fetch('delete_multiple.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        })
            .then(response => response.json())
            .then(data => {
                if (deleteMultipleLockerModal) {
                    deleteMultipleLockerModal.hide();
                }
                if (data.success) {
                    alert(`Berhasil menghapus ${data.deleted_count} lemari${data.failed_count > 0 ? '. ' + data.failed_count + ' gagal dihapus.' : ''}`);
                    if (data.errors && data.errors.length > 0) {
                        alert('Alasan gagal:\n' + data.errors.join('\n'));
                    }
                    location.reload();
                } else {
                    alert('Gagal menghapus lemari: ' + (data.message || 'Unknown error'));
                    if (data.errors && data.errors.length > 0) {
                        alert('Alasan gagal:\n' + data.errors.join('\n'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus lemari');
            });
    });
</script>
</body>
</html>
