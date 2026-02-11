<?php
/**
 * Handle penghapusan multiple lemari
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Hanya admin yang bisa menghapus
require_login();
if (!is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya admin yang bisa menghapus lemari.']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$locker_ids = $input['ids'] ?? [];

if (empty($locker_ids) || !is_array($locker_ids)) {
    echo json_encode(['success' => false, 'message' => 'ID lemari tidak valid']);
    exit();
}

// Validate and sanitize IDs
$locker_ids = array_filter(array_map('intval', $locker_ids));
if (empty($locker_ids)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada lemari yang valid untuk dihapus']);
    exit();
}

try {
    $deleted_count = 0;
    $failed_count = 0;
    $errors = [];
    
    foreach ($locker_ids as $locker_id) {
        if ($locker_id <= 0) {
            $failed_count++;
            continue;
        }
        
        // Cek apakah lemari ada
        $locker = $db->fetch("SELECT id, code, name FROM lockers WHERE id = ?", [$locker_id]);
        if (!$locker) {
            $failed_count++;
            continue;
        }
        
        // Cek apakah ada dokumen aktif yang menggunakan lemari ini (cek berdasarkan name/kode rak)
        // Hanya dokumen aktif yang menghalangi penghapusan lemari
        // Dokumen pemusnahan (deleted) tidak menghalangi penghapusan lemari
        $active_documents = $db->fetch("SELECT COUNT(*) AS cnt FROM documents WHERE month_number = ? AND status = 'active'", [$locker['name']]);
        
        $has_active = (int)($active_documents['cnt'] ?? 0) > 0;
        
        if ($has_active) {
            $errors[] = "Lemari {$locker['name']} tidak bisa dihapus karena masih ada dokumen aktif";
            $failed_count++;
            continue;
        }
        
        // Hapus lemari
        $delete_result = $db->execute("DELETE FROM lockers WHERE id = ?", [$locker_id]);
        
        // Verifikasi penghapusan berhasil
        $verify = $db->fetch("SELECT id FROM lockers WHERE id = ?", [$locker_id]);
        if ($verify) {
            $errors[] = "Gagal menghapus lemari {$locker['name']}";
            $failed_count++;
            continue;
        }
        
        // Log aktivitas
        log_activity($_SESSION['user_id'], 'delete_locker', "Menghapus lemari: {$locker['code']} ({$locker['name']})", null);
        $deleted_count++;
    }
    
    if ($deleted_count > 0) {
        echo json_encode([
            'success' => true, 
            'message' => "Berhasil menghapus $deleted_count lemari" . ($failed_count > 0 ? ", $failed_count gagal" : ''),
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Tidak ada lemari yang berhasil dihapus',
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>

