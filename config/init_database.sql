-- Database untuk Sistem Arsip Dokumen Kantor Imigrasi
CREATE DATABASE IF NOT EXISTS arsip_dokumen_imigrasi;
USE arsip_dokumen_imigrasi;

-- Hapus tabel jika sudah ada (untuk import ulang)
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS document_categories;
DROP TABLE IF EXISTS users;

-- Tabel Users (Admin dan Staff)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('superadmin', 'admin', 'staff') NOT NULL DEFAULT 'staff',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Kategori Dokumen
CREATE TABLE document_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Dokumen
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    status ENUM('active', 'archived', 'deleted') NOT NULL DEFAULT 'active',
    created_by INT NOT NULL,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES document_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel Log Aktivitas
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    document_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL
);

-- Insert data admin dan staff default (jika belum ada)
INSERT IGNORE INTO users (username, password, full_name, email, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Sistem', 'admin@imigrasi.go.id', 'admin', 'active'),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Imigrasi', 'staff@imigrasi.go.id', 'staff', 'active');

-- Insert kategori dokumen default (jika belum ada)
INSERT IGNORE INTO document_categories (category_name, description) VALUES
('Paspor', 'Dokumen paspor dan berkas terkait'),
('Visa', 'Dokumen visa dan aplikasi visa'),
('Izin Tinggal', 'Dokumen izin tinggal dan perpanjangan'),
('Dokumen Perjalanan', 'Dokumen perjalanan dan perizinan'),
('Keimigrasian Umum', 'Dokumen keimigrasian lainnya'),
('Laporan', 'Laporan dan statistik'),
('Surat Menyurat', 'Surat menyurat resmi'),
('Arsip Internal', 'Dokumen arsip internal kantor');

-- Index untuk optimasi query
-- Catatan: Karena tabel sudah di-drop di atas, index juga ikut terhapus
-- Jadi langsung buat index baru tanpa perlu DROP dulu
CREATE INDEX idx_documents_category ON documents(category_id);
CREATE INDEX idx_documents_created_by ON documents(created_by);
CREATE INDEX idx_documents_status ON documents(status);
CREATE INDEX idx_documents_created_at ON documents(created_at);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);
