# Tracking Dokumen Pemusnahan di Laporan

## Fitur yang Ditambahkan

### 1. Laporan Utama (`reports/index.php`)
- **Kolom baru**: "Total Dokumen Pemusnahan" 
- **Fungsi**: Menampilkan jumlah dokumen yang telah dihapus (status = 'deleted') per user
- **Badge warna**: Merah (bg-danger) untuk menunjukkan dokumen yang telah dihapus

### 2. Detail Laporan (`reports/detail.php`)
- **Card statistik baru**: "Dokumen Pemusnahan (Tanggal)" dengan warna merah
- **Card total keseluruhan**: Menampilkan total semua dokumen (aktif + pemusnahan)
- **Section terpisah**: "Riwayat Dokumen Pemusnahan" dengan header merah
- **Profil user**: Statistik lengkap (Aktif, Pemusnahan, Total)

## Struktur Database

Dokumen pemusnahan diidentifikasi dengan:
- **Status**: `'deleted'` di tabel `documents`
- **Created by**: Field `created_by` menunjukkan siapa yang membuat/menghapus dokumen
- **Timestamp**: Field `created_at` menunjukkan kapan dokumen dibuat/dihapus

## Query yang Digunakan

### Laporan Utama
```sql
SELECT 
    u.id,
    u.full_name,
    u.username,
    u.role,
    COUNT(CASE WHEN DATE(d.created_at) = CURDATE() AND d.status = 'active' THEN 1 END) as total_dokumen_hari_ini,
    COUNT(CASE WHEN d.status = 'active' THEN 1 END) as total_dokumen_keseluruhan,
    COUNT(CASE WHEN d.status = 'deleted' THEN 1 END) as total_dokumen_pemusnahan
FROM users u
LEFT JOIN documents d ON u.id = d.created_by
WHERE u.status = 'active'
GROUP BY u.id, u.full_name, u.username, u.role
HAVING (total_dokumen_keseluruhan > 0 OR total_dokumen_pemusnahan > 0 OR u.role IN ('admin', 'staff'))
ORDER BY u.full_name ASC
```

### Detail Laporan - Statistik Keseluruhan
```sql
SELECT 
    COUNT(CASE WHEN status = 'active' THEN 1 END) AS total_active,
    COUNT(CASE WHEN status = 'deleted' THEN 1 END) AS total_destroyed,
    COUNT(*) AS total_all
FROM documents
WHERE created_by = ?
```

### Detail Laporan - Dokumen Pemusnahan Harian
```sql
SELECT 
    COUNT(*) AS total_destroyed
FROM documents
WHERE created_by = ? AND status = 'deleted' AND created_at BETWEEN ? AND ?
```

### Daftar Dokumen Pemusnahan
```sql
SELECT 
    id, document_number, full_name, nik, passport_number, 
    citizen_category, status, created_at
FROM documents
WHERE created_by = ? AND status = 'deleted' AND created_at BETWEEN ? AND ?
ORDER BY created_at DESC
```

## Tampilan UI

### Laporan Utama
| No | Nama Staff/Admin | Role | Total Dokumen Hari Ini | Total Dokumen Keseluruhan | **Total Dokumen Pemusnahan** | Aksi |
|----|------------------|------|-------------------------|---------------------------|------------------------------|------|
| 1  | Admin User       | Admin| 5                       | 150                       | **25**                       | Detail |

### Detail Laporan
**Cards Statistik:**
- 🔵 Total Dokumen (Tanggal): 10
- 🟢 Dokumen Aktif (Tanggal): 8  
- 🔴 **Dokumen Pemusnahan (Tanggal): 2**
- 🔵 Total Dokumen Keseluruhan: 200

**Sections:**
1. **Riwayat Dokumen Aktif** (header biru)
2. **Riwayat Dokumen Pemusnahan** (header merah)

## Integrasi dengan Lemari Pemusnahan

Dokumen di "Lemari Pemusnahan" (`documents/pemusnahan.php`) adalah dokumen dengan status `'deleted'` yang:
- Dibuat/dihapus oleh user tertentu (field `created_by`)
- Dapat dilihat statistiknya di laporan
- Menunjukkan siapa yang bertanggung jawab atas penghapusan dokumen

## Manfaat Fitur

1. **Akuntabilitas**: Melacak siapa yang menghapus dokumen
2. **Audit Trail**: Riwayat lengkap aktivitas penghapusan per user
3. **Monitoring**: Statistik harian dan keseluruhan dokumen pemusnahan
4. **Transparansi**: Laporan terpisah untuk dokumen aktif dan yang dihapus

## Cara Penggunaan

1. **Lihat Statistik Umum**: 
   - Buka menu "Laporan"
   - Lihat kolom "Total Dokumen Pemusnahan"

2. **Lihat Detail per User**:
   - Klik tombol "Detail" pada user yang diinginkan
   - Lihat statistik lengkap dan riwayat dokumen pemusnahan

3. **Filter berdasarkan Tanggal**:
   - Gunakan filter tanggal untuk melihat aktivitas penghapusan pada hari tertentu

## File yang Dimodifikasi

- `reports/index.php` - Laporan utama dengan kolom pemusnahan
- `reports/detail.php` - Detail laporan dengan statistik pemusnahan
- `create_test_destruction_data.php` - Script untuk membuat data test
- `DESTRUCTION_DOCUMENTS_TRACKING.md` - Dokumentasi fitur