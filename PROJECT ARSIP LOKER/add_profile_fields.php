<?php
// Script untuk menambahkan kolom profil tambahan ke tabel users
require_once 'config/database.php';

try {
    echo "Memulai penambahan kolom profil...\n";
    
    // Cek apakah kolom sudah ada
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'birth_date'");
    if (empty($columns)) {
        // Tambahkan kolom baru
        $sql = "ALTER TABLE users 
                ADD COLUMN birth_date DATE NULL AFTER email,
                ADD COLUMN address TEXT NULL AFTER birth_date,
                ADD COLUMN phone_number VARCHAR(20) NULL AFTER address,
                ADD COLUMN division_position VARCHAR(100) NULL AFTER phone_number,
                ADD COLUMN bio_status TEXT NULL AFTER division_position,
                ADD COLUMN profile_picture VARCHAR(255) NULL AFTER bio_status";
        
        $db->execute($sql);
        echo "✅ Kolom profil berhasil ditambahkan\n";
    } else {
        echo "ℹ️ Kolom profil sudah ada\n";
    }
    
    echo "\n🎉 Selesai! Halaman profil siap digunakan.\n";
    
} catch (Exception $e) {
    // Jika error karena kolom sudah ada, itu tidak masalah
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️ Kolom sudah ada, tidak perlu ditambahkan lagi\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>







