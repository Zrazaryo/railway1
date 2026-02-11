# 🌐 Panduan Hosting Aplikasi Sistem Arsip Dokumen

**Ya, aplikasi ini BISA di-hosting agar bisa online dan diakses dari mana saja!**

## ✅ Persyaratan Hosting

Aplikasi ini membutuhkan hosting dengan spesifikasi berikut:

### **Spesifikasi Minimum:**
- ✅ **PHP 7.4 atau lebih tinggi** (disarankan PHP 8.0+)
- ✅ **MySQL 5.7 atau lebih tinggi** (disarankan MySQL 8.0+)
- ✅ **Web Server**: Apache atau Nginx
- ✅ **Ekstensi PHP yang Diperlukan**:
  - PDO
  - PDO_MySQL
  - GD (untuk manipulasi gambar)
  - FileInfo (untuk validasi file)
  - mbstring (untuk string handling)
  - session
  - json
- ✅ **Ruang Disk**: Minimal 1GB (lebih besar lebih baik untuk file upload)
- ✅ **Bandwidth**: Sesuai kebutuhan (minimal 10GB/bulan untuk start)
- ✅ **RAM**: Minimal 256MB (512MB+ lebih baik)

### **Fitur Hosting yang Diperlukan:**
- ✅ Akses phpMyAdmin atau MySQL client
- ✅ File Manager (untuk upload file)
- ✅ FTP/SFTP access (untuk transfer file)
- ✅ Support .htaccess (jika pakai Apache)
- ✅ Support session PHP
- ✅ Support file upload (minimal 10MB per file)

---

## 🏢 Rekomendasi Provider Hosting Indonesia

### **1. Hosting Berbayar (Recommended untuk Produksi):**

#### **A. Niagahoster** ⭐⭐⭐⭐⭐
- **Harga**: Mulai dari Rp 9.000/bulan
- **Fitur**: Unlimited bandwidth, SSL gratis, backup otomatis
- **Website**: https://www.niagahoster.co.id
- **Cocok untuk**: Aplikasi produksi dengan traffic tinggi

#### **B. Rumahweb** ⭐⭐⭐⭐
- **Harga**: Mulai dari Rp 10.000/bulan
- **Fitur**: Support 24/7, cPanel, SSL gratis
- **Website**: https://www.rumahweb.com
- **Cocok untuk**: Aplikasi dengan kebutuhan support lokal

#### **C. IDCloudHost** ⭐⭐⭐⭐
- **Harga**: Mulai dari Rp 15.000/bulan
- **Fitur**: Server Indonesia, kecepatan tinggi
- **Website**: https://www.idcloudhost.com
- **Cocok untuk**: Aplikasi yang butuh kecepatan tinggi

#### **D. Domainesia** ⭐⭐⭐⭐
- **Harga**: Mulai dari Rp 8.000/bulan
- **Fitur**: Unlimited bandwidth, SSL gratis
- **Website**: https://www.domainesia.com
- **Cocok untuk**: Budget terbatas tapi tetap berkualitas

### **2. Hosting Gratis (Untuk Testing/Development):**

#### **A. InfinityFree** ⭐⭐⭐
- **Fitur**: Gratis selamanya, 5GB storage, unlimited bandwidth
- **Website**: https://www.infinityfree.net
- **Catatan**: Ada iklan, cocok untuk testing saja

#### **B. 000webhost** ⭐⭐⭐
- **Fitur**: Gratis, 300MB storage, 3GB bandwidth
- **Website**: https://www.000webhost.com
- **Catatan**: Terbatas, cocok untuk testing

---

## 📋 Langkah-Langkah Deployment ke Hosting

### **Persiapan Sebelum Upload**

#### **1. Backup Data Lokal** 📦
1. **Export Database**:
   - Buka phpMyAdmin: `http://localhost/phpmyadmin`
   - Pilih database `arsip_dokumen_imigrasi`
   - Klik tab **"Export"**
   - Pilih **"Quick"** atau **"Custom"**
   - Klik **"Go"** untuk download file SQL
   - Simpan file dengan nama: `backup_database.sql`

2. **Backup File Upload**:
   - Copy folder `documents/uploads/` (jika ada file yang sudah diupload)
   - Simpan di tempat aman

3. **Backup Folder Project**:
   - Zip seluruh folder `PROJECT ARSIP LOKER`
   - Simpan sebagai backup

#### **2. Siapkan File untuk Hosting** 📁

**File yang PERLU diubah sebelum upload:**

1. **File `config/database.php`**:
   - Akan diubah setelah dapat info database dari hosting
   - Simpan dulu file aslinya

