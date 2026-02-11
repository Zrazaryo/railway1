/**
 * Mandatory GPS System untuk Dashboard Admin dan Staff
 * Sistem Arsip Dokumen
 */

class MandatoryGPSSystem {
    constructor() {
        this.isGPSEnabled = false;
        this.currentPosition = null;
        this.watchId = null;
        this.realTimeTrackingInterval = null;
        this.lastLocationUpdate = null;
        this.locationUpdateInterval = 30000; // 30 detik
        this.options = {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 60000 // 1 menit cache
        };
        
        // Bind methods
        this.init = this.init.bind(this);
        this.showMandatoryGPSModal = this.showMandatoryGPSModal.bind(this);
        this.enableGPS = this.enableGPS.bind(this);
        this.startRealTimeTracking = this.startRealTimeTracking.bind(this);
        this.disableAllButtons = this.disableAllButtons.bind(this);
        this.enableAllButtons = this.enableAllButtons.bind(this);
    }

    /**
     * Initialize mandatory GPS system
     */
    init() {
        // Check if geolocation is supported
        if (!('geolocation' in navigator)) {
            this.showUnsupportedBrowserModal();
            return;
        }

        // Check if GPS is already enabled from session
        this.checkGPSStatus();
    }

    /**
     * Check GPS status from server session
     */
    async checkGPSStatus() {
        try {
            // First check browser permission status
            if ('permissions' in navigator) {
                try {
                    const permission = await navigator.permissions.query({ name: 'geolocation' });
                    if (permission.state === 'granted') {
                        // Permission granted, try to get current position to verify GPS works
                        try {
                            const position = await this.getCurrentPosition();
                            // GPS works, save to server and enable system
                            const serverResult = await this.sendLocationToServer(position, 'GPS_ENABLED');
                            if (serverResult.success) {
                                this.isGPSEnabled = true;
                                this.currentPosition = position;
                                this.enableAllButtons();
                                this.startRealTimeTracking();
                                return; // Exit early, GPS is working
                            }
                        } catch (gpsError) {
                            console.log('GPS permission granted but location unavailable:', gpsError);
                            // Continue to show modal for troubleshooting
                        }
                    }
                } catch (permError) {
                    console.log('Permission API not available:', permError);
                }
            }
            
            // Check server session status
            const response = await fetch('api/check_gps_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.gps_enabled) {
                // GPS already enabled in session, start real-time tracking
                this.isGPSEnabled = true;
                this.enableAllButtons();
                this.startRealTimeTracking();
            } else {
                // GPS not enabled, show mandatory modal
                this.disableAllButtons();
                setTimeout(() => {
                    this.showMandatoryGPSModal();
                }, 1000);
            }
        } catch (error) {
            console.error('Error checking GPS status:', error);
            // Fallback: show GPS modal
            this.disableAllButtons();
            setTimeout(() => {
                this.showMandatoryGPSModal();
            }, 1000);
        }
    }

