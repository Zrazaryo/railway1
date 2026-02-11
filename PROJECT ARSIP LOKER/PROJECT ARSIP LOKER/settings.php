<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login
require_login();

$error_message = '';
$success_message = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'Semua field password harus diisi';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Password baru tidak cocok';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password baru minimal 6 karakter';
    } else {
        try {
            // Verify current password
            $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$_SESSION['user_id']]);
            
            if (!password_verify($current_password, $user['password'])) {
                $error_message = 'Password lama tidak benar';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashed_password, $_SESSION['user_id']]);
                $success_message = 'Password berhasil diubah';
                log_activity($_SESSION['user_id'], 'PASSWORD_CHANGE', 'Password berhasil diubah');
            }
        } catch (Exception $e) {
            $error_message = 'Error: ' . $e->getMessage();
        }
    }
}

// Get current user info
try {
    $user_info = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
} catch (Exception $e) {
    $error_message = 'Error mengambil data user: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-cog me-2"></i>
                        Pengaturan
                    </h1>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo e($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Profil -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    Informasi Profil
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="40%">Username</th>
                                        <td><?php echo e($user_info['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <td><?php echo e($user_info['full_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo e($user_info['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Role</th>
                                        <td>
                                            <span class="badge <?php echo $user_info['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                <?php echo e(ucfirst($user_info['role'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge <?php echo $user_info['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo e(ucfirst($user_info['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Bergabung</th>
                                        <td><?php echo format_date_indonesia($user_info['created_at']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ubah Password -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>
                                    Ubah Password
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Password Lama</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-danger w-100">
                                        <i class="fas fa-save me-2"></i>Ubah Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
























