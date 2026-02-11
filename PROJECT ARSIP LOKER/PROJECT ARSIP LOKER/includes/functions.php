<?php
// Fungsi-fungsi utilitas untuk sistem arsip dokumen

/**
 * Inisialisasi session dengan dukungan multi-tab
 * Set session name berdasarkan parameter tab di URL
 */
function init_multi_session() {
    // Cek apakah ada parameter tab di URL
    $tab_id = isset($_GET['tab']) ? intval($_GET['tab']) : 0;
    
    // Jika ada tab_id, gunakan session name yang berbeda
    if ($tab_id > 0) {
        $session_name = 'PHPSESSID_' . $tab_id;
    } else {
        // Default session name (tab 0)
        $session_name = 'PHPSESSID';
    }
    
    // Tutup session yang sedang aktif jika ada (dengan nama berbeda)
    if (session_status() === PHP_SESSION_ACTIVE) {
        $current_session_name = session_name();
        if ($current_session_name !== $session_name) {
            // Session name berbeda, tutup dan buka session baru
            session_write_close();
        } else {
            // Session sudah aktif dengan nama yang benar
            // Set tab_id di session untuk tracking
            $_SESSION['tab_id'] = $tab_id;
            return;
        }
    }
    
    // Set session name sebelum session_start()
    session_name($session_name);
    
    // Set cookie parameters untuk memastikan session terpisah per tab
    session_set_cookie_params([
        'lifetime' => 0, // Sampai browser ditutup
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start session baru
    session_start();
    
    // Set tab_id di session untuk tracking
    $_SESSION['tab_id'] = $tab_id;
}

/**
 * Dapatkan session name berdasarkan tab_id
 */
function get_session_name($tab_id = 0) {
    if ($tab_id > 0) {
        return 'PHPSESSID_' . $tab_id;
    }
    return 'PHPSESSID';
}

/**
 * Switch session berdasarkan tab_id
 */
function switch_session($tab_id) {
    // Tutup session saat ini
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Set session name baru
    $session_name = get_session_name($tab_id);
    session_name($session_name);
    
    // Start session baru
    session_start();
}

/**
 * Dapatkan parameter tab dari URL
 */
function get_tab_id() {
    return isset($_GET['tab']) ? intval($_GET['tab']) : 0;
}

/**
 * Tambahkan parameter tab ke URL
 */
function add_tab_param($url) {
    $tab_id = get_tab_id();
    if ($tab_id > 0) {
        // Cek apakah URL sudah ada parameter
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . 'tab=' . $tab_id;
    }
    return $url;
}

/**
 * Sanitasi input untuk mencegah XSS
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate nomor dokumen unik
 */
function generate_document_number() {
    $prefix = 'DOC';
    $date = date('Ymd');
    $random = rand(1000, 9999);
    return $prefix . $date . $random;
}

/**
 * Format ukuran file
 */
function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Validasi tipe file yang diizinkan
 */
function is_allowed_file_type($filename) {
    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowed_extensions);
}

/**
 * Generate nama file unik
 */
function generate_unique_filename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Log aktivitas user
 */
function log_activity($user_id, $action, $description = '', $document_id = null) {
    global $db;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO activity_logs (user_id, action, description, document_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $db->execute($sql, [$user_id, $action, $description, $document_id, $ip_address, $user_agent]);
}

/**
 * Cek apakah user sudah login
 */
function is_logged_in() {
    // Cek apakah ada user_id di session
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    // Validasi tab_id: pastikan session digunakan di tab yang benar
    $current_tab_id = get_tab_id();
    
    // Jika ada tab_id di session, pastikan cocok dengan tab_id saat ini
    if (isset($_SESSION['tab_id'])) {
        // Jika session punya tab_id tapi tidak cocok dengan current tab_id
        // Ini berarti session dari tab lain, anggap belum login
        if ($_SESSION['tab_id'] != $current_tab_id) {
            return false;
        }
    } else {
        // Jika session tidak punya tab_id, set sesuai current tab_id
        $_SESSION['tab_id'] = $current_tab_id;
    }
    
    return true;
}

/**
 * Cek apakah user adalah admin
 */
function is_admin() {
    return is_logged_in() && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect jika belum login
 */
function require_login() {
    if (!is_logged_in()) {
        $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
        header('Location: landing.php' . $tab_param);
        exit();
    }
}

/**
 * Redirect jika bukan admin
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        $tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
        header('Location: dashboard.php' . $tab_param);
        exit();
    }
}

/**
 * Format tanggal Indonesia
 */
function format_date_indonesia($date, $include_time = false) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $hari = date('d', $timestamp);
    $bulan_num = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    $result = $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
    
    if ($include_time) {
        $result .= ' ' . date('H:i', $timestamp);
    }
    
    return $result;
}

/**
 * Upload file dengan validasi
 */
function upload_file($file, $upload_dir = 'uploads/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'File tidak valid'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error upload file'];
    }
    
    if (!is_allowed_file_type($file['name'])) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 10MB)'];
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = generate_unique_filename($file['name']);
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }
    
    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'size' => $file['size']
    ];
}

/**
 * Hapus file
 */
function delete_file($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}

/**
 * Escape output untuk HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash_message($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

/**
 * Pagination helper
 */
function paginate($total_records, $records_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records
    ];
}
?>
