USE arsip_dokumen_imigrasi;

-- Tabel master lemari untuk menyimpan konfigurasi A1-A10 s/d Z1-Z10
CREATE TABLE IF NOT EXISTS lockers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE, -- Contoh: A1, B5, Z10
    name VARCHAR(50) NOT NULL,        -- Contoh: A.01
    max_capacity INT NOT NULL DEFAULT 600
);


