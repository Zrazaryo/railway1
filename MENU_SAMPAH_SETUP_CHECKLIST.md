# Menu Sampah - Setup Checklist

## Langkah 1: Setup Database (WAJIB DILAKUKAN DULU)
- [ ] Akses: `http://localhost/PROJECT%20ARSIP%20LOKER/documents/setup_trash.php`
- [ ] Lihat pesan "✓ Setup Selesai!"
- [ ] Verifikasi 3 tabel berhasil dibuat:
  - [ ] `document_trash`
  - [ ] `trash_audit_logs`  
  - [ ] Update kolom `status` di `documents`

## Langkah 2: Verifikasi Menu di Sidebar
- [ ] Logout dan Login kembali ke aplikasi
- [ ] Login sebagai **ADMIN** (tidak bisa dengan staff)
- [ ] Lihat sidebar menu di sebelah kiri
- [ ] Cari menu "Menu Sampah" (icon: 🗑️ trash)
- [ ] Menu harusnya ada di bawah "Lemari Pemusnahan"

## Langkah 3: Test Delete Document
- [ ] Pergi ke "Dokumen Keseluruhan"
- [ ] Lihat list dokumen aktif
- [ ] Pilih satu dokumen
- [ ] Klik tombol "Hapus" (trash icon)
- [ ] Tekan "Hapus" di modal konfirmasi
- [ ] Lihat pesan success "Dokumen berhasil dipindahkan ke sampah"

## Langkah 4: Verifikasi Dokumen di Menu Sampah
- [ ] Klik "Menu Sampah" di sidebar
- [ ] Verifikasi dokumen yang dihapus tadi muncul di list
- [ ] Check informasi:
  - [ ] Nama dokumen
  - [ ] Nama lengkap pemilik
  - [ ] NIK/Nomor Paspor
  - [ ] Countdown: "Akan dihapus otomatis dalam XX hari"

## Langkah 5: Test Restore Document
- [ ] Di Menu Sampah, klik tombol "Pulihkan" pada dokumen
- [ ] Tekan "Pulihkan" di modal konfirmasi
- [ ] Lihat pesan success "Dokumen berhasil dipulihkan!"
- [ ] Dokumen hilang dari Menu Sampah
- [ ] Verifikasi dokumen kembali ke "Dokumen Keseluruhan" dengan status aktif

## Langkah 6: Test Permanent Delete
- [ ] Hapus dokumen lagi (like Step 3)
- [ ] Buka Menu Sampah
- [ ] Klik tombol "Hapus Permanen" (red trash icon)
- [ ] Tekan "Hapus Permanen" di modal warning (FINAL ACTION!)
- [ ] Lihat pesan success "Dokumen berhasil dihapus permanen!"
- [ ] Dokumen hilang dari Menu Sampah dan tidak bisa di-restore

## Langkah 7: Test Search & Filter
- [ ] Di Menu Sampah, masukkan kata pencarian (nama, NIK, dll)
- [ ] Klik "Cari"
- [ ] Verifikasi hasil filter benar
- [ ] Test sorting: klik dropdown "Urutkan"
- [ ] Verifikasi sort ascending/descending bekerja

## Langkah 8: Test Delete dari Lemari Pemusnahan
- [ ] Pergi ke "Lemari Pemusnahan"
- [ ] Pilih dokumen yang ingin dihapus
- [ ] Klik "Hapus" (trash icon)
- [ ] Verifikasi dokumen masuk ke Menu Sampah
- [ ] (Optional) Test restore dari Menu Sampah

## Troubleshooting Checklist

### Jika melihat error saat setup:
- [ ] Check database connection di `config/database.php`
- [ ] Verify MySQL user memiliki CREATE TABLE privilege
- [ ] Check user database `arsip_dokumen_imigrasi` exists
- [ ] Check PHP version ≥ 7.4

### Jika Menu Sampah tidak muncul di sidebar:
- [ ] [ ] Logout dan login kembali (clear session cache)
- [ ] Reload browser (Ctrl+F5 untuk hard refresh)
- [ ] Check file: `includes/sidebar.php` mengandung "Menu Sampah"
- [ ] Check user role adalah "admin"

### Jika dokumen tidak masuk sampah setelah dihapus:
- [ ] Lihat browser console (F12) untuk JS error
- [ ] Check network tab untuk response dari delete.php
- [ ] Query database: `SELECT * FROM document_trash` - apakah ada data?
- [ ] Query database: `SELECT * FROM documents WHERE status = 'trashed'` - apakah ada?

### Jika ada SQL Error "Unknown column":
- [ ] Jalankan setup ulang: `documents/setup_trash.php`
- [ ] Check struktur tabel: `DESC document_trash`
- [ ] Verify semua kolom yang diperlukan ada (dari guide)

### Jika tidak bisa restore:
- [ ] Check user_id tersimpan di session: `echo $_SESSION['user_id'];`
- [ ] Check tabel `documents` menerima status 'active' (tidak ENUM)
- [ ] Check primary key di `document_trash` valid

## Post-Setup Maintenance

### Daily/Weekly:
- [ ] Monitor error logs jika ada
- [ ] Check Menu Sampah tidak penuh
- [ ] Test restore 1-2 dokumen

### Monthly:
- [ ] Review trash space usage:
  ```sql
  SELECT COUNT(*) as total FROM document_trash 
  WHERE status = 'in_trash';
  ```
- [ ] Clean up permanently deleted records:
  ```sql
  DELETE FROM document_trash 
  WHERE status = 'permanently_deleted' 
  AND restored_at < DATE_SUB(NOW(), INTERVAL 3 MONTH);
  ```

### As Needed:
- [ ] Backup trash data sebelum major update
- [ ] Archive old trash to separate storage
- [ ] Review audit logs untuk compliance

## Success Indicators ✓

Jika semua item di bawah terceklis, Menu Sampah sudah setup dengan benar:

- [x] Database tabel `document_trash` dan `trash_audit_logs` ada
- [x] Menu "Menu Sampah" muncul di sidebar
- [x] Bisa delete dokumen dan muncul di sampah
- [x] Bisa restore dokumen dari sampah
- [x] Bisa permanent delete dokumen
- [x] Search & filter bekerja
- [x] Countdown 30 hari menampilkan dengan benar
- [x] Tidak ada SQL error di console
- [x] Activity logs tercatat di database

## Need Help?

Jika masih ada masalah setelah mengikuti checklist ini:

1. **Check Documentation:**
   - Baca `MENU_SAMPAH_COMPLETE_GUIDE.md` untuk detail lengkap

2. **Debug Information untuk Support:**
   - PHP Version: `<?php echo phpversion(); ?>`
   - MySQL Version: Lihat di phpMyAdmin
   - Error message lengkap (screenshot)
   - Browser console error (F12)

3. **Common Issues & Solutions:**
   - Lihat section "Troubleshooting" di `MENU_SAMPAH_COMPLETE_GUIDE.md`

4. **Contact Admin:**
   - Siapkan error message lengkap
   - Siapkan database backup
   - Siapkan list dokumen yang bermasalah

---

**Last Updated:** 2024
**Status:** Production Ready ✓
