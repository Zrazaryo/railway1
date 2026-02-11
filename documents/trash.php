<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/trash_helper.php';

// Cek login (hanya admin yang bisa akses trash)
require_admin();

// Initialize database
$db = new Database();

// Auto-create tabel jika belum ada
$table_check = ensure_trash_tables_exist($db);
$error_message = '';
$success_message = '';

// Cek hasil auto-create
if (!$table_check['success']) {
    $error_message = $table_check['message'];
} else {
    // Jika tabel baru dibuat, tampilkan success message
    if (strpos($table_check['message'], 'berhasil dibuat') !== false) {
        $success_message = '✓ Tabel database telah berhasil dibuat otomatis!';
    }
}

// Ambil parameter
$page = (int)($_GET['page'] ?? 1);
$limit = 15;
$offset = ($page - 1) * $limit;
$search = sanitize_input($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'deleted_at_desc';

// Build query
$where_conditions = ["status = 'in_trash'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR nik LIKE ? OR passport_number LIKE ? OR document_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort
$sort_by = 'deleted_at';
$sort_order = 'DESC';
if ($sort === 'deleted_at_asc') {
    $sort_order = 'ASC';
} elseif ($sort === 'full_name_asc') {
    $sort_by = 'full_name';
    $sort_order = 'ASC';
} elseif ($sort === 'full_name_desc') {
    $sort_by = 'full_name';
    $sort_order = 'DESC';
}

// Get total count
$trash_documents = [];
$total = 0;
$total_pages = 0;

if (empty($error_message)) {
    try {
        $count_sql = "SELECT COUNT(*) as count FROM document_trash WHERE $where_clause";
        $count_result = $db->fetch($count_sql, $params);
        $total = $count_result['count'] ?? 0;
        $total_pages = ceil($total / $limit);

        // Get trash documents
        $sql = "SELECT * FROM document_trash 
                WHERE $where_clause 
                ORDER BY $sort_by $sort_order 
                LIMIT $limit OFFSET $offset";
        $trash_documents = $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        // Jika masih ada error, tampilkan error message
        $error_message = 'Error: ' . $e->getMessage();
    }
}

// Handle restore action
$restore_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'restore') {
        $trash_id = (int)$_POST['trash_id'];
        
        try {
            // Get trash document data
            $trash_doc = $db->fetch("SELECT * FROM document_trash WHERE id = ?", [$trash_id]);
            if (!$trash_doc) {
                $restore_message = '<div class="alert alert-danger">Dokumen sampah tidak ditemukan</div>';
            } else {
                // Restore to documents table
                $restore_sql = "UPDATE documents SET status = 'active' WHERE id = ?";
                $db->execute($restore_sql, [$trash_doc['original_document_id']]);
                
                // Update trash status
                $update_trash_sql = "UPDATE document_trash SET status = 'restored', restored_at = NOW(), restored_by = ? WHERE id = ?";
                $db->execute($update_trash_sql, [$_SESSION['user_id'], $trash_id]);
                
                // Log activity
                log_activity($_SESSION['user_id'], 'RESTORE_DOCUMENT', "Memulihkan dokumen dari sampah: " . $trash_doc['full_name'], $trash_doc['original_document_id']);
                
                $restore_message = '<div class="alert alert-success">✓ Dokumen berhasil dipulihkan!</div>';
                
                // Redirect
                header('Location: trash.php?page=' . $page . '&restored=1');
                exit();
            }
        } catch (Exception $e) {
            $restore_message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } 
    elseif ($_POST['action'] === 'permanent_delete') {
        $trash_id = (int)$_POST['trash_id'];
        
        try {
            $trash_doc = $db->fetch("SELECT * FROM document_trash WHERE id = ?", [$trash_id]);
            if (!$trash_doc) {
                $restore_message = '<div class="alert alert-danger">Dokumen sampah tidak ditemukan</div>';
            } else {
                // Delete from documents table
                $db->execute("DELETE FROM documents WHERE id = ?", [$trash_doc['original_document_id']]);
                
                // Delete file if exists
                if (!empty($trash_doc['file_path']) && file_exists($trash_doc['file_path'])) {
                    unlink($trash_doc['file_path']);
                }
                
                // Update trash status
                $db->execute("UPDATE document_trash SET status = 'permanently_deleted' WHERE id = ?", [$trash_id]);
                
                // Log activity
                log_activity($_SESSION['user_id'], 'PERMANENT_DELETE', "Menghapus permanen dokumen dari sampah: " . $trash_doc['full_name'], $trash_doc['original_document_id']);
                
                $restore_message = '<div class="alert alert-success">✓ Dokumen berhasil dihapus permanen!</div>';
                
                header('Location: trash.php?page=1&permanent=1');
                exit();
            }
        } catch (Exception $e) {
            $restore_message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
    elseif ($_POST['action'] === 'delete_multiple') {
        $trash_ids = $_POST['trash_ids'] ?? [];
        $trash_ids = array_map('intval', $trash_ids);
        
        if (!empty($trash_ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($trash_ids), '?'));
                $documents_to_delete = $db->fetchAll("SELECT * FROM document_trash WHERE id IN ($placeholders)", $trash_ids);
                
                foreach ($documents_to_delete as $trash_doc) {
                    // Delete from documents
                    $db->execute("DELETE FROM documents WHERE id = ?", [$trash_doc['original_document_id']]);
                    
                    // Delete file
                    if (!empty($trash_doc['file_path']) && file_exists($trash_doc['file_path'])) {
                        unlink($trash_doc['file_path']);
                    }
                    
                    // Update status
                    $db->execute("UPDATE document_trash SET status = 'permanently_deleted' WHERE id = ?", [$trash_doc['id']]);
                }
                
                $restore_message = '<div class="alert alert-success">✓ ' . count($trash_ids) . ' dokumen berhasil dihapus permanen!</div>';
                
                header('Location: trash.php?page=1&permanent=1');
                exit();
            } catch (Exception $e) {
                $restore_message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Auto-delete documents older than 30 days
try {
    $auto_delete_sql = "UPDATE document_trash 
                        SET status = 'permanently_deleted' 
                        WHERE status = 'in_trash' 
                        AND restore_deadline < NOW()";
    $db->execute($auto_delete_sql);
} catch (Exception $e) {
    // Log error silently
}

// Helper function for document origin
function format_document_origin_label($origin) {
    switch ($origin) {
        case 'imigrasi_jakarta_pusat_kemayoran':
            return 'Jakarta Pusat Kemayoran';
        case 'imigrasi_ulp_semanggi':
            return 'ULP Semanggi';
        case 'imigrasi_lounge_senayan_city':
            return 'Lounge Senayan City';
        default:
            return $origin ?: '-';
    }
}

// Helper function for days remaining
function days_remaining($restore_deadline) {
    $deadline = new DateTime($restore_deadline);
    $now = new DateTime();
    $interval = $now->diff($deadline);
    return $interval->days;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Sampah - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { padding-top: 56px; }
        .trash-item { 
            transition: all 0.3s ease;
            border-left: 4px solid #dc3545;
        }
        .trash-item:hover {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .days-warning {
            font-size: 0.85rem;
            padding: 0.4rem 0.6rem;
            border-radius: 4px;
        }
        .days-critical { background-color: #ffe6e6; color: #c00; }
        .days-warning-color { background-color: #fff3cd; color: #856404; }
        .days-safe { background-color: #e6f3ff; color: #004085; }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <main class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-trash me-2"></i>Menu Sampah</h1>
                <p class="text-muted">Dokumen yang dihapus dapat dipulihkan dalam 30 hari</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="card">
                    <div class="card-body">
                        <div class="display-6" style="color: #dc3545;">
                            <?php echo $total; ?>
                        </div>
                        <small class="text-muted">Dokumen di Sampah</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['restored'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Dokumen berhasil dipulihkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['permanent'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-trash-alt me-2"></i> Dokumen berhasil dihapus permanen!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> 
                <strong>Error:</strong> <?php echo $error_message; ?>
                <br><br>
                <a href="setup_trash.php" class="btn btn-sm btn-primary" target="_blank">
                    <i class="fas fa-cog me-2"></i>Setup Database Sekarang
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search & Filter -->
        <?php if (empty($error_message)): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, NIK, atau nomor paspor..." value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="deleted_at_desc" <?php echo $sort === 'deleted_at_desc' ? 'selected' : ''; ?>>Terbaru Dihapus</option>
                            <option value="deleted_at_asc" <?php echo $sort === 'deleted_at_asc' ? 'selected' : ''; ?>>Paling Lama Dihapus</option>
                            <option value="full_name_asc" <?php echo $sort === 'full_name_asc' ? 'selected' : ''; ?>>Nama A-Z</option>
                            <option value="full_name_desc" <?php echo $sort === 'full_name_desc' ? 'selected' : ''; ?>>Nama Z-A</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Trash Documents Table -->
        <?php if (!empty($trash_documents)): ?>
            <form method="post" id="trashForm" class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th width="20%">Nama Lengkap</th>
                                    <th width="12%">NIK</th>
                                    <th width="12%">No. Paspor</th>
                                    <th width="10%">Kode Lemari</th>
                                    <th width="15%">Waktu Dihapus</th>
                                    <th width="12%">Sisa Hari</th>
                                    <th width="14%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trash_documents as $doc): ?>
                                    <?php 
                                    $days = days_remaining($doc['restore_deadline']);
                                    $days_class = $days <= 3 ? 'days-critical' : ($days <= 7 ? 'days-warning-color' : 'days-safe');
                                    ?>
                                    <tr class="trash-item">
                                        <td>
                                            <input type="checkbox" name="trash_ids[]" value="<?php echo $doc['id']; ?>" class="form-check-input">
                                        </td>
                                        <td>
                                            <strong><?php echo e($doc['full_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo e($doc['document_number']); ?></small>
                                        </td>
                                        <td><?php echo e($doc['nik'] ?? '-'); ?></td>
                                        <td><?php echo e($doc['passport_number'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo e($doc['locker_code']); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m/Y H:i', strtotime($doc['deleted_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="days-warning <?php echo $days_class; ?>">
                                                <?php echo $days; ?> hari
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="showRestoreModal(<?php echo $doc['id']; ?>, '<?php echo e($doc['full_name']); ?>')" title="Pulihkan dokumen">
                                                <i class="fas fa-undo"></i> Pulihkan
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="showDeleteModal(<?php echo $doc['id']; ?>, '<?php echo e($doc['full_name']); ?>')" title="Hapus permanen">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="card-footer bg-light mt-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display: none;" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                                    <i class="fas fa-trash me-2"></i>Hapus Permanen (Dipilih)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Bulk Delete Modal -->
            <div class="modal fade" id="bulkDeleteModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger">
                            <h5 class="modal-title">Hapus Permanen (Beberapa)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>⚠️ Peringatan!</strong></p>
                            <p>Apakah Anda yakin ingin menghapus dokumen yang dipilih secara permanen?</p>
                            <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan!</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-danger" id="confirmBulkDelete">
                                <i class="fas fa-trash me-2"></i>Hapus Permanen
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4" aria-label="Pagination">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Tidak ada dokumen di sampah
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Static Restore Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Pulihkan Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin memulihkan dokumen ini?</p>
                    <p><strong id="restoreDocName"></strong></p>
                    <small class="text-muted">Dokumen akan dikembalikan ke status aktif</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form id="restoreForm" method="post" style="display: inline;">
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" id="restoreTrashId" name="trash_id" value="">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo me-2"></i>Pulihkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Static Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title">Hapus Permanen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>⚠️ Peringatan!</strong></p>
                    <p>Apakah Anda yakin ingin menghapus dokumen ini secara permanen?</p>
                    <p><strong id="deleteDocName"></strong></p>
                    <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan!</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteForm" method="post" style="display: inline;">
                        <input type="hidden" name="action" value="permanent_delete">
                        <input type="hidden" id="deleteTrashId" name="trash_id" value="">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Hapus Permanen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show restore modal
        function showRestoreModal(trashId, docName) {
            document.getElementById('restoreTrashId').value = trashId;
            document.getElementById('restoreDocName').textContent = docName;
            new bootstrap.Modal(document.getElementById('restoreModal')).show();
        }

        // Show delete modal
        function showDeleteModal(trashId, docName) {
            document.getElementById('deleteTrashId').value = trashId;
            document.getElementById('deleteDocName').textContent = docName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        // Select All Checkbox
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="trash_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkDeleteBtn();
        });

        // Update bulk delete button visibility
        document.querySelectorAll('input[name="trash_ids[]"]').forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteBtn);
        });

        function updateBulkDeleteBtn() {
            const checked = document.querySelectorAll('input[name="trash_ids[]"]:checked');
            document.getElementById('bulkDeleteBtn').style.display = checked.length > 0 ? 'inline-block' : 'none';
        }

        // Confirm bulk delete
        document.getElementById('confirmBulkDelete')?.addEventListener('click', function() {
            const form = document.getElementById('trashForm');
            const action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'delete_multiple';
            form.appendChild(action);
            form.submit();
        });
    </script>
</body>
</html>
