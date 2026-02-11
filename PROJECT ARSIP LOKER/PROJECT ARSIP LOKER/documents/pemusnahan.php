<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Hanya admin yang boleh mengakses
require_admin();

// Helper label asal dokumen
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

// Pagination & filter dasar
$page  = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$search = sanitize_input($_GET['search'] ?? '');
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : null;
$category_filter = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;
$adv_full_name = trim($_GET['full_name'] ?? '');
$adv_passport = trim($_GET['passport_number'] ?? '');
$adv_birth_date = trim($_GET['birth_date'] ?? '');

// Sort sederhana
$sort_param = $_GET['sort'] ?? 'created_at_desc';
$sort_by = 'created_at';
$sort_order = 'DESC';
if ($sort_param === 'created_at_asc') {
    $sort_by = 'created_at';
    $sort_order = 'ASC';
} elseif ($sort_param === 'full_name_asc') {
    $sort_by = 'full_name';
    $sort_order = 'ASC';
}

// Build where
$where_conditions = ["d.status = 'deleted'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(d.full_name LIKE ? OR d.nik LIKE ? OR d.passport_number LIKE ? OR d.document_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($year_filter) {
    $where_conditions[] = "d.document_year = ?";
    $params[] = $year_filter;
}

if (!empty($category_filter)) {
    $where_conditions[] = "d.citizen_category = ?";
    $params[] = $category_filter;
}

if (!empty($adv_full_name)) {
    $where_conditions[] = "d.full_name LIKE ?";
    $params[] = "%$adv_full_name%";
}

if (!empty($adv_passport)) {
    $where_conditions[] = "d.passport_number LIKE ?";
    $params[] = "%$adv_passport%";
}

if (!empty($adv_birth_date)) {
    $where_conditions[] = "d.birth_date = ?";
    $params[] = $adv_birth_date;
}

$where_clause = implode(' AND ', $where_conditions);

// Ambil opsi tahun
$years = $db->fetchAll("SELECT DISTINCT document_year FROM documents WHERE status = 'deleted' AND document_year IS NOT NULL ORDER BY document_year DESC");

// Hitung total
$count_sql = "SELECT COUNT(*) as total FROM documents d WHERE $where_clause";
$total_records = $db->fetch($count_sql, $params)['total'] ?? 0;

// Ambil data
$sql = "SELECT d.id, d.document_number, d.full_name, d.nik, d.passport_number, d.month_number, d.document_order_number,
               d.document_year, d.document_origin, d.created_at, d.citizen_category,
               u.full_name AS created_by_name,
               l.name AS locker_name
        FROM documents d
        LEFT JOIN users u ON d.created_by = u.id
        LEFT JOIN lockers l ON d.month_number = l.code
        WHERE $where_clause
        ORDER BY d.$sort_by $sort_order
        LIMIT $limit OFFSET $offset";

