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
$logs = [];

// Filter parameters
$user_filter = $_GET['user'] ?? '';
$action_filter = $_GET['action'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = sanitize_input($_GET['search'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // Build query conditions
    $where_conditions = ["1=1"];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(la.description LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($user_filter)) {
        $where_conditions[] = "la.user_id = ?";
        $params[] = $user_filter;
    }

    if (!empty($action_filter)) {
        $where_conditions[] = "la.action LIKE ?";
        $params[] = "%$action_filter%";
    }

    if (!empty($date_filter)) {
        $where_conditions[] = "DATE(la.created_at) = ?";
        $params[] = $date_filter;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get total count for pagination
    $total_sql = "
        SELECT COUNT(*) as total
        FROM logs_activity la
        LEFT JOIN users u ON la.user_id = u.id
        WHERE $where_clause
    ";
    $total_result = $db->fetch($total_sql, $params);
    $total_logs = $total_result['total'] ?? 0;
    $total_pages = ceil($total_logs / $limit);

    // Get logs with user info
    $sql_logs = "
        SELECT 
            la.id, la.user_id, la.action, la.description, la.ip_address, 
            la.user_agent, la.created_at,
            u.full_name, u.username, u.role
        FROM logs_activity la
        LEFT JOIN users u ON la.user_id = u.id
        WHERE $where_clause
        ORDER BY la.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $logs = $db->fetchAll($sql_logs, $params);

    // Get users for filter dropdown
    $users = $db->fetchAll("
        SELECT DISTINCT u.id, u.full_name, u.username, u.role
        FROM users u
        INNER JOIN logs_activity la ON u.id = la.user_id
        WHERE u.status = 'active'
        ORDER BY u.full_name ASC
    ");

    // Get action types for filter
    $actions = $db->fetchAll("
        SELECT DISTINCT action
        FROM logs_activity
        ORDER BY action ASC
    ");

    // Get activity statistics
    $today_activities = $db->fetch("SELECT COUNT(*) as count FROM logs_activity WHERE DATE(created_at) = CURDATE()")['count'] ?? 0;
    $week_activities = $db->fetch("SELECT COUNT(*) as count FROM logs_activity WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;
    $month_activities = $db->fetch("SELECT COUNT(*) as count FROM logs_activity WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'] ?? 0;

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Helper function for action badges
function getActionBadge($action) {
    if (strpos($action, 'LOGIN') !== false) return 'success';
    if (strpos($action, 'LOGOUT') !== false) return 'secondary';
    if (strpos($action, 'ADD') !== false || strpos($action, 'CREATE') !== false) return 'primary';
    if (strpos($action, 'EDIT') !== false || strpos($action, 'UPDATE') !== false) return 'warning';
    if (strpos($action, 'DELETE') !== false) return 'danger';
    return 'info';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Superadmin</title>
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
                        <i class="fas fa-history me-2"></i>Log Aktivitas
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportLogs()">
                                <i class="fas fa-download"></i> Export
                            </button>
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
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Aktivitas Hari Ini</h6>
                                        <h4><?php echo number_format($today_activities); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-day fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">7 Hari Terakhir</h6>
                                        <h4><?php echo number_format($week_activities); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-week fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">30 Hari Terakhir</h6>
                                        <h4><?php echo number_format($month_activities); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-alt fa-2x"></i>
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
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" 
                                           value="<?php echo e($search); ?>" placeholder="Cari aktivitas...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="user">
                                    <option value="">Semua User</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($user['full_name']); ?> (<?php echo ucfirst($user['role']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="action">
                                    <option value="">Semua Aksi</option>
                                    <?php foreach ($actions as $action): ?>
                                        <option value="<?php echo e($action['action']); ?>" <?php echo $action_filter === $action['action'] ? 'selected' : ''; ?>>
                                            <?php echo e($action['action']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date" value="<?php echo e($date_filter); ?>">
                            </div>
                            <div class="col-md-1">
                                <select class="form-select" name="limit">
                                    <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="col-md-1">
                                <a href="logs.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Log Aktivitas
                            <span class="badge bg-secondary ms-2"><?php echo number_format($total_logs); ?> total</span>
                        </h5>
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                        <th>Deskripsi</th>
                                        <th>IP Address</th>
                                        <th>User Agent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Tidak ada log aktivitas ditemukan.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td>
                                                    <small>
                                                        <?php echo date('d/m/Y', strtotime($log['created_at'])); ?><br>
                                                        <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($log['full_name']): ?>
                                                        <strong><?php echo e($log['full_name']); ?></strong><br>
                                                        <small class="text-muted">
                                                            <?php echo e($log['username']); ?>
                                                            <span class="badge bg-<?php echo $log['role'] === 'admin' ? 'primary' : ($log['role'] === 'superadmin' ? 'danger' : 'success'); ?> ms-1">
                                                                <?php echo ucfirst($log['role']); ?>
                                                            </span>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">User tidak ditemukan</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getActionBadge($log['action']); ?>">
                                                        <?php echo e($log['action']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo e($log['description']); ?></small>
                                                </td>
                                                <td>
                                                    <small class="font-monospace"><?php echo e($log['ip_address']); ?></small>
                                                </td>
                                                <td>
                                                    <small class="text-muted" title="<?php echo e($log['user_agent']); ?>">
                                                        <?php echo e(substr($log['user_agent'], 0, 50)); ?>...
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Menampilkan <?php echo number_format($offset + 1); ?> - <?php echo number_format(min($offset + $limit, $total_logs)); ?> 
                                    dari <?php echo number_format($total_logs); ?> log
                                </small>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">First</a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item active">
                                            <span class="page-link"><?php echo $page; ?> / <?php echo $total_pages; ?></span>
                                        </li>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">Last</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        function exportLogs() {
            alert('Fitur export log akan dikembangkan lebih lanjut.');
        }
    </script>
</body>
</html>