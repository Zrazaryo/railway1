-- Update schema untuk mendukung form baru
USE arsip_dokumen_imigrasi;

-- Tambahkan kolom yang belum ada (akan error jika sudah ada, tapi tidak masalah)
ALTER TABLE documents ADD COLUMN nik VARCHAR(20) AFTER full_name;
ALTER TABLE documents ADD COLUMN month_number VARCHAR(20) AFTER birth_date;
ALTER TABLE documents ADD COLUMN marriage_certificate VARCHAR(50) AFTER month_number;
ALTER TABLE documents ADD COLUMN birth_certificate VARCHAR(50) AFTER marriage_certificate;
ALTER TABLE documents ADD COLUMN divorce_certificate VARCHAR(50) AFTER birth_certificate;
ALTER TABLE documents ADD COLUMN custody_certificate VARCHAR(50) AFTER divorce_certificate;
ALTER TABLE documents ADD COLUMN citizen_category ENUM('WNI', 'WNA') DEFAULT 'WNI' AFTER custody_certificate;

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






















