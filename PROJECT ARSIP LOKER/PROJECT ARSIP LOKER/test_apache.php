<?php
// File test untuk memastikan Apache bisa akses folder
echo "<h1>✅ Apache Berjalan dengan Baik!</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Path: " . __FILE__ . "</p>";
echo "<hr>";
echo "<h2>Test Database Connection:</h2>";
if (file_exists('config/database.php')) {
    echo "<p>✅ File config/database.php ditemukan</p>";
    try {
        require_once 'config/database.php';
        echo "<p>✅ Database connection: BERHASIL</p>";
        echo "<p>Database: " . DB_NAME . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ Database connection: GAGAL</p>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ File config/database.php TIDAK ditemukan</p>";
}
echo "<hr>";
echo "<h2>File Check:</h2>";
$files_to_check = ['index.php', 'landing.php', 'config/database.php'];
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file ditemukan</p>";
    } else {
        echo "<p>❌ $file TIDAK ditemukan</p>";
    }
}
?>



















