<?php
// Load functions first untuk init_multi_session
require_once '../includes/functions.php';

// Inisialisasi session dengan dukungan multi-tab
init_multi_session();

require_once '../config/database.php';

// Cek login dan role staff
require_login();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
    header('Location: ../index.php' . $tab_param);
    exit();
}

// Search and Filter parameters
$search = sanitize_input($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$sort_param = $_GET['sort'] ?? 'created_at_desc';

// Parse sort parameter
if ($sort_param === 'created_at_desc' || $sort_param === 'created_at') {
    $sort_by = 'd.created_at';
    $sort_order = 'DESC';
} elseif ($sort_param === 'created_at_asc') {
    $sort_by = 'd.created_at';
    $sort_order = 'ASC';
} elseif ($sort_param === 'name' || $sort_param === 'name_asc' || $sort_param === 'full_name') {
    $sort_by = 'd.full_name';
    $sort_order = 'ASC';
} else {
    $sort_by = 'd.created_at';
    $sort_order = 'DESC';
}

// Build query conditions
$where_conditions = ["d.status = 'active'"];
$params = [];

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

// Ambil data statistik untuk staff
try {
    // Total dokumen yang bisa diakses staff (dengan filter)
    $total_documents_sql = "SELECT COUNT(*) as count FROM documents d WHERE $where_clause";
    $total_documents = $db->fetch($total_documents_sql, $params)['count'];
    
    // Dokumen yang dibuat oleh staff ini
    $my_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE created_by = ? AND status = 'active'", [$_SESSION['user_id']])['count'];
    
} catch (Exception $e) {
    $error_message = "Terjadi kesalahan saat mengambil data dashboard";
    $total_documents = 0;
    $my_documents = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Staff - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar konsisten -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar konsisten -->
            <?php include '../includes/sidebar.php'; ?>
            
            <!-- Main Content: Redesain -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="pt-3 pb-2 mb-3">
                    <h1 class="h3 fw-bold text-uppercase" style="letter-spacing:.5px">Dashboard Staff</h1>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo e($error_message); ?></div>
                <?php endif; ?>

                <!-- Cards -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 shadow-sm d-flex align-items-center justify-content-between" style="background:#e9ecff">
                            <div>
                                <div class="small text-muted fw-semibold">TOTAL DOKUMEN</div>
                                <div class="display-6 fw-bold"><?php echo number_format($total_documents); ?></div>
                            </div>
                            <div class="text-primary"><i class="fa-solid fa-user-shield fa-2x"></i></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 shadow-sm d-flex align-items-center justify-content-between" style="background:#e9fff1">
                            <div>
                                <div class="small text-muted fw-semibold">DOKUMEN SAYA</div>
                                <div class="display-6 fw-bold"><?php echo number_format($my_documents); ?></div>
                            </div>
                            <div class="text-success"><i class="fa-solid fa-user fa-2x"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="search-filter-container mb-4">
                    <form method="GET" id="searchForm" class="row g-3">
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
                                <option value="created_at_desc" <?php echo $sort_param === 'created_at_desc' || $sort_param === 'created_at' ? 'selected' : ''; ?>>Dokumen Terbaru</option>
                                <option value="created_at_asc" <?php echo $sort_param === 'created_at_asc' ? 'selected' : ''; ?>>Dokumen Terlama</option>
                                <option value="name" <?php echo $sort_param === 'name' || $sort_param === 'name_asc' || $sort_param === 'full_name' ? 'selected' : ''; ?>>Nama A-Z</option>
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
                    </form>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="text-muted">
                                Menampilkan <?php echo number_format($total_documents); ?> dokumen
                                <?php if (!empty($search) || !empty($category_filter)): ?>
                                    dari hasil pencarian
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <a class="btn btn-sm btn-primary" href="/PROJECT ARSIP LOKER/documents/add.php">
                                <i class="fa-solid fa-circle-plus me-1"></i>Tambahkan Dokumen
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tabel -->
                <?php
                try {
                    // Ambil semua dokumen aktif dengan filter
                    $rows_sql = "SELECT d.id, d.document_number, d.full_name, d.nik, d.passport_number, d.birth_date, d.month_number, d.citizen_category, 
                                        d.document_origin, d.created_at, 
                                        u.full_name as created_by_name,
                                        l.name AS locker_name
                     FROM documents d
                     LEFT JOIN users u ON d.created_by = u.id
                     LEFT JOIN lockers l ON d.month_number = l.code
                                 WHERE $where_clause
                                 ORDER BY $sort_by $sort_order";
                    
                    $rows = $db->fetchAll($rows_sql, $params);
                } catch (Exception $e) {
                    $error_message = "Terjadi kesalahan saat mengambil data dokumen";
                    $rows = [];
                }
                ?>
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle" id="staffDocsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">No</th>
                                    <th>Nama Lengkap</th>
                                    <th>NIK</th>
                                    <th>No Passport</th>
                                    <th>Kode Lemari</th>
                                    <th>Nama Lemari</th>
                                    <th>Dokumen Berasal</th>
                                    <th>Kategori</th>
                                    <th>Di Buat Oleh</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach ($rows as $r): ?>
                                <tr data-name="<?php echo e(strtolower($r['full_name'] ?? '')); ?>" 
                                    data-nik="<?php echo e($r['nik'] ?? ''); ?>" 
                                    data-passport="<?php echo e(strtolower($r['passport_number'] ?? '')); ?>"
                                    data-birth-date="<?php echo $r['birth_date'] ? date('Y-m-d', strtotime($r['birth_date'])) : ''; ?>">
                                    <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                    <td class="fw-semibold"><?php echo e($r['full_name'] ?? '-'); ?></td>
                                    <td><?php echo e($r['nik'] ?? '-'); ?></td>
                                    <td><?php echo e($r['passport_number'] ?? '-'); ?></td>
                                    <td><?php echo e($r['month_number'] ?? '-'); ?></td>
                                    <td><?php echo e($r['locker_name'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $originLabel = '-';
                                        if (!empty($r['document_origin'])) {
                                            switch ($r['document_origin']) {
                                                case 'imigrasi_jakarta_pusat_kemayoran':
                                                    $originLabel = 'Imigrasi Jakarta Pusat Kemayoran';
                                                    break;
                                                case 'imigrasi_ulp_semanggi':
                                                    $originLabel = 'Imigrasi ULP Semanggi';
                                                    break;
                                                case 'imigrasi_lounge_senayan_city':
                                                    $originLabel = 'Imigrasi Lounge Senayan City';
                                                    break;
                                                default:
                                                    $originLabel = $r['document_origin'];
                                            }
                                        }
                                        ?>
                                        <?php echo e($originLabel); ?>
                                    </td>
                                    <td><span class="badge bg-primary"><?php echo e($r['citizen_category'] ?? 'WNI'); ?></span></td>
                                    <td><?php echo e($r['created_by_name'] ?? '-'); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="viewDocument(<?php echo $r['id']; ?>)" 
                                                title="Lihat">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($rows)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada dokumen</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
                    <button type="button" class="btn btn-primary" id="downloadBtn">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        function sortBy(sortValue){
            const urlParams = new URLSearchParams(window.location.search);
            const tabId = urlParams.get('tab');
            
            // Build new URL with sort parameter
            let newUrl = window.location.pathname;
            const params = new URLSearchParams();
            
            if (sortValue) {
                params.set('sort', sortValue);
            }
            
                if (tabId) {
                params.set('tab', tabId);
                }
            
            const queryString = params.toString();
            if (queryString) {
                newUrl += '?' + queryString;
            }
            
            window.location.href = newUrl;
        }
        
        // Advanced search function
        function performAdvancedSearch() {
            const fullName = document.getElementById('search_full_name').value.trim().toLowerCase();
            const birthDate = document.getElementById('search_birth_date').value;
            const passportNumber = document.getElementById('search_passport').value.trim().toLowerCase();
            
            const rows = document.querySelectorAll('#staffDocsTable tbody tr');
            let visibleCount = 0;
            
            rows.forEach(tr => {
                let match = true;
                
                // Filter by full name
                if (fullName) {
                    const name = tr.dataset.name || '';
                    if (!name.includes(fullName)) {
                        match = false;
                    }
                }
                
                // Filter by birth date
                if (birthDate) {
                    const rowBirthDate = tr.dataset.birthDate || '';
                    if (rowBirthDate !== birthDate) {
                        match = false;
                    }
                }
                
                // Filter by passport number
                if (passportNumber) {
                    const passport = tr.dataset.passport || '';
                    if (!passport.includes(passportNumber)) {
                        match = false;
                    }
                }
                
                tr.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('advancedSearchModal'));
            if (modal) modal.hide();
            
            // Show result message
            if (visibleCount === 0) {
                alert('Tidak ada dokumen yang sesuai dengan kriteria pencarian.');
            } else {
                // Optional: show success message
                console.log(`Ditemukan ${visibleCount} dokumen`);
            }
        }
        
        // Reset advanced search
        function resetAdvancedSearch() {
            document.getElementById('search_full_name').value = '';
            document.getElementById('search_birth_date').value = '';
            document.getElementById('search_passport').value = '';
            
            // Show all rows
            document.querySelectorAll('#staffDocsTable tbody tr').forEach(tr => {
                tr.style.display = '';
            });
        }
        
        // View document function
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
    </script>
    <style>
        body { padding-top:56px; }
        .display-6 { font-size:2.5rem; }
    </style>
    
    <!-- Footer -->
    <footer class="text-center py-3 mt-4" style="border-top: 1px solid #E5E7EB; background-color: #f8f9fa;">
        <small style="color: #9CA3AF;">
            © 2026 SITARI (Sistem Tata Arsip Imigrasi Jakarta Pusat). Semua hak dilindungi.
        </small>
    </footer>
</body>
</html>
