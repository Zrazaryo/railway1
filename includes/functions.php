<?php
// Fungsi-fungsi utilitas untuk sistem arsip dokumen

/**
 * Inisialisasi session dengan dukungan multi-tab
 * Set session name berdasarkan parameter tab di URL
 */
function init_multi_session() {
    // Cek apakah ada parameter tab di URL
    $tab_id = isset($_GET['tab']) ? intval($_GET['tab']) : 0;
    
    // Jika ada tab_id, gunakan session name yang berbeda
    if ($tab_id > 0) {
        $session_name = 'PHPSESSID_' . $tab_id;
    } else {
        // Default session name (tab 0)
        $session_name = 'PHPSESSID';
    }
    
    // Tutup session yang sedang aktif jika ada (dengan nama berbeda)
    if (session_status() === PHP_SESSION_ACTIVE) {
        $current_session_name = session_name();
        if ($current_session_name !== $session_name) {
            // Session name berbeda, tutup dan buka session baru
            session_write_close();
        } else {
            // Session sudah aktif dengan nama yang benar
            // Set tab_id di session untuk tracking
            $_SESSION['tab_id'] = $tab_id;
            return;
        }
    }
    
    // Set session name sebelum session_start()
    session_name($session_name);
    
    // Set cookie parameters untuk memastikan session terpisah per tab
    // Di Vercel/hosting HTTPS, secure harus true agar cookie dikirim
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || getenv('VERCEL') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0, // Sampai browser ditutup
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start session baru
    session_start();
    
    // Set tab_id di session untuk tracking
    $_SESSION['tab_id'] = $tab_id;
}

/**
 * Dapatkan session name berdasarkan tab_id
 */
function get_session_name($tab_id = 0) {
    if ($tab_id > 0) {
        return 'PHPSESSID_' . $tab_id;
    }
    return 'PHPSESSID';
}

/**
 * Switch session berdasarkan tab_id
 */
function switch_session($tab_id) {
    // Tutup session saat ini
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Set session name baru
    $session_name = get_session_name($tab_id);
    session_name($session_name);
    
    // Start session baru
    session_start();
}

/**
 * Dapatkan parameter tab dari URL
 */
function get_tab_id() {
    return isset($_GET['tab']) ? intval($_GET['tab']) : 0;
}

/**
 * Tambahkan parameter tab ke URL
 */
function add_tab_param($url) {
    $tab_id = get_tab_id();
    if ($tab_id > 0) {
        // Cek apakah URL sudah ada parameter
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . 'tab=' . $tab_id;
    }
    return $url;
}

/**
 * Sanitasi input untuk mencegah XSS
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate nomor dokumen unik
 */
function generate_document_number() {
    $prefix = 'DOC';
    $date = date('Ymd');
    $random = rand(1000, 9999);
    return $prefix . $date . $random;
}

/**
 * Format ukuran file
 */
function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Validasi tipe file yang diizinkan
 */
function is_allowed_file_type($filename) {
    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowed_extensions);
}

/**
 * Generate nama file unik
 */
function generate_unique_filename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Log aktivitas user
 */
function log_activity($user_id, $action, $description = '', $document_id = null) {
    global $db;
    
    // Deteksi IP address yang lebih akurat
    $ip_address = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO activity_logs (user_id, action, description, document_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $db->execute($sql, [$user_id, $action, $description, $document_id, $ip_address, $user_agent]);
}

/**
 * Mendapatkan IP address client yang akurat
 * Menangani kasus proxy, load balancer, dan CDN
 * Mendukung IPv4 dan IPv6
 */
function get_client_ip() {
    // Array header yang mungkin berisi IP address client
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_REAL_IP',            // Nginx proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip_list = $_SERVER[$header];
            
            // Jika ada multiple IP (separated by comma), cek semua
            if (strpos($ip_list, ',') !== false) {
                $ip_array = explode(',', $ip_list);
                
                // Cari IP public terlebih dahulu
                foreach ($ip_array as $ip) {
                    $ip = trim($ip);
                    
                    // Prioritas 1: IPv4 public
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                    
                    // Prioritas 2: IPv6 public
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $ipv6_analysis = analyze_ipv6($ip);
                        if ($ipv6_analysis['is_public']) {
                            return $ip;
                        }
                    }
                }
                
                // Jika tidak ada public IP, ambil yang valid pertama
                foreach ($ip_array as $ip) {
                    $ip = trim($ip);
                    
                    // IPv4 private/local
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return $ip;
                    }
                    
                    // IPv6 apapun yang valid
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        return $ip;
                    }
                }
            } else {
                // Single IP
                $ip = trim($ip_list);
                
                // Validasi IPv4
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $ip;
                }
                
                // Validasi IPv6
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    return $ip;
                }
            }
        }
    }
    
    // Fallback jika tidak ada IP yang valid
    return 'Unknown';
}



