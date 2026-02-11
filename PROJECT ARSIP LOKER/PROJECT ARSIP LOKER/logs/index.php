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

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search parameter
$search = sanitize_input($_GET['search'] ?? '');

// Sort parameter
$sort_param = $_GET['sort'] ?? 'created_at_desc';

// Parse sort parameter
if ($sort_param === 'created_at_desc') {
    $sort_by = 'al.created_at';
    $sort_order = 'DESC';
} elseif ($sort_param === 'created_at_asc') {
    $sort_by = 'al.created_at';
    $sort_order = 'ASC';
} elseif ($sort_param === 'name_asc') {
    $sort_by = 'u.full_name';
    $sort_order = 'ASC';
} else {
    $sort_by = 'al.created_at';
    $sort_order = 'DESC';
}

// Build query with search filter
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total logs
try {
    $count_sql = "SELECT COUNT(*) as count 
                  FROM activity_logs al 
                  JOIN users u ON al.user_id = u.id 
                  $where_clause";
    $total_logs = $db->fetch($count_sql, $params)['count'];
    $total_pages = ceil($total_logs / $per_page);
    
    // Get logs with pagination
    $logs_sql = "SELECT al.*, u.full_name, u.username 
                 FROM activity_logs al 
                 JOIN users u ON al.user_id = u.id 
                 $where_clause
                 ORDER BY $sort_by $sort_order 
                 LIMIT ? OFFSET ?";
    
    $logs_params = array_merge($params, [$per_page, $offset]);
    $logs = $db->fetchAll($logs_sql, $logs_params);
    
} catch (Exception $e) {
    $error_message = 'Error mengambil data log: ' . $e->getMessage();
    $logs = [];
    $total_logs = 0;
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Sistem Arsip Dokumen</title>
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
                        <i class="fas fa-history me-2"></i>
                        Log Aktivitas
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="text-muted">Total: <?php echo number_format($total_logs); ?> log</span>
                    </div>
                </div>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" id="searchForm" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" 
                                           value="<?php echo e($search); ?>" 
                                           placeholder="Cari berdasarkan nama atau username..."
                                           onkeypress="if(event.key === 'Enter') { event.preventDefault(); document.getElementById('searchForm').submit(); }">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="sort" onchange="document.getElementById('searchForm').submit();">
                                    <option value="created_at_desc" <?php echo $sort_param === 'created_at_desc' ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="created_at_asc" <?php echo $sort_param === 'created_at_asc' ? 'selected' : ''; ?>>Terlama</option>
                                    <option value="name_asc" <?php echo $sort_param === 'name_asc' ? 'selected' : ''; ?>>Nama</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Cari
                                </button>
                            </div>
                            <?php if (!empty($search)): ?>
                            <div class="col-12">
                                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Hapus Filter
                                </a>
                                <small class="text-muted ms-2">
                                    Menampilkan <?php echo number_format($total_logs); ?> hasil untuk "<?php echo e($search); ?>"
                                </small>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Deskripsi</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo format_date_indonesia($log['created_at'], true); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo e($log['full_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo e($log['username']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo e($log['action']); ?></span>
                                            </td>
                                            <td><?php echo e($log['description']); ?></td>
                                            <td><small class="text-muted"><?php echo e($log['ip_address']); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada data log</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Log pagination">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $pagination_params = [];
                                    if (!empty($search)) {
                                        $pagination_params['search'] = $search;
                                    }
                                    if (!empty($sort_param) && $sort_param !== 'created_at_desc') {
                                        $pagination_params['sort'] = $sort_param;
                                    }
                                    $pagination_query = !empty($pagination_params) ? '&' . http_build_query($pagination_params) : '';
                                    ?>
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $pagination_query; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $pagination_query; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $pagination_query; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

















