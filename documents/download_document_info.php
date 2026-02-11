<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login
require_login();

$document_id = (int)($_GET['id'] ?? 0);

if ($document_id <= 0) {
    http_response_code(404);
    die('Invalid document ID');
}

try {
    // Get document details
    $sql = "SELECT d.*, dc.category_name, u.full_name as created_by_name
            FROM documents d
            LEFT JOIN document_categories dc ON d.category_id = dc.id
            LEFT JOIN users u ON d.created_by = u.id
            WHERE d.id = ?";
    
    $document = $db->fetch($sql, [$document_id]);
    
    if (!$document) {
        http_response_code(404);
        die('Document not found');
    }
    
    // Create text content
    $content = "INFORMASI DOKUMEN\n";
    $content .= "=====================================\n\n";
    
    $content .= "Nomor Dokumen: " . ($document['document_number'] ?? '-') . "\n";
    $content .= "Judul: " . ($document['title'] ?? '-') . "\n";
    $content .= "Kategori: " . ($document['category_name'] ?? '-') . "\n";
    $content .= "Tanggal: " . ($document['created_at'] ? date('d/m/Y H:i:s', strtotime($document['created_at'])) : '-') . "\n";
    $content .= "Dibuat oleh: " . ($document['created_by_name'] ?? '-') . "\n\n";
    
    $content .= "DATA PEMILIK\n";
    $content .= "-------------------------------------\n";
    $content .= "Nama Lengkap: " . ($document['full_name'] ?? '-') . "\n";
    $content .= "NIK: " . ($document['nik'] ?? '-') . "\n";
    $content .= "Nomor Paspor: " . ($document['passport_number'] ?? '-') . "\n";
    $content .= "Tanggal Lahir: " . ($document['birth_date'] ? date('d/m/Y', strtotime($document['birth_date'])) : '-') . "\n";
    $content .= "Kategori Warga: " . ($document['citizen_category'] ?? '-') . "\n\n";
    
    $content .= "DESKRIPSI\n";
    $content .= "-------------------------------------\n";
    $content .= ($document['description'] ?? 'Tidak ada deskripsi') . "\n\n";
    
    $content .= "CATATAN: File asli tidak tersedia untuk dokumen ini.\n";
    $content .= "Dokumen ini dibuat pada: " . ($document['created_at'] ? date('d/m/Y H:i:s', strtotime($document['created_at'])) : '-') . "\n";
    
    // Create filename
    $filename = 'DOC_' . ($document['document_number'] ?? 'INFO') . '_' . date('YmdHis') . '.txt';
    
    // Send file
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    
    echo $content;
    exit();
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>
