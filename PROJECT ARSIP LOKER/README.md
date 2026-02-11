# Sistem Arsip Dokumen Kantor Imigrasi

Sistem manajemen arsip dokumen digital yang profesional untuk Kantor Imigrasi dengan fitur lengkap untuk admin dan staff.

## 🚀 Fitur Utama

### Untuk Admin:
- ✅ Dashboard lengkap dengan statistik dan grafik
- ✅ Manajemen dokumen (CRUD)
- ✅ Upload file dengan validasi
- ✅ Sistem pencarian dan filter dokumen
- ✅ Kategori dokumen
- ✅ Manajemen user (admin & staff)
- ✅ Log aktivitas sistem
- ✅ Laporan dan statistik
- ✅ Backup dan restore data

### Untuk Staff:
- ✅ Akses terbatas sesuai peran
- ✅ Upload dan lihat dokumen
- ✅ Pencarian dokumen
- ✅ Download dokumen

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP 7.4+ dengan PDO
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, HTML5, CSS3, JavaScript
- **Icons**: Font Awesome 6
- **Charts**: Chart.js
- **Security**: Password hashing, XSS protection, SQL injection prevention

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Ekstensi PHP: PDO, PDO_MySQL, GD, FileInfo
- Minimal 256MB RAM
- 1GB ruang disk untuk file upload

## 🔧 Instalasi

### 1. Clone atau Download Project
```bash
git clone [repository-url]
# atau download dan extract ke folder web server
```

## 📦 Memindahkan ke Laptop Lain

**Ya, aplikasi ini bisa dipindahkan ke laptop lain dan diakses via localhost!**

## 🚀 PANDUAN LENGKAP: Setup di Laptop Tujuan (Step-by-Step)

### **Langkah 1: Siapkan Folder Project** 📁

**Di Laptop Asal (Laptop Anda Sekarang):**
1. Buka File Explorer
2. Masuk ke folder: `C:\laragon\www\PROJECT ARSIP LOKER`
3. **Copy seluruh folder** `PROJECT ARSIP LOKER` (Ctrl+C)
4. Paste ke **Flashdisk/External Drive** atau **Google Drive** atau media transfer lainnya
   - Atau bisa zip folder tersebut dulu baru copy

### **Langkah 2: Install Laragon di Laptop Tujuan** 💻

**Di Laptop Tujuan (Laptop 1):**
1. Download Laragon dari: https://laragon.org/download/
2. Install Laragon (ikuti installer, biasanya di `C:\laragon`)
3. Setelah install, buka aplikasi **Laragon**
4. Pastikan Laragon sudah **running** (ikon hijau di system tray)
5. **Klik "Start All"** untuk menjalankan Apache & MySQL
   - Pastikan Apache & MySQL berwarna **hijau** (aktif)

### **Langkah 3: Copy Folder ke Laptop Tujuan** 📂

**Di Laptop Tujuan:**
1. Copy folder `PROJECT ARSIP LOKER` dari flashdisk/Google Drive
2. **Paste** folder tersebut ke: `C:\laragon\www\`
   - Jadi path lengkapnya: `C:\laragon\www\PROJECT ARSIP LOKER`
3. Pastikan struktur folder benar:
   ```
   C:\laragon\www\PROJECT ARSIP LOKER\
   ├── assets\
   ├── auth\
   ├── config\
   ├── documents\
   ├── includes\
   ├── index.php
   ├── landing.php
   └── ... (file lainnya)
   ```

### **Langkah 4: Import Database** 🗄️

**Di Laptop Tujuan:**
1. Buka browser (Chrome/Firefox/Edge)
2. Akses: `http://localhost/phpmyadmin`
3. Login phpMyAdmin:
   - **Username**: `root`
   - **Password**: (kosongkan, tekan Enter)
4. Setelah masuk phpMyAdmin:
   - Klik tab **"Impor"** atau **"Import"** di menu atas
   - Klik **"Choose File"** atau **"Pilih File"**
   - Pilih file: `C:\laragon\www\PROJECT ARSIP LOKER\config\init_database.sql`
   - Scroll ke bawah, klik tombol **"Go"** atau **"Kirim"**