2. **File `.htaccess`** (jika ada):
   - Pastikan sudah ada untuk keamanan
   - Jika belum ada, akan dibuat otomatis

**File yang TIDAK perlu diubah:**
- Semua file PHP lainnya
- Folder assets, includes, dll
- Struktur folder tetap sama

---

### **Langkah 1: Daftar & Setup Hosting** 🎯

1. **Daftar ke Provider Hosting** (pilih salah satu dari rekomendasi di atas)
2. **Pilih Paket Hosting** sesuai kebutuhan
3. **Daftarkan Domain** (atau gunakan subdomain gratis dari hosting)
4. **Tunggu email konfirmasi** dari provider
5. **Login ke cPanel** atau control panel hosting

---

### **Langkah 2: Buat Database MySQL** 🗄️

**Di cPanel Hosting:**

1. **Login ke cPanel**
2. **Cari menu "MySQL Databases"** atau **"Database"**
3. **Buat Database Baru**:
   - Nama database: `arsip_dokumen_imigrasi` (atau sesuai keinginan)
   - Klik **"Create Database"**
   - **Catat nama database** (biasanya format: `username_dbname`)

4. **Buat User Database**:
   - Username: buat username baru (contoh: `arsip_user`)
   - Password: buat password yang kuat
   - Klik **"Create User"**
   - **Catat username dan password**

5. **Hubungkan User ke Database**:
   - Pilih user yang baru dibuat
   - Pilih database yang baru dibuat
   - Centang **"ALL PRIVILEGES"**
   - Klik **"Make Changes"**

6. **Catat Informasi Database**:
   ```
   DB_HOST: localhost (atau sesuai info dari hosting)
   DB_USER: [username yang dibuat]
   DB_PASS: [password yang dibuat]
   DB_NAME: [nama database yang dibuat]
   ```

---

### **Langkah 3: Upload File ke Hosting** 📤

**Metode 1: Menggunakan File Manager (Paling Mudah)** ⭐

1. **Login ke cPanel**
2. **Buka "File Manager"**
3. **Masuk ke folder `public_html`** (atau `www` atau `htdocs` - sesuai hosting)
4. **Hapus file default** (jika ada: index.html, welcome.html, dll)
5. **Upload File Project**:
   - Klik **"Upload"** di File Manager
   - Pilih file ZIP project yang sudah disiapkan
   - Tunggu sampai upload selesai
   - **Extract ZIP file**:
     - Klik kanan file ZIP → **"Extract"**
     - Atau gunakan menu **"Extract"** di File Manager
   - **Hapus file ZIP** setelah extract

6. **Atau Upload Manual**:
   - Jika tidak pakai ZIP, upload semua file dan folder satu per satu
   - Pastikan struktur folder tetap sama

**Metode 2: Menggunakan FTP Client (FileZilla)** ⭐⭐

1. **Download FileZilla**: https://filezilla-project.org
2. **Install FileZilla**
3. **Dapatkan Info FTP dari Hosting**:
   - Di cPanel, cari menu **"FTP Accounts"**
   - Catat: **FTP Host**, **Username**, **Password**
   - Atau gunakan **"FTP File Manager"** di cPanel

4. **Koneksi ke FTP**:
   - Buka FileZilla
   - Masukkan:
     - **Host**: [FTP Host dari hosting]
     - **Username**: [FTP Username]
     - **Password**: [FTP Password]
     - **Port**: 21 (atau sesuai info hosting)
   - Klik **"Quickconnect"**

5. **Upload File**:
   - Di panel kiri (Local): pilih folder project lokal
   - Di panel kanan (Remote): masuk ke `public_html`
   - **Drag & drop** semua file dan folder dari kiri ke kanan
   - Tunggu sampai semua file ter-upload

---

### **Langkah 4: Import Database** 📥

1. **Buka phpMyAdmin**:
   - Di cPanel, cari menu **"phpMyAdmin"**
   - Klik untuk membuka

2. **Login phpMyAdmin**:
   - Biasanya auto-login dari cPanel
   - Atau login dengan username/password database yang dibuat

3. **Import Database**:
   - Klik tab **"Import"** di menu atas
   - Klik **"Choose File"**
   - Pilih file `backup_database.sql` yang sudah disiapkan
   - Scroll ke bawah
   - Klik **"Go"** atau **"Import"**
   - Tunggu sampai muncul pesan **"Import has been successfully finished"**

4. **Verifikasi Database**:
   - Di sidebar kiri, pastikan database `arsip_dokumen_imigrasi` sudah muncul
   - Klik database tersebut
   - Pastikan tabel-tabel sudah ada:
     - `users`
     - `document_categories`
     - `documents`
     - `activity_logs`

---