$documents = $db->fetchAll($sql, $params);
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lemari Pemusnahan - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h3 mb-0">Lemari Pemusnahan</h1>
                        <small class="text-muted">Daftar dokumen yang sudah dihapus (status: deleted)</small>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['import_duplicate_rows']) && !empty($_SESSION['import_duplicate_rows'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Dokumen tidak dapat diimport (duplikat):</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($_SESSION['import_duplicate_rows'] as $duplicate): ?>
                                <li>Baris <?php echo $duplicate['row']; ?>: <?php echo htmlspecialchars($duplicate['reason']); ?> (<?php echo htmlspecialchars($duplicate['data']); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['import_duplicate_rows']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['import_failed_rows']) && !empty($_SESSION['import_failed_rows'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Beberapa baris gagal diimport:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($_SESSION['import_failed_rows'] as $failed): ?>
                                <li>Baris <?php echo $failed['row']; ?>: <?php echo htmlspecialchars($failed['reason']); ?> (<?php echo htmlspecialchars($failed['data']); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['import_failed_rows']); ?>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input id="searchInput" type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Cari nama, NIK, paspor, atau nomor dokumen">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="sort" onchange="this.form.submit()">
                                    <option value="created_at_desc" <?php echo $sort_param === 'created_at_desc' ? 'selected' : ''; ?>>Dokumen Terbaru</option>
                                    <option value="created_at_asc" <?php echo $sort_param === 'created_at_asc' ? 'selected' : ''; ?>>Dokumen Terlama</option>
                                    <option value="full_name_asc" <?php echo $sort_param === 'full_name_asc' ? 'selected' : ''; ?>>Nama A-Z</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="category" onchange="this.form.submit()">
                                    <option value="">Semua Kategori</option>
                                    <option value="WNA" <?php echo $category_filter === 'WNA' ? 'selected' : ''; ?>>WNA</option>
                                    <option value="WNI" <?php echo $category_filter === 'WNI' ? 'selected' : ''; ?>>WNI</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="yearFilter" class="form-select" name="year" onchange="this.form.submit()">
                                    <option value="">Semua Tahun</option>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?php echo $y['document_year']; ?>" <?php echo ($year_filter == $y['document_year']) ? 'selected' : ''; ?>>
                                            <?php echo $y['document_year']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-md-1">
                                <a href="pemusnahan.php" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#advancedSearchModal" title="Pencarian Lanjutan">
                                    <span class="fw-semibold">Pencarian Lanjutan</span>
                                    <i class="fas fa-search-plus"></i>
                                </button>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="fas fa-upload"></i>
                                </button>
                            </div>
                        </form>
                        <div class="mt-3">
                            <a href="pemusnahan_years.php" class="btn btn-outline-secondary">
                                Lihat Detail Lemari
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Modal Pilih Tahun dan Lemari -->
                <div class="modal fade" id="selectYearLockerModal" tabindex="-1" aria-labelledby="selectYearLockerModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="selectYearLockerModalLabel">Pilih Tahun dan Lemari</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-4">
                                    <h6 class="mb-3">Pilih Tahun</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:80px">Aksi</th>
                                                    <th>Tahun Dokumen</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($years as $y): ?>
                                                    <tr>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-primary w-100"
                                                                    onclick="document.querySelector('#yearFilter').value='<?php echo $y['document_year']; ?>'; document.forms[0].submit();"
                                                                    data-bs-dismiss="modal">
                                                                Pilih
                                                            </button>
                                                        </td>
                                                        <td><?php echo $y['document_year']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php if (is_admin()): ?>
                            <div class="d-flex justify-content-end mb-2 gap-2">
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                    <i class="fas fa-trash"></i> Hapus Terpilih
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="exportSelected()">
                                    <i class="fas fa-download"></i> Export Terpilih
                                </button>
                                <a href="export_pemusnahan.php?all=1" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-download"></i> Export Semua
                                </a>
                            </div>
                            <?php endif; ?>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <?php if (is_admin()): ?>
                                        <th style="width:30px">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" title="Pilih Semua">
                                        </th>
                                        <?php endif; ?>
                                        <th style="width:50px">No</th>
                                        <th>Nama Lengkap</th>
                                        <th>NIK</th>
                                        <th>No Passport</th>
                                        <th>Kode Lemari</th>
                                        <th>Nama Rak</th>
                                        <th>Urutan Dokumen</th>
                                        <th>Kode Dokumen</th>
                                        <th>Tahun Dokumen</th>
                                        <th>Dokumen Berasal</th>
                                        <th>Kategori</th>
                                        <th>Di Buat Oleh</th>
                                        <th style="width:140px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($documents)): ?>
                                        <tr>
                                            <td colspan="12" class="text-center py-4 text-muted">
                                                <i class="fas fa-archive fa-2x mb-2"></i><br>
                                                Tidak ada dokumen pemusnahan.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = $offset + 1; foreach ($documents as $doc): ?>
                                            <tr>
                                                <?php if (is_admin()): ?>
                                                <td>
                                                    <input type="checkbox" class="document-checkbox" name="doc_ids[]" value="<?php echo $doc['id']; ?>">
                                                </td>
                                                <?php endif; ?>
                                                <td class="text-muted"><?php echo $no++; ?></td>
                                                <td class="fw-semibold"><?php echo e($doc['full_name'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['nik'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['passport_number'] ?? '-'); ?></td>
                                                <td><?php echo e(substr($doc['month_number'] ?? '-', 0, 1)); ?></td>
                                                <td><?php echo e($doc['locker_name'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['document_order_number'] ?? '-'); ?></td>
                                                <td><?php echo e(($doc['locker_name'] ?? '-') . '.' . ($doc['document_order_number'] ?? '-')); ?></td>
                                                <td><?php echo e($doc['document_year'] ?? '-'); ?></td>
                                                <td><?php echo e(format_document_origin_label($doc['document_origin'] ?? '')); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo e($doc['citizen_category'] ?? 'WNI'); ?></span>
                                                </td>
                                                <td><?php echo e($doc['created_by_name'] ?? '-'); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewDocument(<?php echo $doc['id']; ?>)" title="Lihat">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <a href="edit.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>)" title="Hapus Permanen">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $start_page + 4);
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
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
                </div>
            </main>
        </div>
    </div>

    <!-- Modal detail dokumen (reuse view.php) -->
    <div class="modal fade" id="viewDocumentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="documentDetails" style="max-height: 80vh; overflow-y: auto;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="fas fa-upload me-2"></i>
                        Import Dokumen Pemusnahan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="import_pemusnahan.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="import_file" class="form-label">Pilih File Excel/CSV</label>
                            <input type="file" class="form-control" id="import_file" name="import_file" 
                                   accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">
                                Format file: .xlsx, .xls, .csv. Wajib ada kolom: Nama Lengkap, NIK, No Passport, Kode Lemari, Tahun.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="import_year" class="form-label">Tahun Dokumen (opsional)</label>
                            <input type="number" class="form-control" id="import_year" name="import_year" 
                                   min="1900" max="2100" placeholder="Kosongkan jika ingin semua tahun">
                            <div class="form-text">
                                Isi untuk membatasi tahun; kosongkan untuk import semua tahun.
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Dokumen hasil import akan berstatus <strong>deleted (pemusnahan)</strong> dan muncul di halaman ini.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-upload me-1"></i>
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Advanced Search Modal -->
    <div class="modal fade" id="advancedSearchModal" tabindex="-1" aria-labelledby="advancedSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="advancedSearchModalLabel">
                        <i class="fas fa-search-plus"></i> Pencarian Lanjutan Dokumen Pemusnahan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="advancedSearchForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="search_full_name" class="form-label">
                                    <i class="fas fa-user text-primary"></i> Nama Lengkap
                                </label>
                                <input type="text" class="form-control" id="search_full_name" name="full_name" placeholder="Masukkan nama lengkap...">
                                <div class="form-text">Cari berdasarkan nama lengkap pemilik dokumen</div>
                            </div>
                            <div class="col-md-6">
                                <label for="search_birth_date" class="form-label">
                                    <i class="fas fa-calendar text-success"></i> Tanggal Lahir
                                </label>
                                <input type="date" class="form-control" id="search_birth_date" name="birth_date">
                                <div class="form-text">Cari berdasarkan tanggal lahir</div>
                            </div>
                            <div class="col-md-6">
                                <label for="search_passport" class="form-label">
                                    <i class="fas fa-passport text-info"></i> Nomor Paspor
                                </label>
                                <input type="text" class="form-control" id="search_passport" name="passport_number" placeholder="Masukkan nomor paspor...">
                                <div class="form-text">Cari berdasarkan nomor paspor</div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-lightbulb"></i> Tips Pencarian:</h6>
                            <ul class="mb-0">
                                <li>Isi satu atau lebih field untuk hasil yang lebih spesifik.</li>
                                <li>Kombinasi beberapa field memberi hasil lebih akurat.</li>
                                <li>Kosongkan field yang tidak ingin dipakai.</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" onclick="performAdvancedSearchPemusnahan()">
                        <i class="fas fa-search"></i> Cari Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDocument(id) {
            fetch(`view.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('documentDetails').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('viewDocumentModal')).show();
                    } else {
                        alert('Gagal memuat detail dokumen');
                    }
                })
                .catch(() => alert('Terjadi kesalahan saat memuat dokumen'));
        }

        function deleteDocument(id) {
            if (!confirm('Hapus permanen dokumen ini?')) return;
            fetch('delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Berhasil dihapus');
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menghapus dokumen');
                }
            })
            .catch(() => alert('Terjadi kesalahan saat menghapus dokumen'));
        }

        function performAdvancedSearchPemusnahan() {
            const formData = new FormData(document.getElementById('advancedSearchForm'));
            const params = new URLSearchParams();

            for (let [key, value] of formData.entries()) {
                if (value && value.trim() !== '') {
                    params.append(key, value.trim());
                }
            }

            const searchInput = document.getElementById('searchInput');
            if (searchInput && searchInput.value.trim() !== '') {
                params.append('search', searchInput.value.trim());
            }

            const yearSelect = document.getElementById('yearFilter');
            if (yearSelect && yearSelect.value) {
                params.append('year', yearSelect.value);
            }

            window.location.href = `pemusnahan.php?${params.toString()}`;
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.document-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }

        function getSelectedIds() {
            const checkboxes = document.querySelectorAll('.document-checkbox:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }

        function deleteSelected() {
            const ids = getSelectedIds();
            if (ids.length === 0) {
                alert('Pilih minimal satu dokumen yang akan dihapus.');
                return;
            }
            if (!confirm(`Hapus permanen ${ids.length} dokumen terpilih?`)) return;
            fetch('delete_multiple.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`Berhasil menghapus ${data.deleted_count} dokumen`);
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menghapus dokumen');
                }
            })
            .catch(() => alert('Terjadi kesalahan saat menghapus dokumen'));
        }

        function exportSelected() {
            const ids = getSelectedIds();
            if (ids.length === 0) {
                alert('Pilih minimal satu dokumen untuk di-export!');
                return;
            }
            const params = new URLSearchParams();
            ids.forEach(id => params.append('ids[]', id));
            window.location.href = `export_pemusnahan.php?${params.toString()}`;
        }
    </script>
</body>
</html>