5. Tunggu sampai muncul pesan: **"Import has been successfully finished"**
6. Cek di sidebar kiri, database `arsip_dokumen_imigrasi` sudah muncul
7. Klik database tersebut, pastikan tabel-tabel sudah ada:
   - `users`
   - `document_categories`
   - `documents`
   - `activity_logs`

### **Langkah 5: Cek Konfigurasi Database** ⚙️

**Di Laptop Tujuan:**
1. Buka File Explorer
2. Masuk ke: `C:\laragon\www\PROJECT ARSIP LOKER\config\`
3. Buka file `database.php` dengan Notepad atau text editor
4. Pastikan isinya seperti ini:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'arsip_dokumen_imigrasi');
   ```
5. **Jika password MySQL berbeda**, ubah `DB_PASS` sesuai password MySQL di laptop tujuan
6. **Save** file tersebut (Ctrl+S)

### **Langkah 6: Akses Web Aplikasi** 🌐

**Di Laptop Tujuan:**
1. Pastikan **Laragon masih running** (ikon hijau)
2. Pastikan **Apache & MySQL aktif** (hijau di Laragon)
3. Buka browser
4. Akses URL sesuai nama folder:
   
   **Jika nama folder: `PROJECT ARSIP LOKER`**
   - URL: `http://localhost/PROJECT%20ARSIP%20LOKER`
   - Atau: `http://localhost/PROJECT ARSIP LOKER`
   
   **Jika nama folder berbeda**, sesuaikan URL:
   - Contoh: folder `arsip-loker` → `http://localhost/arsip-loker`
   - Contoh: folder `arsip_loker` → `http://localhost/arsip_loker`

5. **Jika muncul halaman landing/login**, berarti **berhasil!** ✅
6. **Jika masih "Not Found"**, lihat troubleshooting di bawah

### **Langkah 7: Test Login** 🔐

**Di Laptop Tujuan:**
1. Setelah web terbuka, Anda akan melihat halaman landing
2. Pilih **"Login sebagai Admin"** atau **"Login sebagai Staff"**
3. Login dengan:
   - **Username**: `admin`
   - **Password**: `password`
4. Jika login berhasil, berarti semua setup sudah benar! ✅

---

## 🔧 Jika Masih "Not Found" - Troubleshooting

### **Cek 1: Lokasi Folder**
- Buka File Explorer → `C:\laragon\www\`
- Pastikan folder `PROJECT ARSIP LOKER` ada di sana
- **Catat nama folder yang sebenarnya** (mungkin ada spasi atau huruf berbeda)

### **Cek 2: Laragon Status**
- Buka aplikasi Laragon
- Pastikan **Apache** dan **MySQL** berwarna **hijau** (running)
- Jika merah, klik **"Start All"**

### **Cek 3: URL yang Benar**
- Cek nama folder di `C:\laragon\www\`
- Gunakan nama folder yang **persis sama** di URL
- Contoh:
  - Folder: `PROJECT ARSIP LOKER` → URL: `http://localhost/PROJECT%20ARSIP%20LOKER`
  - Folder: `PROJECT-ARSIP-LOKER` → URL: `http://localhost/PROJECT-ARSIP-LOKER`

### **Cek 4: phpMyAdmin**
- Coba akses: `http://localhost/phpmyadmin`
- Jika **bisa dibuka**, berarti Apache sudah jalan
- Jika **tidak bisa**, restart Laragon

### **Cek 5: File index.php**
- Pastikan file `index.php` ada di: `C:\laragon\www\PROJECT ARSIP LOKER\index.php`
- Jika tidak ada, copy ulang folder project

### **Cek 6: Database**
- Buka phpMyAdmin: `http://localhost/phpmyadmin`
- Cek apakah database `arsip_dokumen_imigrasi` ada
- Jika tidak ada, import ulang file `init_database.sql`

---

## 📝 Checklist Final

Sebelum menganggap setup selesai, pastikan:

