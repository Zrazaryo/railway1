# Perbaikan Laporan - Tracking Original Creator untuk Dokumen Pemusnahan

## Masalah yang Diperbaiki

**Masalah:** Di menu laporan, total dokumen pemusnahan untuk staff menunjukkan 0, padahal di lemari pemusnahan ada dokumen yang seharusnya dihitung untuk staff tersebut.

**Penyebab:** Query di `reports/index.php` dan `reports/detail.php` hanya menggunakan field `created_by`, sehingga dokumen yang diimport oleh admin tidak terhitung untuk pembuat asli (original creator).

## Solusi yang Diterapkan

### 1. Perbaikan Query di `reports/index.php`

**Query Lama:**
```sql
LEFT JOIN documents d ON u.id = d.created_by
```

**Query Baru:**
```sql
LEFT JOIN documents d ON (d.original_created_by = u.id OR (d.original_created_by IS NULL AND d.created_by = u.id))
```

**Logika:**
- Prioritas: Gunakan `original_created_by` jika tersedia
- Fallback: Gunakan `created_by` jika `original_created_by` adalah NULL
- Hasil: Dokumen pemusnahan terhitung untuk pembuat asli, bukan admin yang import

### 2. Perbaikan Query di `reports/detail.php`

Semua query statistik diubah dari:
```sql
WHERE created_by = ?
```

Menjadi:
```sql
WHERE (original_created_by = ? OR (original_created_by IS NULL AND created_by = ?))
```

Parameter yang digunakan: `[$user_id, $user_id, ...]`

### 3. Query yang Diperbaiki

1. **Statistik dokumen aktif**
2. **Statistik dokumen pemusnahan** 
3. **Total keseluruhan**
4. **Kategori dokumen**
5. **Daftar dokumen aktif**
6. **Daftar dokumen pemusnahan**

## Hasil Perbaikan

### Sebelum Perbaikan:
- Staff: Total Dokumen Pemusnahan = 0
- Admin: Total Dokumen Pemusnahan = 2 (salah, karena admin hanya import)

### Setelah Perbaikan:
- Staff: Total Dokumen Pemusnahan = 2 ✅ (benar, sesuai pembuat asli)
- Admin: Total Dokumen Pemusnahan = 0 ✅ (benar, admin hanya import)

## Contoh Kasus

**Dokumen "salwa1":**
- Dibuat asli oleh: staff (ID: 14)
- Diimport oleh: admin (ID: 1)
- Field database:
  - `created_by` = 1 (admin)
  - `original_created_by` = 14 (staff)
- **Hasil:** Dokumen terhitung di laporan staff, bukan admin

## Backward Compatibility

Perbaikan ini tetap kompatibel dengan dokumen lama:
- Dokumen tanpa `original_created_by` (NULL) tetap menggunakan `created_by`
- Dokumen dengan `original_created_by` menggunakan pembuat asli
- Tidak ada data yang hilang atau rusak

## Testing

File test: `test_reports_fix.php`

**Hasil Test:**
- ✅ Query lama: 0 dokumen untuk staff
- ✅ Query baru: 2 dokumen untuk staff  
- ✅ Dokumen "ar" dan "salwa1" terhitung untuk staff
- ✅ Laporan menunjukkan angka yang benar

## File yang Dimodifikasi

1. `reports/index.php` - Query utama laporan
2. `reports/detail.php` - Query detail laporan per user
3. `test_reports_fix.php` - File test untuk verifikasi

## Manfaat

1. **Akurasi Laporan:** Dokumen pemusnahan terhitung untuk pembuat asli
2. **Audit Trail:** Admin yang import tetap tercatat di `created_by`
3. **Konsistensi:** Laporan sesuai dengan tampilan di lemari pemusnahan
4. **User Experience:** Staff dapat melihat statistik dokumen mereka yang benar