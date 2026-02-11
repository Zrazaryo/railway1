# Menu Sampah - Complete Implementation Guide

## Overview
Menu Sampah adalah fitur soft-delete untuk dokumentasi yang memungkinkan:
- Menyimpan dokumen yang dihapus sementara selama 30 hari
- Memulihkan dokumen kembali ke status aktif
- Menghapus permanen dokumen setelah 30 hari atau sesuai kebutuhan

## Quick Start

### 1. Setup Database Tables
Akses halaman setup untuk membuat tabel database:
```
http://localhost/PROJECT%20ARSIP%20LOKER/documents/setup_trash.php
```

**Atau** jalankan script SQL berikut di MySQL:
```sql
-- Jalankan dari file: create_trash_tables.sql
```

### 2. Verify Menu di Sidebar
- Login sebagai Admin
- Lihat sidebar di sebelah kiri
- Seharusnya ada menu baru "Menu Sampah" di bawah "Lemari Pemusnahan"

### 3. Mulai Gunakan
- Hapus dokumen dari "Dokumen Keseluruhan" atau "Lemari Pemusnahan"
- Dokumen akan masuk ke Menu Sampah
- Tunggu 30 hari atau klik "Restore" untuk memulihkan

## File Structure

### Backend Files
```
documents/
├── trash.php                    # Halaman utama Menu Sampah
├── setup_trash.php              # Script untuk membuat tabel
├── delete.php                   # Hapus dokumen aktif (move to trash)
├── delete_all.php               # Hapus semua dokumen aktif
├── delete_all_pemusnahan.php    # Hapus dokumen di lemari pemusnahan

includes/
├── trash_helper.php             # Helper untuk auto-create tabel
├── functions.php                # (Updated) dengan trash functions
├── sidebar.php                  # (Updated) dengan menu sampah

platform/
├── documents.php                # (Updated) hapus = move to trash

config/
├── database.php                 # Database connection class
```

### Database Tables
```
document_trash
├── id (Primary Key)
├── original_document_id
├── title, full_name, nik, passport_number
├── document_number, document_year
├── month_number, locker_code, locker_name
├── citizen_category, document_origin
├── file_path, description
├── deleted_at, deleted_by
├── restore_deadline (30 hari dari deleted_at)
├── status (in_trash, restored, permanently_deleted)
└── ... (restore info fields)

trash_audit_logs
├── id
├── document_trash_id
├── action (moved_to_trash, restored, permanently_deleted)
├── user_id, action_time, notes
```

## API Endpoints

### GET /documents/trash.php
Tampilkan daftar dokumen di sampah dengan:
- Pagination (default: 15 items per page)
- Search (nama, NIK, nomor paspor, nomor dokumen)
- Sort (deleted_at, full_name)

**Parameters:**
- `page`: Halaman (default: 1)
- `search`: Keyword pencarian
- `sort`: Tipe sorting (deleted_at_desc, deleted_at_asc, full_name_asc, full_name_desc)

**Example:**
```
/documents/trash.php?page=1&search=budi&sort=deleted_at_desc
```

### POST /documents/trash.php
Handle action restore/permanent_delete:
```php
// Data POST
$_POST['action'] = 'restore' atau 'permanent_delete'
$_POST['trash_id'] = ID dari document_trash
```

### POST /documents/delete.php
Hapus dokumen aktif (AJAX JSON request):
```javascript
fetch('delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: document_id })
})
```

## Features

### 1. Soft Delete
**Ketika dokumen dihapus:**
- Data disimpan di `document_trash`
- Status dokumen di `documents` diubah menjadi `'trashed'`
- User dapat melihat countdown 30 hari

**Contoh Flow:**
```
User klik Delete → DELETE button → delete.php
    ↓
Cek dokumen aktif
    ↓
INSERT INTO document_trash
    ↓
UPDATE documents SET status = 'trashed'
    ↓
Log activity
    ↓
Redirect to trash page
```

### 2. Restore Document
**Ketika dokumen di-restore:**
- Status dokumen kembali ke `'active'`
- `document_trash` record diupdate: `status = 'restored'`
- Dokumen hilang dari Menu Sampah

**SQL:**
```sql
UPDATE documents SET status = 'active' WHERE id = ?
UPDATE document_trash SET status = 'restored', 
       restored_at = NOW(), restored_by = ? WHERE id = ?
```

### 3. Permanent Delete
**Ketika dokumen dihapus permanen:**
- Delete dari `documents` table
- Delete dari `document_trash` table
- Delete file fisik jika ada
- Tidak bisa di-restore lagi

### 4. Auto Cleanup
**Setelah 30 hari:**
- Dokumen dapat dihapus permanen
- Atau manual delete via Admin
- Countdown visual di interface

### 5. Audit Logging
**Setiap action dicatat:**
- Siapa yang menghapus
- Kapan dihapus
- Kapan di-restore
- Kapan dihapus permanen

## Security Features

