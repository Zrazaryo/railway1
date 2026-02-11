<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

/**
 * Upload file untuk platform import (support CSV)
 */
function upload_file_for_platform($file, $upload_dir = 'uploads/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'File tidak valid'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error upload file'];
    }
    
    // Untuk platform import, izinkan juga CSV
    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'csv'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 10MB)'];
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = generate_unique_filename($file['name']);
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }
    
    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'size' => $file['size']
    ];
}

$error_message = '';
$success_message = '';

// Ambil tahun dari parameter
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : 0;

if ($selected_year <= 0) {
    header('Location: index.php?error=' . urlencode('Tahun tidak valid'));
    exit();
}

// Cek apakah tahun ada di platform_years
try {
    $year_exists = $db->fetch("SELECT * FROM platform_years WHERE year = ?", [$selected_year]);
    if (!$year_exists) {
        header('Location: index.php?error=' . urlencode('Tahun tidak ditemukan'));
        exit();
    }
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode('Error: ' . $e->getMessage()));
    exit();
}

// Proses upload dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $document_name = sanitize_input($_POST['document_name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    
    if (empty($document_name)) {
        $error_message = 'Nama dokumen harus diisi';
    } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'File dokumen harus diupload';
    } else {
        try {
            $file = $_FILES['document_file'];
            
            // Validasi file
            if (!is_allowed_file_type($file['name'])) {
                $error_message = 'Tipe file tidak diizinkan. Format yang diizinkan: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $error_message = 'Ukuran file terlalu besar (maksimal 10MB)';
            } else {
                // Upload file
                $upload_result = upload_file($file, '../documents/uploads/');
                
                if ($upload_result['success']) {
                    // Generate nomor dokumen
                    $document_number = generate_document_number();
                    
                    // Normalize file path
                    $file_path = $upload_result['filepath'];
                    if (strpos($file_path, '../') === 0) {
                        $file_path = substr($file_path, 3);
                    }
                    $file_path = str_replace('\\', '/', $file_path);
                    
                    // Simpan ke database
                    $sql = "INSERT INTO documents (
                        document_number, 
                        title, 
                        description, 
                        file_path, 
                        file_name, 
                        file_size, 
                        file_type, 
                        status, 
                        created_by,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)";
                    
                    // Set created_at sesuai tahun yang dipilih
                    $created_at = $selected_year . '-' . date('m-d') . ' ' . date('H:i:s');
                    
                    // Untuk dokumen platform, set description dengan flag khusus
                    // Pastikan selalu ada flag PLATFORM_UPLOAD di description
                    if (!empty($description)) {
                        if (strpos($description, 'PLATFORM_UPLOAD') === false) {
                            $platform_description = $description . ' | PLATFORM_UPLOAD';
                        } else {
                            $platform_description = $description;
                        }
                    } else {
                        $platform_description = 'PLATFORM_UPLOAD';
                    }
                    
                    $db->execute($sql, [
                        $document_number,
                        $document_name,
                        $platform_description,
                        $file_path,
                        $upload_result['filename'],
                        $upload_result['size'],
                        $file['type'],
                        $_SESSION['user_id'],
                        $created_at
                    ]);
                    
                    $document_id = $db->lastInsertId();
                    
                    // Log aktivitas
                    log_activity($_SESSION['user_id'], 'PLATFORM_UPLOAD', "Upload dokumen platform: $document_name (Tahun: $selected_year)", $document_id);
                    
                    $success_message = 'Dokumen berhasil diupload';
                    
                    // Redirect untuk menghindari resubmit
                    header("Location: documents.php?year=$selected_year&success=" . urlencode($success_message));
                    exit();
                } else {
                    $error_message = $upload_result['message'] ?? 'Gagal mengupload file';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Proses import dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    if (!isset($_FILES['import_files']) || empty($_FILES['import_files']['name'][0])) {
        $error_message = 'Pilih minimal satu file untuk diimport';
    } else {
        $files = $_FILES['import_files'];
        $imported_count = 0;
        $failed_count = 0;
        $failed_files = [];
        
        // Loop melalui semua file yang diupload
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $failed_count++;
                $failed_files[] = $files['name'][$i] . ' (Error: ' . $files['error'][$i] . ')';
                continue;
            }
            
            try {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                // Validasi file - untuk import platform, izinkan juga CSV
                $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'csv'];
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $failed_count++;
                    $failed_files[] = $file['name'] . ' (Tipe file tidak diizinkan)';
                    continue;
                }
                
                if ($file['size'] > 10 * 1024 * 1024) {
                    $failed_count++;
                    $failed_files[] = $file['name'] . ' (Ukuran file terlalu besar)';
                    continue;
                }
                
                // Upload file - gunakan fungsi khusus untuk platform import yang support CSV
                $upload_result = upload_file_for_platform($file, '../documents/uploads/');
                
                if ($upload_result['success']) {
                    // Generate nomor dokumen
                    $document_number = generate_document_number();
                    
                    // Normalize file path
                    $file_path = $upload_result['filepath'];
                    if (strpos($file_path, '../') === 0) {
                        $file_path = substr($file_path, 3);
                    }
                    $file_path = str_replace('\\', '/', $file_path);
                    
                    // Gunakan nama file sebagai nama dokumen (tanpa extension)
                    $document_name = pathinfo($file['name'], PATHINFO_FILENAME);
                    
                    // Simpan ke database
                    $sql = "INSERT INTO documents (
                        document_number, 
                        title, 
                        description, 
                        file_path, 
                        file_name, 
                        file_size, 
                        file_type, 
                        status, 
                        created_by,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)";
                    
                    // Set created_at sesuai tahun yang dipilih
                    // Format: YYYY-MM-DD HH:MM:SS
                    $created_at = $selected_year . '-' . date('m-d') . ' ' . date('H:i:s');
                    // Pastikan format valid untuk MySQL
                    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $created_at)) {
                        $created_at = date('Y-m-d H:i:s');
                    }
                    
                    // Set description dengan flag khusus untuk dokumen platform
                    $db->execute($sql, [
                        $document_number,
                        $document_name,
                        'PLATFORM_UPLOAD',
                        $file_path,
                        $upload_result['filename'],
                        $upload_result['size'],
                        $file['type'],
                        $_SESSION['user_id'],
                        $created_at
                    ]);
                    
                    $document_id = $db->lastInsertId();
                    
                    // Log aktivitas
                    log_activity($_SESSION['user_id'], 'PLATFORM_IMPORT', "Import dokumen platform: $document_name (Tahun: $selected_year)", $document_id);
                    
                    $imported_count++;
                } else {
                    $failed_count++;
                    $failed_files[] = $file['name'] . ' (' . ($upload_result['message'] ?? 'Gagal upload') . ')';
                }
            } catch (Exception $e) {
                $failed_count++;
                $failed_files[] = $files['name'][$i] . ' (' . $e->getMessage() . ')';
            }
        }
        
        // Set pesan success/error
        if ($imported_count > 0) {
            $success_message = "Berhasil mengimport $imported_count dokumen";
            if ($failed_count > 0) {
                $success_message .= ". Gagal: $failed_count dokumen";
            }
        } else {
            $error_message = "Gagal mengimport semua dokumen. " . implode(', ', $failed_files);
        }
        
        // Simpan failed files ke session untuk ditampilkan
        if (!empty($failed_files)) {
            $_SESSION['import_failed_files'] = $failed_files;
        }
        
        // Redirect untuk menghindari resubmit
        if ($imported_count > 0) {
            header("Location: documents.php?year=$selected_year&success=" . urlencode($success_message));
            exit();
        }
    }
}

