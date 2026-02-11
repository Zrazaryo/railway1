<?php
session_start();
ob_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login dan role admin
require_login();
if (!is_admin()) {
    if (ob_get_level()) ob_end_clean();
    header('Location: index.php?error=access_denied');
    exit();
}

function format_document_origin_export($origin) {
    switch ($origin) {
        case 'imigrasi_jakarta_pusat_kemayoran':
            return 'Imigrasi Jakarta Pusat Kemayoran';
        case 'imigrasi_ulp_semanggi':
            return 'Imigrasi ULP Semanggi';
        case 'imigrasi_lounge_senayan_city':
            return 'Imigrasi Lounge Senayan City';
        default:
            return $origin ?: '';
    }
}

// document_type -> nama file di ZIP (tanpa ekstensi)
function document_type_to_slug($document_type) {
    $map = [
        'KTP' => 'ktp',
        'Kartu Keluarga' => 'kartu_keluarga',
        'Akta Lahir' => 'akta_lahir',
        'Surat Hak Asuh Anak' => 'surat_hak_asuh_anak',
        'Ijazah' => 'ijazah',
        'Paspor' => 'paspor',
        'Surat Nikah' => 'surat_nikah',
        'Surat Cerai' => 'surat_cerai',
    ];
    return $map[$document_type] ?? strtolower(preg_replace('/\s+/', '_', $document_type));
}

