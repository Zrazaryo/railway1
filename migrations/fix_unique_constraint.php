<?php
/**
 * Script migrasi untuk menghapus UNIQUE constraint pada kolom code di tabel lockers.
 * Jalankan via browser: http://localhost/PROJECT%20ARSIP%20LOKER/migrations/fix_unique_constraint.php
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fix UNIQUE Constraint</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Migrasi Database - Hapus UNIQUE Constraint</h2>
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
                echo '<div class="success">';
                echo '✓ Tidak ada UNIQUE index pada kolom code. Constraint sudah dihapus atau tidak pernah ada.';
                echo '</div>';
            } else {
                echo '<div class="info">';
                echo 'Menemukan UNIQUE index pada kolom code. Menghapus constraint...';
                echo '</div>';

                // Hapus UNIQUE index
                $db->execute("ALTER TABLE lockers DROP INDEX code");

                echo '<div class="success">';
                echo '✓ <strong>Berhasil!</strong> UNIQUE index pada kolom code telah dihapus.<br>';
                echo 'Sekarang kode lemari yang sama boleh dipakai lebih dari sekali.';
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '✗ <strong>Gagal melakukan migrasi:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        <a href="../lockers/select.php" class="btn">Kembali ke Daftar Lemari</a>
    </div>
</body>
</html>