    /**
     * Show mandatory GPS modal
     */
    showMandatoryGPSModal() {
        const modalHtml = `
            <div class="modal fade" id="mandatoryGPSModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Aktivasi GPS Wajib
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="fas fa-location-crosshairs fa-4x text-warning"></i>
                                </div>
                                <h4 class="text-warning">GPS Harus Diaktifkan</h4>
                                <p class="text-muted">Untuk keamanan sistem, Anda wajib mengaktifkan GPS sebelum menggunakan dashboard.</p>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Mengapa GPS Diperlukan?</h6>
                                <ul class="mb-0">
                                    <li>Memantau lokasi akses untuk keamanan sistem</li>
                                    <li>Mencegah akses tidak sah dari lokasi mencurigakan</li>
                                    <li>Melacak aktivitas real-time untuk audit</li>
                                    <li>Memastikan integritas data dan sistem</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Penting!</h6>
                                <p class="mb-2">Seluruh tombol dan fitur dashboard akan <strong>dinonaktifkan</strong> sampai GPS berhasil diaktifkan.</p>
                                <div class="small">
                                    <strong>Debug Info:</strong><br>
                                    Browser: <span id="debugBrowser">-</span><br>
                                    HTTPS: <span id="debugHTTPS">-</span><br>
                                    Geolocation Support: <span id="debugGeolocation">-</span><br>
                                    Permission Status: <span id="debugPermission">-</span>
                                </div>
                            </div>
                            
                            <div id="gpsStatus" class="text-center">
                                <div class="spinner-border text-primary me-2" role="status" style="display: none;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span id="gpsStatusText">Klik tombol di bawah untuk mengaktifkan GPS</span>
                            </div>
                            
                            <div id="gpsError" class="alert alert-danger mt-3" style="display: none;">
                                <h6><i class="fas fa-exclamation-circle me-2"></i>Error GPS</h6>
                                <p id="gpsErrorMessage"></p>
                                <div class="mt-2">
                                    <strong>Solusi:</strong>
                                    <ul class="mb-0">
                                        <li>Pastikan GPS/Location Services aktif di perangkat</li>
                                        <li>Berikan izin lokasi untuk browser</li>
                                        <li>Refresh halaman dan coba lagi</li>
                                        <li>Gunakan browser yang mendukung HTML5 Geolocation</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div id="gpsSuccess" class="alert alert-success mt-3" style="display: none;">
                                <h6><i class="fas fa-check-circle me-2"></i>GPS Berhasil Diaktifkan!</h6>
                                <div id="locationInfo"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="enableGPSBtn">
                                <i class="fas fa-map-marker-alt me-1"></i>Aktifkan GPS Sekarang
                            </button>
                            <button type="button" class="btn btn-success" id="autoEnableGPSBtn">
                                <i class="fas fa-magic me-1"></i>Auto Enable GPS
                            </button>
                            <button type="button" class="btn btn-secondary" id="refreshPageBtn" style="display: none;">
                                <i class="fas fa-refresh me-1"></i>Refresh Halaman
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('mandatoryGPSModal'));
        modal.show();

        // Populate debug information
        this.populateDebugInfo();

        // Add event listeners
        document.getElementById('enableGPSBtn').addEventListener('click', this.enableGPS);
        document.getElementById('autoEnableGPSBtn').addEventListener('click', this.autoEnableGPS.bind(this));
        document.getElementById('refreshPageBtn').addEventListener('click', () => {
            window.location.reload();
        });
    }

    /**
     * Enable GPS and start tracking
     */
    async enableGPS() {
        const statusElement = document.getElementById('gpsStatus');
        const statusText = document.getElementById('gpsStatusText');
        const errorElement = document.getElementById('gpsError');
        const successElement = document.getElementById('gpsSuccess');
        const enableBtn = document.getElementById('enableGPSBtn');
        const refreshBtn = document.getElementById('refreshPageBtn');
        const spinner = statusElement.querySelector('.spinner-border');

        // Show loading
        spinner.style.display = 'inline-block';
        statusText.textContent = 'Memeriksa izin GPS...';
        enableBtn.disabled = true;
        errorElement.style.display = 'none';
        successElement.style.display = 'none';

        try {
            let position = null;
            
            // Check permission first
            if ('permissions' in navigator) {
                try {
                    const permission = await navigator.permissions.query({ name: 'geolocation' });
                    if (permission.state === 'granted') {
                        statusText.textContent = 'Izin GPS sudah diberikan, mengambil lokasi...';
                        // Permission already granted, get location directly
                        position = await this.getCurrentPosition();
                    } else if (permission.state === 'denied') {
                        throw new Error('Izin GPS ditolak. Silakan aktifkan di pengaturan browser.');
                    } else {
                        // Permission prompt
                        statusText.textContent = 'Meminta izin GPS...';
                        position = await this.getCurrentPosition();
                    }
                } catch (permError) {
                    console.log('Permission API error, trying direct method:', permError);
                    statusText.textContent = 'Meminta izin GPS...';
                    position = await this.getCurrentPosition();
                }
            } else {
                // No permission API, try direct
                statusText.textContent = 'Meminta izin GPS...';
                position = await this.getCurrentPosition();
            }
            
            if (!position) {
                throw new Error('Gagal mendapatkan lokasi GPS');
            }
            
            // Update status
            statusText.textContent = 'Menyimpan lokasi ke server...';
            
            // Send location to server
            const serverResult = await this.sendLocationToServer(position, 'GPS_ENABLED');
            
            if (serverResult.success) {
                // Get address information
                statusText.textContent = 'Mengambil informasi alamat...';
                const address = await this.reverseGeocode(position.coords.latitude, position.coords.longitude);
                
                // Show success
                spinner.style.display = 'none';
                statusText.textContent = 'GPS berhasil diaktifkan!';
                
                const locationInfo = document.getElementById('locationInfo');
                locationInfo.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Koordinat:</strong><br>
                            <small>${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}</small>
                        </div>
                        <div class="col-md-6">
                            <strong>Akurasi:</strong><br>
                            <small>${Math.round(position.coords.accuracy)} meter</small>
                        </div>
                    </div>
                    ${address ? `<div class="mt-2"><strong>Alamat:</strong><br><small>${address.formatted_address || 'Alamat tidak tersedia'}</small></div>` : ''}
                `;
                
                successElement.style.display = 'block';
                enableBtn.style.display = 'none';
                refreshBtn.style.display = 'inline-block';
                
                // Enable GPS flag
                this.isGPSEnabled = true;
                this.currentPosition = position;
                
                // Enable all buttons
                this.enableAllButtons();
                
                // Start real-time tracking
                this.startRealTimeTracking();
                
                // Auto close modal after 3 seconds
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('mandatoryGPSModal'));
                    if (modal) modal.hide();
                }, 3000);
                
            } else {
                throw new Error(serverResult.message || 'Gagal menyimpan lokasi ke server');
            }
            
        } catch (error) {
            console.error('GPS Error:', error);
            
            // Show error
            spinner.style.display = 'none';
            statusText.textContent = 'Gagal mengaktifkan GPS';
            
            // Check if this is an auto-fixable error
            if (error.canAutoFix && window.autoGPSEnabler) {
                // Hide this modal and show the fix modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('mandatoryGPSModal'));
                if (modal) modal.hide();
                
                // Show auto GPS enabler fix modal
                window.autoGPSEnabler.showLocationFixModal(error);
                
                // Set up callback for when GPS is fixed
                window.autoGPSEnabler.onLocationEnabled = (position) => {
                    // GPS was successfully enabled, continue with our flow
                    this.sendLocationToServer(position, 'GPS_ENABLED')
                        .then(result => {
                            if (result.success) {
                                this.isGPSEnabled = true;
                                this.currentPosition = position;
                                this.enableAllButtons();
                                this.startRealTimeTracking();
                                this.showToast('GPS berhasil diaktifkan!', 'success');
                            }
                        })
                        .catch(err => {
                            this.showToast('Gagal menyimpan lokasi: ' + err.message, 'danger');
                        });
                };
                
                return;
            }
            
            const errorMessage = document.getElementById('gpsErrorMessage');
            if (error.code) {
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage.textContent = 'Izin lokasi ditolak. Silakan berikan izin lokasi untuk browser ini.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage.textContent = 'Informasi lokasi tidak tersedia. Pastikan GPS aktif di perangkat Anda.';
                        break;
                    case error.TIMEOUT:
                        errorMessage.textContent = 'Timeout dalam mendapatkan lokasi. Coba lagi atau periksa koneksi GPS.';
                        break;
                    default:
                        errorMessage.textContent = error.message || 'Error tidak diketahui dalam mendapatkan lokasi.';
                        break;
                }
            } else {
                errorMessage.textContent = error.message || 'Terjadi kesalahan saat mengaktifkan GPS.';
            }
            
            errorElement.style.display = 'block';
            enableBtn.disabled = false;
            
            // Add retry suggestion
            const retryAlert = document.createElement('div');
            retryAlert.className = 'alert alert-info mt-2';
            retryAlert.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>
                <strong>Saran:</strong> Coba refresh halaman atau periksa pengaturan lokasi browser Anda.
            `;
            if (!errorElement.querySelector('.alert-info')) {
                errorElement.appendChild(retryAlert);
            }
        }
    }

    /**
     * Populate debug information in the modal
     */
    populateDebugInfo() {
        // Browser info
        const browserElement = document.getElementById('debugBrowser');
        if (browserElement) {
            const userAgent = navigator.userAgent;
            let browserName = 'Unknown';
            
            if (userAgent.includes('Chrome') && !userAgent.includes('Edg')) {
                browserName = 'Chrome';
            } else if (userAgent.includes('Firefox')) {
                browserName = 'Firefox';
            } else if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) {
                browserName = 'Safari';
            } else if (userAgent.includes('Edg')) {
                browserName = 'Edge';
            }
            
            browserElement.textContent = browserName;
        }
        
        // HTTPS status
        const httpsElement = document.getElementById('debugHTTPS');
        if (httpsElement) {
            const isSecure = location.protocol === 'https:' || location.hostname === 'localhost';
            httpsElement.textContent = isSecure ? 'Secure' : 'Insecure';
            httpsElement.className = isSecure ? 'text-success' : 'text-danger';
        }
        
        // Geolocation support
        const geolocationElement = document.getElementById('debugGeolocation');
        if (geolocationElement) {
            const isSupported = 'geolocation' in navigator;
            geolocationElement.textContent = isSupported ? 'Supported' : 'Not Supported';
            geolocationElement.className = isSupported ? 'text-success' : 'text-danger';
        }
        
        // Permission status
        const permissionElement = document.getElementById('debugPermission');
        if (permissionElement && 'permissions' in navigator) {
            navigator.permissions.query({ name: 'geolocation' })
                .then(permission => {
                    permissionElement.textContent = permission.state;
                    
                    if (permission.state === 'granted') {
                        permissionElement.className = 'text-success';
                    } else if (permission.state === 'denied') {
                        permissionElement.className = 'text-danger';
                    } else {
                        permissionElement.className = 'text-warning';
                    }
                })
                .catch(() => {
                    permissionElement.textContent = 'Unknown';
                    permissionElement.className = 'text-muted';
                });
        } else if (permissionElement) {
            permissionElement.textContent = 'Not Available';
            permissionElement.className = 'text-muted';
        }
    }

    /**
     * Auto Enable GPS using smart detection
     */
    async autoEnableGPS() {
        const statusElement = document.getElementById('gpsStatus');
        const statusText = document.getElementById('gpsStatusText');
        const errorElement = document.getElementById('gpsError');
        const successElement = document.getElementById('gpsSuccess');
        const enableBtn = document.getElementById('enableGPSBtn');
        const autoEnableBtn = document.getElementById('autoEnableGPSBtn');
        const refreshBtn = document.getElementById('refreshPageBtn');
        const spinner = statusElement.querySelector('.spinner-border');

        // Show loading
        spinner.style.display = 'inline-block';
        statusText.textContent = 'Auto-detecting GPS settings...';
        enableBtn.disabled = true;
        autoEnableBtn.disabled = true;
        errorElement.style.display = 'none';
        successElement.style.display = 'none';

        try {
            if (window.autoGPSEnabler) {
                // Initialize auto GPS enabler
                await window.autoGPSEnabler.init();
                
                statusText.textContent = 'Checking device GPS settings...';
                
                // Check device location settings
                const locationSettings = await window.autoGPSEnabler.detectLocationSettings();
                
                if (!locationSettings.enabled) {
                    if (locationSettings.reason === 'GPS disabled on device') {
                        statusText.textContent = 'GPS disabled on device';
                        spinner.style.display = 'none';
                        
                        // Show device GPS instructions
                        window.autoGPSEnabler.showDeviceGPSInstructions();
                        
                        enableBtn.disabled = false;
                        autoEnableBtn.disabled = false;
                        return;
                    }
                }
                
                statusText.textContent = 'Attempting smart GPS activation...';
                
                // Try auto enable GPS
                const result = await window.autoGPSEnabler.autoEnableGPS();
                
                if (result.success) {
                    const position = result.position;
                    
                    // Update status
                    statusText.textContent = 'Saving location to server...';
                    
                    // Send location to server
                    const serverResult = await this.sendLocationToServer(position, 'GPS_ENABLED');
                    
                    if (serverResult.success) {
                        // Get address information
                        statusText.textContent = 'Getting address information...';
                        const address = await this.reverseGeocode(position.coords.latitude, position.coords.longitude);
                        
                        // Show success
                        spinner.style.display = 'none';
                        statusText.textContent = 'GPS successfully auto-enabled!';
                        
                        const locationInfo = document.getElementById('locationInfo');
                        locationInfo.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Coordinates:</strong><br>
                                    <small>${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}</small>
                                </div>
                                <div class="col-md-6">
                                    <strong>Accuracy:</strong><br>
                                    <small>${Math.round(position.coords.accuracy)} meters</small>
                                </div>
                            </div>
                            ${address ? `<div class="mt-2"><strong>Address:</strong><br><small>${address.formatted_address || 'Address not available'}</small></div>` : ''}
                            <div class="mt-2">
                                <small class="text-success">
                                    <i class="fas fa-magic me-1"></i>
                                    GPS was automatically enabled using smart detection!
                                </small>
                            </div>
                        `;
                        
                        successElement.style.display = 'block';
                        enableBtn.style.display = 'none';
                        autoEnableBtn.style.display = 'none';
                        refreshBtn.style.display = 'inline-block';
                        
                        // Enable GPS flag
                        this.isGPSEnabled = true;
                        this.currentPosition = position;
                        
                        // Enable all buttons
                        this.enableAllButtons();
                        
                        // Start real-time tracking
                        this.startRealTimeTracking();
                        
                        // Show success notification
                        this.showToast('GPS berhasil diaktifkan secara otomatis!', 'success');
                        
                        // Auto close modal after 3 seconds
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('mandatoryGPSModal'));
                            if (modal) modal.hide();
                        }, 3000);
                        
                    } else {
                        throw new Error(serverResult.message || 'Failed to save location to server');
                    }
                } else {
                    throw new Error('Auto GPS enable failed');
                }
            } else {
                throw new Error('Auto GPS Enabler not available');
            }
            
        } catch (error) {
            console.error('Auto GPS Error:', error);
            
            // Show error
            spinner.style.display = 'none';
            statusText.textContent = 'Auto GPS enable failed';
            
            // Check if this is an auto-fixable error
            if (error.canAutoFix && window.autoGPSEnabler) {
                // Hide this modal and show the fix modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('mandatoryGPSModal'));
                if (modal) modal.hide();
                
                // Show auto GPS enabler fix modal
                window.autoGPSEnabler.showLocationFixModal(error);
                
                // Set up callback for when GPS is fixed
                window.autoGPSEnabler.onLocationEnabled = (position) => {
                    // GPS was successfully enabled, continue with our flow
                    this.sendLocationToServer(position, 'GPS_ENABLED')
                        .then(result => {
                            if (result.success) {
                                this.isGPSEnabled = true;
                                this.currentPosition = position;
                                this.enableAllButtons();
                                this.startRealTimeTracking();
                                this.showToast('GPS berhasil diaktifkan!', 'success');
                            }
                        })
                        .catch(err => {
                            this.showToast('Gagal menyimpan lokasi: ' + err.message, 'danger');
                        });
                };
                
                return;
            }
            
            const errorMessage = document.getElementById('gpsErrorMessage');
            errorMessage.textContent = error.message || 'Failed to auto-enable GPS. Please try manual activation.';
            
            errorElement.style.display = 'block';
            enableBtn.disabled = false;
            autoEnableBtn.disabled = false;
            
            // Show fallback suggestion
            const fallbackAlert = document.createElement('div');
            fallbackAlert.className = 'alert alert-info mt-2';
            fallbackAlert.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>
                <strong>Tip:</strong> Try the manual "Aktifkan GPS Sekarang" button, or check your browser and device location settings.
            `;
            errorElement.appendChild(fallbackAlert);
        }
    }
    /**
     * Get current position with enhanced error handling
     */
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!('geolocation' in navigator)) {
                reject(new Error('Geolocation tidak didukung browser ini'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve(position);
                },
                (error) => {
                    // Enhanced error handling
                    let enhancedError = error;
                    enhancedError.canAutoFix = false;
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            enhancedError.message = 'Izin lokasi ditolak. Silakan berikan izin lokasi untuk browser ini.';
                            enhancedError.canAutoFix = true;
                            break;
                        case error.POSITION_UNAVAILABLE:
                            enhancedError.message = 'Informasi lokasi tidak tersedia. Pastikan GPS aktif di perangkat Anda.';
                            enhancedError.canAutoFix = true;
                            break;
                        case error.TIMEOUT:
                            enhancedError.message = 'Timeout dalam mendapatkan lokasi. Coba lagi atau periksa koneksi GPS.';
                            enhancedError.canAutoFix = false;
                            break;
                        default:
                            enhancedError.message = 'Error tidak diketahui dalam mendapatkan lokasi.';
                            enhancedError.canAutoFix = false;
                            break;
                    }
                    
                    reject(enhancedError);
                },
                this.options
            );
        });
    }

    /**
     * Send location to server
     */
    async sendLocationToServer(position, action = 'REALTIME_UPDATE') {
        try {
            const locationData = {
                action: action,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                altitude: position.coords.altitude,
                timestamp: position.timestamp,
                user_agent: navigator.userAgent,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
            };

            const response = await fetch('api/save_geolocation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(locationData)
            });

            const result = await response.json();
            return result;
            
        } catch (error) {
            console.error('Error sending location to server:', error);
            throw error;
        }
    }

    /**
     * Reverse geocoding
     */
    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                {
                    headers: {
                        'User-Agent': 'SistemArsipDokumen/1.0'
                    }
                }
            );
            
            const data = await response.json();
            
            if (data && data.address) {
                return {
                    formatted_address: data.display_name,
                    road: data.address.road || '',
                    suburb: data.address.suburb || data.address.neighbourhood || '',
                    city: data.address.city || data.address.town || data.address.village || '',
                    state: data.address.state || '',
                    country: data.address.country || '',
                    postcode: data.address.postcode || ''
                };
            }
            
            return null;
        } catch (error) {
            console.error('Reverse geocoding error:', error);
            return null;
        }
    }

    /**
     * Start real-time GPS tracking
     */
    startRealTimeTracking() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
        }

        // Start watching position
        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                this.currentPosition = position;
                this.onPositionUpdate(position);
            },
            (error) => {
                console.error('Real-time GPS error:', error);
                this.onPositionError(error);
            },
            this.options
        );

        // Start periodic updates
        if (this.realTimeTrackingInterval) {
            clearInterval(this.realTimeTrackingInterval);
        }

        this.realTimeTrackingInterval = setInterval(() => {
            if (this.currentPosition) {
                this.sendLocationToServer(this.currentPosition, 'REALTIME_UPDATE')
                    .then(result => {
                        if (result.success) {
                            this.lastLocationUpdate = new Date();
                            this.updateLocationStatus();
                        }
                    })
                    .catch(error => {
                        console.error('Real-time update error:', error);
                    });
            }
        }, this.locationUpdateInterval);

        console.log('Real-time GPS tracking started');
    }

    /**
     * Stop real-time tracking
     */
    stopRealTimeTracking() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        if (this.realTimeTrackingInterval) {
            clearInterval(this.realTimeTrackingInterval);
            this.realTimeTrackingInterval = null;
        }

        console.log('Real-time GPS tracking stopped');
    }

    /**
     * Position update callback
     */
    onPositionUpdate(position) {
        // Update location status indicator if exists
        this.updateLocationStatus();
        
        // Log position update
        console.log('GPS position updated:', {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy
        });
    }

    /**
     * Position error callback
     */
    onPositionError(error) {
        console.error('GPS position error:', error);
        
        // Show error notification
        this.showLocationErrorNotification(error);
        
        // If GPS becomes unavailable, disable buttons again
        if (error.code === error.PERMISSION_DENIED) {
            this.isGPSEnabled = false;
            this.disableAllButtons();
            this.showMandatoryGPSModal();
        }
    }

    /**
     * Update location status indicator
     */
    updateLocationStatus() {
        const statusElement = document.getElementById('gpsStatusIndicator');
        if (statusElement) {
            const now = new Date();
            const lastUpdate = this.lastLocationUpdate || now;
            const timeDiff = (now - lastUpdate) / 1000; // seconds
            
            if (timeDiff < 60) {
                statusElement.innerHTML = `
                    <i class="fas fa-map-marker-alt text-success me-1"></i>
                    <small class="text-success">GPS Aktif (${Math.round(timeDiff)}s yang lalu)</small>
                `;
            } else {
                statusElement.innerHTML = `
                    <i class="fas fa-map-marker-alt text-warning me-1"></i>
                    <small class="text-warning">GPS Aktif (${Math.round(timeDiff/60)}m yang lalu)</small>
                `;
            }
        }
    }

    /**
     * Show location error notification
     */
    showLocationErrorNotification(error) {
        let message = 'Terjadi error pada GPS tracking';
        
        if (error.code) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Izin lokasi ditolak. GPS akan dinonaktifkan.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Lokasi tidak tersedia. Periksa GPS perangkat Anda.';
                    break;
                case error.TIMEOUT:
                    message = 'Timeout GPS. Mencoba lagi...';
                    break;
            }
        }

        // Show toast notification
        this.showToast(message, 'warning');
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-map-marker-alt me-2"></i>${message}
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

    /**
     * Disable all buttons and interactive elements
     */
    disableAllButtons() {
        // Disable all buttons
        const buttons = document.querySelectorAll('button:not([data-bs-dismiss]):not(.btn-close)');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('gps-disabled');
        });

        // Disable all links
        const links = document.querySelectorAll('a:not([data-bs-dismiss])');
        links.forEach(link => {
            link.style.pointerEvents = 'none';
            link.classList.add('gps-disabled');
        });

        // Disable form inputs
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.disabled = true;
            input.classList.add('gps-disabled');
        });

        // Add overlay to indicate disabled state
        this.addDisabledOverlay();
    }

    /**
     * Enable all buttons and interactive elements
     */
    enableAllButtons() {
        // Enable all buttons
        const buttons = document.querySelectorAll('button.gps-disabled');
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('gps-disabled');
        });

        // Enable all links
        const links = document.querySelectorAll('a.gps-disabled');
        links.forEach(link => {
            link.style.pointerEvents = '';
            link.classList.remove('gps-disabled');
        });

        // Enable form inputs
        const inputs = document.querySelectorAll('input.gps-disabled, select.gps-disabled, textarea.gps-disabled');
        inputs.forEach(input => {
            input.disabled = false;
            input.classList.remove('gps-disabled');
        });

        // Remove disabled overlay
        this.removeDisabledOverlay();
    }

    /**
     * Add disabled overlay
     */
    addDisabledOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'gpsDisabledOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.1);
            z-index: 1000;
            pointer-events: none;
        `;
        
        document.body.appendChild(overlay);
    }

    /**
     * Remove disabled overlay
     */
    removeDisabledOverlay() {
        const overlay = document.getElementById('gpsDisabledOverlay');
        if (overlay) {
            document.body.removeChild(overlay);
        }
    }

    /**
     * Show unsupported browser modal
     */
    showUnsupportedBrowserModal() {
        const modalHtml = `
            <div class="modal fade" id="unsupportedBrowserModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Browser Tidak Didukung
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-browser fa-4x text-danger"></i>
                            </div>
                            <p>Browser Anda tidak mendukung HTML5 Geolocation API yang diperlukan untuk sistem ini.</p>
                            <div class="alert alert-info">
                                <h6>Browser yang Didukung:</h6>
                                <ul class="mb-0">
                                    <li>Google Chrome (versi terbaru)</li>
                                    <li>Mozilla Firefox (versi terbaru)</li>
                                    <li>Microsoft Edge (versi terbaru)</li>
                                    <li>Safari (versi terbaru)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                                <i class="fas fa-refresh me-1"></i>Refresh Halaman
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('unsupportedBrowserModal'));
        modal.show();

        // Disable all functionality
        this.disableAllButtons();
    }

    /**
     * Cleanup when page unloads
     */
    cleanup() {
        this.stopRealTimeTracking();
    }
}

// Global instance
window.mandatoryGPS = new MandatoryGPSSystem();

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.mandatoryGPS.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.mandatoryGPS) {
        window.mandatoryGPS.cleanup();
    }
});

// Add GPS status indicator to navbar if it exists
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        const statusIndicator = document.createElement('div');
        statusIndicator.id = 'gpsStatusIndicator';
        statusIndicator.className = 'navbar-text me-3';
        statusIndicator.innerHTML = `
            <i class="fas fa-map-marker-alt text-muted me-1"></i>
            <small class="text-muted">GPS Menunggu...</small>
        `;
        
        // Insert before the last navbar item
        const navbarNav = navbar.querySelector('.navbar-nav');
        if (navbarNav) {
            navbarNav.parentNode.insertBefore(statusIndicator, navbarNav);
        }
    }
});