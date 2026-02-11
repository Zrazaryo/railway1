# Perbaikan Tracking Pembuat Asli Dokumen Pemusnahan

## Masalah
Ketika admin mengupload dokumen ke lemari pemusnahan melalui import Excel/CSV, field `created_by` berubah menjadi admin yang melakukan import, padahal seharusnya tetap menunjukkan pembuat asli dokumen (misalnya staff).

**Contoh masalah:**
- Dokumen "salwa1" dibuat oleh akun "staff"
- Admin mengdownload dan mengupload ke lemari pemusnahan
- Di laporan, dokumen tersebut muncul sebagai "dibuat oleh admin"

## Solusi

### 1. Penambahan Field Database
Menambahkan 2 field baru di tabel `documents`:
- `original_created_by` (INT) - ID pembuat asli dokumen
- `original_created_at` (TIMESTAMP) - Tanggal pembuatan asli

### 2. Modifikasi Proses Import
File `documents/import_pemusnahan.php` dimodifikasi untuk:
- Mendukung kolom "Dibuat Oleh", "Staff", "Pembuat", atau "Username" dalam file Excel/CSV
- Mencari user berdasarkan username, full_name, atau email
- Menyimpan pembuat asli di field `original_created_by`
- Tetap menyimpan admin yang melakukan import di field `created_by`

### 3. Modifikasi Query Laporan
Query di `reports/index.php` dan `reports/detail.php` diubah untuk:
- Prioritas menggunakan `original_created_by` jika tersedia
- Fallback ke `created_by` jika `original_created_by` NULL
- Menampilkan statistik berdasarkan pembuat asli dokumen

### 4. Modifikasi Tampilan Pemusnahan
Query di `documents/pemusnahan.php` diubah untuk:
- Menampilkan nama pembuat asli menggunakan `COALESCE(u_orig.full_name, u.full_name)`
- JOIN dengan tabel users untuk kedua field (created_by dan original_created_by)

## Cara Penggunaan

### Format File Import
Tambahkan kolom "Dibuat Oleh" dalam file Excel/CSV:

```csv
Nama Lengkap,NIK,No Passport,Kode Lemari,Tahun,Dibuat Oleh,Kategori
John Doe,1234567890123456,A1234567,A1.01,2025,staff,WNA
Jane Smith,2345678901234567,B2345678,A1.02,2025,Dian Susilawati,WNI
```

**Kolom "Dibuat Oleh" dapat berisi:**
- Username: `staff`, `admin`
- Nama lengkap: `Dian Susilawati`, `erni puji hastuti`
- Email: `staff@example.com`

### Proses Import
1. Admin upload file Excel/CSV dengan kolom "Dibuat Oleh"
2. Sistem mencari user berdasarkan informasi di kolom tersebut
3. Jika user ditemukan: `original_created_by` = ID user tersebut
4. Jika user tidak ditemukan: `original_created_by` = NULL (fallback ke admin)
5. `created_by` selalu = admin yang melakukan import

### Hasil di Laporan
- Dokumen akan muncul di laporan pembuat asli (bukan admin yang import)
- Statistik pemusnahan akan terhitung untuk pembuat asli
- Kolom "Di Buat Oleh" di lemari pemusnahan menampilkan pembuat asli

## File yang Dimodifikasi

1. **Database Schema:**
   - `add_original_creator_field.sql` - Script SQL
   - `add_original_creator_field.php` - Script PHP untuk update

2. **Import Process:**
   - `documents/import_pemusnahan.php` - Logika import dengan pembuat asli

3. **Reports:**
   - `reports/index.php` - Laporan utama dengan tracking pembuat asli
   - `reports/detail.php` - Detail laporan dengan statistik pembuat asli

4. **Pemusnahan Display:**
   - `documents/pemusnahan.php` - Tampilan dengan nama pembuat asli

5. **Documentation:**
   - `contoh_import_pemusnahan_dengan_pembuat_asli.csv` - Contoh format file

## Query SQL yang Digunakan

### Laporan Utama
```sql
SELECT 
    u.id, u.full_name, u.username, u.role,
    COUNT(CASE WHEN d.status = 'active' AND (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id)) THEN 1 END) as total_dokumen_keseluruhan,
    COUNT(CASE WHEN d.status = 'deleted' AND (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id)) THEN 1 END) as total_dokumen_pemusnahan
FROM users u
LEFT JOIN documents d ON (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id))
WHERE u.status = 'active'
GROUP BY u.id, u.full_name, u.username, u.role
```

### Tampilan Pemusnahan
```sql
SELECT d.*, 
       COALESCE(u_orig.full_name, u.full_name) AS created_by_name
FROM documents d
LEFT JOIN users u ON d.created_by = u.id
LEFT JOIN users u_orig ON d.original_created_by = u_orig.id
WHERE d.status = 'deleted'
```

## Manfaat

1. **Akurasi Laporan**: Dokumen pemusnahan terhitung untuk pembuat asli
2. **Audit Trail**: Tetap mencatat siapa yang melakukan import (admin)
3. **Fleksibilitas**: Mendukung import dengan atau tanpa informasi pembuat asli
4. **Backward Compatibility**: Dokumen lama tetap berfungsi normal

## Testing

Script `test_original_creator_fix.php` untuk memverifikasi:
- ✅ Field database tersedia
- ✅ Import process berfungsi
- ✅ Query laporan benar
- ✅ Tampilan pemusnahan akurat