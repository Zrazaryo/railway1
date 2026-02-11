<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

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
    $where_conditions[] = "(d.full_name LIKE ? OR d.nik LIKE ? OR d.passport_number LIKE ? OR d.document_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($category_filter) && in_array($category_filter, ['WNA', 'WNI'])) {
    $where_conditions[] = "d.citizen_category = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM documents d WHERE $where_clause";
$total_records = $db->fetch($count_sql, $params)['total'];

// Get documents
$sql = "SELECT d.id, d.document_number, d.full_name, d.nik, d.passport_number, d.month_number, d.citizen_category, d.document_origin, d.document_year,
               d.created_at, d.status, d.file_path, d.file_name, d.file_size, 
               u.full_name as created_by_name,
               l.name AS locker_name
        FROM documents d 
        LEFT JOIN users u ON d.created_by = u.id 
        LEFT JOIN lockers l ON d.month_number = l.code
        WHERE $where_clause 
        ORDER BY d.$sort_by $sort_order 
        LIMIT $limit OFFSET $offset";

$documents = $db->fetchAll($sql, $params);

// Categories are now hardcoded as WNA/WNI

// Calculate pagination
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Dokumen Keseluruhan - Sistem Arsip Dokumen</title>
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
                    <h1 class="h2 mb-0">Daftar Dokumen Keseluruhan</h1>
                    <div class="ms-auto">
                        <a href="../dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Halaman Utama
                        </a>
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
                <div class="search-filter-container">
                    <form method="GET" id="searchForm" class="row g-3">
                        <?php if (isset($_GET['mine']) && $_GET['mine'] == '1'): ?>
                            <input type="hidden" name="mine" value="1">
                        <?php endif; ?>
                        
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo e($search); ?>" placeholder="Cari dokumen..."
                                       onkeypress="if(event.key === 'Enter') { event.preventDefault(); document.getElementById('searchForm').submit(); }">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <select class="form-select" name="sort" onchange="document.getElementById('searchForm').submit();">
                                <option value="created_at_desc" <?php echo $sort_by == 'created_at' && $sort_order == 'DESC' ? 'selected' : ''; ?>>Dokumen Terbaru</option>
                                <option value="created_at_asc" <?php echo $sort_by == 'created_at' && $sort_order == 'ASC' ? 'selected' : ''; ?>>Dokumen Terlama</option>
                                <option value="full_name_asc" <?php echo $sort_by == 'full_name' && $sort_order == 'ASC' ? 'selected' : ''; ?>>Nama A-Z</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select class="form-select" name="category" onchange="document.getElementById('searchForm').submit();">
                                <option value="">Semua Kategori</option>
                                <option value="WNA" <?php echo $category_filter === 'WNA' ? 'selected' : ''; ?>>WNA</option>
                                <option value="WNI" <?php echo $category_filter === 'WNI' ? 'selected' : ''; ?>>WNI</option>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100" title="Cari">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#advancedSearchModal" title="Pencarian Lanjutan">
                                <span class="fw-semibold">Pencarian Lanjutan</span>
                                <i class="fas fa-search-plus"></i>
                            </button>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <a href="../lockers/select.php" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i>Lihat Lemari
                            </a>
                        </div>
                    </form>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="text-muted">
                                Menampilkan <?php echo number_format($total_records); ?> dokumen
                                <?php if (!empty($search) || !empty($category_filter)): ?>
                                    dari hasil pencarian
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php if (is_admin()): ?>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                <i class="fas fa-trash"></i> Hapus Terpilih
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
                                        <th style="width:50px">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" title="Pilih Semua">
                                        </th>
                                        <?php endif; ?>
                                        <th style="width:60px">No</th>
                                        <th>Nama Lengkap</th>
                                        <th>NIK</th>
                                        <th>No Passport</th>
                                        <th>Kode Lemari</th>
                                        <th>Nama Rak</th>
                                        <th>Tahun Dokumen</th>
                                        <th>Dokumen Berasal</th>
                                        <th>Kategori</th>
                                        <th>Di Buat Oleh</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($documents)): ?>
                                        <tr>
                                            <td colspan="<?php echo is_admin() ? '10' : '9'; ?>" class="text-center py-4">
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
                                                <td><?php echo e($doc['month_number'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['locker_name'] ?? '-'); ?></td>
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
                                                        <?php if (is_admin()): ?>
                                                        <a href="edit.php?id=<?php echo $doc['id']; ?>" 
                                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($doc['status'] !== 'deleted'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="deleteDocument(<?php echo $doc['id']; ?>)" 
                                                                    title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
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
        let deleteCorrectAnswer = 0;
        let deleteSelectedIds = [];

        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('deleteConfirmModal');
            if (modalEl) {
                deleteModalInstance = new bootstrap.Modal(modalEl);
                modalEl.addEventListener('hidden.bs.modal', () => {
                    document.getElementById('deleteQuestion').innerHTML = '';
                    document.getElementById('deleteAnswer').value = '';
                    deleteSelectedIds = [];
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
        
        // Delete document function
        function deleteDocument(id) {
            if (confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Dokumen berhasil dihapus');
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
</body>
</html>
