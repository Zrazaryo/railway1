<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Halaman untuk admin atau superadmin
require_login();
if (!is_admin() && !is_superadmin()) {
    header('Location: ../index.php');
    exit();
}

$user_id = (int)($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: index.php?error=User tidak ditemukan');
    exit();
}

$error_message = '';
$user = null;
$document_stats = [
    'total' => 0,
    'active_total' => 0,
    'selected_day_total' => 0
];
$category_counts = [];
$documents = [];

// Date filter (single day)
$selected_date_input = $_GET['date'] ?? '';
$show_all = empty($selected_date_input); // Show all documents if no date is selected
$default_date = new DateTime();
$selected_date = DateTime::createFromFormat('Y-m-d', $selected_date_input) ?: clone $default_date;

if ($show_all) {
    $start_datetime = null;
    $end_datetime = null;
    $period_label = 'Semua Tanggal';
} else {
    $start_datetime = $selected_date->format('Y-m-d 00:00:00');
    $end_datetime = $selected_date->format('Y-m-d 23:59:59');
    $period_label = $selected_date->format('d M Y');
}

try {
    $user = $db->fetch("SELECT id, full_name, username, email, role, status, created_at FROM users WHERE id = ?", [$user_id]);
    
    if (!$user) {
        header('Location: index.php?error=User tidak ditemukan');
        exit();
    }
    
    // Statistik dokumen aktif untuk tanggal yang dipilih (menggunakan original creator)
    if ($show_all) {
        // Show all documents when no date is selected
        $document_stats = $db->fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_total
            FROM documents
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?))
        ", [$user_id, $user_id]);
    } else {
        // Show documents for specific date
        $document_stats = $db->fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_total
            FROM documents
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) AND created_at BETWEEN ? AND ?
        ", [$user_id, $user_id, $start_datetime, $end_datetime]);
    }
    
    // Statistik dokumen pemusnahan untuk tanggal yang dipilih (menggunakan original creator)
    if ($show_all) {
        $destruction_stats = $db->fetch("
            SELECT 
                COUNT(*) AS total_destroyed
            FROM documents
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) AND status = 'deleted'
        ", [$user_id, $user_id]);
    } else {
        $destruction_stats = $db->fetch("
            SELECT 
                COUNT(*) AS total_destroyed
            FROM documents
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) AND status = 'deleted' AND created_at BETWEEN ? AND ?
        ", [$user_id, $user_id, $start_datetime, $end_datetime]);
    }
    
    // Total keseluruhan (semua waktu) - menggunakan original creator
    $overall_stats = $db->fetch("
        SELECT 
            COUNT(CASE WHEN status = 'active' THEN 1 END) AS total_active,
            COUNT(CASE WHEN status = 'deleted' THEN 1 END) AS total_destroyed,
            COUNT(*) AS total_all
        FROM documents
        WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?))
    ", [$user_id, $user_id]);
    
    $document_stats['overall_total'] = $overall_stats['total_active'] ?? 0;
    $document_stats['overall_destroyed'] = $overall_stats['total_destroyed'] ?? 0;
    $document_stats['overall_all'] = $overall_stats['total_all'] ?? 0;
    $document_stats['selected_day_destroyed'] = $destruction_stats['total_destroyed'] ?? 0;
    
    if ($show_all) {
        $category_counts = $db->fetchAll("
            SELECT citizen_category, COUNT(*) AS total
            FROM documents
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?))
            GROUP BY citizen_category
        ", [$user_id, $user_id]);
    } else {
        $category_counts = $db->fetchAll("
            SELECT citizen_category, COUNT(*) AS total
            FROM documents
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) AND created_at BETWEEN ? AND ?
            GROUP BY citizen_category
        ", [$user_id, $user_id, $start_datetime, $end_datetime]);
    }
    
    // Ambil dokumen aktif untuk user ini berdasarkan tanggal yang dipilih
    if ($show_all) {
        $all_active_documents = $db->fetchAll("
            SELECT 
                id,
                document_number,
                full_name,
                nik,
                passport_number,
                citizen_category,
                status,
                created_at,
                month_number,
                document_order_number
            FROM documents
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) AND status = 'active'
            ORDER BY created_at DESC
            LIMIT 50
        ", [$user_id, $user_id]);
    } else {
        $all_active_documents = $db->fetchAll("
            SELECT 
                id,
                document_number,
                full_name,
                nik,
                passport_number,
                citizen_category,
                status,
                created_at,
                month_number,
                document_order_number
            FROM documents
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) AND status = 'active' AND created_at BETWEEN ? AND ?
            ORDER BY created_at DESC
            LIMIT 50
        ", [$user_id, $user_id, $start_datetime, $end_datetime]);
    }
    
} catch (Exception $e) {
    $error_message = 'Error mengambil detail laporan: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - Sistem Arsip Dokumen</title>
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
                    <div>
                        <h1 class="h2">
                            <i class="fas fa-user me-2"></i>
                            Detail Laporan
                        </h1>
                        <p class="text-muted mb-0">
                            Ringkasan aktivitas dokumen milik <?php echo e($user['full_name']); ?>
                        </p>
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Kembali ke Laporan
                    </a>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form class="row g-3 align-items-end" method="GET">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <div class="col-sm-6 col-md-4">
                                <label for="date" class="form-label text-muted small">Pilih Tanggal</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo $selected_date_input; ?>">
                            </div>
                            <div class="col-sm-3 col-md-2 d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>
                                    Terapkan
                                </button>
                            </div>
                            <div class="col-sm-3 col-md-2 d-grid">
                                <a href="detail.php?user_id=<?php echo $user_id; ?>" class="btn btn-outline-secondary">
                                    Reset
                                </a>
                            </div>
                        </form>
                        <p class="text-muted small mb-0 mt-3">
                            Menampilkan data tanggal <?php echo e($period_label); ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-id-card me-2"></i>
                                    Profil User
                                </h5>
                            </div>
                            <div class="card-body">
                                <h4><?php echo e($user['full_name']); ?></h4>
                                <p class="text-muted mb-2">@<?php echo e($user['username']); ?></p>
                                <p class="mb-1">
                                    <i class="fas fa-envelope me-2 text-secondary"></i>
                                    <?php echo e($user['email'] ?? '-'); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-user-tag me-2 text-secondary"></i>
                                    <?php echo $user['role'] === 'admin' ? '<span class="badge bg-danger">Admin</span>' : '<span class="badge bg-info">Staff</span>'; ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-toggle-on me-2 text-secondary"></i>
                                    <?php echo $user['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?>
                                </p>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-clock me-2"></i>
                                    Bergabung sejak <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                </p>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h6 class="text-success mb-1"><?php echo number_format($document_stats['overall_total']); ?></h6>
                                        <small class="text-muted">Dokumen Aktif</small>
                                    </div>
                                    <div class="col-4">
                                        <h6 class="text-danger mb-1"><?php echo number_format($document_stats['overall_destroyed']); ?></h6>
                                        <small class="text-muted">Dokumen Pemusnahan</small>
                                    </div>
                                    <div class="col-4">
                                        <h6 class="text-primary mb-1"><?php echo number_format($document_stats['overall_all']); ?></h6>
                                        <small class="text-muted">Total Semua</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Distribusi Kategori
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($category_counts)): ?>
                                    <p class="text-muted mb-0">Belum ada dokumen</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($category_counts as $category): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><?php echo e($category['citizen_category'] ?? 'Tidak diketahui'); ?></span>
                                                <span class="badge bg-primary rounded-pill">
                                                    <?php echo number_format($category['total'] ?? 0); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card text-white bg-primary mb-3 shadow-sm">
                                    <div class="card-body">
                                        <p class="card-title text-uppercase small mb-1">
                                            <?php echo $show_all ? 'Total Dokumen (Semua Tanggal)' : 'Total Dokumen (Tanggal)'; ?>
                                        </p>
                                        <h3 class="card-text mb-0">
                                            <?php echo number_format($document_stats['active_total'] ?? 0); ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-success mb-3 shadow-sm">
                                    <div class="card-body">
                                        <p class="card-title text-uppercase small mb-1">Total Dokumen Aktif</p>
                                        <h3 class="card-text mb-0">
                                            <?php echo number_format($document_stats['overall_total'] ?? 0); ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-danger mb-3 shadow-sm">
                                    <div class="card-body">
                                        <p class="card-title text-uppercase small mb-1">Total Dokumen Pemusnahan</p>
                                        <h3 class="card-text mb-0">
                                            <?php echo number_format($document_stats['overall_destroyed'] ?? 0); ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-info mb-3 shadow-sm">
                                    <div class="card-body">
                                        <p class="card-title text-uppercase small mb-1">Total Dokumen Keseluruhan</p>
                                        <h3 class="card-text mb-0">
                                            <?php echo number_format($document_stats['overall_all'] ?? 0); ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-folder-open me-2"></i>
                                    Dokumen Aktif
                                </h5>
                                <div class="text-end">
                                    <span class="text-muted small d-block">Tanggal: <?php echo e($period_label); ?></span>
                                    <span class="text-muted small">
                                        <?php echo $show_all ? 'Semua dokumen aktif' : 'Dokumen aktif pada tanggal ini'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($all_active_documents)): ?>
                                    <p class="text-muted mb-0">
                                        <?php if ($show_all): ?>
                                            Tidak ada dokumen aktif.
                                        <?php else: ?>
                                            Tidak ada dokumen aktif yang dibuat pada tanggal <?php echo e($period_label); ?>.
                                        <?php endif; ?>
                                    </p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="5%">No</th>
                                                    <th>Kode Dokumen</th>
                                                    <th>Nama Pemohon</th>
                                                    <th>NIK</th>
                                                    <th>No Passport</th>
                                                    <th>Kategori</th>
                                                    <th>Dibuat</th>
                                                    <th width="10%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no = 1; foreach ($all_active_documents as $document): ?>
                                                    <tr>
                                                        <td><?php echo $no++; ?></td>
                                                        <td>
                                                            <?php
                                                                $kodeDokumen = '-';
                                                                if (!empty($document['month_number']) && $document['document_order_number'] !== null) {
                                                                    $kodeDokumen = $document['month_number'] . '.' . $document['document_order_number'];
                                                                }
                                                            ?>
                                                            <span class="badge bg-primary"><?php echo e($kodeDokumen); ?></span>
                                                        </td>
                                                        <td class="fw-semibold"><?php echo e($document['full_name']); ?></td>
                                                        <td><?php echo e($document['nik']); ?></td>
                                                        <td><?php echo e($document['passport_number']); ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo e($document['citizen_category'] ?? 'WNI'); ?></span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted"><?php echo date('d M Y H:i', strtotime($document['created_at'])); ?></small>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    onclick="viewDocument(<?php echo $document['id']; ?>)" 
                                                                    title="Lihat Detail">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if (count($all_active_documents) >= 50): ?>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <?php if ($show_all): ?>
                                                Menampilkan maksimal 50 dokumen aktif terbaru. Gunakan filter tanggal untuk melihat dokumen pada periode tertentu.
                                            <?php else: ?>
                                                Menampilkan maksimal 50 dokumen untuk tanggal <?php echo e($period_label); ?>. Gunakan filter tanggal lain untuk melihat dokumen pada periode berbeda.
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDocument(id) {
            // Show loading
            const modalBody = document.getElementById('documentDetails');
            modalBody.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br><small class="text-muted">Memuat detail dokumen...</small></div>';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewDocumentModal'));
            modal.show();
            
            // Fetch document details
            fetch(`../documents/view.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = data.html;
                    } else {
                        modalBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Gagal memuat detail dokumen: ' + (data.message || 'Unknown error') + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan saat memuat dokumen</div>';
                });
        }
    </script>
    
    <!-- Modal untuk detail dokumen -->
    <div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDocumentModalLabel">
                        <i class="fas fa-file-alt me-2"></i>
                        Detail Dokumen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="documentDetails" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
