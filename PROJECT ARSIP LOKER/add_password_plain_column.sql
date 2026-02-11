-- Script untuk menambahkan kolom password_plain ke tabel users
-- Kolom ini digunakan untuk menyimpan password asli (dengan enkripsi base64)

USE arsip_dokumen_imigrasi;

-- Tambahkan kolom password_plain jika belum ada
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS password_plain TEXT NULL 
AFTER password;

-- Catatan: 
-- - Password asli akan disimpan dengan enkripsi base64
-- - Untuk user yang sudah ada, password_plain akan NULL sampai password di-update
-- - Password asli hanya bisa dilihat untuk user yang dibuat/di-update setelah kolom ini ditambahkan

















