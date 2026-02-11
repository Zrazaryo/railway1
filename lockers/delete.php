<?php
/**
 * Handle penghapusan lemari
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Hanya admin yang bisa menghapus
require_login();
if (!is_admin()) {
    header('Location: select.php?error=' . urlencode('Akses ditolak. Hanya admin yang bisa menghapus lemari.'));
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: select.php?error=' . urlencode('ID lemari tidak ditemukan.'));
    exit();
}

$locker_id = (int)$_GET['id'];

try {
    // Ambil data lemari untuk validasi
    $locker = $db->fetch("SELECT id, code, name FROM lockers WHERE id = ?", [$locker_id]);
    
    if (!$locker) {
        header('Location: select.php?error=' . urlencode('Lemari tidak ditemukan.'));
        exit();
    }
    
    // Cek apakah ada dokumen aktif yang menggunakan lemari ini (cek berdasarkan name/kode rak)
    // Hanya dokumen aktif yang menghalangi penghapusan lemari
    // Dokumen pemusnahan (deleted) tidak menghalangi penghapusan lemari
    $active_documents = $db->fetch("SELECT COUNT(*) AS cnt FROM documents WHERE month_number = ? AND status = 'active'", [$locker['name']]);
    
    $has_active = (int)($active_documents['cnt'] ?? 0) > 0;
    
    if ($has_active) {
        header('Location: select.php?error=' . urlencode('Tidak bisa menghapus lemari karena masih ada dokumen aktif di dalamnya.'));
        exit();
    }
    
    // Hapus lemari
    $db->execute("DELETE FROM lockers WHERE id = ?", [$locker_id]);
    
    // Log aktivitas
    log_activity($_SESSION['user_id'], 'delete_locker', "Menghapus lemari: {$locker['code']} ({$locker['name']})", null);
    
    header('Location: select.php?success=' . urlencode("Berhasil menghapus lemari {$locker['name']}."));
    exit();
    
} catch (Exception $e) {
    header('Location: select.php?error=' . urlencode('Gagal menghapus lemari: ' . $e->getMessage()));
    exit();
}
?>




