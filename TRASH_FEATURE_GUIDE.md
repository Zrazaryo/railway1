# 🗑️ Panduan Fitur Menu Sampah (Trash Bin)

## Ringkasan
Fitur Menu Sampah memungkinkan dokumen yang dihapus disimpan sementara selama **30 hari** sebelum dihapus secara permanen. Admin dapat memulihkan dokumen atau menghapusnya secara permanen sesuai kebutuhan.

---

## 📋 Daftar Perubahan Implementasi

### 1. **Tabel Database Baru**
Dua tabel baru telah dibuat untuk mendukung fitur sampah:

#### `document_trash`
Menyimpan dokumen yang dihapus dengan detail lengkap:
```sql
CREATE TABLE document_trash (
    id INT PRIMARY KEY AUTO_INCREMENT,
    original_document_id INT,              -- ID dokumen asli dari tabel documents
    title VARCHAR(255),                    -- Judul dokumen
    full_name VARCHAR(255),                -- Nama lengkap pemilik
    nik VARCHAR(16),                       -- NIK
    passport_number VARCHAR(20),           -- Nomor paspor
    document_number VARCHAR(50),           -- Nomor dokumen
    document_year INT,                     -- Tahun dokumen
    month_number VARCHAR(20),              -- Bulan/Lemari
    locker_code VARCHAR(10),               -- Kode lemari
    locker_name VARCHAR(100),              -- Nama lemari
    citizen_category VARCHAR(100),         -- Kategori warga negara
    document_origin VARCHAR(50),           -- Asal dokumen
    file_path VARCHAR(500),                -- Path file
    description TEXT,                      -- Deskripsi
    deleted_at TIMESTAMP,                  -- Waktu dihapus
    deleted_by INT,                        -- ID user yang menghapus
    restore_deadline DATETIME,             -- Batas waktu restore (30 hari)
    document_data LONGTEXT,                -- Data JSON dokumen
    is_restored TINYINT DEFAULT 0,         -- Status restore
    restored_at TIMESTAMP NULL,            -- Waktu di-restore
    restored_by INT,                       -- ID user yang restore
    status VARCHAR(20) DEFAULT 'in_trash', -- Status: in_trash, restored, permanently_deleted
    
    KEY idx_deleted_at (deleted_at),
    KEY idx_restore_deadline (restore_deadline),
    KEY idx_original_document_id (original_document_id),
    KEY idx_status (status)
)
```

#### `trash_audit_logs`
Mencatat setiap operasi pada dokumen sampah untuk audit trail:
```sql
CREATE TABLE trash_audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_trash_id INT NOT NULL,
    action VARCHAR(50),                    -- moved_to_trash, restored, permanently_deleted
    user_id INT,                          -- ID user yang melakukan aksi
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,                           -- Catatan tambahan
    
    FOREIGN KEY (document_trash_id) REFERENCES document_trash(id)
)
```

---

## 🚀 Fitur Menu Sampah

### 1. **Penyimpanan Dokumen ke Sampah**
Ketika dokumen **aktif** atau **pemusnahan** dihapus, dokumen akan:
- ✓ Dipindahkan ke tabel `document_trash` (bukan dihapus permanen)
- ✓ Status dokumen di tabel `documents` menjadi `'trashed'`
- ✓ Menyimpan deadline restore 30 hari ke depan
- ✓ Mencatat user ID yang menghapus
- ✓ Membuat entry di `trash_audit_logs`

**File yang dimodifikasi:**
- `documents/delete.php` - Hapus dokumen aktif
- `documents/delete_all.php` - Hapus semua dokumen aktif
- `documents/delete_all_pemusnahan.php` - Hapus semua dokumen pemusnahan

### 2. **Halaman Menu Sampah** (`documents/trash.php`)
Halaman dengan fitur lengkap untuk mengelola sampah:

#### Fitur-fitur:
- **📊 Statistik** - Jumlah total dokumen di sampah
- **🔍 Pencarian** - Cari berdasarkan nama, NIK, atau nomor paspor
- **📅 Sorting** - Urutkan berdasarkan waktu dihapus atau nama
- **⏱️ Countdown** - Tampilkan sisa hari sebelum dihapus otomatis
- **🔄 Restore** - Pulihkan dokumen kembali ke status aktif (1 dokumen)
- **🗑️ Permanent Delete** - Hapus dokumen secara permanen (tidak bisa di-restore lagi)
- **✅ Bulk Actions** - Hapus beberapa dokumen sekaligus
- **⚠️ Warning Visual** - Indikator warna untuk sisa hari:
  - 🔴 **Merah** (<=3 hari): Critical - akan segera dihapus otomatis
  - 🟡 **Kuning** (4-7 hari): Warning
  - 🔵 **Biru** (>7 hari): Safe

