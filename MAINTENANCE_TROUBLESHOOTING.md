🛠️ MAINTENANCE & TROUBLESHOOTING GUIDE

═══════════════════════════════════════════════════════════════

❓ FREQUENTLY ASKED QUESTIONS (FAQ)

Q1: Bagaimana cara mengakses Menu Sampah?
A: 
   - Login sebagai Admin
   - Buka sidebar (klik icon ☰)
   - Pilih "Menu Sampah" 
   - Menu ada setelah "Lemari Pemusnahan"

Q2: Apakah Staff bisa lihat Menu Sampah?
A: Tidak, Menu Sampah hanya untuk Admin. Staff tidak bisa restore/delete dokumen.

Q3: Bagaimana jika saya lupa restore dokumen sebelum 30 hari habis?
A: Dokumen akan otomatis dihapus secara permanen oleh cleanup script.
   Setidaknya Anda punya 30 hari untuk memutuskan.

Q4: Bisa kah durasi 30 hari diubah?
A: Ya, edit file documents/delete.php dan ubah:
   $restore_deadline = date('Y-m-d H:i:s', strtotime('+30 days'));
   Ganti 30 dengan angka yang diinginkan (14, 60, dll)

Q5: Apakah restore dokumen mengubah lemari/locker?
A: Tidak, dokumen kembali dengan lemari yang sama saat dihapus.

Q6: Bagaimana jika file fisik sudah dihapus dari server?
A: Database tetap tercatat, tapi user tidak bisa download file.
   Anda masih bisa restore ke aktif untuk management, tapi file hilang.

Q7: Dapatkah saya mengembalikan dokumen yang sudah dihapus permanen?
A: TIDAK - permanent delete tidak bisa di-reverse.
   Pastikan sebelum klik "Hapus Permanen".

═══════════════════════════════════════════════════════════════

🔧 TROUBLESHOOTING

MASALAH 1: Setup Table Gagal
─────────────────────────────────────

Gejala: 
  - Access http://localhost/PROJECT%20ARSIP%20LOKER/setup_trash_table.php
  - Error message muncul (bukan success)

Solusi:
  1. Pastikan sudah login sebagai admin
  2. Refresh page, coba lagi
  3. Check browser console untuk error detail
  4. Cek MySQL logs di /var/log/mysql/error.log
  5. Pastikan user MySQL punya permission CREATE TABLE
  
  Cara manual:
  1. Buka phpMyAdmin atau MySQL client
  2. Select database arsip_dokumen_imigrasi
  3. Copy & paste SQL dari create_trash_table.sql
  4. Execute

─────────────────────────────────────

MASALAH 2: Menu Sampah Tidak Muncul di Sidebar
─────────────────────────────────────

Gejala:
  - Sidebar buka tapi "Menu Sampah" tidak ada
  - Hanya ada Lemari Pemusnahan tapi tidak ada Sampah

Solusi:
  1. Clear browser cache (Ctrl+Shift+Delete)
  2. Hard refresh (Ctrl+Shift+R)
  3. Logout dan login ulang
  4. Cek file includes/sidebar.php sudah dimodifikasi
     - Pastikan ada baris: href="/PROJECT ARSIP LOKER/documents/trash.php"
  5. Jika masih tidak muncul, check:
     - ls -la includes/sidebar.php (size seharusnya > 5KB)
     - grep "trash.php" includes/sidebar.php
     
─────────────────────────────────────

MASALAH 3: Dokumen Dihapus Tapi Tidak Muncul di Sampah
─────────────────────────────────────

Gejala:
  - Dokumen berhasil dihapus dari Dokumen Keseluruhan
  - Tapi tidak ada di Menu Sampah
  - Error message tidak ada

Solusi:
  1. Cek status dokumen di database:
     SELECT id, full_name, status FROM documents WHERE status = 'trashed' LIMIT 10;
     
  2. Jika status masih 'active', berarti delete API tidak jalan.
     - Cek browser console untuk error
     - Cek PHP error log: tail -f /var/log/php-errors.log
     
  3. Cek tabel document_trash:
     SELECT COUNT(*) FROM document_trash;
     
  4. Jika tabel kosong:
     - Re-run setup_trash_table.php
     - Check MySQL connection dalam config/database.php
     
  5. Cek document_trash table structure:
     DESC document_trash;

─────────────────────────────────────

MASALAH 4: Restore Dokumen Gagal
─────────────────────────────────────

Gejala:
  - Klik "Pulihkan" di Menu Sampah
  - Modal confirm muncul, klik confirm
  - Tapi dokumen masih di sampah, tidak kembali ke aktif

