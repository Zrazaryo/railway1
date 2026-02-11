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

// Determine current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="sidebar-header mb-3 px-3">
            <h6 class="text-muted text-uppercase fw-bold">
                <i class="fas fa-user-shield me-2"></i>Superadmin Panel
            </h6>
        </div>
        
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>" 
                   href="../superadmin/dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <!-- Divider -->
            <li class="nav-divider">
                <hr class="my-2">
                <small class="text-muted px-3">MONITORING DOKUMEN</small>
            </li>
            
            <!-- Menu Dokumen Keseluruhan -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'documents.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>" 
                   href="../superadmin/documents.php">
                    <i class="fas fa-file-alt me-2"></i>
                    Dokumen Keseluruhan
                </a>
            </li>
            
            <!-- Menu Lemari Dokumen -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'lockers.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>" 
                   href="../superadmin/lockers.php">
                    <i class="fas fa-archive me-2"></i>
                    Lemari Dokumen
                </a>
            </li>
            
            <!-- Menu Lemari Pemusnahan -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'destruction.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>" 
                   href="../superadmin/destruction.php">
                    <i class="fas fa-trash-alt me-2"></i>
                    Lemari Pemusnahan
                </a>
            </li>
            
            <!-- Menu Sampah Dokumen -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'trash.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>" 
                   href="../superadmin/trash.php">
                    <i class="fas fa-trash me-2"></i>
                    Sampah Dokumen
                </a>
            </li>
            
            <!-- Divider -->
            <li class="nav-divider">
                <hr class="my-2">
                <small class="text-muted px-3">MANAJEMEN SISTEM</small>
            </li>
            
            <!-- Menu Manajemen User -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'users.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>" 
                   href="../superadmin/users.php">
                    <i class="fas fa-users me-2"></i>
                    Manajemen User
                </a>
            </li>
            
            <!-- Menu Laporan -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'reports.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>" 
                   href="../superadmin/reports.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Laporan
                </a>
            </li>
            
            <!-- Menu Log Aktivitas -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'logs.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>" 
                   href="../superadmin/logs.php">
                    <i class="fas fa-history me-2"></i>
                    Log Aktivitas
                </a>
            </li>
            
            <!-- Divider -->
            <li class="nav-divider">
                <hr class="my-2">
                <small class="text-muted px-3">AKSES CEPAT</small>
            </li>
            
            <!-- Quick Access to Admin Dashboard -->
            <li class="nav-item">
                <a class="nav-link text-primary" href="../dashboard.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>
                    Dashboard Admin
                </a>
            </li>
            
            <!-- Quick Access to Staff Dashboard -->
            <li class="nav-item">
                <a class="nav-link text-success" href="../staff/dashboard.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>
                    Dashboard Staff
                </a>
            </li>
        </ul>
        
        <!-- Footer -->
        <div class="sidebar-footer mt-4 px-3">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                Mode Supervisi Aktif
            </small>
        </div>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 56px; /* Height of navbar */
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    overflow-y: auto;
}

.sidebar .nav-link {
    color: #333;
    padding: 0.75rem 1rem;
    border-radius: 0;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    background-color: #e9ecef;
    color: #dc3545;
}

.sidebar .nav-link.active {
    background-color: #dc3545;
    color: white;
}

.sidebar .nav-link.active:hover {
    background-color: #c82333;
}

.sidebar .nav-link i {
    width: 20px;
    text-align: center;
}

.nav-divider {
    margin: 0.5rem 0;
}

.nav-divider small {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.sidebar-header h6 {
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

.sidebar-footer {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}

.sidebar-footer small {
    font-size: 0.7rem;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .sidebar {
        position: relative;
        top: 0;
    }
}

/* Main content adjustment */
main {
    margin-left: 0;
}

@media (min-width: 768px) {
    main {
        margin-left: 16.66667%; /* Width of sidebar */
    }
}

@media (min-width: 992px) {
    main {
        margin-left: 16.66667%; /* Width of sidebar */
    }
}
</style>