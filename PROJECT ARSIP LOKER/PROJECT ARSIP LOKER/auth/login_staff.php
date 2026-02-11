<?php
// Load functions first untuk init_multi_session
if (file_exists('../includes/functions.php')) {
    require_once '../includes/functions.php';
}

// Inisialisasi session dengan dukungan multi-tab
init_multi_session();

$error_message = '';
$success_message = '';

// Check if user wants to logout and login as different user
$force_logout = isset($_GET['logout']) && $_GET['logout'] == '1';
if ($force_logout && isset($_SESSION['user_id'])) {
    // Logout current user
    if (file_exists('../config/database.php')) {
        require_once '../config/database.php';
        if (is_logged_in()) {
            log_activity($_SESSION['user_id'], 'LOGOUT', 'User logout untuk login sebagai user lain');
        }
    }
    session_destroy();
    init_multi_session();
    $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
    header('Location: login_staff.php' . $tab_param);
    exit();
}

// Tidak redirect otomatis - biarkan user memilih untuk logout atau tetap login
// Redirect hanya jika user mengakses dari link internal (bukan akses langsung)

// Check if database is configured
$db_configured = false;
if (file_exists('../config/database.php')) {
    try {
        require_once '../config/database.php';
        require_once '../includes/functions.php';
        $db_configured = true;
    } catch (Exception $e) {
        $db_configured = false;
    }
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_configured) {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password harus diisi';
    } else {
        try {
            // Find user with staff role - case sensitive username match
            $sql = "SELECT id, username, password, full_name, email, role, status, profile_picture 
                    FROM users 
                    WHERE BINARY username = ? AND role = 'staff' AND status = 'active'";
            
            $user = $db->fetch($sql, [$username]);
            
            // Double check username match (case-sensitive)
            if ($user && $user['username'] !== $username) {
                $user = null;
            }
            
            if ($user) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Jika user sudah login dengan akun berbeda, logout dulu
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user['id']) {
                        log_activity($_SESSION['user_id'], 'LOGOUT', 'User logout untuk login sebagai user lain');
                        // Hapus remember cookie jika ada
                        if (isset($_COOKIE['remember_token'])) {
                            setcookie('remember_token', '', time() - 3600, '/');
                        }
                    }
                    
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                        $_SESSION['profile_picture'] = $user['profile_picture'];
                    }
                    
                    // Set cookie if remember me checked
                    if ($remember) {
                        $cookie_value = base64_encode($user['id'] . ':' . hash('sha256', $user['password']));
                        setcookie('remember_token', $cookie_value, time() + (86400 * 30), '/');
                    }
                    
                    // Log activity
                    log_activity($user['id'], 'LOGIN_STAFF', 'Staff berhasil login');
                    
                    // Redirect dengan mempertahankan parameter tab
                    $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
                    header('Location: ../staff/dashboard.php' . $tab_param);
                    exit();
                } else {
                    $error_message = 'Password salah';
                }
            } else {
                // Check if user exists but wrong role or status
                $check_user = $db->fetch("SELECT id, username, role, status FROM users WHERE username = ?", [$username]);
                if ($check_user) {
                    if ($check_user['role'] !== 'staff') {
                        $error_message = 'Username ini bukan staff. Silakan login sebagai admin.';
                    } elseif ($check_user['status'] !== 'active') {
                        $error_message = 'Akun Anda tidak aktif. Silakan hubungi administrator.';
                    } else {
                        $error_message = 'Username atau password salah';
                    }
                } else {
                    $error_message = 'Username tidak ditemukan';
                }
            }
            
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Staff - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Side Image -->
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <div class="text-center text-white">
                    <i class="fas fa-user fa-5x mb-4"></i>
                    <h2 class="mb-3">Staff Dashboard</h2>
                    <h4 class="mb-4">Sistem Arsip Dokumen</h4>
                    <p class="lead">Akses terbatas sesuai peran staff</p>
                </div>
            </div>
            
            <!-- Login Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <div class="login-form-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-user fa-3x text-success mb-3"></i>
                        <h3>Login sebagai Staff</h3>
                        <p class="text-muted">Masukkan kredensial staff Anda</p>
                    </div>
                    
                    <?php if (!$db_configured): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Database belum dikonfigurasi. Silakan setup database terlebih dahulu.
                        </div>
                        <div class="text-center">
                            <a href="../setup.php" class="btn btn-primary">
                                <i class="fas fa-cog"></i> Setup Database
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo e($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="login-form">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo e($_POST['username'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Ingat saya
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Staff
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Anda sudah login sebagai <strong><?php echo e($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong> (<?php echo e(ucfirst($_SESSION['user_role'] ?? 'user')); ?>).
                            <br>
                            <a href="login_staff.php?logout=1<?php echo get_tab_id() > 0 ? '&tab=' . get_tab_id() : ''; ?>" class="alert-link mt-2 d-inline-block">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout dan Login sebagai Staff Lain
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo add_tab_param('../landing.php'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            © 2026 SITARI (Sistem Tata Arsip Imigrasi Jakarta Pusat). Semua hak dilindungi.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
