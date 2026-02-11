<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

try {
    // Pastikan tabel lockers ada
    $tableCheck = $db->fetch("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'lockers'", [DB_NAME]);
    if (!$tableCheck || (int)$tableCheck['cnt'] === 0) {
        throw new Exception('Tabel "lockers" belum dibuat. Jalankan skrip SQL update_lockers_schema.sql di folder utama project.');
    }

    // Ambil data lemari + jumlah dokumen aktif per rak
    $sql = "
        SELECT 
            l.id,
            l.code,
            l.name,
            l.max_capacity,
            COUNT(d.id) AS used_count
        FROM lockers l
        LEFT JOIN documents d 
            -- month_number menyimpan nama/kode rak yang sama dengan l.name
            ON d.month_number = l.name 
            AND d.status = 'active'
        GROUP BY l.id, l.code, l.name, l.max_capacity
        ORDER BY CAST(REPLACE(SUBSTRING(l.code, 2), '.', '') AS UNSIGNED) ASC, SUBSTRING(l.code, 1, 1) ASC
    ";
    $lockers = $db->fetchAll($sql);
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $lockers = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lemari Dokumen - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Lemari Dokumen</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (is_admin()): ?>
                        <a href="add.php" class="btn btn-primary me-2" title="Tambah Lemari Baru">
                            <i class="fas fa-plus"></i> Tambah Lemari
                        </a>
                    <?php endif; ?>
                    <?php if (is_superadmin()): ?>
                        <a href="../superadmin/dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    <?php elseif (is_admin()): ?>
                        <a href="../dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    <?php else: ?>
                        <a href="../staff/dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message) && $error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo e($error_message); ?>
                </div>
            <?php else: ?>
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-archive me-2"></i>
                            Daftar Lemari (A s/d Z)
                        </h5>
                        <?php if (is_admin()): ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                <i class="fas fa-trash"></i> Hapus Terpilih
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <?php if (is_admin()): ?>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" title="Pilih Semua">
                                    </th>
                                    <?php endif; ?>
                                    <th style="width: 80px;">No</th>
                                    <th>Kode Lemari</th>
                                    <th>Nama Rak</th>
                                    <th>Max Capacity</th>
                                    <th>Used</th>
                                    <th style="width: 120px;">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($lockers)): ?>
                                    <tr>
                                        <td colspan="<?php echo is_admin() ? '7' : '6'; ?>" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Data lemari belum tersedia.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($lockers as $locker): ?>
                                        <?php
                                        $used = (int)$locker['used_count'];
                                        $max = (int)$locker['max_capacity'];
                                        $percent_raw = $max > 0 ? ($used / $max) * 100 : 0;
                                        $percent_display = ($used > 0 && $percent_raw < 1) ? 1 : round($percent_raw);
                                        $has_documents = $used > 0; // Cek apakah ada dokumen
                                        $is_full = $used >= $max; // Cek apakah lemari penuh
                                        $badge_class = 'bg-success';
                                        if ($percent_display >= 90) {
                                            $badge_class = 'bg-danger';
                                        } elseif ($percent_display >= 70) {
                                            $badge_class = 'bg-warning text-dark';
                                        }
                                        ?>
                                        <tr>
                                            <?php if (is_admin()): ?>
                                            <td>
                                                <input type="checkbox" class="locker-checkbox" value="<?php echo $locker['id']; ?>">
                                            </td>
                                            <?php endif; ?>
                                            <td class="text-muted fw-semibold"><?php echo $no++; ?></td>
                                            <td class="fw-semibold"><?php echo e($locker['code']); ?></td>
                                            <td><?php echo e($locker['name']); ?></td>
                                            <td><?php echo number_format($max); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo number_format($used); ?> (<?php echo $percent_display; ?>%)
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!is_superadmin()): ?>
                                                    <?php if (!$is_full): ?>
                                                        <!-- Tombol Buat Dokumen -->
                                                        <a href="../documents/add.php?locker=<?php echo urlencode($locker['name']); ?>"
                                                           class="btn btn-sm btn-primary" title="Buat Dokumen Baru">
                                                            <i class="fas fa-plus"></i> Buat
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-secondary" disabled title="Lemari penuh">
                                                            <i class="fas fa-lock"></i> Penuh
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <!-- Kirim nama rak sebagai parameter ke halaman detail -->
                                                <a href="detail.php?code=<?php echo urlencode($locker['name']); ?>"
                                                   class="btn btn-sm btn-info ms-1" title="Lihat Detail Lemari">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </a>
                                                <?php if (is_admin()): ?>
                                                    <?php if ($has_documents): ?>
                                                        <button class="btn btn-sm btn-danger ms-1" disabled 
                                                                title="Tidak bisa menghapus lemari karena masih ada dokumen aktif">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger ms-1"
                                                                title="Hapus Lemari"
                                                                onclick="confirmDeleteLocker(<?php echo $locker['id']; ?>, '<?php echo e($locker['name']); ?>')">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">
                            Kapasitas setiap lemari adalah <strong>600 dokumen</strong>.
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal Konfirmasi Penghapusan -->
<div class="modal fade" id="deleteLockerModal" tabindex="-1" aria-labelledby="deleteLockerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteLockerModalLabel">
                    <i class="fas fa-trash me-2"></i>Konfirmasi Penghapusan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteLockerMessage"></p>
                <div class="mb-3">
                    <label for="deleteAnswer" class="form-label">Jawaban Anda</label>
                    <input type="number" class="form-control" id="deleteAnswer" placeholder="Masukkan hasil penjumlahan">
                    <small class="form-text text-muted">Penghapusan hanya akan dilanjutkan jika jawaban benar.</small>
                </div>
                <input type="hidden" id="deleteLockerId">
                <input type="hidden" id="deleteLockerCorrectAnswer">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteLockerBtn">
                    <i class="fas fa-trash me-2"></i>Hapus Lemari
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Penghapusan Multiple -->
<div class="modal fade" id="deleteMultipleLockerModal" tabindex="-1" aria-labelledby="deleteMultipleLockerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteMultipleLockerModalLabel">
                    <i class="fas fa-trash me-2"></i>Konfirmasi Penghapusan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteMultipleLockerMessage"></p>
                <div class="mb-3">
                    <label for="deleteMultipleAnswer" class="form-label">Jawaban Anda</label>
                    <input type="number" class="form-control" id="deleteMultipleAnswer" placeholder="Masukkan hasil penjumlahan">
                    <small class="form-text text-muted">Penghapusan hanya akan dilanjutkan jika jawaban benar.</small>
                </div>
                <input type="hidden" id="deleteMultipleLockerIds">
                <input type="hidden" id="deleteMultipleLockerCorrectAnswer">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteMultipleLockerBtn">
                    <i class="fas fa-trash me-2"></i>Hapus Lemari
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let deleteLockerModal;
    let deleteMultipleLockerModal;
    
    document.addEventListener('DOMContentLoaded', function() {
        deleteLockerModal = new bootstrap.Modal(document.getElementById('deleteLockerModal'));
        deleteMultipleLockerModal = new bootstrap.Modal(document.getElementById('deleteMultipleLockerModal'));
    });

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.locker-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
    }

    function getSelectedLockerIds() {
        return Array.from(document.querySelectorAll('.locker-checkbox:checked'))
            .map(cb => parseInt(cb.value, 10))
            .filter(id => !isNaN(id));
    }

    function confirmDeleteLocker(lockerId, lockerName) {
        const num1 = Math.floor(Math.random() * 10) + 1;
        const num2 = Math.floor(Math.random() * 10) + 1;
        const correctAnswer = num1 + num2;
        
        document.getElementById('deleteLockerId').value = lockerId;
        document.getElementById('deleteLockerCorrectAnswer').value = correctAnswer;
        document.getElementById('deleteLockerMessage').innerHTML = 
            `Anda akan menghapus lemari <strong>${lockerName}</strong>.<br>` +
            `Untuk konfirmasi, jawab pertanyaan berikut:<br>` +
            `<span class="fw-bold">${num1} + ${num2} = ?</span>`;
        document.getElementById('deleteAnswer').value = '';
        
        if (deleteLockerModal) {
            deleteLockerModal.show();
        }
    }

    document.getElementById('confirmDeleteLockerBtn').addEventListener('click', function() {
        const answer = parseInt(document.getElementById('deleteAnswer').value, 10);
        const correctAnswer = parseInt(document.getElementById('deleteLockerCorrectAnswer').value, 10);
        const lockerId = document.getElementById('deleteLockerId').value;
        
        if (isNaN(answer) || answer !== correctAnswer) {
            alert('Jawaban salah! Penghapusan dibatalkan.');
            return;
        }
        
        window.location.href = `delete.php?id=${lockerId}`;
    });

    function deleteSelected() {
        const ids = getSelectedLockerIds();
        if (!ids.length) {
            alert('Pilih minimal satu lemari untuk dihapus!');
            return;
        }

        const num1 = Math.floor(Math.random() * 10) + 1;
        const num2 = Math.floor(Math.random() * 10) + 1;
        const correctAnswer = num1 + num2;
        
        document.getElementById('deleteMultipleLockerIds').value = JSON.stringify(ids);
        document.getElementById('deleteMultipleLockerCorrectAnswer').value = correctAnswer;
        document.getElementById('deleteMultipleLockerMessage').innerHTML = 
            `Anda akan menghapus <strong>${ids.length}</strong> lemari.<br>` +
            `Untuk konfirmasi, jawab pertanyaan berikut:<br>` +
            `<span class="fw-bold">${num1} + ${num2} = ?</span>`;
        document.getElementById('deleteMultipleAnswer').value = '';
        
        if (deleteMultipleLockerModal) {
            deleteMultipleLockerModal.show();
        }
    }

    document.getElementById('confirmDeleteMultipleLockerBtn').addEventListener('click', function() {
        const answer = parseInt(document.getElementById('deleteMultipleAnswer').value, 10);
        const correctAnswer = parseInt(document.getElementById('deleteMultipleLockerCorrectAnswer').value, 10);
        const idsJson = document.getElementById('deleteMultipleLockerIds').value;
        
        if (isNaN(answer) || answer !== correctAnswer) {
            alert('Jawaban salah! Penghapusan dibatalkan.');
            return;
        }
        
        const ids = JSON.parse(idsJson);
        
        fetch('delete_multiple.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        })
            .then(response => response.json())
            .then(data => {
                if (deleteMultipleLockerModal) {
                    deleteMultipleLockerModal.hide();
                }
                if (data.success) {
                    alert(`Berhasil menghapus ${data.deleted_count} lemari${data.failed_count > 0 ? '. ' + data.failed_count + ' gagal dihapus.' : ''}`);
                    if (data.errors && data.errors.length > 0) {
                        alert('Alasan gagal:\n' + data.errors.join('\n'));
                    }
                    location.reload();
                } else {
                    alert('Gagal menghapus lemari: ' + (data.message || 'Unknown error'));
                    if (data.errors && data.errors.length > 0) {
                        alert('Alasan gagal:\n' + data.errors.join('\n'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus lemari');
            });
    });
</script>
</body>
</html>

