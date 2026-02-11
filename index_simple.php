<?php
// Simple test page
echo "<!DOCTYPE html>";
echo "<html lang='id'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Sistem Arsip Dokumen - Test</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";
echo "<div class='row justify-content-center'>";
echo "<div class='col-md-8'>";
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h3><i class='fas fa-shield-alt'></i> Sistem Arsip Dokumen - Kantor Imigrasi</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<h4>Status Sistem:</h4>";

// Check PHP
echo "<div class='alert alert-success'>";
echo "<strong>✅ PHP:</strong> " . phpversion() . " - Berjalan dengan baik";
echo "</div>";

// Check extensions
$extensions = ['pdo', 'pdo_mysql', 'gd', 'fileinfo'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='alert alert-success'>";
        echo "<strong>✅ Extension $ext:</strong> Loaded";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<strong>❌ Extension $ext:</strong> Not loaded";
        echo "</div>";
    }
}

// Check directories
$dirs = ['config', 'uploads', 'auth', 'documents', 'includes', 'assets'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "<div class='alert alert-success'>";
        echo "<strong>✅ Directory $dir:</strong> Exists";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<strong>⚠️ Directory $dir:</strong> Not found";
        echo "</div>";
    }
}

echo "<hr>";
echo "<h5>Langkah Selanjutnya:</h5>";
echo "<ol>";
echo "<li>Jika semua status hijau, klik <strong>Setup Database</strong></li>";
echo "<li>Jika ada error, perbaiki terlebih dahulu</li>";
echo "<li>Setelah setup database, login dengan admin/password</li>";
echo "</ol>";

echo "<div class='text-center mt-4'>";
echo "<a href='setup.php' class='btn btn-primary btn-lg me-3'>";
echo "<i class='fas fa-cog'></i> Setup Database";
echo "</a>";
echo "<a href='test.php' class='btn btn-outline-secondary'>";
echo "<i class='fas fa-info-circle'></i> Info Detail";
echo "</a>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js'></script>";
echo "</body>";
echo "</html>";
?>
