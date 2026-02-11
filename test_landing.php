<?php
// Test file untuk debugging landing.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Landing.php Debug</h1>";
echo "<hr>";

// Test 1: File landing.php
echo "<h2>1. File Check:</h2>";
if (file_exists('landing.php')) {
    echo "<p>✅ landing.php ditemukan</p>";
} else {
    echo "<p>❌ landing.php TIDAK ditemukan</p>";
}

// Test 2: File config/database.php
echo "<h2>2. Config Database:</h2>";
if (file_exists('config/database.php')) {
    echo "<p>✅ config/database.php ditemukan</p>";
    try {
        require_once 'config/database.php';
        echo "<p>✅ Database connection berhasil</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error database: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ config/database.php TIDAK ditemukan</p>";
}

// Test 3: File includes/functions.php
echo "<h2>3. Functions File:</h2>";
if (file_exists('includes/functions.php')) {
    echo "<p>✅ includes/functions.php ditemukan</p>";
    try {
        require_once 'includes/functions.php';
        echo "<p>✅ Functions loaded berhasil</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error functions: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ includes/functions.php TIDAK ditemukan</p>";
}

// Test 4: Assets files
echo "<h2>4. Assets Files:</h2>";
if (file_exists('assets/css/style.css')) {
    echo "<p>✅ assets/css/style.css ditemukan</p>";
} else {
    echo "<p>❌ assets/css/style.css TIDAK ditemukan</p>";
}

if (file_exists('assets/js/script.js')) {
    echo "<p>✅ assets/js/script.js ditemukan</p>";
} else {
    echo "<p>❌ assets/js/script.js TIDAK ditemukan</p>";
}

// Test 5: Session
echo "<h2>5. Session Test:</h2>";
session_start();
echo "<p>✅ Session started</p>";
if (isset($_SESSION['user_id'])) {
    echo "<p>⚠️ User sudah login (user_id: " . $_SESSION['user_id'] . ")</p>";
} else {
    echo "<p>✅ User belum login</p>";
}

// Test 6: Try to include landing.php
echo "<h2>6. Test Include landing.php:</h2>";
echo "<p>Mencoba include landing.php...</p>";
echo "<hr>";
echo "<div style='background: #f0f0f0; padding: 20px; border: 1px solid #ccc;'>";
echo "<p><strong>Jika ada error di bawah ini, itu penyebabnya:</strong></p>";
try {
    // Capture output
    ob_start();
    include 'landing.php';
    $output = ob_get_clean();
    if (empty($output)) {
        echo "<p>⚠️ landing.php tidak menghasilkan output (blank page)</p>";
        echo "<p>Kemungkinan ada redirect atau error yang di-suppress</p>";
    } else {
        echo "<p>✅ landing.php berhasil di-include</p>";
        echo "<p>Output length: " . strlen($output) . " bytes</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
echo "</div>";
?>



















