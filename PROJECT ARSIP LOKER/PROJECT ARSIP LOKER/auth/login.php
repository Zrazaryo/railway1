<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Username dan password harus diisi';
        header('Location: ../index.php');
        exit();
    }
    
    try {
        // Cari user berdasarkan username - case sensitive match
        $sql = "SELECT id, username, password, full_name, email, role, status, profile_picture 
                FROM users 
                WHERE BINARY username = ? AND status = 'active'";
        
        $user = $db->fetch($sql, [$username]);
        
        // Double check username match (case-sensitive)
        if ($user && $user['username'] !== $username) {
            $user = null;
        }
        
        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                $_SESSION['profile_picture'] = $user['profile_picture'];
            }
            
            // Set cookie jika remember me dicentang
            if ($remember) {
                $cookie_value = base64_encode($user['id'] . ':' . hash('sha256', $user['password']));
                setcookie('remember_token', $cookie_value, time() + (86400 * 30), '/'); // 30 hari
            }
            
            // Log aktivitas login
            log_activity($user['id'], 'LOGIN', 'User berhasil login');
            
            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                header('Location: ../dashboard.php');
            } else {
                header('Location: ../staff/dashboard.php');
            }
            exit();
            
        } else {
            $_SESSION['error'] = 'Username atau password salah';
            header('Location: ../index.php');
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        header('Location: ../index.php');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