- [ ] Laragon sudah terinstall di laptop tujuan
- [ ] Apache & MySQL running (hijau di Laragon)
- [ ] Folder project sudah di `C:\laragon\www\PROJECT ARSIP LOKER`
- [ ] Database sudah di-import (cek di phpMyAdmin)
- [ ] File `config/database.php` sudah dicek (username/password MySQL)
- [ ] URL di browser sudah sesuai nama folder
- [ ] Web bisa diakses dan muncul halaman landing/login
- [ ] Login dengan admin/password berhasil

## 🔄 Jika Web Masih Menampilkan Versi Lama

**Masalah**: Setelah copy folder, web masih menampilkan isi yang lama, bukan versi terbaru.

### **Solusi:**

#### **Langkah 1: Pastikan Copy File Terbaru** ✅
1. **Di Laptop Asal:**
   - Pastikan file-file sudah di-update dengan versi terbaru
   - Copy ulang seluruh folder `PROJECT ARSIP LOKER` ke flashdisk/Google Drive
   - Pastikan semua file ter-copy (bisa zip dulu untuk memastikan)

2. **Di Laptop Tujuan:**
   - **HAPUS folder lama** di `C:\laragon\www\PROJECT ARSIP LOKER`
   - Copy ulang folder dari flashdisk/Google Drive
   - Paste ke `C:\laragon\www\`

#### **Langkah 2: Clear Cache Browser** ✅
1. **Clear Browser Cache:**
   - Tekan `Ctrl + Shift + Delete`
   - Pilih "Cached images and files"
   - Pilih "All time"
   - Klik "Clear data"

2. **Atau Gunakan Incognito/Private Mode:**
   - Tekan `Ctrl + Shift + N` (Chrome) atau `Ctrl + Shift + P` (Firefox)
   - Akses web di mode incognito

3. **Hard Refresh:**
   - Tekan `Ctrl + F5` untuk hard refresh
   - Atau `Ctrl + Shift + R`

#### **Langkah 3: Cek Versi File** ✅
1. Akses: `http://localhost/PROJECT%20ARSIP%20LOKER/CHECK_VERSION.php`
2. Lihat tanggal "Modified" dari setiap file
3. Jika tanggal masih lama, berarti file belum ter-update
4. Copy ulang folder project

#### **Langkah 4: Restart Laragon** ✅
1. **Stop Laragon** (klik Stop All)
2. **Tunggu 5 detik**
3. **Start Laragon** (klik Start All)
4. Coba akses web lagi

#### **Langkah 5: Cek Folder yang Benar** ✅
1. Pastikan hanya ada **1 folder** `PROJECT ARSIP LOKER` di `C:\laragon\www\`
2. Jika ada folder lain yang mirip, hapus yang lama
3. Pastikan folder yang digunakan adalah yang terbaru

#### **Langkah 6: Cek Database** ✅
1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Pastikan database `arsip_dokumen_imigrasi` ada
3. Jika ada database lain yang mirip, pastikan tidak salah pakai

### **Quick Fix (Jika Masih Bingung):**
1. **Hapus semua** di `C:\laragon\www\PROJECT ARSIP LOKER`
2. **Copy ulang** folder project dari laptop asal
3. **Restart Laragon**
4. **Clear cache browser** (Ctrl + Shift + Delete)
5. **Hard refresh** (Ctrl + F5)
6. Coba akses lagi

---

### Cara Memindahkan (Ringkas):
1. **Copy folder project** ke laptop tujuan
2. **Install Laragon** (atau XAMPP/WAMP) di laptop tujuan
3. **Pindahkan folder** ke direktori web server:
   - Laragon: `C:\laragon\www\PROJECT ARSIP LOKER`
   - XAMPP: `C:\xampp\htdocs\PROJECT ARSIP LOKER`
   - WAMP: `C:\wamp64\www\PROJECT ARSIP LOKER`
4. **Jalankan Laragon/XAMPP/WAMP** (pastikan Apache & MySQL aktif)
5. **Import database** (lihat panduan lengkap di bagian "Setup Database" di bawah):
   - Buka phpMyAdmin: `http://localhost/phpmyadmin`
   - Klik tab **"Import"** atau **"SQL"**
   - Pilih file: `config/init_database.sql`
   - Klik **"Go"**
   - Database akan otomatis dibuat dengan nama `arsip_dokumen_imigrasi`
