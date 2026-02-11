<?php
/**
 * Script untuk cek dan fix UNIQUE constraint pada tabel lockers
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fix UNIQUE Constraint - Lockers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-database me-2"></i>Fix UNIQUE Constraint - Tabel Lockers</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            echo '<h5>1. Mengecek struktur tabel lockers...</h5>';
                            
                            // Cek struktur tabel
                            $createTable = $db->fetch("SHOW CREATE TABLE lockers");
                            echo '<div class="alert alert-info">';
                            echo '<strong>Struktur tabel saat ini:</strong><br>';
                            echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto;">';
                            echo htmlspecialchars($createTable['Create Table']);
                            echo '</pre>';
                            echo '</div>';
                            
                            echo '<h5 class="mt-4">2. Mengecek index pada kolom code...</h5>';
                            
                            // Cek semua index pada tabel lockers
                            $indexes = $db->fetchAll("SHOW INDEX FROM lockers WHERE Column_name = 'code'");
                            
                            if (empty($indexes)) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="fas fa-check-circle me-2"></i>';
                                echo '<strong>Tidak ada index pada kolom code!</strong> Constraint UNIQUE sudah tidak ada.';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                                echo '<strong>Ditemukan ' . count($indexes) . ' index pada kolom code:</strong>';
                                echo '<table class="table table-sm mt-2">';
                                echo '<thead><tr><th>Key Name</th><th>Non Unique</th><th>Column</th></tr></thead>';
                                echo '<tbody>';
                                foreach ($indexes as $idx) {
                                    $isUnique = $idx['Non_unique'] == 0 ? 'Ya (UNIQUE)' : 'Tidak';
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($idx['Key_name']) . '</td>';
                                    echo '<td>' . $isUnique . '</td>';
                                    echo '<td>' . htmlspecialchars($idx['Column_name']) . '</td>';
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                                echo '</div>';
                                
                                echo '<h5 class="mt-4">3. Menghapus UNIQUE index...</h5>';
                                
                                // Coba hapus semua index UNIQUE pada kolom code
                                $deleted = [];
                                $errors = [];
                                
                                foreach ($indexes as $idx) {
                                    if ($idx['Non_unique'] == 0) { // Hanya hapus yang UNIQUE
                                        $indexName = $idx['Key_name'];
                                        try {
                                            // Skip PRIMARY key
                                            if ($indexName === 'PRIMARY') {
                                                continue;
                                            }
                                            
                                            $db->execute("ALTER TABLE lockers DROP INDEX `{$indexName}`");
                                            $deleted[] = $indexName;
                                            echo '<div class="alert alert-success">';
                                            echo '<i class="fas fa-check me-2"></i>';
                                            echo "Berhasil menghapus index: <strong>{$indexName}</strong>";
                                            echo '</div>';
                                        } catch (Exception $e) {
                                            $errors[] = "Gagal menghapus index {$indexName}: " . $e->getMessage();
                                            echo '<div class="alert alert-danger">';
                                            echo '<i class="fas fa-times me-2"></i>';
                                            echo "Gagal menghapus index <strong>{$indexName}</strong>: " . htmlspecialchars($e->getMessage());
                                            echo '</div>';
                                        }
                                    }
                                }
                                
                                if (!empty($deleted)) {
                                    echo '<div class="alert alert-success mt-3">';
                                    echo '<i class="fas fa-check-circle me-2"></i>';
                                    echo '<strong>Berhasil!</strong> ' . count($deleted) . ' UNIQUE index telah dihapus.';
                                    echo '<br>Sekarang kode lemari yang sama boleh dipakai lebih dari sekali.';
                                    echo '</div>';
                                }
                                
                                if (!empty($errors)) {
                                    echo '<div class="alert alert-warning mt-3">';
                                    echo '<strong>Peringatan:</strong><ul>';
                                    foreach ($errors as $err) {
                                        echo '<li>' . htmlspecialchars($err) . '</li>';
                                    }
                                    echo '</ul></div>';
                                }
                                
                                // Verifikasi ulang
                                echo '<h5 class="mt-4">4. Verifikasi setelah penghapusan...</h5>';
                                $indexesAfter = $db->fetchAll("SHOW INDEX FROM lockers WHERE Column_name = 'code' AND Non_unique = 0");
                                
                                if (empty($indexesAfter)) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle me-2"></i>';
                                    echo '<strong>Verifikasi berhasil!</strong> Tidak ada lagi UNIQUE constraint pada kolom code.';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-warning">';
                                    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                                    echo 'Masih ada ' . count($indexesAfter) . ' UNIQUE index yang tersisa.';
                                    echo '</div>';
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
                            <a href="../lockers/add.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Coba Tambah Lemari Lagi
                            </a>
                            <a href="../lockers/select.php" class="btn btn-secondary">
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