#### Status Dokumen Saat Restore:
Ketika dokumen di-restore:
- Status dokumen kembali menjadi `'active'`
- Dokumen kembali tersedia di menu "Dokumen Keseluruhan"
- Tercatat siapa yang melakukan restore dan kapan
- Perpustakaan tetap sama (tidak berubah)

### 3. **Menu di Sidebar**
Menu "Menu Sampah" telah ditambahkan ke sidebar admin dan staff, berada setelah "Lemari Pemusnahan":
```
📋 Menu Admin
├─ Dashboard
├─ Dokumen Keseluruhan
├─ Lemari Dokumen
├─ Lemari Pemusnahan
├─ 🗑️ Menu Sampah          ← Baru!
├─ Tambah Dokumen
├─ Manajemen User
├─ Laporan
├─ Log Aktivitas
└─ Pengaturan
```

### 4. **Auto-cleanup Dokumen >30 Hari** (`cleanup_trash.php`)
Script otomatis yang menghapus dokumen dari sampah yang sudah melampaui 30 hari:

#### Cara Menggunakan:

**Manual (dari browser):**
```
http://localhost/PROJECT ARSIP LOKER/cleanup_trash.php
```
Hanya admin yang login yang bisa menjalankan.

**Via CRON Job (otomatis harian):**
Tambahkan ke cron jobs server:
```bash
# Jalankan cleanup setiap hari pukul 02:00
0 2 * * * curl http://localhost/PROJECT ARSIP LOKER/cleanup_trash.php

# Atau dengan token keamanan (optional):
0 2 * * * curl "http://localhost/PROJECT ARSIP LOKER/cleanup_trash.php?token=YOUR_SECRET_TOKEN"
```

#### Fitur Cleanup:
- ✓ Mencari dokumen dengan `restore_deadline < NOW()`
- ✓ Menghapus file fisik dari server
- ✓ Menghapus dokumen dari tabel `documents`
- ✓ Update status di `document_trash` menjadi `permanently_deleted`
- ✓ Log ke `trash_audit_logs`
- ✓ Log aktivitas user
- ✓ Report hasil cleanup

---

## 📋 Workflow Lengkap

### Skenario 1: Hapus Dokumen Aktif dan Kemudian Restore
```
1. Admin menghapus dokumen dari "Dokumen Keseluruhan"
   ↓
2. Dokumen dipindahkan ke sampah (tidak dihapus langsung)
   - Status di documents: 'trashed'
   - Entry baru di document_trash
   ↓
3. Admin akses "Menu Sampah"
   ↓
4. Admin lihat dokumen dengan countdown 30 hari
   ↓
5. Admin klik "Pulihkan"
   - Dokumen kembali ke status 'active'
   - Muncul di "Dokumen Keseluruhan" lagi
   - Tercatat waktu dan user restore
```

### Skenario 2: Hapus Permanen dari Sampah
```
1. Admin lihat dokumen di Menu Sampah
   ↓
2. Admin klik "Hapus" atau pilih bulk delete
   ↓
3. Confirm dialog muncul
   ↓
4. Admin klik "Hapus Permanen"
   - File dihapus dari server
   - Dokumen dihapus dari tabel documents
   - Status di trash menjadi 'permanently_deleted'
   - Tidak bisa di-restore lagi
```

### Skenario 3: Auto-cleanup >30 Hari
```
1. Cleanup script dijalankan (manual atau cron)
   ↓
2. Script cari dokumen dengan restore_deadline < NOW()
   ↓
3. Untuk setiap dokumen yang expired:
   - Hapus file
   - Hapus dari documents table
   - Update status ke permanently_deleted
   ↓
4. Generate report hasil cleanup
```

---

## 🛠️ Setup & Instalasi

### Step 1: Buat Tabel Database
Jalankan script setup:
```
http://localhost/PROJECT ARSIP LOKER/setup_trash_table.php
```

Atau jalankan SQL langsung:
```sql
-- File: create_trash_table.sql
-- Copy dan jalankan SQL dari file ini di MySQL
```

### Step 2: Verifikasi Tabel
```sql
SHOW TABLES LIKE 'document_trash%';
-- Harusnya ada 2 tabel:
-- - document_trash
-- - trash_audit_logs
```

### Step 3: Test Fitur
1. Login sebagai admin
2. Hapus satu dokumen dari "Dokumen Keseluruhan"
3. Cek di "Menu Sampah" - dokumen harus ada
4. Coba restore - dokumen harus kembali ke aktif
5. Hapus lagi, kali ini permanent delete

---

## 📊 Database Schema Reference

