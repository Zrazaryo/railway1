<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login
require_login();

// Test pencarian dengan nomor paspor
$test_passport = $_GET['test_passport'] ?? 'A1234567';

try {
    // Query 1: Cek apakah ada data dengan passport_number tertentu
    echo "<h2>Test Pencarian Nomor Paspor</h2>";
    echo "<p>Mencari dokumen dengan nomor paspor: <strong>" . e($test_passport) . "</strong></p>";
    
    // Test direct query
    $sql = "SELECT id, document_number, full_name, nik, passport_number, month_number FROM documents WHERE passport_number LIKE ?";
    $results = $db->fetchAll($sql, ['%' . $test_passport . '%']);
    
    echo "<h3>Hasil Pencarian:</h3>";
    echo "<p>Ditemukan " . count($results) . " dokumen</p>";
    
    if (count($results) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>No. Dokumen</th><th>Nama Lengkap</th><th>NIK</th><th>No. Paspor</th><th>Bulan/Rak</th></tr>";
        foreach ($results as $r) {
            echo "<tr>";
            echo "<td>" . e($r['id']) . "</td>";
            echo "<td>" . e($r['document_number']) . "</td>";
            echo "<td>" . e($r['full_name']) . "</td>";
            echo "<td>" . e($r['nik']) . "</td>";
            echo "<td>" . e($r['passport_number']) . "</td>";
            echo "<td>" . e($r['month_number']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Tidak ada dokumen dengan nomor paspor tersebut. Pastikan ada data dalam database.</p>";
    }
    
    // Test Query 2: Cek struktur kolom documents
    echo "<h3>Struktur Kolom Table Documents:</h3>";
    $columns = $db->fetchAll("SHOW COLUMNS FROM documents");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['id', 'passport_number', 'full_name', 'nik', 'document_number'])) {
            echo "<tr>";
            echo "<td>" . e($col['Field']) . "</td>";
            echo "<td>" . e($col['Type']) . "</td>";
            echo "<td>" . e($col['Null']) . "</td>";
            echo "<td>" . e($col['Key']) . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . e($e->getMessage()) . "</p>";
}
?>
