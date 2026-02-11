<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/search_template.php';

// Cek login dan role admin atau superadmin
require_login();
if (!is_admin() && !is_superadmin()) {
    header('Location: ../index.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search parameter
$search = sanitize_input($_GET['search'] ?? '');

// Sort parameter
$sort_param = $_GET['sort'] ?? 'created_at_desc';

// Parse sort parameter
if ($sort_param === 'created_at_desc') {
    $sort_by = 'al.created_at';
    $sort_order = 'DESC';
} elseif ($sort_param === 'created_at_asc') {
    $sort_by = 'al.created_at';
    $sort_order = 'ASC';
} elseif ($sort_param === 'name_asc') {
    $sort_by = 'u.full_name';
    $sort_order = 'ASC';
} else {
    $sort_by = 'al.created_at';
    $sort_order = 'DESC';
}

// Build query with search filter
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total logs
try {
    $count_sql = "SELECT COUNT(*) as count 
                  FROM activity_logs al 
                  JOIN users u ON al.user_id = u.id 
                  $where_clause";
    $total_logs = $db->fetch($count_sql, $params)['count'];
    $total_pages = ceil($total_logs / $per_page);
    
    // Get logs with pagination
    $logs_sql = "SELECT al.*, u.full_name, u.username 
                 FROM activity_logs al 
                 JOIN users u ON al.user_id = u.id 
                 $where_clause
                 ORDER BY $sort_by $sort_order 
                 LIMIT ? OFFSET ?";
    
    $logs_params = array_merge($params, [$per_page, $offset]);
    $logs = $db->fetchAll($logs_sql, $logs_params);
    
} catch (Exception $e) {
    $error_message = 'Error mengambil data log: ' . $e->getMessage();
    $logs = [];
    $total_logs = 0;
    $total_pages = 0;
}

// Get IP and device statistics for monitoring tab
try {
    // Get unique IP statistics
    $ip_stats = $db->fetchAll("
        SELECT ip_address, COUNT(*) as access_count, 
               MAX(created_at) as last_access,
               COUNT(DISTINCT user_id) as unique_users
        FROM activity_logs 
        WHERE ip_address IS NOT NULL AND ip_address != '' AND ip_address != 'Unknown'
        GROUP BY ip_address 
        ORDER BY access_count DESC 
        LIMIT 5
    ");
    
    // Get OS/Device statistics
    $device_stats = $db->fetchAll("
        SELECT user_agent, COUNT(*) as access_count,
               MAX(created_at) as last_access
        FROM activity_logs 
        WHERE user_agent IS NOT NULL AND user_agent != ''
        GROUP BY user_agent 
        ORDER BY access_count DESC 
        LIMIT 10
    ");
} catch (Exception $e) {
    $ip_stats = [];
    $device_stats = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .text-purple { color: #6f42c1 !important; }
        .bg-purple { background-color: #6f42c1 !important; }
        .badge.bg-purple { color: white !important; }
    </style>
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
                        <i class="fas fa-history me-2"></i>
                        Log Aktivitas & IP Monitoring
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-outline-primary me-2" onclick="detectCurrentIPs()">
                            <i class="fas fa-network-wired me-2"></i>Deteksi IP Lokal
                        </button>
                        <span class="text-muted">Total: <?php echo number_format($total_logs); ?> log</span>
                    </div>
                </div>
                
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="logTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="activity-logs-tab" data-bs-toggle="tab" data-bs-target="#activity-logs" type="button" role="tab">
                            <i class="fas fa-history me-2"></i>Log Aktivitas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ip-monitoring-tab" data-bs-toggle="tab" data-bs-target="#ip-monitoring" type="button" role="tab">
                            <i class="fas fa-network-wired me-2"></i>IP Monitoring
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="local-ip-detection-tab" data-bs-toggle="tab" data-bs-target="#local-ip-detection" type="button" role="tab">
                            <i class="fas fa-radar me-2"></i>Deteksi IP Lokal
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="logTabsContent">
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tab 1: Activity Logs -->
                <div class="tab-pane fade show active" id="activity-logs" role="tabpanel">
                    <!-- Search Form -->
                    <?php
                    render_search_form([
                        'search_placeholder' => 'Cari berdasarkan nama atau username...',
                        'search_value' => $search,
                        'sort_value' => $sort_param,
                        'show_category_filter' => false,
                        'show_advanced_search' => false,
                        'refresh_url' => 'index.php',
                        'sort_options' => [
                            'created_at_desc' => 'Terbaru',
                            'created_at_asc' => 'Terlama',
                            'name_asc' => 'Nama'
                        ]
                    ]);
                    ?>
                    
                    <?php if (!empty($search)): ?>
                    <div class="mb-3">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Hapus Filter
                        </a>
                        <small class="text-muted ms-2">
                            Menampilkan <?php echo number_format($total_logs); ?> hasil untuk "<?php echo e($search); ?>"
                        </small>
                    </div>
                    <?php endif; ?>

                <!-- Activity Logs Table -->
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Deskripsi</th>
                                        <th>IP Address</th>
                                        <th>Device Info</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo format_date_indonesia($log['created_at'], true); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo e($log['full_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo e($log['username']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo e($log['action']); ?></span>
                                            </td>
                                            <td><?php echo e($log['description']); ?></td>
                                            <td><?php echo format_ip_info($log['ip_address'], $log['user_agent']); ?></td>
                                            <td><?php echo parse_user_agent($log['user_agent']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Tidak ada data log</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Log pagination">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $pagination_params = [];
                                    if (!empty($search)) {
                                        $pagination_params['search'] = $search;
                                    }
                                    if (!empty($sort_param) && $sort_param !== 'created_at_desc') {
                                        $pagination_params['sort'] = $sort_param;
                                    }
                                    $pagination_query = !empty($pagination_params) ? '&' . http_build_query($pagination_params) : '';
                                    ?>
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $pagination_query; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $pagination_query; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $pagination_query; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
                </div> <!-- End Activity Logs Tab -->
                
                <!-- Tab 2: IP Monitoring -->
                <div class="tab-pane fade" id="ip-monitoring" role="tabpanel">
                    <div class="row mb-4">
                        <!-- IP Statistics -->
                        <?php if (!empty($ip_stats)): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Top 5 IP Address Aktif</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>IP Address</th>
                                                    <th>Akses</th>
                                                    <th>Users</th>
                                                    <th>Terakhir</th>
                                                    <th>Detail</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ip_stats as $stat): ?>
                                                    <tr>
                                                        <td>
                                                            <?php
                                                            // Deteksi IPv4 atau IPv6
                                                            if (filter_var($stat['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                                                                $ipv6_info = analyze_ipv6($stat['ip_address']);
                                                                $display_ip = strlen($stat['ip_address']) > 15 ? substr($stat['ip_address'], 0, 15) . '...' : $stat['ip_address'];
                                                                
                                                                if ($ipv6_info['is_public']) {
                                                                    echo '<span class="badge bg-success" title="Public IPv6">';
                                                                    echo '<i class="fas fa-globe me-1"></i>' . e($display_ip);
                                                                    echo '</span><br><small class="text-muted">Public IPv6</small>';
                                                                } elseif ($ipv6_info['is_link_local']) {
                                                                    echo '<span class="badge bg-warning" title="Link-Local IPv6">';
                                                                    echo '<i class="fas fa-link me-1"></i>' . e($display_ip);
                                                                    echo '</span><br><small class="text-muted">Link-Local</small>';
                                                                } elseif ($ipv6_info['is_unique_local']) {
                                                                    echo '<span class="badge bg-info" title="Unique Local IPv6">';
                                                                    echo '<i class="fas fa-network-wired me-1"></i>' . e($display_ip);
                                                                    echo '</span><br><small class="text-muted">Unique Local</small>';
                                                                } elseif ($ipv6_info['is_loopback']) {
                                                                    echo '<span class="badge bg-secondary" title="Loopback IPv6">';
                                                                    echo '<i class="fas fa-home me-1"></i>' . e($display_ip);
                                                                    echo '</span><br><small class="text-muted">Loopback</small>';
                                                                } else {
                                                                    echo '<span class="badge bg-purple" title="Special IPv6">';
                                                                    echo '<i class="fas fa-network-wired me-1"></i>' . e($display_ip);
                                                                    echo '</span><br><small class="text-muted">IPv6</small>';
                                                                }
                                                            } elseif (filter_var($stat['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                                                // IPv4 handling
                                                                if (filter_var($stat['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                                                    echo '<span class="badge bg-success" title="Public IPv4">';
                                                                    echo '<i class="fas fa-globe me-1"></i>' . e(substr($stat['ip_address'], 0, 12));
                                                                    echo '</span><br><small class="text-muted">Public IPv4</small>';
                                                                } elseif (filter_var($stat['ip_address'], FILTER_VALIDATE_IP)) {
                                                                    echo '<span class="badge bg-warning" title="Private IPv4">';
                                                                    echo '<i class="fas fa-network-wired me-1"></i>' . e(substr($stat['ip_address'], 0, 12));
                                                                    echo '</span><br><small class="text-muted">Private IPv4</small>';
                                                                }
                                                            } else {
                                                                echo '<span class="badge bg-secondary">';
                                                                echo e(substr($stat['ip_address'], 0, 12));
                                                                echo '</span><br><small class="text-muted">Unknown</small>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo number_format($stat['access_count']); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $stat['unique_users']; ?></span>
                                                        </td>
                                                        <td>
                                                            <small><?php echo format_date_indonesia($stat['last_access'], true); ?></small>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                                    onclick="showIPDetail('<?php echo addslashes($stat['ip_address']); ?>', '')"
                                                                    title="Lihat detail IP">
                                                                <i class="fas fa-info-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Device Statistics -->
                        <?php if (!empty($device_stats)): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-desktop me-2"></i>Top Device/Browser</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Device Info</th>
                                                    <th>Akses</th>
                                                    <th>Terakhir</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($device_stats, 0, 5) as $device): ?>
                                                    <tr>
                                                        <td>
                                                            <small><?php echo parse_user_agent($device['user_agent']); ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo number_format($device['access_count']); ?></span>
                                                        </td>
                                                        <td>
                                                            <small><?php echo format_date_indonesia($device['last_access'], true); ?></small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- IP Analysis Charts -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Analisis IP Address</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Get IP type statistics
                                    try {
                                        $ip_type_stats = $db->fetchAll("
                                            SELECT 
                                                CASE 
                                                    WHEN ip_address LIKE '%:%' THEN 'IPv6'
                                                    WHEN ip_address LIKE '%.%.%.%' THEN 'IPv4'
                                                    ELSE 'Unknown'
                                                END as ip_type,
                                                COUNT(*) as count,
                                                COUNT(DISTINCT ip_address) as unique_ips
                                            FROM activity_logs 
                                            WHERE ip_address IS NOT NULL AND ip_address != '' AND ip_address != 'Unknown'
                                            GROUP BY ip_type
                                        ");
                                    } catch (Exception $e) {
                                        $ip_type_stats = [];
                                    }
                                    ?>
                                    
                                    <?php if (!empty($ip_type_stats)): ?>
                                        <div class="row">
                                            <?php foreach ($ip_type_stats as $stat): ?>
                                                <div class="col-md-4 text-center">
                                                    <h4 class="text-primary"><?php echo number_format($stat['count']); ?></h4>
                                                    <p class="mb-1"><?php echo e($stat['ip_type']); ?> Requests</p>
                                                    <small class="text-muted"><?php echo number_format($stat['unique_ips']); ?> unique IPs</small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p>Belum ada data statistik IP</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- End IP Monitoring Tab -->
                
                <!-- Tab 3: Local IP Detection -->
                <div class="tab-pane fade" id="local-ip-detection" role="tabpanel">
                    <!-- Current Detection Status -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-radar me-2"></i>
                                        Status Deteksi IP Lokal Saat Ini
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="current-detection-status">
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Mendeteksi...</span>
                                            </div>
                                            <p class="mt-2">Mendeteksi IP lokal perangkat Anda...</p>
                                        </div>
                                    </div>
                                    
                                    <div id="current-ip-results" style="display: none;">
                                        <h6>IP Address yang Terdeteksi:</h6>
                                        <div id="detected-ips-list"></div>
                                        
                                        <h6 class="mt-3">Informasi Network:</h6>
                                        <div id="network-info"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- End Local IP Detection Tab -->
                
                </div> <!-- End Tab Content -->
            </main>
        </div>
    </div>
    
    <!-- IP Detail Modal -->
    <div class="modal fade" id="ipDetailModal" tabindex="-1" aria-labelledby="ipDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ipDetailModalLabel">
                        <i class="fas fa-info-circle me-2"></i>Detail IP Address
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="ipDetailContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Mengambil informasi IP address...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/local-ip-detector.js"></script>
    <script>
        // Tab switching handler
        document.addEventListener('DOMContentLoaded', function() {
            const localIpTab = document.getElementById('local-ip-detection-tab');
            localIpTab.addEventListener('shown.bs.tab', function() {
                // Load local IP detection when tab is shown
                loadLocalIPHistory();
                detectCurrentIPs();
            });
        });

        // Detect current IPs
        function detectCurrentIPs() {
            const statusDiv = document.getElementById('current-detection-status');
            const resultsDiv = document.getElementById('current-ip-results');
            
            if (!statusDiv || !resultsDiv) return;
            
            statusDiv.style.display = 'block';
            resultsDiv.style.display = 'none';
            
            window.localIPDetector.detectLocalIPs().then(ips => {
                displayCurrentResults(ips);
                
                // Send to server
                window.localIPDetector.sendToServer().then(result => {
                    if (result && result.success) {
                        console.log('IP data sent to server successfully');
                        // Refresh history after 2 seconds
                        setTimeout(() => {
                            loadLocalIPHistory();
                        }, 2000);
                    }
                });
            });
        }

        // Display current detection results
        function displayCurrentResults(ips) {
            const statusDiv = document.getElementById('current-detection-status');
            const resultsDiv = document.getElementById('current-ip-results');
            const ipsList = document.getElementById('detected-ips-list');
            const networkInfo = document.getElementById('network-info');
            
            if (!statusDiv || !resultsDiv || !ipsList || !networkInfo) return;
            
            statusDiv.style.display = 'none';
            resultsDiv.style.display = 'block';
            
            // Display IPs
            if (ips.length === 0) {
                ipsList.innerHTML = '<div class="alert alert-warning">Tidak ada IP lokal yang terdeteksi.</div>';
            } else {
                let html = '<div class="row">';
                ips.forEach((ipInfo, index) => {
                    const badgeClass = ipInfo.isLocal ? 'bg-warning' : 'bg-success';
                    const typeIcon = ipInfo.type === 'IPv6' ? 'fas fa-network-wired' : 'fas fa-globe';
                    
                    html += `
                        <div class="col-md-6 mb-2">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge ${badgeClass}">
                                                <i class="${typeIcon} me-1"></i>
                                                ${ipInfo.ip}
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                ${ipInfo.type} • ${ipInfo.isLocal ? 'Lokal' : 'Public'} • ${ipInfo.source}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                ipsList.innerHTML = html;
            }
            
            // Display network info
            const info = window.localIPDetector.getFormattedInfo();
            if (info.networkInfo && Object.keys(info.networkInfo).length > 0) {
                let networkHtml = '<div class="row">';
                Object.entries(info.networkInfo).forEach(([key, value]) => {
                    networkHtml += `
                        <div class="col-md-6">
                            <strong>${key}:</strong> ${value}
                        </div>
                    `;
                });
                networkHtml += '</div>';
                networkInfo.innerHTML = networkHtml;
            } else {
                networkInfo.innerHTML = '<div class="text-muted">Informasi network tidak tersedia.</div>';
            }
        }

        // Load local IP detection history
        function loadLocalIPHistory() {
            const historyDiv = document.getElementById('local-ip-history');
            if (!historyDiv) return;
            
            historyDiv.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat riwayat deteksi...</p>
                </div>
            `;
            
            fetch('../api/get_local_ip_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    historyDiv.innerHTML = data.html;
                } else {
                    historyDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                historyDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading history: ${error.message}
                    </div>
                `;
            });
        }

        function showIPDetail(ip, userAgent) {
            const modal = new bootstrap.Modal(document.getElementById('ipDetailModal'));
            const content = document.getElementById('ipDetailContent');
            
            // Reset content
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Mengambil informasi IP address...</p>
                </div>
            `;
            
            modal.show();
            
            // Fetch IP details
            fetch('ip_detail.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ip: ip,
                    user_agent: userAgent
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = data.html;
                } else {
                    content.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Tidak dapat mengambil detail IP address: ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Terjadi kesalahan saat mengambil informasi IP address.
                    </div>
                `;
            });
        }

        // Show local IP detection detail
        function showLocalIPDetail(detectionId) {
            const modal = new bootstrap.Modal(document.getElementById('ipDetailModal'));
            const content = document.getElementById('ipDetailContent');
            
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Fetch detection details
            fetch('../api/get_detection_detail.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    detection_id: detectionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = data.html;
                } else {
                    content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                content.innerHTML = `<div class="alert alert-danger">Error loading details: ${error.message}</div>`;
            });
        }
    </script>
</body>
</html>

