### **Langkah 5: Update Konfigurasi Database** ⚙️

1. **Buka File Manager** di cPanel
2. **Masuk ke folder**: `public_html/config/`
3. **Edit file `database.php`**:
   - Klik kanan file → **"Edit"** atau **"Code Edit"**
   - Update dengan info database dari hosting:

```php
<?php
// Konfigurasi Database
define('DB_HOST', 'localhost'); // atau sesuai info dari hosting
define('DB_USER', 'username_dari_hosting'); // ganti dengan username database
define('DB_PASS', 'password_dari_hosting'); // ganti dengan password database
define('DB_NAME', 'nama_database_dari_hosting'); // ganti dengan nama database

// ... (kode lainnya tetap sama)
```

4. **Save** file tersebut

**Catatan**: 
- `DB_HOST` biasanya `localhost` untuk shared hosting
- Untuk VPS/dedicated server, bisa berbeda (cek di info hosting)
- Pastikan username, password, dan nama database sesuai dengan yang dibuat di cPanel

---

### **Langkah 6: Set Permissions Folder** 🔒

**Di File Manager cPanel:**

1. **Set Permission untuk folder `uploads/`**:
   - Klik kanan folder `uploads/` → **"Change Permissions"**
   - Set menjadi **755** atau **777** (untuk upload file)
   - Centang **"Recurse into subdirectories"**
   - Klik **"Change Permissions"**

2. **Set Permission untuk folder `documents/uploads/`**:
   - Klik kanan folder `documents/uploads/` → **"Change Permissions"**
   - Set menjadi **755** atau **777**
   - Centang **"Recurse into subdirectories"**
   - Klik **"Change Permissions"**

3. **Set Permission untuk file `config/database.php`**:
   - Klik kanan file → **"Change Permissions"**
   - Set menjadi **644** (untuk keamanan)

**Catatan**: 
- **755** = Owner bisa read/write/execute, others bisa read/execute
- **777** = Semua bisa read/write/execute (kurang aman, tapi kadang diperlukan untuk upload)
- **644** = Owner bisa read/write, others hanya read

---

### **Langkah 7: Test Aplikasi** ✅

1. **Buka Browser**
2. **Akses Website**:
   - Jika pakai domain: `http://namadomain.com`
   - Jika pakai subdomain: `http://subdomain.hosting.com`
   - Atau: `http://ip-address-server`

3. **Test Halaman Landing**:
   - Pastikan halaman landing muncul
   - Jika muncul error, cek langkah-langkah sebelumnya

4. **Test Login**:
   - Login dengan:
     - **Username**: `admin`
     - **Password**: `password`
   - Jika login berhasil, berarti setup sudah benar! ✅

5. **Test Upload File**:
   - Coba upload file dokumen
   - Pastikan file bisa ter-upload dan tersimpan

---

## 🔧 Troubleshooting Hosting

### **Error: "Database Connection Failed"** ❌

**Penyebab**: Konfigurasi database salah

**Solusi**:
1. Cek file `config/database.php`
2. Pastikan `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` benar
3. Untuk shared hosting, `DB_HOST` biasanya `localhost`
4. Untuk beberapa hosting, `DB_HOST` bisa berbeda (cek di info hosting)
5. Pastikan user database sudah dihubungkan ke database dengan privilege yang benar

---

### **Error: "404 Not Found"** ❌

**Penyebab**: File tidak ditemukan atau path salah

**Solusi**:
1. Pastikan semua file sudah ter-upload ke folder yang benar (`public_html`)
2. Pastikan file `index.php` ada di root folder
3. Cek struktur folder apakah sudah benar
4. Cek apakah ada file `.htaccess` yang bermasalah

---

### **Error: "Permission Denied" saat Upload File** ❌

**Penyebab**: Permission folder tidak cukup

**Solusi**:
1. Set permission folder `uploads/` menjadi **755** atau **777**
2. Set permission folder `documents/uploads/` menjadi **755** atau **777**
3. Pastikan folder bisa di-write oleh web server

---

### **Error: "File Upload Size Exceeded"** ❌

**Penyebab**: Ukuran file melebihi limit PHP

**Solusi**:
1. **Edit file `php.ini`** (jika bisa diakses):
   - Cari `upload_max_filesize` → set menjadi `10M` atau lebih
   - Cari `post_max_size` → set menjadi `10M` atau lebih
   - Restart web server

2. **Atau buat file `.htaccess`** di root folder:
```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value max_input_time 300
```

3. **Atau hubungi support hosting** untuk meningkatkan limit upload

---

### **Error: "Session Start Failed"** ❌

**Penyebab**: Permission folder session tidak cukup

