# ✅ Checklist Deployment ke Hosting

Gunakan checklist ini untuk memastikan semua langkah deployment sudah dilakukan dengan benar.

## 📋 Persiapan

- [ ] Backup database lokal (export dari phpMyAdmin)
- [ ] Backup folder `documents/uploads/` (jika ada file)
- [ ] Backup seluruh folder project (zip)
- [ ] Baca panduan lengkap di `PANDUAN_HOSTING.md`

## 🏢 Setup Hosting

- [ ] Daftar ke provider hosting
- [ ] Pilih paket hosting sesuai kebutuhan
- [ ] Daftarkan domain (atau gunakan subdomain)
- [ ] Login ke cPanel/control panel hosting
- [ ] Catat informasi FTP dan database

## 🗄️ Setup Database

- [ ] Buat database baru di cPanel
- [ ] Buat user database baru
- [ ] Hubungkan user ke database dengan ALL PRIVILEGES
- [ ] Catat informasi database:
  - [ ] DB_HOST: _______________
  - [ ] DB_USER: _______________
  - [ ] DB_PASS: _______________
  - [ ] DB_NAME: _______________

## 📤 Upload File

- [ ] Upload semua file ke `public_html` (atau folder root hosting)
- [ ] Pastikan struktur folder tetap sama
- [ ] Hapus file default hosting (index.html, welcome.html, dll)
- [ ] Pastikan file `.htaccess` sudah ter-upload

## 📥 Import Database

- [ ] Buka phpMyAdmin di hosting
- [ ] Import file SQL backup database
- [ ] Verifikasi database sudah ter-import
- [ ] Cek tabel-tabel sudah ada:
  - [ ] `users`
  - [ ] `document_categories`
  - [ ] `documents`
  - [ ] `activity_logs`

## ⚙️ Konfigurasi

- [ ] Update file `config/database.php` dengan info database hosting
- [ ] Test koneksi database (buka website)
- [ ] Set permission folder `uploads/` menjadi 755 atau 777
- [ ] Set permission folder `documents/uploads/` menjadi 755 atau 777
- [ ] Set permission file `config/database.php` menjadi 644

## ✅ Testing

- [ ] Website bisa diakses via domain/subdomain
- [ ] Halaman landing muncul dengan benar
- [ ] Login berhasil dengan username: `admin`, password: `password`
- [ ] Dashboard admin bisa diakses
- [ ] Test upload file dokumen
- [ ] Test download file dokumen
- [ ] Test pencarian dokumen
- [ ] Test semua fitur utama aplikasi

## 🔒 Keamanan

- [ ] Ganti password default admin
- [ ] Ganti password default staff (jika ada)
- [ ] Setup SSL/HTTPS (jika tersedia)
- [ ] Aktifkan Force HTTPS Redirect
- [ ] Setup backup otomatis (jika hosting support)
- [ ] Hapus file `database.hosting.example.php` (jika ada)

## 📊 Monitoring

- [ ] Cek error log di cPanel
- [ ] Monitor disk usage
- [ ] Monitor bandwidth usage
- [ ] Setup notifikasi jika ada error

## 📝 Dokumentasi

- [ ] Catat URL website: _______________
- [ ] Catat username admin: _______________
- [ ] Catat password admin baru: _______________
- [ ] Simpan info database di tempat aman
- [ ] Simpan info FTP di tempat aman
- [ ] Simpan info cPanel di tempat aman

## 🎉 Final Check

- [ ] Semua fitur aplikasi berfungsi dengan baik
- [ ] Website bisa diakses dari berbagai device
- [ ] Website bisa diakses dari berbagai browser
- [ ] Tidak ada error di error log
- [ ] Backup sudah disiapkan

---

## 📞 Jika Ada Masalah

1. Cek error log di cPanel
2. Cek file `config/database.php` apakah sudah benar
3. Cek permission folder
4. Hubungi support hosting
5. Lihat troubleshooting di `PANDUAN_HOSTING.md`

---

**Status Deployment**: ⬜ Belum Mulai | ⬜ Sedang Proses | ⬜ Selesai

**Tanggal Deployment**: _______________

**Catatan Tambahan**:
_________________________________________________
_________________________________________________
_________________________________________________











