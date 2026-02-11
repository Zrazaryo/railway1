<?php
/**
 * Component untuk menampilkan security alerts
 * Include file ini di halaman yang ingin menampilkan alert keamanan
 */

if (isset($_SESSION['security_alerts']) && !empty($_SESSION['security_alerts'])) {
    $alerts = $_SESSION['security_alerts'];
    
    echo '<div class="security-alerts mb-3">';
    
    foreach ($alerts as $alert) {
        $alert_class = 'alert-info';
        $icon = 'fas fa-info-circle';
        
        switch ($alert['severity']) {
            case 'warning':
                $alert_class = 'alert-warning';
                $icon = 'fas fa-exclamation-triangle';
                break;
            case 'danger':
                $alert_class = 'alert-danger';
                $icon = 'fas fa-exclamation-circle';
                break;
            case 'success':
                $alert_class = 'alert-success';
                $icon = 'fas fa-check-circle';
                break;
            default:
                $alert_class = 'alert-info';
                $icon = 'fas fa-info-circle';
        }
        
        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
        echo '<i class="' . $icon . ' me-2"></i>';
        echo '<strong>Deteksi Keamanan:</strong> ' . htmlspecialchars($alert['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Clear alerts setelah ditampilkan
    unset($_SESSION['security_alerts']);
}

/**
 * Function untuk menampilkan statistik login device
 */
function display_device_login_stats($user_id = null, $days = 30) {
    global $db;
    
    try {
        $where_clause = "WHERE action IN ('LOGIN_ADMIN', 'LOGIN_STAFF')";
        $params = [];
        
        if ($user_id) {
            $where_clause .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $where_clause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $days;
        
        $logins = $db->fetchAll("
            SELECT user_agent, ip_address, created_at, u.full_name, u.username
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            $where_clause
            ORDER BY created_at DESC
        ", $params);
        
        if (empty($logins)) {
            return;
        }
        
        // Group by device type
        $device_stats = [
            'mobile' => 0,
            'tablet' => 0,
            'desktop' => 0,
            'unknown' => 0
        ];
        
        $os_stats = [];
        $recent_devices = [];
        
        foreach ($logins as $login) {
            $device_info = get_detailed_device_info($login['user_agent']);
            
            // Count device types
            if ($device_info['is_mobile']) {
                $device_stats['mobile']++;
            } elseif ($device_info['is_tablet']) {
                $device_stats['tablet']++;
            } elseif ($device_info['is_desktop']) {
                $device_stats['desktop']++;
            } else {
                $device_stats['unknown']++;
            }
            
            // Count OS
            $os = $device_info['os'];
            if (!isset($os_stats[$os])) {
                $os_stats[$os] = 0;
            }
            $os_stats[$os]++;
            
            // Store recent unique devices
            $device_key = $device_info['os'] . ' - ' . $device_info['browser'];
            if (!isset($recent_devices[$device_key])) {
                $recent_devices[$device_key] = [
                    'device_info' => $device_info,
                    'last_login' => $login['created_at'],
                    'ip_address' => $login['ip_address'],
                    'user_name' => $login['full_name']
                ];
            }
        }
        
        // Sort stats
        arsort($os_stats);
        
        echo '<div class="card shadow-sm mb-4">';
        echo '<div class="card-header">';
        echo '<h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statistik Login Device (' . $days . ' hari terakhir)</h6>';
        echo '</div>';
        echo '<div class="card-body">';
        
        echo '<div class="row">';
        
        // Device Type Stats
        echo '<div class="col-md-6 mb-3">';
        echo '<h6 class="text-muted mb-2">Jenis Device</h6>';
        
        $total_logins = array_sum($device_stats);
        foreach ($device_stats as $type => $count) {
            if ($count > 0) {
                $percentage = round(($count / $total_logins) * 100, 1);
                $icon = '';
                $color = '';
                
                switch ($type) {
                    case 'mobile':
                        $icon = 'fas fa-mobile-alt';
                        $color = 'success';
                        $label = 'Mobile';
                        break;
                    case 'tablet':
                        $icon = 'fas fa-tablet-alt';
                        $color = 'info';
                        $label = 'Tablet';
                        break;
                    case 'desktop':
                        $icon = 'fas fa-desktop';
                        $color = 'primary';
                        $label = 'Desktop';
                        break;
                    default:
                        $icon = 'fas fa-question';
                        $color = 'secondary';
                        $label = 'Unknown';
                }
                
                echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                echo '<span><i class="' . $icon . ' text-' . $color . ' me-2"></i>' . $label . '</span>';
                echo '<div>';
                echo '<span class="badge bg-' . $color . ' me-2">' . $count . '</span>';
                echo '<small class="text-muted">' . $percentage . '%</small>';
                echo '</div>';
                echo '</div>';
            }
        }
        echo '</div>';
        
        // OS Stats
        echo '<div class="col-md-6 mb-3">';
        echo '<h6 class="text-muted mb-2">Sistem Operasi</h6>';
        
        $displayed = 0;
        foreach ($os_stats as $os => $count) {
            if ($displayed >= 5) break;
            
            $percentage = round(($count / $total_logins) * 100, 1);
            $icon = '';
            
            if ($os === 'Android') $icon = 'fab fa-android text-success';
            elseif ($os === 'iOS' || $os === 'iPadOS') $icon = 'fab fa-apple text-secondary';
            elseif (strpos($os, 'Windows') !== false) $icon = 'fab fa-windows text-primary';
            elseif ($os === 'macOS') $icon = 'fab fa-apple text-secondary';
            elseif ($os === 'Linux') $icon = 'fab fa-linux text-dark';
            else $icon = 'fas fa-desktop text-muted';
            
            echo '<div class="d-flex justify-content-between align-items-center mb-2">';
            echo '<span><i class="' . $icon . ' me-2"></i>' . htmlspecialchars($os) . '</span>';
            echo '<div>';
            echo '<span class="badge bg-secondary me-2">' . $count . '</span>';
            echo '<small class="text-muted">' . $percentage . '%</small>';
            echo '</div>';
            echo '</div>';
            
            $displayed++;
        }
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
    } catch (Exception $e) {
        // Ignore errors
    }
}
?>