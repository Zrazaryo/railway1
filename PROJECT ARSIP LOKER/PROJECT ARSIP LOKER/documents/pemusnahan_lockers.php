<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

$year = isset($_GET['year']) ? trim($_GET['year']) : '';

// Ambil daftar lemari dan pemakaian untuk dokumen pemusnahan per tahun (jika dipilih)
$params = [];
$year_filter_sql = '';
if ($year !== '') {
    $year_filter_sql = "AND d.document_year = ?";
    $params[] = $year;
}

$lockers = $db->fetchAll("
    SELECT l.code, l.name, l.max_capacity, COALESCE(u.used_count, 0) AS used_count
    FROM lockers l
    LEFT JOIN (
        SELECT month_number AS code, COUNT(*) AS used_count
        FROM documents d
        WHERE d.status = 'deleted' {$year_filter_sql}
        GROUP BY month_number
    ) u ON u.code = l.code
    ORDER BY l.code ASC
", $params);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Lemari - Pemusnahan</title>
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
                <div>
                    <h1 class="h4 mb-0">Daftar Lemari Pemusnahan</h1>
                    <?php if ($year !== ''): ?>
                        <small class="text-muted">Tahun dokumen: <?php echo htmlspecialchars($year); ?></small>
                    <?php endif; ?>
                </div>
                <a href="pemusnahan_years.php" class="btn btn-outline-secondary">Kembali</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">No</th>
                                    <th>kode lemari (A-Z)</th>
                                    <th>Rak lemari (A.01-10 , Z.01-10)</th>
                                    <th style="width:140px">Max Capacity</th>
                                    <th style="width:120px">Used</th>
                                    <th style="width:100px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lockers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Belum ada lemari.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($lockers as $locker): ?>
                                        <?php
                                            $capacity = $locker['max_capacity'] ?? 600;
                                            $used = $locker['used_count'] ?? 0;
                                            $percent = $capacity > 0 ? round(($used / $capacity) * 100) : 0;
                                            $badgeClass = $percent >= 100 ? 'bg-danger' : ($percent >= 75 ? 'bg-warning text-dark' : 'bg-success');
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo e(substr($locker['code'], 0, 1)); ?></td>
                                            <td><?php echo e($locker['name']); ?></td>
                                            <td><?php echo number_format($capacity); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo number_format($used); ?> (<?php echo $percent; ?>%)
                                                </span>
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary w-100" href="../lockers/detail.php?code=<?php echo urlencode($locker['code']); ?>">
                                                    Detail
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

