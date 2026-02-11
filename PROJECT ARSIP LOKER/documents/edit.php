<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Hanya pengguna login (admin) yang boleh mengedit
require_login();
if (!is_admin()) {
    header('Location: ../dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Ambil ID dokumen
$document_id = (int)($_GET['id'] ?? 0);
if ($document_id <= 0) {
    header('Location: index.php');
    exit();
}

// Ambil data dokumen untuk prefilling
$document = null;
try {
    $document = $db->fetch("SELECT * FROM documents WHERE id = ?", [$document_id]);
    if (!$document) {
        $error_message = 'Dokumen tidak ditemukan';
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
    } else {
        try {
            // Update data utama
            $sql = "UPDATE documents 
                    SET title = ?, full_name = ?, nik = ?, passport_number = ?, birth_date = ?, month_number = ?,
                        marriage_certificate = ?, birth_certificate = ?, divorce_certificate = ?,
                        custody_certificate = ?, citizen_category = ?, document_origin = ?, updated_by = ?, updated_at = NOW()
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
                $_SESSION['user_id'],
                $document_id
            ]);

            // Proses file upload baru (jika ada)
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
                        // Hapus file lama dengan document_type yang sama (jika ada)
                        $old_files_sql = "SELECT * FROM document_files WHERE document_id = ? AND document_type = ?";
                        $old_files = $db->fetchAll($old_files_sql, [$document_id, $document_name]);
                        
                        foreach ($old_files as $old_file) {
                            // Hapus file fisik dari server
                            if (!empty($old_file['file_path'])) {
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
                }
            }

            log_activity($_SESSION['user_id'], 'EDIT_DOCUMENT', "Memperbarui dokumen: $full_name ($uploaded_files file baru)", $document_id);
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
                        <a href="index.php" class="btn btn-outline-secondary">
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

                                    <div class="mb-3">
                                        <label for="month_number" class="form-label">Kode Lemari <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="month_number" name="month_number" value="<?php echo e($document['month_number'] ?? ''); ?>" placeholder="Kode Lemari" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearField('month_number')"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="invalid-feedback">Kode Lemari wajib diisi.</div>
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
                                    <div class="row">
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
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="index.php" class="btn btn-success"><i class="fas fa-arrow-left"></i> Kembali</a>
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
        // - Select "Tidak Ada / Ada" hanya penanda (tidak membuka dialog file)
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
                    statusSelect.value='exists';
                    if(existsOption){
                        existsOption.textContent='Ada ('+fileInput.files[0].name+')';
                    }
                }else{
                    if(existsOption){
                        existsOption.textContent='Ada';
                    }
                }
            });
        });
    </script>
</body>
</html>


