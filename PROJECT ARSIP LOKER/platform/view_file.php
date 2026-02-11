<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$document_id = (int)($_GET['id'] ?? 0);

if ($document_id <= 0) {
    http_response_code(404);
    die('File not found');
}

try {
    // Get document details
    $sql = "SELECT * FROM documents WHERE id = ? AND status = 'active'";
    $document = $db->fetch($sql, [$document_id]);
    
    if (!$document || empty($document['file_path'])) {
        http_response_code(404);
        die('File not found');
    }
    
    // Normalize file path
    $file_path_db = $document['file_path'];
    $filename = $document['file_name'] ?: basename($file_path_db);
    
    // List of possible paths to try
    // Normalize file_path_db - remove leading ../ if present
    $normalized_file_path = $file_path_db;
    if (strpos($normalized_file_path, '../') === 0) {
        $normalized_file_path = substr($normalized_file_path, 3);
    }
    $normalized_file_path = ltrim($normalized_file_path, '/');
    
    $possible_paths = [
        __DIR__ . '/../documents/uploads/' . basename($normalized_file_path),
        __DIR__ . '/../documents/uploads/' . $filename,
        __DIR__ . '/../' . $normalized_file_path,
        __DIR__ . '/../documents/' . $normalized_file_path,
        $file_path_db,
        $normalized_file_path
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
        http_response_code(404);
        die('File not found on server');
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $is_image = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
    
    // Set appropriate headers
    if ($is_image) {
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        header('Content-Type: ' . ($mime_types[$extension] ?? 'image/jpeg'));
        header('Content-Disposition: inline; filename="' . $filename . '"');
    } else {
        // For PDF, try to display inline
        if ($extension === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
    }
    
    header('Content-Length: ' . filesize($absolute_path));
    header('Cache-Control: public, max-age=3600');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read and output file
    readfile($absolute_path);
    exit();
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error loading file: ' . $e->getMessage());
}
?>


