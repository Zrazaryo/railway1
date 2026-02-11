<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error_message = '';
$success_message = '';

// Check if staff user exists
try {
    $staff_check = $db->fetch("SELECT id FROM users WHERE username = 'staff'");
    
    if (!$staff_check) {
        // Create staff user
        $staff_password = password_hash('password', PASSWORD_DEFAULT);
        $db->execute("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)", [
            'staff',
            $staff_password,
            'Staff Imigrasi',
            'staff@imigrasi.go.id',
            'staff',
            'active'
        ]);
        
        $success_message = 'User staff berhasil dibuat! Username: staff, Password: password';
    } else {
        $success_message = 'User staff sudah ada. Username: staff, Password: password';
    }
    
} catch (Exception $e) {
    $error_message = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Staff User - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        .btn-custom {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>
                            <i class="fas fa-user-plus me-2"></i>
                            Create Staff User
                        </h3>
                        <p class="mb-0">Buat akun Staff untuk Sistem Arsip Dokumen</p>
                    </div>
                    <div class="card-body p-5">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Error!</strong> <?php echo e($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Berhasil!</strong> <?php echo e($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            
                            <div class="card bg-light mt-3">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Informasi Login Staff</h5>
                                    <hr>
                                    <p class="mb-2"><strong>Username:</strong> <code>staff</code></p>
                                    <p class="mb-2"><strong>Password:</strong> <code>password</code></p>
                                    <p class="mb-0"><strong>Role:</strong> <span class="badge bg-success">Staff</span></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="auth/login_staff.php" class="btn btn-success btn-lg btn-custom">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login sebagai Staff
                            </a>
                            <a href="check_users.php" class="btn btn-info btn-lg btn-custom">
                                <i class="fas fa-users me-2"></i>
                                Cek Daftar User
                            </a>
                            <a href="index.php" class="btn btn-secondary btn-lg btn-custom">
                                <i class="fas fa-home me-2"></i>
                                Kembali ke Home
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-lightbulb me-2"></i>Catatan Penting:</h6>
                            <ul class="mb-0">
                                <li>User staff akan dibuat secara otomatis jika belum ada</li>
                                <li>Gunakan kredensial di atas untuk login ke sistem</li>
                                <li>Disarankan untuk mengubah password setelah login pertama kali</li>
                                <li>Staff memiliki akses terbatas untuk mengelola dokumen</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>







