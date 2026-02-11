<?php 
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isSuperadmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin';
$roleName = $isSuperadmin ? 'Superadmin' : ($isAdmin ? 'Admin' : 'Staff');
?>
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">
            <i class="fas fa-shield-alt me-2"></i>
            Menu <?php echo $roleName; ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="nav flex-column">
            <?php if ($isSuperadmin): ?>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/superadmin/dashboard.php">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/documents/">
                        <i class="fas fa-file-alt me-3"></i>
                        Dokumen Keseluruhan
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/lockers/list.php">
                        <i class="fas fa-archive me-3"></i>
                        Lemari Dokumen
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/documents/pemusnahan.php">
                        <i class="fas fa-archive me-3"></i>
                        Lemari Pemusnahan
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/reports/">
                        <i class="fas fa-chart-bar me-3"></i>
                        Laporan
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/logs/">
                        <i class="fas fa-history me-3"></i>
                        Log Aktivitas
                    </a>
                </div>
            <?php elseif ($isAdmin): ?>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/dashboard.php">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/documents/">
                        <i class="fas fa-file-alt me-3"></i>
                        Dokumen Keseluruhan
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/lockers/list.php">
                        <i class="fas fa-archive me-3"></i>
                        Lemari Dokumen
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/documents/pemusnahan.php">
                        <i class="fas fa-archive me-3"></i>
                        Lemari Pemusnahan
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/lockers/select.php">
                        <i class="fas fa-plus me-3"></i>
                        Tambah Dokumen
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/users/">
                        <i class="fas fa-users me-3"></i>
                        Manajemen User
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/reports/">
                        <i class="fas fa-chart-bar me-3"></i>
                        Laporan
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/logs/">
                        <i class="fas fa-history me-3"></i>
                        Log Aktivitas
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/settings.php">
                        <i class="fas fa-cog me-3"></i>
                        Pengaturan
                    </a>
                </div>
            <?php else: ?>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/staff/dashboard.php">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/documents/">
                        <i class="fas fa-file-alt me-3"></i>
                        Dokumen Keseluruhan
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/lockers/list.php">
                        <i class="fas fa-archive me-3"></i>
                        Lemari Dokumen
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/documents/pemusnahan.php">
                        <i class="fas fa-archive me-3"></i>
                        Lemari Pemusnahan
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/lockers/select.php">
                        <i class="fas fa-plus me-3"></i>
                        Tambah Dokumen
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link text-white py-3 border-bottom" href="/PROJECT ARSIP LOKER/documents/?mine=1">
                        <i class="fas fa-folder-open me-3"></i>
                        Dokumen Saya
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="nav-item mt-auto">
                <a class="nav-link text-white py-3 border-top" href="/PROJECT ARSIP LOKER/auth/logout.php">
                    <i class="fas fa-sign-out-alt me-3"></i>
                    Keluar
                </a>
            </div>
        </nav>
        
        <!-- User Info -->
        <div class="p-3 border-top">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <?php 
                    $sidebar_profile_picture = $_SESSION['profile_picture'] ?? null;
                    if ($sidebar_profile_picture): 
                        // Ensure path is absolute from root
                        $sidebar_img_path = (strpos($sidebar_profile_picture, '/') === 0 || strpos($sidebar_profile_picture, 'http') === 0) 
                            ? $sidebar_profile_picture 
                            : '/PROJECT ARSIP LOKER/' . ltrim($sidebar_profile_picture, '/');
                    ?>
                        <img src="<?php echo e($sidebar_img_path); ?>" alt="Profile" 
                             class="rounded-circle" 
                             style="width: 48px; height: 48px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <i class="fas fa-user-circle fa-2x text-light" style="display: none;"></i>
                    <?php else: ?>
                        <i class="fas fa-user-circle fa-2x text-light"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="fw-bold"><?php echo e($_SESSION['full_name']); ?></div>
                    <small class="text-muted"><?php echo e(ucfirst($_SESSION['user_role'])); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
