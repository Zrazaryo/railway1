<?php
/**
 * Script migrasi untuk menambahkan UNIQUE constraint pada kolom name (Kode Rak) di tabel lockers.
 * Jalankan via browser: http://localhost/PROJECT%20ARSIP%20LOKER/migrations/add_unique_rack_code.php
 * 
 * PERINGATAN: Script ini akan menghapus data duplikat jika ada sebelum menambahkan constraint.
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Add UNIQUE Constraint - Kode Rak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Migrasi: Tambah UNIQUE Constraint pada Kode Rak</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Step 1: Cek apakah sudah ada UNIQUE constraint
                            echo '<h5>Step 1: Mengecek constraint yang ada</h5>';
                            $indexCheck = $db->fetch("
                                SELECT COUNT(*) AS cnt
                                FROM information_schema.statistics
                                WHERE table_schema = ?
                                  AND table_name = 'lockers'
                                  AND column_name = 'name'
                                  AND non_unique = 0
                            ", [DB_NAME]);
                            
                            $hasUnique = (int)($indexCheck['cnt'] ?? 0) > 0;
                            
                            if ($hasUnique) {
                                echo '<div class="alert alert-info">';
                                echo '<i class="fas fa-info-circle me-2"></i>';
                                echo 'UNIQUE constraint pada kolom name (Kode Rak) sudah ada. Tidak perlu melakukan migrasi.';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                                echo 'Belum ada UNIQUE constraint pada kolom name.';
                                echo '</div>';
                                
                                // Step 2: Cek data duplikat
                                echo '<h5 class="mt-4">Step 2: Mengecek data duplikat</h5>';
                                $duplicates = $db->fetchAll("
                                    SELECT name, COUNT(*) AS cnt
                                    FROM lockers
                                    GROUP BY name
                                    HAVING COUNT(*) > 1
                                ");
                                
                                if (!empty($duplicates)) {
                                    echo '<div class="alert alert-danger">';
                                    echo '<i class="fas fa-exclamation-circle me-2"></i>';
                                    echo '<strong>Ditemukan ' . count($duplicates) . ' Kode Rak yang duplikat:</strong>';
                                    echo '<ul class="mt-2">';
                                    foreach ($duplicates as $dup) {
                                        echo '<li><strong>' . htmlspecialchars($dup['name']) . '</strong> - muncul ' . $dup['cnt'] . ' kali</li>';
                                    }
                                    echo '</ul>';
                                    echo '<p class="mt-2"><strong>Peringatan:</strong> Harap hapus atau perbaiki data duplikat terlebih dahulu sebelum menambahkan UNIQUE constraint.</p>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle me-2"></i>';
                                    echo 'Tidak ada data duplikat. Aman untuk menambahkan UNIQUE constraint.';
                                    echo '</div>';
                                    
                                    // Step 3: Tambah UNIQUE constraint
                                    echo '<h5 class="mt-4">Step 3: Menambahkan UNIQUE constraint</h5>';
                                    try {
                                        $db->execute("ALTER TABLE lockers ADD UNIQUE INDEX idx_name_unique (name)");
                                        
                                        echo '<div class="alert alert-success">';
                                        echo '<i class="fas fa-check-circle me-2"></i>';
                                        echo '<strong>Berhasil!</strong> UNIQUE constraint pada kolom name (Kode Rak) telah ditambahkan.';
                                        echo '<br><br>Sekarang Kode Rak tidak boleh duplikat.';
                                        echo '</div>';
                                    } catch (Exception $e) {
                                        echo '<div class="alert alert-danger">';
                                        echo '<i class="fas fa-times-circle me-2"></i>';
                                        echo '<strong>Gagal menambahkan constraint:</strong> ' . htmlspecialchars($e->getMessage());
                                        echo '</div>';
                                    }
                                }
                            }
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">';
                            echo '<i class="fas fa-exclamation-circle me-2"></i>';
                            echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                            echo '</div>';
                        }
                        ?>
                        <div class="mt-4">
                            <a href="../lockers/select.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Lemari
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



