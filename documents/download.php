<?php
// Pastikan tidak ada output
while (ob_get_level()) {
    ob_end_clean();
}

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login
if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

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
    
    // Get files from document_files table
    $files_sql = "SELECT * FROM document_files WHERE document_id = ? AND file_path != 'STATUS_ONLY' ORDER BY document_type";
    $files = $db->fetchAll($files_sql, [$document_id]);
    
    if (empty($files)) {
        $_SESSION['error_message'] = 'Tidak ada file yang dapat didownload untuk dokumen ini.';
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: ' . $referer);
        exit();
    }
    
    // Ambil file pertama
    $file = $files[0];
    $file_path_db = $file['file_path'];
    $filename = $file['file_name'];
    
    // Log download activity
    log_activity($_SESSION['user_id'], 'DOWNLOAD_DOCUMENT', "Download dokumen: " . ($document['title'] ?? $document['full_name']), $document_id);
    
    // Cari file menggunakan logika yang sama dengan view_file.php
    $file_basename = basename($file_path_db);
    if (empty($file_basename)) {
        $file_basename = $filename;
    }
    
    $possible_paths = [
        __DIR__ . '/uploads/' . $file_basename,
        __DIR__ . '/uploads/' . $filename,
        __DIR__ . '/../uploads/' . $file_basename,
        __DIR__ . '/../uploads/' . $filename,
        __DIR__ . '/../' . ltrim($file_path_db, '/'),
    ];
    
    if (strpos($file_path_db, 'uploads/') !== false) {
        $possible_paths[] = __DIR__ . '/uploads/' . $file_basename;
        $possible_paths[] = __DIR__ . '/uploads/' . $filename;
    }
    
    if (strpos($file_path_db, 'upload/') === 0 && strpos($file_path_db, 'uploads/') !== 0) {
        $corrected_path = str_replace('upload/', 'uploads/', $file_path_db);
        $possible_paths[] = __DIR__ . '/../' . ltrim($corrected_path, '/');
    }
    
    if (strpos($file_path_db, 'uploads/') === 0) {
        $possible_paths[] = __DIR__ . '/uploads/' . substr($file_path_db, strlen('uploads/'));
    }
    
    $absolute_path = null;
    foreach ($possible_paths as $test_path) {
        $normalized_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $test_path);
        if (file_exists($normalized_path) && is_file($normalized_path)) {
            $absolute_path = $normalized_path;
            break;
        }
    }
    
    if (!$absolute_path) {
        $_SESSION['error_message'] = 'File tidak ditemukan di server.';
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: ' . $referer);
        exit();
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $is_image = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
    
    // Set headers - PASTIKAN tidak ada output sebelumnya
    if ($extension === 'pdf') {
        header('Content-Type: application/pdf');
    } elseif ($is_image) {
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        header('Content-Type: ' . ($mime_types[$extension] ?? 'image/jpeg'));
    } else {
        header('Content-Type: application/octet-stream');
    }
    
    // Selalu attachment untuk download
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($absolute_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file
    readfile($absolute_path);
    exit();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    header('Location: index.php');
    exit();
}
