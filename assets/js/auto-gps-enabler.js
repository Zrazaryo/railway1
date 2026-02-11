/**
 * Auto GPS Enabler - Sistem untuk membantu user menyalakan GPS via browser
 * Sistem Arsip Dokumen
 */

class AutoGPSEnabler {
    constructor() {
        this.isSupported = 'geolocation' in navigator;
        this.permissionStatus = null;
        this.deviceType = this.detectDeviceType();
        this.browserType = this.detectBrowserType();
        this.osType = this.detectOSType();
        
        // Bind methods
        this.init = this.init.bind(this);
        this.checkPermissionStatus = this.checkPermissionStatus.bind(this);
        this.requestLocationPermission = this.requestLocationPermission.bind(this);
        this.showLocationSettings = this.showLocationSettings.bind(this);
        this.detectLocationSettings = this.detectLocationSettings.bind(this);
    }

    /**
     * Initialize auto GPS enabler
     */
    async init() {
        if (!this.isSupported) {
            return { success: false, message: 'Geolocation tidak didukung browser ini' };
        }

        // Check current permission status
        await this.checkPermissionStatus();
        
        return { success: true, permissionStatus: this.permissionStatus };
    }

    /**
     * Check current geolocation permission status
     */
    async checkPermissionStatus() {
        if ('permissions' in navigator) {
            try {
                const permission = await navigator.permissions.query({ name: 'geolocation' });
                this.permissionStatus = permission.state;
                
                // Listen for permission changes
                permission.addEventListener('change', () => {
                    this.permissionStatus = permission.state;
                    this.onPermissionChange(permission.state);
                });
                
                return permission.state;
            } catch (error) {
                console.warn('Permission API not fully supported:', error);
                this.permissionStatus = 'unknown';
                return 'unknown';
            }
        } else {
            this.permissionStatus = 'unknown';
            return 'unknown';
        }
    }

    /**
     * Request location permission with enhanced UX
     */
    async requestLocationPermission() {
        return new Promise((resolve, reject) => {
            // Check if permission is already granted
            if ('permissions' in navigator) {
                navigator.permissions.query({ name: 'geolocation' })
                    .then(permission => {
                        if (permission.state === 'granted') {
                            // Permission already granted, get location directly
                            this.getLocationDirectlyLegacy(resolve, reject);
                            return;
                        } else if (permission.state === 'denied') {
                            // Permission denied, reject immediately
                            reject({
                                success: false,
                                message: 'Izin lokasi ditolak',
                                canAutoFix: true,
                                fixInstructions: this.getPermissionFixInstructions(),
                                code: 1 // PERMISSION_DENIED
                            });
                            return;
                        }
                        // If prompt, continue with normal flow
                        this.requestLocationWithDialog(resolve, reject);
                    })
                    .catch(() => {
                        // Fallback if permissions API fails
                        this.requestLocationWithDialog(resolve, reject);
                    });
            } else {
                // No permissions API, use direct request
                this.requestLocationWithDialog(resolve, reject);
            }
        });
    }

    /**
     * Get location directly (for granted permissions)
     */
    async getLocationDirectly() {
        return new Promise((resolve, reject) => {
            const options = {
                enableHighAccuracy: true,
                timeout: 10000, // Shorter timeout for already granted permissions
                maximumAge: 60000 // Allow cached location
            };
            
            navigator.geolocation.getCurrentPosition(
                resolve,
                reject,
                options
            );
        });
    }