Solusi:
  1. Cek apakah deadline sudah expired:
     SELECT id, full_name, restore_deadline, NOW() FROM document_trash 
     WHERE restore_deadline < NOW();
     
  2. Jika deadline sudah lewat, dokumen tidak bisa di-restore.
     Manual restore via SQL:
     UPDATE documents SET status = 'active' WHERE id = [original_document_id];
     
  3. Cek error di browser console (F12 → Network tab)
  
  4. Cek server logs:
     tail -f /var/log/apache2/error.log
     tail -f /var/log/php-errors.log

─────────────────────────────────────

MASALAH 5: Permanent Delete Tidak Berfungsi
─────────────────────────────────────

Gejala:
  - Klik "Hapus Permanen"
  - Loading tapi tidak ada hasil
  - Atau error message

Solusi:
  1. Cek file permissions folder:
     ls -la documents/uploads/
     Harus ada r,w,x permissions untuk PHP process
     
  2. Set permissions jika perlu:
     chmod 755 documents/uploads/
     chmod 755 documents/
     
  3. Cek file masih ada:
     ls -la documents/uploads/[filename]
     
  4. Jika file tidak ada tapi DB masih ada:
     Hanya database akan di-delete, file sudah hilang
     
  5. Manual cleanup via SQL:
     DELETE FROM documents WHERE id = [id];
     DELETE FROM document_trash WHERE id = [trash_id];

─────────────────────────────────────

MASALAH 6: Cleanup Script Tidak Berjalan
─────────────────────────────────────

Gejala:
  - Cron job sudah setup
  - Tapi dokumen >30 hari masih ada
  - atau error muncul saat cleanup berjalan

Solusi:
  1. Test manual dulu:
     - Browser: http://localhost/PROJECT%20ARSIP%20LOKER/cleanup_trash.php
     - Harus muncul output status cleanup
     
  2. Cek cron job syntax:
     crontab -l  (list cron jobs)
     0 2 * * * curl http://localhost/PROJECT%20ARSIP%20LOKER/cleanup_trash.php
     
  3. Cek cron logs:
     grep CRON /var/log/syslog
     tail -f /var/log/cron
     
  4. Cek file permissions:
     chmod 755 cleanup_trash.php
     
  5. Jika curl tidak ada, coba wget:
     0 2 * * * wget -O /dev/null http://localhost/PROJECT%20ARSIP%20LOKER/cleanup_trash.php
     
  6. Cek database jalan:
     mysql -u root -p arsip_dokumen_imigrasi -e "SELECT COUNT(*) FROM document_trash WHERE status='in_trash';"

═══════════════════════════════════════════════════════════════

🧹 MAINTENANCE TASKS

MINGGUAN:
─────────────────────────────────────
1. Cek Menu Sampah untuk dokumen yang mau expired (<=3 hari)
   - Putuskan: restore atau permanent delete?
   
2. Monitor file space:
   du -sh documents/uploads/
   
3. Review cleanup script output:
   Jika manual run, lihat berapa dokumen yang di-cleanup
   
4. Cek activity logs untuk unusual activity:
   SELECT * FROM activity_logs 
   WHERE action IN ('MOVE_TO_TRASH', 'RESTORE_DOCUMENT')
   ORDER BY created_at DESC LIMIT 20;

BULANAN:
─────────────────────────────────────
1. Backup database:
   mysqldump -u root -p arsip_dokumen_imigrasi > backup_arsip_$(date +%Y%m%d).sql
   
2. Review trash_audit_logs:
   SELECT COUNT(*) as total, 
          DATE(action_time) as date,
          action
   FROM trash_audit_logs 
   GROUP BY DATE(action_time), action
   ORDER BY date DESC;
   
3. Check disk space:
   df -h
   
4. Verify data integrity:
   SELECT * FROM document_trash dt 
   WHERE original_document_id NOT IN (SELECT id FROM documents)
   AND status != 'permanently_deleted';

TAHUNAN:
─────────────────────────────────────
1. Archive old trash records:
   - Backup tabel document_trash lama
   - Delete records lebih dari 1 tahun
   
2. Optimize database:
   OPTIMIZE TABLE document_trash;
   OPTIMIZE TABLE trash_audit_logs;
   
3. Review & update dokumentasi

═══════════════════════════════════════════════════════════════

🔍 MONITORING & ANALYTICS