/**
 * Mendapatkan informasi geolokasi dari IP address
 * Menggunakan service gratis ip-api.com (limit 1000 request/bulan)
 */
function get_ip_location($ip_address) {
    // Skip untuk IP lokal atau unknown
    if (empty($ip_address) || $ip_address === 'Unknown' || 
        filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return null;
    }
    
    // Cache key untuk menghindari request berulang
    $cache_key = 'ip_location_' . md5($ip_address);
    
    // Cek cache (implementasi sederhana dengan file)
    $cache_file = sys_get_temp_dir() . '/' . $cache_key . '.cache';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 86400) { // Cache 24 jam
        $cached_data = file_get_contents($cache_file);
        return json_decode($cached_data, true);
    }
    
    try {
        // Request ke ip-api.com
        $context = stream_context_create([
            'http' => [
                'timeout' => 5, // 5 detik timeout
                'user_agent' => 'Mozilla/5.0 (compatible; SistemArsipDokumen/1.0)'
            ]
        ]);
        
        $response = file_get_contents("http://ip-api.com/json/{$ip_address}?fields=status,country,regionName,city,isp,org", false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                // Simpan ke cache
                file_put_contents($cache_file, json_encode($data));
                return $data;
            }
        }
    } catch (Exception $e) {
        // Ignore errors, return null
    }
    
    return null;
}

/**
 * Analisis IPv6 address
 */
function analyze_ipv6($ip_address) {
    if (empty($ip_address) || $ip_address === 'Unknown') {
        return [
            'is_valid' => false,
            'type' => 'unknown',
            'is_private' => false,
            'is_public' => false,
            'is_loopback' => false,
            'is_multicast' => false,
            'is_link_local' => false,
            'is_unique_local' => false,
            'range_info' => 'Invalid IP',
            'compressed' => '',
            'expanded' => '',
            'prefix_length' => 0
        ];
    }
    
    // Validasi IPv6
    if (!filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return [
            'is_valid' => false,
            'type' => 'invalid',
            'is_private' => false,
            'is_public' => false,
            'is_loopback' => false,
            'is_multicast' => false,
            'is_link_local' => false,
            'is_unique_local' => false,
            'range_info' => 'Not a valid IPv6 address',
            'compressed' => '',
            'expanded' => '',
            'prefix_length' => 0
        ];
    }
    
    $analysis = [
        'is_valid' => true,
        'type' => 'ipv6',
        'is_private' => false,
        'is_public' => false,
        'is_loopback' => false,
        'is_multicast' => false,
        'is_link_local' => false,
        'is_unique_local' => false,
        'range_info' => '',
        'compressed' => $ip_address,
        'expanded' => '',
        'prefix_length' => 128
    ];
    
    // Expand IPv6 address
    $expanded = inet_pton($ip_address);
    if ($expanded !== false) {
        $analysis['expanded'] = inet_ntop($expanded);
        $hex_expanded = bin2hex($expanded);
        $analysis['expanded'] = implode(':', str_split($hex_expanded, 4));
    }
    
    // Analyze IPv6 ranges
    $first_block = strtolower(substr($ip_address, 0, 4));
    
    // Loopback (::1)
    if ($ip_address === '::1') {
        $analysis['is_loopback'] = true;
        $analysis['is_private'] = true;
        $analysis['range_info'] = 'Loopback (::1/128)';
    }
    // Link-local (fe80::/10)
    elseif (strpos($ip_address, 'fe8') === 0 || strpos($ip_address, 'fe9') === 0 || 
            strpos($ip_address, 'fea') === 0 || strpos($ip_address, 'feb') === 0) {
        $analysis['is_link_local'] = true;
        $analysis['is_private'] = true;
        $analysis['range_info'] = 'Link-Local (fe80::/10)';
    }
    // Unique Local (fc00::/7)
    elseif (strpos($ip_address, 'fc') === 0 || strpos($ip_address, 'fd') === 0) {
        $analysis['is_unique_local'] = true;
        $analysis['is_private'] = true;
        $analysis['range_info'] = 'Unique Local (fc00::/7)';
    }
    // Multicast (ff00::/8)
    elseif (strpos($ip_address, 'ff') === 0) {
        $analysis['is_multicast'] = true;
        $analysis['range_info'] = 'Multicast (ff00::/8)';
    }
    // Documentation (2001:db8::/32)
    elseif (strpos($ip_address, '2001:db8') === 0 || strpos($ip_address, '2001:0db8') === 0) {
        $analysis['is_private'] = true;
        $analysis['range_info'] = 'Documentation (2001:db8::/32)';
    }
    // 6to4 (2002::/16)
    elseif (strpos($ip_address, '2002:') === 0) {
        $analysis['is_public'] = true;
        $analysis['range_info'] = '6to4 Tunnel (2002::/16)';
    }
    // Teredo (2001::/32)
    elseif (strpos($ip_address, '2001:0:') === 0 || strpos($ip_address, '2001::') === 0) {
        $analysis['is_public'] = true;
        $analysis['range_info'] = 'Teredo Tunnel (2001::/32)';
    }
    // Global Unicast (2000::/3)
    elseif (preg_match('/^[23]/', $first_block)) {
        $analysis['is_public'] = true;
        $analysis['range_info'] = 'Global Unicast (2000::/3)';
    }
    // Unspecified (::)
    elseif ($ip_address === '::') {
        $analysis['is_private'] = true;
        $analysis['range_info'] = 'Unspecified (::/128)';
    }
    // IPv4-mapped IPv6 (::ffff:0:0/96)
    elseif (strpos($ip_address, '::ffff:') === 0) {
        $analysis['is_public'] = true;
        $analysis['range_info'] = 'IPv4-mapped IPv6 (::ffff:0:0/96)';
    }
    // Other ranges
    else {
        $analysis['is_public'] = true;
        $analysis['range_info'] = 'Global IPv6 Address';
    }
    
    return $analysis;
}

