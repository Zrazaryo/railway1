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

try {
    // Get comprehensive statistics
    
    // Document statistics
    $total_documents = $db->fetch("SELECT COUNT(*) as count FROM documents")['count'] ?? 0;
    $active_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status != 'deleted'")['count'] ?? 0;
    $deleted_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'deleted'")['count'] ?? 0;
    
    // User statistics
    $total_users = $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'] ?? 0;
    $admin_users = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'")['count'] ?? 0;
    $staff_users = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'active'")['count'] ?? 0;
    
    // Locker statistics
    $total_lockers = $db->fetch("SELECT COUNT(*) as count FROM lockers")['count'] ?? 0;
    $total_capacity = $db->fetch("SELECT SUM(max_capacity) as total FROM lockers")['total'] ?? 0;
    
    // Documents by category
    $wna_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE citizen_category = 'WNA'")['count'] ?? 0;
    $wni_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE citizen_category = 'WNI'")['count'] ?? 0;
    
    // Documents by origin
    $origin_stats = $db->fetchAll("
        SELECT 
            document_origin,
            COUNT(*) as count,
            COUNT(CASE WHEN status = 'deleted' THEN 1 END) as deleted_count
        FROM documents 
        WHERE document_origin IS NOT NULL AND document_origin != ''
        GROUP BY document_origin
        ORDER BY count DESC
    ");
    
    // Monthly document creation (last 12 months)
    $monthly_creation = $db->fetchAll("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count,
            COUNT(CASE WHEN status = 'deleted' THEN 1 END) as deleted_count
        FROM documents 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    
    // User productivity (documents created per user)
    $user_productivity = $db->fetchAll("
        SELECT 
            u.full_name, u.username, u.role,
            COUNT(d.id) as total_documents,
            COUNT(CASE WHEN d.status = 'deleted' THEN d.id END) as deleted_documents,
            COUNT(CASE WHEN d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN d.id END) as recent_documents
        FROM users u
        LEFT JOIN documents d ON u.id = d.created_by
        WHERE u.status = 'active'
        GROUP BY u.id, u.full_name, u.username, u.role
        ORDER BY total_documents DESC
        LIMIT 10
    ");
    
    // System activity summary
    $login_stats = $db->fetchAll("
        SELECT 
            DATE(created_at) as date,
            COUNT(CASE WHEN action LIKE '%LOGIN%' THEN 1 END) as logins,
            COUNT(CASE WHEN action LIKE '%LOGOUT%' THEN 1 END) as logouts
        FROM logs_activity 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Helper function for document origin
function format_document_origin_label($origin) {
    switch ($origin) {
        case 'imigrasi_jakarta_pusat_kemayoran':
            return 'Imigrasi Jakarta Pusat Kemayoran';
        case 'imigrasi_ulp_semanggi':
            return 'Imigrasi ULP Semanggi';
        case 'imigrasi_lounge_senayan_city':
            return 'Imigrasi Lounge Senayan City';
        default:
            return $origin ?: 'Tidak Diketahui';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/navbar_superadmin.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar_superadmin.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-chart-bar me-2"></i>Laporan Sistem
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportReport()">
                                <i class="fas fa-download"></i> Export PDF
                            </button>
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

                <!-- Overview Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Dokumen</h6>
                                        <h4><?php echo number_format($total_documents); ?></h4>
                                        <small><?php echo number_format($active_documents); ?> aktif, <?php echo number_format($deleted_documents); ?> pemusnahan</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x"></i>
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
                                        <h6 class="card-title">Total User</h6>
                                        <h4><?php echo number_format($total_users); ?></h4>
                                        <small><?php echo number_format($admin_users); ?> admin, <?php echo number_format($staff_users); ?> staff</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
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
                                        <h6 class="card-title">Total Lemari</h6>
                                        <h4><?php echo number_format($total_lockers); ?></h4>
                                        <small>Kapasitas: <?php echo number_format($total_capacity); ?></small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-archive fa-2x"></i>
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
                                        <h4><?php echo $total_capacity > 0 ? round(($active_documents / $total_capacity) * 100, 1) : 0; ?>%</h4>
                                        <small>Kapasitas terpakai</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-pie fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Document Category Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Dokumen per Kategori
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Document Origin Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Dokumen per Asal
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="originChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Creation Trend -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Tren Pembuatan Dokumen (12 Bulan Terakhir)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" width="400" height="100"></canvas>
                    </div>
                </div>

                <div class="row">
                    <!-- User Productivity -->
                    <div class="col-md-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-trophy me-2"></i>Produktivitas User (Top 10)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Role</th>
                                                <th>Total Dokumen</th>
                                                <th>Pemusnahan</th>
                                                <th>30 Hari Terakhir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($user_productivity as $user): ?>
                                                <tr>
                                                    <td><?php echo e($user['full_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'success'; ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($user['total_documents']); ?></td>
                                                    <td><?php echo number_format($user['deleted_documents']); ?></td>
                                                    <td><?php echo number_format($user['recent_documents']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Login Activity -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-sign-in-alt me-2"></i>Aktivitas Login (7 Hari)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($login_stats as $stat): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <small><?php echo date('d/m', strtotime($stat['date'])); ?></small>
                                        <div>
                                            <span class="badge bg-success"><?php echo $stat['logins']; ?> login</span>
                                            <span class="badge bg-secondary"><?php echo $stat['logouts']; ?> logout</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['WNA', 'WNI'],
                datasets: [{
                    data: [<?php echo $wna_documents; ?>, <?php echo $wni_documents; ?>],
                    backgroundColor: ['#FF6384', '#36A2EB']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Origin Chart
        const originCtx = document.getElementById('originChart').getContext('2d');
        new Chart(originCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($o) { return '"' . format_document_origin_label($o['document_origin']) . '"'; }, $origin_stats)); ?>],
                datasets: [{
                    label: 'Total Dokumen',
                    data: [<?php echo implode(',', array_column($origin_stats, 'count')); ?>],
                    backgroundColor: '#36A2EB'
                }, {
                    label: 'Pemusnahan',
                    data: [<?php echo implode(',', array_column($origin_stats, 'deleted_count')); ?>],
                    backgroundColor: '#FF6384'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($m) { return '"' . $m['month'] . '"'; }, array_reverse($monthly_creation))); ?>],
                datasets: [{
                    label: 'Dokumen Dibuat',
                    data: [<?php echo implode(',', array_column(array_reverse($monthly_creation), 'count')); ?>],
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    fill: true
                }, {
                    label: 'Pemusnahan',
                    data: [<?php echo implode(',', array_column(array_reverse($monthly_creation), 'deleted_count')); ?>],
                    borderColor: '#FF6384',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function exportReport() {
            alert('Fitur export PDF akan dikembangkan lebih lanjut.');
        }
    </script>
</body>
</html>