// Proses hapus dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $document_id = (int)($_POST['document_id'] ?? 0);
    
    if ($document_id > 0) {
        try {
            $doc = $db->fetch("SELECT * FROM documents WHERE id = ? AND status = 'active'", [$document_id]);
            
            if ($doc && (empty($doc['full_name']) || $doc['full_name'] == '')) {
                $db->execute("UPDATE documents SET status = 'deleted' WHERE id = ?", [$document_id]);
                log_activity($_SESSION['user_id'], 'PLATFORM_DELETE', "Hapus dokumen platform: " . $doc['title'], $document_id);
                $success_message = 'Dokumen berhasil dihapus';
                header("Location: documents.php?year=$selected_year&success=" . urlencode($success_message));
                exit();
            }
        } catch (Exception $e) {
            $error_message = 'Gagal menghapus dokumen: ' . $e->getMessage();
        }
    }
}

// Tampilkan pesan success dari URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Search and Filter
$search = sanitize_input($_GET['search'] ?? '');
$sort_param = $_GET['sort'] ?? 'created_at_desc';

// Parse sort parameter
if ($sort_param === 'created_at_desc') {
    $sort_by = 'created_at';
    $sort_order = 'DESC';
} elseif ($sort_param === 'created_at_asc') {
    $sort_by = 'created_at';
    $sort_order = 'ASC';
} elseif ($sort_param === 'title_asc') {
    $sort_by = 'title';
    $sort_order = 'ASC';
} else {
    $sort_by = 'created_at';
    $sort_order = 'DESC';
}

