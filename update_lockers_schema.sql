USE arsip_dokumen_imigrasi;

-- Tabel master lemari untuk menyimpan konfigurasi A s/d Z
CREATE TABLE IF NOT EXISTS lockers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE, -- Contoh: A, B, Z
    name VARCHAR(50) NOT NULL,        -- Contoh: A
    max_capacity INT NOT NULL DEFAULT 600
);


