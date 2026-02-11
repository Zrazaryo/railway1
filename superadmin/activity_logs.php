<?php
// Load functions
if (file_exists('../includes/functions.php')) {
    require_once '../includes/functions.php';
}

// Inisialisasi session
init_multi_session();

// Check if user is logged in as superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: ../auth/login_superadmin.php');
    exit();
}

// Load database
if (file_exists('../config/database.php')) {
    require_once '../config/database.php';
} else {
    die('Database configuration not found');
}

$superadmin_id = $_SESSION['user_id'];
$superadmin_name = $_SESSION['full_name'];

// Get filter options
$search_query = $_GET['search'] ?? '';
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search_query)) {
    $where_conditions[] = "(al.description LIKE ?)";
    $params[] = "%$search_query%";
}

if (!empty($action_filter)) {
    $where_conditions[] = "al.action = ?";
    $params[] = $action_filter;
}

if (!empty($user_filter)) {
    $where_conditions[] = "al.user_id = ?";
    $params[] = $user_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get activity logs
$sql = "SELECT al.*, u.full_name, u.role 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $where_clause
        ORDER BY al.$sort_by $sort_order
        LIMIT 500";

try {
    $logs = $db->fetchAll($sql, $params) ?? [];
} catch (Exception $e) {
    $logs = [];
    $error = "Error loading logs: " . $e->getMessage();
}

// Get list of actions for filter
$actions = $db->fetchAll(
    "SELECT DISTINCT action FROM activity_logs ORDER BY action ASC",
    []
) ?? [];

// Get users for filter
$users_list = $db->fetchAll(
    "SELECT id, full_name, role FROM users WHERE role IN ('admin', 'staff', 'user') ORDER BY full_name ASC LIMIT 50",
    []
) ?? [];

$current_tab = get_tab_id();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Monitoring Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: #F9FAFB;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-superadmin {
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            padding: 1rem 0;
        }
        .navbar-superadmin .navbar-brand {
            font-size: 1.2rem;
            font-weight: 700;
            color: white !important;
        }
        .page-header {
            background: white;
            padding: 2rem;
            border-bottom: 1px solid #E5E7EB;
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        .page-subtitle {
            color: #6B7280;
            font-size: 0.95rem;
        }
        .filter-panel {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            margin-bottom: 2rem;
        }
        .filter-group {
            margin-bottom: 1rem;
        }
        .filter-group:last-child {
            margin-bottom: 0;
        }
        .filter-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .form-control, .form-select {
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 0.6rem 0.9rem;
            font-size: 0.9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667EEA;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-filter {
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .activity-table {
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .activity-table table {
            margin-bottom: 0;
        }
        .activity-table th {
            background: #F9FAFB;
            border: none;
            font-weight: 600;
            color: #6B7280;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1.2rem;
        }
        .activity-table td {
            padding: 1.2rem;
            border-top: 1px solid #E5E7EB;
            vertical-align: middle;
        }
        .action-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .action-badge.login {
            background: rgba(34, 197, 94, 0.1);
            color: #166534;
        }
        .action-badge.logout {
            background: rgba(107, 114, 128, 0.1);
            color: #374151;
        }
        .action-badge.create {
            background: rgba(59, 130, 246, 0.1);
            color: #1D4ED8;
        }
        .action-badge.update {
            background: rgba(245, 158, 11, 0.1);
            color: #B45309;
        }
        .action-badge.delete {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
        }
        .action-badge.view {
            background: rgba(102, 126, 234, 0.1);
            color: #667EEA;
        }
        .action-badge.download {
            background: rgba(139, 92, 246, 0.1);
            color: #6D28D9;
        }
        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-readonly {
            display: inline-block;
            background: rgba(34, 197, 94, 0.1);
            color: #166534;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #9CA3AF;
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
        }
        .empty-state i {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .timestamp {
            color: #9CA3AF;
            font-size: 0.85rem;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-superadmin">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-user-secret"></i> Superadmin
            </a>
            <div class="ms-auto">
                <span style="color: rgba(255, 255, 255, 0.85); margin-right: 1rem;">
                    <i class="fas fa-eye"></i> Monitoring Read-Only
                </span>
                <a href="dashboard.php" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-history"></i> Log Aktivitas
            <span class="badge-readonly">
                <i class="fas fa-eye"></i> READ-ONLY
            </span>
        </h1>
        <p class="page-subtitle">Pantau semua aktivitas pengguna dalam sistem termasuk login, perubahan data, dan aksi lainnya.</p>
    </div>

    <div class="container-fluid" style="padding: 0 2rem;">
        <!-- Filter Panel -->
        <div class="filter-panel">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <div class="filter-group">
                        <label for="search" class="filter-label">
                            <i class="fas fa-search"></i> Cari Deskripsi
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="search" 
                            name="search" 
                            placeholder="Masukkan kata kunci..."
                            value="<?php echo htmlspecialchars($search_query); ?>"
                        >
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="filter-group">
                        <label for="action" class="filter-label">
                            <i class="fas fa-tasks"></i> Aksi
                        </label>
                        <select class="form-select" id="action" name="action">
                            <option value="">-- Semua Aksi --</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action['action']); ?>" 
                                    <?php echo $action_filter === $action['action'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action['action']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="filter-group">
                        <label for="user" class="filter-label">
                            <i class="fas fa-user"></i> Pengguna
                        </label>
                        <select class="form-select" id="user" name="user">
                            <option value="">-- Semua Pengguna --</option>
                            <?php foreach ($users_list as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                    <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="filter-group">
                        <label for="date_from" class="filter-label">
                            <i class="fas fa-calendar"></i> Dari Tanggal
                        </label>
                        <input 
                            type="date" 
                            class="form-control" 
                            id="date_from" 
                            name="date_from" 
                            value="<?php echo htmlspecialchars($date_from); ?>"
                        >
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="filter-group">
                        <label for="date_to" class="filter-label">
                            <i class="fas fa-calendar"></i> Sampai Tanggal
                        </label>
                        <input 
                            type="date" 
                            class="form-control" 
                            id="date_to" 
                            name="date_to" 
                            value="<?php echo htmlspecialchars($date_to); ?>"
                        >
                    </div>
                </div>

                <div class="col-md-1 d-flex align-items-end gap-2">
                    <button type="submit" class="btn-filter flex-grow-1" style="padding: 0.6rem 0.5rem;">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="activity_logs.php" class="btn-filter flex-grow-1" style="padding: 0.6rem 0.5rem; text-align: center; text-decoration: none;">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Activity Table -->
        <div class="activity-table">
            <?php if (!empty($logs)): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Waktu</th>
                            <th style="width: 15%;">Pengguna</th>
                            <th style="width: 12%;">Aksi</th>
                            <th style="width: 40%;">Deskripsi</th>
                            <th style="width: 18%;">Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div class="timestamp">
                                        <i class="fas fa-clock"></i>
                                        <?php echo isset($log['created_at']) ? date('d/m/Y H:i:s', strtotime($log['created_at'])) : '-'; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-badge">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($log['full_name'] ?? 'S', 0, 1)); ?>
                                        </div>
                                        <span style="font-weight: 500; color: #1F2937;">
                                            <?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $action = strtoupper($log['action'] ?? '-');
                                    $action_class = strtolower($log['action'] ?? 'view');
                                    // Normalize action class
                                    if (strpos($action_class, 'login') !== false) $action_class = 'login';
                                    elseif (strpos($action_class, 'logout') !== false) $action_class = 'logout';
                                    elseif (strpos($action_class, 'create') !== false || strpos($action_class, 'add') !== false) $action_class = 'create';
                                    elseif (strpos($action_class, 'update') !== false || strpos($action_class, 'edit') !== false) $action_class = 'update';
                                    elseif (strpos($action_class, 'delete') !== false) $action_class = 'delete';
                                    elseif (strpos($action_class, 'download') !== false) $action_class = 'download';
                                    else $action_class = 'view';
                                    ?>
                                    <span class="action-badge <?php echo $action_class; ?>">
                                        <?php 
                                        if ($action_class === 'login') echo '<i class="fas fa-sign-in-alt"></i> ';
                                        elseif ($action_class === 'logout') echo '<i class="fas fa-sign-out-alt"></i> ';
                                        elseif ($action_class === 'create') echo '<i class="fas fa-plus"></i> ';
                                        elseif ($action_class === 'update') echo '<i class="fas fa-edit"></i> ';
                                        elseif ($action_class === 'delete') echo '<i class="fas fa-trash"></i> ';
                                        elseif ($action_class === 'download') echo '<i class="fas fa-download"></i> ';
                                        else echo '<i class="fas fa-eye"></i> ';
                                        ?>
                                        <?php echo $action; ?>
                                    </span>
                                </td>
                                <td>
                                    <small style="color: #6B7280;">
                                        <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
                                    </small>
                                </td>
                                <td>
                                    <small style="color: #9CA3AF;">
                                        <?php echo ucfirst($log['role'] ?? '-'); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>Tidak ada log aktivitas</h5>
                    <p>Tidak ditemukan log aktivitas sesuai filter yang Anda pilih.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Result Count -->
        <?php if (!empty($logs)): ?>
            <div style="margin-top: 1.5rem; color: #6B7280; text-align: center;">
                <small>
                    Menampilkan <strong><?php echo count($logs); ?></strong> log aktivitas (Maksimal 500)
                </small>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
