<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$document_id = (int)($_GET['id'] ?? 0);

if ($document_id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    // Get document details
    $sql = "SELECT * FROM documents WHERE id = ? AND status = 'active'";
    $document = $db->fetch($sql, [$document_id]);
    
    if (!$document || empty($document['file_path'])) {
        header('Location: index.php?error=' . urlencode('Dokumen tidak ditemukan'));
        exit();
    }
    
    // Normalize file path
    $file_path_db = $document['file_path'];
    $filename = $document['file_name'] ?: basename($file_path_db);
    
    // List of possible paths to try
    $possible_paths = [
        __DIR__ . '/../documents/uploads/' . basename($file_path_db),
        __DIR__ . '/../documents/uploads/' . $filename,
        __DIR__ . '/../' . ltrim($file_path_db, '/'),
        $file_path_db
    ];
    
    // Try each path until we find the file
    $absolute_path = null;
    foreach ($possible_paths as $test_path) {
        $normalized_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $test_path);
        if (file_exists($normalized_path) && is_file($normalized_path)) {
            $absolute_path = $normalized_path;
            break;
        }
    }
    
    if (!$absolute_path || !file_exists($absolute_path)) {
        header('Location: index.php?error=' . urlencode('File tidak ditemukan di server'));
        exit();
    }
    
    // Log download activity
    log_activity($_SESSION['user_id'], 'PLATFORM_DOWNLOAD', "Download dokumen platform: " . $document['title'], $document_id);
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($absolute_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read and output file
    readfile($absolute_path);
    exit();
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode('Terjadi kesalahan: ' . $e->getMessage()));
    exit();
}
?>