/**
 * Format informasi IPv6 address untuk ditampilkan
 */
function format_ipv6_info($ip_address, $user_agent = '') {
    if (empty($ip_address) || $ip_address === 'Unknown') {
        return '<span class="badge bg-secondary">Unknown</span>';
    }
    
    $analysis = analyze_ipv6($ip_address);
    
    if (!$analysis['is_valid']) {
        return '<span class="badge bg-danger" title="Invalid IPv6">Invalid</span>';
    }
    
    // Determine badge color and icon based on IP type
    $badge_color = 'secondary';
    $icon = 'fas fa-question';
    $title = $analysis['range_info'];
    
    if ($analysis['is_loopback']) {
        $badge_color = 'info';
        $icon = 'fas fa-home';
    } elseif ($analysis['is_link_local']) {
        $badge_color = 'warning';
        $icon = 'fas fa-link';
    } elseif ($analysis['is_unique_local']) {
        $badge_color = 'warning';
        $icon = 'fas fa-network-wired';
    } elseif ($analysis['is_public']) {
        $badge_color = 'success';
        $icon = 'fas fa-globe';
    } elseif ($analysis['is_multicast']) {
        $badge_color = 'purple';
        $icon = 'fas fa-broadcast-tower';
    }
    
    // Compress IPv6 for display
    $display_ip = strlen($ip_address) > 20 ? substr($ip_address, 0, 20) . '...' : $ip_address;
    
    // Create clickable badge
    $badge = '<a href="javascript:void(0)" onclick="showIPDetail(\'' . addslashes($ip_address) . '\', \'' . addslashes($user_agent) . '\')" class="badge bg-' . $badge_color . ' text-decoration-none" title="' . htmlspecialchars($title) . '">';
    $badge .= '<i class="' . $icon . ' me-1"></i>' . $display_ip;
    $badge .= '</a>';
    
    // Add type info
    $type_info = '<small class="text-muted d-block mt-1">';
    $type_info .= '<i class="' . $icon . ' me-1"></i>';
    if ($analysis['is_public']) {
        $type_info .= 'Public IPv6';
    } elseif ($analysis['is_link_local']) {
        $type_info .= 'Link-Local';
    } elseif ($analysis['is_unique_local']) {
        $type_info .= 'Unique Local';
    } elseif ($analysis['is_loopback']) {
        $type_info .= 'Loopback';
    } elseif ($analysis['is_multicast']) {
        $type_info .= 'Multicast';
    } else {
        $type_info .= 'IPv6';
    }
    $type_info .= '</small>';
    
    return $badge . $type_info;
}

/**
 * Format informasi IP address untuk ditampilkan (mendukung IPv4 dan IPv6)
 */
function format_ip_info($ip_address, $user_agent = '') {
    if (empty($ip_address) || $ip_address === 'Unknown') {
        return '<span class="badge bg-secondary">Unknown</span>';
    }
    
    // Deteksi apakah IPv4 atau IPv6
    if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return format_ipv6_info($ip_address, $user_agent);
    } elseif (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // Simple IPv4 display
        $badge_color = 'secondary';
        $icon = 'fas fa-network-wired';
        
        // Basic IP type detection
        if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $badge_color = 'success';
            $icon = 'fas fa-globe';
            $title = 'Public IPv4';
        } elseif (filter_var($ip_address, FILTER_VALIDATE_IP)) {
            $badge_color = 'warning';
            $icon = 'fas fa-network-wired';
            $title = 'Private IPv4';
        } else {
            $title = 'IPv4 Address';
        }
        
        // Create clickable badge
        $badge = '<a href="javascript:void(0)" onclick="showIPDetail(\'' . addslashes($ip_address) . '\', \'' . addslashes($user_agent) . '\')" class="badge bg-' . $badge_color . ' text-decoration-none" title="' . htmlspecialchars($title) . '">';
        $badge .= '<i class="' . $icon . ' me-1"></i>' . $ip_address;
        $badge .= '</a>';
        
        return $badge;
    } else {
        return '<span class="badge bg-danger">Invalid IP</span>';
    }
}

