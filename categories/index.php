<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role admin
require_login();
if (!is_admin()) {
    header('Location: ../index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $category_name = sanitize_input($_POST['category_name'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            
            if (empty($category_name)) {
                $error_message = 'Nama kategori harus diisi';
            } else {
                try {
                    $db->execute("INSERT INTO document_categories (category_name, description) VALUES (?, ?)", 
                        [$category_name, $description]);
                    $success_message = 'Kategori berhasil ditambahkan';
                    log_activity($_SESSION['user_id'], 'CATEGORY_CREATE', "Menambahkan kategori: $category_name");
                } catch (Exception $e) {
                    $error_message = 'Error: ' . $e->getMessage();
                }
            }
        }
        
        if ($action === 'delete') {
            $category_id = intval($_POST['category_id'] ?? 0);
            
            try {
                // Check if category is being used
                $used = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE category_id = ?", [$category_id]);
                
                if ($used['count'] > 0) {
                    $error_message = 'Kategori tidak dapat dihapus karena masih digunakan oleh dokumen';
                } else {
                    $db->execute("DELETE FROM document_categories WHERE id = ?", [$category_id]);
                    $success_message = 'Kategori berhasil dihapus';
                    log_activity($_SESSION['user_id'], 'CATEGORY_DELETE', "Menghapus kategori ID: $category_id");
                }
            } catch (Exception $e) {
                $error_message = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get all categories
try {
    $categories = $db->fetchAll("SELECT * FROM document_categories ORDER BY category_name");
} catch (Exception $e) {
    $error_message = 'Error mengambil data kategori: ' . $e->getMessage();
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Dokumen - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-folder me-2"></i>
                        Kategori Dokumen
                    </h1>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo e($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo e($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Form Tambah Kategori -->
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-plus me-2"></i>
                                    Tambah Kategori
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <div class="mb-3">
                                        <label for="category_name" class="form-label">Nama Kategori *</label>
                                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Deskripsi</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>Simpan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Daftar Kategori -->
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Daftar Kategori (<?php echo count($categories); ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nama Kategori</th>
                                                <th>Deskripsi</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td><?php echo $category['id']; ?></td>
                                                    <td><strong><?php echo e($category['category_name']); ?></strong></td>
                                                    <td><?php echo e($category['description']); ?></td>
                                                    <td>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus kategori ini?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($categories)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Belum ada kategori</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
