6. **Cek konfigurasi database** di `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');        // Sesuaikan jika perlu
   define('DB_PASS', '');            // Sesuaikan jika perlu
   define('DB_NAME', 'arsip_dokumen_imigrasi');
   ```
7. **Akses aplikasi**: `http://localhost/PROJECT%20ARSIP%20LOKER`
   - Atau sesuaikan dengan nama folder di laptop tujuan

### Catatan Penting:
- ✅ **Tidak ada path hardcoded** - aplikasi menggunakan relative paths
- ✅ **Database host `localhost`** - akan bekerja di laptop manapun
- ✅ **File upload tetap aman** - folder `uploads/` dan `documents/uploads/` akan ikut ter-copy
- ⚠️ **Pastikan MySQL password sama** atau ubah di `config/database.php`
- ⚠️ **Nama folder bisa berbeda**, sesuaikan URL di browser

### 2. Setup Database

#### Cara Import Database (Pilih salah satu metode):

##### **Metode 1: Menggunakan phpMyAdmin (Paling Mudah)** ⭐
1. Buka browser dan akses: `http://localhost/phpmyadmin`
2. Login dengan:
   - **Username**: `root`
   - **Password**: (kosongkan jika tidak ada password)
3. Klik tab **"SQL"** atau **"Import"** di menu atas
4. Jika menggunakan tab **"SQL"**:
   - Klik **"Choose File"** atau **"Pilih File"**
   - Pilih file: `config/init_database.sql`
   - Klik **"Go"** atau **"Kirim"**
5. Jika menggunakan tab **"Import"**:
   - Klik **"Choose File"** → pilih `config/init_database.sql`
   - Scroll ke bawah, klik **"Go"**
6. Tunggu sampai muncul pesan **"Import has been successfully finished"**
7. Cek database sudah muncul di sidebar kiri: `arsip_dokumen_imigrasi`

##### **Metode 2: Menggunakan Command Line (MySQL CLI)**
1. Buka **Command Prompt** atau **PowerShell**
2. Masuk ke folder MySQL bin:
   ```bash
   cd C:\laragon\bin\mysql\mysql-8.0.30\bin
   # atau sesuaikan versi MySQL Anda
   ```
3. Login ke MySQL:
   ```bash
   mysql -u root -p
   # Tekan Enter jika tidak ada password
   ```
4. Import database:
   ```bash
   source C:\laragon\www\PROJECT ARSIP LOKER\config\init_database.sql
   ```
   Atau gunakan perintah:
   ```bash
   mysql -u root -p < "C:\laragon\www\PROJECT ARSIP LOKER\config\init_database.sql"
   ```
5. Verifikasi database:
   ```sql
   SHOW DATABASES;
   USE arsip_dokumen_imigrasi;
   SHOW TABLES;
   ```

##### **Metode 3: Menggunakan HeidiSQL (Laragon)**
1. Buka **HeidiSQL** dari Laragon menu
2. Koneksi ke MySQL (default: `localhost`, user: `root`, password: kosong)
3. Klik kanan di database → **"Execute SQL file"**
4. Pilih file: `config/init_database.sql`
5. Klik **"Execute"** atau tekan **F9**
6. Tunggu sampai selesai

##### **Metode 4: Copy-Paste SQL Manual**
1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Klik tab **"SQL"**
3. Buka file `config/init_database.sql` dengan text editor (Notepad++)
4. **Copy semua isi file** (Ctrl+A, Ctrl+C)
5. **Paste** ke kotak SQL di phpMyAdmin
6. Klik **"Go"**

#### Setelah Import:
- Database `arsip_dokumen_imigrasi` akan otomatis dibuat
- Tabel-tabel akan dibuat lengkap dengan data default
- User default yang tersedia:
  - **Admin**: username `admin`, password `password`
  - **Staff**: username `staff`, password `password`

**⚠️ PENTING**: Ganti password default setelah login pertama kali!

