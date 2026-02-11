<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: staff/dashboard.php');
    }
    exit();
}

// Check if database is configured
$db_configured = false;
if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        require_once 'includes/functions.php';
        $db_configured = true;
    } catch (Exception $e) {
        $db_configured = false;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Arsip Dokumen - Kantor Imigrasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #8B5CF6 0%, #A78BFA 50%, #C4B5FD 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <div style="background: white; border-radius: 24px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15); padding: 3rem; max-width: 450px; width: 100%; margin: 0 20px; text-align: center; position: relative;">
        
        <!-- Status Badge -->
        <?php if ($db_configured): ?>
            <div style="position: absolute; top: 20px; right: 20px;">
                <span style="background: #10B981; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8rem;">
                    <i class="fas fa-check-circle"></i> Database Ready
                </span>
            </div>
        <?php else: ?>
            <div style="position: absolute; top: 20px; right: 20px;">
                <span style="background: #F59E0B; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8rem;">
                    <i class="fas fa-exclamation-triangle"></i> Setup Required
                </span>
            </div>
        <?php endif; ?>

        <!-- Lock Icon -->
        <div style="width: 80px; height: 80px; margin: 0 auto 2rem; background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);">
            <i class="fas fa-lock" style="font-size: 2.5rem; color: white;"></i>
        </div>

        <!-- System Title -->
        <div style="font-size: 1.8rem; font-weight: 700; color: #1F2937; margin-bottom: 0.5rem; line-height: 1.2;">
            Sistem Perasipan Loker<br>
            Dokumen
        </div>

        <!-- Subtitle -->
        <div style="font-size: 1.1rem; color: #6B7280; margin-bottom: 2.5rem; font-weight: 500;">
            Pilih role untuk melanjutkan
        </div>

        <?php if (!$db_configured): ?>
            <!-- Setup Warning -->
            <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border: 1px solid #F59E0B; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; text-align: center;">
                <i class="fas fa-exclamation-triangle" style="color: #D97706; font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                <h6 style="color: #92400E; font-weight: 600; margin-bottom: 0.5rem;">Database Belum Dikonfigurasi</h6>
                <p style="color: #A16207; margin-bottom: 1rem; font-size: 0.9rem;">Silakan setup database terlebih dahulu sebelum dapat login.</p>
                <a href="setup.php" style="background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); border: none; color: white; padding: 0.8rem 2rem; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-block;">
                    <i class="fas fa-cog"></i> Setup Database
                </a>
            </div>
        <?php else: ?>
            <!-- Role Buttons -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="auth/login_admin.php" style="display: flex; align-items: center; padding: 1.2rem 1.5rem; border-radius: 16px; background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%); color: white; font-weight: 600; font-size: 1rem; text-decoration: none; transition: all 0.3s ease;">
                    <i class="fas fa-user-tie" style="font-size: 1.5rem; margin-right: 1rem; width: 30px; text-align: center; color: #FCD34D;"></i>
                    Login sebagai Admin
                </a>
                
                <a href="auth/login_staff.php" style="display: flex; align-items: center; padding: 1.2rem 1.5rem; border-radius: 16px; background: linear-gradient(135deg, #059669 0%, #10B981 100%); color: white; font-weight: 600; font-size: 1rem; text-decoration: none; transition: all 0.3s ease;">
                    <i class="fas fa-user" style="font-size: 1.5rem; margin-right: 1rem; width: 30px; text-align: center; color: #A78BFA;"></i>
                    Login sebagai Staff
                </a>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
            <small style="color: #9CA3AF;">
                © 2024 Sistem Arsip Dokumen Kantor Imigrasi. Semua hak dilindungi.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



