/**
 * Parse User Agent untuk mendapatkan info browser dan OS yang lebih akurat
 */
function parse_user_agent($user_agent) {
    if (empty($user_agent)) {
        return '<small class="text-muted">-</small>';
    }
    
    $browser_info = get_browser_info($user_agent);
    $os_info = get_os_info($user_agent);
    $device_info = get_device_info($user_agent);
    
    return '<small class="text-muted">' . $browser_info . '<br>' . $os_info . '<br>' . $device_info . '</small>';
}

/**
 * Deteksi informasi browser dari user agent
 */
function get_browser_info($user_agent) {
    $browser = 'Unknown Browser';
    $version = '';
    
    // Chrome (harus dicek sebelum Safari karena Chrome juga mengandung Safari)
    if (preg_match('/Chrome\/([0-9\.]+)/', $user_agent, $matches) && strpos($user_agent, 'Edg') === false) {
        $browser = '<i class="fab fa-chrome text-warning"></i> Chrome';
        $version = $matches[1];
    }
    // Firefox
    elseif (preg_match('/Firefox\/([0-9\.]+)/', $user_agent, $matches)) {
        $browser = '<i class="fab fa-firefox text-danger"></i> Firefox';
        $version = $matches[1];
    }
    // Safari (harus dicek setelah Chrome)
    elseif (preg_match('/Safari\/([0-9\.]+)/', $user_agent, $matches) && strpos($user_agent, 'Chrome') === false) {
        $browser = '<i class="fab fa-safari text-info"></i> Safari';
        if (preg_match('/Version\/([0-9\.]+)/', $user_agent, $version_matches)) {
            $version = $version_matches[1];
        }
    }
    // Microsoft Edge
    elseif (preg_match('/Edg\/([0-9\.]+)/', $user_agent, $matches)) {
        $browser = '<i class="fab fa-edge text-primary"></i> Edge';
        $version = $matches[1];
    }
    // Opera
    elseif (preg_match('/Opera\/([0-9\.]+)/', $user_agent, $matches) || preg_match('/OPR\/([0-9\.]+)/', $user_agent, $matches)) {
        $browser = '<i class="fab fa-opera text-danger"></i> Opera';
        $version = $matches[1];
    }
    // Internet Explorer
    elseif (preg_match('/MSIE ([0-9\.]+)/', $user_agent, $matches) || preg_match('/Trident.*rv:([0-9\.]+)/', $user_agent, $matches)) {
        $browser = '<i class="fab fa-internet-explorer text-primary"></i> IE';
        $version = $matches[1];
    }
    // Samsung Internet
    elseif (preg_match('/SamsungBrowser\/([0-9\.]+)/', $user_agent, $matches)) {
        $browser = '<i class="fas fa-mobile-alt text-info"></i> Samsung Internet';
        $version = $matches[1];
    }
    // UC Browser
    elseif (strpos($user_agent, 'UCBrowser') !== false) {
        $browser = '<i class="fas fa-mobile-alt text-warning"></i> UC Browser';
        if (preg_match('/UCBrowser\/([0-9\.]+)/', $user_agent, $matches)) {
            $version = $matches[1];
        }
    }
    // Mobile browsers
    elseif (strpos($user_agent, 'Mobile') !== false) {
        $browser = '<i class="fas fa-mobile-alt text-secondary"></i> Mobile Browser';
    }
    
    return $browser . ($version ? ' ' . substr($version, 0, 5) : '');
}

/**
 * Deteksi informasi OS dari user agent
 */
