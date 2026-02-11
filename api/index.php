<?php
/**
 * Front controller untuk Vercel: meneruskan semua request ke file PHP yang sesuai.
 * Set working directory ke root project agar require/include di file target tetap benar.
 */
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// Di Vercel (serverless), session tidak persist per file. Daftarkan handler session ke DB sebelum file target dijalankan.
if (getenv('VERCEL')) {
    if (file_exists($projectRoot . '/config/database.php')) {
        require_once $projectRoot . '/config/database.php';
        if (file_exists($projectRoot . '/config/session_db.php')) {
            require_once $projectRoot . '/config/session_db.php';
            register_db_session_handler();
        }
    }
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = trim($path, '/');

// Default ke index.php (akan redirect ke landing)
if ($path === '' || $path === 'index.php') {
    $path = 'index.php';
}

// Request ke API (file di folder api/) -> jalankan file tersebut
if (strpos($path, 'api/') === 0) {
    $apiFile = $projectRoot . '/api/' . substr($path, 4);
    if (is_file($apiFile) && pathinfo($apiFile, PATHINFO_EXTENSION) === 'php') {
        $real = realpath($apiFile);
        if ($real && strpos($real, $projectRoot) === 0) {
            require $apiFile;
            return;
        }
    }
}

// Request ke file PHP lain di root atau subfolder
$targetFile = $projectRoot . '/' . $path;
$real = realpath($targetFile);

// Langsung ke file .php
if ($real && is_file($real) && strpos($real, $projectRoot) === 0 && pathinfo($real, PATHINFO_EXTENSION) === 'php') {
    require $real;
    return;
}

// Path mengarah ke folder (mis. /documents/, /users/, /reports/, /logs/) -> coba index.php di dalamnya
if ($real && is_dir($real)) {
    $indexPhp = rtrim($real, DIRECTORY_SEPARATOR) . '/index.php';
    if (file_exists($indexPhp) && is_file($indexPhp)) {
        require $indexPhp;
        return;
    }
}

// Path tanpa .php (mis. "documents" atau "reports") -> coba path/index.php (fallback jika realpath gagal)
$dirIndex = $projectRoot . '/' . $path . '/index.php';
if (file_exists($dirIndex) && is_file($dirIndex)) {
    require $dirIndex;
    return;
}

// 404
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>404</title></head><body><h1>Halaman tidak ditemukan</h1><p>' . htmlspecialchars($path) . '</p></body></html>';
