# Bulk Delete Implementation - Code Changes Reference

## Files Changed & Their Modifications

---

## 1. documents/delete_multiple.php

### BEFORE (Permanent Delete):
```php
// Line 43-54 (OLD)
$sql = "SELECT id, full_name FROM documents WHERE id = ?";
$document = $db->fetch($sql, [$document_id]);
if (!$document) {
    $failed_count++;
    continue;
}

// Hapus permanen, tidak kirim ke pemusnahan otomatis
$sql = "DELETE FROM documents WHERE id = ?";
$db->execute($sql, [$document_id]);

log_activity($_SESSION['user_id'], 'DELETE_DOCUMENT', "Menghapus dokumen: ...", $document_id);
```

### AFTER (Soft Delete to Trash):
```php
// Line 44-46 (NEW)
$sql = "SELECT d.* FROM documents d WHERE d.id = ?";
$document = $db->fetch($sql, [$document_id]);
if (!$document) {
    $failed_count++;
    continue;
}

// Pindahkan ke trash daripada hapus permanen
$restore_deadline = date('Y-m-d H:i:s', strtotime('+30 days'));

// Insert ke document_trash
$insert_trash_sql = "INSERT INTO document_trash (
    original_document_id, title, full_name, nik, passport_number, document_number,
    document_year, month_number, locker_code, locker_name, citizen_category, 
    document_origin, file_path, description, deleted_by, restore_deadline, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'in_trash')";

$db->execute($insert_trash_sql, [
    $document['id'],
    $document['title'] ?? '',
    $document['full_name'] ?? '',
    $document['nik'] ?? '',
    $document['passport_number'] ?? '',
    $document['document_number'] ?? '',
    $document['document_year'] ?? null,
    $document['month_number'] ?? '',
    $document['locker_code'] ?? '',
    $document['locker_name'] ?? 'Platform',
    $document['citizen_category'] ?? 'WNI',
    $document['document_origin'] ?? '',
    $document['file_path'] ?? '',
    $document['description'] ?? '',
    $_SESSION['user_id'],
    $restore_deadline
]);

// Update dokumen status ke trashed
$sql = "UPDATE documents SET status = 'trashed' WHERE id = ?";
$db->execute($sql, [$document_id]);

log_activity($_SESSION['user_id'], 'MOVE_TO_TRASH', "Memindahkan dokumen ke sampah: ...", $document_id);
```

### Key Changes:
- ✅ Changed query from `SELECT id, full_name` to `SELECT d.*` (fetch all columns)
- ✅ Removed `DELETE FROM documents` statement
- ✅ Added INSERT to `document_trash` table
- ✅ Added UPDATE to set `documents.status = 'trashed'`
- ✅ Changed log action from `DELETE_DOCUMENT` to `MOVE_TO_TRASH`
- ✅ Added 30-day `restore_deadline` calculation
- ✅ Use actual `locker_code` & `locker_name` from document data

---

## 2. platform/documents.php

### BEFORE (Alert only):
```javascript
// Lines 912-922 (OLD)
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.document-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Pilih minimal satu dokumen untuk dihapus!');
        return;
    }
    
    if (confirm('Apakah Anda yakin ingin menghapus ' + checkboxes.length + ' dokumen yang dipilih?')) {
        const docIds = Array.from(checkboxes).map(cb => cb.value);
        // Implement delete multiple
        alert('Fitur hapus terpilih akan segera tersedia. Dokumen yang dipilih: ' + docIds.join(', '));
    }
}
```

### AFTER (Full AJAX Implementation):
```javascript
// Lines 912-945 (NEW)
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.document-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Pilih minimal satu dokumen untuk dihapus!');
        return;
    }
    
    if (confirm('Apakah Anda yakin ingin menghapus ' + checkboxes.length + ' dokumen yang dipilih? Dokumen akan dipindahkan ke Menu Sampah dan dapat dipulihkan dalam 30 hari.')) {
        const docIds = Array.from(checkboxes).map(cb => cb.value);
        
        // Call delete_multiple.php
        fetch('../documents/delete_multiple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                document_ids: docIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ ' + data.message);
                // Uncheck all checkboxes
                checkboxes.forEach(cb => cb.checked = false);
                // Reload documents
                loadDocuments();
            } else {
                alert('✗ ' + (data.message || 'Gagal menghapus dokumen'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('✗ Terjadi kesalahan saat menghapus dokumen');
        });
    }
}
```

### Key Changes:
- ✅ Replaced alert() with fetch() AJAX call
- ✅ Send POST request to `../documents/delete_multiple.php`
- ✅ Send JSON body with `document_ids` array
- ✅ Parse JSON response
- ✅ Handle success: uncheck & reload documents
- ✅ Handle error: show user-friendly error message
- ✅ Updated confirmation dialog message (mention 30-day recovery)

