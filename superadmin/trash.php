<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/trash_helper.php';

// Only superadmin
if (!is_logged_in() || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: ../auth/login_superadmin.php');
    exit();
}

// Ensure trash tables exist
ensure_trash_tables_exist($db);

$error_message = '';
$success_message = '';

// Auto-purge: permanently remove documents deleted more than 14 days ago
try {
    $to_purge = $db->fetchAll("SELECT id, file_path FROM document_trash WHERE status = 'in_trash' AND deleted_at <= DATE_SUB(NOW(), INTERVAL 14 DAY)");
    foreach ($to_purge as $p) {
        // delete file if exists
        if (!empty($p['file_path'])) {
            $fp = __DIR__ . '/../' . ltrim($p['file_path'], '/\\');
            if (file_exists($fp)) {
                @unlink($fp);
            }
        }
        $db->execute("DELETE FROM document_trash WHERE id = ?", [$p['id']]);
        log_activity($_SESSION['user_id'], 'PURGE_DOCUMENT', 'Auto-purge dokumen id: ' . $p['id'], null);
    }
} catch (Exception $e) {
    // non-fatal
}

// Handle actions: restore or purge (permanent delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $trash_id = (int)($_POST['trash_id'] ?? 0);
    if ($trash_id > 0) {
        try {
            if ($action === 'restore') {
                $trash_doc = $db->fetch("SELECT * FROM document_trash WHERE id = ?", [$trash_id]);
                if ($trash_doc) {
                    $restore_sql = "UPDATE documents SET status = 'active' WHERE id = ?";
                    $db->execute($restore_sql, [$trash_doc['original_document_id']]);
                    
                    $update_trash = "UPDATE document_trash SET status = 'restored', restored_at = NOW(), restored_by = ? WHERE id = ?";
                    $db->execute($update_trash, [$_SESSION['user_id'], $trash_id]);
                    
                    log_activity($_SESSION['user_id'], 'RESTORE_DOCUMENT', 'Restore dokumen: ' . ($trash_doc['full_name'] ?? $trash_doc['title']), $trash_doc['original_document_id']);
                    $success_message = 'Dokumen berhasil dikembalikan.';
                } else {
                    $error_message = 'Dokumen sampah tidak ditemukan.';
                }
            } elseif ($action === 'purge') {
                $trash_doc = $db->fetch("SELECT * FROM document_trash WHERE id = ?", [$trash_id]);
                if ($trash_doc) {
                    if (!empty($trash_doc['file_path'])) {
                        $fp = __DIR__ . '/../' . ltrim($trash_doc['file_path'], '/\\');
                        if (file_exists($fp)) {@unlink($fp);}    
                    }
                    $db->execute("DELETE FROM document_trash WHERE id = ?", [$trash_id]);
                    log_activity($_SESSION['user_id'], 'PURGE_DOCUMENT', 'Purge dokumen: ' . ($trash_doc['full_name'] ?? $trash_doc['title']), $trash_doc['original_document_id']);
                    $success_message = 'Dokumen dihapus permanen.';
                } else {
                    $error_message = 'Dokumen sampah tidak ditemukan.';
                }
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch trash documents
$trash_docs = [];
try {
    $trash_docs = $db->fetchAll("SELECT dt.*, u.full_name as deleted_by_name FROM document_trash dt LEFT JOIN users u ON dt.deleted_by = u.id WHERE dt.status = 'in_trash' ORDER BY dt.deleted_at DESC");
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

function fmt_origin($o){
    switch($o){
        case 'imigrasi_jakarta_pusat_kemayoran': return 'Kemayoran';
        case 'imigrasi_ulp_semanggi': return 'Semanggi';
        case 'imigrasi_lounge_senayan_city': return 'Senayan City';
        default: return $o ?: '-';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sampah Dokumen - Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar_superadmin.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar_superadmin.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-trash me-2"></i>Sampah Dokumen</h1>
                    <div>
                        <small class="text-muted">Dokumen yang dihapus disimpan di sini selama 14 hari sebelum dihapus permanen.</small>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo e($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo e($success_message); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>NIK</th>
                                        <th>Passport</th>
                                        <th>Asal</th>
                                        <th>Dihapus Oleh</th>
                                        <th>Tanggal Dihapus</th>
                                        <th>Batas Restore</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($trash_docs)): ?>
                                        <tr><td colspan="9" class="text-center py-4">Tidak ada dokumen di sampah.</td></tr>
                                    <?php else: $i=1; foreach($trash_docs as $d): 
                                        $days_left = max(0, ceil((strtotime($d['restore_deadline']) - time()) / 86400));
                                    ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo e($d['full_name'] ?? $d['title'] ?? '-'); ?></td>
                                            <td><?php echo e($d['nik'] ?? '-'); ?></td>
                                            <td><?php echo e($d['passport_number'] ?? '-'); ?></td>
                                            <td><?php echo e(fmt_origin($d['document_origin'])); ?></td>
                                            <td><?php echo e($d['deleted_by_name'] ?? '-'); ?></td>
                                            <td><?php echo e($d['deleted_at'] ? date('d/m/Y H:i', strtotime($d['deleted_at'])) : '-'); ?></td>
                                            <td>
                                                <?php if ($days_left > 0): ?>
                                                    <span class="badge bg-warning"><?php echo $days_left; ?> hari</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="post" style="display:inline">
                                                    <input type="hidden" name="trash_id" value="<?php echo $d['id']; ?>">
                                                    <button name="action" value="restore" class="btn btn-sm btn-success" onclick="return confirm('Kembalikan dokumen ini?')">Restore</button>
                                                </form>
                                                <form method="post" style="display:inline" class="ms-1">
                                                    <input type="hidden" name="trash_id" value="<?php echo $d['id']; ?>">
                                                    <button name="action" value="purge" class="btn btn-sm btn-danger" onclick="return confirm('Hapus permanen dokumen ini?')">Hapus Permanen</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
