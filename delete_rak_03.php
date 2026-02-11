<?php
/**
 * Script untuk menghapus semua kode rak dengan nomor .03
 * Menghapus A.03, B.03, C.03, ..., Z.03 dari tabel lockers
 * Akses via browser: http://localhost/PROJECT%20ARSIP%20LOKER/delete_rak_03.php
 */

session_start();
require_once 'config/database.php';

// Hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Akses ditolak. Hanya admin yang bisa menjalankan script ini.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Kode Rak .03</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .summary { margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 5px; }
        .btn { padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #c82333; }
        .btn-cancel { background: #6c757d; }
        .btn-cancel:hover { background: #5a6268; }
    </style>
</head>
<body>
<div class="container">
    <h2>Hapus Kode Rak .03</h2>
    <hr>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    try {
        echo "<p>Memulai penghapusan kode rak .03...</p>\n";
        echo "<pre>\n";
        
        // Cari semua entri dengan name yang berakhiran .03
        $sql = "SELECT id, code, name FROM lockers WHERE name LIKE '%.03'";
        $lockers_to_delete = $db->fetchAll($sql);
        
        $total_deleted = 0;
        $errors = [];
        
        if (empty($lockers_to_delete)) {
            echo "<span class='warning'>Tidak ada data dengan kode rak .03 yang ditemukan.</span>\n";
        } else {
            echo "Ditemukan " . count($lockers_to_delete) . " entri yang akan dihapus:\n\n";
            
            foreach ($lockers_to_delete as $locker) {
                try {
                    // Hapus entri
                    $db->execute("DELETE FROM lockers WHERE id = ?", [$locker['id']]);
                    $total_deleted++;
                    echo "<span class='success'>✓</span> Dihapus: Code={$locker['code']}, Name={$locker['name']}\n";
                } catch (PDOException $e) {
                    $errors[] = "Code={$locker['code']}, Name={$locker['name']} - " . $e->getMessage();
                    echo "<span class='error'>✗</span> Error: Code={$locker['code']}, Name={$locker['name']} - " . htmlspecialchars($e->getMessage()) . "\n";
                }
            }
        }
        
        echo "</pre>\n";
        echo "<div class='summary'>\n";
        echo "<h3>Ringkasan:</h3>\n";
        echo "<p><strong>Total dihapus:</strong> <span class='success'>{$total_deleted}</span></p>\n";
        if (!empty($errors)) {
            echo "<p><strong>Total error:</strong> <span class='error'>" . count($errors) . "</span></p>\n";
        }
        echo "<p><a href='lockers/select.php' class='btn btn-cancel'>← Kembali ke Daftar Lemari</a></p>\n";
        echo "</div>\n";
        
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    // Tampilkan konfirmasi
    try {
        $sql = "SELECT id, code, name FROM lockers WHERE name LIKE '%.03'";
        $lockers_to_delete = $db->fetchAll($sql);
        
        if (empty($lockers_to_delete)) {
            echo "<p class='warning'>Tidak ada data dengan kode rak .03 yang ditemukan.</p>\n";
            echo "<p><a href='lockers/select.php' class='btn btn-cancel'>← Kembali ke Daftar Lemari</a></p>\n";
        } else {
            echo "<p><strong>Peringatan:</strong> Script ini akan menghapus semua kode rak dengan nomor .03</p>\n";
            echo "<p>Ditemukan <strong>" . count($lockers_to_delete) . " entri</strong> yang akan dihapus:</p>\n";
            echo "<ul>\n";
            foreach ($lockers_to_delete as $locker) {
                echo "<li>Code: <strong>{$locker['code']}</strong>, Name: <strong>{$locker['name']}</strong></li>\n";
            }
            echo "</ul>\n";
            
            echo "<form method='POST' onsubmit='return confirm(\"Apakah Anda yakin ingin menghapus semua kode rak .03? Tindakan ini tidak dapat dibatalkan!\");'>\n";
            echo "<input type='hidden' name='confirm' value='yes'>\n";
            echo "<button type='submit' class='btn'>Ya, Hapus Semua</button>\n";
            echo "<a href='lockers/select.php' class='btn btn-cancel' style='margin-left: 10px;'>Batal</a>\n";
            echo "</form>\n";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
}
?>
</div>
</body>
</html>




