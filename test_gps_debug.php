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
    <title>GPS Debug Test - Sistem Arsip Dokumen</title>
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
                            <i class="fas fa-bug me-2"></i>
                            GPS Debug Test
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Debug GPS System</h6>
                            <p class="mb-0">
                                Halaman ini untuk debugging sistem GPS dan mengidentifikasi masalah aktivasi GPS.
                            </p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Browser Support Check</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="browserSupport">
                                            <div class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Checking browser support...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Permission Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="permissionStatus">
                                            <div class="text-center">
                                                <div class="spinner-border text-success" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Checking permissions...</p>
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
                                        <h6 class="mb-0">GPS Test Controls</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-primary w-100" onclick="testBasicGPS()">
                                                    <i class="fas fa-map-marker-alt me-1"></i>Test Basic GPS
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-success w-100" onclick="testAutoGPSEnabler()">
                                                    <i class="fas fa-magic me-1"></i>Test Auto GPS
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-info w-100" onclick="testMandatoryGPS()">
                                                    <i class="fas fa-shield-alt me-1"></i>Test Mandatory GPS
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-warning w-100" onclick="clearResults()">
                                                    <i class="fas fa-trash me-1"></i>Clear Results
                                                </button>
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
                                        <h6 class="mb-0">Test Results</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="testResults">
                                            <p class="text-muted">No tests run yet. Click a test button above to start.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                            </a>
                            <a href="test_mandatory_gps.php" class="btn btn-success ms-2">
                                <i class="fas fa-map-marker-alt me-1"></i>Mandatory GPS Test
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/auto-gps-enabler.js"></script>
    <script src="assets/js/mandatory-gps.js"></script>
    <script>
        let testResultsDiv = document.getElementById('testResults');
        
        function addResult(title, content, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const resultHtml = `
                <div class="alert alert-${type} mb-2">
                    <h6><i class="fas fa-clock me-2"></i>${title} - ${timestamp}</h6>
                    <div>${content}</div>
                </div>
            `;
            testResultsDiv.insertAdjacentHTML('afterbegin', resultHtml);
        }
        
        function clearResults() {
            testResultsDiv.innerHTML = '<p class="text-muted">Results cleared. Click a test button to start testing.</p>';
        }
        
        // Check browser support
        function checkBrowserSupport() {
            const supportDiv = document.getElementById('browserSupport');
            
            let supportHtml = '<ul class="list-unstyled mb-0">';
            
            // Geolocation API
            if ('geolocation' in navigator) {
                supportHtml += '<li><i class="fas fa-check text-success me-2"></i>Geolocation API: Supported</li>';
            } else {
                supportHtml += '<li><i class="fas fa-times text-danger me-2"></i>Geolocation API: Not Supported</li>';
            }
            
            // HTTPS
            if (location.protocol === 'https:' || location.hostname === 'localhost') {
                supportHtml += '<li><i class="fas fa-check text-success me-2"></i>HTTPS: Secure Context</li>';
            } else {
                supportHtml += '<li><i class="fas fa-exclamation-triangle text-warning me-2"></i>HTTPS: Insecure Context</li>';
            }
            
            // Permissions API
            if ('permissions' in navigator) {
                supportHtml += '<li><i class="fas fa-check text-success me-2"></i>Permissions API: Supported</li>';
            } else {
                supportHtml += '<li><i class="fas fa-times text-warning me-2"></i>Permissions API: Not Supported</li>';
            }
            
            // User Agent
            supportHtml += `<li><i class="fas fa-info-circle text-info me-2"></i>Browser: ${navigator.userAgent}</li>`;
            
            supportHtml += '</ul>';
            supportDiv.innerHTML = supportHtml;
        }
        
        // Check permission status
        async function checkPermissionStatus() {
            const permissionDiv = document.getElementById('permissionStatus');
            
            let permissionHtml = '<ul class="list-unstyled mb-0">';
            
            if ('permissions' in navigator) {
                try {
                    const permission = await navigator.permissions.query({ name: 'geolocation' });
                    const state = permission.state;
                    
                    let icon = 'fas fa-question';
                    let color = 'text-muted';
                    
                    if (state === 'granted') {
                        icon = 'fas fa-check';
                        color = 'text-success';
                    } else if (state === 'denied') {
                        icon = 'fas fa-times';
                        color = 'text-danger';
                    } else if (state === 'prompt') {
                        icon = 'fas fa-exclamation';
                        color = 'text-warning';
                    }
                    
                    permissionHtml += `<li><i class="${icon} ${color} me-2"></i>Permission State: ${state}</li>`;
                } catch (error) {
                    permissionHtml += `<li><i class="fas fa-exclamation-triangle text-warning me-2"></i>Permission Check Error: ${error.message}</li>`;
                }
            } else {
                permissionHtml += '<li><i class="fas fa-times text-warning me-2"></i>Cannot check permission status</li>';
            }
            
            permissionHtml += '</ul>';
            permissionDiv.innerHTML = permissionHtml;
        }
        
        // Test basic GPS
        async function testBasicGPS() {
            addResult('Basic GPS Test', 'Starting basic GPS test...', 'info');
            
            if (!('geolocation' in navigator)) {
                addResult('Basic GPS Test', 'Geolocation not supported in this browser', 'danger');
                return;
            }
            
            const options = {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            };
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const result = `
                        <strong>Success!</strong><br>
                        Latitude: ${position.coords.latitude}<br>
                        Longitude: ${position.coords.longitude}<br>
                        Accuracy: ${position.coords.accuracy} meters<br>
                        Timestamp: ${new Date(position.timestamp).toLocaleString()}
                    `;
                    addResult('Basic GPS Test', result, 'success');
                },
                (error) => {
                    let errorMsg = 'Unknown error';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = 'Permission denied by user';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = 'Position unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMsg = 'Request timeout';
                            break;
                    }
                    addResult('Basic GPS Test', `Error: ${errorMsg} (Code: ${error.code})`, 'danger');
                },
                options
            );
        }
        
        // Test Auto GPS Enabler
        async function testAutoGPSEnabler() {
            addResult('Auto GPS Enabler Test', 'Starting Auto GPS Enabler test...', 'info');
            
            if (!window.autoGPSEnabler) {
                addResult('Auto GPS Enabler Test', 'Auto GPS Enabler not loaded', 'danger');
                return;
            }
            
            try {
                await window.autoGPSEnabler.init();
                addResult('Auto GPS Enabler Test', 'Auto GPS Enabler initialized successfully', 'success');
                
                const result = await window.autoGPSEnabler.autoEnableGPS();
                
                if (result.success) {
                    const resultMsg = `
                        <strong>Auto GPS Success!</strong><br>
                        Latitude: ${result.position.coords.latitude}<br>
                        Longitude: ${result.position.coords.longitude}<br>
                        Accuracy: ${result.position.coords.accuracy} meters
                    `;
                    addResult('Auto GPS Enabler Test', resultMsg, 'success');
                } else {
                    addResult('Auto GPS Enabler Test', `Auto GPS failed: ${result.message}`, 'warning');
                }
            } catch (error) {
                addResult('Auto GPS Enabler Test', `Auto GPS error: ${error.message}`, 'danger');
            }
        }
        
        // Test Mandatory GPS
        async function testMandatoryGPS() {
            addResult('Mandatory GPS Test', 'Starting Mandatory GPS test...', 'info');
            
            if (!window.mandatoryGPS) {
                addResult('Mandatory GPS Test', 'Mandatory GPS not loaded', 'danger');
                return;
            }
            
            try {
                // Reset GPS session first
                const response = await fetch('api/reset_gps_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const resetResult = await response.json();
                if (resetResult.success) {
                    addResult('Mandatory GPS Test', 'GPS session reset successfully', 'info');
                } else {
                    addResult('Mandatory GPS Test', `GPS session reset failed: ${resetResult.message}`, 'warning');
                }
                
                // Initialize mandatory GPS
                window.mandatoryGPS.init();
                addResult('Mandatory GPS Test', 'Mandatory GPS initialized', 'success');
                
                // Force show GPS modal for testing
                setTimeout(() => {
                    window.mandatoryGPS.showMandatoryGPSModal();
                    addResult('Mandatory GPS Test', 'Mandatory GPS modal shown - try activating GPS', 'info');
                }, 1000);
                
            } catch (error) {
                addResult('Mandatory GPS Test', `Mandatory GPS error: ${error.message}`, 'danger');
            }
        }
        
        // Initialize checks on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkBrowserSupport();
            checkPermissionStatus();
            
            // Auto-refresh permission status every 5 seconds
            setInterval(checkPermissionStatus, 5000);
        });
    </script>
</body>
</html>