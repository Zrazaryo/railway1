<?php
// File untuk cek versi file yang sedang digunakan
echo "<h1>🔍 Cek Versi File</h1>";
echo "<hr>";

echo "<h2>1. Info File:</h2>";
echo "<p><strong>File ini dibuat:</strong> " . date("Y-m-d H:i:s", filemtime(__FILE__)) . "</p>";
echo "<p><strong>Path:</strong> " . __FILE__ . "</p>";

echo "<h2>2. Cek File Penting:</h2>";
$files = [
    'dashboard.php',
    'landing.php',
    'index.php',
    'config/database.php',
    'includes/functions.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $modified = date("Y-m-d H:i:s", filemtime($file));
        $size = filesize($file);
        echo "<p>✅ <strong>$file</strong></p>";
        echo "<ul>";
        echo "<li>Modified: $modified</li>";
        echo "<li>Size: " . number_format($size) . " bytes</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ <strong>$file</strong> - TIDAK DITEMUKAN</p>";
    }
}

echo "<h2>3. Cek Database:</h2>";
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
    echo "<p>✅ Database: " . DB_NAME . "</p>";
    echo "<p>✅ Host: " . DB_HOST . "</p>";
    
    // Cek jumlah tabel
    try {
        $tables = $db->fetchAll("SHOW TABLES");
        echo "<p>✅ Jumlah Tabel: " . count($tables) . "</p>";
        echo "<p><strong>Tabel yang ada:</strong></p><ul>";
        foreach ($tables as $table) {
            $table_name = array_values($table)[0];
            echo "<li>$table_name</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>4. Cek Session:</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>✅ Username: " . ($_SESSION['username'] ?? 'N/A') . "</p>";
    echo "<p>✅ Role: " . ($_SESSION['user_role'] ?? 'N/A') . "</p>";
} else {
    echo "<p>⚠️ Belum login</p>";
}

echo "<hr>";
echo "<p><strong>Jika file modified date menunjukkan tanggal lama, berarti file belum ter-update!</strong></p>";
?>



















