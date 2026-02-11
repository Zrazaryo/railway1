<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$error_message = '';
$locker = null;
$documents = [];

if (!isset($_GET['code']) || empty($_GET['code'])) {
    header('Location: select.php?error=' . urlencode('Kode lemari tidak ditemukan.'));
    exit();
}

$locker_code = sanitize_input($_GET['code']);
$search = sanitize_input($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$origin_filter = $_GET['origin'] ?? '';
$sort_param = $_GET['sort'] ?? 'created_at_desc';
$advanced_full_name = sanitize_input($_GET['advanced_full_name'] ?? '');
$advanced_birth_date = sanitize_input($_GET['advanced_birth_date'] ?? '');
$advanced_passport = sanitize_input($_GET['advanced_passport'] ?? '');

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
    // Ambil detail lemari
    $locker = $db->fetch("SELECT id, code, name, max_capacity FROM lockers WHERE code = ?", [$locker_code]);

    if (!$locker) {
        throw new Exception('Lemari dengan kode "' . e($locker_code) . '" tidak ditemukan.');
    }

    // Ambil dokumen yang ada di lemari ini
    $where_conditions = ["d.month_number = ?", "d.status = 'active'"];
    $params = [$locker_code];

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
            d.document_order_number, d.citizen_category, d.document_origin,
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
    <title>Detail Lemari <?php echo e($locker['name'] ?? ''); ?> - Sistem Arsip Dokumen</title>
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
                    <h1 class="h2">Detail Lemari: <?php echo e($locker['name'] ?? '-'); ?> (<?php echo e($locker['code'] ?? '-'); ?>)</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" onclick="history.back()" title="Kembali">
                            <i class="fas fa-arrow-left"></i>
                            <span>Kembali</span>
                        </button>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <form method="GET" id="searchForm" class="row g-3 align-items-center">
                                <input type="hidden" name="code" value="<?php echo e($locker['code']); ?>">
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
                                    <button type="submit" class="btn btn-primary w-100" title="Muat Ulang">
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
                                        Menampilkan <?php echo number_format(count($documents)); ?> dokumen
                                        <?php if (!empty($search) || !empty($category_filter)): ?>
                                            dari hasil pencarian
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <?php if (is_admin()): ?>
                                <div class="col-md-6 text-end">
                                    <a href="../documents/add.php?locker=<?php echo urlencode($locker['code']); ?>" class="btn btn-sm btn-primary me-2">
                                        <i class="fas fa-plus"></i> Buat Dokumen
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                        <i class="fas fa-trash"></i> Hapus Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" onclick="exportSelected()">
                                        <i class="fas fa-download"></i> Export Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="exportAllCurrent()">
                                        <i class="fas fa-download"></i> Export Semua
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="window.location.href='../documents/import.php';">
                                        <i class="fas fa-upload"></i> Import
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informasi Lemari</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Kode Lemari:</strong> <?php echo e(substr($locker['code'], 0, 1)); ?></p>
                                    <p><strong>Nama Lemari:</strong> <?php echo e($locker['name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Kapasitas Maksimal:</strong> <?php echo number_format($locker['max_capacity']); ?> dokumen</p>
                                    <p><strong>Jumlah Terpakai:</strong> <?php echo number_format(count($documents)); ?> dokumen</p>
                                    <p><strong>Sisa Kapasitas:</strong> <?php echo number_format($locker['max_capacity'] - count($documents)); ?> dokumen</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i> Dokumen di Lemari Ini</h5>
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
                                            <th style="width: 150px;">Dokumen Berasal</th>
                                            <th style="width: 80px;">Kategori</th>
                                            <th style="width: 100px;">Di Buat Oleh</th>
                                            <th style="width: 100px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($documents)): ?>
                                            <tr>
                                                <td colspan="<?php echo is_admin() ? '11' : '10'; ?>" class="text-center py-4">
                                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">Tidak ada dokumen di lemari ini.</p>
                                                    <a href="../documents/add.php?locker=<?php echo urlencode($locker['code']); ?>" class="btn btn-sm btn-primary mt-2">
                                                        <i class="fas fa-plus"></i> Tambah Dokumen
                                                    </a>
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
                                                    <td><?php echo e(format_document_origin_label($doc['document_origin'] ?? '')); ?></td>
                                                    <td><span class="badge bg-primary"><?php echo e($doc['citizen_category'] ?? 'WNI'); ?></span></td>
                                                    <td><?php echo e($doc['created_by_name'] ?? '-'); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewDocument(<?php echo $doc['id']; ?>)" 
                                                                title="Lihat">
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
                        <i class="fas fa-search-plus me-2"></i> Pencarian Lanjutan Dokumen
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
        // View document function (copied from documents/index.php and adjusted path)
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

        function exportSelected() {
            const ids = getSelectedDocumentIds();
            if (!ids.length) {
                alert('Pilih minimal satu dokumen untuk di-export!');
                return;
            }
            const params = new URLSearchParams();
            ids.forEach(id => params.append('ids[]', id));
            window.location.href = `../documents/export.php?${params.toString()}`;
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
            window.location.href = `../documents/export.php?${params.toString()}`;
        }

        function deleteSelected() {
            const ids = getSelectedDocumentIds();
            if (!ids.length) {
                alert('Pilih minimal satu dokumen untuk dihapus!');
                return;
            }

            if (!confirm(`Hapus ${ids.length} dokumen terpilih?`)) {
                return;
            }

            fetch('../documents/delete_multiple.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Berhasil menghapus ${data.deleted_count} dokumen`);
                        location.reload();
                    } else {
                        alert('Gagal menghapus dokumen: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus dokumen');
                });
        }
    </script>
</body>
</html>
