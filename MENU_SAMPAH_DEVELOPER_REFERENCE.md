# Menu Sampah - Quick Reference for Developers

## 🎯 Quick Links

| Purpose | Link | File |
|---------|------|------|
| Setup Database | http://localhost/PROJECT%20ARSIP%20LOKER/documents/setup_trash.php | `documents/setup_trash.php` |
| Admin Trash Menu | http://localhost/PROJECT%20ARSIP%20LOKER/documents/trash.php | `documents/trash.php` |
| Main Documentation | [MENU_SAMPAH_COMPLETE_GUIDE.md](MENU_SAMPAH_COMPLETE_GUIDE.md) | root directory |
| Setup Checklist | [MENU_SAMPAH_SETUP_CHECKLIST.md](MENU_SAMPAH_SETUP_CHECKLIST.md) | root directory |
| Implementation Summary | [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) | root directory |

## 🔧 Code Snippets for Development

### Include Trash Helper in Any File
```php
require_once '../includes/trash_helper.php';

// Ensure tables exist (safe to call multiple times)
$result = ensure_trash_tables_exist($db);
if (!$result['success']) {
    echo $result['message']; // Show error
}
```

### Delete Document to Trash (Backend)
```php
try {
    // Get dokumen
    $doc = $db->fetch("SELECT * FROM documents WHERE id = ?", [$doc_id]);
    
    // Prepare trash data
    $restore_deadline = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Insert ke trash
    $sql = "INSERT INTO document_trash (
        original_document_id, title, full_name, nik, passport_number,
        document_number, document_year, month_number, locker_code,
        locker_name, citizen_category, document_origin, file_path,
        description, deleted_by, restore_deadline, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'in_trash')";
    
    $db->execute($sql, [
        $doc['id'],
        $doc['title'] ?? '',
        $doc['full_name'] ?? '',
        $doc['nik'] ?? '',
        $doc['passport_number'] ?? '',
        $doc['document_number'] ?? '',
        $doc['document_year'] ?? null,
        $doc['month_number'] ?? '',
        $doc['month_number'] ?? '', // locker_code
        'Lemari', // locker_name
        $doc['citizen_category'] ?? 'WNI',
        $doc['document_origin'] ?? '',
        $doc['file_path'] ?? '',
        $doc['description'] ?? '',
        $_SESSION['user_id'],
        $restore_deadline
    ]);
    
    // Update original document status
    $db->execute("UPDATE documents SET status = 'trashed' WHERE id = ?", [$doc_id]);
    
    // Log activity
    log_activity($_SESSION['user_id'], 'MOVE_TO_TRASH', 
        "Memindahkan dokumen ke sampah: " . $doc['title'], $doc_id);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
```

### Delete Document to Trash (AJAX/Frontend)
```javascript
function deleteDocument(docId) {
    if (!confirm('Yakin ingin menghapus dokumen ini?')) return;
    
    fetch('delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: docId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Dokumen dipindahkan ke sampah', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(e => showAlert('Error: ' + e.message, 'danger'));
}
```

### Restore Document from Trash
```php
try {
    $trash_doc = $db->fetch("SELECT * FROM document_trash WHERE id = ?", [$trash_id]);
    
    // Restore to active
    $db->execute("UPDATE documents SET status = 'active' WHERE id = ?", 
        [$trash_doc['original_document_id']]);
    
    // Update trash record
    $db->execute("UPDATE document_trash SET status = 'restored', 
        restored_at = NOW(), restored_by = ? WHERE id = ?", 
        [$_SESSION['user_id'], $trash_id]);
    
    // Log
    log_activity($_SESSION['user_id'], 'RESTORE_DOCUMENT',
        "Memulihkan dokumen: " . $trash_doc['full_name'],
        $trash_doc['original_document_id']);
    
    echo json_encode(['success' => true, 'message' => 'Dokumen dipulihkan']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
```

### Query: Count Trash Documents
```sql
-- Total in trash
SELECT COUNT(*) as total FROM document_trash WHERE status = 'in_trash';

-- By citizen category
SELECT citizen_category, COUNT(*) as count 
FROM document_trash 
WHERE status = 'in_trash'
GROUP BY citizen_category;

-- About to expire (< 7 days)
SELECT * FROM document_trash 
WHERE status = 'in_trash' 
AND restore_deadline < DATE_ADD(NOW(), INTERVAL 7 DAY)
ORDER BY restore_deadline ASC;

-- Already expired
SELECT * FROM document_trash 
WHERE status = 'in_trash' 
AND restore_deadline < NOW();
```

### Query: View Audit Trail
```sql
-- All trash actions for a document
SELECT dt.full_name, tal.action, tal.action_time, u.full_name as user_name
FROM document_trash dt
LEFT JOIN trash_audit_logs tal ON dt.id = tal.document_trash_id
LEFT JOIN users u ON tal.user_id = u.id
WHERE dt.original_document_id = ?
ORDER BY tal.action_time DESC;

-- Activity by user
SELECT u.full_name, COUNT(*) as actions, tal.action
FROM trash_audit_logs tal
LEFT JOIN users u ON tal.user_id = u.id
WHERE DATE(tal.action_time) = CURDATE()
GROUP BY u.full_name, tal.action;
```

## 🐛 Debugging