#### Cara Export Database (Untuk Backup Data di Laptop Asal)

**Export dilakukan di laptop asal** untuk menyimpan semua data yang sudah ada sebelum pindah ke laptop tujuan.

##### **Metode 1: Menggunakan phpMyAdmin (Paling Mudah)** ⭐
1. Buka browser dan akses: `http://localhost/phpmyadmin`
2. Login dengan:
   - **Username**: `root`
   - **Password**: (kosongkan jika tidak ada password)
3. Di sidebar kiri, **klik database** `arsip_dokumen_imigrasi`
4. Klik tab **"Ekspor"** atau **"Export"** di menu atas
5. Pilih metode export:
   - **Quick**: Export cepat (default)
   - **Custom**: Export dengan opsi lebih detail (disarankan)
6. Jika pilih **Custom**, pastikan:
   - Format: **SQL**
   - Centang **"Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER statement"**
   - Centang **"Add CREATE PROCEDURE / FUNCTION / EVENT / TRIGGER statement"**
   - Centang **"Add CREATE TABLE statement"**
   - Centang **"Add INSERT statement"**
   - Pilih **"None"** untuk Compression (atau zip jika file besar)
7. Klik tombol **"Go"** atau **"Kirim"**
8. File SQL akan terdownload dengan nama seperti `arsip_dokumen_imigrasi.sql`
9. **Simpan file ini** bersama folder project untuk dipindahkan ke laptop tujuan

##### **Metode 2: Menggunakan Command Line (MySQL CLI)**
1. Buka **Command Prompt** atau **PowerShell**
2. Masuk ke folder MySQL bin:
   ```bash
   cd C:\laragon\bin\mysql\mysql-8.0.30\bin
   ```
3. Export database:
   ```bash
   mysqldump -u root -p arsip_dokumen_imigrasi > "C:\laragon\www\PROJECT ARSIP LOKER\backup_database.sql"
   ```
   - Tekan Enter jika tidak ada password
   - File akan tersimpan di folder project

##### **Metode 3: Menggunakan HeidiSQL (Laragon)**
1. Buka **HeidiSQL** dari Laragon menu
2. Koneksi ke MySQL (default: `localhost`, user: `root`, password: kosong)
3. Klik kanan database `arsip_dokumen_imigrasi`
4. Pilih **"Export database as SQL"**
5. Pilih lokasi penyimpanan (simpan di folder project)
6. Klik **"Save"**

#### Setelah Export:
- File SQL akan berisi semua data: tabel, data, struktur, dll
- File ini bisa dibawa ke laptop tujuan
- Import file ini di laptop tujuan untuk mendapatkan semua data yang sama

### 3. Konfigurasi
Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'arsip_dokumen_imigrasi');
```

### 4. Set Permissions
```bash
chmod 755 uploads/
chmod 644 config/database.php
```

### 5. Akses Sistem
- Buka browser dan akses: `http://localhost/PROJECT-ARSIP-LOKER`
- Login dengan:
  - **Username**: `admin`
  - **Password**: `password` (default)

## 📁 Struktur Folder

```
PROJECT-ARSIP-LOKER/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── script.js
│       └── dashboard.js
├── auth/
│   ├── login.php
│   └── logout.php
├── config/
│   ├── database.php
│   └── init_database.sql
├── documents/
│   ├── add.php
│   ├── index.php
│   ├── view.php
│   ├── download.php
│   └── delete.php
├── includes/
│   ├── navbar.php
│   ├── sidebar.php
│   └── functions.php
├── uploads/
│   └── (folder untuk file upload)
├── index.php
├── dashboard.php
└── README.md
```

## 🔐 Keamanan

- Password di-hash menggunakan `password_hash()`
- Perlindungan XSS dengan `htmlspecialchars()`
- Prepared statements untuk mencegah SQL injection
- Validasi file upload
- Session management yang aman
- Log aktivitas untuk audit trail

## 📊 Fitur Dashboard

- **Statistik Real-time**: Total dokumen, dokumen hari ini, kategori, user
- **Grafik**: Statistik bulanan dan distribusi per kategori
- **Dokumen Terbaru**: 10 dokumen terakhir yang diupload
- **Aktivitas Terbaru**: Log aktivitas user terbaru
- **Responsive Design**: Tampilan optimal di desktop dan mobile

