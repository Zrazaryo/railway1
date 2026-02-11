<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$month_number = $_GET['month_number'] ?? '';
if (!$month_number) {
    echo json_encode(['error' => 'No month_number provided']);
    exit;
}

try {
    $row = $db->fetch("SELECT MAX(document_order_number) AS max_order FROM documents WHERE month_number = ? AND status = 'active'", [$month_number]);
    $next_order = (int)($row['max_order'] ?? 0) + 1;
    echo json_encode(['next_order' => $next_order]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
