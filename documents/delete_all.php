<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role admin
require_admin();

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$confirm = $input['confirm'] ?? false;

if (!$confirm) {
    echo json_encode(['success' => false, 'message' => 'Konfirmasi diperlukan']);
    exit();
}

try {
    // Get total count before deletion
    $count_sql = "SELECT COUNT(*) as total FROM documents WHERE status = 'active'";
    $total_before = $db->fetch($count_sql)['total'];
    
    if ($total_before == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tidak ada dokumen untuk dihapus',
            'deleted_count' => 0
        ]);
        exit();
    }
    
    // Get all document IDs for logging
    $documents = $db->fetchAll("SELECT id, full_name FROM documents WHERE status = 'active'");
    
    // Delete all active documents permanently
    $sql = "DELETE FROM documents WHERE status = 'active'";
    $db->execute($sql);
    
    // Log activity
    log_activity($_SESSION['user_id'], 'DELETE_ALL_DOCUMENTS', "Menghapus semua dokumen ($total_before dokumen)");
    
    echo json_encode([ 
        'success' => true, 
        'message' => "Berhasil menghapus $total_before dokumen",
        'deleted_count' => $total_before
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>



