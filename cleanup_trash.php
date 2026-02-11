<?php
/**
 * Auto-cleanup script untuk dokumen sampah yang sudah lebih dari 30 hari
 * Script ini bisa dijalankan via:
 * 1. Manual: Akses file ini dari browser /cleanup_trash.php
 * 2. CRON: Tambahkan ke cron jobs untuk otomatis harian
 *    Contoh: 0 2 * * * curl http://localhost/PROJECT ARSIP LOKER/cleanup_trash.php
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Bisa diakses dari CLI atau dengan API key
$is_valid_request = false;

// Check 1: Direct browser access by admin
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $is_valid_request = true;
}

// Check 2: CLI mode
if (php_sapi_name() === 'cli') {
    $is_valid_request = true;
}

// Check 3: API key (untuk CRON)
if (!$is_valid_request && isset($_GET['token'])) {
    $api_token = trim($_GET['token']);
    // Gunakan token rahasia yang aman (bisa disimpan di file config terpisah)
    $valid_token = defined('CLEANUP_TOKEN') ? CLEANUP_TOKEN : false;
    if ($api_token === $valid_token && !empty($valid_token)) {
        $is_valid_request = true;
    }
}

if (!$is_valid_request && !isset($_GET['token'])) {
    // Require admin login
    if (!isset($_SESSION['user_id'])) {
        header('Location: /PROJECT ARSIP LOKER/auth/login.php');
        exit();
    }
    require_admin();
}

if (!$is_valid_request) {
    if (php_sapi_name() === 'cli') {
        die("❌ Invalid token for cleanup script\n");
    } else {
        die("❌ Akses ditolak. Token tidak valid.");
    }
}

$db = new Database();
$is_cli = php_sapi_name() === 'cli';

echo ($is_cli ? "🧹 Starting Trash Cleanup Process...\n\n" : "<pre>\n🧹 Starting Trash Cleanup Process...\n\n");

try {
    // Find documents older than 30 days that are still in trash
    $trash_docs = $db->fetchAll("
        SELECT id, original_document_id, file_path, full_name 
        FROM document_trash 
        WHERE status = 'in_trash' 
        AND restore_deadline < NOW()
    ");
    
    if (empty($trash_docs)) {
        $message = "✓ Tidak ada dokumen yang perlu dibersihkan (semua masih dalam periode 30 hari)\n";
        echo ($is_cli ? $message : "<div class='alert alert-success'>$message</div>");
    } else {
        $deleted_count = 0;
        $error_count = 0;
        
        echo "📋 Ditemukan " . count($trash_docs) . " dokumen untuk dihapus:\n\n";
        
        foreach ($trash_docs as $trash_doc) {
            try {
                // Delete actual document file
                if (!empty($trash_doc['file_path']) && file_exists($trash_doc['file_path'])) {
                    if (unlink($trash_doc['file_path'])) {
                        echo "  ✓ File dihapus: " . $trash_doc['file_path'] . "\n";
                    } else {
                        echo "  ⚠ Gagal menghapus file: " . $trash_doc['file_path'] . "\n";
                        $error_count++;
                        continue;
                    }
                }
                
                // Delete document from documents table
                $db->execute("DELETE FROM documents WHERE id = ?", [$trash_doc['original_document_id']]);
                
                // Update trash record
                $db->execute(
                    "UPDATE document_trash SET status = 'permanently_deleted' WHERE id = ?",
                    [$trash_doc['id']]
                );
                
                // Log to audit
                if (isset($_SESSION['user_id'])) {
                    $db->execute(
                        "INSERT INTO trash_audit_logs (document_trash_id, action, user_id, notes) VALUES (?, ?, ?, ?)",
                        [$trash_doc['id'], 'permanently_deleted', $_SESSION['user_id'], 'Auto cleanup']
                    );
                }
                
                echo "  ✓ Dokumen dihapus: " . $trash_doc['full_name'] . "\n";
                $deleted_count++;
                
            } catch (Exception $e) {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                $error_count++;
            }
        }
        
        echo "\n";
        echo "📊 Hasil Cleanup:\n";
        echo "  ✓ Berhasil dihapus: $deleted_count\n";
        echo "  ✗ Error: $error_count\n";
        
        // Log cleanup operation
        if (isset($_SESSION['user_id'])) {
            log_activity(
                $_SESSION['user_id'],
                'TRASH_CLEANUP',
                "Auto cleanup dokumen sampah: $deleted_count dokumen dihapus permanen",
                0
            );
        }
    }
    
    echo "\n✓ Cleanup process selesai!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
}

if (!$is_cli) {
    echo "</pre>";
}
?>