function get_os_info($user_agent) {
    $os = 'Unknown OS';
    $version = '';
    
    // Android
    if (preg_match('/Android ([0-9\.]+)/', $user_agent, $matches)) {
        $os = '<i class="fab fa-android text-success"></i> Android';
        $version = $matches[1];
    }
    // iOS iPhone
    elseif (preg_match('/iPhone OS ([0-9_]+)/', $user_agent, $matches) || preg_match('/iPhone.*OS ([0-9_]+)/', $user_agent, $matches)) {
        $os = '<i class="fab fa-apple text-secondary"></i> iOS (iPhone)';
        $version = str_replace('_', '.', $matches[1]);
    }
    // iOS iPad
    elseif (preg_match('/iPad.*OS ([0-9_]+)/', $user_agent, $matches)) {
        $os = '<i class="fab fa-apple text-secondary"></i> iPadOS';
        $version = str_replace('_', '.', $matches[1]);
    }
    // macOS
    elseif (preg_match('/Mac OS X ([0-9_]+)/', $user_agent, $matches)) {
        $os = '<i class="fab fa-apple text-secondary"></i> macOS';
        $version = str_replace('_', '.', $matches[1]);
    }
    // Windows 11
    elseif (strpos($user_agent, 'Windows NT 10.0') !== false && strpos($user_agent, 'Windows NT 10.0; Win64; x64') !== false) {
        // Windows 11 detection (more complex, but this is a simple approach)
        $os = '<i class="fab fa-windows text-primary"></i> Windows 10/11';
    }
    // Windows 10
    elseif (strpos($user_agent, 'Windows NT 10.0') !== false) {
        $os = '<i class="fab fa-windows text-primary"></i> Windows 10';
    }
    // Windows 8.1
    elseif (strpos($user_agent, 'Windows NT 6.3') !== false) {
        $os = '<i class="fab fa-windows text-primary"></i> Windows 8.1';
    }
    // Windows 8
    elseif (strpos($user_agent, 'Windows NT 6.2') !== false) {
        $os = '<i class="fab fa-windows text-primary"></i> Windows 8';
    }
    // Windows 7
    elseif (strpos($user_agent, 'Windows NT 6.1') !== false) {
        $os = '<i class="fab fa-windows text-primary"></i> Windows 7';
    }
    // Windows Vista
    elseif (strpos($user_agent, 'Windows NT 6.0') !== false) {
        $os = '<i class="fab fa-windows text-primary"></i> Windows Vista';
    }
    // Windows XP
    elseif (strpos($user_agent, 'Windows NT 5.1') !== false) {
        $os = '<i class="fab fa-windows text-primary"></i> Windows XP';
    }
    // Generic Windows
    elseif (strpos($user_agent, 'Windows NT') !== false) {
        $os = '<i class="fab fa-windows text-primary"></i> Windows';
    }
    // Linux distributions
    elseif (strpos($user_agent, 'Ubuntu') !== false) {
        $os = '<i class="fab fa-ubuntu text-warning"></i> Ubuntu';
    }
    elseif (strpos($user_agent, 'CentOS') !== false) {
        $os = '<i class="fab fa-centos text-danger"></i> CentOS';
    }
    elseif (strpos($user_agent, 'Fedora') !== false) {
        $os = '<i class="fab fa-fedora text-primary"></i> Fedora';
    }
    elseif (strpos($user_agent, 'SUSE') !== false) {
        $os = '<i class="fab fa-suse text-success"></i> SUSE';
    }
    elseif (strpos($user_agent, 'Red Hat') !== false) {
        $os = '<i class="fab fa-redhat text-danger"></i> Red Hat';
    }
    elseif (strpos($user_agent, 'Linux') !== false) {
        $os = '<i class="fab fa-linux text-dark"></i> Linux';
    }
    // Chrome OS
    elseif (strpos($user_agent, 'CrOS') !== false) {
        $os = '<i class="fab fa-chrome text-info"></i> Chrome OS';
    }
    // FreeBSD
    elseif (strpos($user_agent, 'FreeBSD') !== false) {
        $os = '<i class="fab fa-freebsd text-danger"></i> FreeBSD';
    }
    // Other Unix-like
    elseif (strpos($user_agent, 'Unix') !== false) {
        $os = '<i class="fas fa-server text-secondary"></i> Unix';
    }
    
    return $os . ($version ? ' ' . substr($version, 0, 8) : '');
}

/**
 * Deteksi jenis device dari user agent
 */
