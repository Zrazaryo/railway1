<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Hanya admin
require_admin();

$error_message = '';
$success_message = '';
$imported_count = 0;
$failed_count = 0;
$failed_rows = [];
$skipped_count = 0;
$skipped_rows = [];
$import_year = null;

if (isset($_POST['import_year']) && $_POST['import_year'] !== '') {
    $import_year = (int)$_POST['import_year'];
    if ($import_year < 1900 || $import_year > 2100) {
        $error_message = 'Tahun import tidak valid. Gunakan format 4 digit, contoh: 2025.';
    }
}

if (empty($error_message) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    try {
        $file = $_FILES['import_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error upload file: ' . $file['error']);
        }
        
        $allowed_extensions = ['csv', 'xlsx', 'xls'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Format file tidak didukung. Gunakan CSV, XLSX, atau XLS');
        }
        
        $file_path = $file['tmp_name'];
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            throw new Exception('Gagal membaca file');
        }
        
        // Skip BOM jika ada
        $first_line = fgets($handle);
        if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
            $first_line = substr($first_line, 3);
        }
        rewind($handle);
        if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
            fseek($handle, 3);
        }
        
        // Header
        $headers = fgetcsv($handle);
        if ($headers === false) {
            throw new Exception('File kosong atau format tidak valid');
        }
        
        // Clean headers: remove BOM, trim, lowercase
        $headers = array_map(function($h) {
            // Remove BOM if present
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            // Trim whitespace
            $h = trim($h);
            // Convert to lowercase
            return strtolower($h);
        }, $headers);
        
        $column_map = [
            'nama lengkap' => 'full_name',
            'nik' => 'nik',
            'no passport' => 'passport_number',
            'passport' => 'passport_number',
            'nomor passport' => 'passport_number',
            'kode lemari' => 'month_number',
            'kode rak' => 'month_number',
            'nama rak' => 'month_number',
            'bulan pemohon' => 'month_number',
            'no bulan pemohon' => 'month_number',
            'no bulan pemohon/kode lemari' => 'month_number',
            'no bulan pemohon\\kode lemari' => 'month_number', // untuk CSV yang escape slash
            'no bulan pemohon/kode rak' => 'month_number',
            'urutan dokumen' => 'document_order_number',
            'tahun' => 'document_year',
            'year' => 'document_year',
            'tahun dokumen' => 'document_year',
            'dokumen berasal' => 'document_origin',
            'asal dokumen' => 'document_origin',
            'kategori' => 'citizen_category',
            'kewarganegaraan' => 'citizen_category',
            'tanggal lahir' => 'birth_date',
            'dibuat oleh' => 'original_created_by',
            'staff' => 'original_created_by',
            'pembuat' => 'original_created_by',
            'created by' => 'original_created_by',
            'username' => 'original_created_by',
            'user' => 'original_created_by',
        ];
        
        $field_map = [];
        foreach ($headers as $index => $header) {
            $header_clean = trim(strtolower($header));
            if (isset($column_map[$header_clean])) {
                $field_map[$column_map[$header_clean]] = $index;
            }
        }
        
        $required_fields = ['full_name', 'nik', 'passport_number'];
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($field_map[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            $found_headers = implode(', ', array_map(function($idx) use ($headers) {
                return $headers[$idx] ?? 'unknown';
            }, array_keys($headers)));
            throw new Exception('Kolom wajib tidak ditemukan: ' . implode(', ', $missing_fields) . '. Header yang ditemukan: ' . $found_headers);
        }
        
        $row_number = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            if (empty(array_filter($row))) {
                continue;
            }
            
            try {
                // Pastikan row memiliki cukup kolom
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                }
                
                $full_name = isset($field_map['full_name']) && isset($row[$field_map['full_name']]) ? trim($row[$field_map['full_name']]) : '';
                $nik = isset($field_map['nik']) && isset($row[$field_map['nik']]) ? trim($row[$field_map['nik']]) : '';
                $passport_number = isset($field_map['passport_number']) && isset($row[$field_map['passport_number']]) ? trim($row[$field_map['passport_number']]) : '';
                
                // Cari pembuat asli dokumen
                $original_created_by = null;
                $original_created_at = null;
                
                if (isset($field_map['original_created_by']) && isset($row[$field_map['original_created_by']])) {
                    $creator_info = trim($row[$field_map['original_created_by']]);
                    if (!empty($creator_info)) {
                        // Coba cari user berdasarkan username, full_name, atau email
                        $user = $db->fetch("
                            SELECT id, full_name, created_at 
                            FROM users 
                            WHERE username = ? OR full_name = ? OR email = ? 
                            LIMIT 1
                        ", [$creator_info, $creator_info, $creator_info]);
                        
                        if ($user) {
                            $original_created_by = $user['id'];
                            $original_created_at = $user['created_at']; // Gunakan tanggal bergabung user sebagai default
                        }
                    }
                }
                
                if (empty($full_name) || empty($nik) || empty($passport_number)) {
                    $failed_rows[] = [
                        'row' => $row_number,
                        'reason' => 'Data wajib kosong (Nama, NIK, atau Passport)',
                        'data' => $full_name
                    ];
                    $failed_count++;
                    continue;
                }
                
                // Tanggal lahir (opsional)
                $birth_date = null;
                if (isset($field_map['birth_date']) && isset($row[$field_map['birth_date']])) {
                    $date_str = trim($row[$field_map['birth_date']]);
                    if ($date_str !== '') {
                        // Coba beberapa format umum: d/m/Y, d-m-Y, Y-m-d
                        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d'];
                        foreach ($formats as $fmt) {
                            $dt = DateTime::createFromFormat($fmt, $date_str);
                            if ($dt && $dt->format($fmt) === $date_str) {
                                $birth_date = $dt->format('Y-m-d');
                                break;
                            }
                        }
                    }
                }

                $month_number = null;
                if (isset($field_map['month_number']) && isset($row[$field_map['month_number']])) {
                    $month_number = trim($row[$field_map['month_number']]);
                    if (empty($month_number)) {
                        $month_number = null;
                    }
                }
                
                $document_order_number = null;
                if (isset($field_map['document_order_number']) && isset($row[$field_map['document_order_number']])) {
                    $document_order_number = (int)$row[$field_map['document_order_number']];
                    if ($document_order_number <= 0) {
                        $document_order_number = null;
                    }
                }
                
                // Ambil tahun dari file
                $document_year = null;
                if (isset($field_map['document_year']) && isset($row[$field_map['document_year']])) {
                    $document_year = (int)preg_replace('/[^0-9]/', '', $row[$field_map['document_year']]);
                    if ($document_year === 0) {
                        $document_year = null;
                    }
                }
                
                // Filter tahun hanya jika import_year diisi
                if ($import_year !== null) {
                    if ($document_year === null) {
                        // Jika tahun di file kosong, gunakan tahun import
                        $document_year = $import_year;
                    } elseif ($document_year !== $import_year) {
                        // Skip jika tahun tidak sesuai
                        $skipped_rows[] = [
                            'row' => $row_number,
                            'reason' => "Tahun dokumen ($document_year) tidak sama dengan tahun import ($import_year)",
                            'data' => $full_name
                        ];
                        $skipped_count++;
                        continue;
                    }
                } else {
                    // Jika import_year kosong dan document_year juga kosong, set default ke tahun sekarang
                    if ($document_year === null) {
                        $document_year = (int)date('Y');
                    }
                }
                
                // Origin default
                $document_origin = null;
                if (isset($field_map['document_origin']) && !empty($row[$field_map['document_origin']])) {
                    $origin_raw = strtolower(trim($row[$field_map['document_origin']]));
                    if (strpos($origin_raw, 'kemayoran') !== false) {
                        $document_origin = 'imigrasi_jakarta_pusat_kemayoran';
                    } elseif (strpos($origin_raw, 'semanggi') !== false) {
                        $document_origin = 'imigrasi_ulp_semanggi';
                    } elseif (strpos($origin_raw, 'senayan') !== false) {
                        $document_origin = 'imigrasi_lounge_senayan_city';
                    }
                }
                if ($document_origin === null || $document_origin === '') {
                    $document_origin = 'imigrasi_lounge_senayan_city';
                }
                
                $citizen_category = 'WNI';
                if (isset($field_map['citizen_category']) && !empty($row[$field_map['citizen_category']])) {
                    $cat = strtoupper(trim($row[$field_map['citizen_category']]));
                    if (in_array($cat, ['WNA', 'WNI'])) {
                        $citizen_category = $cat;
                    }
                }
                
                // Generate document number
                do {
                    $timestamp = time();
                    $random = rand(1000, 9999);
                    $document_number = 'DOC-' . date('Ymd') . '-' . $random . '-' . $timestamp;
                    $exists = $db->fetch("SELECT id FROM documents WHERE document_number = ?", [$document_number]);
                } while ($exists);
                
                // Validate locker - cek berdasarkan name (kode rak) atau code
                $locker = null;
                if ($month_number) {
                    // Cek apakah sudah ada locker dengan nama rak atau kode yang sama
                    $locker = $db->fetch("SELECT id, code, name, max_capacity FROM lockers WHERE name = ? OR code = ?", [$month_number, $month_number]);

                    if (!$locker) {
                        // Jika lemari belum ada, gagalkan import dan minta user membuat lemari terlebih dahulu
                        $failed_rows[] = [
                            'row' => $row_number,
                            'reason' => "Kode Lemari/Rak '$month_number' tidak ditemukan. Silakan buat lemari terlebih dahulu di halaman 'Pilih Lemari Dokumen' sebelum melakukan import.",
                            'data' => $full_name
                        ];
                        $failed_count++;
                        continue;
                    }

                    // Gunakan name (kode rak) dari locker yang ditemukan
                    $month_number = $locker['name'];
                }
                
                // Document order number: jika tidak ada di file, auto berdasar status deleted
                if ($month_number && $document_order_number === null) {
                    $last_order_row = $db->fetch("SELECT MAX(document_order_number) AS max_order FROM documents WHERE month_number = ? AND status = 'deleted'", [$month_number]);
                    $document_order_number = (int)($last_order_row['max_order'] ?? 0) + 1;
                }
                
                $sql = "INSERT INTO documents (
                    document_number, title, full_name, nik, passport_number,
                    birth_date, month_number, marriage_certificate, birth_certificate,
                    divorce_certificate, custody_certificate, citizen_category, document_origin, document_order_number, document_year,
                    file_path, file_name, file_size, file_type, created_by, original_created_by, original_created_at, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'deleted')";
                
                $params = [
                    $document_number,
                    $full_name,
                    $full_name,
                    $nik,
                    $passport_number,
                    $birth_date,
                    $month_number,
                    null, // marriage_certificate
                    null, // birth_certificate
                    null, // divorce_certificate
                    null, // custody_certificate
                    $citizen_category,
                    $document_origin,
                    $document_order_number,
                    $document_year,
                    '', // file_path
                    '', // file_name
                    0,  // file_size
                    'imported', // file_type
                    $_SESSION['user_id'], // created_by (admin yang melakukan import)
                    $original_created_by, // original_created_by (pembuat asli dokumen)
                    $original_created_at // original_created_at (tanggal pembuatan asli)
                ];
                
                $db->execute($sql, $params);
                $document_id = $db->lastInsertId();
                
                log_activity($_SESSION['user_id'], 'IMPORT_PEMUSNAHAN', "Import dokumen pemusnahan: $full_name", $document_id);
                
                $imported_count++;
                
            } catch (Exception $e) {
                $failed_rows[] = [
                    'row' => $row_number,
                    'reason' => $e->getMessage(),
                    'data' => isset($full_name) ? $full_name : 'Unknown'
                ];
                $failed_count++;
            }
        }
        
        fclose($handle);
        
        if ($imported_count > 0) {
            $success_message = "Berhasil mengimport $imported_count dokumen pemusnahan.";
            if ($failed_count > 0) {
                $success_message .= " Gagal: $failed_count dokumen.";
            }
            if ($skipped_count > 0) {
                $success_message .= " Lewati: $skipped_count dokumen (tahun tidak sesuai).";
            }
        } else {
            $error_message = "Tidak ada dokumen yang berhasil diimport.";
            if ($failed_count > 0) {
                $error_message .= " Gagal: $failed_count dokumen.";
            }
            if ($skipped_count > 0) {
                $error_message .= " Lewati: $skipped_count dokumen (tahun tidak sesuai).";
            }
        }
        
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}

$redirect_url = 'pemusnahan.php';
if ($success_message) {
    $redirect_url .= '?success=' . urlencode($success_message);
}
if ($error_message) {
    $redirect_url .= ($success_message ? '&' : '?') . 'error=' . urlencode($error_message);
}
if ($failed_count > 0 && !empty($failed_rows)) {
    $_SESSION['import_failed_rows'] = $failed_rows;
}

header('Location: ' . $redirect_url);
exit();
?>