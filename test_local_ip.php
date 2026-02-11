<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Local IP Detection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-network-wired me-2"></i>
                    Test Local IP Detection
                </h1>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Status Deteksi</h5>
                    </div>
                    <div class="card-body">
                        <div id="detection-status">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Detecting...</span>
                                </div>
                                <p class="mt-2">Mendeteksi IP lokal perangkat Anda...</p>
                            </div>
                        </div>
                        
                        <div id="detection-results" style="display: none;">
                            <h6>Hasil Deteksi:</h6>
                            <div id="ip-list"></div>
                            
                            <h6 class="mt-3">Informasi Network:</h6>
                            <div id="network-info"></div>
                            
                            <h6 class="mt-3">Server Response:</h6>
                            <div id="server-response"></div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Manual Test</h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-primary" onclick="startDetection()">
                            <i class="fas fa-search me-2"></i>Deteksi Ulang
                        </button>
                        <button type="button" class="btn btn-info" onclick="showConsoleInfo()">
                            <i class="fas fa-code me-2"></i>Show Console Info
                        </button>
                        <button type="button" class="btn btn-success" onclick="sendToServer()">
                            <i class="fas fa-server me-2"></i>Send to Server
                        </button>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <h6>Browser Support:</h6>
                        <ul>
                            <li><strong>WebRTC:</strong> <span id="webrtc-support"></span></li>
                            <li><strong>Network API:</strong> <span id="network-api-support"></span></li>
                            <li><strong>Geolocation:</strong> <span id="geolocation-support"></span></li>
                        </ul>
                        
                        <h6>Current Environment:</h6>
                        <ul>
                            <li><strong>User Agent:</strong> <small id="user-agent"></small></li>
                            <li><strong>Platform:</strong> <span id="platform"></span></li>
                            <li><strong>Language:</strong> <span id="language"></span></li>
                            <li><strong>Timezone:</strong> <span id="timezone"></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/local-ip-detector.js"></script>
    <script>
        // Check browser support
        document.addEventListener('DOMContentLoaded', function() {
            // Check WebRTC support
            const webrtcSupport = !!(window.RTCPeerConnection || window.mozRTCPeerConnection || window.webkitRTCPeerConnection);
            document.getElementById('webrtc-support').innerHTML = webrtcSupport ? 
                '<span class="badge bg-success">Supported</span>' : 
                '<span class="badge bg-danger">Not Supported</span>';
            
            // Check Network API support
            const networkApiSupport = 'connection' in navigator;
            document.getElementById('network-api-support').innerHTML = networkApiSupport ? 
                '<span class="badge bg-success">Supported</span>' : 
                '<span class="badge bg-warning">Not Supported</span>';
            
            // Check Geolocation support
            const geolocationSupport = 'geolocation' in navigator;
            document.getElementById('geolocation-support').innerHTML = geolocationSupport ? 
                '<span class="badge bg-success">Supported</span>' : 
                '<span class="badge bg-danger">Not Supported</span>';
            
            // Show environment info
            document.getElementById('user-agent').textContent = navigator.userAgent;
            document.getElementById('platform').textContent = navigator.platform;
            document.getElementById('language').textContent = navigator.language;
            document.getElementById('timezone').textContent = Intl.DateTimeFormat().resolvedOptions().timeZone;
            
            // Start detection
            startDetection();
        });

        function startDetection() {
            const statusDiv = document.getElementById('detection-status');
            const resultsDiv = document.getElementById('detection-results');
            
            statusDiv.style.display = 'block';
            resultsDiv.style.display = 'none';
            
            window.localIPDetector.detectLocalIPs().then(ips => {
                displayResults(ips);
            }).catch(error => {
                console.error('Detection error:', error);
                statusDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        }

        function displayResults(ips) {
            const statusDiv = document.getElementById('detection-status');
            const resultsDiv = document.getElementById('detection-results');
            const ipList = document.getElementById('ip-list');
            const networkInfo = document.getElementById('network-info');
            
            statusDiv.style.display = 'none';
            resultsDiv.style.display = 'block';
            
            // Display IPs
            if (ips.length === 0) {
                ipList.innerHTML = '<div class="alert alert-warning">Tidak ada IP lokal yang terdeteksi.</div>';
            } else {
                let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
                html += '<thead><tr><th>IP Address</th><th>Type</th><th>Status</th><th>Source</th></tr></thead><tbody>';
                
                ips.forEach(ipInfo => {
                    const badgeClass = ipInfo.isLocal ? 'bg-warning' : 'bg-success';
                    const typeClass = ipInfo.type === 'IPv6' ? 'bg-info' : 'bg-primary';
                    
                    html += '<tr>';
                    html += '<td><code>' + ipInfo.ip + '</code></td>';
                    html += '<td><span class="badge ' + typeClass + '">' + ipInfo.type + '</span></td>';
                    html += '<td><span class="badge ' + badgeClass + '">' + (ipInfo.isLocal ? 'Lokal' : 'Public') + '</span></td>';
                    html += '<td><small>' + ipInfo.source + '</small></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                ipList.innerHTML = html;
            }
            
            // Display network info
            const info = window.localIPDetector.getFormattedInfo();
            if (info.networkInfo && Object.keys(info.networkInfo).length > 0) {
                let networkHtml = '<table class="table table-sm">';
                Object.entries(info.networkInfo).forEach(([key, value]) => {
                    networkHtml += '<tr><td><strong>' + key + ':</strong></td><td>' + value + '</td></tr>';
                });
                networkHtml += '</table>';
                networkInfo.innerHTML = networkHtml;
            } else {
                networkInfo.innerHTML = '<div class="text-muted">Informasi network tidak tersedia.</div>';
            }
        }

        function showConsoleInfo() {
            const info = window.localIPDetector.getFormattedInfo();
            console.log('Local IP Detection Info:', info);
            console.table(info.localIPs);
            alert('Check browser console for detailed information');
        }

        function sendToServer() {
            window.localIPDetector.sendToServer().then(result => {
                const serverResponse = document.getElementById('server-response');
                if (result) {
                    serverResponse.innerHTML = '<div class="alert alert-success">Data berhasil dikirim ke server</div>';
                    console.log('Server response:', result);
                } else {
                    serverResponse.innerHTML = '<div class="alert alert-danger">Gagal mengirim data ke server</div>';
                }
            }).catch(error => {
                const serverResponse = document.getElementById('server-response');
                serverResponse.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        }
    </script>
</body>
</html>