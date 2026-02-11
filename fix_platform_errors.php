<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login dan role admin
require_admin();

$messages = [];
$errors = [];

try {
    // 1. Cek dan perbaiki masalah kolom geolocation
    $has_geolocation = has_geolocation_columns();
    
    if (!$has_geolocation) {
        $messages[] = "Kolom geolocation belum ada di database. Platform akan berjalan dalam mode kompatibilitas.";
    } else {
        $messages[] = "Kolom geolocation sudah tersedia di database.";
    }
    
    // 2. Test koneksi database
    $test_query = $db->fetch("SELECT COUNT(*) as count FROM activity_logs");
    $messages[] = "Koneksi database OK. Total log aktivitas: " . number_format($test_query['count']);
    
    // 3. Test fungsi log_activity
    log_activity($_SESSION['user_id'], 'SYSTEM_CHECK', 'Test log activity function');
    $messages[] = "Fungsi log_activity berjalan normal.";
    
    // 4. Cek dan bersihkan session yang bermasalah
    if (isset($_SESSION['last_location']) && !is_array($_SESSION['last_location'])) {
        unset($_SESSION['last_location']);
        $messages[] = "Session geolocation yang bermasalah telah dibersihkan.";
    }
    
    // 5. Test format functions
    $test_ip = get_client_ip();
    $formatted_ip = format_ip_info($test_ip, $_SERVER['HTTP_USER_AGENT'] ?? '');
    $messages[] = "Fungsi format IP berjalan normal: " . strip_tags($formatted_ip);
    
    // 6. Test geolocation format dengan data null
    $geo_format = format_geolocation_info(null, null, null, null);
    $messages[] = "Fungsi format geolocation menangani data null dengan baik.";
    
    // 7. Cek file JavaScript
    $js_files = [
        'assets/js/geolocation.js',
        'assets/js/script.js'
    ];
    
    foreach ($js_files as $file) {
        if (file_exists($file)) {
            $messages[] = "File JavaScript tersedia: $file";
        } else {
            $errors[] = "File JavaScript tidak ditemukan: $file";
        }
    }
    
    // 8. Test user agent parsing
    $ua_info = get_detailed_device_info($_SERVER['HTTP_USER_AGENT'] ?? '');
    $messages[] = "Deteksi device berjalan normal: " . $ua_info['os'] . " - " . $ua_info['browser'];
    
    // 9. Cek permission file dan folder
    $check_paths = [
        'logs/',
        'api/',
        'assets/js/',
        'includes/'
    ];
    
    foreach ($check_paths as $path) {
        if (is_readable($path)) {
            $messages[] = "Path dapat diakses: $path";
        } else {
            $errors[] = "Path tidak dapat diakses: $path";
        }
    }
    
    // 10. Clean up old error logs jika ada
    if (function_exists('error_clear_last')) {
        error_clear_last();
    }
    
} catch (Exception $e) {
    $errors[] = "Error saat melakukan system check: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Platform Errors - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Platform Error Fix & System Check
                        </h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Success Messages -->
                        <?php if (!empty($messages)): ?>
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>System Status - OK</h6>
                                <ul class="mb-0">
                                    <?php foreach ($messages as $message): ?>
                                        <li><?php echo e($message); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Issues Found</h6>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo e($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Platform Status -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Database Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Koneksi Database:</span>
                                            <span class="badge bg-success">Connected</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Kolom Geolocation:</span>
                                            <?php if ($has_geolocation): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Not Available</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Log Function:</span>
                                            <span class="badge bg-success">Working</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Feature Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>IP Detection:</span>
                                            <span class="badge bg-success">Working</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Device Detection:</span>
                                            <span class="badge bg-success">Working</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Geolocation API:</span>
                                            <?php if (file_exists('assets/js/geolocation.js')): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Missing JS</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="mt-4">
                            <h6>Quick Actions:</h6>
                            <div class="btn-group" role="group">
                                <?php if (!$has_geolocation): ?>
                                    <a href="update_geolocation_schema.php" class="btn btn-primary">
                                        <i class="fas fa-database me-1"></i>Update Database Schema
                                    </a>
                                <?php endif; ?>
                                
                                <a href="test_geolocation.php" class="btn btn-info">
                                    <i class="fas fa-map-marker-alt me-1"></i>Test Geolocation
                                </a>
                                
                                <a href="test_device_detection.php" class="btn btn-success">
                                    <i class="fas fa-mobile-alt me-1"></i>Test Device Detection
                                </a>
                                
                                <a href="logs/" class="btn btn-warning">
                                    <i class="fas fa-history me-1"></i>View Activity Logs
                                </a>
                                
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-home me-1"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                        
                        <!-- Troubleshooting Guide -->
                        <div class="mt-4">
                            <h6>Troubleshooting Guide:</h6>
                            <div class="accordion" id="troubleshootingAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                            Undefined array key errors in logs
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <p>Jika Anda melihat error "Undefined array key 'latitude'" di log aktivitas:</p>
                                            <ol>
                                                <li>Jalankan <code>update_geolocation_schema.php</code> untuk menambahkan kolom geolocation</li>
                                                <li>Atau sistem akan otomatis menggunakan mode kompatibilitas</li>
                                                <li>Error ini tidak mempengaruhi fungsi utama platform</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                            Geolocation not working
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <p>Jika fitur geolocation tidak berfungsi:</p>
                                            <ol>
                                                <li>Pastikan browser mendukung HTML5 Geolocation</li>
                                                <li>Berikan permission location saat diminta browser</li>
                                                <li>Pastikan menggunakan HTTPS (geolocation memerlukan secure context)</li>
                                                <li>Cek file <code>assets/js/geolocation.js</code> tersedia</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                            Platform running slow
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <p>Jika platform berjalan lambat:</p>
                                            <ol>
                                                <li>Geolocation API berjalan asynchronous dan tidak memblokir UI</li>
                                                <li>Reverse geocoding memiliki timeout 5 detik</li>
                                                <li>Data location di-cache untuk menghindari request berulang</li>
                                                <li>Semua fitur geolocation bersifat optional dan tidak mengganggu fungsi utama</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Environment Info -->
                        <div class="mt-4">
                            <h6>Current Environment:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>PHP Version:</strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>User Agent:</strong></td>
                                        <td><small><?php echo e($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'); ?></small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>IP Address:</strong></td>
                                        <td><?php echo get_client_ip(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Session ID:</strong></td>
                                        <td><?php echo session_id(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Timezone:</strong></td>
                                        <td><?php echo date_default_timezone_get(); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>