/**
 * HTML5 Geolocation API untuk deteksi lokasi user
 * Sistem Arsip Dokumen
 */

class GeolocationTracker {
    constructor() {
        this.isSupported = 'geolocation' in navigator;
        this.currentPosition = null;
        this.watchId = null;
        this.options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000 // 5 menit cache
        };
    }

    /**
     * Cek apakah browser mendukung geolocation
     */
    isGeolocationSupported() {
        return this.isSupported;
    }

    /**
     * Dapatkan posisi user saat ini
     */
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!this.isSupported) {
                reject(new Error('Geolocation tidak didukung browser ini'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.currentPosition = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        altitude: position.coords.altitude,
                        altitudeAccuracy: position.coords.altitudeAccuracy,
                        heading: position.coords.heading,
                        speed: position.coords.speed,
                        timestamp: position.timestamp
                    };
                    resolve(this.currentPosition);
                },
                (error) => {
                    let errorMessage = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'User menolak permintaan geolocation';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Informasi lokasi tidak tersedia';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Timeout dalam mendapatkan lokasi';
                            break;
                        default:
                            errorMessage = 'Error tidak diketahui dalam mendapatkan lokasi';
                            break;
                    }
                    reject(new Error(errorMessage));
                },
                this.options
            );
        });
    }

    /**
     * Mulai tracking posisi user
     */
    startWatching() {
        if (!this.isSupported) {
            console.warn('Geolocation tidak didukung');
            return false;
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                this.currentPosition = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    altitude: position.coords.altitude,
                    altitudeAccuracy: position.coords.altitudeAccuracy,
                    heading: position.coords.heading,
                    speed: position.coords.speed,
                    timestamp: position.timestamp
                };
                
                // Trigger event untuk update posisi
                this.onPositionUpdate(this.currentPosition);
            },
            (error) => {
                console.error('Geolocation error:', error);
                this.onPositionError(error);
            },
            this.options
        );

        return true;
    }

    /**
     * Hentikan tracking posisi
     */
    stopWatching() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
    }

    /**
     * Callback ketika posisi berubah
     */
    onPositionUpdate(position) {
        // Override di implementasi
        console.log('Position updated:', position);
    }

    /**
     * Callback ketika terjadi error
     */
    onPositionError(error) {
        // Override di implementasi
        console.error('Position error:', error);
    }

    /**
     * Kirim data geolocation ke server
     */
    async sendLocationToServer(action = 'UPDATE_LOCATION') {
        try {
            const position = await this.getCurrentPosition();
            
            const locationData = {
                action: action,
                latitude: position.latitude,
                longitude: position.longitude,
                accuracy: position.accuracy,
                altitude: position.altitude,
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
            
            if (result.success) {
                console.log('Location saved successfully');
                return result;
            } else {
                throw new Error(result.message || 'Failed to save location');
            }
            
        } catch (error) {
            console.error('Error sending location to server:', error);
            throw error;
        }
    }

    /**
     * Format koordinat untuk display
     */
    formatCoordinates(lat, lng, precision = 6) {
        return {
            latitude: parseFloat(lat).toFixed(precision),
            longitude: parseFloat(lng).toFixed(precision),
            dms: this.convertToDMS(lat, lng)
        };
    }

    /**
     * Konversi koordinat decimal ke DMS (Degrees, Minutes, Seconds)
     */
    convertToDMS(lat, lng) {
        const convertDMS = (coordinate, isLatitude) => {
            const absolute = Math.abs(coordinate);
            const degrees = Math.floor(absolute);
            const minutesNotTruncated = (absolute - degrees) * 60;
            const minutes = Math.floor(minutesNotTruncated);
            const seconds = Math.floor((minutesNotTruncated - minutes) * 60);
            
            const direction = isLatitude 
                ? (coordinate >= 0 ? 'N' : 'S')
                : (coordinate >= 0 ? 'E' : 'W');
                
            return `${degrees}°${minutes}'${seconds}"${direction}`;
        };

        return {
            latitude: convertDMS(lat, true),
            longitude: convertDMS(lng, false)
        };
    }

    /**
     * Hitung jarak antara dua koordinat (Haversine formula)
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Radius bumi dalam km
        const dLat = this.toRadians(lat2 - lat1);
        const dLng = this.toRadians(lng2 - lng1);
        
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c;
        
        return {
            km: distance,
            meters: distance * 1000,
            formatted: distance < 1 
                ? `${Math.round(distance * 1000)} meter`
                : `${distance.toFixed(2)} km`
        };
    }

    /**
     * Konversi derajat ke radian
     */
    toRadians(degrees) {
        return degrees * (Math.PI / 180);
    }

    /**
     * Dapatkan alamat dari koordinat (Reverse Geocoding)
     */
    async reverseGeocode(lat, lng) {
        try {
            // Menggunakan service gratis dari OpenStreetMap Nominatim
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
}

// Instance global
window.geoTracker = new GeolocationTracker();

// Auto-initialize ketika DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Cek apakah user sudah memberikan permission sebelumnya
    if (navigator.permissions) {
        navigator.permissions.query({name: 'geolocation'}).then(function(result) {
            if (result.state === 'granted') {
                console.log('Geolocation permission already granted');
            } else if (result.state === 'prompt') {
                console.log('Geolocation permission will be prompted');
            } else {
                console.log('Geolocation permission denied');
            }
        });
    }
});

// Fungsi helper untuk integrasi dengan sistem log
window.logActivityWithLocation = async function(action, description = '') {
    try {
        if (window.geoTracker.isGeolocationSupported()) {
            await window.geoTracker.sendLocationToServer(action);
        }
        
        // Fallback ke log activity biasa jika geolocation gagal
        if (typeof logActivity === 'function') {
            logActivity(action, description);
        }
    } catch (error) {
        console.warn('Geolocation failed, using fallback:', error);
        if (typeof logActivity === 'function') {
            logActivity(action, description);
        }
    }
};