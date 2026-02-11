<?php
require_once __DIR__ . '/bootstrap_vercel.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Set header untuk JSON response hanya jika belum ada output
if (!headers_sent()) {
    header('Content-Type: application/json');
}

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

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        // Cek apakah sudah ada superadmin
        $existing_superadmin = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'superadmin' AND status = 'active'");
        if ($existing_superadmin['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Sudah ada Super Administrator. Sistem hanya mengizinkan maksimal 1 account Super Administrator.']);
            exit();
        }
        
        // Validasi input
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $role = 'superadmin';
        
        // Validasi required fields
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username harus diisi']);
            exit();
        }
        
        if (empty($full_name)) {
            echo json_encode(['success' => false, 'message' => 'Nama lengkap harus diisi']);
            exit();
        }
        
        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Password harus diisi']);
            exit();
        }
        
        // Cek apakah username sudah ada (hanya user yang aktif) - case sensitive
        $existing_user = $db->fetch("SELECT id FROM users WHERE BINARY username = ? AND status = 'active'", [$username]);
        if ($existing_user) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
            exit();
        }
        
        // Jika ada user dengan username yang sama tapi status inactive, hapus dulu (hard delete)
        $inactive_user = $db->fetch("SELECT id FROM users WHERE username = ? AND status = 'inactive'", [$username]);
        if ($inactive_user) {
            try {
                $inactive_user_id = $inactive_user['id'];
                // Update foreign key references
                $db->execute("UPDATE documents SET updated_by = NULL WHERE updated_by = ?", [$inactive_user_id]);
                $db->execute("UPDATE documents SET created_by = ? WHERE created_by = ?", 
                    [$_SESSION['user_id'], $inactive_user_id]);
                $db->execute("DELETE FROM activity_logs WHERE user_id = ?", [$inactive_user_id]);
                // Hapus user yang inactive
                $db->execute("DELETE FROM users WHERE id = ?", [$inactive_user_id]);
            } catch (Exception $e) {
                // Jika gagal, lanjutkan saja - akan error di insert nanti jika masih ada
            }
        }
        
        // Generate email dari username jika tidak ada
        $email = $username . '@imigrasi.go.id';
        
        // Cek apakah email sudah ada, jika ya tambahkan angka
        $email_check = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($email_check) {
            $counter = 1;
            do {
                $email = $username . $counter . '@imigrasi.go.id';
                $email_check = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
                $counter++;
            } while ($email_check && $counter < 100);
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Simpan password asli dengan enkripsi sederhana (base64)
        $password_plain_encrypted = base64_encode($password);
        
        // Cek dan tambahkan kolom password_plain jika belum ada
        try {
            $check_column = $db->fetch("SHOW COLUMNS FROM users LIKE 'password_plain'");
            if (!$check_column) {
                $db->execute("ALTER TABLE users ADD COLUMN password_plain TEXT NULL AFTER password");
            }
        } catch (Exception $e) {
            // Error saat menambahkan kolom, lanjutkan
        }
        
        // Insert user baru
        try {
            $db->execute(
                "INSERT INTO users (username, password, password_plain, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$username, $hashed_password, $password_plain_encrypted, $full_name, $email, $role, 'active']
            );
        } catch (Exception $e) {
            // Jika kolom password_plain belum ada, insert tanpa kolom tersebut
            $db->execute(
                "INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)",
                [$username, $hashed_password, $full_name, $email, $role, 'active']
            );
        }
        
        // Log aktivitas
        log_activity($_SESSION['user_id'], 'SUPERADMIN_CREATE', "Menambahkan Super Administrator: $username");
        
        echo json_encode(['success' => true, 'message' => 'Super Administrator berhasil ditambahkan']);
        
    } elseif ($action === 'update') {
        // Validasi input
        $user_id = intval($_POST['id'] ?? 0);
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $role = 'superadmin';
        
        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID user tidak valid']);
            exit();
        }
        
        // Validasi required fields
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username harus diisi']);
            exit();
        }
        
        if (empty($full_name)) {
            echo json_encode(['success' => false, 'message' => 'Nama lengkap harus diisi']);
            exit();
        }
        
        // Cek apakah user ada dan merupakan superadmin
        $existing_user = $db->fetch("SELECT id, username, role FROM users WHERE id = ?", [$user_id]);
        if (!$existing_user) {
            echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
            exit();
        }
        
        if ($existing_user['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'User bukan Super Administrator']);
            exit();
        }
        
        // Cek apakah username sudah digunakan oleh user lain (hanya user yang aktif) - case sensitive
        $username_check = $db->fetch("SELECT id FROM users WHERE BINARY username = ? AND id != ? AND status = 'active'", [$username, $user_id]);
        if ($username_check) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
            exit();
        }
        
        // Update user
        if (!empty($password)) {
            // Update dengan password baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $password_plain_encrypted = base64_encode($password);
            
            // Cek dan tambahkan kolom password_plain jika belum ada
            try {
                $check_column = $db->fetch("SHOW COLUMNS FROM users LIKE 'password_plain'");
                if (!$check_column) {
                    $db->execute("ALTER TABLE users ADD COLUMN password_plain TEXT NULL AFTER password");
                }
            } catch (Exception $e) {
                // Error saat menambahkan kolom, lanjutkan
            }
            
            // Update dengan password baru
            try {
                $db->execute(
                    "UPDATE users SET username = ?, password = ?, password_plain = ?, full_name = ?, role = ? WHERE id = ?",
                    [$username, $hashed_password, $password_plain_encrypted, $full_name, $role, $user_id]
                );
            } catch (Exception $e) {
                // Jika kolom password_plain belum ada, update tanpa kolom tersebut
                $db->execute(
                    "UPDATE users SET username = ?, password = ?, full_name = ?, role = ? WHERE id = ?",
                    [$username, $hashed_password, $full_name, $role, $user_id]
                );
            }
        } else {
            // Update tanpa mengubah password
            $db->execute(
                "UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?",
                [$username, $full_name, $role, $user_id]
            );
        }
        
        // Log aktivitas
        log_activity($_SESSION['user_id'], 'SUPERADMIN_UPDATE', "Mengupdate Super Administrator: $username (ID: $user_id)");
        
        echo json_encode(['success' => true, 'message' => 'Super Administrator berhasil diupdate']);
        
    } elseif ($action === 'delete') {
        $user_id = intval($_POST['id'] ?? 0);
        
        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID user tidak valid']);
            exit();
        }
        
        // Cek apakah user ada dan merupakan superadmin
        $existing_user = $db->fetch("SELECT id, username, full_name, role FROM users WHERE id = ?", [$user_id]);
        if (!$existing_user) {
            echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
            exit();
        }
        
        if ($existing_user['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'User bukan Super Administrator']);
            exit();
        }
        
        // Jangan izinkan menghapus diri sendiri
        if ($user_id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri']);
            exit();
        }
        
        // Hard delete (benar-benar hapus dari database agar username bisa digunakan lagi)
        // Update foreign key references terlebih dahulu untuk menghindari constraint violation
        try {
            // Set updated_by menjadi NULL untuk dokumen yang diupdate oleh user ini
            $db->execute("UPDATE documents SET updated_by = NULL WHERE updated_by = ?", [$user_id]);
            
            // Set created_by menjadi user yang sedang login untuk dokumen yang dibuat oleh user ini
            // Ini diperlukan karena foreign key constraint ON DELETE RESTRICT
            $db->execute("UPDATE documents SET created_by = ? WHERE created_by = ?", 
                [$_SESSION['user_id'], $user_id]);
            
            // Hapus activity logs yang terkait dengan user ini
            $db->execute("DELETE FROM activity_logs WHERE user_id = ?", [$user_id]);
            
            // Sekarang hapus user dari database
            $db->execute("DELETE FROM users WHERE id = ?", [$user_id]);
            
            // Log aktivitas
            log_activity($_SESSION['user_id'], 'SUPERADMIN_DELETE', "Menghapus Super Administrator: {$existing_user['username']} (ID: $user_id)");
            
            echo json_encode(['success' => true, 'message' => 'Super Administrator berhasil dihapus']);
        } catch (Exception $e) {
            // Jika ada error karena foreign key constraint, coba soft delete sebagai fallback
            $error_msg = $e->getMessage();
            if (strpos($error_msg, 'foreign key') !== false || strpos($error_msg, '1451') !== false || strpos($error_msg, '23000') !== false) {
                // Soft delete sebagai fallback jika masih ada constraint
                $db->execute("UPDATE users SET status = 'inactive' WHERE id = ?", [$user_id]);
                log_activity($_SESSION['user_id'], 'SUPERADMIN_DELETE', "Menghapus Super Administrator (soft): {$existing_user['username']} (ID: $user_id)");
                echo json_encode(['success' => true, 'message' => 'Super Administrator berhasil dihapus (nonaktifkan)']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $error_msg]);
            }
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}