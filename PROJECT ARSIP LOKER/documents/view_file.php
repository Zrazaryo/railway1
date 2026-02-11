<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$file_id = (int)($_GET['id'] ?? 0);

if ($file_id <= 0) {
    http_response_code(404);
    die('File not found');
}

try {
    // Get file details from document_files table
    $sql = "SELECT df.*, d.id as document_id 
            FROM document_files df
            INNER JOIN documents d ON df.document_id = d.id
            WHERE df.id = ? AND d.status != 'deleted'";
    
    $file = $db->fetch($sql, [$file_id]);
    
    if (!$file) {
        http_response_code(404);
        die('File not found');
    }
    
    // Normalize file path - try multiple possible locations
    $file_path_db = $file['file_path'];
    $filename = $file['file_name'];
    $absolute_path = null;
    
    // Extract just the filename if path contains directory
    $file_basename = basename($file_path_db);
    if (empty($file_basename)) {
        $file_basename = $filename;
    }
    
    // List of possible paths to try (in order of likelihood)
    $possible_paths = [];
    
    // 1. Try documents/uploads/ folder first (most likely based on file structure)
    $possible_paths[] = __DIR__ . '/uploads/' . $file_basename;
    $possible_paths[] = __DIR__ . '/uploads/' . $filename;
    
    // 2. Try root uploads/ folder
    $possible_paths[] = __DIR__ . '/../uploads/' . $file_basename;
    $possible_paths[] = __DIR__ . '/../uploads/' . $filename;
    
    // 3. Original path from database (relative to root)
    $possible_paths[] = __DIR__ . '/../' . ltrim($file_path_db, '/');
    
    // 4. If path in DB contains uploads/, try in documents/uploads/ with just filename
    if (strpos($file_path_db, 'uploads/') !== false) {
        $possible_paths[] = __DIR__ . '/uploads/' . $file_basename;
        $possible_paths[] = __DIR__ . '/uploads/' . $filename;
    }
    
    // 5. If path starts with upload/ (typo), try uploads/
    if (strpos($file_path_db, 'upload/') === 0 && strpos($file_path_db, 'uploads/') !== 0) {
        $corrected_path = str_replace('upload/', 'uploads/', $file_path_db);
        $possible_paths[] = __DIR__ . '/../' . $corrected_path;
        $possible_paths[] = __DIR__ . '/uploads/' . basename($corrected_path);
    }
    
    // 6. Try documents/uploads/ with full path from DB (remove uploads/ prefix)
    if (strpos($file_path_db, 'uploads/') === 0) {
        $possible_paths[] = __DIR__ . '/uploads/' . substr($file_path_db, strlen('uploads/'));
    }
    
    // Remove null values
    $possible_paths = array_filter($possible_paths, function($path) {
        return $path !== null;
    });
    
    // Try each path until we find the file
    foreach ($possible_paths as $test_path) {
        $normalized_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $test_path);
        if (file_exists($normalized_path) && is_file($normalized_path)) {
            $absolute_path = $normalized_path;
            break;
        }
    }
    
    // If file still not found, return 404 with helpful message
    if (!$absolute_path || !file_exists($absolute_path)) {
        http_response_code(404);
        // Show helpful error message
        $error_msg = 'File not found on server.<br>';
        $error_msg .= 'Database path: ' . htmlspecialchars($file_path_db) . '<br>';
        $error_msg .= 'Filename: ' . htmlspecialchars($filename) . '<br>';
        $error_msg .= 'Tried locations:<br>';
        foreach ($possible_paths as $tried_path) {
            $error_msg .= '- ' . htmlspecialchars($tried_path) . '<br>';
        }
        die($error_msg);
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
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
        header('Content-Disposition: inline; filename="' . $file['file_name'] . '"');
    } else {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
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
    die('Error loading file');
}
?>

