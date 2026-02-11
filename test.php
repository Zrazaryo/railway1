<?php
// Test PHP Configuration
echo "<h1>PHP Test Page</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";

echo "<h3>PHP Extensions:</h3>";
echo "<ul>";
echo "<li>PDO: " . (extension_loaded('pdo') ? "✅ Loaded" : "❌ Not loaded") . "</li>";
echo "<li>PDO MySQL: " . (extension_loaded('pdo_mysql') ? "✅ Loaded" : "❌ Not loaded") . "</li>";
echo "<li>GD: " . (extension_loaded('gd') ? "✅ Loaded" : "❌ Not loaded") . "</li>";
echo "<li>FileInfo: " . (extension_loaded('fileinfo') ? "✅ Loaded" : "❌ Not loaded") . "</li>";
echo "</ul>";

echo "<h3>Server Information:</h3>";
echo "<ul>";
echo "<li>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Script Path: " . __FILE__ . "</li>";
echo "</ul>";

echo "<h3>File Permissions:</h3>";
echo "<ul>";
echo "<li>config/ directory: " . (is_writable('config/') ? "✅ Writable" : "❌ Not writable") . "</li>";
echo "<li>uploads/ directory: " . (is_writable('uploads/') ? "✅ Writable" : "❌ Not writable") . "</li>";
echo "</ul>";

echo "<h3>Current Directory Contents:</h3>";
echo "<ul>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . $file . "</li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php'>← Kembali ke Halaman Utama</a></p>";
echo "<p><a href='setup.php'>→ Setup Database</a></p>";
?>
