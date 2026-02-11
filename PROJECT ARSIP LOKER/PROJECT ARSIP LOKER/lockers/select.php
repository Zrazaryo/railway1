<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Halaman ini bisa diakses admin & staff
require_login();

// Pastikan tabel lockers sudah ada. Jika belum, tampilkan pesan ramah.
try {
    // Cek apakah tabel lockers ada
    $tableCheck = $db->fetch("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'lockers'", [DB_NAME]);
    if (!$tableCheck || (int)$tableCheck['cnt'] === 0) {
        throw new Exception('Tabel \"lockers\" belum dibuat. Jalankan skrip SQL update_lockers_schema.sql di folder utama project.');
    }

    // Auto seed lemari A1-A10 s/d Z1-Z10 jika tabel masih kosong
    $existingCount = $db->fetch("SELECT COUNT(*) AS total FROM lockers");
    if ((int)($existingCount['total'] ?? 0) === 0) {
        $letters = range('A', 'Z');
        foreach ($letters as $letter) {
            for ($i = 1; $i <= 10; $i++) {
                $code = $letter . $i; // Contoh: A1
                $name = $letter . '.' . str_pad((string)$i, 2, '0', STR_PAD_LEFT); // Contoh: A.01
                $db->execute(
                    "INSERT INTO lockers (code, name, max_capacity) VALUES (?, ?, ?)",
                    [$code, $name, 600]
                );
            }
        }
    }

    // Ambil data lemari + jumlah dokumen yang memakai lemari tsb
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
        ORDER BY 
            LEFT(l.code, 1) ASC,
            CAST(SUBSTRING(l.code, 2) AS UNSIGNED) ASC
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
    <title>Pilih Lemari - Sistem Arsip Dokumen</title>
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
                <h1 class="h2">Pilih Lemari Dokumen</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-outline-secondary" onclick="history.back()" title="Kembali">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
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
                            Daftar Lemari (A1-A10 s/d Z1-Z10)
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
                                    <th style="width: 140px;">Aksi</th>
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
                                        // Tampilkan minimal 1% kalau sudah terisi tapi masih di bawah 1%
                                        $percent_display = ($used > 0 && $percent_raw < 1) ? 1 : round($percent_raw);
                                        $is_full = $used >= $max;
                                        $badge_class = 'bg-success';
                                        if ($percent_display >= 90) {
                                            $badge_class = 'bg-danger';
                                        } elseif ($percent_display >= 70) {
                                            $badge_class = 'bg-warning text-dark';
                                        }
                                        ?>
                                        <tr>
                                            <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                            <td class="fw-semibold"><?php echo e($locker['code']); ?></td>
                                            <td><?php echo e($locker['name']); ?></td>
                                            <td><?php echo number_format($max); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo number_format($used); ?> (<?php echo $percent_display; ?>%)
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($is_full): ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Lemari penuh">
                                                        <i class="fas fa-lock"></i> Penuh
                                                    </button>
                                                <?php else: ?>
                                                    <a href="../documents/add.php?locker=<?php echo urlencode($locker['code']); ?>"
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-check"></i> Pilih
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">
                            Kapasitas setiap lemari adalah <strong>600 dokumen</strong>. Jika sudah penuh, lemari akan terkunci dan tidak bisa dipilih.
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
