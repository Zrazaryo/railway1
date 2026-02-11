-- Script untuk menambahkan kolom profil tambahan ke tabel users
USE arsip_dokumen_imigrasi;

-- Tambahkan kolom baru jika belum ada
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS birth_date DATE NULL AFTER email,
ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER birth_date,
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) NULL AFTER address,
ADD COLUMN IF NOT EXISTS division_position VARCHAR(100) NULL AFTER phone_number,
ADD COLUMN IF NOT EXISTS bio_status TEXT NULL AFTER division_position,
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER bio_status;







