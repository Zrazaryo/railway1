<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role admin
require_login();
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$ip_address = $input['ip'] ?? '';
$user_agent = $input['user_agent'] ?? '';

if (empty($ip_address)) {
    echo json_encode(['success' => false, 'message' => 'IP address tidak ditemukan']);
    exit();
}

try {
    $html = '<div class="row">';
    
    // Deteksi jenis IP address
    $is_ipv6 = filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    $is_ipv4 = filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    
    // Basic IP Info
    $html .= '<div class="col-12 mb-3">';
    if ($is_ipv6) {
        $html .= '<h6><i class="fas fa-network-wired me-2"></i>Informasi IPv6 Address</h6>';
        $ipv6_analysis = analyze_ipv6($ip_address);
        
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>IPv6 Address:</strong></td><td>' . e($ip_address) . '</td></tr>';
        
        if ($ipv6_analysis['is_valid']) {
            $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge bg-success">Valid IPv6</span></td></tr>';
            
            $html .= '<tr><td><strong>Jenis:</strong></td><td>';
            
            if ($ipv6_analysis['is_public']) {
                $html .= '<span class="badge bg-success"><i class="fas fa-globe me-1"></i>Public IPv6</span>';
            } elseif ($ipv6_analysis['is_link_local']) {
                $html .= '<span class="badge bg-warning"><i class="fas fa-link me-1"></i>Link-Local</span>';
            } elseif ($ipv6_analysis['is_unique_local']) {
                $html .= '<span class="badge bg-warning"><i class="fas fa-network-wired me-1"></i>Unique Local</span>';
            } elseif ($ipv6_analysis['is_loopback']) {
                $html .= '<span class="badge bg-info"><i class="fas fa-home me-1"></i>Loopback</span>';
            } elseif ($ipv6_analysis['is_multicast']) {
                $html .= '<span class="badge bg-purple"><i class="fas fa-broadcast-tower me-1"></i>Multicast</span>';
            } else {
                $html .= '<span class="badge bg-secondary">Special Use</span>';
            }
            
            $html .= '</td></tr>';
            $html .= '<tr><td><strong>Range Info:</strong></td><td>' . e($ipv6_analysis['range_info']) . '</td></tr>';
            
            // IPv6 specific details
            if (!empty($ipv6_analysis['expanded'])) {
                $html .= '<tr><td><strong>Expanded Form:</strong></td><td><code>' . e($ipv6_analysis['expanded']) . '</code></td></tr>';
            }
            
            $html .= '<tr><td><strong>Compressed Form:</strong></td><td><code>' . e($ipv6_analysis['compressed']) . '</code></td></tr>';
            
            // Binary representation (first 64 bits)
            $expanded = inet_pton($ip_address);
            if ($expanded !== false) {
                $hex_expanded = bin2hex($expanded);
                $binary_first_64 = '';
                for ($i = 0; $i < 8; $i++) {
                    $hex_part = substr($hex_expanded, $i * 4, 4);
                    $binary_part = str_pad(base_convert($hex_part, 16, 2), 16, '0', STR_PAD_LEFT);
                    $binary_first_64 .= $binary_part . ':';
                }
                $binary_first_64 = rtrim($binary_first_64, ':');
                $html .= '<tr><td><strong>Binary (first 64 bits):</strong></td><td><small><code>' . substr($binary_first_64, 0, 80) . '...</code></small></td></tr>';
                
                // Hexadecimal representation
                $hex_formatted = '';
                for ($i = 0; $i < strlen($hex_expanded); $i += 4) {
                    $hex_formatted .= substr($hex_expanded, $i, 4) . ':';
                }
                $hex_formatted = rtrim($hex_formatted, ':');
                $html .= '<tr><td><strong>Hexadecimal:</strong></td><td><code>' . strtoupper($hex_formatted) . '</code></td></tr>';
            }
            
            // Network information for specific IPv6 types
            if ($ipv6_analysis['is_link_local']) {
                $html .= '<tr><td><strong>Penggunaan:</strong></td><td>Komunikasi dalam jaringan lokal yang sama (RFC 4291)</td></tr>';
            } elseif ($ipv6_analysis['is_unique_local']) {
                $html .= '<tr><td><strong>Penggunaan:</strong></td><td>Jaringan private/internal (RFC 4193)</td></tr>';
            } elseif ($ipv6_analysis['is_loopback']) {
                $html .= '<tr><td><strong>Penggunaan:</strong></td><td>Localhost/Loopback interface</td></tr>';
            } elseif ($ipv6_analysis['is_multicast']) {
                $html .= '<tr><td><strong>Penggunaan:</strong></td><td>Multicast communication (RFC 4291)</td></tr>';
            } elseif (strpos($ipv6_analysis['range_info'], '6to4') !== false) {
                $html .= '<tr><td><strong>Penggunaan:</strong></td><td>IPv6 over IPv4 tunneling (RFC 3056)</td></tr>';
            } elseif (strpos($ipv6_analysis['range_info'], 'Teredo') !== false) {
                $html .= '<tr><td><strong>Penggunaan:</strong></td><td>NAT traversal untuk IPv6 (RFC 4380)</td></tr>';
            }
            
            // Geolocation for public IPv6
            if ($ipv6_analysis['is_public']) {
                $location = get_ip_location($ip_address);
                if ($location) {
                    $html .= '<tr><td><strong>Negara:</strong></td><td>' . e($location['country'] ?? '-') . '</td></tr>';
                    $html .= '<tr><td><strong>Region:</strong></td><td>' . e($location['regionName'] ?? '-') . '</td></tr>';
                    $html .= '<tr><td><strong>Kota:</strong></td><td>' . e($location['city'] ?? '-') . '</td></tr>';
                    $html .= '<tr><td><strong>ISP:</strong></td><td>' . e($location['isp'] ?? '-') . '</td></tr>';
                    if (!empty($location['org'])) {
                        $html .= '<tr><td><strong>Organisasi:</strong></td><td>' . e($location['org']) . '</td></tr>';
                    }
                }
            }
            
        } else {
            $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge bg-danger">Invalid IPv6</span></td></tr>';
            $html .= '<tr><td><strong>Error:</strong></td><td>' . e($ipv6_analysis['range_info']) . '</td></tr>';
        }
        
    } elseif ($is_ipv4) {
        $html .= '<h6><i class="fas fa-network-wired me-2"></i>Informasi IPv4 Address</h6>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>IPv4 Address:</strong></td><td>' . e($ip_address) . '</td></tr>';
        
        $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge bg-success">Valid IPv4</span></td></tr>';
        
        $html .= '<tr><td><strong>Jenis:</strong></td><td>';
        
        if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $html .= '<span class="badge bg-success"><i class="fas fa-globe me-1"></i>Public IPv4</span>';
        } elseif (filter_var($ip_address, FILTER_VALIDATE_IP)) {
            $html .= '<span class="badge bg-warning"><i class="fas fa-network-wired me-1"></i>Private IPv4</span>';
        }
        
        $html .= '</td></tr>';
        
        // Basic IPv4 information
        $ip_parts = explode('.', $ip_address);
        if (count($ip_parts) === 4) {
            $html .= '<tr><td><strong>Oktet:</strong></td><td>' . implode(' . ', array_map('e', $ip_parts)) . '</td></tr>';
        }
        
        // Geolocation for public IPv4
        if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $location = get_ip_location($ip_address);
            if ($location) {
                $html .= '<tr><td><strong>Negara:</strong></td><td>' . e($location['country'] ?? '-') . '</td></tr>';
                $html .= '<tr><td><strong>Region:</strong></td><td>' . e($location['regionName'] ?? '-') . '</td></tr>';
                $html .= '<tr><td><strong>Kota:</strong></td><td>' . e($location['city'] ?? '-') . '</td></tr>';
                $html .= '<tr><td><strong>ISP:</strong></td><td>' . e($location['isp'] ?? '-') . '</td></tr>';
                if (!empty($location['org'])) {
                    $html .= '<tr><td><strong>Organisasi:</strong></td><td>' . e($location['org']) . '</td></tr>';
                }
            }
        }
        
    } else {
        $html .= '<h6><i class="fas fa-network-wired me-2"></i>Informasi IP Address</h6>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>IP Address:</strong></td><td>' . e($ip_address) . '</td></tr>';
        $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge bg-danger">Invalid IP</span></td></tr>';
    }
    
    $html .= '</table>';
    $html .= '</div>';
    
    // User Agent Info
    if (!empty($user_agent)) {
        $device_info = get_detailed_device_info($user_agent);
        
        $html .= '<div class="col-12 mb-3">';
        $html .= '<h6><i class="fas fa-desktop me-2"></i>Informasi Device & Browser</h6>';
        $html .= '<table class="table table-sm">';
        
        $html .= '<tr><td><strong>Browser:</strong></td><td>' . e($device_info['browser']);
        if (!empty($device_info['browser_version'])) {
            $html .= ' v' . e(substr($device_info['browser_version'], 0, 10));
        }
        $html .= '</td></tr>';
        
        $html .= '<tr><td><strong>Sistem Operasi:</strong></td><td>' . e($device_info['os']);
        if (!empty($device_info['os_version'])) {
            $html .= ' v' . e(substr($device_info['os_version'], 0, 10));
        }
        $html .= '</td></tr>';
        
        $html .= '<tr><td><strong>Jenis Device:</strong></td><td>';
        if ($device_info['is_mobile']) {
            $html .= '<span class="badge bg-success"><i class="fas fa-mobile-alt me-1"></i>Mobile</span>';
        } elseif ($device_info['is_tablet']) {
            $html .= '<span class="badge bg-info"><i class="fas fa-tablet-alt me-1"></i>Tablet</span>';
        } else {
            $html .= '<span class="badge bg-primary"><i class="fas fa-desktop me-1"></i>Desktop</span>';
        }
        $html .= '</td></tr>';
        
        $html .= '<tr><td><strong>Platform:</strong></td><td>' . e($device_info['device_type']) . '</td></tr>';
        
        // Deteksi tambahan untuk mobile
        if ($device_info['is_mobile'] || $device_info['is_tablet']) {
            $html .= '<tr><td><strong>Kategori:</strong></td><td>';
            if (strpos($user_agent, 'iPhone') !== false) {
                $html .= '<i class="fab fa-apple text-secondary me-1"></i>iPhone';
            } elseif (strpos($user_agent, 'iPad') !== false) {
                $html .= '<i class="fab fa-apple text-secondary me-1"></i>iPad';
            } elseif (strpos($user_agent, 'Android') !== false) {
                $html .= '<i class="fab fa-android text-success me-1"></i>Android Device';
                
                // Deteksi manufacturer Android
                if (strpos($user_agent, 'Samsung') !== false) {
                    $html .= ' (Samsung)';
                } elseif (strpos($user_agent, 'Huawei') !== false) {
                    $html .= ' (Huawei)';
                } elseif (strpos($user_agent, 'Xiaomi') !== false) {
                    $html .= ' (Xiaomi)';
                } elseif (strpos($user_agent, 'OnePlus') !== false) {
                    $html .= ' (OnePlus)';
                } elseif (strpos($user_agent, 'OPPO') !== false) {
                    $html .= ' (OPPO)';
                } elseif (strpos($user_agent, 'Vivo') !== false) {
                    $html .= ' (Vivo)';
                } elseif (strpos($user_agent, 'LG') !== false) {
                    $html .= ' (LG)';
                } elseif (strpos($user_agent, 'Sony') !== false) {
                    $html .= ' (Sony)';
                }
            }
            $html .= '</td></tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        // Security & Privacy Info
        $html .= '<div class="col-12 mb-3">';
        $html .= '<h6><i class="fas fa-shield-alt me-2"></i>Informasi Keamanan</h6>';
        $html .= '<table class="table table-sm">';
        
        // Deteksi fitur keamanan
        $security_features = [];
        if (strpos($user_agent, 'HTTPS') !== false) {
            $security_features[] = '<span class="badge bg-success">HTTPS</span>';
        }
        if (strpos($user_agent, 'WebKit') !== false) {
            $security_features[] = '<span class="badge bg-info">WebKit</span>';
        }
        if (strpos($user_agent, 'Gecko') !== false) {
            $security_features[] = '<span class="badge bg-info">Gecko</span>';
        }
        
        $html .= '<tr><td><strong>Engine:</strong></td><td>' . 
                 (!empty($security_features) ? implode(' ', $security_features) : 'Standard') . 
                 '</td></tr>';
        
        // Deteksi bot atau crawler
        $is_bot = false;
        $bot_types = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget'];
        foreach ($bot_types as $bot_type) {
            if (stripos($user_agent, $bot_type) !== false) {
                $is_bot = true;
                break;
            }
        }
        
        $html .= '<tr><td><strong>Jenis Akses:</strong></td><td>';
        if ($is_bot) {
            $html .= '<span class="badge bg-warning"><i class="fas fa-robot me-1"></i>Bot/Crawler</span>';
        } else {
            $html .= '<span class="badge bg-success"><i class="fas fa-user me-1"></i>Human User</span>';
        }
        $html .= '</td></tr>';
        
        $html .= '</table>';
        $html .= '</div>';
        
        // Full User Agent
        $html .= '<div class="col-12">';
        $html .= '<h6><i class="fas fa-code me-2"></i>User Agent String</h6>';
        $html .= '<div class="bg-light p-2 rounded"><small style="word-break: break-all;">' . e($user_agent) . '</small></div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>