<?php
// Script untuk menjalankan update database schema
require_once 'config/database.php';

try {
    echo "Memulai update database schema...\n";
    
    // Tambahkan field baru ke tabel documents
    $sql1 = "ALTER TABLE documents 
             ADD COLUMN full_name VARCHAR(200) NULL AFTER title,
             ADD COLUMN birth_date DATE NULL AFTER full_name,
             ADD COLUMN passport_number VARCHAR(50) NULL AFTER birth_date";
    
    $db->execute($sql1);
    echo "✅ Field baru berhasil ditambahkan ke tabel documents\n";
    
    // Tambahkan index untuk optimasi pencarian
    $sql2 = "CREATE INDEX idx_documents_full_name ON documents(full_name)";
    $db->execute($sql2);
    echo "✅ Index untuk full_name berhasil dibuat\n";
    
    $sql3 = "CREATE INDEX idx_documents_birth_date ON documents(birth_date)";
    $db->execute($sql3);
    echo "✅ Index untuk birth_date berhasil dibuat\n";
    
    $sql4 = "CREATE INDEX idx_documents_passport_number ON documents(passport_number)";
    $db->execute($sql4);
    echo "✅ Index untuk passport_number berhasil dibuat\n";
    
    echo "\n🎉 Update database schema berhasil diselesaikan!\n";
    echo "Sekarang Anda dapat menggunakan fitur pencarian baru dengan field:\n";
    echo "- Nama Lengkap\n";
    echo "- Tanggal Lahir\n";
    echo "- Nomor Paspor\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Pastikan database sudah dibuat dan koneksi berjalan dengan baik.\n";
}
?>



























