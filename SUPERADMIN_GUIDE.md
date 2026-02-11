# Panduan Superadmin - Sistem Arsip Dokumen

## Overview
Fitur Superadmin telah ditambahkan ke sistem untuk memberikan akses monitoring dan supervisi penuh terhadap sistem arsip dokumen. Superadmin memiliki kemampuan untuk memantau dokumen dan kinerja staff serta admin.

## Setup Superadmin

### 1. Jalankan Setup Script
Akses file `setup_superadmin.php` melalui browser untuk:
- Menambahkan role 'superadmin' ke database
- Membuat user superadmin default

### 2. Login Credentials Default
- **Username:** `superadmin`
- **Password:** `superadmin123`
- **URL Login:** `auth/login_superadmin.php`

⚠️ **PENTING:** Ganti password setelah login pertama kali!

## Fitur Superadmin

### 1. Dashboard Superadmin
- **URL:** `superadmin/dashboard.php`
- **Fitur:**
  - Statistik keseluruhan sistem
  - Total dokumen aktif dan pemusnahan
  - Total admin dan staff
  - Aktivitas terbaru
  - Grafik penggunaan sistem

### 2. Menu Dokumen Keseluruhan
- **URL:** `superadmin/documents.php`
- **Fitur:**
  - Melihat semua dokumen dalam sistem
  - Filter berdasarkan status, kategori, asal dokumen
  - Statistik dokumen per kategori
  - Monitoring dokumen yang dibuat oleh admin/staff

### 3. Menu Lemari Dokumen
- **URL:** `superadmin/lockers.php`
- **Fitur:**
  - Monitoring semua lemari dokumen
  - Statistik penggunaan kapasitas
  - Status lemari (tersedia, hampir penuh, penuh)
  - Akses cepat ke detail lemari

### 4. Menu Lemari Pemusnahan
- **URL:** `superadmin/destruction.php`
- **Fitur:**
  - Monitoring dokumen pemusnahan per tahun
  - Statistik pemusnahan bulanan
  - Breakdown per rak pemusnahan
  - Filter berdasarkan tahun

### 5. Menu Manajemen User
- **URL:** `superadmin/users.php`
- **Fitur:**
  - Monitoring semua user (admin & staff)
  - Statistik produktivitas user
  - Aktivitas terakhir user
  - Filter berdasarkan role dan status

### 6. Menu Laporan
- **URL:** `superadmin/reports.php`
- **Fitur:**
  - Laporan komprehensif sistem
  - Grafik statistik (Chart.js)
  - Tren pembuatan dokumen
  - Produktivitas user
  - Aktivitas login sistem

### 7. Menu Log Aktivitas
- **URL:** `superadmin/logs.php`
- **Fitur:**
  - Monitoring semua aktivitas sistem
  - Filter berdasarkan user, aksi, tanggal
  - Pagination untuk performa
  - Detail IP address dan user agent
  - Export log (fitur dapat dikembangkan)

## Akses Cepat
Superadmin memiliki akses cepat ke:
- Dashboard Admin (dalam tab baru)
- Dashboard Staff (dalam tab baru)

## Keamanan
- Role superadmin memiliki akses read-only untuk monitoring
- Tidak dapat mengubah data dokumen secara langsung
- Fokus pada supervisi dan monitoring sistem
- Log semua aktivitas superadmin

## Pengembangan Lebih Lanjut
Fitur yang dapat dikembangkan:
1. Export laporan ke PDF/Excel
2. Real-time notifications
3. Advanced analytics dan dashboard
4. Audit trail yang lebih detail
5. System health monitoring
6. Backup dan restore monitoring

## File Structure
```
auth/
├── login_superadmin.php          # Login page untuk superadmin

superadmin/
├── dashboard.php                 # Dashboard utama
├── documents.php                 # Monitoring dokumen keseluruhan
├── lockers.php                   # Monitoring lemari dokumen
├── destruction.php               # Monitoring lemari pemusnahan
├── users.php                     # Manajemen user
├── reports.php                   # Laporan sistem
└── logs.php                      # Log aktivitas

includes/
├── navbar_superadmin.php         # Navbar khusus superadmin
├── sidebar_superadmin.php        # Sidebar dengan menu superadmin
└── functions.php                 # Updated dengan fungsi superadmin

setup_superadmin.php              # Script setup role superadmin
add_superadmin_role.sql          # SQL script untuk manual setup
```

## Troubleshooting

### Error: Column 'role' doesn't have a default value
Jalankan script `setup_superadmin.php` untuk update struktur database.

### Error: Access denied
Pastikan user superadmin sudah dibuat dan aktif di database.

### Error: Page not found
Pastikan semua file superadmin sudah di-upload ke server.

## Support
Untuk bantuan lebih lanjut, hubungi administrator sistem.