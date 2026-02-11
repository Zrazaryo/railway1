<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$document_id = (int)($_GET['document_id'] ?? 0);

if ($document_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit();
}

try {
    // Cari file dengan prioritas: file sebenarnya > STATUS_ONLY
    $files_sql = "SELECT id, file_path FROM document_files WHERE document_id = ? ORDER BY CASE WHEN file_path = 'STATUS_ONLY' THEN 1 ELSE 0 END ASC, id ASC LIMIT 1";
    $file = $db->fetch($files_sql, [$document_id]);
    
    if ($file) {
        // Jika file adalah STATUS_ONLY, cari file lain
        if ($file['file_path'] === 'STATUS_ONLY') {
            $alt_sql = "SELECT id FROM document_files WHERE document_id = ? AND file_path != 'STATUS_ONLY' LIMIT 1";
            $alt_file = $db->fetch($alt_sql, [$document_id]);
            if ($alt_file) {
                echo json_encode(['success' => true, 'file_id' => $alt_file['id']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No downloadable file found']);
            }
        } else {
            echo json_encode(['success' => true, 'file_id' => $file['id']]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file found for this document']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>



