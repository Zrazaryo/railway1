<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role superadmin
if (!is_logged_in() || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: ../auth/login_superadmin.php');
    exit();
}

$error_message = '';
$lockers = [];

try {
    // Get all lockers with document counts
    $sql_lockers = "
        SELECT 
            l.id, l.code, l.name, l.max_capacity, l.created_at,
            COUNT(CASE WHEN d.status != 'deleted' THEN d.id END) as active_documents,
            COUNT(CASE WHEN d.status = 'deleted' THEN d.id END) as deleted_documents,
            COUNT(d.id) as total_documents
        FROM lockers l
        LEFT JOIN documents d ON d.month_number = l.name
        GROUP BY l.id, l.code, l.name, l.max_capacity, l.created_at
        ORDER BY l.code ASC, l.name ASC
    ";
    $lockers = $db->fetchAll($sql_lockers);

    // Get overall statistics
    $total_lockers = count($lockers);
    $total_capacity = array_sum(array_column($lockers, 'max_capacity'));
    $total_used = array_sum(array_column($lockers, 'active_documents'));
    $usage_percentage = $total_capacity > 0 ? round(($total_used / $total_capacity) * 100, 1) : 0;

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lemari Dokumen - Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar_superadmin.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar_superadmin.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-archive me-2"></i>Lemari Dokumen
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Lemari</h6>
                                        <h4><?php echo number_format($total_lockers); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-archive fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Kapasitas</h6>
                                        <h4><?php echo number_format($total_capacity); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-boxes fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Dokumen Tersimpan</h6>
                                        <h4><?php echo number_format($total_used); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Penggunaan</h6>
                                        <h4><?php echo $usage_percentage; ?>%</h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-pie fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lockers Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Lemari Dokumen
                            <span class="badge bg-secondary ms-2"><?php echo number_format($total_lockers); ?> lemari</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode Lemari</th>
                                        <th>Nama Rak</th>
                                        <th>Kapasitas Maksimal</th>
                                        <th>Dokumen Aktif</th>
                                        <th>Dokumen Pemusnahan</th>
                                        <th>Penggunaan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($lockers)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Tidak ada lemari dokumen.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($lockers as $locker): ?>
                                            <?php 
                                            $usage_percent = $locker['max_capacity'] > 0 ? 
                                                round(($locker['active_documents'] / $locker['max_capacity']) * 100, 1) : 0;
                                            $status_class = $usage_percent >= 90 ? 'danger' : ($usage_percent >= 70 ? 'warning' : 'success');
                                            ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td class="fw-semibold"><?php echo e($locker['code']); ?></td>
                                                <td><?php echo e($locker['name']); ?></td>
                                                <td><?php echo number_format($locker['max_capacity']); ?></td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo number_format($locker['active_documents']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo number_format($locker['deleted_documents']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-<?php echo $status_class; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $usage_percent; ?>%"
                                                             aria-valuenow="<?php echo $usage_percent; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?php echo $usage_percent; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($usage_percent >= 90): ?>
                                                        <span class="badge bg-danger">Penuh</span>
                                                    <?php elseif ($usage_percent >= 70): ?>
                                                        <span class="badge bg-warning">Hampir Penuh</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Tersedia</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="../lockers/detail.php?code=<?php echo urlencode($locker['name']); ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Lihat Detail" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                                onclick="showLockerStats(<?php echo $locker['id']; ?>)" 
                                                                title="Statistik">
                                                            <i class="fas fa-chart-bar"></i>
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

    <!-- Locker Stats Modal -->
    <div class="modal fade" id="lockerStatsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Statistik Lemari</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="lockerStatsContent">
                    <!-- Stats will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        function showLockerStats(lockerId) {
            // For now, show a simple stats view
            // In a real implementation, you would fetch detailed stats via AJAX
            document.getElementById('lockerStatsContent').innerHTML = `
                <div class="text-center">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Statistik detail lemari akan ditampilkan di sini.</p>
                    <p><small>Fitur ini dapat dikembangkan lebih lanjut untuk menampilkan grafik penggunaan, riwayat dokumen, dll.</small></p>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('lockerStatsModal')).show();
        }
    </script>
</body>
</html>