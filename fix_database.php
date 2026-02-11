<?php
session_start();

$error_message = '';
$success_message = '';

// Check if database config exists
if (!file_exists('config/database.php')) {
    $error_message = 'Database belum dikonfigurasi. Silakan setup database terlebih dahulu.';
} else {
    try {
        require_once 'config/database.php';
        
        // Check if admin user exists
        $admin_check = $db->fetch("SELECT id FROM users WHERE username = 'admin'");
        $staff_check = $db->fetch("SELECT id FROM users WHERE username = 'staff'");
        
        $users_created = [];
        
        if (!$admin_check) {
            // Create admin user if not exists
            $admin_password = password_hash('password', PASSWORD_DEFAULT);
            $db->execute("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)", [
                'admin',
                $admin_password,
                'Administrator Sistem',
                'admin@imigrasi.go.id',
                'admin',
                'active'
            ]);
            $users_created[] = 'admin';
        }
        
        if (!$staff_check) {
            // Create staff user if not exists
            $staff_password = password_hash('password', PASSWORD_DEFAULT);
            $db->execute("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)", [
                'staff',
                $staff_password,
                'Staff Imigrasi',
                'staff@imigrasi.go.id',
                'staff',
                'active'
            ]);
            $users_created[] = 'staff';
        }
        
        if (!empty($users_created)) {
            $success_message = 'User berhasil dibuat: ' . implode(', ', $users_created) . '. Login dengan username/password: password';
        } else {
            $success_message = 'Database sudah dikonfigurasi dengan benar. Login dengan: admin/password atau staff/password';
        }
        
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="text-center text-white">
                    <i class="fas fa-tools fa-5x mb-4"></i>
                    <h2 class="mb-3">Fix Database</h2>
                    <h4 class="mb-4">Sistem Arsip Dokumen</h4>
                    <p class="lead">Perbaiki dan periksa konfigurasi database</p>
                </div>
            </div>
            
            <div class="col-lg-6 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="login-form-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-wrench fa-3x text-primary mb-3"></i>
                        <h3>Perbaiki Database</h3>
                        <p class="text-muted">Memperbaiki konfigurasi database yang sudah ada</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                            <div class="mt-2">
                                <small>Anda akan diarahkan ke halaman login dalam 3 detik...</small>
                            </div>
                        </div>
                        <script>
                            setTimeout(function() {
                                window.location.href = 'index.php';
                            }, 3000);
                        </script>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Login
                        </a>
                        <a href="setup.php" class="btn btn-outline-secondary">
                            <i class="fas fa-cog"></i> Setup Ulang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
