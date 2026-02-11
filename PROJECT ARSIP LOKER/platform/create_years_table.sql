-- Tabel untuk menyimpan tahun lemari dokumen platform
USE arsip_dokumen_imigrasi;

CREATE TABLE IF NOT EXISTS platform_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Index untuk optimasi
CREATE INDEX idx_platform_years_year ON platform_years(year);


