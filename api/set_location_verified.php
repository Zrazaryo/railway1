<?php
require_once __DIR__ . '/bootstrap_vercel.php';
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['verified']) && $input['verified'] === true) {
        $_SESSION['location_verified'] = true;
        $_SESSION['location_verified_time'] = time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Location verification set successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid verification data'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>