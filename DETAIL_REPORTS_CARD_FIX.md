# Perbaikan Card Statistik di Detail Laporan

## Masalah yang Diperbaiki

**Masalah:** Di halaman detail laporan, card "Dokumen Aktif" dan "Total Dokumen Pemusnahan" menunjukkan angka 0, padahal user tersebut memiliki dokumen.

**Penyebab:** Card menggunakan statistik berdasarkan tanggal tertentu (`active_total` dan `selected_day_destroyed`), bukan total keseluruhan.

## Solusi yang Diterapkan

### 1. Perubahan Data Source untuk Card

**Sebelum:**
- Card "Dokumen Aktif" menggunakan: `$document_stats['active_total']` (hanya tanggal tertentu)
- Card "Total Dokumen Pemusnahan" menggunakan: `$document_stats['selected_day_destroyed']` (hanya tanggal tertentu)

**Sesudah:**
- Card "Total Dokumen Aktif" menggunakan: `$document_stats['overall_total']` (semua waktu)
- Card "Total Dokumen Pemusnahan" menggunakan: `$document_stats['overall_destroyed']` (semua waktu)

### 2. Perubahan Label Card

**Sebelum:**
- "Dokumen Aktif (Tanggal)" → "Dokumen Aktif" → **"Total Dokumen Aktif"**
- "Dokumen Pemusnahan (Tanggal)" → **"Total Dokumen Pemusnahan"**

**Sesudah:**
- Label lebih jelas menunjukkan bahwa ini adalah total keseluruhan, bukan berdasarkan tanggal

### 3. Konsistensi dengan Profil User

Bagian profil user di sidebar kiri sudah menggunakan data yang benar:
- Dokumen Aktif: `$document_stats['overall_total']` ✅
- Dokumen Pemusnahan: `$document_stats['overall_destroyed']` ✅
- Total Semua: `$document_stats['overall_all']` ✅

Sekarang card di bagian kanan juga konsisten dengan profil user.

## Hasil Perbaikan

### Test Results untuk Staff User (ID: 14):

**Sebelum Perbaikan:**
- Card "Dokumen Aktif": 0 (salah - hanya tanggal 29 Des)
- Card "Total Dokumen Pemusnahan": 0 (salah - hanya tanggal 29 Des)

**Setelah Perbaikan:**
- Card "Total Dokumen Aktif": 2 ✅ (benar - semua waktu)
- Card "Total Dokumen Pemusnahan": 2 ✅ (benar - semua waktu)

### Data Aktual User:
- 2 dokumen aktif: "salwa1" dan "ar" (dibuat 30 Des 2025)
- 2 dokumen pemusnahan: "salwa1" dan "ar" (diimport 30 Des 2025, original creator: staff)

## Logika yang Digunakan

### Query Overall Statistics:
```sql
SELECT 
    COUNT(CASE WHEN status = 'active' THEN 1 END) AS total_active,
    COUNT(CASE WHEN status = 'deleted' THEN 1 END) AS total_destroyed,
    COUNT(*) AS total_all
FROM documents
WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?))
```

### Mapping ke Card:
- `total_active` → Card "Total Dokumen Aktif"
- `total_destroyed` → Card "Total Dokumen Pemusnahan"
- `total_all` → Card "Total Dokumen Keseluruhan"

## File yang Dimodifikasi

1. `reports/detail.php` - Mengubah data source card dari tanggal-spesifik ke total keseluruhan
2. `test_detail_reports_fix.php` - File test untuk verifikasi

## Manfaat

1. **Akurasi Data:** Card menampilkan total yang benar
2. **Konsistensi:** Card selaras dengan profil user di sidebar
3. **User Experience:** User melihat statistik yang relevan dan akurat
4. **Clarity:** Label yang jelas menunjukkan ini adalah total keseluruhan