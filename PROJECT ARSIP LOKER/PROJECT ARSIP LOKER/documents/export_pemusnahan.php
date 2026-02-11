<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
if (!is_admin()) {
    header('Location: pemusnahan.php?error=access_denied');
    exit();
}

try {
    $export_all = isset($_GET['all']) && $_GET['all'] == '1';
    $selected_ids = $_GET['ids'] ?? [];

    if ($export_all) {
        $sql = "SELECT 
                    d.id,
                    d.document_number,
                    d.full_name,
                    d.nik,
                    d.passport_number,
                    d.birth_date,
                    d.month_number,
                    d.document_order_number,
                    d.document_year,
                    d.document_origin,
                    d.marriage_certificate,
                    d.birth_certificate,
                    d.divorce_certificate,
                    d.custody_certificate,
                    d.citizen_category,
                    d.created_at,
                    u.full_name as created_by_name,
                    u.username as created_by_username
                FROM documents d
                LEFT JOIN users u ON d.created_by = u.id
                WHERE d.status = 'deleted'
                ORDER BY d.created_at DESC";
        $documents = $db->fetchAll($sql);
        $filename = 'export_pemusnahan_semua_' . date('Y-m-d_His') . '.csv';
    } else {
        if (empty($selected_ids)) {
            header('Location: pemusnahan.php?error=no_selection');
            exit();
        }
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $sql = "SELECT 
                    d.id,
                    d.document_number,
                    d.full_name,
                    d.nik,
                    d.passport_number,
                    d.birth_date,
                    d.month_number,
                    d.document_order_number,
                    d.document_year,
                    d.document_origin,
                    d.marriage_certificate,
                    d.birth_certificate,
                    d.divorce_certificate,
                    d.custody_certificate,
                    d.citizen_category,
                    d.created_at,
                    u.full_name as created_by_name,
                    u.username as created_by_username
                FROM documents d
                LEFT JOIN users u ON d.created_by = u.id
                WHERE d.id IN ($placeholders) AND d.status = 'deleted'
                ORDER BY d.created_at DESC";
        $documents = $db->fetchAll($sql, $selected_ids);
        $filename = 'export_pemusnahan_terpilih_' . date('Y-m-d_His') . '.csv';
    }

    if (empty($documents)) {
        header('Location: pemusnahan.php?error=no_data');
        exit();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    $headers = [
        'No',
        'Nomor Dokumen',
        'Nama Lengkap',
        'NIK',
        'No Passport',
        'Tanggal Lahir',
        'No Bulan Pemohon/Kode Lemari',
        'Urutan Dokumen',
        'Tahun',
        'Dokumen Berasal',
        'No Surat Nikah',
        'No Akta Lahir',
        'No Surat Cerai',
        'No Surat Hak Asuh',
        'Kategori',
        'Dibuat Oleh',
        'Username Pembuat',
        'Tanggal Dibuat'
    ];
    fputcsv($output, $headers);

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
        fputcsv($output, $row);
    }

    fclose($output);

    $count = count($documents);
    $action = $export_all ? 'EXPORT_ALL_PEMUSNAHAN' : 'EXPORT_SELECTED_PEMUSNAHAN';
    $description = $export_all 
        ? "Export semua dokumen pemusnahan ($count dokumen)" 
        : "Export dokumen pemusnahan terpilih ($count dokumen)";
    log_activity($_SESSION['user_id'], $action, $description);

    exit();

} catch (Exception $e) {
    header('Location: pemusnahan.php?error=export_failed&message=' . urlencode($e->getMessage()));
    exit();
}

