<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Hanya pengguna login (admin atau staff pemilik dokumen) yang boleh mengedit
require_login();

// Ambil ID dokumen
$document_id = (int)($_GET['id'] ?? 0);
if ($document_id <= 0) {
    header('Location: index.php');
    exit();
}

// Ambil data dokumen untuk prefilling
$document = null;
$existing_files = [];
try {
    $document = $db->fetch("SELECT * FROM documents WHERE id = ?", [$document_id]);
    if (!$document) {
        $error_message = 'Dokumen tidak ditemukan';
    } else {
        // Cek hak akses: admin boleh edit semua, staff hanya dokumen milik sendiri
        if (!is_admin() && (!isset($_SESSION['user_id']) || $document['created_by'] != $_SESSION['user_id'])) {
            header('Location: index.php?error=forbidden');
            exit();
        }
        // Ambil file yang sudah ada untuk prefilling status
        $files_sql = "SELECT * FROM document_files WHERE document_id = ?";
        $existing_files = $db->fetchAll($files_sql, [$document_id]);
    }
} catch (Exception $e) {
    $error_message = 'Gagal mengambil data dokumen';
}

$error_message = '';
$success_message = '';

// Ambil parameter return untuk redirect setelah edit
$return_page = sanitize_input($_GET['return'] ?? '');
$return_code = sanitize_input($_GET['code'] ?? '');
$return_year = isset($_GET['year']) ? (int)$_GET['year'] : null;

// Ambil ID dokumen
$document_id = (int)($_GET['id'] ?? 0);
if ($document_id <= 0) {
    header('Location: index.php');
    exit();
}

// Ambil data dokumen untuk prefilling
$document = null;
$existing_files = [];
try {
    $document = $db->fetch("SELECT * FROM documents WHERE id = ?", [$document_id]);
    if (!$document) {
        $error_message = 'Dokumen tidak ditemukan';
    } else {
        // Ambil file yang sudah ada untuk prefilling status
        $files_sql = "SELECT * FROM document_files WHERE document_id = ?";
        $existing_files = $db->fetchAll($files_sql, [$document_id]);
    }
} catch (Exception $e) {
    $error_message = 'Gagal mengambil data dokumen';
}

