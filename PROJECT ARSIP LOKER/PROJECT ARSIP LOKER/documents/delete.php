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
$document_id = (int)($input['id'] ?? 0);

if ($document_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID dokumen tidak valid']);
    exit();
}

try {
    // Pastikan dokumen ada
    $sql = "SELECT id, title FROM documents WHERE id = ?";
    $document = $db->fetch($sql, [$document_id]);
    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan']);
        exit();
    }

    // Hapus permanen (tidak dipindah ke pemusnahan)
    $sql = "DELETE FROM documents WHERE id = ?";
    $db->execute($sql, [$document_id]);

    // Log activity
    log_activity($_SESSION['user_id'], 'DELETE_DOCUMENT', "Menghapus dokumen: " . ($document['title'] ?? "ID $document_id"), $document_id);

    echo json_encode(['success' => true, 'message' => 'Dokumen berhasil dihapus permanen']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
