-- Update database schema untuk menambahkan field pencarian dokumen
-- Jalankan script ini untuk menambahkan field baru ke tabel documents

USE arsip_dokumen_imigrasi;

-- Tambahkan field baru ke tabel documents
ALTER TABLE documents 
ADD COLUMN full_name VARCHAR(200) NULL AFTER title,
ADD COLUMN birth_date DATE NULL AFTER full_name,
ADD COLUMN passport_number VARCHAR(50) NULL AFTER birth_date;

-- Tambahkan index untuk optimasi pencarian
CREATE INDEX idx_documents_full_name ON documents(full_name);
CREATE INDEX idx_documents_birth_date ON documents(birth_date);
CREATE INDEX idx_documents_passport_number ON documents(passport_number);

-- Update beberapa dokumen sample untuk testing
UPDATE documents 
SET full_name = 'John Doe', 
    birth_date = '1990-05-15', 
    passport_number = 'A1234567' 
WHERE id = 1;

UPDATE documents 
SET full_name = 'Jane Smith', 
    birth_date = '1985-12-03', 
    passport_number = 'B9876543' 
WHERE id = 2;



























