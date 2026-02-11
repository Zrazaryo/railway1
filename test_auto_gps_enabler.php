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
    <title>Test Auto GPS Enabler - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-magic me-2"></i>
                            Test Auto GPS Enabler System
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Tentang Auto GPS Enabler</h6>
                            <p class="mb-0">
                                Sistem ini membantu user mengaktifkan GPS secara otomatis melalui browser dengan deteksi cerdas 
                                dan panduan langkah-demi-langkah berdasarkan browser dan perangkat yang digunakan.
                            </p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Fitur Auto GPS Enabler</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Smart browser detection</li>
                                            <li><i class="fas fa-check text-success me-2"></i>OS-specific instructions</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Permission status monitoring</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Auto-fix suggestions</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Device GPS detection</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Step-by-step guidance</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">System Detection</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="systemDetection">
                                            <div class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Detecting system...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0">GPS Permission Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="permissionStatus">
                                            <div class="text-center">
                                                <div class="spinner-border text-warning" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Checking permission status...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Test Controls</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-success w-100" onclick="testAutoEnableGPS()">
                                                    <i class="fas fa-magic me-1"></i>Auto Enable GPS
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-info w-100" onclick="checkPermissionStatus()">
                                                    <i class="fas fa-shield-alt me-1"></i>Check Permission
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-warning w-100" onclick="showFixInstructions()">
                                                    <i class="fas fa-tools me-1"></i>Show Fix Guide
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-secondary w-100" onclick="detectLocationSettings()">
                                                    <i class="fas fa-search me-1"></i>Detect GPS Settings
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-outline-primary w-100" onclick="openBrowserSettings()">
                                                    <i class="fas fa-cog me-1"></i>Browser Settings
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-outline-info w-100" onclick="showDeviceInstructions()">
                                                    <i class="fas fa-mobile-alt me-1"></i>Device Instructions
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-outline-success w-100" onclick="testManualGPS()">
                                                    <i class="fas fa-map-marker-alt me-1"></i>Manual GPS Test
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
                                        <h6 class="mb-0">Test Results</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="testResults">
                                            <p class="text-muted">Test results will appear here...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                            </a>
                            <a href="test_mandatory_gps.php" class="btn btn-warning ms-2">
                                <i class="fas fa-map-marker-alt me-1"></i>Test Mandatory GPS
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
    <script src="assets/js/auto-gps-enabler.js"></script>
    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            detectSystemInfo();
            checkPermissionStatus();
        });
        
        async function detectSystemInfo() {
            const systemDiv = document.getElementById('systemDetection');
            
            if (window.autoGPSEnabler) {
                await window.autoGPSEnabler.init();
                
                const info = {
                    browser: window.autoGPSEnabler.browserType,
                    os: window.autoGPSEnabler.osType,
                    device: window.autoGPSEnabler.deviceType,
                    geolocationSupported: window.autoGPSEnabler.isSupported
                };
                
                systemDiv.innerHTML = `
                    <div class="row">
                        <div class="col-6">
                            <strong>Browser:</strong><br>
                            <span class="badge bg-primary">${info.browser.toUpperCase()}</span>
                        </div>
                        <div class="col-6">
                            <strong>OS:</strong><br>
                            <span class="badge bg-success">${info.os.toUpperCase()}</span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <strong>Device:</strong><br>
                            <span class="badge bg-info">${info.device.toUpperCase()}</span>
                        </div>
                        <div class="col-6">
                            <strong>Geolocation:</strong><br>
                            <span class="badge bg-${info.geolocationSupported ? 'success' : 'danger'}">
                                ${info.geolocationSupported ? 'SUPPORTED' : 'NOT SUPPORTED'}
                            </span>
                        </div>
                    </div>
                `;
            } else {
                systemDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Auto GPS Enabler not loaded
                    </div>
                `;
            }
        }
        
        async function checkPermissionStatus() {
            const statusDiv = document.getElementById('permissionStatus');
            
            if (window.autoGPSEnabler) {
                const status = await window.autoGPSEnabler.checkPermissionStatus();
                
                let badgeColor = 'secondary';
                let statusText = status.toUpperCase();
                
                switch(status) {
                    case 'granted':
                        badgeColor = 'success';
                        break;
                    case 'denied':
                        badgeColor = 'danger';
                        break;
                    case 'prompt':
                        badgeColor = 'warning';
                        break;
                    default:
                        badgeColor = 'secondary';
                }
                
                statusDiv.innerHTML = `
                    <div class="text-center">
                        <h4>
                            <span class="badge bg-${badgeColor}">${statusText}</span>
                        </h4>
                        <p class="text-muted">Current geolocation permission status</p>
                        ${status === 'denied' ? '<small class="text-danger">Permission denied - GPS features will not work</small>' : ''}
                        ${status === 'prompt' ? '<small class="text-warning">Permission will be requested when GPS is accessed</small>' : ''}
                        ${status === 'granted' ? '<small class="text-success">Permission granted - GPS features available</small>' : ''}
                    </div>
                `;
            } else {
                statusDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Auto GPS Enabler not available
                    </div>
                `;
            }
        }
        
        async function testAutoEnableGPS() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = `
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Testing Auto Enable GPS...</span>
                    </div>
                </div>
            `;
            
            try {
                if (window.autoGPSEnabler) {
                    const result = await window.autoGPSEnabler.autoEnableGPS();
                    
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i>Auto Enable GPS Success!</h6>
                            <ul class="mb-0">
                                <li>Latitude: ${result.position.coords.latitude.toFixed(6)}</li>
                                <li>Longitude: ${result.position.coords.longitude.toFixed(6)}</li>
                                <li>Accuracy: ${Math.round(result.position.coords.accuracy)} meters</li>
                                <li>Timestamp: ${new Date(result.position.timestamp).toLocaleString()}</li>
                            </ul>
                        </div>
                    `;
                    
                    // Update permission status
                    setTimeout(checkPermissionStatus, 1000);
                } else {
                    throw new Error('Auto GPS Enabler not available');
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-circle me-2"></i>Auto Enable GPS Failed</h6>
                        <p class="mb-0">${error.message}</p>
                        ${error.canAutoFix ? '<small class="text-info">This error can be auto-fixed with instructions.</small>' : ''}
                    </div>
                `;
                
                // If error can be auto-fixed, show the fix modal
                if (error.canAutoFix && window.autoGPSEnabler) {
                    window.autoGPSEnabler.showLocationFixModal(error);
                }
            }
        }
        
        async function detectLocationSettings() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = `
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Detecting location settings...</span>
                    </div>
                </div>
            `;
            
            try {
                if (window.autoGPSEnabler) {
                    const settings = await window.autoGPSEnabler.detectLocationSettings();
                    
                    resultsDiv.innerHTML = `
                        <div class="alert alert-${settings.enabled ? 'success' : 'warning'}">
                            <h6><i class="fas fa-${settings.enabled ? 'check-circle' : 'exclamation-triangle'} me-2"></i>Location Settings Detection</h6>
                            <ul class="mb-0">
                                <li><strong>Status:</strong> ${settings.enabled ? 'Enabled' : 'Disabled'}</li>
                                <li><strong>Reason:</strong> ${settings.reason || 'GPS is working'}</li>
                                ${settings.position ? `<li><strong>Test Position:</strong> ${settings.position.coords.latitude.toFixed(6)}, ${settings.position.coords.longitude.toFixed(6)}</li>` : ''}
                            </ul>
                        </div>
                    `;
                    
                    if (!settings.enabled && settings.reason === 'GPS disabled on device') {
                        window.autoGPSEnabler.showDeviceGPSInstructions();
                    }
                } else {
                    throw new Error('Auto GPS Enabler not available');
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-circle me-2"></i>Detection Failed</h6>
                        <p class="mb-0">${error.message}</p>
                    </div>
                `;
            }
        }
        
        function showFixInstructions() {
            if (window.autoGPSEnabler) {
                const error = {
                    success: false,
                    message: 'Demo: Showing GPS fix instructions',
                    canAutoFix: true,
                    fixInstructions: window.autoGPSEnabler.getPermissionFixInstructions()
                };
                
                window.autoGPSEnabler.showLocationFixModal(error);
            } else {
                alert('Auto GPS Enabler not available');
            }
        }
        
        function openBrowserSettings() {
            if (window.autoGPSEnabler) {
                window.autoGPSEnabler.openBrowserLocationSettings();
            } else {
                alert('Auto GPS Enabler not available');
            }
        }
        
        function showDeviceInstructions() {
            if (window.autoGPSEnabler) {
                window.autoGPSEnabler.showDeviceGPSInstructions();
            } else {
                alert('Auto GPS Enabler not available');
            }
        }
        
        async function testManualGPS() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = `
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Testing manual GPS request...</span>
                    </div>
                </div>
            `;
            
            try {
                if (window.autoGPSEnabler) {
                    const result = await window.autoGPSEnabler.requestLocationPermission();
                    
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i>Manual GPS Test Success!</h6>
                            <ul class="mb-0">
                                <li>Latitude: ${result.position.coords.latitude.toFixed(6)}</li>
                                <li>Longitude: ${result.position.coords.longitude.toFixed(6)}</li>
                                <li>Accuracy: ${Math.round(result.position.coords.accuracy)} meters</li>
                                <li>Message: ${result.message}</li>
                            </ul>
                        </div>
                    `;
                    
                    // Update permission status
                    setTimeout(checkPermissionStatus, 1000);
                } else {
                    throw new Error('Auto GPS Enabler not available');
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-circle me-2"></i>Manual GPS Test Failed</h6>
                        <p class="mb-0">${error.message}</p>
                    </div>
                `;
                
                // If error can be auto-fixed, show the fix modal
                if (error.canAutoFix && window.autoGPSEnabler) {
                    window.autoGPSEnabler.showLocationFixModal(error);
                }
            }
        }
    </script>
</body>
</html>