function get_device_info($user_agent) {
    $device = '';
    
    // Tablet detection
    if (strpos($user_agent, 'iPad') !== false) {
        $device = '<i class="fas fa-tablet-alt text-info"></i> iPad';
    }
    elseif (strpos($user_agent, 'Tablet') !== false || 
            (strpos($user_agent, 'Android') !== false && strpos($user_agent, 'Mobile') === false)) {
        $device = '<i class="fas fa-tablet-alt text-info"></i> Tablet';
    }
    // Mobile phone detection
    elseif (strpos($user_agent, 'iPhone') !== false) {
        $device = '<i class="fas fa-mobile-alt text-success"></i> iPhone';
    }
    elseif (strpos($user_agent, 'Mobile') !== false || 
            strpos($user_agent, 'Android') !== false) {
        $device = '<i class="fas fa-mobile-alt text-success"></i> Mobile';
    }
    // Smart TV
    elseif (strpos($user_agent, 'TV') !== false || 
            strpos($user_agent, 'SmartTV') !== false ||
            strpos($user_agent, 'SMART-TV') !== false) {
        $device = '<i class="fas fa-tv text-primary"></i> Smart TV';
    }
    // Gaming console
    elseif (strpos($user_agent, 'PlayStation') !== false) {
        $device = '<i class="fab fa-playstation text-primary"></i> PlayStation';
    }
    elseif (strpos($user_agent, 'Xbox') !== false) {
        $device = '<i class="fab fa-xbox text-success"></i> Xbox';
    }
    elseif (strpos($user_agent, 'Nintendo') !== false) {
        $device = '<i class="fas fa-gamepad text-danger"></i> Nintendo';
    }
    // Desktop/Laptop (default)
    else {
        $device = '<i class="fas fa-desktop text-primary"></i> Desktop';
    }
    
    return $device;
}

/**
 * Mendapatkan informasi lengkap device untuk display yang lebih detail
 */
function get_detailed_device_info($user_agent) {
    if (empty($user_agent)) {
        return [
            'browser' => 'Unknown',
            'browser_version' => '',
            'os' => 'Unknown',
            'os_version' => '',
            'device_type' => 'Unknown',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => true
        ];
    }
    
    $info = [
        'browser' => 'Unknown',
        'browser_version' => '',
        'os' => 'Unknown', 
        'os_version' => '',
        'device_type' => 'Desktop',
        'is_mobile' => false,
        'is_tablet' => false,
        'is_desktop' => true
    ];
    
    // Detect browser
    if (preg_match('/Chrome\/([0-9\.]+)/', $user_agent, $matches) && strpos($user_agent, 'Edg') === false) {
        $info['browser'] = 'Chrome';
        $info['browser_version'] = $matches[1];
    } elseif (preg_match('/Firefox\/([0-9\.]+)/', $user_agent, $matches)) {
        $info['browser'] = 'Firefox';
        $info['browser_version'] = $matches[1];
    } elseif (preg_match('/Safari\/([0-9\.]+)/', $user_agent, $matches) && strpos($user_agent, 'Chrome') === false) {
        $info['browser'] = 'Safari';
        if (preg_match('/Version\/([0-9\.]+)/', $user_agent, $version_matches)) {
            $info['browser_version'] = $version_matches[1];
        }
    } elseif (preg_match('/Edg\/([0-9\.]+)/', $user_agent, $matches)) {
        $info['browser'] = 'Edge';
        $info['browser_version'] = $matches[1];
    }
    
    // Detect OS
    if (preg_match('/Android ([0-9\.]+)/', $user_agent, $matches)) {
        $info['os'] = 'Android';
        $info['os_version'] = $matches[1];
        $info['is_mobile'] = strpos($user_agent, 'Mobile') !== false;
        $info['is_tablet'] = !$info['is_mobile'];
        $info['is_desktop'] = false;
        $info['device_type'] = $info['is_mobile'] ? 'Mobile' : 'Tablet';
    } elseif (preg_match('/iPhone OS ([0-9_]+)/', $user_agent, $matches)) {
        $info['os'] = 'iOS';
        $info['os_version'] = str_replace('_', '.', $matches[1]);
        $info['is_mobile'] = true;
        $info['is_desktop'] = false;
        $info['device_type'] = 'iPhone';
    } elseif (preg_match('/iPad.*OS ([0-9_]+)/', $user_agent, $matches)) {
        $info['os'] = 'iPadOS';
        $info['os_version'] = str_replace('_', '.', $matches[1]);
        $info['is_tablet'] = true;
        $info['is_desktop'] = false;
        $info['device_type'] = 'iPad';
    } elseif (preg_match('/Mac OS X ([0-9_]+)/', $user_agent, $matches)) {
        $info['os'] = 'macOS';
        $info['os_version'] = str_replace('_', '.', $matches[1]);
        $info['device_type'] = 'Desktop';
    } elseif (strpos($user_agent, 'Windows NT 10.0') !== false) {
        $info['os'] = 'Windows 10/11';
        $info['device_type'] = 'Desktop';
    } elseif (preg_match('/Windows NT ([0-9\.]+)/', $user_agent, $matches)) {
        $info['os'] = 'Windows';
        $info['os_version'] = $matches[1];
        $info['device_type'] = 'Desktop';
    } elseif (strpos($user_agent, 'Linux') !== false) {
        $info['os'] = 'Linux';
        $info['device_type'] = 'Desktop';
    }
    
    return $info;
}

/**
 * Deteksi login yang mencurigakan berdasarkan device dan lokasi
 */
