# Mandatory GPS System Guide

## Overview

Sistem GPS Wajib telah diimplementasikan pada dashboard admin dan staff untuk meningkatkan keamanan dan monitoring aktivitas user. Sistem ini memaksa user untuk mengaktifkan GPS sebelum dapat menggunakan fitur dashboard dan melakukan tracking real-time lokasi user.

## Fitur Utama

### 1. Mandatory GPS Popup
- **Popup wajib** muncul saat user membuka dashboard
- **Semua tombol dan fitur dinonaktifkan** sampai GPS berhasil diaktifkan
- **Overlay transparan** mencegah interaksi dengan halaman
- **Instruksi lengkap** untuk mengaktifkan GPS
- **Error handling** untuk berbagai kasus error GPS

### 2. Real-Time GPS Tracking
- **Tracking otomatis** setiap 30 detik setelah GPS diaktifkan
- **Watchdog system** untuk memantau perubahan lokasi
- **Background updates** tanpa mengganggu user experience
- **Session management** untuk menyimpan status GPS

### 3. Security Features
- **Location anomaly detection** untuk perubahan lokasi mencurigakan
- **Session validation** untuk memastikan GPS tetap aktif
- **IP address correlation** dengan lokasi GPS
- **Activity logging** untuk semua aksi GPS

### 4. User Experience
- **Status indicator** di navbar menampilkan status GPS
- **Toast notifications** untuk update dan error
- **Graceful degradation** jika GPS tidak didukung
- **Auto-recovery** jika GPS terputus

## Implementasi

### 1. File Structure
```
assets/js/mandatory-gps.js          # Core GPS system
api/check_gps_status.php           # Check GPS status
api/save_geolocation.php           # Save GPS data (enhanced)
api/reset_gps_session.php          # Reset GPS session (testing)
test_mandatory_gps.php             # Test page
MANDATORY_GPS_SYSTEM_GUIDE.md      # Documentation
```

### 2. Dashboard Integration
- **Admin Dashboard**: `dashboard.php`
- **Staff Dashboard**: `staff/dashboard.php`
- **Script inclusion**: `<script src="assets/js/mandatory-gps.js"></script>`

### 3. Database Schema
Menggunakan kolom geolocation yang sudah ada di `activity_logs`:
- `latitude` - GPS latitude
- `longitude` - GPS longitude  
- `accuracy` - GPS accuracy in meters
- `altitude` - GPS altitude
- `timezone` - User timezone
- `address_info` - JSON alamat dari reverse geocoding
- `geolocation_timestamp` - Timestamp GPS

## Cara Kerja

### 1. Initialization Flow
```
Page Load → Check Browser Support → Check GPS Status → Show Modal (if needed) → Enable/Disable UI
```

### 2. GPS Activation Flow
```
User Click Enable → Request Permission → Get Position → Send to Server → Reverse Geocoding → Enable UI → Start Tracking
```

### 3. Real-Time Tracking Flow
```
GPS Watch → Position Update → Send to Server → Update Status → Anomaly Detection → Log Activity
```

## API Endpoints

### 1. Check GPS Status
**Endpoint**: `api/check_gps_status.php`
**Method**: POST
**Purpose**: Memeriksa apakah GPS sudah diaktifkan

**Response**:
```json
{
  "success": true,
  "gps_enabled": true,
  "session_gps": true,
  "db_gps": true,
  "last_gps_record": {
    "latitude": -6.200000,
    "longitude": 106.816666,
    "created_at": "2024-01-01 12:00:00",
    "action": "GPS_ENABLED"
  },
  "message": "GPS is enabled"
}
```

### 2. Save Geolocation
**Endpoint**: `api/save_geolocation.php`
**Method**: POST
**Purpose**: Menyimpan data GPS ke database

**Request**:
```json
{
  "action": "GPS_ENABLED",
  "latitude": -6.200000,
  "longitude": 106.816666,
  "accuracy": 10,
  "altitude": 100,
  "timestamp": 1640995200000,
  "timezone": "Asia/Jakarta"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Lokasi berhasil disimpan",
  "data": {
    "latitude": -6.200000,
    "longitude": 106.816666,
    "accuracy": 10,
    "timestamp": "2024-01-01 12:00:00",
    "action": "GPS_ENABLED",
    "address": {
      "formatted_address": "Jakarta, Indonesia",
      "city": "Jakarta",
      "country": "Indonesia"
    }
  }
}
```

### 3. Reset GPS Session
**Endpoint**: `api/reset_gps_session.php`
**Method**: POST
**Purpose**: Reset GPS session untuk testing

## JavaScript API

### 1. MandatoryGPSSystem Class
```javascript
// Global instance
window.mandatoryGPS = new MandatoryGPSSystem();

// Methods
mandatoryGPS.init()                    // Initialize system
mandatoryGPS.enableGPS()               // Enable GPS manually
mandatoryGPS.startRealTimeTracking()   // Start tracking
mandatoryGPS.stopRealTimeTracking()    // Stop tracking
mandatoryGPS.disableAllButtons()       // Disable UI
mandatoryGPS.enableAllButtons()        // Enable UI
```

### 2. Event Callbacks
```javascript
mandatoryGPS.onPositionUpdate = function(position) {
    // Handle position updates
};

mandatoryGPS.onPositionError = function(error) {
    // Handle GPS errors
};
```

