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
$document_ids = $input['ids'] ?? [];

if (empty($document_ids) || !is_array($document_ids)) {
    echo json_encode(['success' => false, 'message' => 'ID dokumen tidak valid']);
    exit();
}

// Validate and sanitize IDs
$document_ids = array_filter(array_map('intval', $document_ids));
if (empty($document_ids)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada dokumen yang valid untuk dihapus']);
    exit();
}

try {
    $deleted_count = 0;
    $failed_count = 0;
    $errors = [];
    
    foreach ($document_ids as $document_id) {
        if ($document_id <= 0) {
            $failed_count++;
            continue;
        }
        
        // Cek ada
        $sql = "SELECT id, full_name FROM documents WHERE id = ?";
        $document = $db->fetch($sql, [$document_id]);
        if (!$document) {
            $failed_count++;
            continue;
        }

        // Hapus permanen, tidak kirim ke pemusnahan otomatis
        $sql = "DELETE FROM documents WHERE id = ?";
        $db->execute($sql, [$document_id]);

        log_activity($_SESSION['user_id'], 'DELETE_DOCUMENT', "Menghapus dokumen: " . ($document['full_name'] ?? 'ID: ' . $document_id), $document_id);
        $deleted_count++;
    }
    
    if ($deleted_count > 0) {
        echo json_encode([
            'success' => true, 
            'message' => "Berhasil menghapus $deleted_count dokumen" . ($failed_count > 0 ? ", $failed_count gagal" : ''),
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Tidak ada dokumen yang berhasil dihapus'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>





