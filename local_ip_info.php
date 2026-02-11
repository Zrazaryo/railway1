<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login
require_login();

$error_message = '';
$success_message = '';

// Get current user's local IP detections
try {
    $detections = $db->fetchAll("
        SELECT * FROM local_ip_detections 
        WHERE user_id = ? OR session_id = ?
        ORDER BY created_at DESC 
        LIMIT 10
    ", [$_SESSION['user_id'], session_id()]);
    
} catch (Exception $e) {
    $error_message = 'Error mengambil data: ' . $e->getMessage();
    $detections = [];
}

// Get statistics
try {
    $stats = $db->fetch("
        SELECT 
            COUNT(*) as total_detections,
            SUM(total_local_ips) as total_ips_detected,
            SUM(ipv4_count) as total_ipv4,
            SUM(ipv6_count) as total_ipv6,
            SUM(local_count) as total_local,
            SUM(public_count) as total_public,
            MAX(created_at) as last_detection
        FROM local_ip_detections 
        WHERE user_id = ? OR session_id = ?
    ", [$_SESSION['user_id'], session_id()]);
    
} catch (Exception $e) {
    $stats = [
        'total_detections' => 0,
        'total_ips_detected' => 0,
        'total_ipv4' => 0,
        'total_ipv6' => 0,
        'total_local' => 0,
        'total_public' => 0,
        'last_detection' => null
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi IP Lokal - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="logged-in">
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-network-wired me-2"></i>
                        Informasi IP Lokal
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" onclick="detectCurrentIPs()">
                            <i class="fas fa-search me-2"></i>Deteksi IP Sekarang
                        </button>
                    </div>
                </div>
                
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
                
                <!-- Current Detection Status -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-radar me-2"></i>
                                    Status Deteksi IP Saat Ini
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
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-chart-line fa-2x text-primary mb-2"></i>
                                <h5 class="card-title"><?php echo number_format($stats['total_detections']); ?></h5>
                                <p class="card-text">Total Deteksi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-network-wired fa-2x text-success mb-2"></i>
                                <h5 class="card-title"><?php echo number_format($stats['total_ips_detected']); ?></h5>
                                <p class="card-text">Total IP Terdeteksi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-globe fa-2x text-info mb-2"></i>
                                <h5 class="card-title"><?php echo number_format($stats['total_ipv4']); ?> / <?php echo number_format($stats['total_ipv6']); ?></h5>
                                <p class="card-text">IPv4 / IPv6</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-home fa-2x text-warning mb-2"></i>
                                <h5 class="card-title"><?php echo number_format($stats['total_local']); ?></h5>
                                <p class="card-text">IP Lokal</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detection History -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2"></i>
                                    Riwayat Deteksi IP
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($detections)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                                        <p>Belum ada riwayat deteksi IP lokal.</p>
                                        <button type="button" class="btn btn-primary" onclick="detectCurrentIPs()">
                                            <i class="fas fa-search me-2"></i>Mulai Deteksi
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Waktu</th>
                                                    <th>Server IP</th>
                                                    <th>IP Lokal</th>
                                                    <th>Statistik</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($detections as $detection): ?>
                                                    <tr>
                                                        <td>
                                                            <small>
                                                                <?php echo format_date_indonesia($detection['created_at'], true); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php echo format_ip_info($detection['server_detected_ip']); ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo $detection['total_local_ips']; ?> IP</span>
                                                            <?php if ($detection['ipv4_count'] > 0): ?>
                                                                <span class="badge bg-info"><?php echo $detection['ipv4_count']; ?> IPv4</span>
                                                            <?php endif; ?>
                                                            <?php if ($detection['ipv6_count'] > 0): ?>
                                                                <span class="badge bg-success"><?php echo $detection['ipv6_count']; ?> IPv6</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                Lokal: <?php echo $detection['local_count']; ?><br>
                                                                Public: <?php echo $detection['public_count']; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                                    onclick="showDetectionDetail(<?php echo $detection['id']; ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Detection Detail Modal -->
    <div class="modal fade" id="detectionDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Detail Deteksi IP
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detection-detail-content">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/local-ip-detector.js"></script>
    <script>
        // Auto-detect IPs on page load
        document.addEventListener('DOMContentLoaded', function() {
            detectCurrentIPs();
        });

        // Detect current IPs
        function detectCurrentIPs() {
            const statusDiv = document.getElementById('current-detection-status');
            const resultsDiv = document.getElementById('current-ip-results');
            
            statusDiv.style.display = 'block';
            resultsDiv.style.display = 'none';
            
            window.localIPDetector.detectLocalIPs().then(ips => {
                displayCurrentResults(ips);
                
                // Send to server
                window.localIPDetector.sendToServer().then(result => {
                    if (result && result.success) {
                        console.log('IP data sent to server successfully');
                        // Refresh page after 2 seconds to show updated history
                        setTimeout(() => {
                            location.reload();
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

        // Show detection detail
        function showDetectionDetail(detectionId) {
            const modal = new bootstrap.Modal(document.getElementById('detectionDetailModal'));
            const content = document.getElementById('detection-detail-content');
            
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Fetch detection details
            fetch('/api/get_detection_detail.php', {
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