<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

// Ambil daftar tahun dokumen pemusnahan (status deleted)
$years = $db->fetchAll("SELECT DISTINCT document_year FROM documents WHERE status = 'deleted' AND document_year IS NOT NULL ORDER BY document_year DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Tahun Dokumen - Lemari Pemusnahan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h4 mb-0">Pilih Tahun Dokumen</h1>
                <a href="pemusnahan.php" class="btn btn-outline-secondary">Kembali</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">No</th>
                                    <th>Tahun</th>
                                    <th style="width:160px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($years)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Belum ada dokumen pemusnahan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($years as $y): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($y['document_year']); ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-primary" href="pemusnahan_lockers.php?year=<?php echo urlencode($y['document_year']); ?>">
                                                    Lihat detail lemari
                                                </a>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

