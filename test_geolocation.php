<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login dan role admin
require_admin();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test HTML5 Geolocation - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Test HTML5 Geolocation API
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <!-- Geolocation Status -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Status Geolocation</h6>
                    </div>
                    <div class="card-body">
                        <div id="geolocationStatus" class="alert alert-secondary">
                            <i class="fas fa-spinner fa-spin me-2"></i>Memeriksa dukungan geolocation...
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-primary" id="getCurrentLocationBtn">
                                    <i class="fas fa-crosshairs me-2"></i>Dapatkan Lokasi Saat Ini
                                </button>
                                <button type="button" class="btn btn-success" id="startWatchingBtn">
                                    <i class="fas fa-play me-2"></i>Mulai Tracking
                                </button>
                                <button type="button" class="btn btn-danger" id="stopWatchingBtn" disabled>
                                    <i class="fas fa-stop me-2"></i>Hentikan Tracking
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-info" id="saveLocationBtn" disabled>
                                    <i class="fas fa-save me-2"></i>Simpan ke Database
                                </button>
                                <button type="button" class="btn btn-warning" id="clearLocationBtn">
                                    <i class="fas fa-trash me-2"></i>Clear Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Current Location -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Lokasi Saat Ini</h6>
                    </div>
                    <div class="card-body">
                        <div id="locationInfo">
                            <p class="text-muted">Belum ada data lokasi. Klik "Dapatkan Lokasi Saat Ini" untuk memulai.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Location History -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Lokasi (5 Terakhir)</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Cek apakah kolom geolocation sudah ada
                            $has_geolocation_columns = has_geolocation_columns();
                            
                            if ($has_geolocation_columns) {
                                $recent_locations = $db->fetchAll("
                                    SELECT latitude, longitude, accuracy, address_info, created_at, geolocation_timestamp
                                    FROM activity_logs 
                                    WHERE user_id = ? AND latitude IS NOT NULL AND longitude IS NOT NULL
                                    ORDER BY created_at DESC 
                                    LIMIT 5
                                ", [$_SESSION['user_id']]);
                                
                                if (!empty($recent_locations)) {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-sm">';
                                    echo '<thead><tr><th>Waktu</th><th>Koordinat</th><th>Akurasi</th><th>Alamat</th><th>Action</th></tr></thead>';
                                    echo '<tbody>';
                                    
                                    foreach ($recent_locations as $location) {
                                        $address = '';
                                        if ($location['address_info']) {
                                            $addr_data = json_decode($location['address_info'], true);
                                            if ($addr_data && is_array($addr_data)) {
                                                $address_parts = [];
                                                if (!empty($addr_data['city'])) $address_parts[] = $addr_data['city'];
                                                if (!empty($addr_data['state'])) $address_parts[] = $addr_data['state'];
                                                if (!empty($addr_data['country'])) $address_parts[] = $addr_data['country'];
                                                $address = implode(', ', $address_parts);
                                            }
                                        }
                                        
                                        echo '<tr>';
                                        echo '<td><small>' . format_date_indonesia($location['created_at'], true) . '</small></td>';
                                        echo '<td><small>' . number_format($location['latitude'], 6) . ', ' . number_format($location['longitude'], 6) . '</small></td>';
                                        echo '<td><small>' . ($location['accuracy'] ? round($location['accuracy']) . 'm' : '-') . '</small></td>';
                                        echo '<td><small>' . e($address ?: '-') . '</small></td>';
                                        echo '<td>';
                                        echo '<button type="button" class="btn btn-sm btn-outline-primary" onclick="showOnMap(' . $location['latitude'] . ', ' . $location['longitude'] . ')">';
                                        echo '<i class="fas fa-map"></i>';
                                        echo '</button>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody></table></div>';
                                } else {
                                    echo '<p class="text-muted">Belum ada riwayat lokasi.</p>';
                                }
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                                echo '<strong>Database belum diupdate!</strong> Kolom geolocation belum ada di database. ';
                                echo '<a href="update_geolocation_schema.php" class="btn btn-sm btn-primary ms-2">';
                                echo '<i class="fas fa-database me-1"></i>Update Database';
                                echo '</a>';
                                echo '</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error: ' . e($e->getMessage()) . '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Map Display -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-map me-2"></i>Peta Lokasi</h6>
                    </div>
                    <div class="card-body">
                        <div id="mapContainer" style="height: 400px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                            <p class="text-muted">Peta akan ditampilkan setelah lokasi didapatkan</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/geolocation.js"></script>
    <script>
        let isWatching = false;
        let currentPosition = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            const statusDiv = document.getElementById('geolocationStatus');
            const locationInfoDiv = document.getElementById('locationInfo');
            const getCurrentBtn = document.getElementById('getCurrentLocationBtn');
            const startWatchingBtn = document.getElementById('startWatchingBtn');
            const stopWatchingBtn = document.getElementById('stopWatchingBtn');
            const saveLocationBtn = document.getElementById('saveLocationBtn');
            const clearLocationBtn = document.getElementById('clearLocationBtn');
            
            // Check geolocation support
            if (window.geoTracker.isGeolocationSupported()) {
                statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i><strong>Geolocation didukung!</strong> Browser Anda mendukung HTML5 Geolocation API.';
                statusDiv.className = 'alert alert-success';
            } else {
                statusDiv.innerHTML = '<i class="fas fa-times-circle me-2"></i><strong>Geolocation tidak didukung!</strong> Browser Anda tidak mendukung HTML5 Geolocation API.';
                statusDiv.className = 'alert alert-danger';
                getCurrentBtn.disabled = true;
                startWatchingBtn.disabled = true;
            }
            
            // Get current location
            getCurrentBtn.addEventListener('click', function() {
                getCurrentBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mendapatkan lokasi...';
                getCurrentBtn.disabled = true;
                
                window.geoTracker.getCurrentPosition()
                    .then(position => {
                        currentPosition = position;
                        displayLocationInfo(position);
                        showOnMap(position.latitude, position.longitude);
                        saveLocationBtn.disabled = false;
                        
                        getCurrentBtn.innerHTML = '<i class="fas fa-crosshairs me-2"></i>Dapatkan Lokasi Saat Ini';
                        getCurrentBtn.disabled = false;
                    })
                    .catch(error => {
                        locationInfoDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error: ${error.message}
                            </div>
                        `;
                        
                        getCurrentBtn.innerHTML = '<i class="fas fa-crosshairs me-2"></i>Dapatkan Lokasi Saat Ini';
                        getCurrentBtn.disabled = false;
                    });
            });
            
            // Start watching
            startWatchingBtn.addEventListener('click', function() {
                if (window.geoTracker.startWatching()) {
                    isWatching = true;
                    startWatchingBtn.disabled = true;
                    stopWatchingBtn.disabled = false;
                    
                    // Override callbacks
                    window.geoTracker.onPositionUpdate = function(position) {
                        currentPosition = position;
                        displayLocationInfo(position);
                        showOnMap(position.latitude, position.longitude);
                        saveLocationBtn.disabled = false;
                    };
                    
                    window.geoTracker.onPositionError = function(error) {
                        locationInfoDiv.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Tracking error: ${error.message}
                            </div>
                        `;
                    };
                }
            });
            
            // Stop watching
            stopWatchingBtn.addEventListener('click', function() {
                window.geoTracker.stopWatching();
                isWatching = false;
                startWatchingBtn.disabled = false;
                stopWatchingBtn.disabled = true;
            });
            
            // Save location
            saveLocationBtn.addEventListener('click', function() {
                if (currentPosition) {
                    saveLocationBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
                    saveLocationBtn.disabled = true;
                    
                    window.geoTracker.sendLocationToServer('MANUAL_LOCATION_SAVE')
                        .then(result => {
                            showToast('success', 'Lokasi berhasil disimpan ke database!');
                            saveLocationBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan ke Database';
                            saveLocationBtn.disabled = false;
                            
                            // Reload page after 2 seconds to show updated history
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        })
                        .catch(error => {
                            showToast('danger', 'Gagal menyimpan lokasi: ' + error.message);
                            saveLocationBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan ke Database';
                            saveLocationBtn.disabled = false;
                        });
                }
            });
            
            // Clear location
            clearLocationBtn.addEventListener('click', function() {
                currentPosition = null;
                locationInfoDiv.innerHTML = '<p class="text-muted">Data lokasi telah dihapus.</p>';
                document.getElementById('mapContainer').innerHTML = '<p class="text-muted">Peta akan ditampilkan setelah lokasi didapatkan</p>';
                saveLocationBtn.disabled = true;
            });
        });
        
        function displayLocationInfo(position) {
            const coords = window.geoTracker.formatCoordinates(position.latitude, position.longitude);
            
            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Koordinat GPS</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Latitude:</strong></td><td>${coords.latitude}</td></tr>
                            <tr><td><strong>Longitude:</strong></td><td>${coords.longitude}</td></tr>
                            <tr><td><strong>Akurasi:</strong></td><td>${Math.round(position.accuracy)} meter</td></tr>
                            <tr><td><strong>Ketinggian:</strong></td><td>${position.altitude ? Math.round(position.altitude) + ' meter' : 'Tidak tersedia'}</td></tr>
                            <tr><td><strong>Kecepatan:</strong></td><td>${position.speed ? Math.round(position.speed * 3.6) + ' km/h' : 'Tidak tersedia'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Format DMS</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Latitude:</strong></td><td>${coords.dms.latitude}</td></tr>
                            <tr><td><strong>Longitude:</strong></td><td>${coords.dms.longitude}</td></tr>
                        </table>
                        
                        <h6 class="mt-3">Informasi Tambahan</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Timestamp:</strong></td><td>${new Date(position.timestamp).toLocaleString('id-ID')}</td></tr>
                            <tr><td><strong>Timezone:</strong></td><td>${Intl.DateTimeFormat().resolvedOptions().timeZone}</td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6>Reverse Geocoding</h6>
                    <div id="addressInfo">
                        <i class="fas fa-spinner fa-spin me-2"></i>Mencari alamat...
                    </div>
                </div>
            `;
            
            document.getElementById('locationInfo').innerHTML = html;
            
            // Get address
            window.geoTracker.reverseGeocode(position.latitude, position.longitude)
                .then(address => {
                    if (address) {
                        const addressHtml = `
                            <div class="alert alert-info">
                                <strong>Alamat:</strong> ${address.formatted_address}<br>
                                <strong>Jalan:</strong> ${address.road || '-'}<br>
                                <strong>Kelurahan:</strong> ${address.suburb || '-'}<br>
                                <strong>Kota:</strong> ${address.city || '-'}<br>
                                <strong>Provinsi:</strong> ${address.state || '-'}<br>
                                <strong>Negara:</strong> ${address.country || '-'}<br>
                                <strong>Kode Pos:</strong> ${address.postcode || '-'}
                            </div>
                        `;
                        document.getElementById('addressInfo').innerHTML = addressHtml;
                    } else {
                        document.getElementById('addressInfo').innerHTML = '<p class="text-muted">Alamat tidak dapat ditemukan.</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('addressInfo').innerHTML = '<p class="text-danger">Error mendapatkan alamat: ' + error.message + '</p>';
                });
        }
        
        function showOnMap(lat, lng) {
            const mapContainer = document.getElementById('mapContainer');
            mapContainer.innerHTML = `
                <iframe 
                    src="https://www.openstreetmap.org/export/embed.html?bbox=${lng-0.01},${lat-0.01},${lng+0.01},${lat+0.01}&layer=mapnik&marker=${lat},${lng}"
                    width="100%" 
                    height="400" 
                    frameborder="0" 
                    style="border: 1px solid #ccc; border-radius: 5px;">
                </iframe>
            `;
        }
        
        function showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 5000);
        }
    </script>
</body>
</html>