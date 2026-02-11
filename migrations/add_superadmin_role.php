<?php
/**
 * Script untuk menambahkan role 'superadmin' ke tabel users
 * Akses via browser: http://localhost/PROJECT%20ARSIP%20LOKER/migrations/add_superadmin_role.php
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Database - Tambah Role Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-shield me-2"></i>
                            Migrasi Database - Tambah Role Superadmin
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Cek struktur kolom role saat ini
                            $current_structure = $db->fetch("SHOW COLUMNS FROM users WHERE Field = 'role'");
                            
                            if (!$current_structure) {
                                echo '<div class="alert alert-danger">❌ Kolom role tidak ditemukan di tabel users!</div>';
                            } else {
                                echo '<div class="alert alert-info">';
                                echo '<strong>Struktur saat ini:</strong><br>';
                                echo 'Type: ' . htmlspecialchars($current_structure['Type']) . '<br>';
                                echo 'Null: ' . htmlspecialchars($current_structure['Null']) . '<br>';
                                echo 'Default: ' . htmlspecialchars($current_structure['Default'] ?? 'NULL');
                                echo '</div>';
                                
                                // Cek apakah sudah ada 'superadmin' di ENUM
                                if (strpos($current_structure['Type'], 'superadmin') !== false) {
                                    echo '<div class="alert alert-success">';
                                    echo '✅ Role "superadmin" sudah ada di tabel users. Tidak perlu update.';
                                    echo '</div>';
                                } else {
                                    // Update ENUM untuk menambahkan 'superadmin'
                                    echo '<div class="alert alert-warning">';
                                    echo '<strong>Memperbarui struktur tabel...</strong><br>';
                                    echo 'Menambahkan "superadmin" ke ENUM role...';
                                    echo '</div>';
                                    
                                    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'staff') NOT NULL DEFAULT 'staff'";
                                    
                                    $db->execute($sql);
                                    
                                    // Verifikasi
                                    $updated_structure = $db->fetch("SHOW COLUMNS FROM users WHERE Field = 'role'");
                                    
                                    if (strpos($updated_structure['Type'], 'superadmin') !== false) {
                                        echo '<div class="alert alert-success">';
                                        echo '✅ <strong>SUKSES!</strong> Role "superadmin" berhasil ditambahkan.<br>';
                                        echo 'Struktur baru: ' . htmlspecialchars($updated_structure['Type']);
                                        echo '</div>';
                                        echo '<div class="alert alert-info">';
                                        echo 'Sekarang Anda dapat membuat akun superadmin dari dashboard admin.';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="alert alert-danger">';
                                        echo '❌ Gagal memverifikasi update. Silakan cek secara manual.';
                                        echo '</div>';
                                    }
                                }
                            }
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">';
                            echo '❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                            echo '</div>';
                        }
                        ?>
                        
                        <div class="mt-3">
                            <a href="../dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

