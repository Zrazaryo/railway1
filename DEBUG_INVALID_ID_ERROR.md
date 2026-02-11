# 🔧 DEBUG: "ID dokumen tidak valid" Error

## Masalah
Ketika klik "Hapus Terpilih", muncul error:
```
Gagal menghapus dokumen: ID dokumen tidak valid
```

## Solusi

### Step 1: Open Browser Console
Tekan **F12** di keyboard

Pilih tab **Console**

### Step 2: Select Documents & Delete
1. Kembali ke halaman dokumen
2. Centang checkbox dokumen (1-2 dokumen dulu)
3. Klik "Hapus Terpilih"
4. Lihat output di Console

### Step 3: Debug Output
Di Console, akan ada log seperti:
```
Checkbox value: "1" → ID: 1
Checkbox value: "2" → ID: 2
Document IDs to delete: [1, 2]
Response status: 200
Response data: {success: true, message: "..."}
```

### Step 4: Troubleshooting

#### **Kasus 1: "Tidak ada dokumen ID yang valid"**

```
Checkbox value: "1" → ID: NaN
Document IDs to delete: []
```

**Penyebab:** Checkbox value bukan angka  
**Solusi:** 
- Refresh halaman (F5)
- Coba dengan dokumen lain
- Check apakah column `id` di tabel documents bernilai angka

#### **Kasus 2: "Response status: 400 atau 404"**

```
Response status: 404
```

**Penyebab:** File delete_multiple.php tidak ditemukan  
**Solusi:**
- Verify file ada: `documents/delete_multiple.php`
- Check path: relatif dari platform/documents.php
- Pastikan tidak ada typo di path

#### **Kasus 3: Error di delete_multiple.php**

Server log akan show:
```
ERROR delete_multiple.php - ID dokumen tidak valid. Input: {"document_ids":[]}
```

**Penyebab:** JSON dari frontend kosong  
**Solusi:**
- Verify checkbox diklik (ada checkmark)
- Verify name attribute pada checkbox
- Try hard refresh: Ctrl+Shift+Delete

### Step 5: Check Server Logs

**File:** `/storage/logs/error.log` (atau lokasi logs Anda)

Cari line dengan `delete_multiple.php`:
```
[2024-01-01] ERROR delete_multiple.php - ID dokumen tidak valid. Input: {"document_ids":[]}
```

Ini akan menunjukkan apa yang diterima dari frontend.

---

## Checklist Perbaikan

- [ ] Update delete_multiple.php dengan better error handling
  → ✅ DONE (add debug logging)

- [ ] Update platform/documents.php dengan console logs
  → ✅ DONE (add detailed logging)

- [ ] Verify checkbox value correct
  → Check di Console saat delete

- [ ] Verify JSON.stringify working
  → Check Network tab (POST body)

- [ ] Test dengan 1 dokumen dulu
  → Paling mudah untuk debug

---

## Network Tab Debugging

1. Open **F12** → **Network** tab
2. Select dokumen & delete
3. Cari request ke `delete_multiple.php`
4. Click request untuk lihat detail

### Check Request:
```
POST ../documents/delete_multiple.php
Content-Type: application/json

Body:
{"document_ids":[1,2,3]}
```

### Check Response:
```
Status: 200
Body:
{
  "success": true,
  "message": "Berhasil memindahkan 3 dokumen...",
  "deleted_count": 3,
  "failed_count": 0
}
```

---

## Quick Fix Checklist

- [ ] **F5** Refresh halaman platform/documents.php
- [ ] **Ctrl+Shift+Delete** Hard refresh (clear cache)
- [ ] Check checkbox ada value: `value="<?php echo $doc['id']; ?>"`
- [ ] Check documents.php di-update (deleteSelected function)
- [ ] Check delete_multiple.php ada & readable
- [ ] Check database status column is VARCHAR (not ENUM)
- [ ] Try delete 1 dokumen dulu (bukan bulk)

---

## Updated Code Changes

### delete_multiple.php
```php
// BARU: Debug logging
error_log('DEBUG delete_multiple.php - input: ' . json_encode($input));
error_log('DEBUG delete_multiple.php - document_ids: ' . json_encode($document_ids));

// BARU: Better validation
$document_ids = array_map(function($id) {
    $id = intval($id);
    return ($id > 0) ? $id : null;
}, $document_ids);
```

### platform/documents.php
```javascript
// BARU: Console logging
console.log('Checkbox value: "' + val + '" → ID: ' + id);
console.log('Document IDs to delete:', docIds);
console.log('Response status:', response.status);

// BARU: Better error messages
alert('✗ Error: Tidak ada dokumen ID yang valid. Coba refresh halaman.');
```

---

## Jika Masih Error

**Kumpulkan info ini:**

1. **Console output:** Screenshot atau copy-paste dari Console
2. **Network response:** Screenshot atau JSON response
3. **Database check:**
   ```sql
   SELECT id, full_name, status FROM documents LIMIT 3;
   ```
4. **Server log:** Search untuk `delete_multiple.php` error
5. **File check:** Verify delete_multiple.php ada & readable

Kemudian share info di atas untuk debugging lebih lanjut.

---

## Known Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| ID dokumen tidak valid | JSON kosong | Refresh halaman & coba lagi |
| NaN di console | Value bukan angka | Clear cache (Ctrl+Shift+Del) |
| Response 404 | File tidak ditemukan | Verify path relatif correct |
| ENUM error | Status column ENUM | Run fix_schema.php |
| No response | CORS atau error | Check server logs |

---

## Testing Steps

### Test 1: Manual Verification
```
1. Open F12 Console
2. Select 1 dokumen
3. Click "Hapus Terpilih"
4. Check console for:
   - Checkbox value logged
   - Document IDs array
   - Response status & data
5. Verify dokumen masuk trash
```

### Test 2: Network Debugging
```
1. Open F12 Network tab
2. Select 2-3 dokumen
3. Click "Hapus Terpilih"
4. Check POST request:
   - URL correct
   - Body has document_ids
   - Response status 200
   - Response is valid JSON
```

### Test 3: Database Verification
```
1. After delete, run SQL:
   SELECT * FROM document_trash 
   WHERE deleted_by = 1 
   ORDER BY id DESC LIMIT 5;
   
   Should show recently deleted documents
```

---

## Support

Jika masih error setelah semua ini, collect:
1. Console screenshot
2. Network request/response screenshot  
3. SQL query output
4. Server error log

Share untuk debugging lebih detail.
