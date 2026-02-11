<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek login
require_login();

// Auto-add profile columns if not exist
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'birth_date'");
    if (empty($columns)) {
        $db->execute("ALTER TABLE users 
            ADD COLUMN birth_date DATE NULL AFTER email,
            ADD COLUMN address TEXT NULL AFTER birth_date,
            ADD COLUMN phone_number VARCHAR(20) NULL AFTER address,
            ADD COLUMN division_position VARCHAR(100) NULL AFTER phone_number,
            ADD COLUMN bio_status TEXT NULL AFTER division_position,
            ADD COLUMN profile_picture VARCHAR(255) NULL AFTER bio_status");
    }
} catch (Exception $e) {
    // Ignore if columns already exist
}

$error_message = '';
$success_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $birth_date = sanitize_input($_POST['birth_date'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $phone_number = sanitize_input($_POST['phone_number'] ?? '');
    $division_position = sanitize_input($_POST['division_position'] ?? '');
    $bio_status = sanitize_input($_POST['bio_status'] ?? '');
    
    if (empty($full_name)) {
        $error_message = 'Nama lengkap harus diisi';
    } elseif (empty($email)) {
        $error_message = 'Email harus diisi';
    } elseif (!validate_email($email)) {
        $error_message = 'Format email tidak valid';
    } else {
        try {
            // Check if email already exists for another user
            $existing_user = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $_SESSION['user_id']]);
            if ($existing_user) {
                $error_message = 'Email sudah digunakan oleh user lain';
            } else {
                // Convert empty date to NULL
                $birth_date = !empty($birth_date) ? $birth_date : null;
                
                // Update profile
                $db->execute("UPDATE users SET 
                    full_name = ?, 
                    email = ?, 
                    birth_date = ?,
                    address = ?,
                    phone_number = ?,
                    division_position = ?,
                    bio_status = ?
                    WHERE id = ?", 
                    [$full_name, $email, $birth_date, $address, $phone_number, $division_position, $bio_status, $_SESSION['user_id']]);
                
                // Update session
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                $success_message = 'Profil berhasil diperbarui';
                log_activity($_SESSION['user_id'], 'UPDATE_PROFILE', 'Profil berhasil diperbarui');
            }
        } catch (Exception $e) {
            $error_message = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowed_types) && $file['size'] <= 5 * 1024 * 1024) { // Max 5MB
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Delete old profile picture if exists
                $old_picture = $db->fetch("SELECT profile_picture FROM users WHERE id = ?", [$_SESSION['user_id']]);
                if ($old_picture && !empty($old_picture['profile_picture'])) {
                    $old_path = $old_picture['profile_picture'];
                    // Try both relative and absolute path
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    } elseif (file_exists(__DIR__ . '/' . $old_path)) {
                        unlink(__DIR__ . '/' . $old_path);
                    }
                }
                
                // Store relative path from root
                $relative_path = $filepath;
                $db->execute("UPDATE users SET profile_picture = ? WHERE id = ?", [$relative_path, $_SESSION['user_id']]);
                
                // Update session
                $_SESSION['profile_picture'] = $relative_path;
                
                // Redirect to refresh page and show photo
                header('Location: profile.php?success=photo_uploaded');
                exit();
            } else {
                $error_message = 'Gagal mengupload foto profil';
            }
        } else {
            $error_message = 'Format file tidak didukung atau ukuran file terlalu besar (max 5MB)';
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success']) && $_GET['success'] === 'photo_uploaded') {
    $success_message = 'Foto profil berhasil diupload';
}