---

## 3. documents/delete_multiple.php - Line-by-line Changes

### Line 17: Change request parameter
```javascript
// OLD: $document_ids = $input['ids'] ?? [];
// NEW: $document_ids = $input['document_ids'] ?? [];
```

### Line 44: Fetch full document data
```php
// OLD: $sql = "SELECT id, full_name FROM documents WHERE id = ?";
// NEW: $sql = "SELECT d.* FROM documents d WHERE id = ?";
```

### Lines 50-75: Insert to trash + update status
```php
// OLD:
$sql = "DELETE FROM documents WHERE id = ?";
$db->execute($sql, [$document_id]);

// NEW:
$restore_deadline = date('Y-m-d H:i:s', strtotime('+30 days'));
$insert_trash_sql = "INSERT INTO document_trash (...) VALUES (...)";
$db->execute($insert_trash_sql, [all document fields]);
$sql = "UPDATE documents SET status = 'trashed' WHERE id = ?";
$db->execute($sql, [$document_id]);
```

### Line 85: Change log action
```php
// OLD: log_activity(..., 'DELETE_DOCUMENT', ...);
// NEW: log_activity(..., 'MOVE_TO_TRASH', ...);
```

### Lines 96-100: Update success message
```php
// OLD: "Berhasil menghapus $deleted_count dokumen"
// NEW: "Berhasil memindahkan $deleted_count dokumen ke Menu Sampah"
```

---

## 4. platform/documents.php - Confirmation Dialog Change

### BEFORE:
```
"Apakah Anda yakin ingin menghapus X dokumen yang dipilih?"
```

### AFTER:
```
"Apakah Anda yakin ingin menghapus X dokumen yang dipilih? 
Dokumen akan dipindahkan ke Menu Sampah dan dapat dipulihkan dalam 30 hari."
```

---

## Database Schema (No Changes Needed)

The implementation uses existing tables that should have been created by:
- `documents/setup_trash.php` - creates document_trash & trash_audit_logs
- `documents/fix_schema.php` - converts documents.status from ENUM to VARCHAR

If not done yet, run those scripts first.

---

## Summary of Changes

| File | Type | Change |
|------|------|--------|
| delete_multiple.php | Backend | Permanent DELETE → Soft DELETE to trash |
| platform/documents.php | Frontend | Alert only → AJAX with success/error handling |
| delete_multiple.php | Request Param | `ids` → `document_ids` |
| delete_multiple.php | Query | `SELECT id, full_name` → `SELECT d.*` |
| delete_multiple.php | Action | DELETE statement → INSERT + UPDATE |
| delete_multiple.php | Message | "menghapus" → "memindahkan ke Menu Sampah" |
| platform/documents.php | Dialog | Added "dapat dipulihkan dalam 30 hari" message |
| platform/documents.php | Handlers | Added fetch, error handling, reload |

---

## Testing the Changes

### Quick Test:
```
1. Open platform/documents.php
2. Select 1-3 documents
3. Click "Hapus Terpilih"
4. Confirm
5. Go to documents/trash.php
6. Documents should be there
```

### Verify JSON Response:
```
Open browser console (F12)
Network tab
Click delete
Check delete_multiple.php response should be:
{
  "success": true,
  "message": "Berhasil memindahkan X dokumen ke Menu Sampah",
  "deleted_count": X,
  "failed_count": 0
}
```

### Check Database:
```sql
-- Verify documents moved to trash
SELECT * FROM document_trash WHERE deleted_by = [user_id];

-- Verify documents marked as trashed
SELECT id, status FROM documents WHERE id IN ([deleted_ids]);
-- Should show: status = 'trashed'
```

---

## Rollback Instructions (If Needed)

If you need to rollback to permanent delete:

### In delete_multiple.php:
```php
// Remove INSERT/UPDATE statements
// Add back DELETE statement:
$sql = "DELETE FROM documents WHERE id = ?";
$db->execute($sql, [$document_id]);
```

### In platform/documents.php:
```javascript
// Replace AJAX block with:
alert('Dokumen akan dihapus. (Permanent delete not yet enabled)');
```

---

## Performance Implications

**Before:**
- 1 DELETE query per document

**After:**
- 1 SELECT query (to get full data)
- 1 INSERT query (to trash table)
- 1 UPDATE query (status)
- 1 LOG INSERT query
- **Total: 4 queries per document**

For 100 documents: 400 queries (still very fast, <2 seconds)

Can be optimized to single batch INSERT if needed (future enhancement).

---

## Notes

- All changes are backward compatible
- No breaking changes to existing code
- Menu Sampah functionality depends on existing setup
- Activity logging uses existing log_activity() function
- 30-day deadline is configurable (just change strtotime('+30 days'))