### 1. Access Control
- Hanya **Admin** yang dapat mengakses Menu Sampah
- Staff tidak bisa lihat Menu Sampah
- `require_admin()` check di setiap endpoint

### 2. User Tracking
- Setiap aksi tercatat dengan user_id
- Activity logs mencatat waktu & action
- Audit trail lengkap

### 3. Data Protection
- Prepared statements untuk semua query
- Input sanitization
- CSRF protection via session

### 4. Restore Window
- 30 hari window untuk restore
- Deadline ditampilkan di interface
- Automatic cleanup policy

## Usage Examples

### Example 1: Delete Dokumen Aktif
```javascript
// di platform/documents.php atau documents/index.php
function deleteDocument(id, name) {
    document.getElementById('deleteDocumentId').value = id;
    document.getElementById('deleteDocumentName').textContent = name;
    bootstrap.Modal.getOrCreateInstance(deleteModal).show();
}

// Form submit ke platform/documents.php atau documents.php
// akan redirect ke trash.php
```

### Example 2: View Trash Menu
```
URL: /documents/trash.php
Menampilkan:
- List dokumen dengan countdown 30 hari
- Search & filter
- Restore button untuk setiap dokumen
- Permanent delete button dengan konfirmasi
- Bulk actions (jika diimplementasi)
```

### Example 3: Restore Dokumen
```php
// POST form ke trash.php
<form method="POST" id="restoreForm">
    <input type="hidden" name="action" value="restore">
    <input type="hidden" name="trash_id" value="<?php echo $doc['id']; ?>">
    <button type="submit">Pulihkan</button>
</form>
```

## Troubleshooting

### Error: "Table document_trash doesn't exist"
**Solution:**
1. Akses: `http://localhost/PROJECT%20ARSIP%20LOKER/documents/setup_trash.php`
2. Klik "Setup Database Sekarang"
3. Tunggu sampai berhasil
4. Refresh halaman trash.php

### Error: "Unknown column 'dt.status' in 'where clause'"
**Solution:**
- Update trash.php ke versi terbaru
- SQL alias sudah diperbaiki
- Clear browser cache

### Dokumen tidak muncul di sampah setelah dihapus
**Check:**
1. Apakah tabel `document_trash` ada? → Lihat setup_trash.php
2. Apakah delete.php menghasilkan error? → Lihat browser console
3. Apakah session user_id ada? → Check $_SESSION['user_id']
4. Apakah dokumen status berubah ke 'trashed'? → Query: SELECT * FROM documents

### Kolom tidak ditemukan di documents table
**Check:**
```sql
DESC documents;
```
Pastikan kolom berikut ada:
- `full_name`, `nik`, `passport_number`, `document_number`
- `document_year`, `month_number`, `citizen_category`
- `document_origin`, `file_path`, `description`, `status`

Jika tidak ada, jalankan update schema:
```sql
ALTER TABLE documents ADD COLUMN full_name VARCHAR(255);
ALTER TABLE documents ADD COLUMN nik VARCHAR(16);
-- etc...
```

## Maintenance

### Cleanup Old Trash
Hapus dokumen yang sudah melewati 30 hari:
```php
// Jalankan via cron atau manual
// File: documents/cleanup_trash.php (jika ada)
```

SQL:
```sql
DELETE FROM document_trash 
WHERE restore_deadline < NOW() 
  AND status = 'in_trash';
```

### Monitor Trash Size
```sql
-- Lihat jumlah dokumen di sampah
SELECT COUNT(*) as total, 
       SUM(CHAR_LENGTH(document_data)) as size_bytes
FROM document_trash 
WHERE status = 'in_trash';
```

### Backup Trash Data
```sql
-- Export untuk backup
SELECT * FROM document_trash 
WHERE status != 'permanently_deleted'
INTO OUTFILE '/path/to/trash_backup.csv'
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"';
```

## Future Enhancements

1. **Bulk Restore/Delete**
   - Checkbox untuk select multiple
   - Bulk action buttons
   
2. **Advanced Search**
   - Date range filter
   - Locker filter
   - User filter

3. **Storage Quota**
   - Monitor storage usage
   - Alert jika > 1GB
   
4. **Notifications**
   - Email reminder sebelum auto-delete
   - Notification center di dashboard

5. **Batch Processing**
   - Schedule cleanup
   - Automatic permanent delete after 30 days

6. **Export/Archive**
   - Export sampah ke file
   - Archive ke long-term storage

## Support & Debugging

### Enable Debug Mode
Di `includes/functions.php` atau `config/database.php`:
```php
define('DEBUG', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Error Logs
- PHP Error Log: `/var/log/php-errors.log`
- Browser Console: F12 → Console
- Network Tab: Check fetch/AJAX responses

### Contact Support
Jika ada error yang tidak bisa diatasi, siapkan:
1. Screenshot error message
2. Browser console error
3. Database query yang error
4. PHP error log content
5. Current user role & ID

---

**Last Updated:** 2024
**Version:** 1.0
**Compatibility:** PHP 7.4+, MySQL 5.7+, Bootstrap 5.3+
