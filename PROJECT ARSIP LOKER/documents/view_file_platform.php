<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$file_path = $_GET['path'] ?? '';

if (empty($file_path)) {
    http_response_code(404);
    die('File path not provided');
}

try {
    // Normalize file path
    $file_path_db = $file_path;
    $filename = basename($file_path_db);
    
    // List of possible paths to try
    $possible_paths = [
        __DIR__ . '/uploads/' . basename($file_path_db),
        __DIR__ . '/uploads/' . $filename,
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
