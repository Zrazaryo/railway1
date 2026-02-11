<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/search_template.php';

// Cek login (boleh diakses admin & staff)
require_login();

// Helper untuk menampilkan label asal dokumen
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

// Pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and Filter
$search = sanitize_input($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$origin_filter = $_GET['origin'] ?? '';
$creator_filter = $_GET['creator'] ?? '';
$status_filter = 'active'; // Default status tetap aktif
$sort_param = $_GET['sort'] ?? 'created_at_desc';

// Parse sort parameter
if ($sort_param === 'created_at_desc') {
    $sort_by = 'created_at';
    $sort_order = 'DESC';
} elseif ($sort_param === 'created_at_asc') {
    $sort_by = 'created_at';
    $sort_order = 'ASC';
} elseif ($sort_param === 'full_name_asc') {
    $sort_by = 'full_name';
    $sort_order = 'ASC';
} else {
    $sort_by = 'created_at';
    $sort_order = 'DESC';
}

// Build query
$where_conditions = ["d.status = ?"];
$params = [$status_filter];

// Jika parameter mine=1, tampilkan hanya dokumen milik user
// Hapus filter berdasarkan role - semua user (admin dan staff) bisa lihat semua dokumen
if (isset($_GET['mine']) && $_GET['mine'] == '1') {
    $where_conditions[] = "d.created_by = ?";
    $params[] = $_SESSION['user_id'];
}

if (!empty($search)) {
    $where_conditions[] = "(d.full_name LIKE ? OR d.nik LIKE ? OR d.passport_number LIKE ? OR d.document_number LIKE ? OR COALESCE(u.full_name, '') LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($category_filter) && in_array($category_filter, ['WNA', 'WNI'])) {
    $where_conditions[] = "d.citizen_category = ?";
    $params[] = $category_filter;
}

// Filter asal dokumen
$allowed_origins = [
    'imigrasi_lounge_senayan_city',
    'imigrasi_ulp_semanggi',
    'imigrasi_jakarta_pusat_kemayoran'
];
if (!empty($origin_filter) && in_array($origin_filter, $allowed_origins, true)) {
    $where_conditions[] = "d.document_origin = ?";
    $params[] = $origin_filter;
}

// Filter berdasarkan pembuat dokumen
if (!empty($creator_filter)) {
    $where_conditions[] = "COALESCE(u.full_name, '') LIKE ?";
    $params[] = "%$creator_filter%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get list of creators for filter dropdown
try {
    $creators = $db->fetchAll("
        SELECT DISTINCT u.full_name 
        FROM documents d 
        LEFT JOIN users u ON d.created_by = u.id 
        WHERE u.full_name IS NOT NULL 
        ORDER BY u.full_name ASC
    ");
} catch (Exception $e) {
    $creators = [];
    error_log("Error fetching creators: " . $e->getMessage());
}

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM documents d LEFT JOIN users u ON d.created_by = u.id WHERE $where_clause";
    $total_records = $db->fetch($count_sql, $params)['total'];
} catch (Exception $e) {
    $total_records = 0;
    error_log("Error in count query: " . $e->getMessage());
}

// Get documents
try {
    $sql = "SELECT d.id, d.document_number, d.full_name, d.nik, d.passport_number, d.month_number, d.citizen_category, d.document_origin, d.document_year,
                   d.created_at, d.status, d.file_path, d.file_name, d.file_size, d.document_order_number, 
                   u.full_name as created_by_name,
                   l.code AS locker_code,
                   l.name AS locker_name
            FROM documents d 
            LEFT JOIN users u ON d.created_by = u.id 
            LEFT JOIN lockers l ON d.month_number = l.name
            WHERE $where_clause 
            ORDER BY d.$sort_by $sort_order 
            LIMIT $limit OFFSET $offset";

    $documents = $db->fetchAll($sql, $params);
} catch (Exception $e) {
    $documents = [];
    error_log("Error in main query: " . $e->getMessage());
}

// Categories are now hardcoded as WNA/WNI

// Calculate pagination
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($_GET['mine']) && $_GET['mine'] == '1') ? 'Dokumen Saya' : 'Dokumen Keseluruhan'; ?> - Sistem Arsip Dokumen</title>
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
                <div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-2 pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 mb-0"><?php echo (isset($_GET['mine']) && $_GET['mine'] == '1') ? 'Dokumen Saya' : 'Dokumen Keseluruhan'; ?></h1>
                    <div class="ms-auto">
                        <?php if (is_admin()): ?>
                            <a href="../dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Halaman Utama
                            </a>
                        <?php else: ?>
                            <a href="../staff/dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Halaman Utama
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
                
                <?php if (isset($_SESSION['import_failed_rows']) && !empty($_SESSION['import_failed_rows'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
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
                
                <!-- Search and Filter -->
                <?php
                // Preserve mine parameter if exists
                $additional_params = [];
                if (isset($_GET['mine']) && $_GET['mine'] == '1') {
                    $additional_params['mine'] = '1';
                }
                
                $refresh_url = 'index.php';
                if (isset($_GET['mine']) && $_GET['mine'] == '1') {
                    $refresh_url .= '?mine=1';
                }
                
                render_search_form([
                    'search_placeholder' => 'Cari nama, NIK, paspor, kode dokumen, atau pembuat...',
                    'search_value' => $search,
                    'sort_value' => $sort_param,
                    'category_value' => $category_filter,
                    'refresh_url' => $refresh_url,
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
                            'name' => 'creator',
                            'placeholder' => 'Semua Dibuat Oleh',
                            'col_size' => '2',
                            'options' => array_column($creators, 'full_name', 'full_name')
                        ]
                    ]
                ]);
                
                // Add hidden fields for additional parameters
                foreach ($additional_params as $key => $value) {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
                ?>
                
                <div class="row mt-3">
                    <div class="col-12 d-flex justify-content-end">
                        <?php if (is_superadmin()): ?>
                            <!-- Superadmin: tetap hanya melihat daftar lemari -->
                            <a href="../lockers/select.php" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i>Lihat Lemari
                            </a>
                        <?php elseif (is_admin()): ?>
                            <!-- Admin: bisa mengelola lemari dan dokumen -->
                            <a href="../lockers/select.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Lemari dan Tambah Dokumen
                            </a>
                        <?php else: ?>
                            <!-- Staff: hanya tambah dokumen (tidak punya hak tambah lemari) -->
                            <a href="../lockers/select.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Dokumen
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                    </form>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="text-muted">
                                Menampilkan <?php echo number_format($total_records); ?> dokumen
                                <?php if (!empty($search) || !empty($category_filter) || !empty($origin_filter) || !empty($creator_filter)): ?>
                                    dari hasil pencarian
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php if (is_admin()): ?>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                <i class="fas fa-trash"></i> Hapus Terpilih
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteAllDocuments()" title="Hapus Semua Dokumen">
                                <i class="fas fa-trash-alt"></i> Hapus Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-success" onclick="exportSelected()">
                                <i class="fas fa-download"></i> Export Terpilih
                            </button>
                            <a href="export.php?all=1" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-download"></i> Export Semua
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-upload"></i> Import
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Documents Table -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <?php if (is_admin()): ?>
                                        <th style="width:30px">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" title="Pilih Semua">
                                        </th>
                                        <?php endif; ?>
                                        <th style="width:40px">No</th>
                                        <th style="width:150px">Nama Lengkap</th>
                                        <th style="width:100px">NIK</th>
                                        <th style="width:100px">No Passport</th>
                                        <th style="width:80px">Kode Lemari</th>
                                        <th style="width:100px">Nama Rak</th>
                                        <th style="width:120px">Urutan Dokumen</th>
                                        <th style="width:120px">Kode Dokumen</th>  
                                        <th style="width:100px">Tahun Dokumen</th>
                                        <th style="width:150px">Dokumen Berasal</th>
                                        <th style="width:80px">Kategori</th>
                                        <th style="width:100px">Di Buat Oleh</th>
                                        <th style="width:100px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($documents)): ?>
                                        <tr>
                                            <td colspan="<?php echo is_admin() ? '14' : '13'; ?>" class="text-center py-4">
                                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Tidak ada dokumen ditemukan</p>
                                                <a href="../lockers/select.php" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Tambah Dokumen Pertama
                                                </a>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($documents as $doc): ?>
                                            <tr>
                                                <?php if (is_admin()): ?>
                                                <td>
                                                    <input type="checkbox" class="document-checkbox" name="doc_ids[]" value="<?php echo $doc['id']; ?>">
                                                </td>
                                                <?php endif; ?>
                                                <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                                <td class="fw-semibold"><?php echo e($doc['full_name'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['nik'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['passport_number'] ?? '-'); ?></td>
                                                <!-- Kode lemari = ekstrak dari locker_code atau month_number -->
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
                                                <td><?php echo e($kodeLemari); ?></td>
                                                <!-- Nama rak = nilai lengkap month_number (misal A3.01) -->
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
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewDocument(<?php echo $doc['id']; ?>)" 
                                                                title="Lihat">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if (is_admin() || (isset($_SESSION['user_id']) && $doc['created_by_name'] === $_SESSION['full_name'])): ?>
                                                        <a href="edit.php?id=<?php echo $doc['id']; ?>" 
                                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        <?php if (is_admin() && $doc['status'] !== 'deleted'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="deleteDocument(<?php echo $doc['id']; ?>)" 
                                                                    title="Hapus">
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
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
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
        let deleteModalInstance = null;
        let deleteAllModalInstance = null;
        let deleteCorrectAnswer = 0;
        let deleteSelectedIds = [];
        let deleteSingleId = null;
        let deleteAllAnswer1 = 0;
        let deleteAllAnswer2 = 0;
        let deleteAllCurrentStep = 1; // 1 = question 1, 2 = question 2
        let deleteAllQuestion1Data = null;
        let deleteAllQuestion2Data = null;

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
            
            const deleteAllModalEl = document.getElementById('deleteAllConfirmModal');
            if (deleteAllModalEl) {
                deleteAllModalInstance = new bootstrap.Modal(deleteAllModalEl);
                deleteAllModalEl.addEventListener('hidden.bs.modal', () => {
                    // Reset to step 1
                    deleteAllCurrentStep = 1;
                    document.getElementById('deleteAllQuestionContainer').innerHTML = '';
                    document.getElementById('deleteAllAnswer').value = '';
                    document.getElementById('deleteAllAnswer').placeholder = 'Masukkan hasil penjumlahan';
                    deleteAllAnswer1 = 0;
                    deleteAllAnswer2 = 0;
                    deleteAllQuestion1Data = null;
                    deleteAllQuestion2Data = null;
                    // Hide next button, show check button
                    document.getElementById('deleteAllCheckBtn').style.display = 'inline-block';
                    document.getElementById('deleteAllNextBtn').style.display = 'none';
                });
            }
        });
        // View document function
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
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat dokumen');
                });
        }
        
        // Delete document function with math confirmation
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
        
        // Advanced search function
        function performAdvancedSearch() {
            const formData = new FormData(document.getElementById('advancedSearchForm'));
            const params = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    params.append(key, value);
                }
            }
            
            // Redirect to search results page
            window.location.href = `search_results.php?${params.toString()}`;
        }
        
        // Toggle select all checkbox
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.document-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        // Export selected documents
        function exportSelected() {
            const checkboxes = document.querySelectorAll('.document-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Pilih minimal satu dokumen untuk di-export!');
                return;
            }
            
            const docIds = Array.from(checkboxes).map(cb => cb.value);
            const params = new URLSearchParams();
            docIds.forEach(id => params.append('ids[]', id));
            
            window.location.href = `export.php?${params.toString()}`;
        }
        
        // Delete selected documents with math confirmation
        function deleteSelected() {
            const checkboxes = document.querySelectorAll('.document-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Pilih minimal satu dokumen untuk dihapus!');
                return;
            }
            
            // Generate random math question
            const num1 = Math.floor(Math.random() * 10) + 1;
            const num2 = Math.floor(Math.random() * 10) + 1;
            const correctAnswer = num1 + num2;
            deleteCorrectAnswer = correctAnswer;
            deleteSelectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
            
            const questionText = `
                Anda akan menghapus <strong>${checkboxes.length}</strong> dokumen.<br>
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
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: deleteSingleId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Dokumen berhasil dihapus');
                        if (deleteModalInstance) {
                            deleteModalInstance.hide();
                        }
                        location.reload();
                    } else {
                        alert('Gagal menghapus dokumen: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus dokumen');
                });
                return;
            }
            
            // Handle multiple delete
            if (!deleteSelectedIds.length) {
                alert('Tidak ada dokumen yang dipilih.');
                return;
            }
            
            fetch('delete_multiple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids: deleteSelectedIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Berhasil menghapus ${data.deleted_count} dokumen`);
                    if (deleteModalInstance) {
                        deleteModalInstance.hide();
                    }
                    location.reload();
                } else {
                    alert('Gagal menghapus dokumen: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus dokumen');
            });
        }
        
        // Delete all documents with 2 questions confirmation (step by step)
        function deleteAllDocuments() {
            // Reset to step 1
            deleteAllCurrentStep = 1;
            
            // Generate 2 random math questions
            const num1_1 = Math.floor(Math.random() * 10) + 1;
            const num2_1 = Math.floor(Math.random() * 10) + 1;
            const correctAnswer1 = num1_1 + num2_1;
            
            const num1_2 = Math.floor(Math.random() * 10) + 1;
            const num2_2 = Math.floor(Math.random() * 10) + 1;
            const correctAnswer2 = num1_2 + num2_2;
            
            deleteAllAnswer1 = correctAnswer1;
            deleteAllAnswer2 = correctAnswer2;
            
            // Store question data
            deleteAllQuestion1Data = { num1: num1_1, num2: num2_1, answer: correctAnswer1 };
            deleteAllQuestion2Data = { num1: num1_2, num2: num2_2, answer: correctAnswer2 };
            
            // Get total documents count
            const totalDocs = <?php echo $total_records; ?>;
            document.getElementById('deleteAllTotalDocs').textContent = totalDocs;
            
            // Show question 1
            showDeleteAllQuestion(1);
            
            // Reset UI
            document.getElementById('deleteAllAnswer').value = '';
            document.getElementById('deleteAllAnswer').placeholder = 'Masukkan hasil penjumlahan';
            document.getElementById('deleteAllCheckBtn').style.display = 'inline-block';
            document.getElementById('deleteAllNextBtn').style.display = 'none';
            
            if (deleteAllModalInstance) {
                deleteAllModalInstance.show();
            }
        }
        
        function showDeleteAllQuestion(step) {
            const container = document.getElementById('deleteAllQuestionContainer');
            let questionData, questionText;
            
            if (step === 1) {
                questionData = deleteAllQuestion1Data;
                questionText = `
                    <div class="mb-3">
                        <strong>Pertanyaan 1 dari 2:</strong><br>
                        <span class="fw-bold fs-5">${questionData.num1} + ${questionData.num2} = ?</span>
                    </div>
                `;
            } else {
                questionData = deleteAllQuestion2Data;
                questionText = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> Pertanyaan 1 benar!
                    </div>
                    <div class="mb-3">
                        <strong>Pertanyaan 2 dari 2:</strong><br>
                        <span class="fw-bold fs-5">${questionData.num1} + ${questionData.num2} = ?</span>
                    </div>
                `;
            }
            
            container.innerHTML = questionText;
            document.getElementById('deleteAllAnswer').value = '';
            document.getElementById('deleteAllAnswer').focus();
        }
        
        function checkDeleteAllAnswer() {
            const answer = parseInt(document.getElementById('deleteAllAnswer').value, 10);
            
            if (isNaN(answer)) {
                alert('Masukkan jawaban yang valid!');
                return;
            }
            
            if (deleteAllCurrentStep === 1) {
                // Check question 1
                if (answer !== deleteAllAnswer1) {
                    alert('Jawaban salah! Silakan coba lagi.');
                    document.getElementById('deleteAllAnswer').value = '';
                    document.getElementById('deleteAllAnswer').focus();
                    return;
                }
                
                // Question 1 correct, move to question 2
                deleteAllCurrentStep = 2;
                showDeleteAllQuestion(2);
                document.getElementById('deleteAllCheckBtn').style.display = 'none';
                document.getElementById('deleteAllNextBtn').style.display = 'inline-block';
                
            } else if (deleteAllCurrentStep === 2) {
                // Check question 2
                if (answer !== deleteAllAnswer2) {
                    alert('Jawaban salah! Silakan coba lagi.');
                    document.getElementById('deleteAllAnswer').value = '';
                    document.getElementById('deleteAllAnswer').focus();
                    return;
                }
                
                // Both questions correct, show success and proceed to final confirmation
                const container = document.getElementById('deleteAllQuestionContainer');
                container.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> Pertanyaan 2 benar! Kedua pertanyaan telah dijawab dengan benar.
                    </div>
                `;
                document.getElementById('deleteAllAnswer').style.display = 'none';
                document.getElementById('deleteAllCheckBtn').style.display = 'none';
                document.getElementById('deleteAllNextBtn').style.display = 'inline-block';
            }
        }
        
        function proceedToFinalDelete() {
            // Confirm again with user
            if (!confirm('PERINGATAN: Anda akan menghapus SEMUA dokumen secara permanen. Tindakan ini TIDAK DAPAT DIBATALKAN. Apakah Anda yakin?')) {
                return;
            }
            
            // Proceed with delete all
            fetch('delete_all.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ confirm: true })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Berhasil menghapus ${data.deleted_count} dokumen`);
                    if (deleteAllModalInstance) {
                        deleteAllModalInstance.hide();
                    }
                    location.reload();
                } else {
                    alert('Gagal menghapus dokumen: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus dokumen');
            });
        }
    </script>
    
    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="fas fa-upload me-2"></i>
                        Import Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="import.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="import_file" class="form-label">Pilih File Excel/CSV</label>
                            <input type="file" class="form-control" id="import_file" name="import_file" 
                                   accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">
                                Format file yang didukung: .xlsx, .xls, .csv
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
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check_duplicate" name="check_duplicate" value="1">
                                <label class="form-check-label" for="check_duplicate">
                                    <strong>Cek duplikat sebelum import</strong>
                                </label>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Default: TIDAK dicek</strong> - Semua dokumen akan diimport tanpa pengecekan duplikat. 
                                    Centang ini hanya jika Anda ingin sistem menolak dokumen yang sudah ada di database berdasarkan NIK atau Passport.
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Catatan:</strong> Disarankan file Excel/CSV memiliki kolom: 
                            Nama Lengkap, NIK, No Passport, Kode Lemari, Dokumen Berasal, Kategori (WNI/WNA), <strong>Tahun</strong>
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
                        <i class="fas fa-search-plus"></i> Pencarian Lanjutan Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="advancedSearchForm">
                        <div class="row g-3">
                            <!-- Nama Lengkap -->
                            <div class="col-md-6">
                                <label for="search_full_name" class="form-label">
                                    <i class="fas fa-user text-primary"></i> Nama Lengkap
                                </label>
                                <input type="text" class="form-control" id="search_full_name" name="full_name" 
                                       placeholder="Masukkan nama lengkap...">
                                <div class="form-text">Cari berdasarkan nama lengkap pemilik dokumen</div>
                            </div>
                            
                            <!-- Tanggal Lahir -->
                            <div class="col-md-6">
                                <label for="search_birth_date" class="form-label">
                                    <i class="fas fa-calendar text-success"></i> Tanggal Lahir
                                </label>
                                <input type="date" class="form-control" id="search_birth_date" name="birth_date">
                                <div class="form-text">Cari berdasarkan tanggal lahir</div>
                            </div>
                            
                            <!-- Nomor Paspor -->
                            <div class="col-md-6">
                                <label for="search_passport" class="form-label">
                                    <i class="fas fa-passport text-info"></i> Nomor Paspor
                                </label>
                                <input type="text" class="form-control" id="search_passport" name="passport_number" 
                                       placeholder="Masukkan nomor paspor...">
                                <div class="form-text">Cari berdasarkan nomor paspor</div>
                            </div>
                            
                        </div>
                        
                        <!-- Search Tips -->
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-lightbulb"></i> Tips Pencarian:</h6>
                            <ul class="mb-0">
                                <li>Anda dapat mengisi satu atau lebih field untuk pencarian yang lebih spesifik</li>
                                <li>Kombinasi beberapa field akan memberikan hasil yang lebih akurat</li>
                                <li>Kosongkan field yang tidak ingin digunakan dalam pencarian</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" onclick="performAdvancedSearch()">
                        <i class="fas fa-search"></i> Cari Dokumen
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
    
    <!-- Delete All Confirmation Modal -->
    <div class="modal fade" id="deleteAllConfirmModal" tabindex="-1" aria-labelledby="deleteAllConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #dc3545 !important; color: #ffffff !important;">
                    <h5 class="modal-title" id="deleteAllConfirmModalLabel" style="color: #ffffff !important;">
                        <i class="fas fa-exclamation-triangle me-2" style="color: #ffffff !important;"></i>
                        <span style="color: #ffffff !important;">Konfirmasi Hapus Semua Dokumen</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>PERINGATAN!</strong> Anda akan menghapus <strong id="deleteAllTotalDocs">0</strong> dokumen secara permanen. Tindakan ini TIDAK DAPAT DIBATALKAN!
                    </div>
                    <p class="mb-3">Untuk konfirmasi, jawab 2 pertanyaan berikut secara berurutan:</p>
                    
                    <div id="deleteAllQuestionContainer" class="mb-3">
                        <!-- Questions will be displayed here step by step -->
                    </div>
                    
                    <div class="mb-3">
                        <label for="deleteAllAnswer" class="form-label">Jawaban Anda</label>
                        <input type="number" class="form-control" id="deleteAllAnswer" 
                               placeholder="Masukkan hasil penjumlahan"
                               onkeypress="if(event.key === 'Enter') { checkDeleteAllAnswer(); }">
                        <small class="text-muted">Tekan Enter atau klik tombol untuk melanjutkan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="deleteAllCheckBtn" onclick="checkDeleteAllAnswer()">
                        <i class="fas fa-check me-1"></i> Periksa Jawaban
                    </button>
                    <button type="button" class="btn btn-danger" id="deleteAllNextBtn" onclick="proceedToFinalDelete()" style="display: none;">
                        <i class="fas fa-trash-alt me-1"></i> Hapus Semua Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