Track Dokumen di Sampah:
```sql
SELECT 
    DATE(deleted_at) as tanggal,
    COUNT(*) as jumlah_dihapus,
    COUNT(CASE WHEN status='restored' THEN 1 END) as restored,
    COUNT(CASE WHEN status='in_trash' THEN 1 END) as still_in_trash,
    COUNT(CASE WHEN status='permanently_deleted' THEN 1 END) as permanent_deleted
FROM document_trash
GROUP BY DATE(deleted_at)
ORDER BY tanggal DESC;
```

Siapa Paling Sering Delete Dokumen:
```sql
SELECT 
    u.full_name,
    COUNT(*) as total_deletes,
    DATE(dt.deleted_at) as last_delete
FROM document_trash dt
JOIN users u ON dt.deleted_by = u.id
GROUP BY dt.deleted_by, DATE(dt.deleted_at)
ORDER BY total_deletes DESC;
```

Dokumen Yg Sudah Mau Expired (<=7 hari):
```sql
SELECT 
    full_name,
    document_number,
    DATEDIFF(restore_deadline, NOW()) as days_remaining,
    deleted_at,
    restored_by
FROM document_trash
WHERE status = 'in_trash' 
AND DATEDIFF(restore_deadline, NOW()) <= 7
ORDER BY restore_deadline ASC;
```

═══════════════════════════════════════════════════════════════

📋 CHECKLIST SETUP

Setup Database:
☐ Run setup_trash_table.php
☐ Verify tabel document_trash created
☐ Verify tabel trash_audit_logs created
☐ Check tabel structure dengan DESC

Verify Menu:
☐ Sidebar muncul "Menu Sampah"
☐ Klik bisa akses trash.php
☐ Halaman load dengan benar

Test Fitur:
☐ Delete 1 dokumen dari Keseluruhan
☐ Cek dokumen ada di sampah
☐ Restore dokumen → kembali aktif
☐ Delete lagi, permanent delete → hilang selamanya

Setup Maintenance:
☐ Setup cron job untuk auto-cleanup (optional)
☐ Setup backup script
☐ Document & train staff

═══════════════════════════════════════════════════════════════

⚠️ COMMON PITFALLS

JANGAN:
✗ Langsung permanent delete tanpa berpikir 2x
✗ Lupa setup auto-cleanup dan storage jadi penuh
✗ Tidak backup database sebelum deploy
✗ Set durasi 30 hari terlalu pendek (1-2 hari)
✗ Jalankan cleanup script lebih dari 1x sehari

HARUS:
✓ Always backup sebelum major changes
✓ Test di environment development dulu
✓ Monitor cleanup logs secara berkala
✓ Educate users tentang fitur sampah
✓ Keep audit logs untuk compliance

═══════════════════════════════════════════════════════════════

📞 EMERGENCY PROCEDURES

KASUS: Dokumen Penting Terhapus Permanen!
─────────────────────────────────────

Immediate Actions:
1. Stop cleanup script kalau sedang jalan
2. Cek database backup terakhir
3. Restore dari backup (stop aplikasi dulu)
4. Check jika file masih ada di server/trash

Jangka Panjang:
1. Review proses delete - terlalu mudah?
2. Add additional confirmation dialog?
3. Setup backup lebih frequent
4. Train admin tentang 30 hari safety period

KASUS: Cleanup Script Error & Crash
─────────────────────────────────────

1. Check PHP error log:
   tail -f /var/log/php-errors.log
   
2. Check MySQL error log:
   tail -f /var/log/mysql/error.log
   
3. Manual cleanup:
   php cleanup_trash.php (dari CLI)
   
4. Check disk space:
   df -h

KASUS: Database Corruption
─────────────────────────────────────

1. Stop aplikasi
2. Backup database terakhir yang valid:
   mysqldump -u root -p arsip_dokumen_imigrasi > backup_safe.sql
   
3. Check table integrity:
   CHECK TABLE document_trash;
   CHECK TABLE trash_audit_logs;
   
4. Repair jika perlu:
   REPAIR TABLE document_trash;
   REPAIR TABLE trash_audit_logs;

═══════════════════════════════════════════════════════════════

📚 DOCUMENTATION REFERENCES

- TRASH_FEATURE_GUIDE.md ..................... Panduan lengkap fitur
- TRASH_SETUP_QUICK_START.txt ............... Quick start
- IMPLEMENTASI_MENU_SAMPAH.md ............... Summary implementasi
- MAINTENANCE_TROUBLESHOOTING.md ........... File ini
- create_trash_table.sql .................... SQL schema

═══════════════════════════════════════════════════════════════

Version: 1.0
Last Updated: 2024
Status: ✅ Ready to Production
