<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login dan role admin
require_admin();

$success_messages = [];
$error_messages = [];

try {
    // Cek apakah kolom sudah ada
    $check_columns = $db->fetchAll("SHOW COLUMNS FROM activity_logs LIKE 'latitude'");
    
    if (empty($check_columns)) {
        // Tambahkan kolom geolocation
        $alter_queries = [
            "ALTER TABLE activity_logs 
             ADD COLUMN latitude DECIMAL(10, 8) NULL COMMENT 'Latitude koordinat GPS'",
            
            "ALTER TABLE activity_logs 
             ADD COLUMN longitude DECIMAL(11, 8) NULL COMMENT 'Longitude koordinat GPS'",
            
            "ALTER TABLE activity_logs 
             ADD COLUMN accuracy DECIMAL(10, 2) NULL COMMENT 'Akurasi GPS dalam meter'",
            
            "ALTER TABLE activity_logs 
             ADD COLUMN altitude DECIMAL(10, 2) NULL COMMENT 'Ketinggian dalam meter'",
            
            "ALTER TABLE activity_logs 
             ADD COLUMN timezone VARCHAR(50) NULL COMMENT 'Timezone user'",
            
            "ALTER TABLE activity_logs 
             ADD COLUMN address_info TEXT NULL COMMENT 'Informasi alamat dari reverse geocoding (JSON)'",
            
            "ALTER TABLE activity_logs 
             ADD COLUMN geolocation_timestamp DATETIME NULL COMMENT 'Timestamp dari GPS device'"
        ];
        
        foreach ($alter_queries as $query) {
            $db->execute($query);
        }
        
        // Tambahkan index
        $index_queries = [
            "CREATE INDEX idx_activity_logs_location ON activity_logs(latitude, longitude)",
            "CREATE INDEX idx_activity_logs_geo_timestamp ON activity_logs(geolocation_timestamp)"
        ];
        
        foreach ($index_queries as $query) {
            try {
                $db->execute($query);
            } catch (Exception $e) {
                // Index mungkin sudah ada, abaikan error
            }
        }
        
        $success_messages[] = 'Kolom geolocation berhasil ditambahkan ke tabel activity_logs';
    } else {
        $success_messages[] = 'Kolom geolocation sudah ada di tabel activity_logs';
    }
    
} catch (Exception $e) {
    $error_messages[] = 'Error updating database: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Geolocation Schema - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Update Database Schema - Geolocation Support
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_messages)): ?>
                            <?php foreach ($success_messages as $message): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo e($message); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_messages)): ?>
                            <?php foreach ($error_messages as $message): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo e($message); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <h6>Kolom yang ditambahkan:</h6>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <strong>latitude</strong> - DECIMAL(10, 8) - Latitude koordinat GPS
                                </li>
                                <li class="list-group-item">
                                    <strong>longitude</strong> - DECIMAL(11, 8) - Longitude koordinat GPS
                                </li>
                                <li class="list-group-item">
                                    <strong>accuracy</strong> - DECIMAL(10, 2) - Akurasi GPS dalam meter
                                </li>
                                <li class="list-group-item">
                                    <strong>altitude</strong> - DECIMAL(10, 2) - Ketinggian dalam meter
                                </li>
                                <li class="list-group-item">
                                    <strong>timezone</strong> - VARCHAR(50) - Timezone user
                                </li>
                                <li class="list-group-item">
                                    <strong>address_info</strong> - TEXT - Informasi alamat (JSON)
                                </li>
                                <li class="list-group-item">
                                    <strong>geolocation_timestamp</strong> - DATETIME - Timestamp GPS
                                </li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <h6>Index yang ditambahkan:</h6>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <strong>idx_activity_logs_location</strong> - Index untuk pencarian berdasarkan koordinat
                                </li>
                                <li class="list-group-item">
                                    <strong>idx_activity_logs_geo_timestamp</strong> - Index untuk timestamp geolocation
                                </li>
                            </ul>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Kembali ke Dashboard
                            </a>
                            <a href="logs/" class="btn btn-success">
                                <i class="fas fa-history me-2"></i>
                                Lihat Log Aktivitas
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