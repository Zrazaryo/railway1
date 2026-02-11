<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Script untuk setup role superadmin
try {
    echo "<h2>Setup Superadmin Role</h2>";
    
    // 1. Update enum role di tabel users
    echo "<p>1. Updating users table to add superadmin role...</p>";
    $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'superadmin') NOT NULL DEFAULT 'staff'");
    echo "<p style='color: green;'>✓ Users table updated successfully</p>";
    
    // 2. Cek apakah user superadmin sudah ada
    $existing_superadmin = $db->fetch("SELECT id FROM users WHERE username = 'superadmin'");
    
    if ($existing_superadmin) {
        echo "<p>2. Superadmin user already exists, updating role...</p>";
        $db->query("UPDATE users SET role = 'superadmin', status = 'active' WHERE username = 'superadmin'");
        echo "<p style='color: green;'>✓ Existing superadmin user updated</p>";
    } else {
        echo "<p>2. Creating default superadmin user...</p>";
        // Password: superadmin123
        $hashed_password = password_hash('superadmin123', PASSWORD_DEFAULT);
        
        $db->query("
            INSERT INTO users (username, password, full_name, email, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            'superadmin',
            $hashed_password,
            'Super Administrator',
            'superadmin@example.com',
            'superadmin',
            'active'
        ]);
        echo "<p style='color: green;'>✓ Default superadmin user created</p>";
    }
    
    echo "<h3 style='color: green;'>Setup completed successfully!</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Login Credentials:</h4>";
    echo "<p><strong>Username:</strong> superadmin</p>";
    echo "<p><strong>Password:</strong> superadmin123</p>";
    echo "<p><strong>URL:</strong> <a href='auth/login_superadmin.php'>auth/login_superadmin.php</a></p>";
    echo "<p style='color: red;'><strong>IMPORTANT:</strong> Please change the password after first login!</p>";
    echo "</div>";
    
    echo "<p><a href='landing.php' class='btn btn-primary'>Go to Landing Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure your database is properly configured and accessible.</p>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
        }
        .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    </style>
</head>
<body>
</body>
</html>