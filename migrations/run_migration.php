<?php
/**
 * Script untuk menjalankan migrasi menghapus UNIQUE constraint pada kolom code di tabel lockers
 * Akses via browser: http://localhost/PROJECT%20ARSIP%20LOKER/migrations/run_migration.php
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Database - Hapus UNIQUE Constraint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Migrasi Database</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Cek apakah index UNIQUE pada kolom code masih ada
                            $sql = "
                                SELECT COUNT(*) AS cnt
                                FROM information_schema.statistics
                                WHERE table_schema = ?
                                  AND table_name = 'lockers'
                                  AND column_name = 'code'
                                  AND non_unique = 0
                            ";

                            $row = $db->fetch($sql, [DB_NAME]);
                            $has_unique = (int)($row['cnt'] ?? 0) > 0;

                            if (!$has_unique) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="fas fa-check-circle me-2"></i>';
                                echo 'Tidak ada UNIQUE index pada kolom code. Constraint sudah dihapus atau tidak pernah ada.';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-info">';
                                echo '<i class="fas fa-info-circle me-2"></i>';
                                echo 'Menemukan UNIQUE index pada kolom code. Menghapus constraint...';
                                echo '</div>';

                                // Hapus UNIQUE index
                                $db->execute("ALTER TABLE lockers DROP INDEX code");

                                echo '<div class="alert alert-success mt-3">';
                                echo '<i class="fas fa-check-circle me-2"></i>';
                                echo '<strong>Berhasil!</strong> UNIQUE index pada kolom code telah dihapus.';
                                echo '<br><br>';
                                echo 'Sekarang kode lemari yang sama boleh dipakai lebih dari sekali.';
                                echo '</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">';
                            echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                            echo '<strong>Gagal melakukan migrasi:</strong> ' . htmlspecialchars($e->getMessage());
                            echo '</div>';
                        }
                        ?>
                        <div class="mt-4">
                            <a href="../lockers/select.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Lemari
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html>



