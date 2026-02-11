-- Update schema untuk mendukung form baru
USE arsip_dokumen_imigrasi;

-- Tambahkan kolom yang belum ada
ALTER TABLE documents 
ADD COLUMN IF NOT EXISTS nik VARCHAR(20) AFTER full_name,
ADD COLUMN IF NOT EXISTS month_number VARCHAR(20) AFTER birth_date,
ADD COLUMN IF NOT EXISTS marriage_certificate VARCHAR(50) AFTER month_number,
ADD COLUMN IF NOT EXISTS birth_certificate VARCHAR(50) AFTER marriage_certificate,
ADD COLUMN IF NOT EXISTS divorce_certificate VARCHAR(50) AFTER birth_certificate,
ADD COLUMN IF NOT EXISTS custody_certificate VARCHAR(50) AFTER divorce_certificate,
ADD COLUMN IF NOT EXISTS citizen_category ENUM('WNI', 'WNA') DEFAULT 'WNI' AFTER custody_certificate,
ADD COLUMN IF NOT EXISTS document_year INT NULL AFTER citizen_category;

-- Buat tabel untuk menyimpan file-file dokumen jika belum ada
CREATE TABLE IF NOT EXISTS document_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

-- Index untuk optimasi
CREATE INDEX IF NOT EXISTS idx_document_files_document_id ON document_files(document_id);
CREATE INDEX IF NOT EXISTS idx_document_files_type ON document_files(document_type);






















