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
    <title>Test Mandatory GPS System - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Test Mandatory GPS System
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Tentang Test Ini</h6>
                            <p class="mb-0">
                                Halaman ini menguji sistem GPS wajib yang akan diterapkan pada dashboard admin dan staff. 
                                Sistem akan memaksa user untuk mengaktifkan GPS sebelum dapat menggunakan fitur dashboard.
                            </p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Fitur Mandatory GPS</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Popup wajib GPS saat dashboard dibuka</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Semua tombol dinonaktifkan sampai GPS aktif</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Real-time GPS tracking setiap 30 detik</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Deteksi perubahan lokasi mencurigakan</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Status GPS di navbar</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Reverse geocoding untuk alamat</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0">Status GPS Saat Ini</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="currentGPSStatus">
                                            <div class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Memeriksa status GPS...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Test Buttons (Akan Dinonaktifkan Jika GPS Belum Aktif)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-primary w-100">
                                                    <i class="fas fa-file-alt me-1"></i>Tambah Dokumen
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-success w-100">
                                                    <i class="fas fa-users me-1"></i>Kelola User
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-info w-100">
                                                    <i class="fas fa-history me-1"></i>Log Aktivitas
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-warning w-100">
                                                    <i class="fas fa-cog me-1"></i>Pengaturan
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" placeholder="Test input field">
                                            </div>
                                            <div class="col-md-6">
                                                <select class="form-select">
                                                    <option>Test select option</option>
                                                    <option>Option 1</option>
                                                    <option>Option 2</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <a href="#" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-external-link-alt me-1"></i>Test Link
                                                </a>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="button" class="btn btn-outline-danger w-100" onclick="testFunction()">
                                                    <i class="fas fa-trash me-1"></i>Test Function
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Manual GPS Controls</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-success w-100" onclick="checkGPSStatus()">
                                                    <i class="fas fa-search me-1"></i>Cek Status GPS
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-warning w-100" onclick="forceShowGPSModal()">
                                                    <i class="fas fa-map-marker-alt me-1"></i>Tampilkan Modal GPS
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-info w-100" onclick="resetGPSSession()">
                                                    <i class="fas fa-refresh me-1"></i>Reset GPS Session
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                            </a>
                            <a href="logs/index.php" class="btn btn-success ms-2">
                                <i class="fas fa-history me-1"></i>Lihat Log Aktivitas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/mandatory-gps.js"></script>
    <script>
        function testFunction() {
            alert('Test function berhasil dipanggil! GPS sudah aktif.');
        }
        
        async function checkGPSStatus() {
            const statusDiv = document.getElementById('currentGPSStatus');
            statusDiv.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memeriksa status GPS...</p>
                </div>
            `;
            
            try {
                const response = await fetch('api/check_gps_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let statusHtml = '';
                    
                    if (data.gps_enabled) {
                        statusHtml = `
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>GPS Aktif</h6>
                                <ul class="mb-0">
                                    <li>Session GPS: ${data.session_gps ? 'Aktif' : 'Tidak aktif'}</li>
                                    <li>Database GPS: ${data.db_gps ? 'Aktif' : 'Tidak aktif'}</li>
                                </ul>
                            </div>
                        `;
                        
                        if (data.last_gps_record) {
                            statusHtml += `
                                <div class="mt-2">
                                    <strong>Record GPS Terakhir:</strong><br>
                                    <small>
                                        Koordinat: ${data.last_gps_record.latitude}, ${data.last_gps_record.longitude}<br>
                                        Waktu: ${data.last_gps_record.created_at}<br>
                                        Aksi: ${data.last_gps_record.action}
                                    </small>
                                </div>
                            `;
                        }
                    } else {
                        statusHtml = `
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>GPS Belum Aktif</h6>
                                <p class="mb-0">GPS perlu diaktifkan untuk menggunakan dashboard.</p>
                            </div>
                        `;
                    }
                    
                    statusDiv.innerHTML = statusHtml;
                } else {
                    statusDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>Error</h6>
                            <p class="mb-0">${data.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                statusDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-circle me-2"></i>Error</h6>
                        <p class="mb-0">Gagal memeriksa status GPS: ${error.message}</p>
                    </div>
                `;
            }
        }
        
        function forceShowGPSModal() {
            if (window.mandatoryGPS) {
                // Remove existing modal if any
                const existingModal = document.getElementById('mandatoryGPSModal');
                if (existingModal) {
                    existingModal.remove();
                }
                
                // Show GPS modal
                window.mandatoryGPS.showMandatoryGPSModal();
            } else {
                alert('Mandatory GPS system belum dimuat');
            }
        }
        
        async function resetGPSSession() {
            try {
                // Reset session GPS
                const response = await fetch('api/reset_gps_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('GPS session berhasil direset. Halaman akan dimuat ulang.');
                    window.location.reload();
                } else {
                    alert('Gagal reset GPS session: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        // Auto check GPS status on load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(checkGPSStatus, 2000);
        });
    </script>
</body>
</html>