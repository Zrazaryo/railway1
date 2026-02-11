<?php
/**
 * Script untuk membuat tabel trash secara otomatis
 * Jalankan script ini sekali untuk setup tabel document_trash dan trash_audit_logs
 */

session_start();
require_once '../config/database.php';

// Initialize database dengan PDO connection langsung
$db = new Database();
$conn = $db->getConnection();

echo "<h2>Setup Tabel Menu Sampah</h2>";
echo "<hr>";

try {
    // PRIORITY 0: Fix schema DULU sebelum buat tabel trash
    echo "<p><strong>0. Mengecek dan memperbaiki schema dokumen table...</strong></p>";
    
    $columns = $db->fetchAll("DESC documents");
    $status_type = null;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            $status_type = $col['Type'];
            break;
        }
    }
    
    if (strpos($status_type, 'ENUM') !== false) {
        echo "<p>Status masih ENUM, melakukan konversi ke VARCHAR...</p>";
        
        // Add temp column
        try {
            $conn->exec("ALTER TABLE documents ADD COLUMN status_temp VARCHAR(20)");
        } catch (Exception $e) {}
        
        // Copy data
        $conn->exec("UPDATE documents SET status_temp = status");
        
        // Drop old
        $conn->exec("ALTER TABLE documents DROP COLUMN status");
        
        // Rename
        $conn->exec("ALTER TABLE documents CHANGE status_temp status VARCHAR(20) DEFAULT 'active' COMMENT 'Status: active, archived, deleted, trashed'");
        
        // Add index
        try {
            $conn->exec("ALTER TABLE documents ADD INDEX idx_documents_status (status)");
        } catch (Exception $e) {}
        
        echo "<p style='color:green;'>✓ Schema dokumen berhasil diperbaiki (ENUM → VARCHAR)</p>";
    } else {
        echo "<p style='color:green;'>✓ Schema dokumen sudah benar (VARCHAR)</p>";
    }
    
    echo "<br>";
    
    // 1. Buat tabel document_trash
    echo "<p><strong>1. Membuat tabel document_trash...</strong></p>";
    
    $sql1 = "CREATE TABLE IF NOT EXISTS document_trash (
        id INT PRIMARY KEY AUTO_INCREMENT,
        original_document_id INT NOT NULL COMMENT 'ID dokumen asli',
        title VARCHAR(255) COMMENT 'Judul/Nama Dokumen',
        full_name VARCHAR(255) COMMENT 'Nama Lengkap',
        nik VARCHAR(16) COMMENT 'NIK',
        passport_number VARCHAR(20) COMMENT 'Nomor Paspor',
        document_number VARCHAR(50) COMMENT 'Nomor Dokumen',
        document_year INT COMMENT 'Tahun Dokumen',
        month_number VARCHAR(20) COMMENT 'Bulan/Lemari',
        locker_code VARCHAR(10) COMMENT 'Kode Lemari',
        locker_name VARCHAR(100) COMMENT 'Nama Lemari',
        citizen_category VARCHAR(100) COMMENT 'Kategori Warga Negara',
        document_origin VARCHAR(50) COMMENT 'Asal Dokumen',
        file_path VARCHAR(500) COMMENT 'Path File Dokumen',
        description TEXT COMMENT 'Deskripsi Dokumen',
        deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu dihapus',
        deleted_by INT COMMENT 'ID User yang menghapus',
        restore_deadline DATETIME COMMENT 'Batas waktu restore (30 hari)',
        document_data LONGTEXT COMMENT 'Data dokumen lengkap (JSON)',
        is_restored TINYINT DEFAULT 0 COMMENT 'Status apakah sudah di-restore',
        restored_at TIMESTAMP NULL COMMENT 'Waktu di-restore',
        restored_by INT COMMENT 'ID User yang restore',
        status VARCHAR(20) DEFAULT 'in_trash' COMMENT 'Status: in_trash, restored, permanently_deleted',
        
        KEY idx_deleted_at (deleted_at),
        KEY idx_restore_deadline (restore_deadline),
        KEY idx_original_document_id (original_document_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql1);
    echo "<p style='color:green;'>✓ Tabel document_trash berhasil dibuat</p>";
    
    // 2. Buat tabel trash_audit_logs
    echo "<p><strong>2. Membuat tabel trash_audit_logs...</strong></p>";
    
    $sql2 = "CREATE TABLE IF NOT EXISTS trash_audit_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        document_trash_id INT NOT NULL,
        action VARCHAR(50) COMMENT 'moved_to_trash, restored, permanently_deleted',
        user_id INT,
        action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        
        KEY idx_action_time (action_time),
        KEY idx_document_trash_id (document_trash_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql2);
    echo "<p style='color:green;'>✓ Tabel trash_audit_logs berhasil dibuat</p>";
    
    // 3. Update tabel documents
    echo "<p><strong>3. Mengupdate tabel documents...</strong></p>";
    
    // Cek apakah kolom status masih ENUM atau sudah VARCHAR
    $columns = $db->fetchAll("DESC documents");
    $status_type = null;
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            $status_type = $col['Type'];
            break;
        }
    }
    
    if (strpos($status_type, 'ENUM') !== false) {
        // Ubah ENUM menjadi VARCHAR agar bisa menyimpan nilai baru 'trashed'
        $sql3 = "ALTER TABLE documents MODIFY COLUMN status VARCHAR(20) DEFAULT 'active' COMMENT 'Status dokumen: active, trashed, deleted, archived'";
        $conn->exec($sql3);
        echo "<p style='color:green;'>✓ Kolom status di tabel documents berhasil diupdate dari ENUM ke VARCHAR</p>";
    } else {
        echo "<p style='color:blue;'>ℹ Kolom status sudah dalam format VARCHAR</p>";
    }
    
    // 4. Add index jika belum ada
    echo "<p><strong>4. Membuat index untuk optimasi...</strong></p>";
    
    // Cek apakah index sudah ada
    $indexes = $db->fetchAll("SHOW INDEX FROM documents WHERE Column_name = 'status'");
    
    if (count($indexes) === 0) {
        try {
            $sql4 = "ALTER TABLE documents ADD INDEX idx_documents_status (status)";
            $conn->exec($sql4);
            echo "<p style='color:green;'>✓ Index idx_documents_status berhasil dibuat</p>";
        } catch (Exception $e) {
            echo "<p style='color:orange;'>⚠ Index mungkin sudah ada atau error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color:blue;'>ℹ Index idx_documents_status sudah ada</p>";
    }
    
    echo "<hr>";
    echo "<h3 style='color:green;'>✓ Setup Selesai!</h3>";
    echo "<p>Tabel Menu Sampah telah berhasil dibuat. Silakan:</p>";
    echo "<ol>";
    echo "<li><a href='../includes/sidebar.php'>Refresh halaman</a> atau logout/login kembali</li>";
    echo "<li>Pergi ke Menu Sampah di sidebar admin</li>";
    echo "<li>Mulai menggunakan fitur Menu Sampah</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<p>Silakan hubungi administrator sistem.</p>";
} catch (Exception $e) {
    echo "<h3 style='color:red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<p>Silakan hubungi administrator sistem.</p>";
}
?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h2 { color: #333; }
    h3 { margin-top: 20px; }
    p { line-height: 1.6; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