## Configuration

### 1. GPS Options
```javascript
this.options = {
    enableHighAccuracy: true,    // High accuracy GPS
    timeout: 15000,             // 15 second timeout
    maximumAge: 60000           // 1 minute cache
};
```

### 2. Tracking Intervals
```javascript
this.locationUpdateInterval = 30000;  // 30 seconds real-time updates
```

### 3. Session Management
- GPS status disimpan di `$_SESSION['gps_enabled']`
- Last location di `$_SESSION['last_location']`
- Update timestamp di `$_SESSION['last_gps_update']`

## Security Features

### 1. Location Anomaly Detection
- **Distance threshold**: Alert jika perpindahan > 100km dalam 2 jam
- **Speed detection**: Alert jika perpindahan > 1000km dalam 24 jam
- **VPN/Proxy detection**: Alert untuk perpindahan tidak wajar

### 2. Session Security
- **GPS validation** setiap kali akses dashboard
- **Session timeout** jika GPS tidak update > 1 jam
- **Cross-validation** antara IP dan GPS location

### 3. Activity Logging
Semua aksi GPS dicatat dengan detail:
- `GPS_ENABLED` - GPS diaktifkan
- `REALTIME_UPDATE` - Update real-time
- `GPS_SESSION_RESET` - Session direset
- `LOCATION_ANOMALY` - Deteksi anomali lokasi

## Error Handling

### 1. GPS Errors
- **PERMISSION_DENIED**: User menolak izin lokasi
- **POSITION_UNAVAILABLE**: GPS tidak tersedia
- **TIMEOUT**: Timeout mendapatkan lokasi
- **ACCURACY_LOW**: Akurasi GPS rendah

### 2. Network Errors
- **API_ERROR**: Error komunikasi dengan server
- **GEOCODING_ERROR**: Error reverse geocoding
- **DATABASE_ERROR**: Error menyimpan ke database

### 3. Browser Compatibility
- **UNSUPPORTED_BROWSER**: Browser tidak mendukung Geolocation API
- **HTTPS_REQUIRED**: HTTPS diperlukan untuk GPS
- **FEATURE_DISABLED**: GPS dinonaktifkan di browser

## Testing

### 1. Test Page
Akses `test_mandatory_gps.php` untuk:
- Test mandatory GPS popup
- Verifikasi button disable/enable
- Check GPS status
- Manual GPS controls
- Reset GPS session

### 2. Manual Testing Steps
1. **Fresh Session**: Hapus cookies/session, akses dashboard
2. **GPS Denial**: Tolak izin GPS, verifikasi error handling
3. **GPS Enable**: Aktifkan GPS, verifikasi UI enabled
4. **Real-time**: Pindah lokasi, verifikasi tracking
5. **Anomaly**: Test perpindahan jauh, verifikasi alert

### 3. Browser Testing
Test di berbagai browser:
- Chrome (desktop & mobile)
- Firefox (desktop & mobile)
- Safari (desktop & mobile)
- Edge (desktop)

## Troubleshooting

### 1. GPS Tidak Muncul
- Periksa apakah `mandatory-gps.js` dimuat
- Cek console untuk JavaScript errors
- Verifikasi HTTPS (GPS butuh secure context)

### 2. GPS Error Terus
- Pastikan GPS aktif di perangkat
- Berikan izin lokasi untuk browser
- Cek koneksi internet untuk geocoding

### 3. Button Tidak Enable
- Cek status GPS di session
- Verifikasi response API `check_gps_status.php`
- Periksa JavaScript console errors

### 4. Real-time Tidak Jalan
- Cek `watchId` dan `realTimeTrackingInterval`
- Verifikasi API `save_geolocation.php`
- Periksa network requests di DevTools

## Performance Optimization

### 1. GPS Caching
- Cache position selama 1 menit
- Avoid redundant API calls
- Smart update intervals

### 2. Network Optimization
- Batch GPS updates
- Compress JSON payloads
- Error retry with backoff

### 3. UI Optimization
- Lazy load GPS modal
- Debounce position updates
- Efficient DOM manipulation

## Future Enhancements

### 1. Advanced Features
- **Geofencing**: Alert jika keluar area tertentu
- **Route tracking**: Simpan rute perjalanan user
- **Offline support**: Cache GPS saat offline
- **Map integration**: Tampilkan lokasi di peta

### 2. Analytics
- **Location heatmap**: Visualisasi lokasi akses
- **Usage patterns**: Analisis pola penggunaan
- **Security dashboard**: Monitor aktivitas mencurigakan

### 3. Mobile App Integration
- **Push notifications**: Alert real-time
- **Background tracking**: Tracking saat app background
- **Battery optimization**: Efficient GPS usage

## Kesimpulan

Sistem GPS Wajib telah berhasil diimplementasikan dengan fitur:
- ✅ Mandatory GPS popup dengan UI disable
- ✅ Real-time GPS tracking setiap 30 detik
- ✅ Location anomaly detection untuk keamanan
- ✅ Comprehensive error handling
- ✅ Session management dan validation
- ✅ Activity logging untuk audit
- ✅ Test page untuk verification
- ✅ Cross-browser compatibility

Sistem ini meningkatkan keamanan dengan memastikan semua akses dashboard dapat dilacak lokasinya secara real-time, memberikan audit trail yang lengkap untuk aktivitas user.