### Enable Verbose Error Logging
```php
// In config/database.php or top of trash.php
if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Log SQL queries
    echo '<pre>';
    echo "SQL: " . $sql . "\n";
    echo "Params: " . json_encode($params) . "\n";
    echo '</pre>';
}
```

### Check if Tables Exist
```php
// Check if trash tables exist
try {
    $db->fetch("SELECT COUNT(*) FROM document_trash");
    echo "✓ Tabel document_trash ada";
} catch (Exception $e) {
    echo "✗ Tabel document_trash tidak ada: " . $e->getMessage();
}

try {
    $db->fetch("SELECT COUNT(*) FROM trash_audit_logs");
    echo "✓ Tabel trash_audit_logs ada";
} catch (Exception $e) {
    echo "✗ Tabel trash_audit_logs tidak ada: " . $e->getMessage();
}
```

### Monitor SQL Errors
```php
// In delete.php or trash.php
try {
    // SQL operation
} catch (PDOException $e) {
    // Log the error
    error_log("[TRASH ERROR] " . $e->getMessage());
    error_log("[SQL] " . $e->queryString);
    error_log("[CODE] " . $e->getCode());
    
    // Return to user
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'sql' => DEBUG ? $e->queryString : 'hidden'
    ]);
}
```

## 📝 Common Tasks

### Task: Add New Column to Trash Table
```sql
ALTER TABLE document_trash 
ADD COLUMN new_column VARCHAR(255) AFTER description;

-- Add index if needed
ALTER TABLE document_trash 
ADD INDEX idx_new_column (new_column);
```

### Task: Backup Trash Data
```sql
-- Export to file
SELECT * FROM document_trash 
INTO OUTFILE '/tmp/trash_backup.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Or dump with mysqldump
mysqldump -u root arsip_dokumen_imigrasi document_trash > trash_backup.sql
```

### Task: Clean Old Trash (>30 days)
```sql
-- Find old trash
SELECT * FROM document_trash 
WHERE status = 'in_trash' 
AND restore_deadline < NOW();

-- Delete old trash
DELETE FROM document_trash 
WHERE status = 'in_trash' 
AND restore_deadline < DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Delete permanently_deleted records older than 3 months
DELETE FROM document_trash 
WHERE status = 'permanently_deleted' 
AND deleted_at < DATE_SUB(NOW(), INTERVAL 3 MONTH);
```

### Task: Find Orphaned Records
```sql
-- Trash records with deleted original documents
SELECT dt.* FROM document_trash dt
LEFT JOIN documents d ON dt.original_document_id = d.id
WHERE d.id IS NULL;

-- Clean up (be careful!)
DELETE dt FROM document_trash dt
LEFT JOIN documents d ON dt.original_document_id = d.id
WHERE d.id IS NULL AND dt.status = 'permanently_deleted';
```

### Task: Generate Trash Report
```sql
SELECT 
    DATE(deleted_at) as tanggal,
    COUNT(*) as jumlah_dihapus,
    SUM(CASE WHEN status = 'restored' THEN 1 ELSE 0 END) as dipulihkan,
    SUM(CASE WHEN status = 'in_trash' THEN 1 ELSE 0 END) as masih_di_sampah,
    SUM(CASE WHEN status = 'permanently_deleted' THEN 1 ELSE 0 END) as dihapus_permanen
FROM document_trash
GROUP BY DATE(deleted_at)
ORDER BY tanggal DESC;
```

## 🔐 Security Checklist

- [ ] `require_admin()` called in trash.php
- [ ] All inputs sanitized via `sanitize_input()`
- [ ] All queries use prepared statements
- [ ] Session check for user_id
- [ ] Confirm modals for delete actions
- [ ] File delete only after confirm
- [ ] Activity logged for all actions
- [ ] No sensitive data in logs
- [ ] Proper error handling (no SQL exposed)

## 📦 Dependencies

**Required:**
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- PDO MySQL extension
- Bootstrap 5.3+
- FontAwesome 6+ (for icons)

**Optional:**
- jQuery (for enhanced UX, not required)
- DataTables (for advanced sorting)

## 🚀 Performance Tips

1. **Index Strategy:**
   - Query usually filters by status → `idx_status`
   - Sort by deleted_at → `idx_deleted_at`
   - Find by deadline → `idx_restore_deadline`
   - All are created automatically

2. **Query Optimization:**
   ```php
   // Good: Uses index
   SELECT * FROM document_trash 
   WHERE status = 'in_trash'
   ORDER BY deleted_at DESC
   LIMIT 15 OFFSET 0;
   
   // Bad: Full scan
   SELECT * FROM document_trash 
   WHERE full_name LIKE '%budi%'
   AND document_year = 2023;
   ```

3. **Pagination:**
   - Always use LIMIT in SELECT
   - Default: 15 items per page
   - Can adjust in trash.php line ~25

4. **Search Strategy:**
   - If search is slow, add index:
   ```sql
   ALTER TABLE document_trash 
   ADD FULLTEXT INDEX idx_search (full_name, nik, passport_number);
   ```

## 📞 Support

For issues not covered here, check:
1. `MENU_SAMPAH_COMPLETE_GUIDE.md` - Detailed documentation
2. `MENU_SAMPAH_SETUP_CHECKLIST.md` - Step-by-step setup
3. Browser console (F12) - JavaScript errors
4. PHP error log - Server errors
5. Database error logs - MySQL errors

---

**Last Updated:** 2024  
**Version:** 1.0  
**For Developers Only**
