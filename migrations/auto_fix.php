<?php
/**
 * Script otomatis untuk menghapus UNIQUE constraint
 * Jalankan sekali: http://localhost/PROJECT%20ARSIP%20LOKER/migrations/auto_fix.php
 */

require_once '../config/database.php';

try {
    // Cek dan hapus semua UNIQUE index pada kolom code (kecuali PRIMARY)
    $indexes = $db->fetchAll("SHOW INDEX FROM lockers WHERE Column_name = 'code' AND Non_unique = 0 AND Key_name != 'PRIMARY'");
    
    if (empty($indexes)) {
        echo "SUCCESS: Tidak ada UNIQUE constraint pada kolom code.\n";
        exit(0);
    }
    
    foreach ($indexes as $idx) {
        $indexName = $idx['Key_name'];
        try {
            $db->execute("ALTER TABLE lockers DROP INDEX `{$indexName}`");
            echo "SUCCESS: Berhasil menghapus index '{$indexName}'\n";
        } catch (Exception $e) {
            // Coba tanpa backtick
            try {
                $db->execute("ALTER TABLE lockers DROP INDEX {$indexName}");
                echo "SUCCESS: Berhasil menghapus index '{$indexName}'\n";
            } catch (Exception $e2) {
                echo "ERROR: Gagal menghapus index '{$indexName}': " . $e2->getMessage() . "\n";
            }
        }
    }
    
    // Verifikasi
    $remaining = $db->fetchAll("SHOW INDEX FROM lockers WHERE Column_name = 'code' AND Non_unique = 0 AND Key_name != 'PRIMARY'");
    if (empty($remaining)) {
        echo "SUCCESS: Verifikasi berhasil. UNIQUE constraint sudah dihapus.\n";
    } else {
        echo "WARNING: Masih ada " . count($remaining) . " UNIQUE index yang tersisa.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}



