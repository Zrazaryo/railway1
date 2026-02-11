<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$error_message = '';
$locker = null;
$documents = [];

// Parameter "code" bisa berupa nama rak (misal: A.01, A.02) atau kode lemari (A, B)
if (!isset($_GET['code']) || empty($_GET['code'])) {
    header('Location: ../documents/pemusnahan_lockers.php?error=' . urlencode('Kode lemari tidak ditemukan.'));
    exit();
}

$locker_code = sanitize_input($_GET['code']); // nama rak atau kode lemari
$search = sanitize_input($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$origin_filter = $_GET['origin'] ?? '';
$sort_param = $_GET['sort'] ?? 'created_at_desc';
$advanced_full_name = sanitize_input($_GET['advanced_full_name'] ?? '');
$advanced_birth_date = sanitize_input($_GET['advanced_birth_date'] ?? '');
$advanced_passport = sanitize_input($_GET['advanced_passport'] ?? '');
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : null;

// Tentukan kolom urut
$sort_by = 'document_order_number';
$sort_order = 'ASC';
if ($sort_param === 'created_at_desc') {
    $sort_by = 'created_at';
    $sort_order = 'DESC';
} elseif ($sort_param === 'created_at_asc') {
    $sort_by = 'created_at';
    $sort_order = 'ASC';
} elseif ($sort_param === 'full_name_asc') {
    $sort_by = 'full_name';
    $sort_order = 'ASC';
}

try {
    // Ambil detail lemari berdasarkan name (rak) atau code (kode lemari)
    // Jika lemari sudah dihapus, buat objek locker virtual dari dokumen deleted
    $locker = $db->fetch("SELECT id, code, name, max_capacity FROM lockers WHERE name = ? OR code = ? LIMIT 1", [$locker_code, $locker_code]);

    if (!$locker) {
        // Lemari tidak ada di tabel lockers (sudah dihapus), buat objek virtual
        // Cek apakah ada dokumen deleted dengan month_number ini
        $doc_check = $db->fetch("SELECT COUNT(*) AS cnt FROM documents WHERE month_number = ? AND status = 'deleted' LIMIT 1", [$locker_code]);
        if (!$doc_check || (int)$doc_check['cnt'] === 0) {
            throw new Exception('Lemari dengan kode "' . e($locker_code) . '" tidak ditemukan.');
        }
        
        // Extract kode lemari dari month_number
        $code = '';
        if (preg_match('/^([A-Z])(\d)\./', $locker_code, $matches)) {
            $code = $matches[1] . $matches[2]; // A1, B3
        } elseif (preg_match('/^([A-Z])\./', $locker_code, $matches)) {
            $code = $matches[1]; // A, B (format lama)
        } else {
            $code = substr($locker_code, 0, 1);
        }
        
        // Buat objek locker virtual
        $locker = [
            'id' => null,
            'code' => $code,
            'name' => $locker_code,
            'max_capacity' => 600 // Default capacity
        ];
    }

    // Ambil dokumen yang ada di rak ini - HANYA untuk dokumen pemusnahan (status deleted)
    // month_number sekarang diisi dengan nama/kode rak (contoh: A.01)
    $where_conditions = ["d.month_number = ?"];
    $params = [$locker_code];

    // Status dokumen: HANYA deleted untuk pemusnahan
    $where_conditions[] = "d.status = ?";
    $params[] = 'deleted';

    // Filter tahun (wajib untuk pemusnahan per tahun)
    if ($year_filter) {
        $where_conditions[] = "d.document_year = ?";
        $params[] = $year_filter;
    }

    if (!empty($search)) {
        $where_conditions[] = "(d.full_name LIKE ? OR d.nik LIKE ? OR d.passport_number LIKE ? OR d.document_number LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($advanced_full_name)) {
        $where_conditions[] = "d.full_name LIKE ?";
        $params[] = '%' . $advanced_full_name . '%';
    }

    if (!empty($advanced_passport)) {
        $where_conditions[] = "d.passport_number LIKE ?";
        $params[] = '%' . $advanced_passport . '%';
    }

    if (!empty($advanced_birth_date)) {
        $date_obj = DateTime::createFromFormat('d/m/Y', $advanced_birth_date);
        if ($date_obj) {
            $where_conditions[] = "d.birth_date = ?";
            $params[] = $date_obj->format('Y-m-d');
        }
    }

    if (!empty($category_filter) && in_array($category_filter, ['WNA', 'WNI'])) {
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

    $where_clause = implode(' AND ', $where_conditions);

    $sql_documents = "
        SELECT 
            d.id, d.document_number, d.full_name, d.nik, d.passport_number,
            d.document_order_number, d.document_year, d.citizen_category, d.document_origin,
            d.created_at,
            u.full_name as created_by_name
        FROM documents d
        LEFT JOIN users u ON d.created_by = u.id
        WHERE $where_clause
        ORDER BY d.$sort_by $sort_order
    ";
    $documents = $db->fetchAll($sql_documents, $params);

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lemari Pemusnahan <?php echo e($locker['name'] ?? ''); ?> - Sistem Arsip Dokumen</title>
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
                        <h1 class="h2">Detail Rak Pemusnahan: <?php echo e($locker['name'] ?? '-'); ?> (Lemari <?php echo e($locker['code'] ?? '-'); ?>)</h1>
                        <?php if ($year_filter): ?>
                            <small class="text-muted">Tahun dokumen: <?php echo htmlspecialchars($year_filter); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../documents/pemusnahan_lockers.php<?php echo $year_filter ? '?year=' . urlencode($year_filter) : ''; ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2" title="Kembali">
                            <i class="fas fa-arrow-left"></i>
                            <span>Kembali</span>
                        </a>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo e($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$error_message): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <form method="GET" id="searchForm" class="row g-3 align-items-center">
                                <input type="hidden" name="code" value="<?php echo e($locker_code); ?>">
                                <?php if ($year_filter): ?>
                                    <input type="hidden" name="year" value="<?php echo e($year_filter); ?>">
                                <?php endif; ?>
                                <input type="hidden" name="advanced_full_name" id="advanced_full_name" value="<?php echo e($advanced_full_name); ?>">
                                <input type="hidden" name="advanced_birth_date" id="advanced_birth_date" value="<?php echo e($advanced_birth_date); ?>">
                                <input type="hidden" name="advanced_passport" id="advanced_passport" value="<?php echo e($advanced_passport); ?>">
                                
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

                                <div class="col-md-2">
                                    <select class="form-select" name="origin" onchange="document.getElementById('searchForm').submit();">
                                        <option value="">Semua Asal Dokumen</option>
                                        <option value="imigrasi_lounge_senayan_city" <?php echo $origin_filter === 'imigrasi_lounge_senayan_city' ? 'selected' : ''; ?>>Imigrasi Lounge Senayan City</option>
                                        <option value="imigrasi_ulp_semanggi" <?php echo $origin_filter === 'imigrasi_ulp_semanggi' ? 'selected' : ''; ?>>Imigrasi ULP Semanggi</option>
                                        <option value="imigrasi_jakarta_pusat_kemayoran" <?php echo $origin_filter === 'imigrasi_jakarta_pusat_kemayoran' ? 'selected' : ''; ?>>Imigrasi Jakarta Pusat Kemayoran</option>
                                    </select>
                                </div>

                                <div class="col-md-1">
                                    <button type="button" class="btn btn-primary w-100" title="Muat Ulang" onclick="location.href='detail_pemusnahan.php?code=<?php echo urlencode($locker_code); ?><?php echo $year_filter ? '&year=' . urlencode($year_filter) : ''; ?>'">
                                        <i class="fas fa-rotate-right"></i>
                                    </button>
                                </div>

                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2" data-bs-toggle="modal" data-bs-target="#advancedSearchModal" title="Pencarian Lanjutan">
                                        <span class="fw-semibold">Pencarian Lanjutan</span>
                                        <i class="fas fa-search-plus fa-lg"></i>
                                    </button>
                                </div>
                            </form>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        Menampilkan <?php echo number_format(count($documents)); ?> dokumen pemusnahan
                                        <?php if (!empty($search) || !empty($category_filter)): ?>
                                            dari hasil pencarian
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <?php if (is_admin()): ?>
                                <div class="col-md-6 text-end">
                                    <a href="../documents/add_pemusnahan.php?locker=<?php echo urlencode($locker['name']); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i> Tambah Dokumen Pemusnahan
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                        <i class="fas fa-trash"></i> Hapus Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteAllDocuments()" title="Hapus semua dokumen pemusnahan di rak ini secara permanen">
                                        <i class="fas fa-trash-alt"></i> Hapus Semua
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" onclick="exportSelected()">
                                        <i class="fas fa-download"></i> Export Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="exportAllCurrent()">
                                        <i class="fas fa-download"></i> Export Semua
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informasi Rak Pemusnahan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Kode Lemari:</strong> <?php echo e($locker['code']); ?></p>
                                    <p><strong>Nama Rak:</strong> <?php echo e($locker['name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Kapasitas Maksimal:</strong> <?php echo number_format($locker['max_capacity']); ?> dokumen</p>
                                    <p><strong>Jumlah Dokumen Pemusnahan:</strong> <?php echo number_format(count($documents)); ?> dokumen</p>
                                    <?php if ($year_filter): ?>
                                        <p><strong>Tahun Dokumen:</strong> <?php echo htmlspecialchars($year_filter); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i> Dokumen Pemusnahan di Lemari Ini</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <?php if (is_admin()): ?>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" title="Pilih Semua">
                                            </th>
                                            <?php endif; ?>
                                            <th style="width: 40px;">No</th>
                                            <th style="width: 150px;">Nama Lengkap</th>
                                            <th style="width: 100px;">NIK</th>
                                            <th style="width: 100px;">No Passport</th>
                                            <th style="width: 120px;">Urutan Dokumen</th>
                                            <th style="width: 120px;">Kode Dokumen</th>
                                            <th style="width: 100px;">Tahun Dokumen</th>
                                            <th style="width: 150px;">Dokumen Berasal</th>
                                            <th style="width: 80px;">Kategori</th>
                                            <th style="width: 100px;">Di Buat Oleh</th>
                                            <th style="width: 100px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($documents)): ?>
                                            <tr>
                                                <td colspan="<?php echo is_admin() ? '12' : '11'; ?>" class="text-center py-4">
                                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">Tidak ada dokumen pemusnahan di lemari ini.</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $no = 1; foreach ($documents as $doc): ?>
                                                <tr>
                                                    <?php if (is_admin()): ?>
                                                    <td>
                                                        <input type="checkbox" class="document-checkbox" value="<?php echo $doc['id']; ?>">
                                                    </td>
                                                    <?php endif; ?>
                                                    <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                                    <td class="fw-semibold"><?php echo e($doc['full_name'] ?? '-'); ?></td>
                                                    <td><?php echo e($doc['nik'] ?? '-'); ?></td>
                                                    <td><?php echo e($doc['passport_number'] ?? '-'); ?></td>
                                                    <td><?php echo e($doc['document_order_number'] ?? '-'); ?></td>
                                                    <td><?php echo e(($locker['name'] ?? '-') . '.' . ($doc['document_order_number'] ?? '-')); ?></td>
                                                    <td><?php echo e($doc['document_year'] ?? '-'); ?></td>
                                                    <td><?php echo e(format_document_origin_label($doc['document_origin'] ?? '')); ?></td>
                                                    <td><span class="badge bg-primary"><?php echo e($doc['citizen_category'] ?? 'WNI'); ?></span></td>
                                                    <td><?php echo e($doc['created_by_name'] ?? '-'); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewDocument(<?php echo $doc['id']; ?>)" 
                                                                title="Lihat">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                            <?php if (is_admin()): ?>
                                                            <a href="../documents/edit.php?id=<?php echo $doc['id']; ?>&return=pemusnahan&code=<?php echo urlencode($locker_code); ?><?php echo $year_filter ? '&year=' . urlencode($year_filter) : ''; ?>" 
                                                               class="btn btn-sm btn-outline-warning" 
                                                               title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php endif; ?>
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
                <?php endif; ?>
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
                    <button type="button" class="btn btn-primary" id="downloadBtn">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Search Modal -->
    <div class="modal fade" id="advancedSearchModal" tabindex="-1" aria-labelledby="advancedSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="advancedSearchModalLabel">
                        <i class="fas fa-search-plus me-2"></i> Pencarian Lanjutan Dokumen Pemusnahan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fas fa-user me-2"></i>Nama Lengkap</label>
                            <input type="text" class="form-control" id="modalFullName" placeholder="Masukkan nama lengkap...">
                            <small class="text-muted">Cari berdasarkan nama lengkap pemilik dokumen</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fas fa-calendar-alt me-2"></i>Tanggal Lahir</label>
                            <input type="date" class="form-control" id="modalBirthDate">
                            <small class="text-muted">Pilih tanggal lahir dari kalender</small>
                        </div>
                    </div>
                    <div class="row g-4 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fas fa-passport me-2"></i>Nomor Paspor</label>
                            <input type="text" class="form-control" id="modalPassport" placeholder="Masukkan nomor paspor...">
                            <small class="text-muted">Cari berdasarkan nomor paspor</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="performAdvancedSearch()">
                        <i class="fas fa-search"></i> Cari Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        // View document function
        document.addEventListener('DOMContentLoaded', () => {
            // Prefill modal fields from current filter values
            document.getElementById('modalFullName').value = "<?php echo e($advanced_full_name); ?>";
            
            // Convert birth date from dd/mm/yyyy to yyyy-mm-dd for date input
            const birthDateValue = "<?php echo e($advanced_birth_date); ?>";
            if (birthDateValue) {
                const parts = birthDateValue.split('/');
                if (parts.length === 3) {
                    const formattedDate = parts[2] + '-' + parts[1].padStart(2, '0') + '-' + parts[0].padStart(2, '0');
                    document.getElementById('modalBirthDate').value = formattedDate;
                }
            }
            
            document.getElementById('modalPassport').value = "<?php echo e($advanced_passport); ?>";
        });

        function viewDocument(id) {
            fetch(`../documents/view.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('documentDetails').innerHTML = data.html;
                        document.getElementById('downloadBtn').onclick = () => {
                            window.open(`../documents/download.php?id=${id}`, '_blank');
                        };
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

        function performAdvancedSearch() {
            const fullName = document.getElementById('modalFullName').value.trim();
            const birthDateInput = document.getElementById('modalBirthDate').value.trim();
            const passport = document.getElementById('modalPassport').value.trim();

            // Convert birth date from yyyy-mm-dd to dd/mm/yyyy
            let birthDate = '';
            if (birthDateInput) {
                const parts = birthDateInput.split('-');
                if (parts.length === 3) {
                    birthDate = parts[2] + '/' + parts[1] + '/' + parts[0];
                }
            }

            document.getElementById('advanced_full_name').value = fullName;
            document.getElementById('advanced_birth_date').value = birthDate;
            document.getElementById('advanced_passport').value = passport;

            const modalEl = document.getElementById('advancedSearchModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }

            document.getElementById('searchForm').submit();
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.document-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }

        function getSelectedDocumentIds() {
            return Array.from(document.querySelectorAll('.document-checkbox:checked'))
                .map(cb => parseInt(cb.value, 10))
                .filter(id => !isNaN(id));
        }

        function exportSelected() {
            const ids = getSelectedDocumentIds();
            if (!ids.length) {
                alert('Pilih minimal satu dokumen untuk di-export!');
                return;
            }
            const params = new URLSearchParams();
            ids.forEach(id => params.append('ids[]', id));
            window.location.href = `../documents/export_pemusnahan.php?${params.toString()}`;
        }

        function exportAllCurrent() {
            const allIds = Array.from(document.querySelectorAll('.document-checkbox'))
                .map(cb => parseInt(cb.value, 10))
                .filter(id => !isNaN(id));

            if (!allIds.length) {
                alert('Tidak ada dokumen untuk di-export.');
                return;
            }

            const params = new URLSearchParams();
            allIds.forEach(id => params.append('ids[]', id));
            window.location.href = `../documents/export_pemusnahan.php?${params.toString()}`;
        }

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

        function deleteSelected() {
            const ids = getSelectedDocumentIds();
            if (!ids.length) {
                alert('Pilih minimal satu dokumen yang akan dihapus.');
                return;
            }
            
            // Generate random math question
            const num1 = Math.floor(Math.random() * 10) + 1;
            const num2 = Math.floor(Math.random() * 10) + 1;
            const correctAnswer = num1 + num2;
            deleteCorrectAnswer = correctAnswer;
            deleteSelectedIds = ids.map(id => parseInt(id));
            
            const questionText = `
                Anda akan menghapus <strong>${ids.length}</strong> dokumen pemusnahan secara permanen.<br>
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
            
            fetch('../documents/delete_multiple.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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
                    alert(data.message || 'Gagal menghapus dokumen');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus dokumen');
            });
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
            // Check if there are any documents to delete
            const allDocuments = document.querySelectorAll('.document-checkbox');
            if (allDocuments.length === 0) {
                alert('Tidak ada dokumen pemusnahan di rak ini untuk dihapus.');
                return;
            }

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
            
            // Get all document IDs in this locker
            const allDocumentIds = Array.from(document.querySelectorAll('.document-checkbox'))
                .map(cb => parseInt(cb.value, 10))
                .filter(id => !isNaN(id));

            if (allDocumentIds.length === 0) {
                alert('Tidak ada dokumen untuk dihapus.');
                return;
            }
            
            // Show loading state
            const confirmBtn = event.target;
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menghapus...';
            confirmBtn.disabled = true;
            
            // Send delete request
            fetch('../documents/delete_multiple.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: allDocumentIds })
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
                        <i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Hapus Semua Dokumen Pemusnahan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>PERINGATAN!</strong> Anda akan menghapus SEMUA dokumen pemusnahan di rak ini secara permanen. Tindakan ini tidak dapat dibatalkan!
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
                            Saya memahami bahwa semua dokumen pemusnahan di rak ini akan dihapus secara permanen
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

