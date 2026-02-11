<?php
/**
 * Script untuk menambahkan kolom password_plain ke tabel users
 * Jalankan file ini sekali untuk menambahkan kolom password_plain
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login dan role admin
require_admin();

$error_message = '';
$success_message = '';

try {
    // Cek apakah kolom password_plain sudah ada
    $check_column = $db->fetch("SHOW COLUMNS FROM users LIKE 'password_plain'");
    
    if ($check_column) {
        $success_message = 'Kolom password_plain sudah ada di tabel users.';
    } else {
        // Tambahkan kolom password_plain
        $db->execute("ALTER TABLE users ADD COLUMN password_plain TEXT NULL AFTER password");
        $success_message = 'Kolom password_plain berhasil ditambahkan ke tabel users.';
    }
} catch (Exception $e) {
    $error_message = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Password Plain Column - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Add Password Plain Column
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo e($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo e($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Informasi:</h6>
                            <ul class="mb-0">
                                <li>Kolom <code>password_plain</code> digunakan untuk menyimpan password asli (dengan enkripsi base64)</li>
                                <li>Password asli hanya bisa dilihat untuk user yang dibuat/di-update setelah kolom ini ditambahkan</li>
                                <li>Untuk user yang sudah ada, password_plain akan NULL sampai password di-update</li>
                            </ul>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

















