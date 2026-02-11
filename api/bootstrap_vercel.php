<?php
/**
 * Di Vercel, daftarkan session handler ke database sebelum session_start().
 * Include file ini di awal setiap file API yang memakai session.
 */
if (getenv('VERCEL') && session_status() === PHP_SESSION_NONE) {
    if (file_exists(__DIR__ . '/../config/database.php')) {
        require_once __DIR__ . '/../config/database.php';
        if (file_exists(__DIR__ . '/../config/session_db.php')) {
            require_once __DIR__ . '/../config/session_db.php';
            register_db_session_handler();
        }
    }
}
