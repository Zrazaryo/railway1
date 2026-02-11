USE arsip_dokumen_imigrasi;

-- Tambahkan kolom type untuk membedakan lemari dokumen biasa dan lemari pemusnahan
ALTER TABLE lockers 
ADD COLUMN type ENUM('dokumen', 'pemusnahan') NOT NULL DEFAULT 'dokumen' AFTER max_capacity;

-- Set semua lemari yang sudah ada sebagai type 'dokumen' (default)
UPDATE lockers SET type = 'dokumen' WHERE type IS NULL OR type = '';

-- Buat index untuk mempercepat query berdasarkan type
CREATE INDEX idx_lockers_type ON lockers(type);