// Proses update saat submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $document) {
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
    $document_order_number_raw = sanitize_input($_POST['document_order_number'] ?? '');

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
    } elseif (empty($document_order_number_raw)) {
        $error_message = 'Urutan dokumen wajib diisi';
    } elseif (!ctype_digit($document_order_number_raw) || (int)$document_order_number_raw <= 0) {
        $error_message = 'Urutan dokumen harus berupa angka lebih besar dari 0';
    } else {
        try {
            $document_order_number = (int)$document_order_number_raw;

            // Pastikan nomor urut dokumen tidak bentrok di rak yang sama (hanya untuk dokumen aktif lain)
            $checkSql = "SELECT COUNT(*) AS cnt 
                         FROM documents 
                         WHERE month_number = ? 
                           AND document_order_number = ? 
                           AND status = 'active'
                           AND id != ?";
            $dupRow = $db->fetch($checkSql, [$month_number, $document_order_number, $document_id]);
            $duplicateCount = (int)($dupRow['cnt'] ?? 0);
            if ($duplicateCount > 0) {
                throw new Exception('Nomor urut dokumen ' . $document_order_number . ' sudah digunakan di rak ' . $month_number . '. Silakan gunakan nomor lain.');
            }

            // Update data utama
            $sql = "UPDATE documents 
                    SET title = ?, full_name = ?, nik = ?, passport_number = ?, birth_date = ?, month_number = ?,
                        marriage_certificate = ?, birth_certificate = ?, divorce_certificate = ?,
                        custody_certificate = ?, citizen_category = ?, document_origin = ?, document_order_number = ?, updated_by = ?, updated_at = NOW()
                    WHERE id = ?";
            $db->execute($sql, [
                $full_name, // sinkronkan title lama dengan nama lengkap
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
                $document_order_number,
                $_SESSION['user_id'],
                $document_id
            ]);

            // Proses file upload dan status dokumen
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

            // Mapping field name ke status field name
            $status_field_mapping = [
                'ktp_file' => 'ktp_status',
                'family_card_file' => 'family_card_status',
                'birth_certificate_file' => 'birth_certificate_status',
                'custody_certificate_file' => 'custody_certificate_status',
                'diploma_file' => 'diploma_status',
                'passport_file' => 'passport_status',
                'marriage_certificate_file' => 'marriage_certificate_status',
                'divorce_certificate_file' => 'divorce_certificate_status'
            ];

            $uploaded_files = 0;
            foreach ($document_types as $field_name => $document_name) {
                // Cek apakah ada file yang diupload
                $has_file_upload = isset($_FILES[$field_name]) && !empty($_FILES[$field_name]['name']);
                
                // Cek status dari form (Ada/Tidak Ada)
                $status_field = $status_field_mapping[$field_name];
                $document_status = sanitize_input($_POST[$status_field] ?? 'none'); // 'exists' atau 'none'
                
                // Hapus semua record lama dengan document_type yang sama
                $old_files_sql = "SELECT * FROM document_files WHERE document_id = ? AND document_type = ?";
                $old_files = $db->fetchAll($old_files_sql, [$document_id, $document_name]);
                
                foreach ($old_files as $old_file) {
                    // Hapus file fisik dari server (hanya jika bukan status placeholder)
                    if (!empty($old_file['file_path']) && $old_file['file_path'] !== 'STATUS_ONLY') {
                        $file_path_to_delete = $old_file['file_path'];
                        
                        // Jika path relatif, coba beberapa lokasi yang mungkin
                        if (!file_exists($file_path_to_delete)) {
                            // Coba di documents/uploads/
                            $alt_path = __DIR__ . '/uploads/' . basename($old_file['file_path']);
                            if (file_exists($alt_path)) {
                                $file_path_to_delete = $alt_path;
                            } else {
                                // Coba di root uploads/
                                $alt_path2 = __DIR__ . '/../uploads/' . basename($old_file['file_path']);
                                if (file_exists($alt_path2)) {
                                    $file_path_to_delete = $alt_path2;
                                } else {
                                    // Coba path relatif dari root project
                                    $alt_path3 = __DIR__ . '/../' . ltrim($old_file['file_path'], '/');
                                    if (file_exists($alt_path3)) {
                                        $file_path_to_delete = $alt_path3;
                                    }
                                }
                            }
                        }
                        
                        if (file_exists($file_path_to_delete)) {
                            delete_file($file_path_to_delete);
                        }
                    }
                    // Hapus record dari database
                    $delete_sql = "DELETE FROM document_files WHERE id = ?";
                    $db->execute($delete_sql, [$old_file['id']]);
                }
                
                // Jika ada file yang diupload, simpan file
                if ($has_file_upload) {
                    $file = $_FILES[$field_name];
                    if (is_allowed_file_type($file['name'])) {
                        // Upload file baru
                        $upload_result = upload_file($file);
                        if ($upload_result['success']) {
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
                } elseif ($document_status === 'exists') {
                    // Jika status "Ada" tapi tidak ada file, buat record placeholder
                    $detail_sql = "INSERT INTO document_files (document_id, document_type, file_path, file_name, file_size, file_type) 
                                   VALUES (?, ?, ?, ?, ?, ?)";
                    $db->execute($detail_sql, [
                        $document_id,
                        $document_name,
                        'STATUS_ONLY', // Placeholder untuk status "Ada" tanpa file
                        'Ada', // file_name untuk status
                        0, // file_size = 0
                        'status' // file_type = 'status'
                    ]);
                }
                // Jika status "none" (Tidak Ada), tidak perlu membuat record (sudah dihapus di atas)
            }

            log_activity($_SESSION['user_id'], 'EDIT_DOCUMENT', "Memperbarui dokumen: $full_name ($uploaded_files file baru)", $document_id);
            
            // Jika ada parameter return, redirect ke halaman tersebut
            if ($return_page === 'locker' && !empty($return_code)) {
                header('Location: ../lockers/detail.php?code=' . urlencode($return_code) . '&success=' . urlencode('Perubahan dokumen berhasil disimpan'));
                exit();
            } elseif ($return_page === 'pemusnahan' && !empty($return_code)) {
                $redirectUrl = '../lockers/detail_pemusnahan.php?code=' . urlencode($return_code);
                if ($return_year) {
                    $redirectUrl .= '&year=' . urlencode($return_year);
                }
                $redirectUrl .= '&success=' . urlencode('Perubahan dokumen berhasil disimpan');
                header('Location: ' . $redirectUrl);
                exit();
            } elseif ($return_page === 'search') {
                // Kembali ke halaman hasil pencarian dengan parameter pencarian yang sama
                $search_params = [];
                if (!empty($_GET['search'])) $search_params[] = 'search=' . urlencode($_GET['search']);
                if (!empty($_GET['full_name'])) $search_params[] = 'full_name=' . urlencode($_GET['full_name']);
                if (!empty($_GET['birth_date'])) $search_params[] = 'birth_date=' . urlencode($_GET['birth_date']);
                if (!empty($_GET['passport_number'])) $search_params[] = 'passport_number=' . urlencode($_GET['passport_number']);
                if (!empty($_GET['category'])) $search_params[] = 'category=' . urlencode($_GET['category']);
                if (!empty($_GET['date_from'])) $search_params[] = 'date_from=' . urlencode($_GET['date_from']);
                if (!empty($_GET['date_to'])) $search_params[] = 'date_to=' . urlencode($_GET['date_to']);
                
                $query_string = !empty($search_params) ? '?' . implode('&', $search_params) : '';
                header('Location: search_results.php' . $query_string . '&success=' . urlencode('Perubahan dokumen berhasil disimpan'));
                exit();
            }
            
            $success_message = 'Perubahan dokumen berhasil disimpan';

            // Refresh data untuk menampilkan nilai terbaru
            $document = $db->fetch("SELECT * FROM documents WHERE id = ?", [$document_id]);
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan saat menyimpan perubahan: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dokumen - Sistem Arsip Dokumen</title>
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
                    <h1 class="h2">Edit Dokumen</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($return_page === 'locker' && !empty($return_code)): ?>
                            <a href="../lockers/detail.php?code=<?php echo urlencode($return_code); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        <?php elseif ($return_page === 'pemusnahan' && !empty($return_code)): ?>
                            <a href="../lockers/detail_pemusnahan.php?code=<?php echo urlencode($return_code); ?><?php echo $return_year ? '&year=' . urlencode($return_year) : ''; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        <?php else: ?>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

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

                <?php if ($document): ?>
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Form Edit Dokumen</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($document['full_name'] ?? ''); ?>" placeholder="Value" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('full_name')"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="nik" class="form-label">NIK <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nik" name="nik" value="<?php echo e($document['nik'] ?? ''); ?>" placeholder="Value" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('nik')"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="invalid-feedback">NIK wajib diisi.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="passport_number" class="form-label">No Passport <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="passport_number" name="passport_number" value="<?php echo e($document['passport_number'] ?? ''); ?>" placeholder="Value" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('passport_number')"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="invalid-feedback">No Passport wajib diisi.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="birth_date" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo e($document['birth_date'] ?? ''); ?>" required>
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="month_number" class="form-label">Kode Lemari <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="month_number" name="month_number" value="<?php echo e($document['month_number'] ?? ''); ?>" placeholder="Kode Lemari" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('month_number')"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="invalid-feedback">Kode Lemari wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="document_order_number" class="form-label">Urutan Dokumen <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number"
                                                       class="form-control"
                                                       id="document_order_number"
                                                       name="document_order_number"
                                                       value="<?php echo e($document['document_order_number'] ?? ''); ?>"
                                                       min="1"
                                                       placeholder="Misal: 1, 2, 3..."
                                                       required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('document_order_number')"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="invalid-feedback">Urutan dokumen wajib diisi dan harus berupa angka.</div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kode Dokumen</label>
                                        <div class="input-group">
                                            <?php
                                                $kodeDokumen = '-';
                                                if (!empty($document['month_number']) && $document['document_order_number'] !== null) {
                                                    $kodeDokumen = $document['month_number'] . '.' . $document['document_order_number'];
                                                }
                                            ?>
                                            <input type="text" class="form-control" value="<?php echo e($kodeDokumen); ?>" readonly>
                                        </div>
                                        <div class="form-text">Otomatis dari Kode Lemari + urutan dokumen.</div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="marriage_certificate" class="form-label">No Surat Nikah</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="marriage_certificate" name="marriage_certificate" value="<?php echo e($document['marriage_certificate'] ?? ''); ?>" placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('marriage_certificate')"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="form-text">Boleh dikosongkan jika tidak ada.</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="birth_certificate" class="form-label">No Akta Lahir</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="birth_certificate" name="birth_certificate" value="<?php echo e($document['birth_certificate'] ?? ''); ?>" placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('birth_certificate')"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="form-text">Boleh dikosongkan jika tidak ada.</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="divorce_certificate" class="form-label">No Surat Cerai</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="divorce_certificate" name="divorce_certificate" value="<?php echo e($document['divorce_certificate'] ?? ''); ?>" placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('divorce_certificate')"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="form-text">Boleh dikosongkan jika tidak ada.</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="custody_certificate" class="form-label">No Surat Hak Asuh</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="custody_certificate" name="custody_certificate" value="<?php echo e($document['custody_certificate'] ?? ''); ?>" placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('custody_certificate')"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="form-text">Boleh dikosongkan jika tidak ada.</div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">KATAGORI DOKUMEN</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="citizen_category" id="wni" value="WNI" <?php echo ($document['citizen_category'] ?? 'WNI') === 'WNI' ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-primary" for="wni">WNI</label>
                                            <input type="radio" class="btn-check" name="citizen_category" id="wna" value="WNA" <?php echo ($document['citizen_category'] ?? '') === 'WNA' ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-secondary" for="wna">WNA</label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="document_origin" class="form-label">Dokumen Berasal <span class="text-danger">*</span></label>
                                        <select class="form-select" id="document_origin" name="document_origin" required>
                                            <option value="">Pilih asal dokumen...</option>
                                            <option value="imigrasi_jakarta_pusat_kemayoran" <?php echo (($document['document_origin'] ?? '') === 'imigrasi_jakarta_pusat_kemayoran') ? 'selected' : ''; ?>>
                                                Imigrasi Jakarta Pusat Kemayoran
                                            </option>
                                            <option value="imigrasi_ulp_semanggi" <?php echo (($document['document_origin'] ?? '') === 'imigrasi_ulp_semanggi') ? 'selected' : ''; ?>>
                                                Imigrasi ULP Semanggi
                                            </option>
                                            <option value="imigrasi_lounge_senayan_city" <?php echo (($document['document_origin'] ?? '') === 'imigrasi_lounge_senayan_city') ? 'selected' : ''; ?>>
                                                Imigrasi Lounge Senayan City
                                            </option>
                                        </select>
                                        <div class="invalid-feedback">Dokumen berasal wajib dipilih.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Upload Dokumen</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Fungsi helper untuk mendapatkan status dokumen
                                    function getDocumentStatus($document_type, $existing_files) {
                                        foreach ($existing_files as $file) {
                                            if ($file['document_type'] === $document_type) {
                                                return 'exists';
                                            }
                                        }
                                        return 'none';
                                    }
                                    
                                    // Mapping document types
                                    $doc_types = [
                                        'ktp_file' => ['label' => 'KTP', 'type' => 'KTP', 'status_field' => 'ktp_status'],
                                        'family_card_file' => ['label' => 'KARTU KELUARGA', 'type' => 'Kartu Keluarga', 'status_field' => 'family_card_status'],
                                        'birth_certificate_file' => ['label' => 'AKTA LAHIR', 'type' => 'Akta Lahir', 'status_field' => 'birth_certificate_status'],
                                        'custody_certificate_file' => ['label' => 'SURAT HAK ASUH ANAK', 'type' => 'Surat Hak Asuh Anak', 'status_field' => 'custody_certificate_status'],
                                        'diploma_file' => ['label' => 'IJAZAH', 'type' => 'Ijazah', 'status_field' => 'diploma_status'],
                                        'passport_file' => ['label' => 'PASPOR', 'type' => 'Paspor', 'status_field' => 'passport_status'],
                                        'marriage_certificate_file' => ['label' => 'SURAT NIKAH', 'type' => 'Surat Nikah', 'status_field' => 'marriage_certificate_status'],
                                        'divorce_certificate_file' => ['label' => 'SURAT CERAI', 'type' => 'Surat Cerai', 'status_field' => 'divorce_certificate_status']
                                    ];
                                    ?>
                                    <div class="row">
                                        <?php foreach ($doc_types as $field_name => $doc_info): 
                                            $current_status = getDocumentStatus($doc_info['type'], $existing_files);
                                        ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><?php echo e($doc_info['label']); ?></label>
                                            <div class="input-group file-upload-group" data-input-name="<?php echo e($field_name); ?>" data-status-field="<?php echo e($doc_info['status_field']); ?>">
                                                <button type="button" class="btn btn-outline-secondary file-upload-trigger">
                                                    <i class="fas fa-paperclip"></i>
                                                </button>
                                                <select class="form-select file-upload-status" name="<?php echo e($doc_info['status_field']); ?>">
                                                    <option value="none" <?php echo $current_status === 'none' ? 'selected' : ''; ?>>Tidak Ada</option>
                                                    <option value="exists" <?php echo $current_status === 'exists' ? 'selected' : ''; ?>>Ada</option>
                                                </select>
                                            </div>
                                            <input type="file" name="<?php echo e($field_name); ?>" class="d-none real-file-input"
                                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <?php if ($return_page === 'locker' && !empty($return_code)): ?>
                                    <a href="../lockers/detail.php?code=<?php echo urlencode($return_code); ?>" class="btn btn-success"><i class="fas fa-arrow-left"></i> Kembali</a>
                                <?php elseif ($return_page === 'pemusnahan' && !empty($return_code)): ?>
                                    <a href="../lockers/detail_pemusnahan.php?code=<?php echo urlencode($return_code); ?><?php echo $return_year ? '&year=' . urlencode($return_year) : ''; ?>" class="btn btn-success"><i class="fas fa-arrow-left"></i> Kembali</a>
                                <?php elseif ($return_page === 'search'): ?>
                                    <?php
                                    $search_params = [];
                                    if (!empty($_GET['search'])) $search_params[] = 'search=' . urlencode($_GET['search']);
                                    if (!empty($_GET['full_name'])) $search_params[] = 'full_name=' . urlencode($_GET['full_name']);
                                    if (!empty($_GET['birth_date'])) $search_params[] = 'birth_date=' . urlencode($_GET['birth_date']);
                                    if (!empty($_GET['passport_number'])) $search_params[] = 'passport_number=' . urlencode($_GET['passport_number']);
                                    if (!empty($_GET['category'])) $search_params[] = 'category=' . urlencode($_GET['category']);
                                    if (!empty($_GET['date_from'])) $search_params[] = 'date_from=' . urlencode($_GET['date_from']);
                                    if (!empty($_GET['date_to'])) $search_params[] = 'date_to=' . urlencode($_GET['date_to']);
                                    $query_string = !empty($search_params) ? '?' . implode('&', $search_params) : '';
                                    ?>
                                    <a href="search_results.php<?php echo $query_string; ?>" class="btn btn-success"><i class="fas fa-arrow-left"></i> Kembali</a>
                                <?php else: ?>
                                <a href="index.php" class="btn btn-success"><i class="fas fa-arrow-left"></i> Kembali</a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearField(id){document.getElementById(id).value='';}

        // Mekanisme upload dokumen:
        // - Select "Tidak Ada / Ada" untuk status dokumen
        // - Klik ikon klip untuk memilih file (opsional)
        document.querySelectorAll('.file-upload-group').forEach(function(group){
            const inputName = group.getAttribute('data-input-name');
            const triggerBtn = group.querySelector('.file-upload-trigger');
            const statusSelect = group.querySelector('.file-upload-status');
            if (!inputName || !triggerBtn || !statusSelect) return;

            let fileInput = document.querySelector('input.real-file-input[name="'+inputName+'"]');
            if (!fileInput){
                fileInput = document.createElement('input');
                fileInput.type='file';
                fileInput.name=inputName;
                fileInput.className='d-none real-file-input';
                fileInput.accept='.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif';
                group.parentNode.appendChild(fileInput);
            }

            // Klik ikon klip => buka file dialog
            triggerBtn.addEventListener('click',function(){
                fileInput.click();
            });

            // Setelah pilih / batalkan file
            fileInput.addEventListener('change',function(){
                const existsOption=statusSelect.querySelector('option[value="exists"]');
                if(fileInput.files && fileInput.files.length>0){
                    // Jika file dipilih, otomatis set status ke "Ada"
                    statusSelect.value='exists';
                    if(existsOption){
                        existsOption.textContent='Ada ('+fileInput.files[0].name+')';
                    }
                }else{
                    // Jika file dibatalkan, kembalikan teks "Ada" ke normal
                    if(existsOption){
                        existsOption.textContent='Ada';
                    }
                }
            });
            
            // Update status saat select berubah (tidak perlu action khusus, sudah ada name attribute)
        });
    </script>
</body>
</html>


