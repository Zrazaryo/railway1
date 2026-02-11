# Google Maps Address Lookup Integration Guide

## Overview

Sistem Arsip Dokumen sekarang telah terintegrasi dengan Google Maps Geocoding API untuk mendapatkan alamat lengkap dari koordinat GPS yang terdeteksi pada landing page dan tersimpan dalam log aktivitas.

## Fitur yang Ditambahkan

### 1. API Endpoint untuk Google Maps
- **File**: `api/get_gmaps_address.php`
- **Fungsi**: Mengambil alamat lengkap dari koordinat GPS menggunakan Google Maps Geocoding API
- **Fallback**: OpenStreetMap Nominatim API jika Google Maps API tidak tersedia
- **Caching**: Sistem cache 24 jam untuk mengurangi API calls

### 2. Frontend Integration
- **File**: `logs/index.php`
- **Fungsi**: JavaScript functions untuk menampilkan alamat Google Maps dalam modal detail lokasi
- **Features**:
  - Loading state saat mengambil alamat
  - Format alamat lengkap dengan detail (jalan, kelurahan, kota, provinsi, dll)
  - Error handling dan fallback
  - Indikator sumber data (Google Maps atau OpenStreetMap)

### 3. Enhanced Geolocation Display
- **File**: `includes/functions.php`
- **Function**: `format_geolocation_info()`
- **Improvement**: Menambahkan hint "Klik untuk alamat Google Maps" pada koordinat yang belum memiliki alamat

### 4. IP Location Integration
- **File**: `logs/ip_detail.php`
- **Improvement**: Menambahkan link ke Google Maps untuk koordinat yang didapat dari IP geolocation

## Cara Kerja

### 1. Flow Utama
```
User Login → GPS Detection → Koordinat Tersimpan → Log Aktivitas → Klik Koordinat → Google Maps API → Alamat Lengkap
```

### 2. API Request Flow
```
Frontend → api/get_gmaps_address.php → Google Maps API → Response → Cache → Frontend Display
```

### 3. Fallback System
```
Google Maps API → (jika gagal) → OpenStreetMap Nominatim → (jika gagal) → Alamat dari database/session
```

## Konfigurasi Google Maps API

### 1. Mendapatkan API Key
1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru atau pilih project yang ada
3. Enable Google Maps Geocoding API
4. Buat API Key di Credentials
5. Restrict API Key untuk keamanan (optional)

### 2. Konfigurasi di Sistem
Edit file `api/get_gmaps_address.php`:
```php
// Ganti dengan API key yang valid
$google_api_key = 'YOUR_ACTUAL_GOOGLE_MAPS_API_KEY';
```

### 3. Tanpa Google Maps API Key
Sistem akan otomatis menggunakan OpenStreetMap Nominatim sebagai fallback jika:
- API key tidak diset (masih `YOUR_GOOGLE_MAPS_API_KEY`)
- Google Maps API error atau limit exceeded
- Network error ke Google Maps

## Format Response API

### Success Response
```json
{
  "success": true,
  "address": {
    "formatted_address": "Jl. Sudirman No.1, Jakarta Pusat, DKI Jakarta, Indonesia",
    "street_number": "1",
    "route": "Jalan Sudirman",
    "sublocality": "Jakarta Pusat",
    "locality": "Jakarta",
    "administrative_area_level_2": "Jakarta",
    "administrative_area_level_1": "DKI Jakarta",
    "country": "Indonesia",
    "postal_code": "10110",
    "place_id": "ChIJ...",
    "types": ["street_address"],
    "geometry": {
      "location": {"lat": -6.200000, "lng": 106.816666},
      "location_type": "ROOFTOP"
    },
    "source": "google_maps"
  },
  "source": "api"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Tidak dapat mendapatkan alamat dari koordinat tersebut"
}
```

## Testing

### 1. Test Page
Akses `test_gmaps_integration.php` untuk:
- Test Google Maps API dengan koordinat Jakarta dan Surabaya
- Melihat format response API
- Test modal detail lokasi
- Verifikasi fallback system

### 2. Manual Testing
1. Login ke sistem
2. Buka Log Aktivitas (`logs/index.php`)
3. Klik pada koordinat GPS di kolom "Lokasi GPS"
4. Modal akan terbuka dan menampilkan alamat dari Google Maps

### 3. API Testing
```bash
curl -X POST http://your-domain/api/get_gmaps_address.php \
  -H "Content-Type: application/json" \
  -d '{"latitude": -6.200000, "longitude": 106.816666}'
```

## Troubleshooting

### 1. API Key Issues
- **Error**: "API key not valid"
- **Solution**: Pastikan API key benar dan Google Maps Geocoding API sudah enabled

### 2. Quota Exceeded
- **Error**: "You have exceeded your daily request quota"
- **Solution**: Upgrade Google Cloud billing atau gunakan fallback OpenStreetMap

### 3. Network Issues
- **Error**: "Failed to fetch address"
- **Solution**: Sistem otomatis fallback ke OpenStreetMap atau alamat dari database

### 4. CORS Issues
- **Error**: "CORS policy blocked"
- **Solution**: API endpoint sudah menggunakan server-side request, tidak ada CORS issue

## Security Considerations

### 1. API Key Protection
- Jangan expose API key di frontend JavaScript
- Gunakan server-side request (sudah implemented)
- Restrict API key di Google Cloud Console

### 2. Rate Limiting
- Sistem menggunakan cache 24 jam
- Fallback ke OpenStreetMap untuk mengurangi Google API calls
- Consider implementing additional rate limiting

### 3. Input Validation
- Koordinat divalidasi sebelum API call
- Sanitasi input untuk mencegah injection

## Performance Optimization

### 1. Caching System
- Cache response selama 24 jam
- Cache key berdasarkan koordinat
- Automatic cache cleanup

### 2. Lazy Loading
- Alamat Google Maps dimuat saat modal dibuka
- Tidak mempengaruhi loading time halaman utama

### 3. Fallback Strategy
- OpenStreetMap sebagai fallback gratis
- Alamat dari database/session sebagai last resort

## Future Enhancements

### 1. Batch Geocoding
- Process multiple coordinates dalam satu request
- Untuk halaman dengan banyak log entries

### 2. Reverse Geocoding Cache
- Simpan hasil geocoding ke database
- Reduce API calls untuk koordinat yang sama

### 3. Map Integration
- Embed Google Maps atau OpenStreetMap
- Show location markers
- Street view integration

### 4. Address Validation
- Validate addresses saat input
- Suggest corrections untuk alamat yang salah

## Monitoring dan Analytics

### 1. API Usage Tracking
- Monitor Google Maps API quota usage
- Track fallback usage statistics
- Log API errors untuk debugging

### 2. Performance Metrics
- Response time monitoring
- Cache hit rate
- Error rate tracking

## Kesimpulan

Integrasi Google Maps Address Lookup telah berhasil diimplementasikan dengan:
- ✅ API endpoint yang robust dengan fallback system
- ✅ Frontend integration yang user-friendly
- ✅ Caching system untuk performance
- ✅ Error handling yang comprehensive
- ✅ Security best practices
- ✅ Test page untuk verification

Sistem sekarang dapat menampilkan alamat lengkap dari koordinat GPS yang terdeteksi, meningkatkan nilai informasi dalam log aktivitas dan membantu admin dalam monitoring keamanan sistem.