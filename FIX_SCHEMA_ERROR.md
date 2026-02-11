# ⚠️ URGENT FIX - Database Schema Error

## Error yang Terjadi:
```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
```

## Penyebab:
Kolom `status` di tabel `documents` masih dalam format **ENUM** yang hanya accept nilai:
- 'active'
- 'archived'
- 'deleted'

Tidak bisa menerima nilai **'trashed'** yang diperlukan untuk soft delete.

## Solusi:

### Step 1: Fix Schema Otomatis
Akses URL ini di browser:
```
http://localhost/PROJECT%20ARSIP%20LOKER/documents/fix_schema.php
```

Tunggu sampai muncul pesan ✅ "Schema Fix Selesai!"

### Step 2: Test Delete Lagi
Setelah fix_schema.php selesai:
1. Kembali ke halaman "Dokumen Keseluruhan"
2. Coba hapus dokumen lagi
3. Seharusnya berhasil dan muncul di Menu Sampah

---

## Alternatif Manual (jika auto fix tidak bekerja):

Jalankan SQL berikut di phpMyAdmin atau MySQL CLI:

```sql
-- Ubah kolom status dari ENUM ke VARCHAR
ALTER TABLE documents MODIFY COLUMN status VARCHAR(20) DEFAULT 'active' COMMENT 'Status: active, archived, deleted, trashed';

-- Tambah index (optional)
ALTER TABLE documents ADD INDEX idx_documents_status (status);
```

---

**Setelah fix selesai, hubungi jika masih ada error!**
