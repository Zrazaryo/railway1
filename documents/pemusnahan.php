<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/search_template.php';

// Boleh diakses admin dan staff (staff hanya bisa lihat)
require_login();

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
$origin_filter = isset($_GET['origin']) && $_GET['origin'] !== '' ? $_GET['origin'] : null;
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

$allowed_origins = [
    'imigrasi_lounge_senayan_city',
    'imigrasi_ulp_semanggi',
    'imigrasi_jakarta_pusat_kemayoran'
];
if (!empty($origin_filter) && in_array($origin_filter, $allowed_origins, true)) {
    $where_conditions[] = "d.document_origin = ?";
    $params[] = $origin_filter;
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
               COALESCE(u_orig.full_name, u.full_name) AS created_by_name,
               l.code AS locker_code,
               l.name AS locker_name
        FROM documents d
        LEFT JOIN users u ON d.created_by = u.id
        LEFT JOIN users u_orig ON d.original_created_by = u_orig.id
        LEFT JOIN lockers l ON d.month_number = l.name
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
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (is_admin()): ?>
                            <a href="../dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
                        <?php else: ?>
                            <a href="../staff/dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
                        <?php endif; ?>
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
                <?php
                render_search_form([
                    'search_placeholder' => 'Cari nama, NIK, paspor, atau nomor dokumen',
                    'search_value' => $search,
                    'sort_value' => $sort_param,
                    'category_value' => $category_filter,
                    'refresh_url' => 'pemusnahan.php',
                    'sort_options' => [
                        'created_at_desc' => 'Dokumen Terbaru',
                        'created_at_asc' => 'Dokumen Terlama',
                        'full_name_asc' => 'Nama A-Z'
                    ],
                    'additional_filters' => [
                        [
                            'name' => 'origin',
                            'placeholder' => 'Semua Asal Dokumen',
                            'col_size' => '2',
                            'options' => [
                                'imigrasi_lounge_senayan_city' => 'Imigrasi Lounge Senayan City',
                                'imigrasi_ulp_semanggi' => 'Imigrasi ULP Semanggi',
                                'imigrasi_jakarta_pusat_kemayoran' => 'Imigrasi Jakarta Pusat Kemayoran'
                            ]
                        ],
                        [
                            'name' => 'year',
                            'placeholder' => 'Semua Tahun',
                            'col_size' => '2',
                            'options' => array_column($years, 'document_year', 'document_year')
                        ]
                    ]
                ]);
                ?>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mt-3">
                            <a href="pemusnahan_years.php" class="btn btn-outline-secondary">
                                Lihat Detail Lemari
                            </a>
                            <?php if (is_admin()): ?>
                            <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-upload me-1"></i> Import Data
                            </button>
                            <?php endif; ?>
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
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteAllDocuments()" title="Hapus semua dokumen pemusnahan secara permanen">
                                    <i class="fas fa-trash-alt"></i> Hapus Semua
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
                                            <td colspan="<?php echo is_admin() ? '14' : '13'; ?>" class="text-center py-4 text-muted">
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
                                                <!-- Kode lemari = ekstrak dari locker_code atau month_number -->
                                                <?php
                                                $kodeLemari = '-';
                                                if (isset($doc['locker_code']) && !empty($doc['locker_code'])) {
                                                    // Gunakan locker_code jika ada, ekstrak kode lemari
                                                    $code = $doc['locker_code'];
                                                    // Format: A101 -> A1, A1 -> A1, A01 -> A
                                                    if (preg_match('/^([A-Z])(\d)(\d{2})$/', $code, $matches)) {
                                                        $kodeLemari = $matches[1] . $matches[2]; // A1, B1, Z1
                                                    } elseif (preg_match('/^([A-Z])(\d+)$/', $code, $matches)) {
                                                        $kodeLemari = $code; // A1, A10, B5
                                                    } else {
                                                        $kodeLemari = substr($code, 0, 1); // A, B, Z
                                                    }
                                                } elseif (!empty($doc['month_number'])) {
                                                    // Fallback: ekstrak dari month_number (misal A.01 -> A, A1.01 -> A1)
                                                    if (preg_match('/^([A-Z])(\d)\./', $doc['month_number'], $matches)) {
                                                        $kodeLemari = $matches[1] . $matches[2]; // A1, B3
                                                    } elseif (preg_match('/^([A-Z])\./', $doc['month_number'], $matches)) {
                                                        $kodeLemari = $matches[1]; // A, B (format lama)
                                                    } else {
                                                        $kodeLemari = substr($doc['month_number'], 0, 1);
                                                    }
                                                }
                                                ?>
                                                <td><?php echo e($kodeLemari); ?></td>
                                                <!-- Nama rak = nilai lengkap month_number (misal A.01, A1.01) -->
                                                <td><?php echo e($doc['month_number'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['document_order_number'] ?? '-'); ?></td>
                                                <?php
                                                    $kodeDokumen = '-';
                                                    if (!empty($doc['month_number']) && $doc['document_order_number'] !== null) {
                                                        $kodeDokumen = $doc['month_number'] . '.' . $doc['document_order_number'];
                                                    }
                                                ?>
                                                <td><?php echo e($kodeDokumen); ?></td>
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
                                                        <?php if (is_admin()): ?>
                                                        <a href="edit.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>)" title="Hapus Permanen">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
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
                                Format file: .xlsx, .xls, .csv. Wajib ada kolom: Nama Lengkap, NIK, No Passport, Kode Lemari, Tahun.<br>
                                <strong>Opsional:</strong> Tambahkan kolom "Dibuat Oleh" atau "Staff" untuk menyimpan pembuat asli dokumen (username atau nama lengkap).
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
    <?php
    render_advanced_search_modal([
        'modal_title' => 'Pencarian Lanjutan Dokumen Pemusnahan',
        'callback_function' => 'performAdvancedSearchPemusnahan'
    ]);
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/advanced-search.js"></script>
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

        let deleteModalInstance = null;
        let deleteCorrectAnswer = 0;
        let deleteSelectedIds = [];
        let deleteSingleId = null;

        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('deleteConfirmModal');
            if (modalEl) {
                deleteModalInstance = new bootstrap.Modal(modalEl);
                modalEl.addEventListener('hidden.bs.modal', () => {
                    document.getElementById('deleteQuestion').innerHTML = '';
                    document.getElementById('deleteAnswer').value = '';
                    deleteSelectedIds = [];
                    deleteSingleId = null;
                });
            }
        });

        function deleteDocument(id) {
            // Generate random math question
            const num1 = Math.floor(Math.random() * 10) + 1;
            const num2 = Math.floor(Math.random() * 10) + 1;
            const correctAnswer = num1 + num2;
            deleteCorrectAnswer = correctAnswer;
            deleteSingleId = id;
            deleteSelectedIds = [];
            
            const questionText = `
                Anda akan menghapus <strong>1</strong> dokumen.<br>
                Untuk konfirmasi, jawab pertanyaan berikut:<br>
                <span class="fw-bold">${num1} + ${num2} = ?</span>
            `;
            document.getElementById('deleteQuestion').innerHTML = questionText;
            document.getElementById('deleteAnswer').value = '';
            
            if (deleteModalInstance) {
                deleteModalInstance.show();
            }
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
            
            // Generate random math question
            const num1 = Math.floor(Math.random() * 10) + 1;
            const num2 = Math.floor(Math.random() * 10) + 1;
            const correctAnswer = num1 + num2;
            deleteCorrectAnswer = correctAnswer;
            deleteSelectedIds = ids.map(id => parseInt(id));
            deleteSingleId = null;
            
            const questionText = `
                Anda akan menghapus <strong>${ids.length}</strong> dokumen.<br>
                Untuk konfirmasi, jawab pertanyaan berikut:<br>
                <span class="fw-bold">${num1} + ${num2} = ?</span>
            `;
            document.getElementById('deleteQuestion').innerHTML = questionText;
            document.getElementById('deleteAnswer').value = '';
            
            if (deleteModalInstance) {
                deleteModalInstance.show();
            }
        }

        function confirmDeleteSelected() {
            const answerInput = document.getElementById('deleteAnswer');
            const answer = parseInt(answerInput.value, 10);
            
            if (isNaN(answer) || answer !== deleteCorrectAnswer) {
                alert('Jawaban salah! Penghapusan dibatalkan.');
                return;
            }
            
            // Handle single delete
            if (deleteSingleId !== null) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: deleteSingleId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Berhasil dihapus');
                        if (deleteModalInstance) {
                            deleteModalInstance.hide();
                        }
                        location.reload();
                    } else {
                        alert(data.message || 'Gagal menghapus dokumen');
                    }
                })
                .catch(() => alert('Terjadi kesalahan saat menghapus dokumen'));
                return;
            }
            
            // Handle multiple delete
            if (!deleteSelectedIds.length) {
                alert('Tidak ada dokumen yang dipilih.');
                return;
            }
            
            fetch('delete_multiple.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: deleteSelectedIds })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`Berhasil menghapus ${data.deleted_count} dokumen`);
                    if (deleteModalInstance) {
                        deleteModalInstance.hide();
                    }
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

        // Variables for delete all functionality
        let deleteAllModalInstance = null;
        let deleteAllSecondModalInstance = null;
        let deleteAllFirstAnswer = 0;
        let deleteAllSecondAnswer = 0;

        // Initialize delete all modals when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const deleteAllModalEl = document.getElementById('deleteAllModal');
            const deleteAllSecondModalEl = document.getElementById('deleteAllSecondModal');
            
            if (deleteAllModalEl) {
                deleteAllModalInstance = new bootstrap.Modal(deleteAllModalEl);
                deleteAllModalEl.addEventListener('hidden.bs.modal', () => {
                    document.getElementById('deleteAllQuestion').innerHTML = '';
                    document.getElementById('deleteAllAnswer').value = '';
                    deleteAllFirstAnswer = 0;
                });
            }
            
            if (deleteAllSecondModalEl) {
                deleteAllSecondModalInstance = new bootstrap.Modal(deleteAllSecondModalEl);
                deleteAllSecondModalEl.addEventListener('hidden.bs.modal', () => {
                    document.getElementById('deleteAllSecondQuestion').innerHTML = '';
                    document.getElementById('deleteAllSecondAnswer').value = '';
                    document.getElementById('finalConfirmCheck').checked = false;
                    deleteAllSecondAnswer = 0;
                });
            }
        });

        function deleteAllDocuments() {
            // Generate first random math question
            const num1 = Math.floor(Math.random() * 20) + 1;
            const num2 = Math.floor(Math.random() * 20) + 1;
            const correctAnswer = num1 + num2;
            deleteAllFirstAnswer = correctAnswer;
            
            const questionText = `
                <div class="text-center">
                    <p class="mb-2">Untuk melanjutkan, jawab pertanyaan berikut:</p>
                    <div class="fs-4 text-danger fw-bold">${num1} + ${num2} = ?</div>
                </div>
            `;
            document.getElementById('deleteAllQuestion').innerHTML = questionText;
            document.getElementById('deleteAllAnswer').value = '';
            
            if (deleteAllModalInstance) {
                deleteAllModalInstance.show();
            }
        }

        function showSecondConfirmation() {
            const answerInput = document.getElementById('deleteAllAnswer');
            const answer = parseInt(answerInput.value, 10);
            
            if (isNaN(answer) || answer !== deleteAllFirstAnswer) {
                alert('Jawaban salah! Silakan coba lagi.');
                answerInput.focus();
                return;
            }
            
            // Hide first modal
            if (deleteAllModalInstance) {
                deleteAllModalInstance.hide();
            }
            
            // Generate second random math question
            const num1 = Math.floor(Math.random() * 15) + 5;
            const num2 = Math.floor(Math.random() * 15) + 5;
            const correctAnswer = num1 + num2;
            deleteAllSecondAnswer = correctAnswer;
            
            const questionText = `
                <div class="text-center">
                    <p class="mb-2">Konfirmasi kedua - jawab pertanyaan berikut:</p>
                    <div class="fs-4 text-danger fw-bold">${num1} + ${num2} = ?</div>
                </div>
            `;
            document.getElementById('deleteAllSecondQuestion').innerHTML = questionText;
            document.getElementById('deleteAllSecondAnswer').value = '';
            document.getElementById('finalConfirmCheck').checked = false;
            
            // Show second modal after a short delay
            setTimeout(() => {
                if (deleteAllSecondModalInstance) {
                    deleteAllSecondModalInstance.show();
                }
            }, 300);
        }

        function confirmDeleteAll() {
            const answerInput = document.getElementById('deleteAllSecondAnswer');
            const answer = parseInt(answerInput.value, 10);
            const finalCheck = document.getElementById('finalConfirmCheck').checked;
            
            if (isNaN(answer) || answer !== deleteAllSecondAnswer) {
                alert('Jawaban salah! Silakan coba lagi.');
                answerInput.focus();
                return;
            }
            
            if (!finalCheck) {
                alert('Anda harus mencentang kotak konfirmasi untuk melanjutkan.');
                return;
            }
            
            // Show loading state
            const confirmBtn = event.target;
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menghapus...';
            confirmBtn.disabled = true;
            
            // Send delete request
            fetch('delete_all_pemusnahan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ confirm: true })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Berhasil! ${data.message}`);
                    window.location.reload();
                } else {
                    alert('Gagal menghapus dokumen: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus dokumen');
            })
            .finally(() => {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
                if (deleteAllSecondModalInstance) {
                    deleteAllSecondModalInstance.hide();
                }
            });
        }
    </script>
    
    <!-- Delete All Confirmation Modal -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAllModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Hapus Semua Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>PERINGATAN!</strong> Anda akan menghapus SEMUA dokumen pemusnahan secara permanen. Tindakan ini tidak dapat dibatalkan!
                    </div>
                    <p id="deleteAllQuestion" class="mb-3 fw-bold"></p>
                    <div class="mb-3">
                        <label for="deleteAllAnswer" class="form-label">Jawaban Anda (Konfirmasi 1)</label>
                        <input type="number" class="form-control" id="deleteAllAnswer" placeholder="Masukkan hasil penjumlahan">
                        <small class="text-muted">Jawab pertanyaan matematika untuk melanjutkan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" onclick="showSecondConfirmation()">
                        <i class="fas fa-arrow-right me-1"></i> Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Delete All Confirmation Modal -->
    <div class="modal fade" id="deleteAllSecondModal" tabindex="-1" aria-labelledby="deleteAllSecondModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAllSecondModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Terakhir
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>KONFIRMASI TERAKHIR!</strong> Ini adalah kesempatan terakhir untuk membatalkan.
                    </div>
                    <p id="deleteAllSecondQuestion" class="mb-3 fw-bold"></p>
                    <div class="mb-3">
                        <label for="deleteAllSecondAnswer" class="form-label">Jawaban Anda (Konfirmasi 2)</label>
                        <input type="number" class="form-control" id="deleteAllSecondAnswer" placeholder="Masukkan hasil penjumlahan">
                        <small class="text-muted">Jawab pertanyaan matematika untuk menghapus semua dokumen.</small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="finalConfirmCheck">
                        <label class="form-check-label fw-bold text-danger" for="finalConfirmCheck">
                            Saya memahami bahwa semua dokumen pemusnahan akan dihapus secara permanen
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmDeleteAll()">
                        <i class="fas fa-trash me-1"></i> Hapus Semua Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">
                        <i class="fas fa-trash me-2"></i> Konfirmasi Penghapusan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteQuestion" class="mb-3"></p>
                    <div class="mb-3">
                        <label for="deleteAnswer" class="form-label">Jawaban Anda</label>
                        <input type="number" class="form-control" id="deleteAnswer" placeholder="Masukkan hasil penjumlahan">
                        <small class="text-muted">Penghapusan hanya akan dilanjutkan jika jawaban benar.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmDeleteSelected()">
                        <i class="fas fa-trash me-1"></i> Hapus Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

