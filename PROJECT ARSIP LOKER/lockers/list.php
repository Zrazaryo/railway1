<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

try {
    $tableCheck = $db->fetch("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'lockers'", [DB_NAME]);
    if (!$tableCheck || (int)$tableCheck['cnt'] === 0) {
        throw new Exception('Tabel "lockers" belum dibuat. Jalankan skrip SQL update_lockers_schema.sql di folder utama project.');
    }

    $sql = "
        SELECT 
            l.id,
            l.code,
            l.name,
            l.max_capacity,
            COUNT(d.id) AS used_count
        FROM lockers l
        LEFT JOIN documents d 
            ON d.month_number = l.code 
            AND d.status = 'active'
        GROUP BY l.id, l.code, l.name, l.max_capacity
        ORDER BY l.code ASC
    ";
    $lockers = $db->fetchAll($sql);
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $lockers = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lemari Dokumen - Sistem Arsip Dokumen</title>
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
                <h1 class="h2">Lemari Dokumen</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message) && $error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo e($error_message); ?>
                </div>
            <?php else: ?>
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-archive me-2"></i>
                            Daftar Lemari (A s/d Z)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <th style="width: 80px;">No</th>
                                    <th>Kode Lemari</th>
                                    <th>Nama Lemari</th>
                                    <th>Max Capacity</th>
                                    <th>Used</th>
                                    <th style="width: 120px;">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($lockers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Data lemari belum tersedia.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($lockers as $locker): ?>
                                        <?php
                                        $used = (int)$locker['used_count'];
                                        $max = (int)$locker['max_capacity'];
                                        $percent_raw = $max > 0 ? ($used / $max) * 100 : 0;
                                        $percent_display = ($used > 0 && $percent_raw < 1) ? 1 : round($percent_raw);
                                        $badge_class = 'bg-success';
                                        if ($percent_display >= 90) {
                                            $badge_class = 'bg-danger';
                                        } elseif ($percent_display >= 70) {
                                            $badge_class = 'bg-warning text-dark';
                                        }
                                        ?>
                                        <tr>
                                            <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                            <td class="fw-semibold"><?php echo e(substr($locker['code'], 0, 1)); ?></td>
                                            <td><?php echo e($locker['name']); ?></td>
                                            <td><?php echo number_format($max); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo number_format($used); ?> (<?php echo $percent_display; ?>%)
                                                </span>
                                            </td>
                                            <td>
                                                <a href="detail.php?code=<?php echo urlencode($locker['code']); ?>"
                                                   class="btn btn-sm btn-info" title="Lihat Detail Lemari">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">
                            Kapasitas setiap lemari adalah <strong>600 dokumen</strong>.
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

