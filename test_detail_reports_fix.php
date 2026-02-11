<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Test Detail Reports Fix</h1>";

try {
    // Test untuk user staff (ID: 14)
    $user_id = 14;
    
    echo "<h2>Testing for Staff User (ID: $user_id)</h2>";
    
    // Test 1: Total keseluruhan (semua waktu) - menggunakan original creator
    $overall_stats = $db->fetch("
        SELECT 
            COUNT(CASE WHEN status = 'active' THEN 1 END) AS total_active,
            COUNT(CASE WHEN status = 'deleted' THEN 1 END) AS total_destroyed,
            COUNT(*) AS total_all
        FROM documents
        WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?))
    ", [$user_id, $user_id]);
    
    echo "<h3>Overall Statistics (All Time):</h3>";
    echo "- Total Active Documents: " . ($overall_stats['total_active'] ?? 0) . "<br>";
    echo "- Total Destroyed Documents: " . ($overall_stats['total_destroyed'] ?? 0) . "<br>";
    echo "- Total All Documents: " . ($overall_stats['total_all'] ?? 0) . "<br>";
    
    // Test 2: Statistik untuk tanggal tertentu (29 Des 2025)
    $selected_date = '2025-12-29';
    $start_datetime = $selected_date . ' 00:00:00';
    $end_datetime = $selected_date . ' 23:59:59';
    
    $date_stats = $db->fetch("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_total
        FROM documents
        WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) AND created_at BETWEEN ? AND ?
    ", [$user_id, $user_id, $start_datetime, $end_datetime]);
    
    echo "<h3>Statistics for Date ($selected_date):</h3>";
    echo "- Total Documents on Date: " . ($date_stats['total'] ?? 0) . "<br>";
    echo "- Active Documents on Date: " . ($date_stats['active_total'] ?? 0) . "<br>";
    
    // Test 3: Cek dokumen yang ada untuk user ini
    echo "<h3>Documents for this user:</h3>";
    $user_docs = $db->fetchAll("
        SELECT d.id, d.full_name, d.status, d.created_at, d.created_by, d.original_created_by,
               u.full_name as created_by_name,
               u_orig.full_name as original_created_by_name
        FROM documents d
        LEFT JOIN users u ON d.created_by = u.id
        LEFT JOIN users u_orig ON d.original_created_by = u_orig.id
        WHERE (d.original_created_by = ? OR (d.original_created_by IS NULL AND d.created_by = ?))
        ORDER BY d.created_at DESC
    ", [$user_id, $user_id]);
    
    if (count($user_docs) > 0) {
        foreach ($user_docs as $doc) {
            echo "- {$doc['full_name']} (Status: {$doc['status']}, Created: {$doc['created_at']}, By: {$doc['created_by_name']}, Original: " . ($doc['original_created_by_name'] ?: 'NULL') . ")<br>";
        }
    } else {
        echo "No documents found for this user<br>";
    }
    
    echo "<h2>✅ Test Results Summary:</h2>";
    echo "<p><strong>The cards should now show:</strong></p>";
    echo "<ul>";
    echo "<li>Total Dokumen Aktif: " . ($overall_stats['total_active'] ?? 0) . "</li>";
    echo "<li>Total Dokumen Pemusnahan: " . ($overall_stats['total_destroyed'] ?? 0) . "</li>";
    echo "</ul>";
    echo "<p>These are overall totals, not limited to a specific date.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error occurred:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>