<?php
/**
 * Session handler berbasis database untuk Vercel (serverless).
 * Session file tidak persist di serverless; data disimpan di MySQL.
 * Panggil register_db_session_handler() sebelum session_start().
 */
if (!defined('DB_HOST') || !class_exists('Database')) {
    return;
}

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $db;
    private $table = 'php_sessions';
    private $maxLifetime;

    public function __construct() {
        global $db;
        $this->db = $db;
        $this->maxLifetime = (int) ini_get('session.gc_maxlifetime');
        if ($this->maxLifetime <= 0) {
            $this->maxLifetime = 1440;
        }
    }

    public function open($path, $name): bool {
        try {
            $conn = $this->db->getConnection();
            $conn->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
                id VARCHAR(128) PRIMARY KEY,
                data TEXT,
                last_activity INT UNSIGNED NOT NULL,
                INDEX idx_last_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string|false {
        try {
            $row = $this->db->fetch(
                "SELECT data FROM {$this->table} WHERE id = ? AND last_activity > ?",
                [$id, time() - $this->maxLifetime]
            );
            return $row ? (string) $row['data'] : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public function write($id, $data): bool {
        try {
            $this->db->execute(
                "REPLACE INTO {$this->table} (id, data, last_activity) VALUES (?, ?, ?)",
                [$id, $data, time()]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function destroy($id): bool {
        try {
            $this->db->execute("DELETE FROM {$this->table} WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function gc($max_lifetime): int|false {
        try {
            $this->db->execute("DELETE FROM {$this->table} WHERE last_activity < ?", [time() - $max_lifetime]);
            return 0;
        } catch (Exception $e) {
            return false;
        }
    }
}

function register_db_session_handler() {
    $handler = new DatabaseSessionHandler();
    session_set_save_handler($handler, true);
}
