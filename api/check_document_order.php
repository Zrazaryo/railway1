<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$response = [
    'is_available' => true,
    'message' => ''
];

if (!isset($_GET['month_number']) || !isset($_GET['document_order_number'])) {
    $response['is_available'] = false;
    $response['message'] = 'Parameter kode lemari dan nomor urut dokumen tidak lengkap.';
    echo json_encode($response);
    exit();
}

$month_number = sanitize_input($_GET['month_number']);
$document_order_number = (int) sanitize_input($_GET['document_order_number']);

if (empty($month_number) || $document_order_number <= 0) {
    $response['is_available'] = false;
    $response['message'] = 'Kode lemari atau nomor urut dokumen tidak valid.';
    echo json_encode($response);
    exit();
}

try {
    $sql = "SELECT COUNT(*) FROM documents WHERE month_number = ? AND document_order_number = ? AND status = 'active'";
    $count = $db->fetch($sql, [$month_number, $document_order_number])['COUNT(*)'];

    if ($count > 0) {
        $response['is_available'] = false;
        $response['message'] = 'Nomor urut dokumen ini sudah digunakan di lemari yang sama.';
    }
} catch (Exception $e) {
    $response['is_available'] = false;
    $response['message'] = 'Terjadi kesalahan saat memeriksa ketersediaan nomor urut.';
    // Opsional: log $e->getMessage() untuk debugging
}

echo json_encode($response);


