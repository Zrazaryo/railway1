<?php
/**
 * Halaman untuk menambah lemari baru
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Hanya admin yang bisa menambah lemari
require_login();
if (!is_admin()) {
    header('Location: select.php?error=' . urlencode('Akses ditolak. Hanya admin yang bisa menambah lemari.'));
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize_input($_POST['code'] ?? '');
    $name = sanitize_input($_POST['name'] ?? '');
    $max_capacity = (int)($_POST['max_capacity'] ?? 800);
    
    // Validasi
    if (empty($code)) {
        $error_message = 'Kode lemari harus diisi.';
    } elseif (empty($name)) {
        $error_message = 'Kode rak harus diisi.';
    } elseif ($max_capacity <= 0) {
        $error_message = 'Max capacity harus lebih dari 0.';
    } else {
        try {
            // Cek apakah Kode Rak sudah ada (tidak boleh duplikat)
            $existing = $db->fetch(
                "SELECT id, code, name FROM lockers WHERE name = ?",
                [$name]
            );
            
            if ($existing) {
                $error_message = "Kode Rak '{$name}' sudah digunakan. Silakan gunakan Kode Rak yang berbeda.";
            } else {
                // Mulai 2025-xx: kode lemari boleh dipakai berulang,
                // jadi kita tidak lagi melakukan validasi unik di level aplikasi.
                // Validasi cukup memastikan field terisi.

                // Insert lemari baru
                $db->execute(
                    "INSERT INTO lockers (code, name, max_capacity) VALUES (?, ?, ?)",
                    [$code, $name, $max_capacity]
                );
                
                // Log aktivitas
                log_activity($_SESSION['user_id'], 'add_locker', "Menambah lemari baru: {$code} ({$name})", null);
                
                header('Location: select.php?success=' . urlencode("Berhasil menambah lemari baru: {$name}."));
                exit();
            }
            
        } catch (PDOException $e) {
            // Kalau di database masih ada constraint UNIQUE lama, tampilkan pesan yang menjelaskan
            if ($e->getCode() == 23000) {
                $error_message = 'Gagal menambah lemari karena masih ada constraint UNIQUE di database untuk kolom kode. Silakan jalankan skrip migrasi untuk mengizinkan kode lemari ganda.';
            } else {
                $error_message = 'Gagal menambah lemari: ' . $e->getMessage();
            }
        } catch (Exception $e) {
            $error_message = 'Gagal menambah lemari: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Lemari - Sistem Arsip Dokumen</title>
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
                <h1 class="h2">Tambah Lemari Baru</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="select.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        Form Tambah Lemari
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">
                                    Kode Lemari <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="code" 
                                       name="code" 
                                       value="<?php echo e($_POST['code'] ?? ''); ?>"
                                       placeholder="Contoh: A01, B05, Z10" 
                                       required>
                                <small class="form-text text-muted">
                                    Format: Huruf diikuti angka (contoh: A01, B05, Z10)
                                </small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    Kode Rak <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo e($_POST['name'] ?? ''); ?>"
                                       placeholder="Contoh: A.01, B.05, Z.10" 
                                       required>
                                <small class="form-text text-muted">
                                    Format: Huruf titik angka (contoh: A.01, B.05, Z.10)
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_capacity" class="form-label">
                                    Max Capacity <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="max_capacity" 
                                       name="max_capacity" 
                                       value="<?php echo e($_POST['max_capacity'] ?? 800); ?>"
                                       min="1" 
                                       required>
                                <small class="form-text text-muted">
                                    Kapasitas maksimal dokumen yang bisa disimpan
                                </small>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Simpan
                            </button>
                            <a href="select.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

