<?php
/**
 * Script migrasi untuk menghapus UNIQUE constraint pada kolom code di tabel lockers.
 * Jalankan sekali via browser atau CLI:
 *   - Browser: http://localhost/PROJECT%20ARSIP%20LOKER/migrations/remove_unique_locker_code.php
 */

session_start();
require_once '../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

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
        echo "Tidak ada UNIQUE index pada kolom code. Tidak ada perubahan yang dilakukan.\n";
        exit(0);
    }

    echo "Menghapus UNIQUE index pada kolom code di tabel lockers...\n";

    // Di MySQL, ketika didefinisikan sebagai `code VARCHAR(10) UNIQUE`,
    // nama index biasanya sama dengan nama kolom, yaitu `code`.
    $db->execute("ALTER TABLE lockers DROP INDEX code");

    echo "Berhasil menghapus UNIQUE index pada kolom code.\n";
    echo "Sekarang kode lemari yang sama boleh dipakai lebih dari sekali.\n";

} catch (Exception $e) {
    echo "Gagal melakukan migrasi: " . $e->getMessage() . "\n";
    exit(1);
}





