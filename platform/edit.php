<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

$error_message = '';
$success_message = '';

// Ambil ID dokumen
$document_id = (int)($_GET['id'] ?? 0);
if ($document_id <= 0) {
    header('Location: documents.php');
    exit();
}

// Ambil data dokumen untuk prefilling
$document = null;
try {
    $document = $db->fetch("SELECT * FROM documents WHERE id = ? AND (description = 'PLATFORM_UPLOAD' OR description LIKE '%PLATFORM_UPLOAD%' OR description LIKE '%Dokumen diimport%')", [$document_id]);
    if (!$document) {
        $error_message = 'Dokumen tidak ditemukan';
    }
} catch (Exception $e) {
    $error_message = 'Gagal mengambil data dokumen';
}

// Proses update saat submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $document) {
    $title = sanitize_input($_POST['title'] ?? '');
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
    $description = sanitize_input($_POST['description'] ?? '');
    
    // Validasi untuk dokumen platform - hanya title wajib
    if (empty($title)) {
        $error_message = 'Nama dokumen harus diisi';
    } else {
        try {
            // Update data dokumen platform
            $sql = "UPDATE documents 
                    SET title = ?, full_name = ?, nik = ?, passport_number = ?, birth_date = ?, month_number = ?,
                        marriage_certificate = ?, birth_certificate = ?, divorce_certificate = ?,
                        custody_certificate = ?, citizen_category = ?, document_origin = ?, 
                        description = ?, updated_by = ?, updated_at = NOW()
                    WHERE id = ?";
            $db->execute($sql, [
                $title,
                $full_name ?: null,
                $nik ?: null,
                $passport_number ?: null,
                $birth_date ?: null,
                $month_number ?: null,
                $marriage_certificate ?: null,
                $birth_certificate ?: null,
                $divorce_certificate ?: null,
                $custody_certificate ?: null,
                $citizen_category ?: 'WNI',
                $document_origin ?: null,
                $description ?: 'PLATFORM_UPLOAD',
                $_SESSION['user_id'],
                $document_id
            ]);

            log_activity($_SESSION['user_id'], 'PLATFORM_EDIT', "Mengedit dokumen platform: $title", $document_id);
            $success_message = 'Perubahan dokumen berhasil disimpan';

            // Refresh data untuk menampilkan nilai terbaru
            $document = $db->fetch("SELECT * FROM documents WHERE id = ?", [$document_id]);
            
            // Redirect ke halaman dokumen dengan tahun yang sesuai
            $year = date('Y', strtotime($document['created_at']));
            header("Location: documents.php?year=$year&success=" . urlencode($success_message));
            exit();
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan saat menyimpan perubahan: ' . $e->getMessage();
        }
    }
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
    <title>Edit Dokumen Platform - Sistem Arsip Dokumen</title>
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
                    <h1 class="h2">Edit Dokumen Platform</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="documents.php?year=<?php echo date('Y', strtotime($document['created_at'] ?? 'now')); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
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
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Form Edit Dokumen Platform</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Nama Dokumen <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo e($document['title'] ?? ''); ?>" 
                                                   placeholder="Nama Dokumen" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('title')"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="invalid-feedback">Nama dokumen wajib diisi.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Nama Lengkap</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?php echo e($document['full_name'] ?? ''); ?>" 
                                                   placeholder="Nama Lengkap">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('full_name')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="nik" class="form-label">NIK</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nik" name="nik" 
                                                   value="<?php echo e($document['nik'] ?? ''); ?>" 
                                                   placeholder="NIK">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('nik')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="passport_number" class="form-label">No Passport</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="passport_number" name="passport_number" 
                                                   value="<?php echo e($document['passport_number'] ?? ''); ?>" 
                                                   placeholder="No Passport">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('passport_number')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="birth_date" class="form-label">Tanggal Lahir</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                                   value="<?php echo e($document['birth_date'] ?? ''); ?>">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="month_number" class="form-label">Kode Lemari</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="month_number" name="month_number" 
                                                   value="<?php echo e($document['month_number'] ?? ''); ?>" 
                                                   placeholder="Kode Lemari">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('month_number')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="marriage_certificate" class="form-label">No Surat Nikah</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="marriage_certificate" name="marriage_certificate" 
                                                       value="<?php echo e($document['marriage_certificate'] ?? ''); ?>" 
                                                       placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('marriage_certificate')"><i class="fas fa-times"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="birth_certificate" class="form-label">No Akta Lahir</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="birth_certificate" name="birth_certificate" 
                                                       value="<?php echo e($document['birth_certificate'] ?? ''); ?>" 
                                                       placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('birth_certificate')"><i class="fas fa-times"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="divorce_certificate" class="form-label">No Surat Cerai</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="divorce_certificate" name="divorce_certificate" 
                                                       value="<?php echo e($document['divorce_certificate'] ?? ''); ?>" 
                                                       placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('divorce_certificate')"><i class="fas fa-times"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="custody_certificate" class="form-label">No Surat Hak Asuh</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="custody_certificate" name="custody_certificate" 
                                                       value="<?php echo e($document['custody_certificate'] ?? ''); ?>" 
                                                       placeholder="Value">
                                                <button class="btn btn-outline-secondary" type="button" onclick="clearField('custody_certificate')"><i class="fas fa-times"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Kategori</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="citizen_category" id="wni" value="WNI" 
                                                   <?php echo (($document['citizen_category'] ?? 'WNI') == 'WNI') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-primary" for="wni">WNI</label>
                                            
                                            <input type="radio" class="btn-check" name="citizen_category" id="wna" value="WNA"
                                                   <?php echo (($document['citizen_category'] ?? 'WNI') == 'WNA') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-secondary" for="wna">WNA</label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="document_origin" class="form-label">Dokumen Berasal</label>
                                        <select class="form-select" id="document_origin" name="document_origin">
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
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Deskripsi</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" 
                                                  placeholder="Masukkan deskripsi dokumen"><?php echo e($document['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-file me-2"></i>File Dokumen</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($document['file_path'])): ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <i class="fas fa-file me-2"></i>
                                                    <strong><?php echo e($document['file_name'] ?? 'File'); ?></strong>
                                                    <small class="text-muted ms-2">(<?php echo format_file_size($document['file_size'] ?? 0); ?>)</small>
                                                </div>
                                                <a href="view_file.php?id=<?php echo $document_id; ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </a>
                                            </div>
                                            <small class="text-muted">File ini diupload melalui platform upload dokumen.</small>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Tidak ada file dokumen</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="documents.php?year=<?php echo date('Y', strtotime($document['created_at'] ?? 'now')); ?>" class="btn btn-success">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
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
        function clearField(fieldId) {
            document.getElementById(fieldId).value = '';
        }
        
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

