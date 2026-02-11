<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login
require_login();

$error_message = '';
$success_message = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'Semua field password harus diisi';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Password baru tidak cocok';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password baru minimal 6 karakter';
    } else {
        try {
            // Verify current password
            $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$_SESSION['user_id']]);
            
            if (!password_verify($current_password, $user['password'])) {
                $error_message = 'Password lama tidak benar';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashed_password, $_SESSION['user_id']]);
                $success_message = 'Password berhasil diubah';
                log_activity($_SESSION['user_id'], 'PASSWORD_CHANGE', 'Password berhasil diubah');
            }
        } catch (Exception $e) {
            $error_message = 'Error: ' . $e->getMessage();
        }
    }
}

// Get current user info
try {
    $user_info = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
} catch (Exception $e) {
    $error_message = 'Error mengambil data user: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-cog me-2"></i>
                        Pengaturan
                    </h1>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo e($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Profil -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div style="background-color: #0d6efd !important; color: #ffffff !important; padding: 1rem 1.25rem; border-radius: 15px 15px 0 0; border-bottom: 1px solid #0d6efd !important;">
                                <h5 class="mb-0" style="color: #ffffff !important; font-weight: 600 !important; margin: 0 !important;">
                                    <i class="fas fa-user me-2" style="color: #ffffff !important;"></i>
                                    <span style="color: #ffffff !important;">Informasi Profil</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="40%">Username</th>
                                        <td><?php echo e($user_info['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <td><?php echo e($user_info['full_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo e($user_info['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Role</th>
                                        <td>
                                            <span class="badge <?php echo $user_info['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                <?php echo e(ucfirst($user_info['role'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge <?php echo $user_info['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo e(ucfirst($user_info['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Bergabung</th>
                                        <td><?php echo format_date_indonesia($user_info['created_at']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ubah Password -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div style="background-color: #dc3545 !important; color: #ffffff !important; padding: 1rem 1.25rem; border-radius: 15px 15px 0 0; border-bottom: 1px solid #dc3545 !important;">
                                <h5 class="mb-0" style="color: #ffffff !important; font-weight: 600 !important; margin: 0 !important;">
                                    <i class="fas fa-lock me-2" style="color: #ffffff !important;"></i>
                                    <span style="color: #ffffff !important;">Ubah Password</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Password Lama</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-danger w-100">
                                        <i class="fas fa-save me-2"></i>Ubah Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- About & User Guide Section -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm">
                            <div style="background-color: #198754 !important; color: #ffffff !important; padding: 1rem 1.25rem; border-radius: 15px 15px 0 0; border-bottom: 1px solid #198754 !important;">
                                <h5 class="mb-0" style="color: #ffffff !important; font-weight: 600 !important; margin: 0 !important;">
                                    <i class="fas fa-info-circle me-2" style="color: #ffffff !important;"></i>
                                    <span style="color: #ffffff !important;">Bantuan & Panduan</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="fw-bold">Buku Panduan Sistem Arsip Dokumen</h6>
                                        <p class="text-muted mb-3">
                                            Panduan lengkap penggunaan sistem arsip dokumen untuk admin. 
                                            Berisi tutorial step-by-step untuk semua fitur yang tersedia.
                                        </p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge bg-primary">Manajemen Akun</span>
                                            <span class="badge bg-success">Manajemen Lemari</span>
                                            <span class="badge bg-info">Manajemen Dokumen</span>
                                            <span class="badge bg-warning">Pemusnahan</span>
                                            <span class="badge bg-danger">Monitoring</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#userGuideModal">
                                            <i class="fas fa-book me-2"></i>
                                            Buka Panduan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- User Guide Modal -->
    <div class="modal fade" id="userGuideModal" tabindex="-1" aria-labelledby="userGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="userGuideModalLabel">
                        <i class="fas fa-book me-2"></i>
                        Buku Panduan Sistem Arsip Dokumen - Menu Admin
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                    <div class="container-fluid">
                        <!-- Table of Contents -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Isi</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-dark">👥 Manajemen Akun</h6>
                                                <ul class="list-unstyled ms-3">
                                                    <li><a href="#tutorial-1" class="text-decoration-none">1. Tutorial Menambahkan Akun</a></li>
                                                    <li><a href="#tutorial-2" class="text-decoration-none">2. Tutorial Mengedit Akun</a></li>
                                                    <li><a href="#tutorial-3" class="text-decoration-none">3. Tutorial Menghapus Akun</a></li>
                                                </ul>
                                                
                                                <h6 class="text-dark">🗄️ Manajemen Lemari</h6>
                                                <ul class="list-unstyled ms-3">
                                                    <li><a href="#tutorial-4" class="text-decoration-none">4. Tutorial Menambahkan Lemari</a></li>
                                                    <li><a href="#tutorial-5" class="text-decoration-none">5. Tutorial Menghapus Lemari</a></li>
                                                </ul>
                                                
                                                <h6 class="text-dark">📄 Manajemen Dokumen</h6>
                                                <ul class="list-unstyled ms-3">
                                                    <li><a href="#tutorial-6" class="text-decoration-none">6. Tutorial Menambahkan Dokumen</a></li>
                                                    <li><a href="#tutorial-7" class="text-decoration-none">7. Tutorial Melihat Dokumen</a></li>
                                                    <li><a href="#tutorial-8" class="text-decoration-none">8. Tutorial Melihat Detail Lemari</a></li>
                                                    <li><a href="#tutorial-9" class="text-decoration-none">9. Tutorial Melihat Daftar Lemari</a></li>
                                                    <li><a href="#tutorial-10" class="text-decoration-none">10. Tutorial Menghapus Dokumen Terpilih</a></li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="list-unstyled">
                                                    <li><a href="#tutorial-11" class="text-decoration-none">11. Tutorial Menghapus Semua Dokumen</a></li>
                                                    <li><a href="#tutorial-12" class="text-decoration-none">12. Tutorial Mengedit Dokumen</a></li>
                                                    <li><a href="#tutorial-13" class="text-decoration-none">13. Tutorial Mencari Dokumen</a></li>
                                                    <li><a href="#tutorial-14" class="text-decoration-none">14. Tutorial Mengkategorikan Urutan Dokumen</a></li>
                                                    <li><a href="#tutorial-15" class="text-decoration-none">15. Tutorial Mendownload Dokumen</a></li>
                                                    <li><a href="#tutorial-16" class="text-decoration-none">16. Tutorial Mengupload Dokumen</a></li>
                                                </ul>
                                                
                                                <h6 class="text-dark">🗑️ Pemusnahan Dokumen</h6>
                                                <ul class="list-unstyled ms-3">
                                                    <li><a href="#tutorial-17" class="text-decoration-none">17. Tutorial Menambahkan Dokumen ke Lemari Pemusnahan</a></li>
                                                    <li><a href="#tutorial-18" class="text-decoration-none">18. Tutorial Melihat Detail Lemari Pemusnahan</a></li>
                                                </ul>
                                                
                                                <h6 class="text-dark">📊 Monitoring & Laporan</h6>
                                                <ul class="list-unstyled ms-3">
                                                    <li><a href="#tutorial-19" class="text-decoration-none">19. Tutorial Melihat History Aktivitas</a></li>
                                                    <li><a href="#tutorial-20" class="text-decoration-none">20. Tutorial Melihat Laporan Akun</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tutorial Content -->
                        <div class="tutorial-content">
                            <!-- Tutorial 1: Menambahkan Akun -->
                            <div id="tutorial-1" class="mb-5">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">👥 1. Tutorial Menambahkan Akun</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Dashboard Admin → Bagian "Daftar Admin" atau "Daftar Staff"<br>
                                            <strong>🎯 Tujuan:</strong> Menambahkan akun Admin atau Staff baru ke sistem
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Login ke Dashboard Admin</strong>
                                                <ul>
                                                    <li>Buka browser dan akses sistem</li>
                                                    <li>Login dengan akun admin</li>
                                                    <li>Aktivasi GPS jika diminta</li>
                                                </ul>
                                            </li>
                                            <li><strong>Navigasi ke User Management</strong>
                                                <ul>
                                                    <li>Di Dashboard Admin, scroll ke bagian tengah</li>
                                                    <li>Temukan area "Daftar Admin" (kiri) dan "Daftar Staff" (kanan)</li>
                                                </ul>
                                            </li>
                                            <li><strong>Pilih Jenis Akun</strong>
                                                <ul>
                                                    <li><strong>Untuk Admin:</strong> Klik tombol "➕ Tambah" di bagian "Daftar Admin"</li>
                                                    <li><strong>Untuk Staff:</strong> Klik tombol "➕ Tambah" di bagian "Daftar Staff"</li>
                                                </ul>
                                            </li>
                                            <li><strong>Isi Form Tambah User</strong>
                                                <ul>
                                                    <li><strong>Role:</strong> Otomatis terisi sesuai pilihan</li>
                                                    <li><strong>Nama Lengkap:</strong> Masukkan nama lengkap user</li>
                                                    <li><strong>Username:</strong> Buat username unik (huruf kecil, angka, underscore)</li>
                                                    <li><strong>Password:</strong> Buat password kuat (minimal 6 karakter)</li>
                                                </ul>
                                            </li>
                                            <li><strong>Simpan Data</strong>
                                                <ul>
                                                    <li>Klik tombol "💾 Simpan"</li>
                                                    <li>Tunggu konfirmasi berhasil</li>
                                                    <li>Akun baru akan muncul di tabel</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-lightbulb me-2"></i>Tips:</h6>
                                            <ul class="mb-0">
                                                <li>Username harus unik, tidak boleh sama dengan user lain</li>
                                                <li>Password gunakan kombinasi huruf, angka, dan simbol</li>
                                                <li>Catat kredensial untuk diberikan kepada user</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Catatan Penting:</h6>
                                            <ul class="mb-0">
                                                <li>Semua field wajib diisi</li>
                                                <li>Username tidak bisa diubah setelah dibuat</li>
                                                <li>Password akan ditampilkan dalam bentuk bintang (••••••••)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 2: Mengedit Akun -->
                            <div id="tutorial-2" class="mb-5">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">✏️ 2. Tutorial Mengedit Akun</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Dashboard Admin → Tabel Admin/Staff → Tombol Edit<br>
                                            <strong>🎯 Tujuan:</strong> Mengubah informasi akun yang sudah ada
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Temukan Akun yang Ingin Diedit</strong>
                                                <ul>
                                                    <li>Di tabel Admin atau Staff</li>
                                                    <li>Gunakan fitur pencarian jika diperlukan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Klik Tombol Edit</strong>
                                                <ul>
                                                    <li>Klik tombol "✏️" di kolom Aksi</li>
                                                    <li>Modal edit akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Ubah Data yang Diperlukan</strong>
                                                <ul>
                                                    <li><strong>Nama Lengkap:</strong> Dapat diubah</li>
                                                    <li><strong>Username:</strong> Dapat diubah (pastikan tetap unik)</li>
                                                    <li><strong>Password:</strong> Kosongkan jika tidak ingin mengubah</li>
                                                </ul>
                                            </li>
                                            <li><strong>Simpan Perubahan</strong>
                                                <ul>
                                                    <li>Klik "💾 Simpan"</li>
                                                    <li>Perubahan langsung terlihat di tabel</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-lightbulb me-2"></i>Tips:</h6>
                                            <ul class="mb-0">
                                                <li>Kosongkan field password jika tidak ingin mengubah</li>
                                                <li>Username baru harus tetap unik</li>
                                                <li>Perubahan langsung berlaku setelah disimpan</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 3: Menghapus Akun -->
                            <div id="tutorial-3" class="mb-5">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">🗑️ 3. Tutorial Menghapus Akun</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Dashboard Admin → Tabel Admin/Staff → Tombol Hapus<br>
                                            <strong>🎯 Tujuan:</strong> Menghapus akun yang tidak diperlukan lagi
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Temukan Akun yang Ingin Dihapus</strong>
                                                <ul>
                                                    <li>Di tabel Admin atau Staff</li>
                                                    <li>Pastikan akun benar-benar tidak diperlukan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Klik Tombol Hapus</strong>
                                                <ul>
                                                    <li>Klik tombol "🗑️" di kolom Aksi</li>
                                                    <li>Modal konfirmasi akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Konfirmasi Penghapusan</strong>
                                                <ul>
                                                    <li>Modal akan menampilkan detail akun</li>
                                                    <li>Akan muncul soal matematika (contoh: 5 + 3 = ?)</li>
                                                    <li>Jawab soal matematika dengan benar</li>
                                                </ul>
                                            </li>
                                            <li><strong>Konfirmasi Final</strong>
                                                <ul>
                                                    <li>Klik "🗑️ Hapus User"</li>
                                                    <li>Akun akan terhapus dari sistem</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-danger">
                                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Peringatan:</h6>
                                            <ul class="mb-0">
                                                <li>Penghapusan tidak dapat dibatalkan</li>
                                                <li>Pastikan akun benar-benar tidak diperlukan</li>
                                                <li>Soal matematika adalah fitur keamanan</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 4: Menambahkan Lemari -->
                            <div id="tutorial-4" class="mb-5">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">🗄️ 4. Tutorial Menambahkan Lemari</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Sidebar → "Lemari Dokumen" → Tombol Tambah<br>
                                            <strong>🎯 Tujuan:</strong> Membuat lemari/rak dokumen baru
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Navigasi ke Lemari Dokumen</strong>
                                                <ul>
                                                    <li>Klik menu "🗄️ Lemari Dokumen" di sidebar</li>
                                                    <li>Halaman daftar lemari akan terbuka</li>
                                                </ul>
                                            </li>
                                            <li><strong>Klik Tambah Lemari</strong>
                                                <ul>
                                                    <li>Klik tombol "➕ Tambah Lemari"</li>
                                                    <li>Form tambah lemari akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Isi Data Lemari</strong>
                                                <ul>
                                                    <li><strong>Kode Lemari:</strong> Format huruf + angka (A1, B2, C3)</li>
                                                    <li><strong>Nama Lemari:</strong> Format kode + titik + nomor (A1.01, B2.05)</li>
                                                    <li><strong>Deskripsi:</strong> Keterangan lemari (opsional)</li>
                                                </ul>
                                            </li>
                                            <li><strong>Simpan Lemari</strong>
                                                <ul>
                                                    <li>Klik "💾 Simpan"</li>
                                                    <li>Lemari baru akan muncul di daftar</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-info-circle me-2"></i>Format Penamaan:</h6>
                                            <ul class="mb-0">
                                                <li><strong>Kode:</strong> A1, B2, C3 (Huruf + Angka)</li>
                                                <li><strong>Nama:</strong> A1.01, A1.02, B2.01 (Kode + titik + urutan)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 5: Menghapus Lemari -->
                            <div id="tutorial-5" class="mb-5">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">🗑️ 5. Tutorial Menghapus Lemari</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Lemari Dokumen → Tombol Hapus<br>
                                            <strong>🎯 Tujuan:</strong> Menghapus lemari yang tidak digunakan
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Buka Halaman Lemari Dokumen</strong>
                                                <ul>
                                                    <li>Klik menu "🗄️ Lemari Dokumen"</li>
                                                </ul>
                                            </li>
                                            <li><strong>Temukan Lemari yang Ingin Dihapus</strong>
                                                <ul>
                                                    <li>Pastikan lemari kosong (tidak ada dokumen)</li>
                                                </ul>
                                            </li>
                                            <li><strong>Klik Tombol Hapus</strong>
                                                <ul>
                                                    <li>Klik tombol "🗑️ Hapus"</li>
                                                    <li>Modal konfirmasi akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Konfirmasi dengan Soal Matematika</strong>
                                                <ul>
                                                    <li>Jawab soal matematika yang muncul</li>
                                                    <li>Klik "Hapus" untuk konfirmasi final</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-danger">
                                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Peringatan:</h6>
                                            <ul class="mb-0">
                                                <li>Pastikan lemari kosong sebelum dihapus</li>
                                                <li>Penghapusan tidak dapat dibatalkan</li>
                                                <li>Lemari yang berisi dokumen tidak bisa dihapus</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 6: Menambahkan Dokumen -->
                            <div id="tutorial-6" class="mb-5">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">📄 6. Tutorial Menambahkan Dokumen</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Sidebar → "Dokumen" → Tombol Tambah<br>
                                            <strong>🎯 Tujuan:</strong> Menambahkan dokumen baru ke dalam lemari
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Navigasi ke Halaman Dokumen</strong>
                                                <ul>
                                                    <li>Klik menu "📄 Dokumen" di sidebar</li>
                                                    <li>Halaman daftar dokumen akan terbuka</li>
                                                </ul>
                                            </li>
                                            <li><strong>Klik Tambah Dokumen</strong>
                                                <ul>
                                                    <li>Klik tombol "➕ Tambah Dokumen"</li>
                                                    <li>Form tambah dokumen akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Isi Data Dokumen</strong>
                                                <ul>
                                                    <li><strong>Judul Dokumen:</strong> Nama/judul dokumen</li>
                                                    <li><strong>Nomor Dokumen:</strong> Nomor unik dokumen</li>
                                                    <li><strong>Kategori:</strong> Pilih kategori dokumen</li>
                                                    <li><strong>Lemari:</strong> Pilih lemari penyimpanan</li>
                                                    <li><strong>Tanggal Dokumen:</strong> Tanggal pembuatan dokumen</li>
                                                    <li><strong>Deskripsi:</strong> Keterangan tambahan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Upload File (Opsional)</strong>
                                                <ul>
                                                    <li>Klik "Pilih File" untuk upload dokumen</li>
                                                    <li>Format yang didukung: PDF, DOC, DOCX, JPG, PNG</li>
                                                </ul>
                                            </li>
                                            <li><strong>Simpan Dokumen</strong>
                                                <ul>
                                                    <li>Klik "💾 Simpan"</li>
                                                    <li>Dokumen akan tersimpan di lemari yang dipilih</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-lightbulb me-2"></i>Tips:</h6>
                                            <ul class="mb-0">
                                                <li>Gunakan nomor dokumen yang unik dan mudah diingat</li>
                                                <li>Pilih kategori yang sesuai untuk memudahkan pencarian</li>
                                                <li>Upload file asli untuk backup digital</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 7: Melihat Dokumen -->
                            <div id="tutorial-7" class="mb-5">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">👁️ 7. Tutorial Melihat Dokumen</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Dokumen → Tabel Dokumen<br>
                                            <strong>🎯 Tujuan:</strong> Melihat daftar dan detail dokumen
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Cara Melihat Dokumen:</h6>
                                        <ol>
                                            <li><strong>Daftar Dokumen</strong>
                                                <ul>
                                                    <li>Buka menu "📄 Dokumen"</li>
                                                    <li>Semua dokumen ditampilkan dalam tabel</li>
                                                    <li>Informasi: Judul, Nomor, Kategori, Lemari, Tanggal</li>
                                                </ul>
                                            </li>
                                            <li><strong>Detail Dokumen</strong>
                                                <ul>
                                                    <li>Klik tombol "👁️ Detail" pada dokumen</li>
                                                    <li>Modal detail akan menampilkan informasi lengkap</li>
                                                    <li>Termasuk file yang diupload (jika ada)</li>
                                                </ul>
                                            </li>
                                            <li><strong>Filter dan Pencarian</strong>
                                                <ul>
                                                    <li>Gunakan kotak pencarian untuk mencari dokumen</li>
                                                    <li>Filter berdasarkan kategori atau lemari</li>
                                                    <li>Urutkan berdasarkan tanggal atau nama</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle me-2"></i>Informasi yang Ditampilkan:</h6>
                                            <ul class="mb-0">
                                                <li>Judul dan nomor dokumen</li>
                                                <li>Kategori dan lokasi lemari</li>
                                                <li>Tanggal dokumen dan tanggal input</li>
                                                <li>Status dan file attachment</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 8: Melihat Detail Lemari -->
                            <div id="tutorial-8" class="mb-5">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">🗄️ 8. Tutorial Melihat Detail Lemari</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Lemari Dokumen → Tombol Detail<br>
                                            <strong>🎯 Tujuan:</strong> Melihat isi dan informasi detail lemari
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Buka Halaman Lemari</strong>
                                                <ul>
                                                    <li>Klik menu "🗄️ Lemari Dokumen"</li>
                                                    <li>Daftar semua lemari akan ditampilkan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Pilih Lemari</strong>
                                                <ul>
                                                    <li>Klik tombol "👁️ Detail" pada lemari yang ingin dilihat</li>
                                                    <li>Halaman detail lemari akan terbuka</li>
                                                </ul>
                                            </li>
                                            <li><strong>Informasi yang Ditampilkan</strong>
                                                <ul>
                                                    <li><strong>Info Lemari:</strong> Kode, nama, deskripsi</li>
                                                    <li><strong>Statistik:</strong> Jumlah dokumen, kapasitas</li>
                                                    <li><strong>Daftar Dokumen:</strong> Semua dokumen dalam lemari</li>
                                                    <li><strong>Riwayat:</strong> Log aktivitas lemari</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-chart-bar me-2"></i>Statistik Lemari:</h6>
                                            <ul class="mb-0">
                                                <li>Total dokumen tersimpan</li>
                                                <li>Dokumen per kategori</li>
                                                <li>Tanggal dokumen terlama dan terbaru</li>
                                                <li>Status kapasitas lemari</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 9: Melihat Daftar Lemari -->
                            <div id="tutorial-9" class="mb-5">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">📋 9. Tutorial Melihat Daftar Lemari</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Sidebar → "Lemari Dokumen"<br>
                                            <strong>🎯 Tujuan:</strong> Melihat semua lemari dan statusnya
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Informasi yang Ditampilkan:</h6>
                                        <ol>
                                            <li><strong>Tabel Lemari</strong>
                                                <ul>
                                                    <li><strong>Kode Lemari:</strong> Identifikasi unik lemari</li>
                                                    <li><strong>Nama Lemari:</strong> Nama lengkap lemari</li>
                                                    <li><strong>Jumlah Dokumen:</strong> Total dokumen dalam lemari</li>
                                                    <li><strong>Status:</strong> Aktif/Tidak Aktif</li>
                                                    <li><strong>Tanggal Dibuat:</strong> Kapan lemari dibuat</li>
                                                </ul>
                                            </li>
                                            <li><strong>Fitur Pencarian</strong>
                                                <ul>
                                                    <li>Kotak pencarian untuk mencari lemari</li>
                                                    <li>Filter berdasarkan status</li>
                                                    <li>Sorting berdasarkan kode atau nama</li>
                                                </ul>
                                            </li>
                                            <li><strong>Aksi yang Tersedia</strong>
                                                <ul>
                                                    <li>👁️ Detail - Melihat isi lemari</li>
                                                    <li>✏️ Edit - Mengubah info lemari</li>
                                                    <li>🗑️ Hapus - Menghapus lemari kosong</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle me-2"></i>Status Lemari:</h6>
                                            <ul class="mb-0">
                                                <li><span class="badge bg-success">Aktif</span> - Lemari dapat digunakan</li>
                                                <li><span class="badge bg-warning">Penuh</span> - Lemari mendekati kapasitas</li>
                                                <li><span class="badge bg-secondary">Kosong</span> - Lemari tidak berisi dokumen</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 10: Menghapus Dokumen Terpilih -->
                            <div id="tutorial-10" class="mb-5">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">🗑️ 10. Tutorial Menghapus Dokumen Terpilih</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Dokumen → Checkbox → Tombol Hapus Terpilih<br>
                                            <strong>🎯 Tujuan:</strong> Menghapus beberapa dokumen sekaligus
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Buka Halaman Dokumen</strong>
                                                <ul>
                                                    <li>Klik menu "📄 Dokumen"</li>
                                                    <li>Tabel dokumen akan ditampilkan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Pilih Dokumen</strong>
                                                <ul>
                                                    <li>Centang checkbox di kolom pertama untuk setiap dokumen</li>
                                                    <li>Atau centang "Pilih Semua" untuk memilih semua dokumen</li>
                                                    <li>Jumlah dokumen terpilih akan ditampilkan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Hapus Dokumen Terpilih</strong>
                                                <ul>
                                                    <li>Klik tombol "🗑️ Hapus Terpilih"</li>
                                                    <li>Modal konfirmasi akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Konfirmasi Penghapusan</strong>
                                                <ul>
                                                    <li>Daftar dokumen yang akan dihapus ditampilkan</li>
                                                    <li>Jawab soal matematika untuk konfirmasi</li>
                                                    <li>Klik "Hapus" untuk menyelesaikan</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Peringatan:</h6>
                                            <ul class="mb-0">
                                                <li>Penghapusan tidak dapat dibatalkan</li>
                                                <li>File yang diupload juga akan terhapus</li>
                                                <li>Pastikan dokumen benar-benar tidak diperlukan</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 11: Menghapus Semua Dokumen -->
                            <div id="tutorial-11" class="mb-5">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">⚠️ 11. Tutorial Menghapus Semua Dokumen</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-danger">
                                            <strong>📍 Lokasi:</strong> Menu Dokumen → Tombol Hapus Semua<br>
                                            <strong>🎯 Tujuan:</strong> Menghapus seluruh dokumen dalam sistem<br>
                                            <strong>⚠️ PERINGATAN:</strong> Fitur ini sangat berbahaya!
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Akses Menu Dokumen</strong>
                                                <ul>
                                                    <li>Klik menu "📄 Dokumen"</li>
                                                    <li>Pastikan Anda benar-benar ingin menghapus semua</li>
                                                </ul>
                                            </li>
                                            <li><strong>Klik Hapus Semua</strong>
                                                <ul>
                                                    <li>Cari tombol "🗑️ Hapus Semua Dokumen" (biasanya berwarna merah)</li>
                                                    <li>Modal peringatan akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Konfirmasi Bertingkat</strong>
                                                <ul>
                                                    <li><strong>Konfirmasi 1:</strong> Centang "Saya yakin ingin menghapus semua"</li>
                                                    <li><strong>Konfirmasi 2:</strong> Ketik "HAPUS SEMUA" di kotak teks</li>
                                                    <li><strong>Konfirmasi 3:</strong> Jawab soal matematika</li>
                                                </ul>
                                            </li>
                                            <li><strong>Eksekusi Penghapusan</strong>
                                                <ul>
                                                    <li>Klik "HAPUS SEMUA DOKUMEN"</li>
                                                    <li>Proses penghapusan akan berjalan</li>
                                                    <li>Semua dokumen dan file akan terhapus</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-danger">
                                            <h6><i class="fas fa-skull-crossbones me-2"></i>PERINGATAN KERAS:</h6>
                                            <ul class="mb-0">
                                                <li>Semua dokumen akan terhapus PERMANEN</li>
                                                <li>Semua file upload akan terhapus</li>
                                                <li>Tidak ada cara untuk mengembalikan data</li>
                                                <li>Gunakan hanya untuk reset sistem atau testing</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 12: Mengedit Dokumen -->
                            <div id="tutorial-12" class="mb-5">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">✏️ 12. Tutorial Mengedit Dokumen</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Dokumen → Tombol Edit<br>
                                            <strong>🎯 Tujuan:</strong> Mengubah informasi dokumen yang sudah ada
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Temukan Dokumen</strong>
                                                <ul>
                                                    <li>Buka menu "📄 Dokumen"</li>
                                                    <li>Cari dokumen yang ingin diedit</li>
                                                    <li>Gunakan pencarian jika diperlukan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Klik Tombol Edit</strong>
                                                <ul>
                                                    <li>Klik tombol "✏️ Edit" di kolom aksi</li>
                                                    <li>Modal edit dokumen akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Ubah Data Dokumen</strong>
                                                <ul>
                                                    <li><strong>Judul:</strong> Dapat diubah</li>
                                                    <li><strong>Nomor Dokumen:</strong> Dapat diubah (harus tetap unik)</li>
                                                    <li><strong>Kategori:</strong> Pilih kategori baru</li>
                                                    <li><strong>Lemari:</strong> Pindah ke lemari lain</li>
                                                    <li><strong>Tanggal:</strong> Ubah tanggal dokumen</li>
                                                    <li><strong>Deskripsi:</strong> Update keterangan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Update File (Opsional)</strong>
                                                <ul>
                                                    <li>Upload file baru untuk mengganti file lama</li>
                                                    <li>Atau hapus file yang ada</li>
                                                </ul>
                                            </li>
                                            <li><strong>Simpan Perubahan</strong>
                                                <ul>
                                                    <li>Klik "💾 Simpan Perubahan"</li>
                                                    <li>Dokumen akan diupdate dengan data baru</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-lightbulb me-2"></i>Tips Edit:</h6>
                                            <ul class="mb-0">
                                                <li>Nomor dokumen harus tetap unik setelah diedit</li>
                                                <li>Memindah lemari akan mengubah lokasi fisik dokumen</li>
                                                <li>File lama akan terhapus jika diupload file baru</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 13: Mencari Dokumen -->
                            <div id="tutorial-13" class="mb-5">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">🔍 13. Tutorial Mencari Dokumen (Biasa & Lanjutan)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Dokumen → Kotak Pencarian & Filter<br>
                                            <strong>🎯 Tujuan:</strong> Menemukan dokumen dengan cepat dan akurat
                                        </div>
                                        
                                        <h6 class="text-dark">🔍 Pencarian Biasa:</h6>
                                        <ol>
                                            <li><strong>Pencarian Teks</strong>
                                                <ul>
                                                    <li>Ketik kata kunci di kotak pencarian</li>
                                                    <li>Sistem akan mencari di judul, nomor, dan deskripsi</li>
                                                    <li>Hasil muncul secara real-time</li>
                                                </ul>
                                            </li>
                                            <li><strong>Filter Kategori</strong>
                                                <ul>
                                                    <li>Pilih kategori dari dropdown</li>
                                                    <li>Hanya dokumen kategori tersebut yang ditampilkan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Filter Lemari</strong>
                                                <ul>
                                                    <li>Pilih lemari tertentu</li>
                                                    <li>Tampilkan dokumen dari lemari tersebut saja</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">🎯 Pencarian Lanjutan:</h6>
                                        <ol>
                                            <li><strong>Kombinasi Filter</strong>
                                                <ul>
                                                    <li>Gunakan pencarian teks + kategori + lemari bersamaan</li>
                                                    <li>Hasil akan lebih spesifik dan akurat</li>
                                                </ul>
                                            </li>
                                            <li><strong>Pencarian Berdasarkan Tanggal</strong>
                                                <ul>
                                                    <li>Filter berdasarkan rentang tanggal dokumen</li>
                                                    <li>Atau tanggal input ke sistem</li>
                                                </ul>
                                            </li>
                                            <li><strong>Sorting Hasil</strong>
                                                <ul>
                                                    <li>Urutkan berdasarkan tanggal (terbaru/terlama)</li>
                                                    <li>Urutkan berdasarkan nama (A-Z atau Z-A)</li>
                                                    <li>Urutkan berdasarkan nomor dokumen</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-search me-2"></i>Tips Pencarian Efektif:</h6>
                                            <ul class="mb-0">
                                                <li>Gunakan kata kunci spesifik untuk hasil lebih akurat</li>
                                                <li>Kombinasikan beberapa filter untuk pencarian detail</li>
                                                <li>Gunakan wildcard (*) untuk pencarian partial</li>
                                                <li>Reset filter untuk melihat semua dokumen kembali</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 14: Mengkategorikan Urutan Dokumen -->
                            <div id="tutorial-14" class="mb-5">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">📊 14. Tutorial Mengkategorikan Urutan Dokumen</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Dokumen → Header Tabel → Tombol Sort<br>
                                            <strong>🎯 Tujuan:</strong> Mengurutkan dan mengkategorikan tampilan dokumen
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Cara Mengurutkan:</h6>
                                        <ol>
                                            <li><strong>Sort Berdasarkan Kolom</strong>
                                                <ul>
                                                    <li>Klik header kolom yang ingin diurutkan</li>
                                                    <li>Klik sekali: Ascending (A-Z, 1-9, lama-baru)</li>
                                                    <li>Klik dua kali: Descending (Z-A, 9-1, baru-lama)</li>
                                                </ul>
                                            </li>
                                            <li><strong>Kategori Pengurutan</strong>
                                                <ul>
                                                    <li><strong>Judul:</strong> Alfabetis A-Z atau Z-A</li>
                                                    <li><strong>Nomor:</strong> Numerik atau alfanumerik</li>
                                                    <li><strong>Kategori:</strong> Berdasarkan nama kategori</li>
                                                    <li><strong>Lemari:</strong> Berdasarkan kode lemari</li>
                                                    <li><strong>Tanggal:</strong> Kronologis (lama-baru atau baru-lama)</li>
                                                </ul>
                                            </li>
                                            <li><strong>Multi-Level Sorting</strong>
                                                <ul>
                                                    <li>Urutkan berdasarkan kategori dulu</li>
                                                    <li>Kemudian urutkan berdasarkan tanggal</li>
                                                    <li>Untuk pengelompokan yang lebih terstruktur</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">📂 Pengelompokan Kategori:</h6>
                                        <ol>
                                            <li><strong>Group by Category</strong>
                                                <ul>
                                                    <li>Aktifkan mode "Group by Category"</li>
                                                    <li>Dokumen akan dikelompokkan berdasarkan kategori</li>
                                                    <li>Setiap kategori memiliki header tersendiri</li>
                                                </ul>
                                            </li>
                                            <li><strong>Group by Locker</strong>
                                                <ul>
                                                    <li>Kelompokkan berdasarkan lemari penyimpanan</li>
                                                    <li>Memudahkan melihat isi setiap lemari</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-sort me-2"></i>Indikator Pengurutan:</h6>
                                            <ul class="mb-0">
                                                <li>🔼 Panah naik: Ascending (A-Z, 1-9, lama-baru)</li>
                                                <li>🔽 Panah turun: Descending (Z-A, 9-1, baru-lama)</li>
                                                <li>Header yang aktif akan tersorot</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 15: Mendownload Dokumen -->
                            <div id="tutorial-15" class="mb-5">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">⬇️ 15. Tutorial Mendownload Dokumen</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Dokumen → Tombol Download<br>
                                            <strong>🎯 Tujuan:</strong> Mengunduh file dokumen yang tersimpan
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Cara Download:</h6>
                                        <ol>
                                            <li><strong>Download File Individual</strong>
                                                <ul>
                                                    <li>Temukan dokumen yang ingin didownload</li>
                                                    <li>Klik tombol "⬇️ Download" di kolom aksi</li>
                                                    <li>File akan terdownload ke folder Downloads</li>
                                                </ul>
                                            </li>
                                            <li><strong>Download dari Detail</strong>
                                                <ul>
                                                    <li>Klik "👁️ Detail" pada dokumen</li>
                                                    <li>Di modal detail, klik "⬇️ Download File"</li>
                                                    <li>File akan terdownload dengan nama asli</li>
                                                </ul>
                                            </li>
                                            <li><strong>Download Multiple (Batch)</strong>
                                                <ul>
                                                    <li>Centang checkbox beberapa dokumen</li>
                                                    <li>Klik "⬇️ Download Terpilih"</li>
                                                    <li>File akan didownload dalam bentuk ZIP</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">📦 Format Download:</h6>
                                        <ul>
                                            <li><strong>Single File:</strong> Format asli (PDF, DOC, JPG, dll)</li>
                                            <li><strong>Multiple Files:</strong> Archive ZIP berisi semua file</li>
                                            <li><strong>Nama File:</strong> [Nomor]_[Judul].[ekstensi]</li>
                                        </ul>
                                        
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-info-circle me-2"></i>Catatan Download:</h6>
                                            <ul class="mb-0">
                                                <li>Hanya dokumen yang memiliki file yang bisa didownload</li>
                                                <li>Dokumen tanpa file akan menampilkan pesan error</li>
                                                <li>Download batch dibatasi maksimal 50 file</li>
                                                <li>File besar mungkin membutuhkan waktu lebih lama</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 16: Mengupload Dokumen -->
                            <div id="tutorial-16" class="mb-5">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">⬆️ 16. Tutorial Mengupload Dokumen</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Form Tambah/Edit Dokumen → Area Upload<br>
                                            <strong>🎯 Tujuan:</strong> Mengunggah file digital dokumen
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Cara Upload:</h6>
                                        <ol>
                                            <li><strong>Saat Menambah Dokumen Baru</strong>
                                                <ul>
                                                    <li>Buka form "Tambah Dokumen"</li>
                                                    <li>Isi data dokumen terlebih dahulu</li>
                                                    <li>Di bagian "Upload File", klik "Pilih File"</li>
                                                    <li>Pilih file dari komputer Anda</li>
                                                </ul>
                                            </li>
                                            <li><strong>Saat Mengedit Dokumen</strong>
                                                <ul>
                                                    <li>Buka form "Edit Dokumen"</li>
                                                    <li>Jika sudah ada file, akan ditampilkan nama file lama</li>
                                                    <li>Klik "Ganti File" untuk upload file baru</li>
                                                    <li>File lama akan tergantikan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Drag & Drop Upload</strong>
                                                <ul>
                                                    <li>Drag file dari explorer ke area upload</li>
                                                    <li>Drop file di area yang ditandai</li>
                                                    <li>File akan otomatis terupload</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">📄 Format File yang Didukung:</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul>
                                                    <li><strong>Dokumen:</strong> PDF, DOC, DOCX</li>
                                                    <li><strong>Spreadsheet:</strong> XLS, XLSX</li>
                                                    <li><strong>Presentasi:</strong> PPT, PPTX</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul>
                                                    <li><strong>Gambar:</strong> JPG, JPEG, PNG, GIF</li>
                                                    <li><strong>Teks:</strong> TXT, RTF</li>
                                                    <li><strong>Maksimal:</strong> 10MB per file</li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Aturan Upload:</h6>
                                            <ul class="mb-0">
                                                <li>Ukuran file maksimal 10MB</li>
                                                <li>Nama file tidak boleh mengandung karakter khusus</li>
                                                <li>File akan disimpan dengan nama yang aman</li>
                                                <li>Upload file baru akan menggantikan file lama</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 17: Menambahkan Dokumen ke Lemari Pemusnahan -->
                            <div id="tutorial-17" class="mb-5">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">🗑️ 17. Tutorial Menambahkan Dokumen ke Lemari Pemusnahan</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning">
                                            <strong>📍 Lokasi:</strong> Menu Dokumen → Tombol Pemusnahan<br>
                                            <strong>🎯 Tujuan:</strong> Memindahkan dokumen ke lemari pemusnahan<br>
                                            <strong>⚠️ Catatan:</strong> Dokumen akan dijadwalkan untuk dimusnahkan
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Langkah-langkah:</h6>
                                        <ol>
                                            <li><strong>Pilih Dokumen untuk Pemusnahan</strong>
                                                <ul>
                                                    <li>Buka menu "📄 Dokumen"</li>
                                                    <li>Centang checkbox dokumen yang akan dimusnahkan</li>
                                                    <li>Atau pilih satu dokumen dengan klik tombol "🗑️ Musnahkan"</li>
                                                </ul>
                                            </li>
                                            <li><strong>Klik Tombol Pemusnahan</strong>
                                                <ul>
                                                    <li>Untuk multiple: "🗑️ Musnahkan Terpilih"</li>
                                                    <li>Untuk single: "🗑️ Musnahkan" di kolom aksi</li>
                                                    <li>Modal pemusnahan akan muncul</li>
                                                </ul>
                                            </li>
                                            <li><strong>Isi Data Pemusnahan</strong>
                                                <ul>
                                                    <li><strong>Alasan Pemusnahan:</strong> Pilih dari dropdown</li>
                                                    <li><strong>Tanggal Rencana:</strong> Kapan akan dimusnahkan</li>
                                                    <li><strong>Keterangan:</strong> Catatan tambahan</li>
                                                    <li><strong>Petugas:</strong> Nama petugas yang bertanggung jawab</li>
                                                </ul>
                                            </li>
                                            <li><strong>Konfirmasi Pemusnahan</strong>
                                                <ul>
                                                    <li>Review daftar dokumen yang akan dimusnahkan</li>
                                                    <li>Jawab soal matematika untuk konfirmasi</li>
                                                    <li>Klik "Pindah ke Lemari Pemusnahan"</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">📋 Alasan Pemusnahan:</h6>
                                        <ul>
                                            <li>Masa retensi habis</li>
                                            <li>Dokumen rusak/tidak terbaca</li>
                                            <li>Duplikasi dokumen</li>
                                            <li>Tidak relevan lagi</li>
                                            <li>Permintaan pemilik dokumen</li>
                                            <li>Lainnya (sebutkan dalam keterangan)</li>
                                        </ul>
                                        
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle me-2"></i>Status Setelah Pemusnahan:</h6>
                                            <ul class="mb-0">
                                                <li>Dokumen dipindah ke "Lemari Pemusnahan"</li>
                                                <li>Status berubah menjadi "Menunggu Pemusnahan"</li>
                                                <li>Dokumen tidak muncul di daftar dokumen aktif</li>
                                                <li>Masih bisa dibatalkan sebelum tanggal pemusnahan</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 18: Melihat Detail Lemari Pemusnahan -->
                            <div id="tutorial-18" class="mb-5">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">🗂️ 18. Tutorial Melihat Detail Lemari Pemusnahan</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Sidebar → "Lemari Pemusnahan"<br>
                                            <strong>🎯 Tujuan:</strong> Melihat dokumen yang dijadwalkan untuk dimusnahkan
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Cara Mengakses:</h6>
                                        <ol>
                                            <li><strong>Buka Menu Pemusnahan</strong>
                                                <ul>
                                                    <li>Klik menu "🗑️ Lemari Pemusnahan" di sidebar</li>
                                                    <li>Halaman lemari pemusnahan akan terbuka</li>
                                                </ul>
                                            </li>
                                            <li><strong>Lihat Daftar Dokumen</strong>
                                                <ul>
                                                    <li>Semua dokumen yang dijadwalkan pemusnahan ditampilkan</li>
                                                    <li>Informasi: Judul, Tanggal Rencana, Alasan, Status</li>
                                                </ul>
                                            </li>
                                            <li><strong>Filter Berdasarkan Status</strong>
                                                <ul>
                                                    <li><span class="badge bg-warning">Menunggu</span> - Belum waktunya dimusnahkan</li>
                                                    <li><span class="badge bg-danger">Siap Musnahkan</span> - Sudah waktunya</li>
                                                    <li><span class="badge bg-success">Selesai</span> - Sudah dimusnahkan</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">📊 Informasi yang Ditampilkan:</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul>
                                                    <li>Judul dan nomor dokumen</li>
                                                    <li>Lemari asal dokumen</li>
                                                    <li>Tanggal rencana pemusnahan</li>
                                                    <li>Alasan pemusnahan</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul>
                                                    <li>Petugas yang bertanggung jawab</li>
                                                    <li>Status pemusnahan</li>
                                                    <li>Tanggal input ke lemari pemusnahan</li>
                                                    <li>Keterangan tambahan</li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <h6 class="text-dark">⚡ Aksi yang Tersedia:</h6>
                                        <ul>
                                            <li><strong>👁️ Detail:</strong> Melihat informasi lengkap dokumen</li>
                                            <li><strong>↩️ Batalkan:</strong> Mengembalikan dokumen ke lemari aktif</li>
                                            <li><strong>✅ Musnahkan:</strong> Menandai dokumen sudah dimusnahkan</li>
                                            <li><strong>📝 Edit Jadwal:</strong> Mengubah tanggal rencana pemusnahan</li>
                                        </ul>
                                        
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-calendar-alt me-2"></i>Jadwal Pemusnahan:</h6>
                                            <ul class="mb-0">
                                                <li>Dokumen dengan tanggal ≤ hari ini akan berstatus "Siap Musnahkan"</li>
                                                <li>Sistem akan mengirim notifikasi H-7 sebelum pemusnahan</li>
                                                <li>Pemusnahan harus dikonfirmasi manual oleh admin</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 19: Melihat History Aktivitas -->
                            <div id="tutorial-19" class="mb-5">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">📊 19. Tutorial Melihat History Aktivitas (Log Aktivitas)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Menu Sidebar → "Log Aktivitas"<br>
                                            <strong>🎯 Tujuan:</strong> Memantau semua aktivitas yang terjadi di sistem
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Cara Mengakses:</h6>
                                        <ol>
                                            <li><strong>Buka Menu Log</strong>
                                                <ul>
                                                    <li>Klik menu "📊 Log Aktivitas" di sidebar</li>
                                                    <li>Halaman log aktivitas akan terbuka</li>
                                                </ul>
                                            </li>
                                            <li><strong>Lihat Daftar Aktivitas</strong>
                                                <ul>
                                                    <li>Semua aktivitas ditampilkan dalam tabel</li>
                                                    <li>Urutkan dari yang terbaru ke terlama</li>
                                                </ul>
                                            </li>
                                            <li><strong>Filter Aktivitas</strong>
                                                <ul>
                                                    <li><strong>Berdasarkan User:</strong> Pilih user tertentu</li>
                                                    <li><strong>Berdasarkan Jenis:</strong> Login, CRUD, dll</li>
                                                    <li><strong>Berdasarkan Tanggal:</strong> Rentang waktu tertentu</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">📝 Jenis Aktivitas yang Dicatat:</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-dark">👤 Aktivitas User:</h6>
                                                <ul>
                                                    <li>LOGIN - User masuk sistem</li>
                                                    <li>LOGOUT - User keluar sistem</li>
                                                    <li>PASSWORD_CHANGE - Ubah password</li>
                                                    <li>PROFILE_UPDATE - Update profil</li>
                                                </ul>
                                                
                                                <h6 class="text-dark">🗄️ Aktivitas Lemari:</h6>
                                                <ul>
                                                    <li>LOCKER_CREATE - Buat lemari baru</li>
                                                    <li>LOCKER_UPDATE - Edit lemari</li>
                                                    <li>LOCKER_DELETE - Hapus lemari</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-dark">📄 Aktivitas Dokumen:</h6>
                                                <ul>
                                                    <li>DOCUMENT_CREATE - Tambah dokumen</li>
                                                    <li>DOCUMENT_UPDATE - Edit dokumen</li>
                                                    <li>DOCUMENT_DELETE - Hapus dokumen</li>
                                                    <li>DOCUMENT_DOWNLOAD - Download file</li>
                                                    <li>DOCUMENT_MOVE_TO_DESTRUCTION - Pindah ke pemusnahan</li>
                                                </ul>
                                                
                                                <h6 class="text-dark">🗑️ Aktivitas Pemusnahan:</h6>
                                                <ul>
                                                    <li>DESTRUCTION_SCHEDULE - Jadwal pemusnahan</li>
                                                    <li>DESTRUCTION_CANCEL - Batal musnahkan</li>
                                                    <li>DESTRUCTION_COMPLETE - Selesai musnahkan</li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <h6 class="text-dark">📊 Informasi Log:</h6>
                                        <ul>
                                            <li><strong>Timestamp:</strong> Tanggal dan waktu aktivitas</li>
                                            <li><strong>User:</strong> Siapa yang melakukan aktivitas</li>
                                            <li><strong>Jenis:</strong> Tipe aktivitas yang dilakukan</li>
                                            <li><strong>Deskripsi:</strong> Detail aktivitas</li>
                                            <li><strong>IP Address:</strong> Alamat IP user</li>
                                            <li><strong>User Agent:</strong> Browser dan device yang digunakan</li>
                                        </ul>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-filter me-2"></i>Tips Monitoring:</h6>
                                            <ul class="mb-0">
                                                <li>Gunakan filter tanggal untuk melihat aktivitas periode tertentu</li>
                                                <li>Monitor aktivitas login untuk keamanan</li>
                                                <li>Cek aktivitas penghapusan untuk audit</li>
                                                <li>Export log untuk laporan eksternal</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tutorial 20: Melihat Laporan Akun -->
                            <div id="tutorial-20" class="mb-5">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">📈 20. Tutorial Melihat Laporan Akun</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <strong>📍 Lokasi:</strong> Dashboard Admin → Statistik & Laporan<br>
                                            <strong>🎯 Tujuan:</strong> Melihat laporan dan statistik penggunaan akun
                                        </div>
                                        
                                        <h6 class="text-dark">📋 Jenis Laporan:</h6>
                                        <ol>
                                            <li><strong>Laporan Aktivitas User</strong>
                                                <ul>
                                                    <li>Frekuensi login per user</li>
                                                    <li>Waktu terakhir login</li>
                                                    <li>Jumlah aktivitas per hari/minggu/bulan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Statistik Dokumen per User</strong>
                                                <ul>
                                                    <li>Jumlah dokumen yang ditambahkan</li>
                                                    <li>Jumlah dokumen yang diedit</li>
                                                    <li>Jumlah dokumen yang dihapus</li>
                                                </ul>
                                            </li>
                                            <li><strong>Laporan Penggunaan Sistem</strong>
                                                <ul>
                                                    <li>User paling aktif</li>
                                                    <li>Waktu puncak penggunaan</li>
                                                    <li>Fitur yang paling sering digunakan</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">📊 Cara Mengakses Laporan:</h6>
                                        <ol>
                                            <li><strong>Dashboard Statistik</strong>
                                                <ul>
                                                    <li>Di dashboard admin, lihat bagian "Statistik"</li>
                                                    <li>Grafik dan angka statistik ditampilkan</li>
                                                </ul>
                                            </li>
                                            <li><strong>Laporan Detail</strong>
                                                <ul>
                                                    <li>Klik "📊 Lihat Laporan Detail"</li>
                                                    <li>Pilih jenis laporan yang diinginkan</li>
                                                    <li>Tentukan rentang tanggal</li>
                                                </ul>
                                            </li>
                                            <li><strong>Export Laporan</strong>
                                                <ul>
                                                    <li>Klik "📄 Export PDF" atau "📊 Export Excel"</li>
                                                    <li>Laporan akan terdownload</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        
                                        <h6 class="text-dark">📈 Metrik yang Dilacak:</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-dark">👥 Metrik User:</h6>
                                                <ul>
                                                    <li>Total user aktif</li>
                                                    <li>User baru per periode</li>
                                                    <li>Rata-rata session duration</li>
                                                    <li>Login success rate</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-dark">📄 Metrik Dokumen:</h6>
                                                <ul>
                                                    <li>Dokumen ditambahkan per user</li>
                                                    <li>Dokumen diedit per user</li>
                                                    <li>Dokumen didownload per user</li>
                                                    <li>Dokumen dimusnahkan per user</li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <h6 class="text-dark">🎯 Filter Laporan:</h6>
                                        <ul>
                                            <li><strong>Periode:</strong> Harian, Mingguan, Bulanan, Custom</li>
                                            <li><strong>User:</strong> Semua user atau user tertentu</li>
                                            <li><strong>Jenis Aktivitas:</strong> Login, CRUD, Download, dll</li>
                                            <li><strong>Status:</strong> Aktif, Tidak Aktif, Semua</li>
                                        </ul>
                                        
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-chart-line me-2"></i>Manfaat Laporan:</h6>
                                            <ul class="mb-0">
                                                <li>Monitoring performa user</li>
                                                <li>Identifikasi user yang tidak aktif</li>
                                                <li>Analisis pola penggunaan sistem</li>
                                                <li>Perencanaan kapasitas dan maintenance</li>
                                                <li>Audit trail untuk compliance</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Penutup Tutorial -->
                            <div class="alert alert-success text-center">
                                <h5><i class="fas fa-check-circle me-2"></i>Tutorial Lengkap Selesai!</h5>
                                <p class="mb-0">
                                    Anda telah menyelesaikan semua 20 tutorial untuk sistem arsip dokumen. 
                                    Panduan ini mencakup semua fitur utama dari manajemen akun hingga pelaporan.
                                </p>
                                <hr>
                                <p class="mb-0">
                                    <strong>💡 Tips:</strong> Bookmark halaman ini atau cetak panduan untuk referensi cepat. 
                                    Jika ada pertanyaan, hubungi administrator sistem.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Tutup
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printGuide()">
                        <i class="fas fa-print me-2"></i>Cetak Panduan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Override all colored text in user guide modal to black */
        #userGuideModal .text-primary,
        #userGuideModal .text-success,
        #userGuideModal .text-info,
        #userGuideModal .text-warning,
        #userGuideModal .text-danger {
            color: #000000 !important;
        }
    </style>
    <script>
        // Function to print the guide
        function printGuide() {
            const printContent = document.querySelector('#userGuideModal .modal-body').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Buku Panduan Sistem Arsip Dokumen</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
                    <style>
                        @media print {
                            .no-print { display: none !important; }
                            body { font-size: 12px; }
                            .card { break-inside: avoid; margin-bottom: 20px; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container-fluid">
                        <div class="text-center mb-4">
                            <h1>📖 Buku Panduan Sistem Arsip Dokumen</h1>
                            <h2>Menu Admin</h2>
                            <hr>
                        </div>
                        ${printContent}
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Smooth scroll for internal links
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('#userGuideModal a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        });
    </script>
</body>
</html>



























