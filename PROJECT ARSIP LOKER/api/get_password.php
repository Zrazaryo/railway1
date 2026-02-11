<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Cek login dan role admin
if (!is_logged_in() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit();
}

$user_id = intval($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID user tidak valid']);
    exit();
}

try {
    // Ambil data user
    $user = $db->fetch("SELECT id, username, password FROM users WHERE id = ?", [$user_id]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit();
    }
    
    // Cek apakah kolom password_plain ada, jika belum tambahkan
    try {
        $check_column = $db->fetch("SHOW COLUMNS FROM users LIKE 'password_plain'");
        if (!$check_column) {
            // Tambahkan kolom password_plain
            $db->execute("ALTER TABLE users ADD COLUMN password_plain TEXT NULL AFTER password");
        }
    } catch (Exception $e) {
        // Error saat menambahkan kolom, lanjutkan
    }
    
    // Cek apakah ada password_plain di database
    try {
        $user_with_plain = $db->fetch("SELECT password_plain FROM users WHERE id = ?", [$user_id]);
        if ($user_with_plain && !empty($user_with_plain['password_plain'])) {
            // Decrypt password_plain (base64 decode)
            $password_plain = base64_decode($user_with_plain['password_plain']);
            if ($password_plain !== false) {
                echo json_encode(['success' => true, 'password' => $password_plain]);
                exit();
            }
        }
    } catch (Exception $e) {
        // Kolom password_plain belum ada atau error
    }
    
    // Jika tidak ada password_plain, coba verifikasi dengan password default
    // Password default biasanya "password" untuk user yang dibuat oleh sistem
    $default_passwords = ['password', 'admin', '123456'];
    $found_password = null;
    
    foreach ($default_passwords as $default_pass) {
        if (password_verify($default_pass, $user['password'])) {
            $found_password = $default_pass;
            // Simpan password_plain untuk penggunaan selanjutnya
            try {
                $password_plain_encrypted = base64_encode($default_pass);
                $db->execute("UPDATE users SET password_plain = ? WHERE id = ?", [$password_plain_encrypted, $user_id]);
            } catch (Exception $e) {
                // Error saat update, lanjutkan
            }
            break;
        }
    }
    
    if ($found_password) {
        echo json_encode(['success' => true, 'password' => $found_password]);
    } else {
        // Jika tidak ada password_plain dan tidak bisa ditebak, beri tahu user
        echo json_encode(['success' => false, 'message' => 'Password asli tidak tersedia. Silakan edit user dan set password baru untuk menyimpan password asli.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

