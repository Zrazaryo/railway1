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
    $user_id = $_SESSION['user_id'];
    
    // Cek apakah kolom geolocation sudah ada di database
    $has_geolocation_columns = has_geolocation_columns();
    
    if (!$has_geolocation_columns) {
        // Jika kolom geolocation belum ada, anggap GPS tidak diperlukan
        echo json_encode([
            'success' => true,
            'gps_enabled' => true,
            'message' => 'Geolocation columns not available, GPS not required'
        ]);
        exit();
    }
    
    // Cek apakah user sudah pernah mengaktifkan GPS dalam session ini
    $gps_enabled_session = isset($_SESSION['gps_enabled']) && $_SESSION['gps_enabled'] === true;
    
    // Cek apakah ada record GPS terbaru dari user (dalam 1 jam terakhir)
    $recent_gps = $db->fetch("
        SELECT id, latitude, longitude, created_at, action
        FROM activity_logs 
        WHERE user_id = ? 
        AND latitude IS NOT NULL 
        AND longitude IS NOT NULL 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY created_at DESC 
        LIMIT 1
    ", [$user_id]);
    
    $gps_enabled_db = !empty($recent_gps);
    
    // GPS dianggap enabled jika ada di session ATAU ada record terbaru di database
    $gps_enabled = $gps_enabled_session || $gps_enabled_db;
    
    if ($gps_enabled) {
        // Set session flag
        $_SESSION['gps_enabled'] = true;
        $_SESSION['last_gps_check'] = time();
        
        // Log activity
        log_activity($user_id, 'GPS_STATUS_CHECK', 'GPS status verified as enabled');
    }
    
    echo json_encode([
        'success' => true,
        'gps_enabled' => $gps_enabled,
        'session_gps' => $gps_enabled_session,
        'db_gps' => $gps_enabled_db,
        'last_gps_record' => $recent_gps ? [
            'latitude' => $recent_gps['latitude'],
            'longitude' => $recent_gps['longitude'],
            'created_at' => $recent_gps['created_at'],
            'action' => $recent_gps['action']
        ] : null,
        'message' => $gps_enabled ? 'GPS is enabled' : 'GPS needs to be enabled'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking GPS status: ' . $e->getMessage()
    ]);
}
?>