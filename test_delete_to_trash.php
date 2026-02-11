<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Simple test - tidak perlu login
$db = new Database();

// Cek apakah tabel document_trash ada
echo "<h2>Testing Delete to Trash Function</h2>";
echo "<hr>";

try {
    // Cek struktur tabel documents
    echo "<h3>1. Struktur Tabel documents</h3>";
    $columns = $db->fetchAll("DESC documents");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";
    
    // Cek apakah tabel document_trash ada
    echo "<h3>2. Struktur Tabel document_trash</h3>";
    try {
        $trash_columns = $db->fetchAll("DESC document_trash");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($trash_columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color:red;'><strong>Tabel document_trash belum ada!</strong></p>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Jalankan <code>includes/trash_helper.php</code> atau <code>documents/setup_trash.php</code> terlebih dahulu</p>";
    }
    echo "<br>";
    
    // Cek dokumen yang ada
    echo "<h3>3. Dokumen Aktif yang Ada</h3>";
    $active_docs = $db->fetchAll("SELECT id, document_number, title, full_name, status FROM documents WHERE status = 'active' LIMIT 5");
    if (count($active_docs) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Document Number</th><th>Title</th><th>Full Name</th><th>Status</th></tr>";
        foreach ($active_docs as $doc) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($doc['id']) . "</td>";
            echo "<td>" . htmlspecialchars($doc['document_number']) . "</td>";
            echo "<td>" . htmlspecialchars($doc['title']) . "</td>";
            echo "<td>" . htmlspecialchars($doc['full_name'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($doc['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>Tidak ada dokumen aktif untuk ditest. Silakan buat dokumen terlebih dahulu.</p>";
    }
    echo "<br>";
    
    // Cek dokumen di sampah
    echo "<h3>4. Dokumen di Sampah</h3>";
    try {
        $trash_docs = $db->fetchAll("SELECT id, original_document_id, title, full_name, status, restore_deadline FROM document_trash ORDER BY deleted_at DESC LIMIT 5");
        if (count($trash_docs) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Original ID</th><th>Title</th><th>Full Name</th><th>Status</th><th>Restore Deadline</th></tr>";
            foreach ($trash_docs as $doc) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($doc['id']) . "</td>";
                echo "<td>" . htmlspecialchars($doc['original_document_id']) . "</td>";
                echo "<td>" . htmlspecialchars($doc['title']) . "</td>";
                echo "<td>" . htmlspecialchars($doc['full_name'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($doc['status']) . "</td>";
                echo "<td>" . htmlspecialchars($doc['restore_deadline']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:blue;'>Tidak ada dokumen di sampah.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Tidak bisa query document_trash: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "<br>";
    
    // Test kolom apa saja di documents
    echo "<h3>5. Sample Dokumen Lengkap (untuk debug)</h3>";
    $sample = $db->fetch("SELECT * FROM documents WHERE status = 'active' LIMIT 1");
    if ($sample) {
        echo "<pre>";
        echo "Kolom yang ada di dokumen:\n";
        foreach ($sample as $key => $value) {
            echo "  $key: " . (is_null($value) ? "NULL" : htmlspecialchars(substr($value, 0, 100))) . "\n";
        }
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
