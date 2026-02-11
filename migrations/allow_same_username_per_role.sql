-- Izinkan username sama untuk role berbeda (mis. aryo sebagai admin dan aryo sebagai superadmin).
-- Jalankan sekali di database (Railway/MySQL).
-- Jika error "check that column/key exists", cek nama index: SHOW INDEX FROM users; lalu DROP INDEX nama_index;
ALTER TABLE users DROP INDEX username;
ALTER TABLE users ADD UNIQUE KEY unique_username_role (username, role);
