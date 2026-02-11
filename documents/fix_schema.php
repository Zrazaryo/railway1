<?php
/**
 * Fix Schema - Update kolom status di tabel documents
 * Ubah dari ENUM menjadi VARCHAR untuk support 'trashed' status
 */

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>🔧 Fixing Database Schema</h2>";
echo "<hr>";

try {
    // Step 1: Check current status column type
    echo "<p><strong>Step 1: Check kolom status di tabel documents...</strong></p>";
    $columns = $db->fetchAll("DESC documents");
    $status_type = null;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            $status_type = $col['Type'];
            break;
        }
    }
    
    echo "<p>Current status type: <code>$status_type</code></p>";
    
    if (strpos($status_type, 'ENUM') !== false) {
        echo "<p style='color:orange;'>⚠ Status masih ENUM - perlu diubah ke VARCHAR</p>";
        
        // Step 2: Buat kolom temporary
        echo "<p><strong>Step 2: Membuat kolom temporary...</strong></p>";
        try {
            $conn->exec("ALTER TABLE documents ADD COLUMN status_temp VARCHAR(20)");
            echo "<p style='color:green;'>✓ Kolom status_temp dibuat</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                throw $e;
            }
            echo "<p style='color:blue;'>ℹ Kolom status_temp sudah ada</p>";
        }
        
        // Step 3: Copy data dari ENUM ke VARCHAR
        echo "<p><strong>Step 3: Mengcopy data dari status ENUM ke status_temp...</strong></p>";
        $conn->exec("UPDATE documents SET status_temp = status");
        echo "<p style='color:green;'>✓ Data berhasil dicopy</p>";
        
        // Step 4: Drop kolom status lama
        echo "<p><strong>Step 4: Menghapus kolom status lama...</strong></p>";
        $conn->exec("ALTER TABLE documents DROP COLUMN status");
        echo "<p style='color:green;'>✓ Kolom status lama dihapus</p>";
        
        // Step 5: Rename status_temp ke status
        echo "<p><strong>Step 5: Mengubah nama kolom...</strong></p>";
        $conn->exec("ALTER TABLE documents CHANGE status_temp status VARCHAR(20) DEFAULT 'active' COMMENT 'Status: active, archived, deleted, trashed'");
        echo "<p style='color:green;'>✓ Kolom status_temp direname ke status</p>";
        
        // Step 6: Add index
        echo "<p><strong>Step 6: Menambah index untuk optimasi...</strong></p>";
        try {
            $conn->exec("ALTER TABLE documents ADD INDEX idx_documents_status (status)");
            echo "<p style='color:green;'>✓ Index idx_documents_status berhasil ditambah</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                throw $e;
            }
            echo "<p style='color:blue;'>ℹ Index sudah ada</p>";
        }
        
        // Step 7: Verify
        echo "<p><strong>Step 7: Verifikasi perubahan...</strong></p>";
        $columns = $db->fetchAll("DESC documents");
        foreach ($columns as $col) {
            if ($col['Field'] === 'status') {
                echo "<p style='color:green;'>✓ Kolom status sekarang: <code>{$col['Type']}</code></p>";
                echo "<p style='color:green;'>✓ Default: <code>{$col['Default']}</code></p>";
                break;
            }
        }
        
        echo "<hr>";
        echo "<h3 style='color:green;'>✅ Schema Fix Selesai!</h3>";
        echo "<p>Kolom status sekarang bisa menerima nilai 'trashed' untuk soft delete.</p>";
        echo "<p><strong>Silakan coba hapus dokumen lagi.</strong></p>";
        
    } else {
        echo "<p style='color:green;'>✓ Status sudah dalam format VARCHAR - tidak perlu diubah</p>";
        echo "<p>Schema sudah benar!</p>";
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>✗ Database Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . htmlspecialchars($e->getCode()) . "</p>";
    
    echo "<hr>";
    echo "<h4>💡 Solusi Alternative (Manual):</h4>";
    echo "<p>Jika auto fix tidak bekerja, jalankan SQL ini di phpMyAdmin:</p>";
    echo "<pre style='background:#f5f5f5; padding:10px; border-left:4px solid red;'>
-- 1. Buat kolom temporary
ALTER TABLE documents ADD COLUMN status_temp VARCHAR(20);

-- 2. Copy data
UPDATE documents SET status_temp = status;

-- 3. Drop kolom lama
ALTER TABLE documents DROP COLUMN status;

-- 4. Rename temp ke status
ALTER TABLE documents CHANGE status_temp status VARCHAR(20) DEFAULT 'active' COMMENT 'Status: active, archived, deleted, trashed';

-- 5. Add index
ALTER TABLE documents ADD INDEX idx_documents_status (status);
    </pre>";
    
} catch (Exception $e) {
    echo "<h3 style='color:red;'>✗ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    h2, h3, h4 { color: #333; }
    p { line-height: 1.6; margin: 10px 0; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    pre { background: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; overflow-x: auto; }
    ul { margin-left: 20px; }
    li { margin: 5px 0; }
</style>

