<?php
// Load functions first untuk init_multi_session
require_once '../includes/functions.php';

// Inisialisasi session dengan dukungan multi-tab
init_multi_session();

require_once '../config/database.php';

// Cek login dan role superadmin
require_superadmin();

// Ambil data statistik dashboard
try {
    // Total dokumen
    $total_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'active'")['count'];
    
    // Total user
    $total_users = $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'];
    
    // Total superadmin
    $total_superadmins = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'superadmin' AND status = 'active'")['count'];
    
    // Total admin
    $total_admins = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'")['count'];
    
    // Total staff
    $total_staff = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'active'")['count'];
    
    // Total dokumen pemusnahan (lemari pemusnahan)
    $total_destruction_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'deleted'")['count'];
    
    // Total dokumen hari ini
    $today_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'active' AND DATE(created_at) = DATE(NOW())")['count'];
    
    // Total keseluruhan dokumen (aktif + pemusnahan)
    $total_all_documents = $total_documents + $total_destruction_documents;
    
    // Data laporan dokumen per staff/admin
    $users_with_docs = $db->fetchAll("
        SELECT 
            u.id,
            u.full_name,
            u.username,
            u.role,
            COUNT(CASE WHEN DATE(d.created_at) = CURDATE() AND d.status = 'active' AND (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id)) THEN 1 END) as total_dokumen_hari_ini,
            COUNT(CASE WHEN d.status = 'active' AND (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id)) THEN 1 END) as total_dokumen_keseluruhan,
            COUNT(CASE WHEN d.status = 'deleted' AND (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id)) THEN 1 END) as total_dokumen_pemusnahan
        FROM users u
        LEFT JOIN documents d ON (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id))
        WHERE u.status = 'active'
        GROUP BY u.id, u.full_name, u.username, u.role
        HAVING (total_dokumen_keseluruhan > 0 OR total_dokumen_pemusnahan > 0 OR u.role IN ('superadmin', 'admin', 'staff'))
        ORDER BY u.full_name ASC
    ");
    
} catch (Exception $e) {
    $error_message = "Terjadi kesalahan saat mengambil data dashboard";
    $users_with_docs = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Superadmin - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { padding-top:56px; }
        .menu-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-radius: 12px;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
        }
        .menu-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-shield text-danger me-2"></i>
                        Dashboard Superadmin
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <!-- Baris 1: Dokumen Aktif, Superadmin, Admin, Staff -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Dokumen Aktif
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_documents); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Total Superadmin
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_superadmins); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Admin
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_admins); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Staff
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_staff); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Baris 2: Total User, Dokumen Pemusnahan, Dokumen Hari Ini, Keseluruhan Dokumen -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total User
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_users); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Total Dokumen Pemusnahan
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_destruction_documents); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-trash fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Dokumen Hari Ini
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($today_documents); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-secondary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                            Total Keseluruhan Dokumen
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_all_documents); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-database fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Laporan Dokumen per Staff/Admin -->
                <div class="card shadow-sm mb-4">
                    <div style="background-color: #0d6efd !important; color: #ffffff !important; padding: 1rem 1.25rem; border-radius: 15px 15px 0 0; border-bottom: 1px solid #0d6efd !important;">
                        <h5 class="mb-0" style="color: #ffffff !important; font-weight: 600 !important; margin: 0 !important;">
                            <i class="fas fa-table me-2" style="color: #ffffff !important;"></i>
                            <span style="color: #ffffff !important;">Laporan Dokumen per Staff/Admin</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="20%">Nama Staff/Admin</th>
                                        <th width="10%">Role</th>
                                        <th width="15%">Total Dokumen Hari Ini</th>
                                        <th width="15%">Total Dokumen Keseluruhan</th>
                                        <th width="15%">Total Dokumen Pemusnahan</th>
                                        <th width="20%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users_with_docs)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Tidak ada data
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; ?>
                                        <?php foreach ($users_with_docs as $user): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td>
                                                    <strong><?php echo e($user['full_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">@<?php echo e($user['username']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($user['role'] === 'superadmin'): ?>
                                                        <span class="badge bg-danger" style="background-color: #DC2626 !important;">Superadmin</span>
                                                    <?php elseif ($user['role'] === 'admin'): ?>
                                                        <span class="badge bg-primary">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">Staff</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success fs-6">
                                                        <?php echo number_format($user['total_dokumen_hari_ini']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary fs-6">
                                                        <?php echo number_format($user['total_dokumen_keseluruhan']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger fs-6">
                                                        <?php echo number_format($user['total_dokumen_pemusnahan']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="../reports/detail.php?user_id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>
                                                        Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Menu Cards -->
                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <a href="../documents/" class="text-decoration-none">
                            <div class="card shadow menu-card h-100">
                                <div class="card-body text-center">
                                    <div class="menu-icon text-primary">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <h5 class="card-title">Dokumen Keseluruhan</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <a href="../lockers/list.php" class="text-decoration-none">
                            <div class="card shadow menu-card h-100">
                                <div class="card-body text-center">
                                    <div class="menu-icon text-info">
                                        <i class="fas fa-archive"></i>
                                    </div>
                                    <h5 class="card-title">Lemari Dokumen</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <a href="../documents/pemusnahan.php" class="text-decoration-none">
                            <div class="card shadow menu-card h-100">
                                <div class="card-body text-center">
                                    <div class="menu-icon text-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </div>
                                    <h5 class="card-title">Lemari Pemusnahan</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <a href="../reports/" class="text-decoration-none">
                            <div class="card shadow menu-card h-100">
                                <div class="card-body text-center">
                                    <div class="menu-icon text-success">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <h5 class="card-title">Laporan</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <a href="../logs/" class="text-decoration-none">
                            <div class="card shadow menu-card h-100">
                                <div class="card-body text-center">
                                    <div class="menu-icon text-warning">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <h5 class="card-title">Log Aktivitas</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    
    <!-- Footer -->
    <footer class="text-center py-3 mt-4" style="border-top: 1px solid #E5E7EB; background-color: #f8f9fa;">
        <small style="color: #9CA3AF;">
            © 2026 SITARI (Sistem Tata Arsip Imigrasi Jakarta Pusat). Semua hak dilindungi.
        </small>
    </footer>
</body>
</html>

