<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role admin
require_admin();

$document_id = (int)($_GET['id'] ?? 0);

if ($document_id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    // Get document details
    $sql = "SELECT * FROM documents WHERE id = ? AND status != 'deleted'";
    $document = $db->fetch($sql, [$document_id]);
    
    if (!$document) {
        header('Location: index.php');
        exit();
    }
    
    $file_path = $document['file_path'];
    $original_filename = $document['file_name'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        header('Location: index.php');
        exit();
    }
    
    // Log download activity
    log_activity($_SESSION['user_id'], 'DOWNLOAD_DOCUMENT', "Download dokumen: " . $document['title'], $document_id);
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $original_filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read and output file
    readfile($file_path);
    exit();
    
} catch (Exception $e) {
    header('Location: index.php');
    exit();
}
?>