function detect_suspicious_login($user_id, $ip_address, $user_agent) {
    global $db;
    
    $alerts = [];
    
    try {
        // Ambil 10 login terakhir user ini
        $recent_logins = $db->fetchAll("
            SELECT ip_address, user_agent, created_at 
            FROM activity_logs 
            WHERE user_id = ? AND action IN ('LOGIN_ADMIN', 'LOGIN_STAFF') 
            ORDER BY created_at DESC 
            LIMIT 10
        ", [$user_id]);
        
        if (count($recent_logins) > 1) {
            $device_info = get_detailed_device_info($user_agent);
            
            // Cek apakah ini device baru
            $known_devices = [];
            $known_ips = [];
            
            foreach ($recent_logins as $login) {
                if ($login['ip_address'] !== $ip_address) {
                    $known_ips[] = $login['ip_address'];
                }
                if ($login['user_agent'] !== $user_agent) {
                    $prev_device = get_detailed_device_info($login['user_agent']);
                    $known_devices[] = $prev_device['os'] . ' - ' . $prev_device['browser'];
                }
            }
            
            // Alert untuk IP baru
            if (!in_array($ip_address, $known_ips)) {
                $ip_type = 'unknown';
                if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ipv6_info = analyze_ipv6($ip_address);
                    if ($ipv6_info['is_public']) {
                        $ip_type = 'public IPv6';
                    } elseif ($ipv6_info['is_link_local']) {
                        $ip_type = 'link-local IPv6';
                    } elseif ($ipv6_info['is_unique_local']) {
                        $ip_type = 'unique local IPv6';
                    } else {
                        $ip_type = 'IPv6';
                    }
                } elseif (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        $ip_type = 'public IPv4';
                    } else {
                        $ip_type = 'private IPv4';
                    }
                }
                
                $alerts[] = [
                    'type' => 'new_ip',
                    'message' => "Login dari IP address baru: {$ip_address} ({$ip_type})",
                    'severity' => 'warning'
                ];
            }
            
            // Alert untuk device/OS baru
            $current_device = $device_info['os'] . ' - ' . $device_info['browser'];
            if (!in_array($current_device, $known_devices)) {
                $alerts[] = [
                    'type' => 'new_device',
                    'message' => 'Login dari device baru: ' . $current_device,
                    'severity' => 'info'
                ];
            }
            
            // Alert untuk mobile login (jika biasanya desktop)
            if ($device_info['is_mobile'] || $device_info['is_tablet']) {
                $desktop_count = 0;
                foreach ($recent_logins as $login) {
                    $prev_device = get_detailed_device_info($login['user_agent']);
                    if ($prev_device['is_desktop']) {
                        $desktop_count++;
                    }
                }
                
                if ($desktop_count > 7) { // Jika 70% login sebelumnya dari desktop
                    $alerts[] = [
                        'type' => 'mobile_login',
                        'message' => 'Login dari device mobile/tablet (biasanya desktop)',
                        'severity' => 'info'
                    ];
                }
            }
            
            // Alert untuk OS yang jarang digunakan
            $rare_os = ['FreeBSD', 'Unix', 'Chrome OS'];
            if (in_array($device_info['os'], $rare_os)) {
                $alerts[] = [
                    'type' => 'rare_os',
                    'message' => 'Login dari OS yang jarang digunakan: ' . $device_info['os'],
                    'severity' => 'warning'
                ];
            }
        }
        
    } catch (Exception $e) {
        // Ignore errors in detection
    }
    
    return $alerts;
}

/**
 * Log aktivitas dengan deteksi login mencurigakan
 */
function log_activity_with_detection($user_id, $action, $description = '', $document_id = null) {
    global $db;
    
    // Log aktivitas normal
    log_activity($user_id, $action, $description, $document_id);
    
    // Deteksi login mencurigakan hanya untuk aksi login
    if (in_array($action, ['LOGIN_ADMIN', 'LOGIN_STAFF'])) {
        $ip_address = get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $alerts = detect_suspicious_login($user_id, $ip_address, $user_agent);
        
        // Simpan alert ke session untuk ditampilkan
        if (!empty($alerts)) {
            if (!isset($_SESSION['security_alerts'])) {
                $_SESSION['security_alerts'] = [];
            }
            $_SESSION['security_alerts'] = array_merge($_SESSION['security_alerts'], $alerts);
        }
    }
}

/**
 * Cek apakah user sudah login
 */
