<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role superadmin
if (!is_logged_in() || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: ../auth/login_superadmin.php');
    exit();
}

$error_message = '';
$users = [];

// Filter parameters
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = sanitize_input($_GET['search'] ?? '');

try {
    // Build query conditions
    $where_conditions = ["1=1"];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($role_filter) && in_array($role_filter, ['admin', 'staff', 'superadmin'])) {
        $where_conditions[] = "u.role = ?";
        $params[] = $role_filter;
    }

    if (!empty($status_filter) && in_array($status_filter, ['active', 'inactive'])) {
        $where_conditions[] = "u.status = ?";
        $params[] = $status_filter;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get users with activity stats
    $sql_users = "
        SELECT 
            u.id, u.username, u.full_name, u.email, u.role, u.status, 
            u.created_at, u.last_login,
            COUNT(DISTINCT d.id) as total_documents,
            COUNT(DISTINCT CASE WHEN d.status = 'deleted' THEN d.id END) as deleted_documents,
            COUNT(DISTINCT la.id) as total_activities,
            MAX(la.created_at) as last_activity
        FROM users u
        LEFT JOIN documents d ON u.id = d.created_by
        LEFT JOIN logs_activity la ON u.id = la.user_id
        WHERE $where_clause
        GROUP BY u.id, u.username, u.full_name, u.email, u.role, u.status, u.created_at, u.last_login
        ORDER BY u.role ASC, u.full_name ASC
    ";
    $users = $db->fetchAll($sql_users, $params);

    // Get overall statistics
    $total_users = count($users);
    $total_admins = count(array_filter($users, function($u) { return $u['role'] === 'admin'; }));
    $total_staff = count(array_filter($users, function($u) { return $u['role'] === 'staff'; }));
    $total_superadmins = count(array_filter($users, function($u) { return $u['role'] === 'superadmin'; }));
    $active_users = count(array_filter($users, function($u) { return $u['status'] === 'active'; }));

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar_superadmin.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar_superadmin.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users me-2"></i>Manajemen User
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total User</h6>
                                        <h4><?php echo number_format($total_users); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Admin</h6>
                                        <h4><?php echo number_format($total_admins); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-tie fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Staff</h6>
                                        <h4><?php echo number_format($total_staff); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">User Aktif</h6>
                                        <h4><?php echo number_format($active_users); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" id="searchForm" class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" 
                                           value="<?php echo e($search); ?>" placeholder="Cari user...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="role">
                                    <option value="">Semua Role</option>
                                    <option value="superadmin" <?php echo $role_filter === 'superadmin' ? 'selected' : ''; ?>>Superadmin</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar User
                            <span class="badge bg-secondary ms-2"><?php echo number_format($total_users); ?> user</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Total Dokumen</th>
                                        <th>Dokumen Pemusnahan</th>
                                        <th>Aktivitas Terakhir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Tidak ada user ditemukan.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td class="fw-semibold"><?php echo e($user['username']); ?></td>
                                                <td><?php echo e($user['full_name']); ?></td>
                                                <td><?php echo e($user['email']); ?></td>
                                                <td>
                                                    <?php if ($user['role'] === 'superadmin'): ?>
                                                        <span class="badge bg-danger">Superadmin</span>
                                                    <?php elseif ($user['role'] === 'admin'): ?>
                                                        <span class="badge bg-primary">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Staff</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['status'] === 'active'): ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo number_format($user['total_documents']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo number_format($user['deleted_documents']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($user['last_activity']): ?>
                                                        <?php echo date('d/m/Y H:i', strtotime($user['last_activity'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewUserStats(<?php echo $user['id']; ?>)" 
                                                                title="Lihat Statistik">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                                onclick="viewUserActivity(<?php echo $user['id']; ?>)" 
                                                                title="Log Aktivitas">
                                                            <i class="fas fa-history"></i>
                                                        </button>
                                                    </div>
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

    <!-- User Stats Modal -->
    <div class="modal fade" id="userStatsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Statistik User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userStatsContent">
                    <!-- Stats will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- User Activity Modal -->
    <div class="modal fade" id="userActivityModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log Aktivitas User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userActivityContent">
                    <!-- Activity will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        function viewUserStats(userId) {
            document.getElementById('userStatsContent').innerHTML = `
                <div class="text-center">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Statistik detail user akan ditampilkan di sini.</p>
                    <p><small>Fitur ini dapat dikembangkan untuk menampilkan grafik produktivitas, performa, dll.</small></p>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('userStatsModal')).show();
        }

        function viewUserActivity(userId) {
            document.getElementById('userActivityContent').innerHTML = `
                <div class="text-center">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Log aktivitas user akan ditampilkan di sini.</p>
                    <p><small>Fitur ini dapat dikembangkan untuk menampilkan riwayat login, aktivitas dokumen, dll.</small></p>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('userActivityModal')).show();
        }
    </script>
</body>
</html>