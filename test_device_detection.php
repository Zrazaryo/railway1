<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login dan role admin
require_admin();

// Sample user agents untuk testing
$test_user_agents = [
    // Android Mobile
    'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 12; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 11; OnePlus 9 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Mobile Safari/537.36',
    
    // Android Tablet
    'Mozilla/5.0 (Linux; Android 12; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
    
    // iPhone
    'Mozilla/5.0 (iPhone; CPU iPhone OS 16_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 15_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.6 Mobile/15E148 Safari/604.1',
    
    // iPad
    'Mozilla/5.0 (iPad; CPU OS 16_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Mobile/15E148 Safari/604.1',
    
    // Windows Desktop
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:111.0) Gecko/20100101 Firefox/111.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36 Edg/112.0.1722.58',
    
    // macOS
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15',
    
    // Linux
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:111.0) Gecko/20100101 Firefox/111.0',
    
    // Mobile Browsers
    'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/20.0 Chrome/106.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 16_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/112.0.5615.46 Mobile/15E148 Safari/604.1',
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Device Detection - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .text-purple { color: #6f42c1 !important; }
        .bg-purple { background-color: #6f42c1 !important; }
        .badge.bg-purple { color: white !important; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-mobile-alt me-2"></i>
                        Test Device Detection
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <!-- Current Device -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-desktop me-2"></i>Device & IP Anda Saat Ini</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                        $current_ip = get_client_ip();
                        $current_device = get_detailed_device_info($current_ua);
                        ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>IP Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>IP Address:</strong></td>
                                        <td><?php echo format_ip_info($current_ip, $current_ua); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td>
                                            <?php
                                            if (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                                                $ipv6_info = analyze_ipv6($current_ip);
                                                if ($ipv6_info['is_public']) {
                                                    echo '<span class="badge bg-success">Public IPv6</span>';
                                                } elseif ($ipv6_info['is_link_local']) {
                                                    echo '<span class="badge bg-warning">Link-Local IPv6</span>';
                                                } elseif ($ipv6_info['is_unique_local']) {
                                                    echo '<span class="badge bg-warning">Unique Local IPv6</span>';
                                                } elseif ($ipv6_info['is_loopback']) {
                                                    echo '<span class="badge bg-info">Loopback IPv6</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">IPv6</span>';
                                                }
                                            } elseif (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                                if (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                                    echo '<span class="badge bg-success">Public IPv4</span>';
                                                } else {
                                                    echo '<span class="badge bg-warning">Private IPv4</span>';
                                                }
                                            } else {
                                                echo '<span class="badge bg-secondary">Unknown</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php if (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)): ?>
                                        <?php $ipv6_info = analyze_ipv6($current_ip); ?>
                                        <tr>
                                            <td><strong>Range Info:</strong></td>
                                            <td><?php echo e($ipv6_info['range_info']); ?></td>
                                        </tr>
                                        <?php if (!empty($ipv6_info['expanded'])): ?>
                                        <tr>
                                            <td><strong>Expanded:</strong></td>
                                            <td><small><code><?php echo e(substr($ipv6_info['expanded'], 0, 30)) . '...'; ?></code></small></td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Device Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Browser:</strong></td>
                                        <td><?php echo e($current_device['browser'] . ' ' . $current_device['browser_version']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>OS:</strong></td>
                                        <td><?php echo e($current_device['os'] . ' ' . $current_device['os_version']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Device Type:</strong></td>
                                        <td><?php echo e($current_device['device_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Display:</strong></td>
                                        <td><?php echo parse_user_agent($current_ua); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>IP Flags:</h6>
                                <div>
                                    <?php if (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)): ?>
                                        <?php $ipv6_info = analyze_ipv6($current_ip); ?>
                                        <?php if ($ipv6_info['is_public']): ?>
                                            <span class="badge bg-success"><i class="fas fa-globe"></i> Public IPv6</span>
                                        <?php endif; ?>
                                        <?php if ($ipv6_info['is_link_local']): ?>
                                            <span class="badge bg-warning"><i class="fas fa-link"></i> Link-Local</span>
                                        <?php endif; ?>
                                        <?php if ($ipv6_info['is_unique_local']): ?>
                                            <span class="badge bg-warning"><i class="fas fa-network-wired"></i> Unique Local</span>
                                        <?php endif; ?>
                                        <?php if ($ipv6_info['is_loopback']): ?>
                                            <span class="badge bg-info"><i class="fas fa-home"></i> Loopback</span>
                                        <?php endif; ?>
                                        <?php if ($ipv6_info['is_multicast']): ?>
                                            <span class="badge bg-purple"><i class="fas fa-broadcast-tower"></i> Multicast</span>
                                        <?php endif; ?>
                                    <?php elseif (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)): ?>
                                        <?php if (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)): ?>
                                            <span class="badge bg-success"><i class="fas fa-globe"></i> Public IPv4</span>
                                        <?php elseif (filter_var($current_ip, FILTER_VALIDATE_IP)): ?>
                                            <span class="badge bg-warning"><i class="fas fa-network-wired"></i> Private IPv4</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-question"></i> Unknown</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Device Flags:</h6>
                                <div>
                                    <?php if ($current_device['is_mobile']): ?>
                                        <span class="badge bg-success"><i class="fas fa-mobile-alt"></i> Mobile</span>
                                    <?php endif; ?>
                                    <?php if ($current_device['is_tablet']): ?>
                                        <span class="badge bg-info"><i class="fas fa-tablet-alt"></i> Tablet</span>
                                    <?php endif; ?>
                                    <?php if ($current_device['is_desktop']): ?>
                                        <span class="badge bg-primary"><i class="fas fa-desktop"></i> Desktop</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <h6>User Agent String:</h6>
                            <div class="bg-light p-2 rounded">
                                <small style="word-break: break-all;"><?php echo e($current_ua); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Test Results -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-vial me-2"></i>Test Results - Sample User Agents</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Device Type</th>
                                        <th>OS</th>
                                        <th>Browser</th>
                                        <th>Display</th>
                                        <th>User Agent (Truncated)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($test_user_agents as $ua): ?>
                                        <?php $device = get_detailed_device_info($ua); ?>
                                        <tr>
                                            <td>
                                                <?php if ($device['is_mobile']): ?>
                                                    <span class="badge bg-success">Mobile</span>
                                                <?php elseif ($device['is_tablet']): ?>
                                                    <span class="badge bg-info">Tablet</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Desktop</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($device['os'] . ' ' . substr($device['os_version'], 0, 5)); ?></td>
                                            <td><?php echo e($device['browser'] . ' ' . substr($device['browser_version'], 0, 5)); ?></td>
                                            <td><?php echo parse_user_agent($ua); ?></td>
                                            <td>
                                                <small class="text-muted" style="word-break: break-all;">
                                                    <?php echo e(substr($ua, 0, 80)) . '...'; ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- IPv6 Test Results -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-network-wired me-2"></i>Test IPv6 Address Detection</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Sample IPv6 addresses for testing
                        $test_ipv6_addresses = [
                            '2001:db8::1',                    // Documentation
                            '::1',                            // Loopback
                            'fe80::1',                        // Link-local
                            'fc00::1',                        // Unique local
                            'fd00::1',                        // Unique local
                            '2001:4860:4860::8888',          // Google DNS (Public)
                            '2606:4700:4700::1111',          // Cloudflare DNS (Public)
                            'ff02::1',                        // Multicast
                            '2002:c000:0204::1',             // 6to4
                            '2001:0:4137:9e76:2087:77a:53ef:7b0b', // Teredo
                            '::ffff:192.0.2.1',             // IPv4-mapped
                            '2001:db8:85a3::8a2e:370:7334', // Documentation (expanded)
                        ];
                        ?>
                        
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>IPv6 Address</th>
                                        <th>Type</th>
                                        <th>Range Info</th>
                                        <th>Compressed</th>
                                        <th>Expanded (partial)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($test_ipv6_addresses as $ip): ?>
                                        <?php $analysis = analyze_ipv6($ip); ?>
                                        <tr>
                                            <td>
                                                <code style="font-size: 0.8em;"><?php echo e(strlen($ip) > 25 ? substr($ip, 0, 25) . '...' : $ip); ?></code>
                                            </td>
                                            <td>
                                                <?php if ($analysis['is_valid']): ?>
                                                    <?php if ($analysis['is_public']): ?>
                                                        <span class="badge bg-success"><i class="fas fa-globe"></i> Public</span>
                                                    <?php elseif ($analysis['is_link_local']): ?>
                                                        <span class="badge bg-warning"><i class="fas fa-link"></i> Link-Local</span>
                                                    <?php elseif ($analysis['is_unique_local']): ?>
                                                        <span class="badge bg-warning"><i class="fas fa-network-wired"></i> Unique Local</span>
                                                    <?php elseif ($analysis['is_loopback']): ?>
                                                        <span class="badge bg-info"><i class="fas fa-home"></i> Loopback</span>
                                                    <?php elseif ($analysis['is_multicast']): ?>
                                                        <span class="badge bg-purple"><i class="fas fa-broadcast-tower"></i> Multicast</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Special</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Invalid</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo e($analysis['range_info']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($analysis['is_valid']): ?>
                                                    <small><code><?php echo e(strlen($analysis['compressed']) > 20 ? substr($analysis['compressed'], 0, 20) . '...' : $analysis['compressed']); ?></code></small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($analysis['is_valid'] && !empty($analysis['expanded'])): ?>
                                                    <small><code><?php echo e(substr($analysis['expanded'], 0, 25)) . '...'; ?></code></small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <h6>IPv6 Address Types:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><strong>Global Unicast:</strong> 2000::/3 (Public)</li>
                                        <li><strong>Link-Local:</strong> fe80::/10</li>
                                        <li><strong>Unique Local:</strong> fc00::/7</li>
                                        <li><strong>Loopback:</strong> ::1/128</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><strong>Multicast:</strong> ff00::/8</li>
                                        <li><strong>Documentation:</strong> 2001:db8::/32</li>
                                        <li><strong>6to4:</strong> 2002::/16</li>
                                        <li><strong>Teredo:</strong> 2001::/32</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Detection Test -->
                <?php if (isset($_GET['test_security'])): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Detection Test</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $alerts = detect_suspicious_login($_SESSION['user_id'], $current_ip, $current_ua);
                        if (!empty($alerts)) {
                            foreach ($alerts as $alert) {
                                $class = $alert['severity'] === 'warning' ? 'alert-warning' : 'alert-info';
                                echo '<div class="alert ' . $class . '">';
                                echo '<strong>' . ucfirst($alert['type']) . ':</strong> ' . e($alert['message']);
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-success">Tidak ada aktivitas mencurigakan terdeteksi.</div>';
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="?test_security=1" class="btn btn-outline-warning">
                        <i class="fas fa-shield-alt"></i> Test Security Detection
                    </a>
                    <a href="logs/" class="btn btn-outline-primary">
                        <i class="fas fa-history"></i> Lihat Log Aktivitas
                    </a>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>