try {
    $export_all = isset($_GET['all']) && $_GET['all'] == '1';
    $selected_ids = isset($_GET['ids']) ? (array)$_GET['ids'] : [];
    $with_files = isset($_GET['with_files']) && $_GET['with_files'] == '1';
    
    if ($export_all) {
        $sql = "SELECT d.id, d.document_number, d.full_name, d.nik, d.passport_number, d.birth_date,
                d.month_number, d.document_order_number, d.document_year, d.document_origin,
                d.marriage_certificate, d.birth_certificate, d.divorce_certificate, d.custody_certificate,
                d.citizen_category, d.created_at, u.full_name as created_by_name, u.username as created_by_username
                FROM documents d LEFT JOIN users u ON d.created_by = u.id
                WHERE d.status = 'active' ORDER BY d.created_at DESC";
        $documents = $db->fetchAll($sql);
        $filename_base = 'export_semua_dokumen_' . date('Y-m-d_His');
    } else {
        if (empty($selected_ids)) {
            if (ob_get_level()) ob_end_clean();
            header('Location: index.php?error=no_selection');
            exit();
        }
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $sql = "SELECT d.id, d.document_number, d.full_name, d.nik, d.passport_number, d.birth_date,
                d.month_number, d.document_order_number, d.document_year, d.document_origin,
                d.marriage_certificate, d.birth_certificate, d.divorce_certificate, d.custody_certificate,
                d.citizen_category, d.created_at, u.full_name as created_by_name, u.username as created_by_username
                FROM documents d LEFT JOIN users u ON d.created_by = u.id
                WHERE d.id IN ($placeholders) AND d.status = 'active' ORDER BY d.created_at DESC";
        $documents = $db->fetchAll($sql, $selected_ids);
        $filename_base = 'export_dokumen_terpilih_' . date('Y-m-d_His');
    }
    
    if (empty($documents)) {
        if (ob_get_level()) ob_end_clean();
        header('Location: index.php?error=no_data');
        exit();
    }
    
    if ($with_files && class_exists('ZipArchive')) {
        // Export ZIP: data.csv + files/0/, files/1/, ...
        if (ob_get_level()) ob_end_clean();
        $zip_filename = $filename_base . '.zip';
        $zip = new ZipArchive();
        $tmp_zip = tempnam(sys_get_temp_dir(), 'arsip_export_');
        if ($zip->open($tmp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            header('Location: index.php?error=export_failed&message=' . urlencode('Gagal membuat file ZIP'));
            exit();
        }
        
        // CSV ke memory (pakai fputcsv agar escape konsisten)
        $csv_mem = fopen('php://memory', 'r+');
        fprintf($csv_mem, "\xEF\xBB\xBF");
        $headers = ['No','Nomor Dokumen','Nama Lengkap','NIK','No Passport','Tanggal Lahir','No Bulan Pemohon/Kode Lemari','Urutan Dokumen','Tahun','Dokumen Berasal','No Surat Nikah','No Akta Lahir','No Surat Cerai','No Surat Hak Asuh','Kategori','Dibuat Oleh','Username Pembuat','Tanggal Dibuat'];
        fputcsv($csv_mem, $headers, ',', '"', '\\');
        $no = 1;
        foreach ($documents as $doc) {
            $row = [
                $no++,
                $doc['document_number'] ?? '',
                $doc['full_name'] ?? '',
                $doc['nik'] ?? '',
                $doc['passport_number'] ?? '',
                !empty($doc['birth_date']) ? date('d/m/Y', strtotime($doc['birth_date'])) : '',
                $doc['month_number'] ?? '',
                $doc['document_order_number'] ?? '',
                $doc['document_year'] ?? '',
                format_document_origin_export($doc['document_origin'] ?? ''),
                $doc['marriage_certificate'] ?? '',
                $doc['birth_certificate'] ?? '',
                $doc['divorce_certificate'] ?? '',
                $doc['custody_certificate'] ?? '',
                $doc['citizen_category'] ?? 'WNI',
                $doc['created_by_name'] ?? '',
                $doc['created_by_username'] ?? '',
                !empty($doc['created_at']) ? format_date_indonesia($doc['created_at'], true) : ''
            ];
            fputcsv($csv_mem, $row, ',', '"', '\\');
        }
        rewind($csv_mem);
        $zip->addFromString('data.csv', stream_get_contents($csv_mem));
        fclose($csv_mem);
        
        // File lampiran per dokumen (urutan = index 0, 1, 2, ...)
        $doc_dir = __DIR__ . '/../';
        foreach ($documents as $index => $doc) {
            $files = $db->fetchAll("SELECT id, document_type, file_path, file_name, file_size, file_type, file_content FROM document_files WHERE document_id = ? AND file_path != 'STATUS_ONLY'", [$doc['id']]);
            foreach ($files as $f) {
                $ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION)) ?: 'bin';
                $slug = document_type_to_slug($f['document_type']);
                $zip_name = "files/{$index}/{$slug}.{$ext}";
                $content = null;
                if (!empty($f['file_content'])) {
                    $content = $f['file_content'];
                } else {
                    $base = basename($f['file_path']);
                    $paths = [
                        __DIR__ . '/uploads/' . $base,
                        __DIR__ . '/uploads/' . $f['file_name'],
                        $doc_dir . 'uploads/' . $base,
                        $doc_dir . ltrim($f['file_path'], '/'),
                    ];
                    foreach ($paths as $p) {
                        $p = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $p);
                        if (file_exists($p) && is_file($p)) {
                            $content = file_get_contents($p);
                            break;
                        }
                    }
                }
                if ($content !== null) {
                    $zip->addFromString($zip_name, $content);
                }
            }
        }
        
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($tmp_zip));
        readfile($tmp_zip);
        @unlink($tmp_zip);
        $count = count($documents);
        log_activity($_SESSION['user_id'], $export_all ? 'EXPORT_ALL_DOCUMENTS' : 'EXPORT_SELECTED_DOCUMENTS', "Export dokumen dengan lampiran ($count dokumen)", null);
        exit();
    }
    
    // Export CSV saja (tanpa file)
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename_base . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    $headers = ['No','Nomor Dokumen','Nama Lengkap','NIK','No Passport','Tanggal Lahir','No Bulan Pemohon/Kode Lemari','Urutan Dokumen','Tahun','Dokumen Berasal','No Surat Nikah','No Akta Lahir','No Surat Cerai','No Surat Hak Asuh','Kategori','Dibuat Oleh','Username Pembuat','Tanggal Dibuat'];
    fputcsv($output, $headers, ',', '"', '\\');
    $no = 1;
    foreach ($documents as $doc) {
        $row = [
            $no++,
            $doc['document_number'] ?? '',
            $doc['full_name'] ?? '',
            $doc['nik'] ?? '',
            $doc['passport_number'] ?? '',
            !empty($doc['birth_date']) ? date('d/m/Y', strtotime($doc['birth_date'])) : '',
            $doc['month_number'] ?? '',
            $doc['document_order_number'] ?? '',
            $doc['document_year'] ?? '',
            format_document_origin_export($doc['document_origin'] ?? ''),
            $doc['marriage_certificate'] ?? '',
            $doc['birth_certificate'] ?? '',
            $doc['divorce_certificate'] ?? '',
            $doc['custody_certificate'] ?? '',
            $doc['citizen_category'] ?? 'WNI',
            $doc['created_by_name'] ?? '',
            $doc['created_by_username'] ?? '',
            !empty($doc['created_at']) ? format_date_indonesia($doc['created_at'], true) : ''
        ];
        fputcsv($output, $row, ',', '"', '\\');
    }
    fclose($output);
    $count = count($documents);
    log_activity($_SESSION['user_id'], $export_all ? 'EXPORT_ALL_DOCUMENTS' : 'EXPORT_SELECTED_DOCUMENTS', $export_all ? "Export semua dokumen ($count dokumen)" : "Export dokumen terpilih ($count dokumen)", null);
    exit();
    
} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    header('Location: index.php?error=export_failed&message=' . urlencode($e->getMessage()));
    exit();
}
?>


