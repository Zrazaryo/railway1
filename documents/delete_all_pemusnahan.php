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
    $count_sql = "SELECT COUNT(*) as total FROM documents WHERE status = 'deleted'";
    $total_before = $db->fetch($count_sql)['total'];
    
    if ($total_before == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tidak ada dokumen pemusnahan untuk dihapus',
            'deleted_count' => 0
        ]);
        exit();
    }
    
    // Get all document IDs for logging
    $documents = $db->fetchAll("SELECT id, full_name FROM documents WHERE status = 'deleted'");
    
    // Delete all document files first
    $document_files = $db->fetchAll("
        SELECT df.* FROM document_files df 
        INNER JOIN documents d ON df.document_id = d.id 
        WHERE d.status = 'deleted'
    ");
    
    foreach ($document_files as $file) {
        if (!empty($file['file_path']) && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
    }
    
    // Delete document files records
    $db->execute("
        DELETE df FROM document_files df 
        INNER JOIN documents d ON df.document_id = d.id 
        WHERE d.status = 'deleted'
    ");
    
    // Delete all deleted documents permanently
    $sql = "DELETE FROM documents WHERE status = 'deleted'";
    $db->execute($sql);
    
    // Log activity
    log_activity($_SESSION['user_id'], 'DELETE_ALL_PEMUSNAHAN', "Menghapus semua dokumen pemusnahan ($total_before dokumen)");
    
    echo json_encode([
        'success' => true, 
        'message' => "Berhasil menghapus $total_before dokumen pemusnahan secara permanen",
        'deleted_count' => $total_before
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>