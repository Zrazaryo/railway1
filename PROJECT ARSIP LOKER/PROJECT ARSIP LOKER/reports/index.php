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

// Get data staff/admin dengan total dokumen hari ini
try {
    // Ambil semua user (admin dan staff) yang aktif
    $users_with_docs = $db->fetchAll("
        SELECT 
            u.id,
            u.full_name,
            u.username,
            u.role,
            COUNT(CASE WHEN DATE(d.created_at) = CURDATE() THEN 1 END) as total_dokumen_hari_ini,
            COUNT(CASE WHEN d.status = 'active' THEN 1 END) as total_dokumen_keseluruhan
        FROM users u
        LEFT JOIN documents d ON u.id = d.created_by AND d.status = 'active'
        WHERE u.status = 'active'
        GROUP BY u.id, u.full_name, u.username, u.role
        ORDER BY u.full_name ASC
    ");
    
} catch (Exception $e) {
    $error_message = 'Error mengambil data laporan: ' . $e->getMessage();
    $users_with_docs = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Arsip Dokumen</title>
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
                        <i class="fas fa-chart-bar me-2"></i>
                        Laporan
                    </h1>
                </div>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tabel Laporan Staff/Admin -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>
                            Laporan Dokumen per Staff/Admin
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="25%">Nama Staff/Admin</th>
                                        <th width="12%">Role</th>
                                        <th width="18%">Total Dokumen Hari Ini</th>
                                        <th width="18%">Total Dokumen Keseluruhan</th>
                                        <th width="22%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users_with_docs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
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
                                                    <?php if ($user['role'] === 'admin'): ?>
                                                        <span class="badge bg-danger">Admin</span>
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
                                                    <a href="detail.php?user_id=<?php echo $user['id']; ?>" 
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
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
















