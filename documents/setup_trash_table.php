<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/trash_helper.php';

// Cek login
require_login();

// Hanya admin yang bisa akses
if ($_SESSION['role'] !== 'admin') {
    die('Akses ditolak. Hanya admin yang bisa mengakses halaman ini.');
}

$db = new Database();

// Setup tabel trash
$result = ensure_trash_tables_exist($db);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Menu Sampah</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Setup Menu Sampah</h5>
                </div>
                <div class="card-body">
                    <?php if ($result['success']): ?>
                        <div class="alert alert-success" role="alert">
                            <h4 class="alert-heading">Berhasil!</h4>
                            <p><?php echo htmlspecialchars($result['message']); ?></p>
                            <hr>
                            <p class="mb-0">Menu Sampah sudah siap digunakan. <a href="../platform/index.php" class="alert-link">Kembali ke Dashboard</a></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger" role="alert">
                            <h4 class="alert-heading">Gagal!</h4>
                            <p><?php echo htmlspecialchars($result['message']); ?></p>
                            <hr>
                            <p class="mb-0">Hubungi administrator sistem untuk bantuan.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <h6>Informasi Setup:</h6>
                        <ul>
                            <li>Tabel <code>document_trash</code> dibuat untuk menyimpan dokumen yang dihapus</li>
                            <li>Tabel <code>trash_audit_logs</code> dibuat untuk mencatat aktivitas trash</li>
                            <li>Dokumen yang dihapus akan tersimpan selama 30 hari sebelum dihapus permanent</li>
                            <li>User dapat memulihkan dokumen dari Menu Sampah dalam 30 hari</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
?>
