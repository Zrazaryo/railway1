<?php
/**
 * Script langsung untuk menghapus UNIQUE constraint
 * Akses: http://localhost/PROJECT%20ARSIP%20LOKER/migrations/direct_fix.php
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fix UNIQUE Constraint - Direct</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #155724; background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #c3e6cb; }
        .error { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #f5c6cb; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #bee5eb; }
        .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #ffeaa7; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #dee2e6; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; margin-right: 10px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h3 { color: #555; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔧 Fix UNIQUE Constraint - Tabel Lockers</h2>
        
        <?php
        $steps = [];
        
        try {
            // Step 1: Cek struktur tabel
            echo '<h3>Step 1: Mengecek struktur tabel lockers</h3>';
            $createTable = $db->fetch("SHOW CREATE TABLE lockers");
            $steps[] = ['step' => 'Cek struktur tabel', 'status' => 'success'];
            
            echo '<div class="info">';
            echo '<strong>Struktur CREATE TABLE:</strong>';
            echo '<pre>' . htmlspecialchars($createTable['Create Table']) . '</pre>';
            echo '</div>';
            
            // Step 2: Cek semua index
            echo '<h3>Step 2: Mengecek semua index pada tabel lockers</h3>';
            $allIndexes = $db->fetchAll("SHOW INDEX FROM lockers");
            $steps[] = ['step' => 'Cek semua index', 'status' => 'success'];
            
            echo '<div class="info">';
            echo '<strong>Semua index yang ditemukan:</strong>';
            echo '<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
            echo '<tr style="background: #f8f9fa;"><th>Key Name</th><th>Non Unique</th><th>Column</th><th>Index Type</th></tr>';
            foreach ($allIndexes as $idx) {
                $isUnique = $idx['Non_unique'] == 0 ? '<span style="color: red; font-weight: bold;">UNIQUE</span>' : 'Bukan UNIQUE';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($idx['Key_name']) . '</td>';
                echo '<td>' . $isUnique . '</td>';
                echo '<td>' . htmlspecialchars($idx['Column_name']) . '</td>';
                echo '<td>' . htmlspecialchars($idx['Index_type']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            
            // Step 3: Cek index khusus pada kolom code
            echo '<h3>Step 3: Mengecek index pada kolom "code"</h3>';
            $codeIndexes = $db->fetchAll("SHOW INDEX FROM lockers WHERE Column_name = 'code'");
            
            if (empty($codeIndexes)) {
                echo '<div class="success">';
                echo '✅ <strong>Tidak ada index pada kolom code!</strong> Constraint UNIQUE sudah tidak ada.';
                echo '</div>';
                $steps[] = ['step' => 'Tidak ada UNIQUE constraint', 'status' => 'success'];
            } else {
                $uniqueIndexes = array_filter($codeIndexes, function($idx) {
                    return $idx['Non_unique'] == 0 && $idx['Key_name'] !== 'PRIMARY';
                });
                
                if (empty($uniqueIndexes)) {
                    echo '<div class="success">';
                    echo '✅ <strong>Tidak ada UNIQUE constraint pada kolom code!</strong>';
                    echo '</div>';
                    $steps[] = ['step' => 'Tidak ada UNIQUE constraint', 'status' => 'success'];
                } else {
                    echo '<div class="warning">';
                    echo '⚠️ <strong>Ditemukan ' . count($uniqueIndexes) . ' UNIQUE index pada kolom code yang perlu dihapus:</strong>';
                    echo '<ul>';
                    foreach ($uniqueIndexes as $idx) {
                        echo '<li><strong>' . htmlspecialchars($idx['Key_name']) . '</strong> (Non_unique = 0)</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                    
                    // Step 4: Hapus UNIQUE index
                    echo '<h3>Step 4: Menghapus UNIQUE index</h3>';
                    $deleted = [];
                    $errors = [];
                    
                    foreach ($uniqueIndexes as $idx) {
                        $indexName = $idx['Key_name'];
                        
                        // Skip PRIMARY key
                        if ($indexName === 'PRIMARY') {
                            continue;
                        }
                        
                        try {
                            // Coba beberapa variasi perintah
                            $queries = [
                                "ALTER TABLE lockers DROP INDEX `{$indexName}`",
                                "ALTER TABLE lockers DROP INDEX {$indexName}",
                                "DROP INDEX `{$indexName}` ON lockers",
                                "DROP INDEX {$indexName} ON lockers"
                            ];
                            
                            $success = false;
                            foreach ($queries as $query) {
                                try {
                                    $db->execute($query);
                                    $deleted[] = $indexName;
                                    $success = true;
                                    echo '<div class="success">';
                                    echo '✅ Berhasil menghapus index: <strong>' . htmlspecialchars($indexName) . '</strong>';
                                    echo '</div>';
                                    break;
                                } catch (Exception $e) {
                                    // Coba query berikutnya
                                    continue;
                                }
                            }
                            
                            if (!$success) {
                                throw new Exception("Semua metode gagal untuk index: {$indexName}");
                            }
                            
                        } catch (Exception $e) {
                            $errors[] = "Index {$indexName}: " . $e->getMessage();
                            echo '<div class="error">';
                            echo '❌ Gagal menghapus index <strong>' . htmlspecialchars($indexName) . '</strong>: ' . htmlspecialchars($e->getMessage());
                            echo '</div>';
                        }
                    }
                    
                    if (!empty($deleted)) {
                        $steps[] = ['step' => 'Hapus UNIQUE index', 'status' => 'success', 'count' => count($deleted)];
                    }
                    
                    if (!empty($errors)) {
                        $steps[] = ['step' => 'Hapus UNIQUE index', 'status' => 'error', 'errors' => $errors];
                    }
                    
                    // Step 5: Verifikasi
                    echo '<h3>Step 5: Verifikasi setelah penghapusan</h3>';
                    $remainingIndexes = $db->fetchAll("SHOW INDEX FROM lockers WHERE Column_name = 'code' AND Non_unique = 0 AND Key_name != 'PRIMARY'");
                    
                    if (empty($remainingIndexes)) {
                        echo '<div class="success">';
                        echo '✅ <strong>Berhasil!</strong> Tidak ada lagi UNIQUE constraint pada kolom code.';
                        echo '<br><br>Sekarang Anda bisa menambahkan lemari dengan kode yang sama (misalnya beberapa lemari dengan kode "A" tetapi Kode Rak berbeda).';
                        echo '</div>';
                        $steps[] = ['step' => 'Verifikasi', 'status' => 'success'];
                    } else {
                        echo '<div class="warning">';
                        echo '⚠️ Masih ada ' . count($remainingIndexes) . ' UNIQUE index yang tersisa:';
                        echo '<ul>';
                        foreach ($remainingIndexes as $idx) {
                            echo '<li>' . htmlspecialchars($idx['Key_name']) . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                        $steps[] = ['step' => 'Verifikasi', 'status' => 'warning'];
                    }
                }
            }
            
            // Summary
            echo '<h3>📋 Ringkasan</h3>';
            echo '<div class="info">';
            echo '<ul>';
            foreach ($steps as $step) {
                $icon = $step['status'] === 'success' ? '✅' : ($step['status'] === 'error' ? '❌' : '⚠️');
                echo '<li>' . $icon . ' ' . $step['step'];
                if (isset($step['count'])) {
                    echo ' (' . $step['count'] . ' index dihapus)';
                }
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '<br><br><strong>Trace:</strong><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px;">
            <a href="../lockers/add.php" class="btn btn-success">
                ➕ Coba Tambah Lemari Lagi
            </a>
            <a href="../lockers/select.php" class="btn">
                ← Kembali ke Daftar Lemari
            </a>
        </div>
    </div>
</body>
</html>



