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
    
    // Reset GPS session variables
    unset($_SESSION['gps_enabled']);
    unset($_SESSION['last_location']);
    unset($_SESSION['last_gps_update']);
    unset($_SESSION['last_gps_check']);
    
    // Log the reset action
    log_activity($user_id, 'GPS_SESSION_RESET', 'GPS session direset untuk testing');
    
    echo json_encode([
        'success' => true,
        'message' => 'GPS session berhasil direset'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error resetting GPS session: ' . $e->getMessage()
    ]);
}
?>