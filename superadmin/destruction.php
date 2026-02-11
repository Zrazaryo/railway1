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
$destruction_data = [];

// Filter parameters
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

try {
    // Get destruction documents grouped by locker/rack
    $sql_destruction = "
        SELECT 
            d.month_number as rack_name,
            COUNT(*) as total_documents,
            d.document_year,
            MIN(d.created_at) as first_document,
            MAX(d.created_at) as last_document,
            GROUP_CONCAT(DISTINCT u.full_name) as created_by_users
        FROM documents d
        LEFT JOIN users u ON d.created_by = u.id
        WHERE d.status = 'deleted' AND d.document_year = ?
        GROUP BY d.month_number, d.document_year
        ORDER BY d.month_number ASC
    ";
    $destruction_data = $db->fetchAll($sql_destruction, [$year_filter]);

    // Get overall statistics for the year
    $total_destroyed = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'deleted' AND document_year = ?", [$year_filter])['count'] ?? 0;
    $total_wna_destroyed = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'deleted' AND document_year = ? AND citizen_category = 'WNA'", [$year_filter])['count'] ?? 0;
    $total_wni_destroyed = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'deleted' AND document_year = ? AND citizen_category = 'WNI'", [$year_filter])['count'] ?? 0;

    // Get available years
    $available_years = $db->fetchAll("SELECT DISTINCT document_year FROM documents WHERE status = 'deleted' ORDER BY document_year DESC");

    // Get monthly destruction stats for the year
    $monthly_stats = $db->fetchAll("
        SELECT 
            MONTH(created_at) as month,
            COUNT(*) as count
        FROM documents 
        WHERE status = 'deleted' AND document_year = ?
        GROUP BY MONTH(created_at)
        ORDER BY month
    ", [$year_filter]);

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lemari Pemusnahan - Superadmin</title>
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
                        <i class="fas fa-trash-alt me-2"></i>Lemari Pemusnahan
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <select class="form-select" onchange="filterByYear(this.value)">
                                <?php foreach ($available_years as $year_data): ?>
                                    <option value="<?php echo $year_data['document_year']; ?>" 
                                            <?php echo $year_data['document_year'] == $year_filter ? 'selected' : ''; ?>>
                                        Tahun <?php echo $year_data['document_year']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Pemusnahan</h6>
                                        <h4><?php echo number_format($total_destroyed); ?></h4>
                                        <small>Tahun <?php echo $year_filter; ?></small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-trash-alt fa-2x"></i>
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
                                        <h6 class="card-title">WNA Dimusnahkan</h6>
                                        <h4><?php echo number_format($total_wna_destroyed); ?></h4>
                                        <small><?php echo $total_destroyed > 0 ? round(($total_wna_destroyed / $total_destroyed) * 100, 1) : 0; ?>%</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-globe fa-2x"></i>
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
                                        <h6 class="card-title">WNI Dimusnahkan</h6>
                                        <h4><?php echo number_format($total_wni_destroyed); ?></h4>
                                        <small><?php echo $total_destroyed > 0 ? round(($total_wni_destroyed / $total_destroyed) * 100, 1) : 0; ?>%</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-flag fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Rak Pemusnahan</h6>
                                        <h4><?php echo number_format(count($destruction_data)); ?></h4>
                                        <small>Rak aktif</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-archive fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Destruction Data Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Rak Pemusnahan - Tahun <?php echo $year_filter; ?>
                            <span class="badge bg-secondary ms-2"><?php echo number_format(count($destruction_data)); ?> rak</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Rak</th>
                                        <th>Total Dokumen</th>
                                        <th>Tahun Dokumen</th>
                                        <th>Dokumen Pertama</th>
                                        <th>Dokumen Terakhir</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($destruction_data)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-trash-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Tidak ada data pemusnahan untuk tahun <?php echo $year_filter; ?>.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($destruction_data as $data): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td class="fw-semibold"><?php echo e($data['rack_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo number_format($data['total_documents']); ?></span>
                                                </td>
                                                <td><?php echo $data['document_year']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($data['first_document'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($data['last_document'])); ?></td>
                                                <td>
                                                    <small><?php echo e($data['created_by_users'] ?? '-'); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="../lockers/detail_pemusnahan.php?code=<?php echo urlencode($data['rack_name']); ?>&year=<?php echo $data['document_year']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Lihat Detail" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                                onclick="showDestructionStats('<?php echo e($data['rack_name']); ?>', <?php echo $data['document_year']; ?>)" 
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

                <!-- Monthly Stats Chart -->
                <?php if (!empty($monthly_stats)): ?>
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Statistik Pemusnahan Bulanan - Tahun <?php echo $year_filter; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                            $monthly_data = array_fill(1, 12, 0);
                            foreach ($monthly_stats as $stat) {
                                $monthly_data[$stat['month']] = $stat['count'];
                            }
                            $max_count = max($monthly_data);
                            ?>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <div class="col-md-1 mb-3">
                                    <div class="text-center">
                                        <div class="progress" style="height: 100px; width: 20px; margin: 0 auto;">
                                            <div class="progress-bar bg-danger" 
                                                 role="progressbar" 
                                                 style="height: <?php echo $max_count > 0 ? ($monthly_data[$i] / $max_count) * 100 : 0; ?>%"
                                                 title="<?php echo $monthly_data[$i]; ?> dokumen">
                                            </div>
                                        </div>
                                        <small class="mt-2 d-block"><?php echo $months[$i-1]; ?></small>
                                        <small class="text-muted"><?php echo $monthly_data[$i]; ?></small>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Destruction Stats Modal -->
    <div class="modal fade" id="destructionStatsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Statistik Pemusnahan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="destructionStatsContent">
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
        function filterByYear(year) {
            window.location.href = '?year=' + year;
        }

        function showDestructionStats(rackName, year) {
            document.getElementById('destructionStatsContent').innerHTML = `
                <div class="text-center">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <h5>Statistik Rak: ${rackName}</h5>
                    <p class="text-muted">Tahun: ${year}</p>
                    <p><small>Statistik detail pemusnahan akan ditampilkan di sini.</small></p>
                    <p><small>Fitur ini dapat dikembangkan untuk menampilkan breakdown per kategori, asal dokumen, dll.</small></p>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('destructionStatsModal')).show();
        }
    </script>
</body>
</html>