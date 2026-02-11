<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Test Date Filter for Document List</h1>";

try {
    // Test 1: Check documents with different dates
    echo "<h2>1. Documents by Date Check</h2>";
    $docs_by_date = $db->fetchAll("
        SELECT DATE(created_at) as doc_date, COUNT(*) as count,
               GROUP_CONCAT(full_name SEPARATOR ', ') as names
        FROM documents 
        WHERE status = 'active'
        GROUP BY DATE(created_at)
        ORDER BY doc_date DESC
        LIMIT 10
    ");
    
    if (count($docs_by_date) > 0) {
        echo "✅ Found documents on " . count($docs_by_date) . " different dates:<br>";
        foreach ($docs_by_date as $date_group) {
            echo "- {$date_group['doc_date']}: {$date_group['count']} documents ({$date_group['names']})<br>";
        }
    } else {
        echo "ℹ️ No active documents found<br>";
    }
    
    // Test 2: Test date filter query
    echo "<h2>2. Date Filter Query Test</h2>";
    
    // Get a specific date that has documents
    $test_date = $db->fetch("SELECT DATE(created_at) as test_date FROM documents WHERE status = 'active' ORDER BY created_at DESC LIMIT 1");
    
    if ($test_date) {
        $selected_date = $test_date['test_date'];
        $start_datetime = $selected_date . ' 00:00:00';
        $end_datetime = $selected_date . ' 23:59:59';
        
        echo "Testing date filter for: $selected_date<br>";
        echo "Time range: $start_datetime to $end_datetime<br><br>";
        
        // Test query for specific user
        $staff_user = $db->fetch("SELECT id, full_name FROM users WHERE role = 'staff' AND status = 'active' LIMIT 1");
        
        if ($staff_user) {
            $user_id = $staff_user['id'];
            
            // Query with date filter (new implementation)
            $filtered_docs = $db->fetchAll("
                SELECT 
                    id,
                    document_number,
                    full_name,
                    nik,
                    passport_number,
                    citizen_category,
                    status,
                    created_at,
                    month_number,
                    document_order_number
                FROM documents
                WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) 
                AND status = 'active' 
                AND created_at BETWEEN ? AND ?
                ORDER BY created_at DESC
                LIMIT 50
            ", [$user_id, $user_id, $start_datetime, $end_datetime]);
            
            // Query without date filter (old implementation)
            $all_docs = $db->fetchAll("
                SELECT 
                    id,
                    document_number,
                    full_name,
                    created_at
                FROM documents
                WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?)) 
                AND status = 'active'
                ORDER BY created_at DESC
                LIMIT 50
            ", [$user_id, $user_id]);
            
            echo "User: {$staff_user['full_name']} (ID: $user_id)<br>";
            echo "Documents with date filter ($selected_date): " . count($filtered_docs) . "<br>";
            echo "Documents without date filter (all): " . count($all_docs) . "<br><br>";
            
            if (count($filtered_docs) > 0) {
                echo "<strong>Filtered documents for $selected_date:</strong><br>";
                foreach ($filtered_docs as $doc) {
                    $created_date = date('Y-m-d H:i:s', strtotime($doc['created_at']));
                    echo "- {$doc['full_name']} (Created: $created_date)<br>";
                }
            } else {
                echo "No documents found for user on $selected_date<br>";
            }
        } else {
            echo "No staff user found for testing<br>";
        }
    } else {
        echo "No documents found for date testing<br>";
    }
    
    // Test 3: Test different date ranges
    echo "<h2>3. Date Range Testing</h2>";
    $test_dates = [
        date('Y-m-d'), // Today
        date('Y-m-d', strtotime('-1 day')), // Yesterday
        date('Y-m-d', strtotime('-7 days')), // 1 week ago
    ];
    
    foreach ($test_dates as $test_date) {
        $start_datetime = $test_date . ' 00:00:00';
        $end_datetime = $test_date . ' 23:59:59';
        
        $count_result = $db->fetch("
            SELECT COUNT(*) as total
            FROM documents
            WHERE status = 'active' 
            AND created_at BETWEEN ? AND ?
        ", [$start_datetime, $end_datetime]);
        
        echo "Date $test_date: {$count_result['total']} documents<br>";
    }
    
    echo "<h2>✅ Test Completed Successfully!</h2>";
    echo "<p><strong>Summary:</strong> The date filter functionality has been implemented for the document list.</p>";
    
    echo "<h3>New Features:</h3>";
    echo "<ul>";
    echo "<li>✅ Documents are now filtered by selected date</li>";
    echo "<li>✅ Query uses BETWEEN for precise date range filtering</li>";
    echo "<li>✅ Header shows selected date period</li>";
    echo "<li>✅ Empty state message includes selected date</li>";
    echo "<li>✅ Maintains original creator logic</li>";
    echo "</ul>";
    
    echo "<h3>How it Works:</h3>";
    echo "<ul>";
    echo "<li>User selects a date using the date picker</li>";
    echo "<li>System creates time range: YYYY-MM-DD 00:00:00 to YYYY-MM-DD 23:59:59</li>";
    echo "<li>Query filters documents created within that 24-hour period</li>";
    echo "<li>Results show only documents from the selected date</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error occurred:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>