<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role admin
require_login();
if (!is_admin()) {
    header('Location: ../index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $username = sanitize_input($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $full_name = sanitize_input($_POST['full_name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $role = sanitize_input($_POST['role'] ?? 'staff');
            $status = sanitize_input($_POST['status'] ?? 'active');
            
            if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
                $error_message = 'Semua field harus diisi';
            } elseif (!validate_email($email)) {
                $error_message = 'Format email tidak valid';
            } elseif (strlen($password) < 4) {
                $error_message = 'Password minimal 4 karakter';
            } else {
                try {
                    // Check if username already exists (case sensitive)
                    $existing_user = $db->fetch("SELECT id FROM users WHERE BINARY username = ? AND status = 'active'", [$username]);
                    if ($existing_user) {
                        $error_message = 'Username sudah digunakan. Silakan gunakan username lain.';
                    } else {
                        // Hash password dengan benar
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Pastikan status adalah 'active' untuk user baru
                        if ($status !== 'active' && $status !== 'inactive') {
                            $status = 'active';
                        }
                        
                        // Insert user baru
                        $db->execute("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)", 
                            [$username, $hashed_password, $full_name, $email, $role, $status]);
                        
                        $success_message = "User '$username' berhasil ditambahkan dengan role '$role'";
                        log_activity($_SESSION['user_id'], 'USER_CREATE', "Menambahkan user: $username (role: $role)");
                    }
                } catch (Exception $e) {
                    $error_message = 'Error: ' . $e->getMessage();
                }
            }
        }
        
        if ($action === 'delete') {
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id == $_SESSION['user_id']) {
                $error_message = 'Anda tidak dapat menghapus akun Anda sendiri';
            } else {
                try {
                    $db->execute("DELETE FROM users WHERE id = ?", [$user_id]);
                    $success_message = 'User berhasil dihapus';
                    log_activity($_SESSION['user_id'], 'USER_DELETE', "Menghapus user ID: $user_id");
                } catch (Exception $e) {
                    $error_message = 'Error: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get all users
try {
    $users = $db->fetchAll("SELECT id, username, full_name, email, role, status, created_at FROM users ORDER BY created_at DESC");
} catch (Exception $e) {
    $error_message = 'Error mengambil data user: ' . $e->getMessage();
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users me-2"></i>
                        Manajemen User
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
                    <!-- Form Tambah User -->
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Tambah User
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username *</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Nama Lengkap *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="staff">Staff</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>Simpan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Daftar User -->
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Daftar User (<?php echo count($users); ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Nama Lengkap</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><strong><?php echo e($user['username']); ?></strong></td>
                                                    <td><?php echo e($user['full_name']); ?></td>
                                                    <td><?php echo e($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                            <?php echo e(ucfirst($user['role'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo e(ucfirst($user['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <small class="text-muted">Anda</small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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










