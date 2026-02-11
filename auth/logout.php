<?php
// Load functions first untuk init_multi_session
require_once '../includes/functions.php';

// Inisialisasi session dengan dukungan multi-tab
init_multi_session();

require_once '../config/database.php';

if (is_logged_in()) {
    // Log aktivitas logout
    log_activity($_SESSION['user_id'], 'LOGOUT', 'User berhasil logout');
    
    // Hapus remember cookie jika ada
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Hapus semua session
session_destroy();

// Redirect ke halaman login dengan mempertahankan parameter tab
$tab_param = get_tab_id() > 0 ? '?tab=' . get_tab_id() : '';
header('Location: ../landing.php' . $tab_param);
exit();
?>
