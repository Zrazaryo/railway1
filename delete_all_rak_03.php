<?php
/**
 * Script untuk langsung menghapus semua kode rak dengan nomor .03
 * Menghapus A.03, B.03, C.03, ..., Y.03, Z.03 dari tabel lockers
 * Akses via browser: http://localhost/PROJECT%20ARSIP%20LOKER/delete_all_rak_03.php
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
    <title>Hapus Semua Kode Rak .03</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .summary { margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 5px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
    <h2>Hapus Semua Kode Rak .03</h2>
    <hr>
<?php
try {
    echo "<p>Memulai penghapusan semua kode rak .03 (A.03 sampai Z.03)...</p>\n";
    echo "<pre>\n";
    
    // Cari semua entri dengan name yang berakhiran .03
    $sql = "SELECT id, code, name FROM lockers WHERE name LIKE '%.03' ORDER BY name ASC";
    $lockers_to_delete = $db->fetchAll($sql);
    
    $total_deleted = 0;
    $errors = [];
    
    if (empty($lockers_to_delete)) {
        echo "<span class='info'>Tidak ada data dengan kode rak .03 yang ditemukan.</span>\n";
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
    echo "<p><strong>Total dihapus:</strong> <span class='success'>{$total_deleted}</span> entri</p>\n";
    if (!empty($errors)) {
        echo "<p><strong>Total error:</strong> <span class='error'>" . count($errors) . "</span></p>\n";
    }
    echo "<p><a href='lockers/select.php' class='btn'>← Kembali ke Daftar Lemari</a></p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
</div>
</body>
</html>




