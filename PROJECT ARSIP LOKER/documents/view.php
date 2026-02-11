<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login (boleh diakses admin & staff)
require_login();

// Set JSON header
header('Content-Type: application/json');

$document_id = (int)($_GET['id'] ?? 0);

// Helper format dokumen berasal
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

if ($document_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID dokumen tidak valid']);
    exit();
}

try {
    // Get document details
    $sql = "SELECT d.*, dc.category_name, u.full_name as created_by_name, u2.full_name as updated_by_name
            FROM documents d 
            LEFT JOIN document_categories dc ON d.category_id = dc.id 
            LEFT JOIN users u ON d.created_by = u.id 
            LEFT JOIN users u2 ON d.updated_by = u2.id 
            WHERE d.id = ?";
    
    $document = $db->fetch($sql, [$document_id]);
    
    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan']);
        exit();
    }
    
    // Get uploaded files from document_files table
    $files_sql = "SELECT * FROM document_files WHERE document_id = ? ORDER BY document_type";
    $uploaded_files = $db->fetchAll($files_sql, [$document_id]);
    
    // Organize files by document type
    $files_by_type = [];
    foreach ($uploaded_files as $file) {
        $files_by_type[$file['document_type']][] = $file;
    }
    
    // Document types mapping
    $document_types = [
        'KTP' => 'KTP',
        'Kartu Keluarga' => 'KARTU KELUARGA',
        'Akta Lahir' => 'AKTA LAHIR',
        'Surat Hak Asuh Anak' => 'SURAT HAK ASUH ANAK',
        'Ijazah' => 'IJAZAH',
        'Paspor' => 'PASPOR',
        'Surat Nikah' => 'SURAT NIKAH',
        'Surat Cerai' => 'SURAT CERAI'
    ];
    
    // Format birth date for display
    $birth_date_display = '';
    if (!empty($document['birth_date'])) {
        $birth_date_obj = DateTime::createFromFormat('Y-m-d', $document['birth_date']);
        if ($birth_date_obj) {
            $birth_date_display = $birth_date_obj->format('d/m/Y');
        } else {
            $birth_date_display = $document['birth_date'];
        }
    }
    
    // Generate HTML with same layout as edit.php
    $html = '
    <div class="document-details">
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Form Edit Dokumen</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['full_name'] ?? '') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">NIK <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['nik'] ?? '') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No Passport <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['passport_number'] ?? '') . '" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($birth_date_display) . '" readonly>
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode Lemari <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="' . e($document['month_number'] ?? '') . '" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Dokumen</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="' . e($document['document_year'] ?? '-') . '" readonly>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dokumen Berasal</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="' . e(format_document_origin_label($document['document_origin'] ?? '')) . '" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Surat Nikah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="' . e($document['marriage_certificate'] ?? '') . '" readonly>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Akta Lahir <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="' . e($document['birth_certificate'] ?? '') . '" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Surat Cerai <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="' . e($document['divorce_certificate'] ?? '') . '" readonly>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Surat Hak Asuh <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="' . e($document['custody_certificate'] ?? '') . '" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">KATAGORI DOKUMEN</label>
                            <div class="btn-group w-100" role="group">';
    
    $citizen_category = $document['citizen_category'] ?? 'WNI';
    $html .= '<button type="button" class="btn ' . ($citizen_category === 'WNI' ? 'btn-primary' : 'btn-outline-primary') . '" disabled>WNI</button>';
    $html .= '<button type="button" class="btn ' . ($citizen_category === 'WNA' ? 'btn-primary' : 'btn-outline-secondary') . '" disabled>WNA</button>';
    
    $html .= '
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Upload Dokumen</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">';
    
    // Display each document type with uploaded files
    foreach ($document_types as $type_key => $type_label) {
        $html .= '<div class="col-md-6 mb-3">';
        $html .= '<label class="form-label">' . $type_label . '</label>';
        $html .= '<div class="input-group">';
        $html .= '<span class="input-group-text"><i class="fas fa-paperclip"></i></span>';
        
        if (isset($files_by_type[$type_key]) && count($files_by_type[$type_key]) > 0) {
            $file_count = count($files_by_type[$type_key]);
            $html .= '<select class="form-select" disabled>';
            $html .= '<option value="' . $file_count . '">' . $file_count . ' file</option>';
            $html .= '</select>';
            
            // Add file previews
            $html .= '<div class="mt-2 w-100">';
            foreach ($files_by_type[$type_key] as $file) {
                $file_id = $file['id'];
                $file_extension = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
                $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif']);
                
                // Use view_file.php endpoint for secure file access
                // Use absolute path from root to work from both admin and staff pages
                // Get the base path from current script location
                $current_dir = dirname($_SERVER['SCRIPT_NAME']);
                // Go up one level from documents/ to get project root
                $project_root = dirname($current_dir);
                // Ensure we have a leading slash
                if ($project_root !== '/') {
                    $project_root = rtrim($project_root, '/');
                }
                
                $file_view_url = $project_root . '/documents/view_file.php?id=' . $file_id;
                
                $html .= '<div class="mb-2 p-2 border rounded">';
                $html .= '<div class="d-flex justify-content-between align-items-center">';
                $html .= '<span class="small"><i class="fas fa-file me-1"></i>' . e($file['file_name']) . '</span>';
                $html .= '<a href="' . htmlspecialchars($file_view_url) . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat/Download">';
                $html .= '<i class="fas fa-eye"></i>';
                $html .= '</a>';
                $html .= '</div>';
                
                // Show image preview using the secure endpoint
                if ($is_image) {
                    $html .= '<div class="mt-2 text-center">';
                    $html .= '<img src="' . htmlspecialchars($file_view_url) . '" class="img-thumbnail" style="max-width: 100%; max-height: 150px; cursor: pointer;" onclick="window.open(\'' . htmlspecialchars($file_view_url) . '\', \'_blank\')" alt="' . e($file['file_name']) . '" onerror="this.style.display=\'none\'">';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<select class="form-select" disabled>';
            $html .= '<option value="0">0</option>';
            $html .= '</select>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '
                        </div>
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
