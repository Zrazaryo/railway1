<?php
/**
 * CONTOH KONFIGURASI DATABASE UNTUK HOSTING
 * 
 * INSTRUKSI:
 * 1. Copy file ini menjadi database.php
 * 2. Isi dengan informasi database dari hosting Anda
 * 3. Hapus file ini setelah selesai (atau rename menjadi .bak)
 */

// Konfigurasi Database untuk Hosting
// GANTI dengan informasi database dari hosting Anda!

define('DB_HOST', 'localhost'); 
// Catatan: 
// - Untuk shared hosting biasanya 'localhost'
// - Untuk VPS/dedicated bisa berbeda (cek di info hosting)
// - Contoh: 'mysql.hosting.com' atau '127.0.0.1'

define('DB_USER', 'username_database_dari_hosting'); 
// Ganti dengan username database yang dibuat di cPanel
// Contoh: 'arsip_user' atau 'username_arsip123'

define('DB_PASS', 'password_database_dari_hosting'); 
// Ganti dengan password database yang dibuat di cPanel
// Pastikan password kuat dan aman!

define('DB_NAME', 'nama_database_dari_hosting'); 
// Ganti dengan nama database yang dibuat di cPanel
// Contoh: 'arsip_dokumen_imigrasi' atau 'username_arsip_db'
// Catatan: Beberapa hosting menambahkan prefix username, jadi formatnya bisa:
// 'username_arsip_dokumen_imigrasi'

class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Jangan tampilkan error detail di production untuk keamanan
            // Ganti dengan pesan error yang lebih aman
            die("Koneksi database gagal. Silakan hubungi administrator.");
            // Untuk debugging, bisa gunakan:
            // die("Koneksi database gagal: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Buat instance global database
$db = new Database();
?>











