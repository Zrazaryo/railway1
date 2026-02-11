-- Update schema untuk mendukung form baru
USE arsip_dokumen_imigrasi;

-- Tambahkan kolom baru ke tabel documents
ALTER TABLE documents 
ADD COLUMN full_name VARCHAR(100) AFTER document_number,
ADD COLUMN nik VARCHAR(20) AFTER full_name,
ADD COLUMN passport_number VARCHAR(50) AFTER nik,
ADD COLUMN birth_date DATE AFTER passport_number,
ADD COLUMN month_number VARCHAR(20) AFTER birth_date,
ADD COLUMN marriage_certificate VARCHAR(50) AFTER month_number,
ADD COLUMN birth_certificate VARCHAR(50) AFTER marriage_certificate,
ADD COLUMN divorce_certificate VARCHAR(50) AFTER birth_certificate,
ADD COLUMN custody_certificate VARCHAR(50) AFTER divorce_certificate,
ADD COLUMN citizen_category ENUM('WNI', 'WNA') DEFAULT 'WNI' AFTER custody_certificate,
ADD COLUMN document_origin VARCHAR(100) NULL AFTER citizen_category,
ADD COLUMN document_order_number INT NULL AFTER document_origin, -- Kolom baru untuk nomor urut dokumen dalam lemari
ADD COLUMN document_year INT NULL AFTER document_order_number;

-- Buat tabel untuk menyimpan file-file dokumen
CREATE TABLE document_files (
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
CREATE INDEX idx_document_files_document_id ON document_files(document_id);
CREATE INDEX idx_document_files_type ON document_files(document_type);