## 🔍 Pencarian dan Filter

- Pencarian berdasarkan judul, nomor dokumen, atau deskripsi
- Filter berdasarkan kategori dan status
- Sorting berdasarkan tanggal, judul, atau nomor dokumen
- Pagination untuk performa optimal

## 📤 Upload File

- **Format yang Didukung**: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF
- **Ukuran Maksimal**: 10MB per file
- **Validasi**: Tipe file dan ukuran
- **Preview**: Preview gambar langsung
- **Auto-rename**: Nama file otomatis untuk mencegah konflik

## 👥 Manajemen User

### Admin:
- Akses penuh ke semua fitur
- Manajemen user dan kategori
- Log aktivitas dan laporan
- Backup dan restore

### Staff:
- Upload dan download dokumen
- Pencarian dokumen
- Akses terbatas sesuai peran

## 📈 Laporan dan Statistik

- Grafik dokumen per bulan
- Distribusi dokumen per kategori
- Statistik user dan aktivitas
- Export data ke Excel/CSV
- Log aktivitas lengkap

## 🔧 Konfigurasi Tambahan

### Upload Settings
Edit di `includes/functions.php`:
```php
// Ukuran maksimal file (dalam bytes)
if ($file['size'] > 10 * 1024 * 1024) { // 10MB
```

### Allowed File Types
```php
$allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
```

## 🐛 Troubleshooting

### Error "Not Found" / "404 Not Found" ⚠️

**Masalah**: Setelah import database, web tidak bisa dibuka dan muncul error "Not Found"

#### **Penyebab & Solusi:**

1. **Cek Lokasi Folder** ✅
   - Pastikan folder project ada di: `C:\laragon\www\PROJECT ARSIP LOKER`
   - Jika nama folder berbeda, sesuaikan URL di browser
   - Cek apakah folder benar-benar ada di direktori www Laragon

2. **Cek Apache Service** ✅
   - Pastikan **Laragon** sudah running (ikon hijau)
   - Pastikan **Apache** aktif (bisa cek di Laragon menu)
   - Restart Laragon jika perlu: Stop → Start

3. **Cek URL yang Benar** ✅
   - URL harus sesuai dengan **nama folder** yang ada
   - Jika nama folder: `PROJECT ARSIP LOKER`
     - Gunakan: `http://localhost/PROJECT%20ARSIP%20LOKER`
     - Atau: `http://localhost/PROJECT ARSIP LOKER`
   - Jika nama folder berbeda, sesuaikan URL
   - **Cara cek nama folder**: Buka `C:\laragon\www\` dan lihat nama foldernya

4. **Cek File index.php** ✅
   - Pastikan file `index.php` ada di folder root project
   - File `index.php` akan redirect ke `landing.php`

5. **Coba URL Langsung ke Landing Page** ✅
   - Coba akses langsung: `http://localhost/PROJECT%20ARSIP%20LOKER/landing.php`
   - Atau: `http://localhost/PROJECT%20ARSIP%20LOKER/index.php`

6. **Cek phpMyAdmin Bisa Diakses** ✅
   - Coba akses: `http://localhost/phpmyadmin`
   - Jika phpMyAdmin bisa, berarti Apache sudah jalan
   - Jika tidak bisa, restart Laragon

7. **Cek .htaccess** ✅
   - File `.htaccess` sudah ada di root folder
   - Jika error 500, mungkin `.htaccess` bermasalah
   - Coba rename `.htaccess` menjadi `.htaccess.bak` untuk test

8. **Cek Database Connection** ✅
   - Pastikan database sudah di-import dengan benar
   - Cek di phpMyAdmin apakah database `arsip_dokumen_imigrasi` sudah ada
   - Cek file `config/database.php` apakah konfigurasinya benar

#### **Langkah Troubleshooting Lengkap (Step-by-Step):**

