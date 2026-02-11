<?php
// Load functions first untuk init_multi_session
require_once 'includes/functions.php';

// Inisialisasi session dengan dukungan multi-tab
init_multi_session();

require_once 'config/database.php';

// Cek login dan role admin
require_admin();

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
    
    // Dokumen terbaru (10 terakhir)
    // Gunakan join yang sudah dikelompokkan per kode lemari
    // supaya tidak terjadi duplikasi dokumen ketika ada
    // lebih dari satu lemari dengan code yang sama.
    $recent_documents = $db->fetchAll("
        SELECT d.id,
               d.document_number,
               d.full_name,
               d.nik,
               d.passport_number,
               d.month_number,
               d.document_year,
               d.citizen_category,
               d.document_origin, 
               d.created_at,
               d.document_order_number,
               u.full_name AS created_by_name,
               l.code AS locker_code,
               l.name AS locker_name
        FROM documents d 
        LEFT JOIN users u ON d.created_by = u.id 
        LEFT JOIN lockers l ON d.month_number = l.name
        WHERE d.status = 'active' 
        ORDER BY d.created_at DESC 
        LIMIT 10
    ");
    
    // Aktivitas terbaru - dinonaktifkan
    // $recent_activities = $db->fetchAll("
    //     SELECT al.*, u.full_name 
    //     FROM activity_logs al 
    //     LEFT JOIN users u ON al.user_id = u.id 
    //     ORDER BY al.created_at DESC 
    //     LIMIT 10
    // ");
    
} catch (Exception $e) {
    $error_message = "Terjadi kesalahan saat mengambil data dashboard";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body { padding-top:56px; }
        .display-6 { font-size:2.5rem; }
        
        /* Fix alignment untuk dashboard */
        .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
        }
        
        main {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }
        
        /* Memastikan row dan col sejajar */
        .row {
            margin-left: 0;
            margin-right: 0;
        }
        
        .row > * {
            padding-left: 15px;
            padding-right: 15px;
        }
        
        /* Custom styles for Recent Documents table on Dashboard Admin */
        .recent-documents-table {
            width: 100%;
        }
        .recent-documents-table th:nth-child(1) { width: 5%; } /* No */
        .recent-documents-table th:nth-child(2) { width: 20%; } /* Nama Lengkap */
        .recent-documents-table th:nth-child(3) { width: 15%; } /* NIK */
        .recent-documents-table th:nth-child(4) { width: 15%; } /* No Passport */
        .recent-documents-table th:nth-child(5) { width: 8%; } /* Kode Lemari */
        .recent-documents-table th:nth-child(6) { width: 10%; } /* Nama Lemari */
        .recent-documents-table th:nth-child(7) { width: 8%; } /* Urutan Dokumen */
        .recent-documents-table th:nth-child(8) { width: 10%; } /* Kode Dokumen */
        .recent-documents-table th:nth-child(9) { width: 12%; } /* Dokumen Berasal */
        .recent-documents-table th:nth-child(10) { width: 7%; } /* Kategori */
        .recent-documents-table th:nth-child(11) { width: 8%; } /* Di Buat Oleh */
        .recent-documents-table td {
            font-size: 0.875rem;
            word-wrap: break-word;
            vertical-align: middle;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .recent-documents-table th:nth-child(1) { width: 8%; }
            .recent-documents-table th:nth-child(2) { width: 35%; }
            .recent-documents-table th:nth-child(3) { width: 28%; }
            .recent-documents-table th:nth-child(4) { width: 29%; }
            
            .recent-documents-table td {
                font-size: 0.8rem;
                padding: 0.5rem 0.25rem;
            }
            
            .recent-documents-table th {
                font-size: 0.75rem;
                padding: 0.5rem 0.25rem;
                white-space: nowrap;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* Extra small devices */
        @media (max-width: 576px) {
            .recent-documents-table th:nth-child(1) { width: 10%; }
            .recent-documents-table th:nth-child(2) { width: 40%; }
            .recent-documents-table th:nth-child(3) { width: 25%; }
            .recent-documents-table th:nth-child(4) { width: 25%; }
            
            .recent-documents-table td {
                font-size: 0.75rem;
                padding: 0.4rem 0.2rem;
            }
            
            .recent-documents-table th {
                font-size: 0.7rem;
                padding: 0.4rem 0.2rem;
            }
        }
        
        /* Memastikan card memiliki margin yang konsisten */
        .card {
            margin-bottom: 1.5rem;
        }
        
        /* Memastikan kolom admin dan staff sejajar */
        .col-lg-6 {
            padding-left: 15px;
            padding-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Admin</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Cetak
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Security Alerts -->
                <?php // include 'includes/security_alerts.php'; // File removed ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
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
                        <div class="card border-left-secondary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
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
                        <div class="card border-left-dark shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
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
                
                <!-- Device Login Statistics -->
                <?php // display_device_login_stats(null, 7); // Function not available ?>
                
                <!-- User Management Row -->
                <div class="row mb-4">
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-danger">Daftar Superadmin</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="superadminSearch" placeholder="Cari superadmin...">
                                    </div>
                                    <button class="btn btn-sm btn-danger" onclick="addUser('superadmin')">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="superadminTable">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Username</th>
                                                <th>Password</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $superadmins = $db->fetchAll("SELECT id, username, full_name, password FROM users WHERE role = 'superadmin' AND status = 'active' ORDER BY full_name");
                                            $no = 1;
                                            foreach ($superadmins as $superadmin): ?>
                                                <tr data-role="superadmin">
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo e($superadmin['full_name']); ?></td>
                                                    <td><?php echo e($superadmin['username']); ?></td>
                                                    <td>
                                                        <span class="password-display" id="superadmin-password-<?php echo $superadmin['id']; ?>">••••••••</span>
                                                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="togglePassword('superadmin-password-<?php echo $superadmin['id']; ?>', <?php echo $superadmin['id']; ?>)" title="Tampilkan Password">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" onclick="editUser(this, <?php echo $superadmin['id']; ?>)" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(this, <?php echo $superadmin['id']; ?>)" title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Daftar Admin</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="adminSearch" placeholder="Cari admin...">
                                    </div>
                                    <button class="btn btn-sm btn-success" onclick="addUser('admin')">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="adminTable">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Username</th>
                                                <th>Password</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $admins = $db->fetchAll("SELECT id, username, full_name, password FROM users WHERE role = 'admin' AND status = 'active' ORDER BY full_name");
                                            $no = 1;
                                            foreach ($admins as $admin): ?>
                                                <tr data-role="admin">
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo e($admin['full_name']); ?></td>
                                                    <td><?php echo e($admin['username']); ?></td>
                                                    <td>
                                                        <span class="password-display" id="admin-password-<?php echo $admin['id']; ?>">••••••••</span>
                                                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="togglePassword('admin-password-<?php echo $admin['id']; ?>', <?php echo $admin['id']; ?>)" title="Tampilkan Password">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" onclick="editUser(this, <?php echo $admin['id']; ?>)" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(this, <?php echo $admin['id']; ?>)" title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-success">Daftar Staff</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="staffSearch" placeholder="Cari staff...">
                                    </div>
                                    <button class="btn btn-sm btn-success" onclick="addUser('staff')">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="staffTable">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Username</th>
                                                <th>Password</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $staffs = $db->fetchAll("SELECT id, username, full_name, password FROM users WHERE role = 'staff' AND status = 'active' ORDER BY full_name");
                                            $no = 1;
                                            foreach ($staffs as $staff): ?>
                                                <tr data-role="staff">
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo e($staff['full_name']); ?></td>
                                                    <td><?php echo e($staff['username']); ?></td>
                                                    <td>
                                                        <span class="password-display" id="staff-password-<?php echo $staff['id']; ?>">••••••••</span>
                                                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="togglePassword('staff-password-<?php echo $staff['id']; ?>', <?php echo $staff['id']; ?>)" title="Tampilkan Password">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" onclick="editUser(this, <?php echo $staff['id']; ?>)" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(this, <?php echo $staff['id']; ?>)" title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
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
                <!-- Add/Edit User Modal -->
                <div class="modal fade" id="userModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="userModalTitle">Tambah User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="userForm">
                                    <input type="hidden" name="id" id="userId">
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" name="role" id="userRole" required>
                                            <option value="superadmin">Superadmin</option>
                                            <option value="admin">Admin</option>
                                            <option value="staff">Staff</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" name="full_name" id="userFullName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" id="userUsername" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="text" class="form-control" name="password" id="userPassword" placeholder="Isi untuk set/reset password">
                                        <div class="form-text">Kosongkan saat edit jika tidak ingin mengubah password.</div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-primary" id="saveUserBtn">
                                    <i class="fas fa-save me-2"></i>Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteUserModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-trash me-2"></i> Konfirmasi Penghapusan
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p id="deleteUserMessage"></p>
                                <ul class="list-unstyled mb-3">
                                    <li><strong>Role:</strong> <span id="delRole">-</span></li>
                                    <li><strong>Nama:</strong> <span id="delFullName">-</span></li>
                                    <li><strong>Username:</strong> <span id="delUsername">-</span></li>
                                </ul>
                                <div class="mb-3">
                                    <label for="deleteUserAnswer" class="form-label">Jawaban Anda</label>
                                    <input type="number" class="form-control" id="deleteUserAnswer" placeholder="Masukkan hasil penjumlahan">
                                    <small class="form-text text-muted">Penghapusan hanya akan dilanjutkan jika jawaban benar.</small>
                                </div>
                                <input type="hidden" id="delUserId">
                                <input type="hidden" id="deleteUserCorrectAnswer">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                    <i class="fas fa-trash me-2"></i>Hapus User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Documents and Activities -->
                <div class="row">
                    <div class="col-lg-12"> <!-- Diubah dari col-lg-8 menjadi col-lg-12 -->
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Dokumen Terbaru</h6>
                                <a href="documents/" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye d-none d-sm-inline"></i> <span class="d-none d-sm-inline">Lihat Semua</span><span class="d-sm-none">Semua</span>
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover recent-documents-table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Lengkap</th>
                                                <th>NIK</th>
                                                <th>No Passport</th>
                                                <th class="d-none d-md-table-cell">Kode Lemari</th>
                                                <th class="d-none d-md-table-cell">Nama Lemari</th>
                                                <th class="d-none d-md-table-cell">Urutan Dokumen</th>
                                                <th class="d-none d-md-table-cell">Kode Dokumen</th>
                                                <th class="d-none d-lg-table-cell">Dokumen Berasal</th>
                                                <th class="d-none d-md-table-cell">Kategori</th>
                                                <th class="d-none d-lg-table-cell">Di Buat Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_documents)): ?>
                                                <tr>
                                                    <td colspan="11" class="text-center py-4">
                                                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted">Tidak ada dokumen ditemukan</p>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php $no = 1; foreach ($recent_documents as $doc): ?>
                                                    <tr>
                                                        <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                                        <td class="fw-semibold"><?php echo e($doc['full_name'] ?? '-'); ?></td>
                                                        <td><?php echo e($doc['nik'] ?? '-'); ?></td>
                                                        <td><?php echo e($doc['passport_number'] ?? '-'); ?></td>
                                                        <!-- Kode Lemari = ekstrak dari locker_code atau month_number -->
                                                        <?php
                                                        $kodeLemari = '-';
                                                        if (isset($doc['locker_code']) && !empty($doc['locker_code'])) {
                                                            // Gunakan locker_code jika ada
                                                            $kodeLemari = $doc['locker_code'];
                                                        } elseif (!empty($doc['month_number'])) {
                                                            // Fallback: ekstrak dari month_number (misal A3.01 -> A3, A.01 -> A)
                                                            if (preg_match('/^([A-Z])(\d)\./', $doc['month_number'], $matches)) {
                                                                $kodeLemari = $matches[1] . $matches[2]; // A3, B1
                                                            } elseif (preg_match('/^([A-Z])\./', $doc['month_number'], $matches)) {
                                                                $kodeLemari = $matches[1]; // A, B (format lama)
                                                            } else {
                                                                $kodeLemari = substr($doc['month_number'], 0, 1);
                                                            }
                                                        }
                                                        ?>
                                                        <td class="d-none d-md-table-cell"><?php echo e($kodeLemari); ?></td>
                                                        <!-- Nama Rak = month_number -->
                                                        <td class="d-none d-md-table-cell"><?php echo e($doc['month_number'] ?? '-'); ?></td>
                                                        <td class="d-none d-md-table-cell"><?php echo e($doc['document_order_number'] ?? '-'); ?></td>
                                                        <?php
                                                            $kodeDokumen = '-';
                                                            if (!empty($doc['month_number']) && $doc['document_order_number'] !== null) {
                                                                $kodeDokumen = $doc['month_number'] . '.' . $doc['document_order_number'];
                                                            }
                                                        ?>
                                                        <td class="d-none d-md-table-cell"><?php echo e($kodeDokumen); ?></td>
                                                        <td class="d-none d-lg-table-cell">
                                                        <?php
                                                        $originLabel = '-';
                                                        if (!empty($doc['document_origin'])) {
                                                            switch ($doc['document_origin']) {
                                                                case 'imigrasi_jakarta_pusat_kemayoran':
                                                                    $originLabel = 'Imigrasi Jakarta Pusat Kemayoran';
                                                                    break;
                                                                case 'imigrasi_ulp_semanggi':
                                                                    $originLabel = 'Imigrasi ULP Semanggi';
                                                                    break;
                                                                case 'imigrasi_lounge_senayan_city':
                                                                    $originLabel = 'Imigrasi Lounge Senayan City';
                                                                    break;
                                                                default:
                                                                    $originLabel = $doc['document_origin'];
                                                            }
                                                        }
                                                        echo e($originLabel);
                                                        ?>
                                                        </td>
                                                        <td class="d-none d-md-table-cell">
                                                            <span class="badge bg-primary"><?php echo e($doc['citizen_category'] ?? 'WNI'); ?></span>
                                                        </td>
                                                        <td class="d-none d-lg-table-cell"><?php echo e($doc['created_by_name'] ?? '-'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kolom Aktivitas Terbaru dinonaktifkan -->
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/local-ip-detector.js"></script>
    <script>
        // User Management Functions
        let userModal;
        function openUserModal(title) {
            if (!userModal) userModal = new bootstrap.Modal(document.getElementById('userModal'));
            document.getElementById('userModalTitle').textContent = title;
            userModal.show();
        }

        function resetUserForm() {
            document.getElementById('userId').value = '';
            document.getElementById('userRole').value = 'admin';
            document.getElementById('userFullName').value = '';
            document.getElementById('userUsername').value = '';
            document.getElementById('userPassword').value = '';
        }

        function addUser(role) {
            resetUserForm();
            document.getElementById('userRole').value = role;
            const roleNames = { 'superadmin': 'Superadmin', 'admin': 'Admin', 'staff': 'Staff' };
            openUserModal('Tambah User ' + (roleNames[role] || role));
            document.getElementById('saveUserBtn').onclick = saveCreateUser;
        }
        
        function setupLiveSearch(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            if (!input || !table) return;
            input.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    let found = false;
                    for (let j = 0; j < cells.length - 1; j++) {
                        if (cells[j].textContent.toLowerCase().includes(term)) {
                            found = true;
                            break;
                        }
                    }
                    rows[i].style.display = found ? '' : 'none';
                }
            });
        }
        
        function editUser(btn, userId) {
            // Ambil data dari baris tabel terdekat
            const row = btn.closest('tr');
            const cells = row.querySelectorAll('td');
            const fullName = cells[1].textContent.trim();
            const username = cells[2].textContent.trim();
            const tableId = row.closest('table').id;
            let role = 'staff';
            if (tableId === 'superadminTable') {
                role = 'superadmin';
            } else if (tableId === 'adminTable') {
                role = 'admin';
            }
            
            // Try to get role from data attribute if available
            const rowRole = row.getAttribute('data-role') || role;

            resetUserForm();
            document.getElementById('userId').value = userId;
            document.getElementById('userRole').value = rowRole;
            document.getElementById('userFullName').value = fullName;
            document.getElementById('userUsername').value = username;
            const roleNames = { 'superadmin': 'Superadmin', 'admin': 'Admin', 'staff': 'Staff' };
            openUserModal('Edit User ' + (roleNames[rowRole] || rowRole));
            document.getElementById('saveUserBtn').onclick = saveUpdateUser;
        }
        
        let deleteUserModal;
        function deleteUser(btn, userId) {
            const row = btn.closest('tr');
            const cells = row.querySelectorAll('td');
            const fullName = cells[1].textContent.trim();
            const username = cells[2].textContent.trim();
            const tableId = row.closest('table').id;
            let role = 'Staff';
            if (tableId === 'superadminTable') {
                role = 'Superadmin';
            } else if (tableId === 'adminTable') {
                role = 'Admin';
            }

            // Generate random math question
            const num1 = Math.floor(Math.random() * 10) + 1;
            const num2 = Math.floor(Math.random() * 10) + 1;
            const correctAnswer = num1 + num2;

            if (!deleteUserModal) deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            document.getElementById('delUserId').value = userId;
            document.getElementById('delRole').textContent = role;
            document.getElementById('delFullName').textContent = fullName;
            document.getElementById('delUsername').textContent = username;
            document.getElementById('deleteUserCorrectAnswer').value = correctAnswer;
            document.getElementById('deleteUserMessage').innerHTML = 
                `Anda akan menghapus user berikut.<br>` +
                `Untuk konfirmasi, jawab pertanyaan berikut:<br>` +
                `<span class="fw-bold">${num1} + ${num2} = ?</span>`;
            document.getElementById('deleteUserAnswer').value = '';
            deleteUserModal.show();
        }

        document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function() {
            const answer = parseInt(document.getElementById('deleteUserAnswer').value, 10);
            const correctAnswer = parseInt(document.getElementById('deleteUserCorrectAnswer').value, 10);
            
            if (isNaN(answer) || answer !== correctAnswer) {
                alert('Jawaban salah! Penghapusan dibatalkan.');
                return;
            }
            
            const userId = document.getElementById('delUserId').value;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', userId);
            const res = await fetch('api/user_manage.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                deleteUserModal.hide();
                location.reload();
            } else {
                alert(data.message || 'Gagal menghapus');
            }
        });

        async function saveCreateUser() {
            const form = document.getElementById('userForm');
            
            // Validasi form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Validasi password untuk create
            const password = document.getElementById('userPassword').value.trim();
            if (!password) {
                alert('Password harus diisi untuk user baru');
                return;
            }
            
            try {
                const fd = new FormData(form);
                fd.append('action', 'create');
                const res = await fetch('api/user_manage.php', { method: 'POST', body: fd });
                
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const data = await res.json();
                if (data.success) {
                    alert('User berhasil ditambahkan');
                    if (userModal) userModal.hide();
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menambah user');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
            }
        }

        async function saveUpdateUser() {
            const form = document.getElementById('userForm');
            
            // Validasi form (kecuali password)
            const requiredFields = ['full_name', 'username', 'role'];
            let isValid = true;
            
            for (const fieldName of requiredFields) {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field && !field.value.trim()) {
                    field.reportValidity();
                    isValid = false;
                    break;
                }
            }
            
            if (!isValid) {
                return;
            }
            
            try {
                const fd = new FormData(form);
                fd.append('action', 'update');
                const res = await fetch('api/user_manage.php', { method: 'POST', body: fd });
                
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const data = await res.json();
                if (data.success) {
                    alert('User berhasil diupdate');
                    if (userModal) userModal.hide();
                    location.reload();
                } else {
                    alert(data.message || 'Gagal mengubah user');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
            }
        }
        
        async function togglePassword(elementId, userId) {
            const element = document.getElementById(elementId);
            const button = element.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (element.textContent === '••••••••') {
                // Show password - ambil dari API
                try {
                    const formData = new FormData();
                    formData.append('user_id', userId);
                    
                    const res = await fetch('api/get_password.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.success && data.password) {
                        element.textContent = data.password;
                        icon.className = 'fas fa-eye-slash';
                        button.title = 'Sembunyikan Password';
                    } else {
                        alert('Password asli tidak tersedia. Password mungkin belum disimpan dengan benar.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengambil password');
                }
            } else {
                // Hide password
                element.textContent = '••••••••';
                icon.className = 'fas fa-eye';
                button.title = 'Tampilkan Password';
            }
        }
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            setupLiveSearch('superadminSearch', 'superadminTable');
            setupLiveSearch('adminSearch', 'adminTable');
            setupLiveSearch('staffSearch', 'staffTable');
        });
    </script>
    
    <!-- Footer -->
    <footer class="text-center py-3 mt-4" style="border-top: 1px solid #E5E7EB; background-color: #f8f9fa;">
        <small style="color: #9CA3AF;">
            © 2026 SITARI (Sistem Tata Arsip Imigrasi Jakarta Pusat). Semua hak dilindungi.
        </small>
    </footer>
</body>
</html>
