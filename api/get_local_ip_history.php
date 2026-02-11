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

try {
    // Get current user's local IP detections
    $detections = $db->fetchAll("
        SELECT * FROM local_ip_detections 
        WHERE user_id = ? OR session_id = ?
        ORDER BY created_at DESC 
        LIMIT 10
    ", [$_SESSION['user_id'], session_id()]);
    
    if (empty($detections)) {
        echo json_encode([
            'success' => true,
            'html' => '
                <div class="text-center text-muted py-4">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <p>Belum ada riwayat deteksi IP lokal.</p>
                    <button type="button" class="btn btn-primary" onclick="detectCurrentIPs()">
                        <i class="fas fa-search me-2"></i>Mulai Deteksi
                    </button>
                </div>
            '
        ]);
        exit();
    }
    
    // Generate HTML for detection history
    $html = '<div class="table-responsive">';
    $html .= '<table class="table table-striped">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Waktu</th>';
    $html .= '<th>Server IP</th>';
    $html .= '<th>IP Lokal</th>';
    $html .= '<th>Statistik</th>';
    $html .= '<th>Aksi</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($detections as $detection) {
        $html .= '<tr>';
        
        // Waktu
        $html .= '<td>';
        $html .= '<small>' . format_date_indonesia($detection['created_at'], true) . '</small>';
        $html .= '</td>';
        
        // Server IP
        $html .= '<td>';
        $html .= format_ip_info($detection['server_detected_ip']);
        $html .= '</td>';
        
        // IP Lokal
        $html .= '<td>';
        $html .= '<span class="badge bg-primary">' . $detection['total_local_ips'] . ' IP</span>';
        if ($detection['ipv4_count'] > 0) {
            $html .= ' <span class="badge bg-info">' . $detection['ipv4_count'] . ' IPv4</span>';
        }
        if ($detection['ipv6_count'] > 0) {
            $html .= ' <span class="badge bg-success">' . $detection['ipv6_count'] . ' IPv6</span>';
        }
        $html .= '</td>';
        
        // Statistik
        $html .= '<td>';
        $html .= '<small>';
        $html .= 'Lokal: ' . $detection['local_count'] . '<br>';
        $html .= 'Public: ' . $detection['public_count'];
        $html .= '</small>';
        $html .= '</td>';
        
        // Aksi
        $html .= '<td>';
        $html .= '<button type="button" class="btn btn-sm btn-outline-info" ';
        $html .= 'onclick="showLocalIPDetail(' . $detection['id'] . ')">';
        $html .= '<i class="fas fa-eye"></i>';
        $html .= '</button>';
        $html .= '</td>';
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    // Add statistics summary
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
    
    if ($stats && $stats['total_detections'] > 0) {
        $html .= '<div class="row mt-4">';
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<div class="card text-center">';
        $html .= '<div class="card-body">';
        $html .= '<i class="fas fa-chart-line fa-2x text-primary mb-2"></i>';
        $html .= '<h5 class="card-title">' . number_format($stats['total_detections']) . '</h5>';
        $html .= '<p class="card-text">Total Deteksi</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<div class="card text-center">';
        $html .= '<div class="card-body">';
        $html .= '<i class="fas fa-network-wired fa-2x text-success mb-2"></i>';
        $html .= '<h5 class="card-title">' . number_format($stats['total_ips_detected']) . '</h5>';
        $html .= '<p class="card-text">Total IP Terdeteksi</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<div class="card text-center">';
        $html .= '<div class="card-body">';
        $html .= '<i class="fas fa-globe fa-2x text-info mb-2"></i>';
        $html .= '<h5 class="card-title">' . number_format($stats['total_ipv4']) . ' / ' . number_format($stats['total_ipv6']) . '</h5>';
        $html .= '<p class="card-text">IPv4 / IPv6</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<div class="card text-center">';
        $html .= '<div class="card-body">';
        $html .= '<i class="fas fa-home fa-2x text-warning mb-2"></i>';
        $html .= '<h5 class="card-title">' . number_format($stats['total_local']) . '</h5>';
        $html .= '<p class="card-text">IP Lokal</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>