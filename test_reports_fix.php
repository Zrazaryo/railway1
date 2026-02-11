<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Test Reports Fix - Original Creator Tracking</h1>";

try {
    // Test 1: Check documents in pemusnahan with original creators
    echo "<h2>1. Documents in Pemusnahan (Deleted Status)</h2>";
    $pemusnahan_docs = $db->fetchAll("
        SELECT d.id, d.full_name, d.status, d.created_by, d.original_created_by,
               u.full_name as created_by_name,
               u_orig.full_name as original_created_by_name,
               COALESCE(u_orig.full_name, u.full_name) AS effective_creator
        FROM documents d
        LEFT JOIN users u ON d.created_by = u.id
        LEFT JOIN users u_orig ON d.original_created_by = u_orig.id
        WHERE d.status = 'deleted'
        ORDER BY d.id DESC
        LIMIT 10
    ");
    
    if (count($pemusnahan_docs) > 0) {
        echo "Found " . count($pemusnahan_docs) . " documents in pemusnahan:<br>";
        foreach ($pemusnahan_docs as $doc) {
            echo "- ID: {$doc['id']}, Name: {$doc['full_name']}, Created by: {$doc['created_by_name']}, Original: {$doc['original_created_by_name']}, Effective: {$doc['effective_creator']}<br>";
        }
    } else {
        echo "No documents found in pemusnahan<br>";
    }
    
    // Test 2: Check reports query with original creator logic
    echo "<h2>2. Reports Query Test (with Original Creator Logic)</h2>";
    $users_with_docs = $db->fetchAll("
        SELECT 
            u.id,
            u.full_name,
            u.username,
            u.role,
            COUNT(CASE WHEN d.status = 'active' AND (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id)) THEN 1 END) as total_dokumen_keseluruhan,
            COUNT(CASE WHEN d.status = 'deleted' AND (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id)) THEN 1 END) as total_dokumen_pemusnahan
        FROM users u
        LEFT JOIN documents d ON (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id))
        WHERE u.status = 'active'
        GROUP BY u.id, u.full_name, u.username, u.role
        HAVING (total_dokumen_keseluruhan > 0 OR total_dokumen_pemusnahan > 0 OR u.role IN ('admin', 'staff'))
        ORDER BY u.full_name ASC
    ");
    
    echo "Reports query results:<br>";
    foreach ($users_with_docs as $user) {
        echo "- {$user['full_name']} ({$user['role']}): Active={$user['total_dokumen_keseluruhan']}, Pemusnahan={$user['total_dokumen_pemusnahan']}<br>";
    }
    
    // Test 3: Compare old vs new logic for specific user
    echo "<h2>3. Comparison Test for Staff User</h2>";
    
    // Find a staff user
    $staff_user = $db->fetch("SELECT id, full_name, username FROM users WHERE role = 'staff' AND status = 'active' LIMIT 1");
    
    if ($staff_user) {
        echo "Testing for staff user: {$staff_user['full_name']} (ID: {$staff_user['id']})<br><br>";
        
        // Old logic (only created_by)
        $old_logic = $db->fetch("
            SELECT 
                COUNT(CASE WHEN status = 'deleted' THEN 1 END) as pemusnahan_count
            FROM documents 
            WHERE created_by = ?
        ", [$staff_user['id']]);
        
        // New logic (original_created_by OR created_by)
        $new_logic = $db->fetch("
            SELECT 
                COUNT(CASE WHEN status = 'deleted' THEN 1 END) as pemusnahan_count
            FROM documents 
            WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?))
        ", [$staff_user['id'], $staff_user['id']]);
        
        echo "Old logic (created_by only): {$old_logic['pemusnahan_count']} documents<br>";
        echo "New logic (original_created_by priority): {$new_logic['pemusnahan_count']} documents<br>";
        
        if ($new_logic['pemusnahan_count'] > $old_logic['pemusnahan_count']) {
            echo "✅ <strong>Fix working!</strong> New logic shows more documents for staff user<br>";
        } elseif ($new_logic['pemusnahan_count'] == $old_logic['pemusnahan_count']) {
            echo "ℹ️ Same count - this is normal if no documents were imported with original creator info<br>";
        } else {
            echo "⚠️ Unexpected result - new logic shows fewer documents<br>";
        }
        
        // Show specific documents for this staff user
        echo "<br><strong>Documents for this staff user (new logic):</strong><br>";
        $staff_docs = $db->fetchAll("
            SELECT d.id, d.full_name, d.status, d.created_by, d.original_created_by,
                   u.full_name as created_by_name,
                   u_orig.full_name as original_created_by_name
            FROM documents d
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN users u_orig ON d.original_created_by = u_orig.id
            WHERE (d.original_created_by = ? OR (d.original_created_by IS NULL AND d.created_by = ?))
            AND d.status = 'deleted'
        ", [$staff_user['id'], $staff_user['id']]);
        
        foreach ($staff_docs as $doc) {
            echo "- {$doc['full_name']} (Status: {$doc['status']}, Created by: {$doc['created_by_name']}, Original: " . ($doc['original_created_by_name'] ?: 'NULL') . ")<br>";
        }
    } else {
        echo "No staff user found for testing<br>";
    }
    
    echo "<h2>✅ Test Completed</h2>";
    echo "<p><strong>Summary:</strong> The reports query has been updated to use original creator logic. This should fix the issue where staff documents in pemusnahan were not showing up in reports.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error occurred:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>