### Diagram Relasi
```
documents                    document_trash
│                           │
├─ id (PK) ────────────────┼─ original_document_id (FK)
├─ title                    ├─ id (PK)
├─ full_name                ├─ title
├─ nik                       ├─ full_name
├─ passport_number          ├─ nik
├─ document_number          ├─ passport_number
├─ status ('active',        ├─ document_number
│  'trashed',               ├─ deleted_at
│  'deleted')               ├─ deleted_by (FK -> users.id)
└─ ...                      ├─ restore_deadline
                            ├─ status ('in_trash',
                            │  'restored',
                            │  'permanently_deleted')
                            └─ ...
                                    ↓
                            trash_audit_logs
                            ├─ document_trash_id (FK)
                            ├─ action
                            ├─ user_id (FK)
                            ├─ action_time
                            └─ notes
```

---

## 🔐 Security & Best Practices

### Keamanan:
1. ✓ Hanya admin yang bisa akses Menu Sampah
2. ✓ Permanent delete memerlukan konfirmasi dialog
3. ✓ Semua operasi tercatat di audit log
4. ✓ Cleanup script bisa di-protect dengan token

### Best Practices:
1. **Backup Regular** - Backup database secara teratur
2. **Monitor Trash** - Cek Menu Sampah secara berkala
3. **Set Cleanup Schedule** - Setup cron job untuk cleanup otomatis
4. **Review Audit Logs** - Monitor trash_audit_logs untuk aktivitas mencurigakan

---

## 📝 Customization

### Mengubah Durasi Sampah (30 hari)
Edit di file `documents/delete.php`, `delete_all.php`, `delete_all_pemusnahan.php`:
```php
// Current: 30 hari
$restore_deadline = date('Y-m-d H:i:s', strtotime('+30 days'));

// Ubah ke 14 hari:
$restore_deadline = date('Y-m-d H:i:s', strtotime('+14 days'));

// Ubah ke 60 hari:
$restore_deadline = date('Y-m-d H:i:s', strtotime('+60 days'));
```

### Mengubah Indikator Warna Warning
Edit di `documents/trash.php` dalam fungsi `days_remaining()`:
```php
$days_class = $days <= 3 ? 'days-critical' : 
              ($days <= 7 ? 'days-warning-color' : 'days-safe');

// Ubah ke 5 hari critical dan 10 hari warning:
$days_class = $days <= 5 ? 'days-critical' : 
              ($days <= 10 ? 'days-warning-color' : 'days-safe');
```

### Setup Cleanup Token (Optional)
Untuk keamanan extra, edit `cleanup_trash.php`:
```php
// Tambahkan di config/database.php atau file config:
define('CLEANUP_TOKEN', 'your_secret_token_here');

// Kemudian jalankan:
http://localhost/PROJECT ARSIP LOKER/cleanup_trash.php?token=your_secret_token_here
```

---

## 📊 Log Activity

Semua operasi sampah dicatat di tabel `activity_logs`:
```sql
-- Contoh log entries:
SELECT * FROM activity_logs 
WHERE action IN ('MOVE_TO_TRASH', 'RESTORE_DOCUMENT', 'PERMANENT_DELETE', 'TRASH_CLEANUP')
ORDER BY created_at DESC;
```

Dan juga di `trash_audit_logs` untuk detail lebih:
```sql
-- Audit trail sampah:
SELECT dal.*, dt.full_name, u.full_name as user_name
FROM trash_audit_logs dal
JOIN document_trash dt ON dal.document_trash_id = dt.id
LEFT JOIN users u ON dal.user_id = u.id
ORDER BY dal.action_time DESC;
```

---

## ❓ Troubleshooting

### Q: Dokumen tidak muncul di Menu Sampah setelah dihapus
**A:** 
- Pastikan tabel `document_trash` sudah dibuat
- Jalankan `setup_trash_table.php`
- Periksa error log PHP

### Q: Permanent delete tidak bekerja
**A:**
- Periksa permissions folder `documents/uploads/`
- Pastikan file masih ada di server
- Cek error log PHP

### Q: Cleanup script error
**A:**
- Pastikan dijalankan sebagai admin (untuk web access)
- Cek permissions folder
- Periksa database connection

### Q: Dokumen tidak bisa di-restore
**A:**
- Pastikan status dokumen di documents table adalah 'trashed'
- Periksa deadline belum expired (compare dengan NOW())
- Restart browser/session

---

## 📞 Support
Untuk bantuan lebih lanjut, silahkan hubungi tim development atau cek file ini di:
- `/PROJECT ARSIP LOKER/TRASH_FEATURE_GUIDE.md`

---

**Last Updated:** 2024
**Version:** 1.0
**Status:** ✅ Production Ready