**Solusi**:
1. Pastikan folder `tmp` atau folder session bisa di-write
2. Atau set session save path di `config/database.php` atau file utama:
```php
ini_set('session.save_path', '/tmp');
session_start();
```

---

### **Website Lambat** 🐌

**Penyebab**: Banyak faktor

**Solusi**:
1. **Enable Caching** (jika hosting support)
2. **Optimize Database**:
   - Di phpMyAdmin, pilih database → tab **"Operations"** → **"Optimize table"**
3. **Compress File**:
   - Minify CSS dan JavaScript
4. **Gunakan CDN** untuk asset static (Bootstrap, Font Awesome)
5. **Upgrade Hosting** ke paket yang lebih tinggi

---

## 🔒 Keamanan untuk Hosting

### **1. Ganti Password Default** 🔐

**Setelah login pertama kali:**
1. Login sebagai admin
2. Ganti password default (`password`) dengan password yang kuat
3. Lakukan hal yang sama untuk user staff

### **2. Backup Rutin** 💾

**Setup Backup Otomatis** (jika hosting support):
1. Di cPanel, cari menu **"Backup"** atau **"Backup Wizard"**
2. Setup backup otomatis harian/mingguan
3. Simpan backup di tempat aman

**Atau Backup Manual**:
1. Export database secara rutin (minimal seminggu sekali)
2. Download folder `documents/uploads/` secara rutin
3. Simpan backup di komputer lokal atau cloud storage

### **3. Update File Secara Rutin** 🔄

1. **Monitor keamanan PHP**:
   - Pastikan versi PHP selalu update
   - Update di cPanel jika ada versi PHP baru

2. **Monitor aplikasi**:
   - Cek log error secara rutin
   - Update aplikasi jika ada bug fix

### **4. Gunakan HTTPS/SSL** 🔒

**Setup SSL Certificate** (Gratis):
1. Di cPanel, cari menu **"SSL/TLS"** atau **"Let's Encrypt"**
2. Install SSL certificate gratis
3. Aktifkan **"Force HTTPS Redirect"**
4. Update URL di aplikasi jika perlu

**Manfaat SSL**:
- Data terenkripsi saat transfer
- Lebih aman untuk login
- Meningkatkan kepercayaan user

---

## 📊 Monitoring & Maintenance

### **1. Monitor Log Error** 📝

1. Di cPanel, cari menu **"Error Log"** atau **"Logs"**
2. Cek error secara rutin
3. Fix error yang muncul

### **2. Monitor Disk Usage** 💾

1. Di cPanel, cek **"Disk Usage"**
2. Pastikan tidak melebihi limit
3. Hapus file yang tidak perlu
4. Archive file lama jika perlu

### **3. Monitor Bandwidth** 📈

1. Di cPanel, cek **"Bandwidth Usage"**
2. Pastikan tidak melebihi limit
3. Optimize aplikasi jika bandwidth tinggi

---

## 🎯 Checklist Deployment

Sebelum menganggap deployment selesai, pastikan:

- [ ] Database sudah dibuat di hosting
- [ ] User database sudah dibuat dan dihubungkan ke database
- [ ] Semua file sudah ter-upload ke `public_html`
- [ ] Database sudah di-import dari file SQL
- [ ] File `config/database.php` sudah di-update dengan info database hosting
- [ ] Permission folder `uploads/` dan `documents/uploads/` sudah di-set (755/777)
- [ ] Website bisa diakses via domain/subdomain
- [ ] Halaman landing muncul dengan benar
- [ ] Login berhasil dengan username/password default
- [ ] Upload file berhasil
- [ ] Download file berhasil
- [ ] SSL/HTTPS sudah diaktifkan (opsional tapi disarankan)
- [ ] Password default sudah diganti
- [ ] Backup sudah disiapkan

---

## 📞 Support

Jika mengalami masalah saat deployment:

1. **Cek dokumentasi hosting** dari provider
2. **Hubungi support hosting** (biasanya 24/7)
3. **Cek error log** di cPanel
4. **Cek file `.htaccess`** apakah ada konflik
5. **Test dengan file PHP sederhana** untuk memastikan PHP berjalan

---

## 🎉 Selamat!

Jika semua checklist sudah selesai, berarti aplikasi Anda sudah **ONLINE** dan bisa diakses dari mana saja! 🌐

**Tips Tambahan**:
- Bookmark URL aplikasi untuk akses cepat
- Simpan info login hosting di tempat aman
- Lakukan backup rutin
- Monitor aplikasi secara berkala

---

**Dibuat untuk**: Sistem Arsip Dokumen Kantor Imigrasi  
**Versi**: 1.0  
**Update Terakhir**: 2024











