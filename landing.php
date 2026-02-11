<?php
// Load functions first untuk init_multi_session
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
}

// Inisialisasi session dengan dukungan multi-tab
init_multi_session();

// Check if user wants to force login (logout first)
$force_login = isset($_GET['force_login']) && $_GET['force_login'] == '1';
if ($force_login && isset($_SESSION['user_id'])) {
    // Logout current user
    session_destroy();
    init_multi_session();
    header('Location: landing.php' . (get_tab_id() > 0 ? '?tab=' . get_tab_id() : ''));
    exit();
}

// Redirect jika sudah login (kecuali jika force_login)
if (isset($_SESSION['user_id']) && !$force_login) {
    $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
    if (isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'superadmin') {
            header('Location: superadmin/dashboard.php' . $tab_param);
        } elseif ($_SESSION['user_role'] === 'admin') {
            header('Location: dashboard.php' . $tab_param);
        } else {
            header('Location: staff/dashboard.php' . $tab_param);
        }
    }
    exit();
}

// Check if database is configured
$db_configured = false;
if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        $db_configured = true;
    } catch (Exception $e) {
        $db_configured = false;
    }
}

// Get current tab ID untuk digunakan di template
$current_tab = get_tab_id();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Arsip Dokumen - Kantor Imigrasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .landing-body {
            background: linear-gradient(135deg, #8B5CF6 0%, #A78BFA 50%, #C4B5FD 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            padding: 3rem;
            max-width: 450px;
            width: 100%;
            margin: 0 20px;
            text-align: center;
            position: relative;
        }
        .lock-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lock-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .lock-icon i {
            font-size: 2.5rem;
            color: white;
        }
        .system-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        .system-subtitle {
            font-size: 1.1rem;
            color: #6B7280;
            margin-bottom: 2.5rem;
            font-weight: 500;
        }
        .role-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .role-button {
            display: flex;
            align-items: center;
            padding: 1.2rem 1.5rem;
            border-radius: 16px;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .role-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            color: white;
        }
        .role-button.admin {
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
        }
        .role-button.staff {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
        }
        .role-button.superadmin {
            background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);
        }
        .role-button i {
            font-size: 1.5rem;
            margin-right: 1rem;
            width: 30px;
            text-align: center;
        }
        .role-button.admin i {
            color: #FCD34D;
        }
        .role-button.staff i {
            color: #A78BFA;
        }
        .role-button.superadmin i {
            color: #FEF3C7;
        }
        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }
        .status-badge .badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        .setup-warning {
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
            border: 1px solid #F59E0B;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .setup-warning i {
            color: #D97706;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .setup-warning h6 {
            color: #92400E;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .setup-warning p {
            color: #A16207;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .setup-btn {
            background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .setup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
            color: white;
            text-decoration: none;
        }
        @media (max-width: 768px) {
            .main-card {
                margin: 0 10px;
                padding: 2rem;
            }
            .system-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="landing-body">
    <div class="main-card">
        <!-- Status Badge -->
        <?php if ($db_configured): ?>
            <div class="status-badge">
                <span class="badge bg-success">
                    <i class="fas fa-check-circle"></i> Database Ready
                </span>
            </div>
        <?php else: ?>
            <div class="status-badge">
                <span class="badge bg-warning">
                    <i class="fas fa-exclamation-triangle"></i> Setup Required
                </span>
            </div>
        <?php endif; ?>

        <!-- JAKPUS Logo -->
        <div class="lock-icon">
            <img src="assets/images/jakpus-logo.png" alt="JAKPUS Logo">
        </div>

        <!-- System Title -->
                <div class="system-title" style="margin-bottom:0;">
                    <span style="display:block;font-size:2.5rem;font-weight:900;color:#1F2937;letter-spacing:1px;">SITARI</span>
                    <span style="display:block;font-size:1.15rem;font-weight:500;color:#374151;margin-top:0.2rem;">Sistem Tata Arsip Imigrasi Jakarta Pusat</span>
                </div>

        <!-- Subtitle -->
        <div class="system-subtitle">
            Pilih role untuk melanjutkan
        </div>

        <?php if (!$db_configured): ?>
            <!-- Setup Warning -->
            <div class="setup-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <h6>Database Belum Dikonfigurasi</h6>
                <p>Silakan setup database terlebih dahulu sebelum dapat login.</p>
                <a href="setup.php" class="setup-btn">
                    <i class="fas fa-cog"></i> Setup Database
                </a>
            </div>
        <?php else: ?>
            <!-- Role Buttons -->
            <div class="role-buttons">
                <a href="<?php echo add_tab_param('auth/login_superadmin.php'); ?>" class="role-button superadmin">
                    <i class="fas fa-user-shield"></i>
                    Login sebagai Superadmin
                </a>
                
                <a href="<?php echo add_tab_param('auth/login_admin.php'); ?>" class="role-button admin">
                    <i class="fas fa-user-tie"></i>
                    Login sebagai Admin
                </a>
                
                <a href="<?php echo add_tab_param('auth/login_staff.php'); ?>" class="role-button staff">
                    <i class="fas fa-user"></i>
                    Login sebagai Staff
                </a>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center mt-4 pt-3" style="border-top: 1px solid #E5E7EB;">
            <?php if ($current_tab > 0): ?>
                <div style="margin-bottom: 0.5rem;">
                    <span style="background: #6366F1; color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; display: inline-block;">
                        <i class="fas fa-tag"></i> Tab <?php echo $current_tab; ?>
                    </span>
                </div>
            <?php endif; ?>
            <small style="color: #9CA3AF;">
                 © 2026 SITARI (Sistem Tata Arsip Imigrasi Jakarta Pusat). Semua hak dilindungi.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
