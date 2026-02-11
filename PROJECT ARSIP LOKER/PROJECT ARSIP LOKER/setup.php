<?php
session_start();

$error_message = '';
$success_message = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = trim($_POST['db_pass'] ?? '');
    $db_name = trim($_POST['db_name'] ?? 'arsip_dokumen_imigrasi');
    
    if (empty($db_host) || empty($db_user) || empty($db_name)) {
        $error_message = 'Host, Username, dan Database Name harus diisi';
    } else {
        // Test database connection
        try {
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Test connection to specific database
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Update database configuration
            $config_content = "<?php
// Konfigurasi Database
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

class Database {
    private \$connection;
    
    public function __construct() {
        try {
            \$this->connection = new PDO(
                \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException \$e) {
            die(\"Koneksi database gagal: \" . \$e->getMessage());
        }
    }
    
    public function getConnection() {
        return \$this->connection;
    }
    
    public function prepare(\$sql) {
        return \$this->connection->prepare(\$sql);
    }
    
    public function execute(\$sql, \$params = []) {
        \$stmt = \$this->connection->prepare(\$sql);
        return \$stmt->execute(\$params);
    }
    
    public function fetch(\$sql, \$params = []) {
        \$stmt = \$this->connection->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt->fetch();
    }
    
    public function fetchAll(\$sql, \$params = []) {
        \$stmt = \$this->connection->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt->fetchAll();
    }
    
    public function lastInsertId() {
        return \$this->connection->lastInsertId();
    }
}

// Buat instance global database
\$db = new Database();
?>";
            
            if (file_put_contents('config/database.php', $config_content)) {
                // Check if tables already exist
                $tables_check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
                
                if (!$tables_check) {
                    // Import database schema only if tables don't exist
                    $sql_file = 'config/init_database.sql';
                    if (file_exists($sql_file)) {
                        $sql_content = file_get_contents($sql_file);
                        
                        // Remove CREATE DATABASE line since we already created it
                        $sql_content = preg_replace('/CREATE DATABASE.*?;/i', '', $sql_content);
                        $sql_content = preg_replace('/USE.*?;/i', '', $sql_content);
                        
                        // Split by semicolon and execute each statement
                        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
                        
                        foreach ($statements as $statement) {
                            if (!empty($statement)) {
                                try {
                                    $pdo->exec($statement);
                                } catch (PDOException $e) {
                                    // Skip if table already exists
                                    if (strpos($e->getMessage(), 'already exists') === false) {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                }
                
                if (!$tables_check) {
                    $success_message = 'Database berhasil dikonfigurasi! Silakan login dengan username: admin, password: password';
                } else {
                    $success_message = 'Database sudah ada dan berhasil dikonfigurasi! Silakan login dengan username: admin, password: password';
                }
                
                // Redirect after 3 seconds
                header("refresh:3;url=index.php");
                
            } else {
                $error_message = 'Gagal menyimpan konfigurasi database';
            }
            
        } catch (PDOException $e) {
            $error_message = 'Koneksi database gagal: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Side Image -->
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="text-center text-white">
                    <i class="fas fa-database fa-5x mb-4"></i>
                    <h2 class="mb-3">Setup Database</h2>
                    <h4 class="mb-4">Sistem Arsip Dokumen</h4>
                    <p class="lead">Konfigurasi database untuk pertama kali</p>
                </div>
            </div>
            
            <!-- Setup Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="login-form-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-cog fa-3x text-primary mb-3"></i>
                        <h3>Konfigurasi Database</h3>
                        <p class="text-muted">Masukkan informasi koneksi database</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                            <div class="mt-2">
                                <small>Anda akan diarahkan ke halaman login dalam 3 detik...</small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="login-form">
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Database Host</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-server"></i></span>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                       value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_user" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="db_user" name="db_user" 
                                       value="<?php echo htmlspecialchars($_POST['db_user'] ?? 'root'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_pass" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                       value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>">
                            </div>
                            <div class="form-text">Kosongkan jika tidak ada password</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_name" class="form-label">Nama Database</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-database"></i></span>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                       value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'arsip_dokumen_imigrasi'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between gap-2">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Setup Database
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Informasi:</h6>
                            <ul class="mb-0 small">
                                <li>Database akan dibuat otomatis jika belum ada</li>
                                <li>Semua tabel dan data default akan diimport</li>
                                <li>Login default: admin / password</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
