<?php
// Pastikan session sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login dan memiliki role superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: ../auth/login_superadmin.php');
    exit();
}

$current_user = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Superadmin';
$profile_picture = $_SESSION['profile_picture'] ?? null;
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-danger fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="../superadmin/dashboard.php">
            <i class="fas fa-user-shield me-2"></i>
            <span class="fw-bold">Sistem Arsip Dokumen</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../superadmin/dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <?php if ($profile_picture && file_exists($profile_picture)): ?>
                            <img src="../<?php echo e($profile_picture); ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                        <?php else: ?>
                            <i class="fas fa-user-shield me-2"></i>
                        <?php endif; ?>
                        <span><?php echo e($current_user); ?></span>
                        <span class="badge bg-light text-danger ms-2">Superadmin</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <h6 class="dropdown-header">
                                <i class="fas fa-user-shield me-1"></i>
                                <?php echo e($current_user); ?>
                            </h6>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../profile.php">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../settings.php">
                                <i class="fas fa-cog me-2"></i>Pengaturan
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar-brand {
    font-size: 1.1rem;
}

.navbar-nav .nav-link {
    font-weight: 500;
    transition: all 0.3s ease;
}

.navbar-nav .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.7rem;
}

body {
    padding-top: 56px; /* Adjust for fixed navbar */
}
</style>