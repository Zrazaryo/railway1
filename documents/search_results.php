<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role admin
require_admin();

// Ambil parameter pencarian
$search = sanitize_input($_GET['search'] ?? '');
$full_name = sanitize_input($_GET['full_name'] ?? '');
$birth_date = sanitize_input($_GET['birth_date'] ?? '');
$passport_number = sanitize_input($_GET['passport_number'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$date_from = sanitize_input($_GET['date_from'] ?? '');
$date_to = sanitize_input($_GET['date_to'] ?? '');

// Build query conditions
$where_conditions = ["d.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(d.title LIKE ? OR d.document_number LIKE ? OR d.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($full_name)) {
    $where_conditions[] = "d.full_name LIKE ?";
    $params[] = "%$full_name%";
}

if (!empty($birth_date)) {
    $where_conditions[] = "d.birth_date = ?";
    $params[] = $birth_date;
}

if (!empty($passport_number)) {
    $where_conditions[] = "d.passport_number LIKE ?";
    $params[] = "%$passport_number%";
}

if ($category_filter > 0) {
    $where_conditions[] = "d.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(d.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(d.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// Get search results
$sql = "SELECT d.*, dc.category_name, u.full_name as created_by_name 
        FROM documents d 
        LEFT JOIN document_categories dc ON d.category_id = dc.id 
        LEFT JOIN users u ON d.created_by = u.id 
        WHERE $where_clause 
        ORDER BY d.created_at DESC";

$search_results = $db->fetchAll($sql, $params);
$total_results = count($search_results);

// Ambil file_id pertama untuk setiap dokumen (untuk download)
foreach ($search_results as &$doc) {
    // Cari file dengan prioritas: file sebenarnya > STATUS_ONLY
    $file_sql = "SELECT id FROM document_files WHERE document_id = ? ORDER BY CASE WHEN file_path = 'STATUS_ONLY' THEN 1 ELSE 0 END ASC, id ASC LIMIT 1";
    $file = $db->fetch($file_sql, [$doc['id']]);
    $doc['first_file_id'] = $file ? $file['id'] : null;
}
unset($doc); // Unset reference

// Get categories for filter
$categories = $db->fetchAll("SELECT id, category_name FROM document_categories ORDER BY category_name");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian Dokumen - Sistem Arsip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .search-criteria {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .result-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            margin-bottom: 1.5rem;
        }
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .result-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        .criteria-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            margin: 0.25rem;
            display: inline-block;
        }
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: #f8f9fa;
            border-radius: 15px;
        }
        .search-stats {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Search Header -->
                <div class="search-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="mb-3">
                                    <i class="fas fa-search"></i> Hasil Pencarian Dokumen
                                </h1>
                                <p class="mb-0">Menampilkan hasil pencarian berdasarkan kriteria yang dipilih</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="index.php" class="btn btn-light btn-lg">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Dokumen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="container">
                    <!-- Search Statistics -->
                    <div class="search-stats">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">
                                    <i class="fas fa-chart-bar"></i> 
                                    Ditemukan <?php echo number_format($total_results); ?> dokumen
                                </h4>
                                <p class="mb-0">Berdasarkan kriteria pencarian yang Anda pilih</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-light" onclick="window.history.back()">
                                    <i class="fas fa-search"></i> Ubah Pencarian
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search Criteria -->
                    <div class="search-criteria">
                        <h5 class="mb-3">
                            <i class="fas fa-filter"></i> Kriteria Pencarian:
                        </h5>
                        <div class="criteria-list">
                            <?php if (!empty($search)): ?>
                                <span class="criteria-badge">
                                    <i class="fas fa-search"></i> Pencarian Umum: "<?php echo e($search); ?>"
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($full_name)): ?>
                                <span class="criteria-badge">
                                    <i class="fas fa-user"></i> Nama: "<?php echo e($full_name); ?>"
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($birth_date)): ?>
                                <span class="criteria-badge">
                                    <i class="fas fa-calendar"></i> Tanggal Lahir: <?php echo date('d/m/Y', strtotime($birth_date)); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($passport_number)): ?>
                                <span class="criteria-badge">
                                    <i class="fas fa-passport"></i> Paspor: "<?php echo e($passport_number); ?>"
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($category_filter > 0): ?>
                                <?php 
                                $selected_category = array_filter($categories, function($cat) use ($category_filter) {
                                    return $cat['id'] == $category_filter;
                                });
                                $category_name = !empty($selected_category) ? reset($selected_category)['category_name'] : 'Unknown';
                                ?>
                                <span class="criteria-badge">
                                    <i class="fas fa-folder"></i> Kategori: <?php echo e($category_name); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($date_from) || !empty($date_to)): ?>
                                <span class="criteria-badge">
                                    <i class="fas fa-calendar-alt"></i> 
                                    Periode: 
                                    <?php echo !empty($date_from) ? date('d/m/Y', strtotime($date_from)) : 'Awal'; ?>
                                    - 
                                    <?php echo !empty($date_to) ? date('d/m/Y', strtotime($date_to)) : 'Akhir'; ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (empty($search) && empty($full_name) && empty($birth_date) && empty($passport_number) && $category_filter == 0 && empty($date_from) && empty($date_to)): ?>
                                <span class="criteria-badge">
                                    <i class="fas fa-info-circle"></i> Tidak ada kriteria pencarian spesifik
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Search Results -->
                    <?php if (empty($search_results)): ?>
                        <div class="no-results">
                            <i class="fas fa-search fa-4x text-muted mb-4"></i>
                            <h3 class="text-muted">Tidak Ada Dokumen Ditemukan</h3>
                            <p class="text-muted mb-4">
                                Tidak ada dokumen yang sesuai dengan kriteria pencarian Anda.<br>
                                Coba ubah kriteria pencarian atau gunakan kata kunci yang berbeda.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($search_results as $doc): ?>
                                <div class="col-lg-6 col-xl-4">
                                    <div class="card result-card">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-file-alt"></i> <?php echo e($doc['document_number']); ?>
                                                </h6>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo date('d/m/Y', strtotime($doc['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title text-primary"><?php echo e($doc['title']); ?></h5>
                                            
                                            <?php if (!empty($doc['full_name'])): ?>
                                                <p class="mb-2">
                                                    <i class="fas fa-user text-primary"></i> 
                                                    <strong><?php echo e($doc['full_name']); ?></strong>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($doc['birth_date'])): ?>
                                                <p class="mb-2">
                                                    <i class="fas fa-calendar text-success"></i> 
                                                    Lahir: <?php echo date('d/m/Y', strtotime($doc['birth_date'])); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($doc['passport_number'])): ?>
                                                <p class="mb-2">
                                                    <i class="fas fa-passport text-info"></i> 
                                                    Paspor: <strong><?php echo e($doc['passport_number']); ?></strong>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if ($doc['category_name']): ?>
                                                <p class="mb-2">
                                                    <i class="fas fa-folder text-warning"></i> 
                                                    <?php echo e($doc['category_name']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($doc['description'])): ?>
                                                <p class="card-text text-muted">
                                                    <?php echo e(substr($doc['description'], 0, 100)) . (strlen($doc['description']) > 100 ? '...' : ''); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-user"></i> <?php echo e($doc['created_by_name']); ?>
                                                </small>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewDocument(<?php echo $doc['id']; ?>)" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php
                                                    // Build search parameters for return URL
                                                    $edit_params = ['id' => $doc['id'], 'return' => 'search'];
                                                    if (!empty($search)) $edit_params['search'] = $search;
                                                    if (!empty($full_name)) $edit_params['full_name'] = $full_name;
                                                    if (!empty($birth_date)) $edit_params['birth_date'] = $birth_date;
                                                    if (!empty($passport_number)) $edit_params['passport_number'] = $passport_number;
                                                    if ($category_filter > 0) $edit_params['category'] = $category_filter;
                                                    if (!empty($date_from)) $edit_params['date_from'] = $date_from;
                                                    if (!empty($date_to)) $edit_params['date_to'] = $date_to;
                                                    $edit_url = 'edit.php?' . http_build_query($edit_params);
                                                    ?>
                                                    <a href="<?php echo $edit_url; ?>" class="btn btn-sm btn-outline-warning" title="Edit Dokumen">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>, '<?php echo e($doc['full_name'] ?? $doc['title']); ?>')" title="Hapus Dokumen">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- View Document Modal -->
    <div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDocumentModalLabel">Detail Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="documentDetails">
                    <!-- Document details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteDocumentModalLabel">
                        <i class="fas fa-trash me-2"></i> Konfirmasi Penghapusan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus dokumen ini?</p>
                    <ul class="list-unstyled mb-3">
                        <li><strong>Nama:</strong> <span id="deleteDocumentName">-</span></li>
                        <li><strong>ID Dokumen:</strong> <span id="deleteDocumentId">-</span></li>
                    </ul>
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Peringatan:</strong> Tindakan ini tidak dapat dibatalkan. Semua data terkait dokumen ini akan dihapus secara permanen.
                    </div>
                    <div class="mb-3">
                        <label for="deleteDocumentAnswer" class="form-label">Untuk konfirmasi, jawab pertanyaan berikut:</label>
                        <p class="fw-bold mb-2" id="deleteDocumentQuestion"></p>
                        <input type="number" class="form-control" id="deleteDocumentAnswer" placeholder="Masukkan hasil penjumlahan">
                        <small class="form-text text-muted">Penghapusan hanya akan dilanjutkan jika jawaban benar.</small>
                    </div>
                    <input type="hidden" id="deleteDocumentCorrectAnswer">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>Hapus Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
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
        let deleteDocumentId = null;
        function deleteDocument(id, name) {
            deleteDocumentId = id;
            document.getElementById('deleteDocumentName').textContent = name;
            document.getElementById('deleteDocumentId').textContent = id;
            
            // Generate random math question
            const num1 = Math.floor(Math.random() * 10) + 1;
            const num2 = Math.floor(Math.random() * 10) + 1;
            const correctAnswer = num1 + num2;
            
            document.getElementById('deleteDocumentQuestion').textContent = `${num1} + ${num2} = ?`;
            document.getElementById('deleteDocumentCorrectAnswer').value = correctAnswer;
            document.getElementById('deleteDocumentAnswer').value = '';
            
            new bootstrap.Modal(document.getElementById('deleteDocumentModal')).show();
        }
        
        // Confirm delete
        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            if (!deleteDocumentId) return;
            
            // Validasi jawaban matematika
            const answer = parseInt(document.getElementById('deleteDocumentAnswer').value, 10);
            const correctAnswer = parseInt(document.getElementById('deleteDocumentCorrectAnswer').value, 10);
            
            if (isNaN(answer) || answer !== correctAnswer) {
                alert('Jawaban salah! Penghapusan dibatalkan.');
                return;
            }
            
            try {
                const response = await fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: deleteDocumentId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Dokumen berhasil dihapus');
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menghapus dokumen');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus dokumen');
            }
        });
    </script>
</body>
</html>