// Build query - tampilkan hanya dokumen platform upload di tahun tersebut
// Dokumen platform memiliki description yang mengandung 'PLATFORM_UPLOAD' atau 'Dokumen diimport'
// Atau dokumen yang memiliki title tetapi full_name kosong (ciri dokumen platform)
$where_conditions = [
    "d.status = 'active'",
    "(d.description LIKE '%PLATFORM_UPLOAD%' 
      OR d.description LIKE '%Dokumen diimport%' 
      OR (d.title IS NOT NULL AND d.title != '' AND (d.full_name IS NULL OR d.full_name = ''))
      OR (d.description IS NULL AND d.title IS NOT NULL AND d.title != '' AND d.full_name IS NULL))",
    "(YEAR(d.created_at) = ? OR d.created_at LIKE ?)"
];
$params = [$selected_year, $selected_year . '%'];

if (!empty($search)) {
    $where_conditions[] = "(d.title LIKE ? OR d.description LIKE ? OR d.file_name LIKE ? OR d.full_name LIKE ? OR d.nik LIKE ? OR d.passport_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM documents d WHERE $where_clause";
$total_records = $db->fetch($count_sql, $params)['total'];

// Ambil daftar dokumen berdasarkan tahun dengan join ke lockers
try {
    // Debug: Tampilkan query untuk troubleshooting (hapus di production jika tidak diperlukan)
    // error_log("Query: $documents_sql");
    // error_log("Params: " . print_r($params, true));
    
    $documents_sql = "SELECT d.*, 
                             u.full_name as created_by_name,
                             l.name AS locker_name
                      FROM documents d
                      LEFT JOIN users u ON d.created_by = u.id
                      LEFT JOIN lockers l ON d.month_number = l.code
                      WHERE $where_clause
                      ORDER BY d.$sort_by $sort_order";
    
    $documents = $db->fetchAll($documents_sql, $params);
    
    // Jika tidak ada dokumen, coba query alternatif yang lebih sederhana
    if (empty($documents) && empty($search)) {
        // Query alternatif: ambil semua dokumen aktif di tahun tersebut yang memiliki title
        // Ini untuk memastikan dokumen platform yang baru diupload muncul
        $alt_where = [
            "d.status = 'active'",
            "d.title IS NOT NULL AND d.title != ''",
            "(YEAR(d.created_at) = ? OR d.created_at LIKE ?)"
        ];
        $alt_params = [$selected_year, $selected_year . '%'];
        $alt_sql = "SELECT d.*, 
                           u.full_name as created_by_name,
                           l.name AS locker_name
                    FROM documents d
                    LEFT JOIN users u ON d.created_by = u.id
                    LEFT JOIN lockers l ON d.month_number = l.code
                    WHERE " . implode(' AND ', $alt_where) . "
                    ORDER BY d.$sort_by $sort_order
                    LIMIT 100";
        
        $alt_documents = $db->fetchAll($alt_sql, $alt_params);
        
        // Jika query alternatif menemukan dokumen, gunakan hasilnya
        // Tapi filter lagi untuk hanya ambil yang sesuai kriteria platform
        if (!empty($alt_documents)) {
            $filtered_docs = [];
            foreach ($alt_documents as $doc) {
                $is_platform = false;
                // Cek apakah ini dokumen platform
                if (!empty($doc['description']) && 
                    (strpos($doc['description'], 'PLATFORM_UPLOAD') !== false || 
                     strpos($doc['description'], 'Dokumen diimport') !== false)) {
                    $is_platform = true;
                } elseif (empty($doc['full_name']) && !empty($doc['title'])) {
                    // Dokumen dengan title tapi tanpa full_name kemungkinan platform upload
                    $is_platform = true;
                }
                
                if ($is_platform) {
                    $filtered_docs[] = $doc;
                }
            }
            
            if (!empty($filtered_docs)) {
                $documents = $filtered_docs;
                $total_records = count($filtered_docs);
            }
        }
    }
} catch (Exception $e) {
    $error_message = 'Error mengambil data: ' . $e->getMessage();
    $documents = [];
    $total_records = 0;
}

// Helper untuk format document origin
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
    <title>Dokumen Tahun <?php echo $selected_year; ?> - Platform Upload</title>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-file-alt me-2"></i>
                        Dokumen Tahun <?php echo $selected_year; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
                
                <!-- Alert Messages -->
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo e($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['import_failed_files']) && !empty($_SESSION['import_failed_files'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Beberapa file gagal diimport:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($_SESSION['import_failed_files'] as $failed): ?>
                                <li><?php echo htmlspecialchars($failed); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['import_failed_files']); ?>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="search-filter-container mb-3">
                    <form method="GET" id="searchForm" class="row g-3">
                        <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                        
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
                                <option value="title_asc" <?php echo $sort_by == 'title' && $sort_order == 'ASC' ? 'selected' : ''; ?>>Nama A-Z</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select class="form-select" disabled>
                                <option>Semua Kategori</option>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100" title="Cari">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <div class="col-md-1">
                            <button type="button" class="btn btn-primary w-100" onclick="document.getElementById('searchForm').submit();" title="Cari">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="text-muted">
                                Menampilkan <?php echo number_format($total_records); ?> dokumen
                                <?php if (!empty($search)): ?>
                                    dari hasil pencarian
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                <i class="fas fa-trash"></i> Hapus Terpilih
                            </button>
                            <button type="button" class="btn btn-sm btn-success" onclick="exportSelected()">
                                <i class="fas fa-download"></i> Export Terpilih
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportAll()">
                                <i class="fas fa-download"></i> Export Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-upload"></i> Import
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Tabel Dokumen -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" title="Pilih Semua">
                                        </th>
                                        <th style="width: 40px">No</th>
                                        <th style="width: 150px">Nama Lengkap</th>
                                        <th style="width: 100px">NIK</th>
                                        <th style="width: 100px">No Passport</th>
                                        <th style="width: 80px">Kode Lemari</th>
                                        <th style="width: 100px">Nama Lemari</th>
                                        <th style="width: 120px">Urutan Dokumen</th>
                                        <th style="width: 120px">Kode Dokumen</th>
                                        <th style="width: 150px">Dokumen Berasal</th>
                                        <th style="width: 80px">Kategori</th>
                                        <th style="width: 100px">Di Buat Oleh</th>
                                        <th style="width: 100px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($documents)): ?>
                                        <tr>
                                            <td colspan="13" class="text-center py-4">
                                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Tidak ada dokumen ditemukan untuk tahun <?php echo $selected_year; ?></p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($documents as $doc): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="document-checkbox" name="doc_ids[]" value="<?php echo $doc['id']; ?>">
                                                </td>
                                                <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                                <td class="fw-semibold" title="<?php echo e($doc['full_name'] ?: ($doc['title'] ?? $doc['file_name'] ?? '-')); ?>">
                                                    <?php 
                                                    $display_name = $doc['full_name'] ?: ($doc['title'] ?? $doc['file_name'] ?? '-');
                                                    // Truncate jika terlalu panjang
                                                    if (strlen($display_name) > 50) {
                                                        echo e(substr($display_name, 0, 47)) . '...';
                                                    } else {
                                                        echo e($display_name);
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo e($doc['nik'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['passport_number'] ?? '-'); ?></td>
                                                <td><?php echo e(substr($doc['month_number'] ?? '-', 0, 1)); ?></td>
                                                <td><?php echo e($doc['locker_name'] ?? '-'); ?></td>
                                                <td><?php echo e($doc['document_order_number'] ?? '-'); ?></td>
                                                <td><?php echo e(($doc['locker_name'] ?? '-') . '.' . ($doc['document_order_number'] ?? '-')); ?></td>
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
                                                            <i class="fas fa-eye"></i> lihat
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-warning" 
                                                                onclick="editDocument(<?php echo $doc['id']; ?>)" 
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i> edit
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteDocument(<?php echo $doc['id']; ?>, '<?php echo e($doc['title'] ?? $doc['full_name'] ?? 'Dokumen'); ?>')" 
                                                                title="Hapus">
                                                            <i class="fas fa-trash"></i> hapus
                                                        </button>
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
            </main>
        </div>
    </div>
    
    <!-- Modal Upload Dokumen -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadModalLabel">
                        <i class="fas fa-upload me-2"></i>Upload Dokumen Baru - Tahun <?php echo $selected_year; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="action" value="upload">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="document_name" class="form-label">
                                Nama Dokumen <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="document_name" 
                                   name="document_name" required 
                                   placeholder="Masukkan nama dokumen">
                        </div>
                        
                        <div class="mb-3">
                            <label for="document_file" class="form-label">
                                File Dokumen <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="document_file" 
                                   name="document_file" required 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                            <small class="text-muted">Maksimal 10MB</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" placeholder="Masukkan deskripsi dokumen (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Import -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="fas fa-upload me-2"></i>Import Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="importForm">
                    <input type="hidden" name="action" value="import">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Petunjuk:</strong> Upload file dokumen yang telah didownload dari menu dokumen. Anda dapat mengupload beberapa file sekaligus.
                        </div>
                        
                        <div class="mb-3">
                            <label for="import_files" class="form-label">
                                Pilih File Dokumen <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="import_files" 
                                   name="import_files[]" required multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.csv">
                            <small class="text-muted">
                                Format yang didukung: PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, PNG, GIF. Maksimal 10MB per file.
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">File yang dipilih:</label>
                            <div id="fileList" class="border rounded p-2" style="min-height: 100px; max-height: 200px; overflow-y: auto;">
                                <small class="text-muted">Belum ada file dipilih</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-upload me-2"></i>Import Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal View Document -->
    <div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewDocumentModalLabel">
                        <i class="fas fa-eye me-2"></i>Detail Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="documentDetails">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Memuat...</span>
                        </div>
                        <p class="mt-2">Memuat detail dokumen...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-trash me-2"></i>Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus dokumen <strong id="deleteDocumentName"></strong>?</p>
                    <p class="text-danger small mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="document_id" id="deleteDocumentId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle select all
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.document-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        // Delete document
        function deleteDocument(id, name) {
            document.getElementById('deleteDocumentId').value = id;
            document.getElementById('deleteDocumentName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // View document
        function viewDocument(id) {
            fetch('view.php?id=' + id)
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
        
        // Edit document
        function editDocument(id) {
            window.location.href = 'edit.php?id=' + id;
        }
        
        // Delete selected documents
        function deleteSelected() {
            const checkboxes = document.querySelectorAll('.document-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Pilih minimal satu dokumen untuk dihapus!');
                return;
            }
            
            if (confirm('Apakah Anda yakin ingin menghapus ' + checkboxes.length + ' dokumen yang dipilih?')) {
                const docIds = Array.from(checkboxes).map(cb => cb.value);
                // Implement delete multiple
                alert('Fitur hapus terpilih akan segera tersedia. Dokumen yang dipilih: ' + docIds.join(', '));
            }
        }
        
        // Export selected documents
        function exportSelected() {
            const checkboxes = document.querySelectorAll('.document-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Pilih minimal satu dokumen untuk di-export!');
                return;
            }
            
            const docIds = Array.from(checkboxes).map(cb => cb.value);
            alert('Fitur export terpilih akan segera tersedia. Dokumen yang dipilih: ' + docIds.join(', '));
        }
        
        // Export all documents
        function exportAll() {
            if (confirm('Apakah Anda yakin ingin mengexport semua dokumen?')) {
                alert('Fitur export semua akan segera tersedia.');
            }
        }
        
        // Validasi form upload
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('document_file');
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (fileInput.files.length > 0) {
                if (fileInput.files[0].size > maxSize) {
                    e.preventDefault();
                    alert('Ukuran file terlalu besar. Maksimal 10MB.');
                    return false;
                }
            }
        });
        
        // Preview file list untuk import
        document.getElementById('import_files').addEventListener('change', function(e) {
            const fileList = document.getElementById('fileList');
            const files = e.target.files;
            
            if (files.length === 0) {
                fileList.innerHTML = '<small class="text-muted">Belum ada file dipilih</small>';
                return;
            }
            
            let html = '<ul class="list-unstyled mb-0">';
            let totalSize = 0;
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                totalSize += file.size;
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const isValid = file.size <= maxSize;
                const icon = isValid ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger';
                
                html += `<li class="mb-2">
                    <i class="fas ${icon} me-2"></i>
                    <strong>${file.name}</strong>
                    <small class="text-muted">(${fileSize} MB)</small>
                    ${!isValid ? '<span class="badge bg-danger ms-2">Terlalu besar</span>' : ''}
                </li>`;
            }
            
            html += '</ul>';
            html += `<div class="mt-2"><small class="text-muted">Total: ${files.length} file, ${(totalSize / 1024 / 1024).toFixed(2)} MB</small></div>`;
            
            fileList.innerHTML = html;
        });
        
        // Validasi form import
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('import_files');
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif'];
            let hasError = false;
            let errorMessage = '';
            
            if (fileInput.files.length === 0) {
                e.preventDefault();
                alert('Pilih minimal satu file untuk diimport!');
                return false;
            }
            
            for (let i = 0; i < fileInput.files.length; i++) {
                const file = fileInput.files[i];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(fileExtension)) {
                    hasError = true;
                    errorMessage += file.name + ' (Tipe file tidak diizinkan)\n';
                }
                
                if (file.size > maxSize) {
                    hasError = true;
                    errorMessage += file.name + ' terlalu besar (maksimal 10MB)\n';
                }
            }
            
            if (hasError) {
                e.preventDefault();
                alert('Beberapa file tidak valid:\n' + errorMessage);
                return false;
            }
        });
    </script>
</body>
</html>

