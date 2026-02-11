-- Script untuk menambahkan role superadmin ke sistem

-- 1. Update enum role di tabel users untuk menambahkan 'superadmin'
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'superadmin') NOT NULL DEFAULT 'staff';

-- 2. Buat user superadmin default (password: superadmin123)
INSERT INTO users (username, password, full_name, email, role, status, created_at) 
VALUES (
    'superadmin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: superadmin123
    'Super Administrator', 
    'superadmin@example.com', 
    'superadmin', 
    'active', 
    NOW()
) ON DUPLICATE KEY UPDATE 
    role = 'superadmin',
    status = 'active';

-- 3. Update functions.php untuk mengenali role superadmin (akan dilakukan via PHP)

-- Catatan: 
-- Username: superadmin
-- Password: superadmin123
-- Silakan ganti password setelah login pertama kali