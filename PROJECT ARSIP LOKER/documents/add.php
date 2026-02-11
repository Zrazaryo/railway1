<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$error_message = '';
$success_message = '';

// Ambil kode lemari dari query (GET) atau dari POST (saat submit ulang)
$selected_locker_code = sanitize_input($_GET['locker'] ?? ($_POST['month_number'] ?? ''));
$selected_locker = null;

if (empty($selected_locker_code)) {
    // Jika tidak ada kode lemari, paksa user pilih lemari dulu
    header('Location: ../lockers/select.php?error=' . urlencode('Silakan pilih lemari terlebih dahulu sebelum menambah dokumen.'));
    exit();
}

// Ambil data lemari dan cek kapasitas
try {
    $selected_locker = $db->fetch("SELECT * FROM lockers WHERE code = ?", [$selected_locker_code]);
    if (!$selected_locker) {
        header('Location: ../lockers/select.php?error=' . urlencode('Kode lemari tidak valid.'));
        exit();
    }
} catch (Exception $e) {
    $error_message = 'Gagal mengambil data lemari: ' . $e->getMessage();
}

// Proses form upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $nik = sanitize_input($_POST['nik'] ?? '');
    $passport_number = sanitize_input($_POST['passport_number'] ?? '');
    $birth_date = sanitize_input($_POST['birth_date'] ?? '');
    $month_number = sanitize_input($_POST['month_number'] ?? '');
    $marriage_certificate = sanitize_input($_POST['marriage_certificate'] ?? '');
    $birth_certificate = sanitize_input($_POST['birth_certificate'] ?? '');
    $divorce_certificate = sanitize_input($_POST['divorce_certificate'] ?? '');
    $custody_certificate = sanitize_input($_POST['custody_certificate'] ?? '');
    $citizen_category = sanitize_input($_POST['citizen_category'] ?? 'WNI');
    $document_origin = sanitize_input($_POST['document_origin'] ?? '');
    $document_year = isset($_POST['document_year']) ? (int)$_POST['document_year'] : null;
    
    // Validasi input wajib
    if (empty($full_name)) {
        $error_message = 'Nama lengkap harus diisi';
    } elseif (empty($nik)) {
        $error_message = 'NIK wajib diisi';
    } elseif (empty($passport_number)) {
        $error_message = 'No Passport wajib diisi';
    } elseif (empty($birth_date)) {
        $error_message = 'Tanggal Lahir wajib diisi';
    } elseif (empty($month_number)) {
        $error_message = 'Kode Lemari wajib diisi';
    } elseif (empty($document_origin)) {
        $error_message = 'Dokumen berasal wajib dipilih';
    } elseif (empty($document_year)) {
        $error_message = 'Tahun dokumen wajib diisi';
    } elseif ($document_year < 1900 || $document_year > 2100) {
        $error_message = 'Tahun dokumen harus antara 1900-2100';
    } else {
        try {
                // Cek kapasitas lemari (maksimal 600 dokumen)
                $countRow = $db->fetch(
                    "SELECT COUNT(*) AS used FROM documents WHERE month_number = ? AND status = 'active'",
                    [$month_number]
                );
                $usedCount = (int)($countRow['used'] ?? 0);
                $maxCapacity = (int)($selected_locker['max_capacity'] ?? 600);

                if ($usedCount >= $maxCapacity) {
                    throw new Exception("Lemari {$month_number} sudah penuh (kapasitas {$maxCapacity} dokumen). Silakan pilih lemari lain.");
                }

                // Generate nomor dokumen
                $document_number = generate_document_number();
                
            // Simpan data utama ke database (sertakan kolom lama agar kompatibel)
            $sql = "INSERT INTO documents (document_number, title, full_name, nik, passport_number, birth_date, month_number, marriage_certificate, birth_certificate, divorce_certificate, custody_certificate, citizen_category, document_origin, document_year, file_path, file_name, file_size, file_type, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $db->execute($sql, [
                    $document_number,
                    $full_name, // title diisi nama lengkap sebagai fallback
                    $full_name,
                    $nik ?: null,
                    $passport_number ?: null,
                    $birth_date ?: null,
                    $month_number ?: null,
                    $marriage_certificate ?: null,
                    $birth_certificate ?: null,
                    $divorce_certificate ?: null,
                    $custody_certificate ?: null,
                    $citizen_category,
                    $document_origin ?: null,
                    $document_year,
                    '', // file_path legacy (tidak digunakan)
                    '', // file_name legacy
                    0,  // file_size legacy
                    '', // file_type legacy
                    $_SESSION['user_id']
                ]);
            
            $document_id = $db->lastInsertId();
            
            // Proses upload file untuk setiap jenis dokumen
            $document_types = [
                'ktp_file' => 'KTP',
                'family_card_file' => 'Kartu Keluarga',
                'birth_certificate_file' => 'Akta Lahir',
                'custody_certificate_file' => 'Surat Hak Asuh Anak',
                'diploma_file' => 'Ijazah',
                'passport_file' => 'Paspor',
                'marriage_certificate_file' => 'Surat Nikah',
                'divorce_certificate_file' => 'Surat Cerai'
            ];
            
            $uploaded_files = 0;
            
            foreach ($document_types as $field_name => $document_name) {
                if (isset($_FILES[$field_name]) && !empty($_FILES[$field_name]['name'])) {
                    $file = $_FILES[$field_name];
                    
                    if (is_allowed_file_type($file['name'])) {
                        $upload_result = upload_file($file);
                        
                        if ($upload_result['success']) {
                            // Simpan detail dokumen
                            $detail_sql = "INSERT INTO document_files (document_id, document_type, file_path, file_name, file_size, file_type) 
                                          VALUES (?, ?, ?, ?, ?, ?)";
                            
                            $db->execute($detail_sql, [
                                $document_id,
                                $document_name,
                    $upload_result['filepath'],
                    $upload_result['filename'],
                    $upload_result['size'],
                                $file['type']
                            ]);
                            
                            $uploaded_files++;
                        }
                    }
                }
            }
                
                // Log aktivitas
            log_activity($_SESSION['user_id'], 'ADD_DOCUMENT', "Menambah dokumen: $full_name ($uploaded_files file)", $document_id);
                
            $success_message = "Dokumen berhasil ditambahkan dengan $uploaded_files file";
                
                // Redirect ke halaman dokumen setelah 2 detik
                header("refresh:2;url=index.php");
            
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan saat menyimpan dokumen: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Dokumen - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .btn-group .btn-check:checked + .btn {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        .btn-group .btn-check:not(:checked) + .btn {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #212529;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
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
                    <h1 class="h2">Tambah Dokumen Baru</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <!-- Alert Messages -->
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
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
                
                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                        <!-- Kolom Kiri - Informasi Pribadi -->
                        <div class="col-lg-6">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                        <i class="fas fa-user me-2"></i>
                                        Form Tambahkan Dokumen
                                </h5>
                            </div>
                            <div class="card-body">
                                    <!-- Informasi Pribadi -->
                                    <div class="mb-3">
                                            <label for="full_name" class="form-label">Nama Lengkap</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="full_name" name="full_name" required 
                                                   value="<?php echo e($_POST['full_name'] ?? ''); ?>" 
                                                   placeholder="Value">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('full_name')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="nik" class="form-label">NIK <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nik" name="nik" required 
                                                   value="<?php echo e($_POST['nik'] ?? ''); ?>" 
                                                   placeholder="Value">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('nik')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">NIK wajib diisi.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="passport_number" class="form-label">No Passport <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="passport_number" name="passport_number" required 
                                                   value="<?php echo e($_POST['passport_number'] ?? ''); ?>" 
                                                   placeholder="Value">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('passport_number')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">No Passport wajib diisi.</div>
                                        </div>
                                        
                                    <div class="mb-3">
                                            <label for="birth_date" class="form-label">Tanggal Lahir</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="birth_date" name="birth_date" required 
                                                   value="<?php echo e($_POST['birth_date'] ?? ''); ?>">
                                            <span class="input-group-text">
                                                <i class="fas fa-calendar"></i>
                                            </span>
                                        </div>
                                        </div>
                                        
                                    <div class="mb-3">
                                        <label for="month_number_display" class="form-label">Kode Lemari <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="month_number_display"
                                                   value="<?php echo e(substr($selected_locker_code, 0, 1)); ?>"
                                                   placeholder="Kode Lemari" readonly>
                                            <span class="input-group-text">
                                                <i class="fas fa-archive"></i>
                                            </span>
                                        </div>
                                        <input type="hidden" id="month_number" name="month_number" value="<?php echo e($selected_locker_code); ?>">
                                        <div class="form-text">
                                            Kode lemari dipilih dari halaman sebelumnya. Kapasitas tiap lemari: 600 dokumen.
                                        </div>
                                        <div class="invalid-feedback">Kode Lemari wajib diisi.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nama Rak</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="<?php echo e($selected_locker['name'] ?? ''); ?>" readonly>
                                            <span class="input-group-text">
                                                <i class="fas fa-tag"></i>
                                            </span>
                                        </div>
                                        <div class="form-text">
                                            Nama lemari mengikuti pengaturan di menu pilih lemari (misal A.01, B.05, dll).
                                        </div>
                                    </div>
                                        
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="marriage_certificate" class="form-label">No Surat Nikah</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="marriage_certificate" name="marriage_certificate" 
                                                       value="<?php echo e($_POST['marriage_certificate'] ?? ''); ?>" 
                                                       placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('marriage_certificate')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Boleh dikosongkan jika tidak ada.</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="birth_certificate" class="form-label">No Akta Lahir</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="birth_certificate" name="birth_certificate" 
                                                       value="<?php echo e($_POST['birth_certificate'] ?? ''); ?>" 
                                                       placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('birth_certificate')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Boleh dikosongkan jika tidak ada.</div>
                                        </div>
                                        </div>
                                        
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="divorce_certificate" class="form-label">No Surat Cerai</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="divorce_certificate" name="divorce_certificate" 
                                                       value="<?php echo e($_POST['divorce_certificate'] ?? ''); ?>" 
                                                       placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('divorce_certificate')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Boleh dikosongkan jika tidak ada.</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="custody_certificate" class="form-label">No Surat Hak Asuh</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="custody_certificate" name="custody_certificate" 
                                                       value="<?php echo e($_POST['custody_certificate'] ?? ''); ?>" 
                                                       placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('custody_certificate')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Boleh dikosongkan jika tidak ada.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Kategori Dokumen -->
                                    <div class="mb-4">
                                        <label class="form-label">KATAGORI DOKUMEN</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="citizen_category" id="wni" value="WNI" 
                                                   <?php echo (isset($_POST['citizen_category']) && $_POST['citizen_category'] == 'WNI') ? 'checked' : 'checked'; ?>>
                                            <label class="btn btn-outline-primary" for="wni">WNI</label>
                                            
                                            <input type="radio" class="btn-check" name="citizen_category" id="wna" value="WNA"
                                                   <?php echo (isset($_POST['citizen_category']) && $_POST['citizen_category'] == 'WNA') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-secondary" for="wna">WNA</label>
                                        </div>
                                    </div>

                                    <!-- Dokumen Berasal -->
                                    <div class="mb-3">
                                        <label for="document_origin" class="form-label">Dokumen Berasal <span class="text-danger">*</span></label>
                                        <select class="form-select" id="document_origin" name="document_origin" required>
                                            <option value="">Pilih asal dokumen...</option>
                                            <option value="imigrasi_jakarta_pusat_kemayoran" <?php echo (($_POST['document_origin'] ?? '') === 'imigrasi_jakarta_pusat_kemayoran') ? 'selected' : ''; ?>>
                                                Imigrasi Jakarta Pusat Kemayoran
                                            </option>
                                            <option value="imigrasi_ulp_semanggi" <?php echo (($_POST['document_origin'] ?? '') === 'imigrasi_ulp_semanggi') ? 'selected' : ''; ?>>
                                                Imigrasi ULP Semanggi
                                            </option>
                                            <option value="imigrasi_lounge_senayan_city" <?php echo (($_POST['document_origin'] ?? '') === 'imigrasi_lounge_senayan_city') ? 'selected' : ''; ?>>
                                                Imigrasi Lounge Senayan City
                                            </option>
                                        </select>
                                        <div class="invalid-feedback">Dokumen berasal wajib dipilih.</div>
                                    </div>
                                    
                                    <!-- Tombol Aksi: (hapus tombol Tambahkan Foto sesuai permintaan) -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kolom Kanan - Upload Dokumen -->
                    <div class="col-lg-6">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-file-upload me-2"></i>
                                    Upload Dokumen
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Grid 2x4 untuk jenis dokumen -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">KTP</label>
                                        <div class="input-group file-upload-group" data-input-name="ktp_file">
                                            <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                            <select class="form-select file-upload-status">
                                                <option value="none" selected>Tidak Ada</option>
                                                <option value="exists">Ada</option>
                                            </select>
                                        </div>
                                        <input type="file" name="ktp_file" class="d-none real-file-input"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">KARTU KELUARGA</label>
                                        <div class="input-group file-upload-group" data-input-name="family_card_file">
                                            <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                            <select class="form-select file-upload-status">
                                                <option value="none" selected>Tidak Ada</option>
                                                <option value="exists">Ada</option>
                                            </select>
                                        </div>
                                        <input type="file" name="family_card_file" class="d-none real-file-input"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">AKTA LAHIR</label>
                                        <div class="input-group file-upload-group" data-input-name="birth_certificate_file">
                                            <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                            <select class="form-select file-upload-status">
                                                <option value="none" selected>Tidak Ada</option>
                                                <option value="exists">Ada</option>
                                            </select>
                                        </div>
                                        <input type="file" name="birth_certificate_file" class="d-none real-file-input"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SURAT HAK ASUH ANAK</label>
                                        <div class="input-group file-upload-group" data-input-name="custody_certificate_file">
                                            <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                            <select class="form-select file-upload-status">
                                                <option value="none" selected>Tidak Ada</option>
                                                <option value="exists">Ada</option>
                                            </select>
                                        </div>
                                        <input type="file" name="custody_certificate_file" class="d-none real-file-input"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">IJAZAH</label>
                                        <div class="input-group file-upload-group" data-input-name="diploma_file">
                                            <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                            <select class="form-select file-upload-status">
                                                <option value="none" selected>Tidak Ada</option>
                                                <option value="exists">Ada</option>
                                            </select>
                                        </div>
                                        <input type="file" name="diploma_file" class="d-none real-file-input"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">PASPOR</label>
                                        <div class="input-group file-upload-group" data-input-name="passport_file">
                                            <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                            <select class="form-select file-upload-status">
                                                <option value="none" selected>Tidak Ada</option>
                                                <option value="exists">Ada</option>
                                            </select>
                                        </div>
                                        <input type="file" name="passport_file" class="d-none real-file-input"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SURAT NIKAH</label>
                                        <div class="input-group file-upload-group" data-input-name="marriage_certificate_file">
                                            <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                            <select class="form-select file-upload-status">
                                                <option value="none" selected>Tidak Ada</option>
                                                <option value="exists">Ada</option>
                                            </select>
                                        </div>
                                        <input type="file" name="marriage_certificate_file" class="d-none real-file-input"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SURAT CERAI</label>
                                        <div class="input-group file-upload-group" data-input-name="divorce_certificate_file">
                                            <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                            <select class="form-select file-upload-status">
                                                <option value="none" selected>Tidak Ada</option>
                                                <option value="exists">Ada</option>
                                            </select>
                                        </div>
                                        <input type="file" name="divorce_certificate_file" class="d-none real-file-input"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tombol Submit -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="index.php" class="btn btn-success">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        // Fungsi untuk menghapus field
        function clearField(fieldId) {
            document.getElementById(fieldId).value = '';
        }
        
        // (fitur tambah foto dihapus)
        
        // Handle upload dokumen:
        // - Select "Tidak Ada / Ada" hanya penanda, TIDAK membuka dialog file
        // - Klik ikon klip baru membuka dialog file (opsional)
        document.querySelectorAll('.file-upload-group').forEach(function(group) {
            const inputName = group.getAttribute('data-input-name');
            const triggerBtn = group.querySelector('.file-upload-trigger');
            const statusSelect = group.querySelector('.file-upload-status');
            if (!inputName || !triggerBtn || !statusSelect) return;

            let fileInput = document.querySelector('input.real-file-input[name="' + inputName + '"]');
            if (!fileInput) {
                fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = inputName;
                fileInput.className = 'd-none real-file-input';
                fileInput.accept = '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif';
                group.parentNode.appendChild(fileInput);
            }

            // Klik ikon klip => buka dialog pilih file (opsional)
            triggerBtn.addEventListener('click', function () {
                fileInput.click();
            });

            // Perubahan file: jika ada file, otomatis set status ke "Ada (nama file)"
            fileInput.addEventListener('change', function () {
                const existsOption = statusSelect.querySelector('option[value="exists"]');
                if (fileInput.files && fileInput.files.length > 0) {
                    statusSelect.value = 'exists';
                    if (existsOption) {
                        existsOption.textContent = 'Ada (' + fileInput.files[0].name + ')';
                    }
                } else {
                    // Jika file dihapus, kembalikan label "Ada" apa adanya, tapi tidak memaksa user ke "Tidak Ada"
                    if (existsOption) {
                        existsOption.textContent = 'Ada';
                    }
                }
            });
        });
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