// Get current user info
try {
    $user_info = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    
    // Get user statistics
    $total_documents = $db->fetch("SELECT COUNT(*) as count FROM documents WHERE created_by = ? AND status = 'active'", [$_SESSION['user_id']])['count'];
    
    // Get recent activity count
    $recent_activities = $db->fetch("SELECT COUNT(*) as count FROM activity_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$_SESSION['user_id']])['count'];
    
} catch (Exception $e) {
    $error_message = 'Error mengambil data user: ' . $e->getMessage();
    $user_info = null;
    $total_documents = 0;
    $recent_activities = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .profile-container {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            border: 2px solid #e0e7ff;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin: 0 auto 20px;
            border: 4px solid #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-avatar i {
            font-size: 60px;
            color: white;
        }
        .avatar-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #28a745;
            border: 3px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .avatar-upload-btn:hover {
            background: #218838;
        }
        .profile-form-field {
            border: none;
            border-bottom: 2px solid #dee2e6;
            border-radius: 0;
            padding: 10px 0;
            background: transparent;
        }
        .profile-form-field:focus {
            border-bottom-color: #667eea;
            box-shadow: none;
            background: transparent;
        }
        .profile-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        .join-date {
            color: #6c757d;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user me-2"></i>
                        Profil Saya
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
                
                <?php if ($user_info): ?>
                    <div class="profile-container">
                        <div class="row">
                            <!-- Left Side - Avatar -->
                            <div class="col-md-4 text-center">
                                <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                    <div class="profile-avatar">
                                        <?php if (!empty($user_info['profile_picture'])): 
                                            // Ensure path is absolute from root
                                            $avatar_img_path = (strpos($user_info['profile_picture'], '/') === 0 || strpos($user_info['profile_picture'], 'http') === 0) 
                                                ? $user_info['profile_picture'] 
                                                : '/PROJECT ARSIP LOKER/' . ltrim($user_info['profile_picture'], '/');
                                        ?>
                                            <img src="<?php echo e($avatar_img_path); ?>" alt="Profile Picture" 
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <i class="fas fa-user" style="display: none;"></i>
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                        <label for="profile_picture" class="avatar-upload-btn">
                                            <i class="fas fa-plus text-white"></i>
                                        </label>
                                        <input type="file" id="profile_picture" name="profile_picture" 
                                               accept="image/*" style="display: none;" 
                                               onchange="document.getElementById('avatarForm').submit();">
                                    </div>
                                </form>
                                
                                <div class="join-date">
                                    <i class="fas fa-calendar me-2"></i>
                                    Bergabung pada: <strong><?php echo format_date_indonesia($user_info['created_at']); ?></strong>
                                </div>
                                
                                <button type="submit" form="profileForm" class="btn btn-success mt-4 w-100">
                                    <i class="fas fa-save me-2"></i>Simpan
                                </button>
                            </div>
                            
                            <!-- Right Side - Form -->
                            <div class="col-md-8">
                                <form method="POST" id="profileForm">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="profile-label">USERNAME :</label>
                                            <input type="text" class="form-control profile-form-field" 
                                                   value="<?php echo e($user_info['username']); ?>" 
                                                   disabled>
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="profile-label">NAMA LENGKAP :</label>
                                            <input type="text" class="form-control profile-form-field" 
                                                   name="full_name" 
                                                   value="<?php echo e($user_info['full_name'] ?? ''); ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="profile-label">TANGGAL LAHIR :</label>
                                            <input type="date" class="form-control profile-form-field" 
                                                   name="birth_date" 
                                                   value="<?php echo !empty($user_info['birth_date']) ? date('Y-m-d', strtotime($user_info['birth_date'])) : ''; ?>">
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="profile-label">ALAMAT :</label>
                                            <textarea class="form-control profile-form-field" 
                                                      name="address" 
                                                      rows="2"><?php echo e($user_info['address'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="profile-label">EMAIL :</label>
                                            <input type="email" class="form-control profile-form-field" 
                                                   name="email" 
                                                   value="<?php echo e($user_info['email'] ?? ''); ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="profile-label">NOMER TELEPON :</label>
                                            <input type="text" class="form-control profile-form-field" 
                                                   name="phone_number" 
                                                   value="<?php echo e($user_info['phone_number'] ?? ''); ?>" 
                                                   placeholder="08xxxxxxxxxx">
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="profile-label">DEVISI/JABATAN :</label>
                                            <input type="text" class="form-control profile-form-field" 
                                                   name="division_position" 
                                                   value="<?php echo e($user_info['division_position'] ?? ''); ?>" 
                                                   placeholder="Contoh: Staff Imigrasi">
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="profile-label">BIO STATUS :</label>
                                            <textarea class="form-control profile-form-field" 
                                                      name="bio_status" 
                                                      rows="3" 
                                                      placeholder="Tuliskan bio atau status Anda..."><?php echo e($user_info['bio_status'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="update_profile" value="1">
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
