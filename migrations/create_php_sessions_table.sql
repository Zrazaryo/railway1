-- Tabel session untuk PHP di Vercel (serverless)
-- Jalankan sekali di database Railway Anda.
CREATE TABLE IF NOT EXISTS php_sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT,
    last_activity INT UNSIGNED NOT NULL,
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
