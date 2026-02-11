<?php
// Konfigurasi Database - Railway MySQL (env di Vercel, fallback lokal)
$mysqlUrl = getenv('MYSQL_PUBLIC_URL');
if ($mysqlUrl && preg_match('#^mysql://([^:]+):([^@]+)@([^:]+):(\d+)/(.+)$#', $mysqlUrl, $m)) {
    define('DB_USER', urldecode($m[1]));
    define('DB_PASS', urldecode($m[2]));
    define('DB_HOST', $m[3]);
    define('DB_PORT', $m[4]);
    define('DB_NAME', $m[5]);
} elseif (getenv('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_PORT', getenv('DB_PORT') ?: '49077');
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    define('DB_NAME', getenv('DB_NAME'));
} else {
    define('DB_HOST', 'switchyard.proxy.rlwy.net');
    define('DB_PORT', '49077');
    define('DB_USER', 'root');
    define('DB_PASS', 'vqlLNfYxbBqPWYIYzFclDDrFsSuDUgTZ');
    define('DB_NAME', 'railway');
}

class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
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