<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login
require_login();
if (!is_admin()) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Google Maps Integration - Sistem Arsip Dokumen</title>
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
                            <i class="fab fa-google me-2"></i>
                            Test Google Maps Address Lookup Integration
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Test halaman untuk memverifikasi integrasi Google Maps API dalam sistem log aktivitas.
                        </p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Test Koordinat Jakarta</h6>
                                <div class="mb-3">
                                    <strong>Latitude:</strong> -6.200000<br>
                                    <strong>Longitude:</strong> 106.816666<br>
                                    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="testGoogleMapsAPI(-6.200000, 106.816666)">
                                        <i class="fab fa-google me-1"></i>Test Google Maps API
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Test Koordinat Surabaya</h6>
                                <div class="mb-3">
                                    <strong>Latitude:</strong> -7.250445<br>
                                    <strong>Longitude:</strong> 112.768845<br>
                                    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="testGoogleMapsAPI(-7.250445, 112.768845)">
                                        <i class="fab fa-google me-1"></i>Test Google Maps API
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Hasil Test API</h6>
                                <div id="apiResult" class="border rounded p-3 bg-light">
                                    <p class="text-muted mb-0">Klik tombol test di atas untuk melihat hasil...</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Test Format Geolocation Info</h6>
                                <div class="border rounded p-3">
                                    <?php
                                    // Test format_geolocation_info function
                                    echo '<p><strong>Jakarta:</strong></p>';
                                    echo format_geolocation_info(-6.200000, 106.816666, 10, '{"road":"Jalan Sudirman","city":"Jakarta","country":"Indonesia"}');
                                    
                                    echo '<p class="mt-3"><strong>Surabaya (tanpa address_info):</strong></p>';
                                    echo format_geolocation_info(-7.250445, 112.768845, 25, null);
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="logs/index.php" class="btn btn-success">
                                <i class="fas fa-history me-1"></i>Kembali ke Log Aktivitas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Location Detail Modal (copy from logs/index.php) -->
    <div class="modal fade" id="locationDetailModal" tabindex="-1" aria-labelledby="locationDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationDetailModalLabel">
                        <i class="fas fa-map-marker-alt me-2"></i>Detail Lokasi GPS
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="locationDetailContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat informasi lokasi...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="openInMapsBtn" style="display: none;">
                        <i class="fas fa-external-link-alt me-1"></i>Buka di Google Maps
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentLat = null;
        let currentLng = null;
        let currentAddress = '';
        
        function testGoogleMapsAPI(lat, lng) {
            const resultDiv = document.getElementById('apiResult');
            
            resultDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span>Testing Google Maps API untuk koordinat ${lat}, ${lng}...</span>
                </div>
            `;
            
            fetch('api/get_gmaps_address.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.address) {
                    const addr = data.address;
                    let html = '<div class="alert alert-success"><i class="fas fa-check me-2"></i><strong>API Test Berhasil!</strong></div>';
                    
                    html += '<table class="table table-sm">';
                    html += `<tr><td><strong>Source:</strong></td><td>${addr.source === 'google_maps' ? '<i class="fab fa-google text-primary"></i> Google Maps' : '<i class="fas fa-map text-info"></i> OpenStreetMap'}</td></tr>`;
                    html += `<tr><td><strong>Formatted Address:</strong></td><td>${addr.formatted_address || '-'}</td></tr>`;
                    html += `<tr><td><strong>Jalan:</strong></td><td>${[addr.street_number, addr.route].filter(Boolean).join(' ') || '-'}</td></tr>`;
                    html += `<tr><td><strong>Kelurahan:</strong></td><td>${addr.sublocality || '-'}</td></tr>`;
                    html += `<tr><td><strong>Kota:</strong></td><td>${addr.locality || '-'}</td></tr>`;
                    html += `<tr><td><strong>Kabupaten:</strong></td><td>${addr.administrative_area_level_2 || '-'}</td></tr>`;
                    html += `<tr><td><strong>Provinsi:</strong></td><td>${addr.administrative_area_level_1 || '-'}</td></tr>`;
                    html += `<tr><td><strong>Negara:</strong></td><td>${addr.country || '-'}</td></tr>`;
                    html += `<tr><td><strong>Kode Pos:</strong></td><td>${addr.postal_code || '-'}</td></tr>`;
                    html += '</table>';
                    
                    html += `<button type="button" class="btn btn-info btn-sm" onclick="showLocationDetail(${lat}, ${lng}, '${addr.formatted_address || ''}')">
                        <i class="fas fa-map-marker-alt me-1"></i>Lihat Detail Modal
                    </button>`;
                    
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>API Test Gagal:</strong> ${data.message || 'Unknown error'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
            });
        }
        
        // Copy functions from logs/index.php
        function showLocationDetail(lat, lng, address = '') {
            currentLat = lat;
            currentLng = lng;
            currentAddress = address;
            
            const modal = new bootstrap.Modal(document.getElementById('locationDetailModal'));
            const content = document.getElementById('locationDetailContent');
            const mapsBtn = document.getElementById('openInMapsBtn');
            
            // Show maps button
            mapsBtn.style.display = 'inline-block';
            mapsBtn.onclick = function() {
                window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
            };
            
            // Format coordinates
            const latDMS = convertToDMS(lat, true);
            const lngDMS = convertToDMS(lng, false);
            
            // Show loading state first
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-map-marker-alt me-2"></i>Koordinat GPS</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Latitude:</strong></td>
                                <td>${lat.toFixed(8)}</td>
                            </tr>
                            <tr>
                                <td><strong>Longitude:</strong></td>
                                <td>${lng.toFixed(8)}</td>
                            </tr>
                            <tr>
                                <td><strong>Latitude (DMS):</strong></td>
                                <td>${latDMS}</td>
                            </tr>
                            <tr>
                                <td><strong>Longitude (DMS):</strong></td>
                                <td>${lngDMS}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-map-pin me-2"></i>Informasi Lokasi</h6>
                        <div class="mb-3" id="addressInfo">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="text-muted">Mengambil alamat dari Google Maps...</span>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyCoordinates()">
                                <i class="fas fa-copy me-1"></i>Salin Koordinat
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="openInGoogleMaps()">
                                <i class="fas fa-external-link-alt me-1"></i>Google Maps
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="openInOpenStreetMap()">
                                <i class="fas fa-external-link-alt me-1"></i>OpenStreetMap
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-map me-2"></i>Peta Lokasi</h6>
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe 
                                src="https://www.openstreetmap.org/export/embed.html?bbox=${lng-0.01},${lat-0.01},${lng+0.01},${lat+0.01}&layer=mapnik&marker=${lat},${lng}"
                                width="100%" 
                                height="300" 
                                frameborder="0" 
                                style="border: 1px solid #ccc; border-radius: 5px;">
                            </iframe>
                        </div>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Fetch enhanced address from Google Maps API
            fetchGoogleMapsAddress(lat, lng);
        }
        
        function fetchGoogleMapsAddress(lat, lng) {
            fetch('api/get_gmaps_address.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng
                })
            })
            .then(response => response.json())
            .then(data => {
                const addressInfo = document.getElementById('addressInfo');
                
                if (data.success && data.address) {
                    const addr = data.address;
                    let addressHtml = '';
                    
                    // Format alamat lengkap
                    if (addr.formatted_address) {
                        addressHtml += `<div class="mb-3">
                            <strong>Alamat Lengkap:</strong><br>
                            <span class="text-primary">${addr.formatted_address}</span>
                        </div>`;
                    }
                    
                    // Detail alamat dalam tabel
                    addressHtml += '<table class="table table-sm table-borderless">';
                    
                    if (addr.street_number || addr.route) {
                        const street = [addr.street_number, addr.route].filter(Boolean).join(' ');
                        if (street) {
                            addressHtml += `<tr><td><i class="fas fa-road text-muted me-2"></i><strong>Jalan:</strong></td><td>${street}</td></tr>`;
                        }
                    }
                    
                    if (addr.sublocality) {
                        addressHtml += `<tr><td><i class="fas fa-map-marker text-muted me-2"></i><strong>Kelurahan:</strong></td><td>${addr.sublocality}</td></tr>`;
                    }
                    
                    if (addr.locality) {
                        addressHtml += `<tr><td><i class="fas fa-city text-muted me-2"></i><strong>Kota:</strong></td><td>${addr.locality}</td></tr>`;
                    }
                    
                    if (addr.administrative_area_level_2) {
                        addressHtml += `<tr><td><i class="fas fa-map text-muted me-2"></i><strong>Kabupaten:</strong></td><td>${addr.administrative_area_level_2}</td></tr>`;
                    }
                    
                    if (addr.administrative_area_level_1) {
                        addressHtml += `<tr><td><i class="fas fa-flag text-muted me-2"></i><strong>Provinsi:</strong></td><td>${addr.administrative_area_level_1}</td></tr>`;
                    }
                    
                    if (addr.country) {
                        addressHtml += `<tr><td><i class="fas fa-globe text-muted me-2"></i><strong>Negara:</strong></td><td>${addr.country}</td></tr>`;
                    }
                    
                    if (addr.postal_code) {
                        addressHtml += `<tr><td><i class="fas fa-mail-bulk text-muted me-2"></i><strong>Kode Pos:</strong></td><td>${addr.postal_code}</td></tr>`;
                    }
                    
                    addressHtml += '</table>';
                    
                    // Source info
                    const sourceIcon = addr.source === 'google_maps' 
                        ? '<i class="fab fa-google text-primary me-1"></i>Google Maps' 
                        : '<i class="fas fa-map text-info me-1"></i>OpenStreetMap';
                    
                    addressHtml += `<div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>Sumber: ${sourceIcon}
                        </small>
                    </div>`;
                    
                    addressInfo.innerHTML = addressHtml;
                    
                } else {
                    // Fallback to original address or show error
                    let fallbackHtml = '';
                    
                    if (currentAddress) {
                        fallbackHtml = `<p class="mb-2"><strong>Alamat:</strong><br>${currentAddress}</p>`;
                    } else {
                        fallbackHtml = '<p class="text-muted">Alamat tidak tersedia</p>';
                    }
                    
                    if (data.message) {
                        fallbackHtml += `<div class="alert alert-warning alert-sm mt-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <small>${data.message}</small>
                        </div>`;
                    }
                    
                    addressInfo.innerHTML = fallbackHtml;
                }
            })
            .catch(error => {
                console.error('Error fetching Google Maps address:', error);
                const addressInfo = document.getElementById('addressInfo');
                
                let fallbackHtml = '';
                if (currentAddress) {
                    fallbackHtml = `<p class="mb-2"><strong>Alamat:</strong><br>${currentAddress}</p>`;
                } else {
                    fallbackHtml = '<p class="text-muted">Alamat tidak tersedia</p>';
                }
                
                fallbackHtml += `<div class="alert alert-danger alert-sm mt-2">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    <small>Gagal mengambil alamat dari Google Maps</small>
                </div>`;
                
                addressInfo.innerHTML = fallbackHtml;
            });
        }
        
        function convertToDMS(coordinate, isLatitude) {
            const absolute = Math.abs(coordinate);
            const degrees = Math.floor(absolute);
            const minutesNotTruncated = (absolute - degrees) * 60;
            const minutes = Math.floor(minutesNotTruncated);
            const seconds = Math.floor((minutesNotTruncated - minutes) * 60);
            
            const direction = isLatitude 
                ? (coordinate >= 0 ? 'N' : 'S')
                : (coordinate >= 0 ? 'E' : 'W');
                
            return `${degrees}°${minutes}'${seconds}"${direction}`;
        }
        
        function copyCoordinates() {
            const coords = `${currentLat}, ${currentLng}`;
            navigator.clipboard.writeText(coords).then(function() {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check me-2"></i>Koordinat berhasil disalin!
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 3000);
            });
        }
        
        function openInGoogleMaps() {
            window.open(`https://www.google.com/maps?q=${currentLat},${currentLng}`, '_blank');
        }
        
        function openInOpenStreetMap() {
            window.open(`https://www.openstreetmap.org/?mlat=${currentLat}&mlon=${currentLng}&zoom=15`, '_blank');
        }
    </script>
</body>
</html>