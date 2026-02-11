<?php 
$isStaff = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff';

// Get profile picture from session or database
$profile_picture = $_SESSION['profile_picture'] ?? null;
if (!$profile_picture && isset($_SESSION['user_id'])) {
    try {
        if (!isset($db)) {
            require_once __DIR__ . '/../config/database.php';
        }
        $user_profile = $db->fetch("SELECT profile_picture FROM users WHERE id = ?", [$_SESSION['user_id']]);
        if ($user_profile && !empty($user_profile['profile_picture'])) {
            // Check if file exists (try both relative and absolute path)
            $file_path = $user_profile['profile_picture'];
            $file_exists = file_exists($file_path) || file_exists(__DIR__ . '/../' . $file_path);
            if ($file_exists) {
                $profile_picture = $user_profile['profile_picture'];
                $_SESSION['profile_picture'] = $profile_picture;
            }
        }
    } catch (Exception $e) {
        // Ignore error
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark <?php echo $isStaff ? 'bg-success' : 'bg-primary'; ?> fixed-top">
    <div class="container-fluid">
        <button class="btn btn-link text-white me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <i class="fas fa-bars"></i>
        </button>
        
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $isStaff ? '/PROJECT ARSIP LOKER/staff/dashboard.php' : '/PROJECT ARSIP LOKER/dashboard.php'; ?>">
            <img src="/PROJECT ARSIP LOKER/assets/images/jakpus-logo.png" alt="JAKPUS Logo" style="height: 32px; width: auto; margin-right: 10px;">
            Sistem Tata Arsip Imigrasi
        </a>
        
        <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                    <?php if ($profile_picture): 
                        // Ensure path is absolute from root
                        $profile_img_path = (strpos($profile_picture, '/') === 0 || strpos($profile_picture, 'http') === 0) 
                            ? $profile_picture 
                            : '/PROJECT ARSIP LOKER/' . ltrim($profile_picture, '/');
                    ?>
                        <img src="<?php echo e($profile_img_path); ?>" alt="Profile" 
                             class="rounded-circle me-2" 
                             style="width: 32px; height: 32px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <i class="fas fa-user-circle me-1" style="display: none;"></i>
                    <?php else: ?>
                        <i class="fas fa-user-circle me-1"></i>
                    <?php endif; ?>
                    <?php echo e($_SESSION['full_name']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="/PROJECT ARSIP LOKER/profile.php">
                            <i class="fas fa-user me-2"></i>Profil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="/PROJECT ARSIP LOKER/settings.php">
                            <i class="fas fa-cog me-2"></i>Pengaturan
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="/PROJECT ARSIP LOKER/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Keluar
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Add top padding to body for fixed navbar -->
<style>
body {
    padding-top: 56px;
}
</style>