function is_logged_in() {
    // Cek apakah ada user_id di session
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    // Validasi tab_id: pastikan session digunakan di tab yang benar
    $current_tab_id = get_tab_id();
    
    // Jika ada tab_id di session, pastikan cocok dengan tab_id saat ini
    if (isset($_SESSION['tab_id'])) {
        // Jika session punya tab_id tapi tidak cocok dengan current tab_id
        // Ini berarti session dari tab lain, anggap belum login
        if ($_SESSION['tab_id'] != $current_tab_id) {
            return false;
        }
    } else {
        // Jika session tidak punya tab_id, set sesuai current tab_id
        $_SESSION['tab_id'] = $current_tab_id;
    }
    
    return true;
}

/**
 * Cek apakah user adalah admin
 */
function is_admin() {
    return is_logged_in() && $_SESSION['user_role'] === 'admin';
}

/**
 * Cek apakah user adalah superadmin
 */
function is_superadmin() {
    return is_logged_in() && $_SESSION['user_role'] === 'superadmin';
}

/**
 * Cek apakah user adalah staff
 */
function is_staff() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff';
}

/**
 * Redirect jika bukan admin atau staff (superadmin dilarang untuk aksi ini)
 */
function require_admin_or_staff() {
    require_login();
    // Jika superadmin, arahkan kembali ke dashboard superadmin
    if (is_superadmin()) {
        $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
        header('Location: ../superadmin/dashboard.php' . $tab_param);
        exit();
    }

    if (!is_admin() && !is_staff()) {
        $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
        header('Location: index.php' . $tab_param);
        exit();
    }
}

/**
 * Redirect jika bukan superadmin
 */
function require_superadmin() {
    require_login();
    if (!is_superadmin()) {
        $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
        // Redirect berdasarkan role
        if (isset($_SESSION['user_role'])) {
            if ($_SESSION['user_role'] === 'admin') {
                header('Location: ../dashboard.php' . $tab_param);
            } elseif ($_SESSION['user_role'] === 'staff') {
                header('Location: ../staff/dashboard.php' . $tab_param);
            } else {
                header('Location: ../landing.php' . $tab_param);
            }
        } else {
            header('Location: ../landing.php' . $tab_param);
        }
        exit();
    }
}

/**
 * Redirect jika belum login
 */
function require_login() {
    if (!is_logged_in()) {
        $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
        header('Location: landing.php' . $tab_param);
        exit();
    }
}

/**
 * Redirect jika bukan admin (superadmin juga diizinkan)
 */
function require_admin() {
    require_login();
    if (!is_admin() && !is_superadmin()) {
        $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
        // Redirect staff ke dashboard staff mereka
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff') {
            // Tentukan path relatif ke staff/dashboard.php berdasarkan lokasi script
            $script_dir = dirname($_SERVER['SCRIPT_NAME']);
            $script_dir = str_replace('\\', '/', rtrim($script_dir, '/\\'));
            
            // Normalisasi: hilangkan leading slash dan PROJECT ARSIP LOKER jika ada
            $script_dir = ltrim($script_dir, '/');
            $parts = explode('/', $script_dir);
            
            // Filter bagian yang relevan (abaikan PROJECT ARSIP LOKER di awal)
            $relevant_parts = [];
            $skip_project_name = true;
            foreach ($parts as $part) {
                if (!empty($part)) {
                    if ($skip_project_name && ($part === 'PROJECT ARSIP LOKER' || strpos($part, 'PROJECT') !== false)) {
                        continue; // Skip nama project di awal
                    }
                    $skip_project_name = false;
                    if ($part !== 'PROJECT ARSIP LOKER') {
                        $relevant_parts[] = $part;
                    }
                }
            }
            
            // Hitung kedalaman folder
            $depth = count($relevant_parts);
            
            if ($depth == 0) {
                // Di root project
                $redirect_path = 'staff/dashboard.php';
            } else {
                // Di subfolder, naik ke root dulu
                $redirect_path = str_repeat('../', $depth) . 'staff/dashboard.php';
            }
            
            header('Location: ' . $redirect_path . $tab_param);
        } else {
            // Bukan admin dan bukan staff, redirect ke index
            header('Location: index.php' . $tab_param);
        }
        exit();
    }
}

/**
 * Format tanggal Indonesia
 */
function format_date_indonesia($date, $include_time = false) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $hari = date('d', $timestamp);
    $bulan_num = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    $result = $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
    
    if ($include_time) {
        $result .= ' ' . date('H:i', $timestamp);
    }
    
    return $result;
}

/**
 * Upload file dengan validasi
 */
function upload_file($file, $upload_dir = 'uploads/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'File tidak valid'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error upload file'];
    }
    
    if (!is_allowed_file_type($file['name'])) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 10MB)'];
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = generate_unique_filename($file['name']);
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }
    
    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'size' => $file['size']
    ];
}

/**
 * Hapus file
 */
function delete_file($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}

/**
 * Escape output untuk HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash_message($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

/**
 * Pagination helper
 */
function paginate($total_records, $records_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records
    ];
}
?>