**Langkah 1: Cek Lokasi Folder**
```
1. Buka File Explorer
2. Masuk ke: C:\laragon\www\
3. Cek apakah folder "PROJECT ARSIP LOKER" ada di sana
4. CATAT NAMA FOLDER YANG SEBENARNYA (huruf besar/kecil, spasi, dll)
```

**Langkah 2: Cek Laragon Status**
```
1. Buka aplikasi Laragon
2. Pastikan Apache & MySQL berwarna HIJAU (running)
3. Jika MERAH, klik "Start All"
4. Tunggu sampai Apache & MySQL hijau
```

**Langkah 3: Test Apache dengan File Test**
```
1. Buka browser
2. Akses: http://localhost/[NAMA_FOLDER]/test_apache.php
   - Ganti [NAMA_FOLDER] dengan nama folder yang sebenarnya
   - Contoh: http://localhost/PROJECT%20ARSIP%20LOKER/test_apache.php
3. Jika file test.php muncul, berarti Apache sudah jalan ✅
4. Jika "Not Found", berarti nama folder di URL salah
```

**Langkah 4: Cek URL yang Benar**
```
1. Lihat nama folder di C:\laragon\www\
2. Gunakan nama folder yang PERSIS SAMA di browser
3. Jika nama folder: "PROJECT ARSIP LOKER"
   → URL: http://localhost/PROJECT%20ARSIP%20LOKER
4. Jika nama folder: "PROJECT-ARSIP-LOKER"
   → URL: http://localhost/PROJECT-ARSIP-LOKER
5. Jika nama folder: "arsip_loker"
   → URL: http://localhost/arsip_loker
```

**Langkah 5: Coba URL Langsung**
```
1. Coba akses: http://localhost/[NAMA_FOLDER]/landing.php
2. Coba akses: http://localhost/[NAMA_FOLDER]/index.php
3. Jika salah satu bisa, berarti setup sudah benar
```

**Langkah 6: Cek phpMyAdmin**
```
1. Coba akses: http://localhost/phpmyadmin
2. Jika phpMyAdmin bisa dibuka = Apache sudah jalan ✅
3. Jika tidak bisa = restart Laragon
```

#### **Contoh URL yang Benar:**
- Folder: `PROJECT ARSIP LOKER` → URL: `http://localhost/PROJECT%20ARSIP%20LOKER`
- Folder: `PROJECT-ARSIP-LOKER` → URL: `http://localhost/PROJECT-ARSIP-LOKER`
- Folder: `arsip_loker` → URL: `http://localhost/arsip_loker`

### Error Database Connection
- Pastikan MySQL service berjalan
- Cek konfigurasi database di `config/database.php`
- Pastikan database sudah dibuat dan di-import
- Cek username/password MySQL di `config/database.php` sesuai dengan laptop tujuan

### Error Upload File
- Cek permission folder `uploads/`
- Pastikan `upload_max_filesize` dan `post_max_size` di php.ini cukup besar
- Cek ekstensi PHP yang diperlukan

### Error Login
- Pastikan username dan password benar
- Cek apakah user status aktif
- Pastikan session PHP berjalan

## 📞 Support

Untuk bantuan teknis atau pertanyaan:
- Email: support@imigrasi.go.id
- Phone: (021) 1234-5678
- Dokumentasi lengkap tersedia di folder `docs/`

## 📝 Changelog

### v1.0.0 (2024-01-01)
- ✅ Release awal dengan fitur admin lengkap
- ✅ Sistem upload dan manajemen dokumen
- ✅ Dashboard dengan statistik
- ✅ Pencarian dan filter
- ✅ Log aktivitas
- ✅ Responsive design

## 🔄 Update Mendatang

- [ ] Fitur staff dashboard
- [ ] Notifikasi real-time
- [ ] Backup otomatis
- [ ] API untuk integrasi
- [ ] Mobile app
- [ ] Multi-language support

## 📄 Lisensi

Sistem ini dikembangkan untuk Kantor Imigrasi Republik Indonesia.
© 2024 Kantor Imigrasi. Semua hak dilindungi.

---

**Catatan**: Pastikan untuk mengubah password default setelah instalasi pertama kali untuk keamanan sistem.
