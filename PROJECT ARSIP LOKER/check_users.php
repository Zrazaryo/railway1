<?php
// Script untuk cek user yang ada di database
require_once 'config/database.php';

echo "<h2>Daftar User di Database:</h2>";

try {
    $users = $db->fetchAll("SELECT id, username, full_name, role, status FROM users");
    
    if (empty($users)) {
        echo "<p style='color: red;'>Tidak ada user di database!</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['full_name'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . $user['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='fix_database.php'>Fix Database</a> | <a href='index.php'>Kembali</a>";
?>






























