<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

// Helper untuk menampilkan label asal dokumen
function format_document_origin_label($origin) {
    switch ($origin) {
        case 'imigrasi_jakarta_pusat_kemayoran':
            return 'Imigrasi Jakarta Pusat Kemayoran';
        case 'imigrasi_ulp_semanggi':
            return 'Imigrasi ULP Semanggi';
        case 'imigrasi_lounge_senayan_city':
            return 'Imigrasi Lounge Senayan City';
        default:
            return $origin ?: '-';
    }
}

// Set JSON header
header('Content-Type: application/json');

$document_id = (int)($_GET['id'] ?? 0);

if ($document_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID dokumen tidak valid']);
    exit();
}

try {
    // Get document details - hanya dokumen platform upload
    // Termasuk dokumen yang memiliki title tetapi full_name kosong (ciri dokumen platform)
    $sql = "SELECT d.*, u.full_name as created_by_name, u2.full_name as updated_by_name
            FROM documents d 
            LEFT JOIN users u ON d.created_by = u.id 
            LEFT JOIN users u2 ON d.updated_by = u2.id 
            WHERE d.id = ? 
            AND (d.description = 'PLATFORM_UPLOAD' OR d.description LIKE '%PLATFORM_UPLOAD%' OR d.description LIKE '%Dokumen diimport%' OR (d.title IS NOT NULL AND d.title != '' AND (d.full_name IS NULL OR d.full_name = '')))";
    
    $document = $db->fetch($sql, [$document_id]);
    
    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan']);
        exit();
    }
    
    // Format created date for display
    $created_at_display = '';
    if (!empty($document['created_at'])) {
        $created_at_obj = new DateTime($document['created_at']);
        $created_at_display = $created_at_obj->format('d/m/Y H:i');
    }
    
    // Generate HTML untuk dokumen platform
    $html = '
    <div class="document-details">
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Detail Dokumen Platform</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Dokumen <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['title'] ?? '-') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['full_name'] ?? '-') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">NIK</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['nik'] ?? '-') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No Passport</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['passport_number'] ?? '-') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode Lemari</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['month_number'] ?? '-') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dokumen Berasal</label>
                            <input type="text" class="form-control" value="' . e(format_document_origin_label($document['document_origin'] ?? '')) . '" readonly>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Kategori</label>
                            <div class="btn-group w-100" role="group">';
    
    $citizen_category = $document['citizen_category'] ?? 'WNI';
    $html .= '<button type="button" class="btn ' . ($citizen_category === 'WNI' ? 'btn-primary' : 'btn-outline-primary') . '" disabled>WNI</button>';
    $html .= '<button type="button" class="btn ' . ($citizen_category === 'WNA' ? 'btn-primary' : 'btn-outline-secondary') . '" disabled>WNA</button>';
    
    $html .= '
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor Dokumen</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['document_number'] ?? '-') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" rows="3" readonly>' . e($document['description'] ?? '-') . '</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file me-2"></i>File Dokumen</h5>
                    </div>
                    <div class="card-body">';
    
    // Tampilkan file dokumen platform
    if (!empty($document['file_path'])) {
        $file_view_url = 'view_file.php?id=' . $document['id'];
        $file_extension = strtolower(pathinfo($document['file_name'], PATHINFO_EXTENSION));
        $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif']);
        
        $html .= '<div class="mb-3">';
        $html .= '<div class="d-flex justify-content-between align-items-center mb-2">';
        $html .= '<div>';
        $html .= '<i class="fas fa-file me-2"></i>';
        $html .= '<strong>' . e($document['file_name'] ?? 'File') . '</strong>';
        $html .= '<small class="text-muted ms-2">(' . format_file_size($document['file_size'] ?? 0) . ')</small>';
        $html .= '</div>';
        $html .= '<a href="' . htmlspecialchars($file_view_url) . '" target="_blank" class="btn btn-sm btn-outline-primary">';
        $html .= '<i class="fas fa-eye"></i> Lihat';
        $html .= '</a>';
        $html .= '</div>';
        
        // Show image preview
        if ($is_image) {
            $html .= '<div class="mt-2 text-center">';
            $html .= '<img src="' . htmlspecialchars($file_view_url) . '" class="img-thumbnail" style="max-width: 100%; max-height: 300px; cursor: pointer;" onclick="window.open(\'' . htmlspecialchars($file_view_url) . '\', \'_blank\')" alt="' . e($document['file_name']) . '" onerror="this.style.display=\'none\'">';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<p class="text-muted">Tidak ada file dokumen</p>';
    }
    
    // Format updated date for display
    $updated_at_display = '';
    if (!empty($document['updated_at'])) {
        $updated_at_obj = new DateTime($document['updated_at']);
        $updated_at_display = $updated_at_obj->format('d/m/Y H:i');
    }
    
    $html .= '
                        <div class="border-top pt-3">
                            <div class="d-flex align-items-center text-muted fw-semibold mb-2" style="font-size: 15px;">
                                <i class="fas fa-clock me-2"></i>
                                <span>Dibuat pada: ' . e($created_at_display ?: '-') . '</span>
                            </div>';
    
    if ($updated_at_display) {
        $html .= '<div class="d-flex align-items-center text-muted fw-semibold mb-2" style="font-size: 15px;">
                                <i class="fas fa-edit me-2"></i>
                                <span>Diupdate pada: ' . e($updated_at_display) . '</span>
                            </div>';
    }
    
    $html .= '<div class="d-flex align-items-center text-muted fw-semibold mb-2" style="font-size: 15px;">
                                <i class="fas fa-user me-2"></i>
                                <span>Dibuat oleh: ' . e($document['created_by_name'] ?? '-') . '</span>
                            </div>';
    
    if (!empty($document['updated_by_name'])) {
        $html .= '<div class="d-flex align-items-center text-muted fw-semibold" style="font-size: 15px;">
                                <i class="fas fa-user-edit me-2"></i>
                                <span>Diupdate oleh: ' . e($document['updated_by_name']) . '</span>
                            </div>';
    }
    
    $html .= '</div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'document' => $document
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>

