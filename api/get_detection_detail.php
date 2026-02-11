<?php
require_once __DIR__ . '/bootstrap_vercel.php';
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
require_login();

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$detection_id = $input['detection_id'] ?? 0;

if (!$detection_id) {
    echo json_encode(['success' => false, 'message' => 'Detection ID required']);
    exit();
}

try {
    // Get detection details
    $detection = $db->fetch("
        SELECT * FROM local_ip_detections 
        WHERE id = ? AND (user_id = ? OR session_id = ?)
    ", [$detection_id, $_SESSION['user_id'], session_id()]);
    
    if (!$detection) {
        echo json_encode(['success' => false, 'message' => 'Detection not found']);
        exit();
    }
    
    // Parse JSON data
    $local_ips = json_decode($detection['local_ips_data'], true) ?: [];
    $network_info = json_decode($detection['network_info'], true) ?: [];
    $client_info = json_decode($detection['client_info'], true) ?: [];
    
    // Generate HTML
    $html = '<div class="container-fluid">';
    
    // Basic Info
    $html .= '<div class="row mb-3">';
    $html .= '<div class="col-12">';
    $html .= '<h6><i class="fas fa-info-circle me-2"></i>Informasi Dasar</h6>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Waktu Deteksi:</strong></td><td>' . format_date_indonesia($detection['created_at'], true) . '</td></tr>';
    $html .= '<tr><td><strong>Server IP:</strong></td><td>' . format_ip_info($detection['server_detected_ip']) . '</td></tr>';
    $html .= '<tr><td><strong>Session ID:</strong></td><td><code>' . e(substr($detection['session_id'], 0, 16)) . '...</code></td></tr>';
    $html .= '<tr><td><strong>Total IP Lokal:</strong></td><td><span class="badge bg-primary">' . $detection['total_local_ips'] . '</span></td></tr>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Statistics
    $html .= '<div class="row mb-3">';
    $html .= '<div class="col-12">';
    $html .= '<h6><i class="fas fa-chart-bar me-2"></i>Statistik</h6>';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-3"><div class="text-center"><h5 class="text-info">' . $detection['ipv4_count'] . '</h5><small>IPv4</small></div></div>';
    $html .= '<div class="col-md-3"><div class="text-center"><h5 class="text-success">' . $detection['ipv6_count'] . '</h5><small>IPv6</small></div></div>';
    $html .= '<div class="col-md-3"><div class="text-center"><h5 class="text-warning">' . $detection['local_count'] . '</h5><small>Lokal</small></div></div>';
    $html .= '<div class="col-md-3"><div class="text-center"><h5 class="text-primary">' . $detection['public_count'] . '</h5><small>Public</small></div></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Local IPs
    if (!empty($local_ips)) {
        $html .= '<div class="row mb-3">';
        $html .= '<div class="col-12">';
        $html .= '<h6><i class="fas fa-network-wired me-2"></i>IP Address Terdeteksi</h6>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-sm table-striped">';
        $html .= '<thead><tr><th>IP Address</th><th>Type</th><th>Status</th><th>Source</th><th>Timestamp</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($local_ips as $ip_info) {
            $html .= '<tr>';
            $html .= '<td><code>' . e($ip_info['ip']) . '</code></td>';
            
            // Type badge
            $type_class = $ip_info['type'] === 'IPv6' ? 'bg-success' : 'bg-info';
            $html .= '<td><span class="badge ' . $type_class . '">' . e($ip_info['type']) . '</span></td>';
            
            // Status badges
            $html .= '<td>';
            if ($ip_info['isLocal']) {
                $html .= '<span class="badge bg-warning me-1">Lokal</span>';
            }
            if ($ip_info['isPublic']) {
                $html .= '<span class="badge bg-success me-1">Public</span>';
            }
            $html .= '</td>';
            
            $html .= '<td><small>' . e($ip_info['source']) . '</small></td>';
            $html .= '<td><small>' . date('H:i:s', strtotime($ip_info['timestamp'])) . '</small></td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Network Info
    if (!empty($network_info)) {
        $html .= '<div class="row mb-3">';
        $html .= '<div class="col-12">';
        $html .= '<h6><i class="fas fa-wifi me-2"></i>Informasi Network</h6>';
        $html .= '<table class="table table-sm">';
        
        foreach ($network_info as $key => $value) {
            $html .= '<tr>';
            $html .= '<td><strong>' . e(ucfirst(str_replace('_', ' ', $key))) . ':</strong></td>';
            $html .= '<td>' . e($value) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Client Info
    if (!empty($client_info)) {
        $html .= '<div class="row mb-3">';
        $html .= '<div class="col-12">';
        $html .= '<h6><i class="fas fa-desktop me-2"></i>Informasi Client</h6>';
        $html .= '<table class="table table-sm">';
        
        // Basic client info
        if (isset($client_info['platform'])) {
            $html .= '<tr><td><strong>Platform:</strong></td><td>' . e($client_info['platform']) . '</td></tr>';
        }
        if (isset($client_info['language'])) {
            $html .= '<tr><td><strong>Language:</strong></td><td>' . e($client_info['language']) . '</td></tr>';
        }
        if (isset($client_info['timezone'])) {
            $html .= '<tr><td><strong>Timezone:</strong></td><td>' . e($client_info['timezone']) . '</td></tr>';
        }
        if (isset($client_info['cookieEnabled'])) {
            $html .= '<tr><td><strong>Cookie Enabled:</strong></td><td>' . ($client_info['cookieEnabled'] ? 'Yes' : 'No') . '</td></tr>';
        }
        if (isset($client_info['onLine'])) {
            $html .= '<tr><td><strong>Online:</strong></td><td>' . ($client_info['onLine'] ? 'Yes' : 'No') . '</td></tr>';
        }
        
        // Screen info
        if (isset($client_info['screen'])) {
            $screen = $client_info['screen'];
            $html .= '<tr><td><strong>Screen:</strong></td><td>' . 
                     e($screen['width']) . 'x' . e($screen['height']) . 
                     ' (' . e($screen['colorDepth']) . '-bit)' . 
                     '</td></tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // User Agent
    if (isset($client_info['user_agent'])) {
        $html .= '<div class="row">';
        $html .= '<div class="col-12">';
        $html .= '<h6><i class="fas fa-code me-2"></i>User Agent</h6>';
        $html .= '<div class="bg-light p-2 rounded">';
        $html .= '<small style="word-break: break-all;">' . e($client_info['user_agent']) . '</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'detection' => $detection
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>