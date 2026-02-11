-- Tabel untuk menyimpan dokumen yang dihapus (sampah)
CREATE TABLE IF NOT EXISTS document_trash (
    id INT PRIMARY KEY AUTO_INCREMENT,
    original_document_id INT NOT NULL COMMENT 'ID dokumen asli',
    title VARCHAR(255) COMMENT 'Judul/Nama Dokumen',
    full_name VARCHAR(255) COMMENT 'Nama Lengkap',
    nik VARCHAR(16) COMMENT 'NIK',
    passport_number VARCHAR(20) COMMENT 'Nomor Paspor',
    document_number VARCHAR(50) COMMENT 'Nomor Dokumen',
    document_year INT COMMENT 'Tahun Dokumen',
    month_number VARCHAR(20) COMMENT 'Bulan/Lemari',
    locker_code VARCHAR(10) COMMENT 'Kode Lemari',
    locker_name VARCHAR(100) COMMENT 'Nama Lemari',
    citizen_category VARCHAR(100) COMMENT 'Kategori Warga Negara',
    document_origin VARCHAR(50) COMMENT 'Asal Dokumen',
    file_path VARCHAR(500) COMMENT 'Path File Dokumen',
    description TEXT COMMENT 'Deskripsi Dokumen',
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu dihapus',
    deleted_by INT COMMENT 'ID User yang menghapus',
    restore_deadline DATETIME COMMENT 'Batas waktu restore (30 hari)',
    document_data LONGTEXT COMMENT 'Data dokumen lengkap (JSON)',
    is_restored TINYINT DEFAULT 0 COMMENT 'Status apakah sudah di-restore',
    restored_at TIMESTAMP NULL COMMENT 'Waktu di-restore',
    restored_by INT COMMENT 'ID User yang restore',
    status VARCHAR(20) DEFAULT 'in_trash' COMMENT 'Status: in_trash, restored, permanently_deleted',
    
    KEY idx_deleted_at (deleted_at),
    KEY idx_restore_deadline (restore_deadline),
    KEY idx_original_document_id (original_document_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel audit untuk tracking trash operations
CREATE TABLE IF NOT EXISTS trash_audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_trash_id INT NOT NULL,
    action VARCHAR(50) COMMENT 'moved_to_trash, restored, permanently_deleted',
    user_id INT,
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    FOREIGN KEY (document_trash_id) REFERENCES document_trash(id),
    KEY idx_action_time (action_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