    /**
     * Get location directly without showing dialog (for already granted permissions) - Legacy method
     */
    getLocationDirectlyLegacy(resolve, reject) {
        const options = {
            enableHighAccuracy: true,
            timeout: 8000, // Shorter timeout for already granted permissions
            maximumAge: 60000 // Allow cached location
        };
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                if (resolve) {
                    resolve({
                        success: true,
                        position: position,
                        message: 'Lokasi berhasil diaktifkan'
                    });
                } else {
                    return position;
                }
            },
            (error) => {
                const enhancedError = {
                    success: false,
                    error: error,
                    message: this.getErrorMessage(error),
                    canAutoFix: error.code !== 3, // Not timeout
                    fixInstructions: error.code === 1 ? this.getPermissionFixInstructions() : this.getLocationUnavailableInstructions(),
                    code: error.code
                };
                
                if (reject) {
                    reject(enhancedError);
                } else {
                    throw enhancedError;
                }
            },
            options
        );
    }

    /**
     * Request location with dialog (for prompt state)
     */
    requestLocationWithDialog(resolve, reject) {
        // Show loading indicator
        this.showLocationRequestDialog();
        
        // Enhanced options for better GPS accuracy
        const options = {
            enableHighAccuracy: true,
            timeout: 20000, // Increased timeout
            maximumAge: 0 // Force fresh location
        };
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.hideLocationRequestDialog();
                resolve({
                    success: true,
                    position: position,
                    message: 'Lokasi berhasil diaktifkan'
                });
            },
            (error) => {
                this.hideLocationRequestDialog();
                
                const enhancedError = {
                    success: false,
                    error: error,
                    message: this.getErrorMessage(error),
                    canAutoFix: error.code !== 3, // Not timeout
                    fixInstructions: error.code === 1 ? this.getPermissionFixInstructions() : this.getLocationUnavailableInstructions(),
                    code: error.code
                };
                
                reject(enhancedError);
            },
            options
        );
    }

    /**
     * Get error message based on error code
     */
    getErrorMessage(error) {
        switch(error.code) {
            case 1: // PERMISSION_DENIED
                return 'Izin lokasi ditolak';
            case 2: // POSITION_UNAVAILABLE
                return 'Lokasi tidak tersedia';
            case 3: // TIMEOUT
                return 'Timeout mendapatkan lokasi';
            default:
                return 'Error tidak diketahui';
        }
    }

    /**
     * Show location request dialog
     */
    showLocationRequestDialog() {
        // Remove existing dialog
        const existingDialog = document.getElementById('locationRequestDialog');
        if (existingDialog) {
            existingDialog.remove();
        }

        const dialog = document.createElement('div');
        dialog.id = 'locationRequestDialog';
        dialog.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
        dialog.style.cssText = `
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            backdrop-filter: blur(5px);
        `;

        dialog.innerHTML = `
            <div class="card shadow-lg" style="max-width: 400px; width: 90%;">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="text-primary">Mengaktifkan GPS...</h5>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Browser akan meminta izin akses lokasi. Silakan klik <strong>"Allow"</strong> atau <strong>"Izinkan"</strong>.
                        </small>
                    </div>
                    
                    <div class="text-muted">
                        <small>
                            <i class="fas fa-shield-alt me-1"></i>
                            Lokasi Anda aman dan hanya digunakan untuk keamanan sistem.
                        </small>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="autoGPSEnabler.cancelLocationRequest()">
                            <i class="fas fa-times me-1"></i>Batal
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(dialog);
        
        // Auto-hide after 30 seconds to prevent infinite loading
        setTimeout(() => {
            this.hideLocationRequestDialog();
        }, 30000);
    }

    /**
     * Hide location request dialog
     */
    hideLocationRequestDialog() {
        const dialog = document.getElementById('locationRequestDialog');
        if (dialog) {
            dialog.remove();
        }
    }

    /**
     * Cancel location request
     */
    cancelLocationRequest() {
        this.hideLocationRequestDialog();
        
        // Show error message
        this.showErrorNotification('Permintaan lokasi dibatalkan oleh user');
        
        // Trigger callback if exists
        if (this.onLocationCancelled) {
            this.onLocationCancelled();
        }
    }

    /**
     * Get permission fix instructions based on browser and OS
     */
    getPermissionFixInstructions() {
        const instructions = [];
        
        if (this.browserType === 'chrome') {
            instructions.push({
                title: 'Chrome - Aktifkan Lokasi',
                steps: [
                    'Klik ikon kunci/info di sebelah kiri URL',
                    'Pilih "Site settings" atau "Pengaturan situs"',
                    'Ubah "Location" dari "Block" ke "Allow"',
                    'Refresh halaman dan coba lagi'
                ],
                icon: 'fab fa-chrome',
                color: 'warning'
            });
        } else if (this.browserType === 'firefox') {
            instructions.push({
                title: 'Firefox - Aktifkan Lokasi',
                steps: [
                    'Klik ikon shield di sebelah kiri URL',
                    'Klik "Turn off Tracking Protection"',
                    'Atau klik ikon "i" → Permissions → Location → Allow',
                    'Refresh halaman dan coba lagi'
                ],
                icon: 'fab fa-firefox',
                color: 'danger'
            });
        } else if (this.browserType === 'safari') {
            instructions.push({
                title: 'Safari - Aktifkan Lokasi',
                steps: [
                    'Buka Safari → Preferences → Websites',
                    'Pilih "Location" di sidebar kiri',
                    'Set website ini ke "Allow"',
                    'Refresh halaman dan coba lagi'
                ],
                icon: 'fab fa-safari',
                color: 'info'
            });
        }

        // OS-specific instructions
        if (this.osType === 'android') {
            instructions.push({
                title: 'Android - Aktifkan GPS',
                steps: [
                    'Buka Settings → Location',
                    'Pastikan Location Services ON',
                    'Buka Settings → Apps → Browser',
                    'Pilih Permissions → Location → Allow',
                    'Kembali ke browser dan refresh'
                ],
                icon: 'fab fa-android',
                color: 'success'
            });
        } else if (this.osType === 'ios') {
            instructions.push({
                title: 'iOS - Aktifkan GPS',
                steps: [
                    'Buka Settings → Privacy & Security → Location Services',
                    'Pastikan Location Services ON',
                    'Scroll ke bawah, pilih browser (Safari/Chrome)',
                    'Pilih "While Using App"',
                    'Kembali ke browser dan refresh'
                ],
                icon: 'fab fa-apple',
                color: 'secondary'
            });
        } else if (this.osType === 'windows') {
            instructions.push({
                title: 'Windows - Aktifkan GPS',
                steps: [
                    'Buka Settings → Privacy → Location',
                    'Pastikan "Location for this device" ON',
                    'Pastikan "Allow apps to access location" ON',
                    'Scroll ke bawah, pastikan browser diizinkan',
                    'Restart browser dan coba lagi'
                ],
                icon: 'fab fa-windows',
                color: 'primary'
            });
        }

        return instructions;
    }

    /**
     * Get location unavailable fix instructions
     */
    getLocationUnavailableInstructions() {
        return [
            {
                title: 'GPS Tidak Tersedia - Solusi',
                steps: [
                    'Pastikan GPS/Location Services aktif di perangkat',
                    'Keluar dari gedung atau area tertutup',
                    'Tunggu beberapa saat untuk GPS lock',
                    'Restart browser dan coba lagi',
                    'Gunakan WiFi untuk assisted GPS'
                ],
                icon: 'fas fa-satellite-dish',
                color: 'warning'
            }
        ];
    }

    /**
     * Show comprehensive location fix modal
     */
    showLocationFixModal(error) {
        const modalHtml = `
            <div class="modal fade" id="locationFixModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="fas fa-tools me-2"></i>
                                Cara Mengaktifkan GPS
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>GPS Error</h6>
                                <p class="mb-0">${error.message}</p>
                            </div>
                            
                            <div class="row">
                                ${error.fixInstructions.map((instruction, index) => `
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-${instruction.color}">
                                            <div class="card-header bg-${instruction.color} text-white">
                                                <h6 class="mb-0">
                                                    <i class="${instruction.icon} me-2"></i>
                                                    ${instruction.title}
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <ol class="mb-0">
                                                    ${instruction.steps.map(step => `<li><small>${step}</small></li>`).join('')}
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb me-2"></i>Tips Tambahan</h6>
                                <ul class="mb-0">
                                    <li>Pastikan menggunakan HTTPS (bukan HTTP)</li>
                                    <li>Coba refresh halaman setelah mengubah pengaturan</li>
                                    <li>Restart browser jika masih bermasalah</li>
                                    <li>Gunakan browser terbaru untuk kompatibilitas terbaik</li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" onclick="autoGPSEnabler.retryLocationRequest()">
                                <i class="fas fa-redo me-1"></i>Coba Lagi
                            </button>
                            <button type="button" class="btn btn-info" onclick="autoGPSEnabler.openBrowserLocationSettings()">
                                <i class="fas fa-cog me-1"></i>Buka Pengaturan Browser
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal
        const existingModal = document.getElementById('locationFixModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('locationFixModal'));
        modal.show();
    }

    /**
     * Retry location request
     */
    async retryLocationRequest() {
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('locationFixModal'));
        if (modal) modal.hide();

        // Wait a bit then retry
        setTimeout(async () => {
            try {
                const result = await this.requestLocationPermission();
                if (result.success) {
                    this.showSuccessNotification('GPS berhasil diaktifkan!');
                    // Trigger success callback if exists
                    if (this.onLocationEnabled) {
                        this.onLocationEnabled(result.position);
                    }
                }
            } catch (error) {
                if (error.canAutoFix) {
                    this.showLocationFixModal(error);
                } else {
                    this.showErrorNotification(error.message);
                }
            }
        }, 500);
    }

    /**
     * Open browser location settings
     */
    openBrowserLocationSettings() {
        if (this.browserType === 'chrome') {
            // Chrome settings URL
            window.open('chrome://settings/content/location', '_blank');
        } else if (this.browserType === 'firefox') {
            // Firefox about:preferences
            window.open('about:preferences#privacy', '_blank');
        } else if (this.browserType === 'safari') {
            // Safari preferences
            alert('Buka Safari → Preferences → Websites → Location untuk mengatur izin lokasi.');
        } else {
            // Generic instruction
            alert('Buka pengaturan browser Anda dan cari bagian "Location" atau "Privacy" untuk mengatur izin lokasi.');
        }
    }

    /**
     * Detect device type
     */
    detectDeviceType() {
        const userAgent = navigator.userAgent.toLowerCase();
        
        if (/mobile|android|iphone|ipad|phone/i.test(userAgent)) {
            return 'mobile';
        } else if (/tablet|ipad/i.test(userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Detect browser type
     */
    detectBrowserType() {
        const userAgent = navigator.userAgent.toLowerCase();
        
        if (userAgent.includes('chrome') && !userAgent.includes('edg')) {
            return 'chrome';
        } else if (userAgent.includes('firefox')) {
            return 'firefox';
        } else if (userAgent.includes('safari') && !userAgent.includes('chrome')) {
            return 'safari';
        } else if (userAgent.includes('edg')) {
            return 'edge';
        } else {
            return 'unknown';
        }
    }

    /**
     * Detect OS type
     */
    detectOSType() {
        const userAgent = navigator.userAgent.toLowerCase();
        
        if (/android/i.test(userAgent)) {
            return 'android';
        } else if (/iphone|ipad|ipod/i.test(userAgent)) {
            return 'ios';
        } else if (/windows/i.test(userAgent)) {
            return 'windows';
        } else if (/mac/i.test(userAgent)) {
            return 'macos';
        } else if (/linux/i.test(userAgent)) {
            return 'linux';
        } else {
            return 'unknown';
        }
    }

    /**
     * Show success notification
     */
    showSuccessNotification(message) {
        this.showToast(message, 'success');
    }

    /**
     * Show error notification
     */
    showErrorNotification(message) {
        this.showToast(message, 'danger');
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
     * Auto-enable GPS with smart detection
     */
    async autoEnableGPS() {
        try {
            // First, check if we already have permission
            const permissionStatus = await this.checkPermissionStatus();
            
            if (permissionStatus === 'granted') {
                // Already have permission, get location directly without dialog
                try {
                    const position = await this.getLocationDirectly();
                    return {
                        success: true,
                        position: position,
                        message: 'Lokasi berhasil diaktifkan'
                    };
                } catch (error) {
                    // Even with permission, location might fail
                    throw {
                        success: false,
                        message: 'GPS permission granted but location unavailable: ' + error.message,
                        canAutoFix: true,
                        fixInstructions: this.getLocationUnavailableInstructions(),
                        code: 2 // POSITION_UNAVAILABLE
                    };
                }
            } else if (permissionStatus === 'denied') {
                // Permission denied, show fix instructions
                const error = {
                    success: false,
                    message: 'Izin lokasi ditolak. Ikuti panduan untuk mengaktifkan GPS.',
                    canAutoFix: true,
                    fixInstructions: this.getPermissionFixInstructions(),
                    code: 1 // PERMISSION_DENIED
                };
                throw error;
            } else {
                // Permission prompt or unknown, request permission with dialog
                const result = await this.requestLocationPermission();
                return result;
            }
        } catch (error) {
            // Enhance error with fix instructions if not already present
            if (!error.fixInstructions && error.code) {
                if (error.code === 1) { // PERMISSION_DENIED
                    error.canAutoFix = true;
                    error.fixInstructions = this.getPermissionFixInstructions();
                } else if (error.code === 2) { // POSITION_UNAVAILABLE
                    error.canAutoFix = true;
                    error.fixInstructions = this.getLocationUnavailableInstructions();
                } else {
                    error.canAutoFix = false;
                    error.fixInstructions = [];
                }
            }
            
            throw error;
        }
    }

    /**
     * Get location directly (for granted permissions)
     */
    async getLocationDirectly() {
        return new Promise((resolve, reject) => {
            const options = {
                enableHighAccuracy: true,
                timeout: 10000, // Shorter timeout for already granted permissions
                maximumAge: 60000 // Allow cached location
            };
            
            navigator.geolocation.getCurrentPosition(
                resolve,
                reject,
                options
            );
        });
    }

    /**
     * Permission change callback
     */
    onPermissionChange(state) {
        console.log('Permission state changed:', state);
        
        if (state === 'granted') {
            this.showSuccessNotification('Izin lokasi diberikan!');
        } else if (state === 'denied') {
            this.showErrorNotification('Izin lokasi ditolak');
        }
        
        // Trigger callback if exists
        if (this.onPermissionStateChange) {
            this.onPermissionStateChange(state);
        }
    }

    /**
     * Check if location services are enabled on device
     */
    async detectLocationSettings() {
        try {
            // Try to get position with very short timeout
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    resolve,
                    reject,
                    { timeout: 1000, maximumAge: 0, enableHighAccuracy: false }
                );
            });
            
            return { enabled: true, position: position };
        } catch (error) {
            if (error.code === error.POSITION_UNAVAILABLE) {
                return { enabled: false, reason: 'GPS disabled on device' };
            } else if (error.code === error.PERMISSION_DENIED) {
                return { enabled: false, reason: 'Permission denied' };
            } else {
                return { enabled: false, reason: 'Timeout or other error' };
            }
        }
    }

    /**
     * Show device-specific GPS enable instructions
     */
    showDeviceGPSInstructions() {
        let instructions = [];
        
        if (this.osType === 'android') {
            instructions = [
                'Buka Settings (Pengaturan)',
                'Pilih "Location" atau "Lokasi"',
                'Aktifkan "Use location" atau "Gunakan lokasi"',
                'Pilih mode "High accuracy" untuk hasil terbaik',
                'Kembali ke browser dan refresh halaman'
            ];
        } else if (this.osType === 'ios') {
            instructions = [
                'Buka Settings (Pengaturan)',
                'Pilih "Privacy & Security"',
                'Pilih "Location Services"',
                'Aktifkan "Location Services"',
                'Scroll ke bawah, pilih browser yang digunakan',
                'Pilih "While Using App"'
            ];
        } else if (this.osType === 'windows') {
            instructions = [
                'Buka Settings (Windows + I)',
                'Pilih "Privacy"',
                'Pilih "Location" di sidebar kiri',
                'Aktifkan "Location for this device"',
                'Aktifkan "Allow apps to access your location"',
                'Pastikan browser diizinkan mengakses lokasi'
            ];
        } else {
            instructions = [
                'Buka pengaturan perangkat Anda',
                'Cari bagian "Location" atau "Privacy"',
                'Aktifkan layanan lokasi',
                'Berikan izin lokasi untuk browser',
                'Restart browser dan coba lagi'
            ];
        }

        const modal = `
            <div class="modal fade" id="deviceGPSModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Aktifkan GPS di Perangkat
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                GPS tampaknya dinonaktifkan di perangkat Anda.
                            </div>
                            
                            <h6>Langkah-langkah untuk ${this.osType.toUpperCase()}:</h6>
                            <ol>
                                ${instructions.map(step => `<li>${step}</li>`).join('')}
                            </ol>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Setelah mengaktifkan GPS, kembali ke halaman ini dan coba lagi.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" onclick="autoGPSEnabler.retryLocationRequest()">
                                <i class="fas fa-redo me-1"></i>Coba Lagi
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal
        const existingModal = document.getElementById('deviceGPSModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add and show modal
        document.body.insertAdjacentHTML('beforeend', modal);
        const modalInstance = new bootstrap.Modal(document.getElementById('deviceGPSModal'));
        modalInstance.show();
    }
}

// Global instance
window.autoGPSEnabler = new AutoGPSEnabler();

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.autoGPSEnabler.init();
});