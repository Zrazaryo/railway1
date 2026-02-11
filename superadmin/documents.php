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
$documents = [];

// Filter parameters
$search = sanitize_input($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$origin_filter = $_GET['origin'] ?? '';
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : null;
$sort_param = $_GET['sort'] ?? 'created_at_desc';

// Determine sort order
$sort_by = 'created_at';
$sort_order = 'DESC';
if ($sort_param === 'created_at_asc') {
    $sort_order = 'ASC';
} elseif ($sort_param === 'full_name_asc') {
    $sort_by = 'full_name';
    $sort_order = 'ASC';
} elseif ($sort_param === 'full_name_desc') {
    $sort_by = 'full_name';
    $sort_order = 'DESC';
}

try {
    // Build query conditions
    $where_conditions = ["d.status != 'trashed'"];  // Exclude trashed documents
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(d.full_name LIKE ? OR d.nik LIKE ? OR d.passport_number LIKE ? OR d.document_number LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($status_filter)) {
        $where_conditions[] = "d.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($category_filter) && in_array($category_filter, ['WNA', 'WNI'])) {
        $where_conditions[] = "d.citizen_category = ?";
        $params[] = $category_filter;
    }

    if (!empty($origin_filter)) {
        $where_conditions[] = "d.document_origin = ?";
        $params[] = $origin_filter;
    }

    if ($year_filter) {
        $where_conditions[] = "d.document_year = ?";
        $params[] = $year_filter;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get documents with user info
    $sql_documents = "
        SELECT 
            d.id, d.document_number, d.full_name, d.nik, d.passport_number,
            d.document_order_number, d.document_year, d.citizen_category, 
            d.document_origin, d.status, d.month_number, d.created_at,
            u.full_name as created_by_name, u.role as created_by_role
        FROM documents d
        LEFT JOIN users u ON d.created_by = u.id
        WHERE $where_clause
        ORDER BY d.$sort_by $sort_order
        LIMIT 1000
    ";
    $documents = $db->fetchAll($sql_documents, $params);

    // Get statistics - exclude trashed documents
    $total_active = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'active'")['count'] ?? 0;
    $total_deleted = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE status = 'deleted'")['count'] ?? 0;
    $total_wna = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE citizen_category = 'WNA' AND status != 'trashed'")['count'] ?? 0;
    $total_wni = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE citizen_category = 'WNI' AND status != 'trashed'")['count'] ?? 0;

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Helper function for document origin
function format_document_origin_label($origin) {
    switch ($origin) {
        case 'imigrasi_jakarta_pusat_kemayoran':
            return 'Imigrasi Jakarta Pusat Kemayoran';
        case 'imigrasi_ulp_semanggi':
            return 'Imigrasi ULP Semanggi';
        case 'imigrasi_lounge_senayan_city':
            return 'Imigrasi Lounge Senayan City';
        default:
            return $origin ?: '-';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Keseluruhan - Superadmin</title>
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
                        <i class="fas fa-file-alt me-2"></i>Dokumen Keseluruhan
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
                                        <h6 class="card-title">Dokumen Aktif</h6>
                                        <h4><?php echo number_format($total_active); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Dokumen Pemusnahan</h6>
                                        <h4><?php echo number_format($total_deleted); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-trash-alt fa-2x"></i>
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
                                        <h6 class="card-title">WNA</h6>
                                        <h4><?php echo number_format($total_wna); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-globe fa-2x"></i>
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
                                        <h6 class="card-title">WNI</h6>
                                        <h4><?php echo number_format($total_wni); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-flag fa-2x"></i>
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
                                           value="<?php echo e($search); ?>" placeholder="Cari dokumen...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="deleted" <?php echo $status_filter === 'deleted' ? 'selected' : ''; ?>>Pemusnahan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="category">
                                    <option value="">Semua Kategori</option>
                                    <option value="WNA" <?php echo $category_filter === 'WNA' ? 'selected' : ''; ?>>WNA</option>
                                    <option value="WNI" <?php echo $category_filter === 'WNI' ? 'selected' : ''; ?>>WNI</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="sort">
                                    <option value="created_at_desc" <?php echo $sort_param == 'created_at_desc' ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="created_at_asc" <?php echo $sort_param == 'created_at_asc' ? 'selected' : ''; ?>>Terlama</option>
                                    <option value="full_name_asc" <?php echo $sort_param == 'full_name_asc' ? 'selected' : ''; ?>>Nama A-Z</option>
                                    <option value="full_name_desc" <?php echo $sort_param == 'full_name_desc' ? 'selected' : ''; ?>>Nama Z-A</option>
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

                <!-- Documents Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Dokumen
                            <span class="badge bg-secondary ms-2"><?php echo number_format(count($documents)); ?> dokumen</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Lengkap</th>
                                        <th>NIK</th>
                                        <th>No Passport</th>
                                        <th>Status</th>
                                        <th>Kategori</th>
                                        <th>Lokasi</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($documents)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Tidak ada dokumen ditemukan.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($documents as $doc): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td class="fw-semibold"><?php echo e($doc['full_name'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['nik'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['passport_number'] ?? '-'); ?></td>
                                                <td>
                                                    <?php if ($doc['status'] === 'deleted'): ?>
                                                        <span class="badge bg-danger">Pemusnahan</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo e($doc['citizen_category'] ?? 'WNI'); ?></span>
                                                </td>
                                                <td><?php echo e($doc['month_number'] ?? '-'); ?></td>
                                                <td>
                                                    <?php echo e($doc['created_by_name'] ?? '-'); ?>
                                                    <?php if ($doc['created_by_role']): ?>
                                                        <br><small class="text-muted">(<?php echo ucfirst($doc['created_by_role']); ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewDocument(<?php echo $doc['id']; ?>)" 
                                                            title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
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

    <!-- View Document Modal -->
    <div class="modal fade" id="viewDocumentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="documentDetails" style="max-height: 80vh; overflow-y: auto;">
                    <!-- Document details will be loaded here -->
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
        function viewDocument(id) {
            fetch(`../documents/view.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('documentDetails').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('viewDocumentModal')).show();
                    } else {
                        alert('Gagal memuat detail dokumen: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat dokumen');
                });
        }
    </script>
</body>
</html>