<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

$year = isset($_GET['year']) ? trim($_GET['year']) : '';

// Ambil daftar lemari dan pemakaian untuk dokumen pemusnahan per tahun (jika dipilih)
// Query ini mengambil dari dokumen deleted langsung, tidak bergantung pada tabel lockers
// sehingga lemari yang sudah dihapus tetap muncul jika masih ada dokumen deleted
$params = [];
$where_conditions = ["d.status = 'deleted'"];

if ($year !== '') {
    $where_conditions[] = "d.document_year = ?";
    $params[] = $year;
}

$where_clause = implode(' AND ', $where_conditions);

// Query mengambil langsung dari documents, LEFT JOIN dengan lockers untuk max_capacity
// Jika lemari sudah dihapus, max_capacity akan NULL dan kita gunakan default 600
$lockers_raw = $db->fetchAll("
    SELECT 
        d.month_number AS name,
        l.code AS locker_code,
        l.max_capacity,
        COUNT(d.id) AS used_count
    FROM documents d
    LEFT JOIN lockers l ON d.month_number = l.name
    WHERE {$where_clause}
    GROUP BY d.month_number, l.code, l.max_capacity
    HAVING COUNT(d.id) > 0
    ORDER BY d.month_number ASC
", $params);

// Extract kode lemari dari month_number jika locker_code tidak ada (lemari sudah dihapus)
// Logika: A.01 -> A, A1.01 -> A1, A2.02 -> A2
$lockers = [];
foreach ($lockers_raw as $row) {
    $month_number = $row['name'];
    $code = $row['locker_code'];
    
    // Jika tidak ada locker_code (lemari sudah dihapus), extract dari month_number
    if (empty($code) && !empty($month_number)) {
        // Pattern: A.01 -> A, A1.01 -> A1, A2.02 -> A2
        if (preg_match('/^([A-Z])(\d)\./', $month_number, $matches)) {
            $code = $matches[1] . $matches[2]; // A1, B3
        } elseif (preg_match('/^([A-Z])\./', $month_number, $matches)) {
            $code = $matches[1]; // A, B (format lama)
        } else {
            $code = substr($month_number, 0, 1);
        }
    }
    
    $lockers[] = [
        'code' => $code,
        'name' => $month_number,
        'max_capacity' => $row['max_capacity'] ?? 600,
        'used_count' => $row['used_count']
    ];
}

// Sort by code
usort($lockers, function($a, $b) {
    return strcmp($a['code'], $b['code']);
});
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
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="pemusnahan_years.php" class="btn btn-outline-secondary">Kembali</a>
                </div>
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
                                            $capacity = (int)($locker['max_capacity'] ?? 600);
                                            $used = (int)($locker['used_count'] ?? 0);
                                            $percent_raw = $capacity > 0 ? ($used / $capacity) * 100 : 0;
                                            // Tampilkan minimal 1% kalau sudah terisi tapi masih di bawah 1%
                                            $percent = ($used > 0 && $percent_raw < 1) ? 1 : round($percent_raw);
                                            $badgeClass = $percent >= 100 ? 'bg-danger' : ($percent >= 75 ? 'bg-warning text-dark' : 'bg-success');
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo e($locker['code']); ?></td>
                                            <td><?php echo e($locker['name']); ?></td>
                                            <td><?php echo number_format($capacity); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo number_format($used); ?> (<?php echo $percent; ?>%)
                                                </span>
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary w-100"
                                                   href="../lockers/detail_pemusnahan.php?code=<?php echo urlencode($locker['name']); ?>&year=<?php echo urlencode($year); ?>">
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

