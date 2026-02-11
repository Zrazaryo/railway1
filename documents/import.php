<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login dan role admin
require_login();
if (!is_admin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

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

// Opsi untuk skip pengecekan duplikat (default: true untuk menghindari false positive)
// User bisa menonaktifkan dengan checkbox jika ingin mengecek duplikat
$skip_duplicate_check = !isset($_POST['check_duplicate']) || $_POST['check_duplicate'] !== '1';

if (empty($error_message) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    try {
        $file = $_FILES['import_file'];
        
        // Validasi file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error upload file: ' . $file['error']);
        }
        
        $allowed_extensions = ['csv', 'xlsx', 'xls'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Format file tidak didukung. Gunakan CSV, XLSX, atau XLS');
        }
        
        // Baca file
        $file_path = $file['tmp_name'];
        $handle = fopen($file_path, 'r');
        
        if ($handle === false) {
            throw new Exception('Gagal membaca file');
        }
        
        // Skip BOM jika ada (untuk UTF-8)
        $first_line = fgets($handle);
        if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
            $first_line = substr($first_line, 3);
        }
        rewind($handle);
        if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
            fseek($handle, 3);
        }
        
        // Baca header (baris pertama)
        $headers = fgetcsv($handle);
        if ($headers === false) {
            throw new Exception('File kosong atau format tidak valid');
        }
        
        // Normalize headers (case insensitive, trim)
        $headers = array_map(function($h) {
            return trim(strtolower($h));
        }, $headers);
        
        // Mapping kolom yang diharapkan
        $column_map = [
            'nama lengkap' => 'full_name',
            'nik' => 'nik',
            'no passport' => 'passport_number',
            'passport' => 'passport_number',
            'tanggal lahir' => 'birth_date',
            'no bulan pemohon' => 'month_number',
            'bulan pemohon' => 'month_number',
            'kode lemari' => 'month_number',
            'no bulan pemohon/kode lemari' => 'month_number', // Menambahkan ini
            'no surat nikah' => 'marriage_certificate',
            'surat nikah' => 'marriage_certificate',
            'no akta lahir' => 'birth_certificate',
            'akta lahir' => 'birth_certificate',
            'no surat cerai' => 'divorce_certificate',
            'surat cerai' => 'divorce_certificate',
            'no surat hak asuh' => 'custody_certificate',
            'surat hak asuh' => 'custody_certificate',
            'kategori' => 'citizen_category',
            'kewarganegaraan' => 'citizen_category',
            'dokumen berasal' => 'document_origin',
            'asal dokumen' => 'document_origin',
            'urutan dokumen' => 'document_order_number',
            'urutan' => 'document_order_number',
            'order' => 'document_order_number',
            'no urut' => 'document_order_number',
            'nomor urut' => 'document_order_number',
            'tahun' => 'document_year',
            'year' => 'document_year',
            'dibuat oleh' => 'original_created_by',
            'staff' => 'original_created_by',
            'pembuat' => 'original_created_by',
            'created by' => 'original_created_by',
            'username' => 'original_created_by',
            'user' => 'original_created_by'
        ];
        
        // Map headers ke field names
        $field_map = [];
        foreach ($headers as $index => $header) {
            $header_clean = trim(strtolower($header));
            if (isset($column_map[$header_clean])) {
                $field_map[$column_map[$header_clean]] = $index;
            }
        }
        
        // Validasi kolom wajib
        $required_fields = ['full_name', 'nik', 'passport_number'];
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($field_map[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception('Kolom wajib tidak ditemukan: ' . implode(', ', $missing_fields));
        }
        
        // Process data rows
        $row_number = 1; // Header is row 1
        $processed_in_batch = []; // Track dokumen yang sudah diproses dalam batch ini
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            try {
                // Extract data dengan normalisasi yang lebih ketat
                $full_name = isset($field_map['full_name']) ? trim($row[$field_map['full_name']]) : '';
                $nik = isset($field_map['nik']) ? trim($row[$field_map['nik']]) : '';
                $passport_number = isset($field_map['passport_number']) ? trim($row[$field_map['passport_number']]) : '';
                
                // Normalisasi: hapus whitespace berlebih dan pastikan tidak kosong setelah trim
                $nik = preg_replace('/\s+/', ' ', $nik); // Normalisasi spasi
                $passport_number = preg_replace('/\s+/', ' ', $passport_number); // Normalisasi spasi
                
                // Trim lagi setelah normalisasi
                $nik = trim($nik);
                $passport_number = trim($passport_number);
                
                // Set ke null jika kosong setelah normalisasi (untuk konsistensi dengan database)
                if ($nik === '') $nik = null;
                if ($passport_number === '') $passport_number = null;
                
                // Determine created_by (default to current admin performing import)
                $created_by_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                if (isset($field_map['original_created_by']) && isset($row[$field_map['original_created_by']])) {
                    $creator_info = trim($row[$field_map['original_created_by']]);
                    if (!empty($creator_info)) {
                        // Try to find user by username, full_name, or email
                        $user = $db->fetch(
                            "SELECT id FROM users WHERE username = ? OR full_name = ? OR email = ? LIMIT 1",
                            [$creator_info, $creator_info, $creator_info]
                        );

                        if ($user) {
                            $created_by_user_id = $user['id'];
                        }
                    }
                }

                // Validasi data wajib - setidaknya salah satu NIK atau passport harus ada
                if (empty($full_name) || (empty($nik) && empty($passport_number))) {
                    $failed_rows[] = [
                        'row' => $row_number,
                        'reason' => 'Data wajib kosong (Nama wajib, dan minimal NIK atau Passport harus ada)',
                        'data' => $full_name
                    ];
                    $failed_count++;
                    continue;
                }
                
                // Extract optional data
                $birth_date = null;
                if (isset($field_map['birth_date']) && !empty($row[$field_map['birth_date']])) {
                    $date_str = trim($row[$field_map['birth_date']]);
                    // Try to parse date (support multiple formats)
                    $date_obj = DateTime::createFromFormat('d/m/Y', $date_str);
                    if (!$date_obj) {
                        $date_obj = DateTime::createFromFormat('Y-m-d', $date_str);
                    }
                    if ($date_obj) {
                        $birth_date = $date_obj->format('Y-m-d');
                    }
                }
                
                // Ambil nama rak dari CSV (sederhana, tanpa parsing kompleks)
                $month_number = isset($field_map['month_number']) ? trim($row[$field_map['month_number']]) : null;
                if ($month_number === '') {
                    $month_number = null;
                }
                
                $marriage_certificate = isset($field_map['marriage_certificate']) ? trim($row[$field_map['marriage_certificate']]) : null;
                $birth_certificate = isset($field_map['birth_certificate']) ? trim($row[$field_map['birth_certificate']]) : null;
                $divorce_certificate = isset($field_map['divorce_certificate']) ? trim($row[$field_map['divorce_certificate']]) : null;
                $custody_certificate = isset($field_map['custody_certificate']) ? trim($row[$field_map['custody_certificate']]) : null;
                // Ambil urutan dokumen dari CSV (PRIORITAS TERTINGGI)
                $document_order_number = null;
                if (isset($field_map['document_order_number']) && isset($row[$field_map['document_order_number']])) {
                    $order_raw = $row[$field_map['document_order_number']];
                    if ($order_raw !== null && $order_raw !== '' && $order_raw !== false) {
                        $order_raw = trim($order_raw);
                        if ($order_raw !== '') {
                            // Langsung convert ke integer
                            $document_order_number = (int)$order_raw;
                            // Jika hasilnya 0 atau negatif, set null (akan dihitung otomatis)
                            if ($document_order_number <= 0) {
                                $document_order_number = null;
                            }
                        }
                    }
                }
                
                $document_year = null;
                if (isset($field_map['document_year']) && isset($row[$field_map['document_year']])) {
                    $document_year = (int)preg_replace('/[^0-9]/', '', $row[$field_map['document_year']]);
                    if ($document_year === 0) {
                        $document_year = null;
                    }
                }
                if ($document_year === null && $import_year !== null) {
                    $document_year = $import_year;
                }
                
                if ($import_year !== null && $document_year !== null && $document_year !== $import_year) {
                    $skipped_rows[] = [
                        'row' => $row_number,
                        'reason' => "Tahun dokumen ($document_year) tidak sama dengan tahun import ($import_year)",
                        'data' => $full_name
                    ];
                    $skipped_count++;
                    continue;
                }

                // Asal dokumen (fallback default jika kosong)
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
                    $document_origin = 'imigrasi_lounge_senayan_city'; // default fallback
                }
                
                // Kategori (default WNI)
                $citizen_category = 'WNI';
                if (isset($field_map['citizen_category']) && !empty($row[$field_map['citizen_category']])) {
                    $cat = strtoupper(trim($row[$field_map['citizen_category']]));
                    if (in_array($cat, ['WNA', 'WNI'])) {
                        $citizen_category = $cat;
                    }
                }
                
                // Check if document already exists (by NIK or passport)
                // DEFAULT: Pengecekan duplikat DINONAKTIFKAN untuk memungkinkan import ulang dokumen yang sudah pernah di-export
                // User bisa mengaktifkan pengecekan duplikat dengan mencentang checkbox "Cek duplikat sebelum import"
                $existing = null;
                $duplicate_reason = '';
                
                // Hanya cek duplikat jika user secara eksplisit meminta (checkbox dicentang)
                if (!$skip_duplicate_check) {
                    // Buat key unik untuk tracking dalam batch
                    $batch_key = null;
                    if ($nik !== null && $nik !== '') {
                        $batch_key = 'nik:' . $nik;
                    } elseif ($passport_number !== null && $passport_number !== '') {
                        $batch_key = 'passport:' . $passport_number;
                    }
                    
                    // Cek duplikat dalam batch yang sama (dalam file yang sama) - hanya jika pengecekan aktif
                    if ($batch_key !== null && isset($processed_in_batch[$batch_key])) {
                        $failed_rows[] = [
                            'row' => $row_number,
                            'reason' => 'Dokumen duplikat dalam file yang sama',
                            'data' => $full_name
                        ];
                        $failed_count++;
                        continue;
                    }
                    
                    // Cek duplikat di database - hanya jika nilai tidak null dan tidak kosong
                    $has_valid_nik = ($nik !== null && $nik !== '' && strlen($nik) > 0);
                    $has_valid_passport = ($passport_number !== null && $passport_number !== '' && strlen($passport_number) > 0);
                    
                    // Cek duplikat berdasarkan NIK terlebih dahulu (jika ada)
                    if ($has_valid_nik) {
                        $nik_clean = $nik; // Sudah di-trim sebelumnya
                        $existing_nik = $db->fetch(
                            "SELECT id FROM documents WHERE nik = ? AND nik IS NOT NULL AND nik != '' AND LENGTH(nik) > 0 AND status = 'active' LIMIT 1",
                            [$nik_clean]
                        );
                        
                        if ($existing_nik) {
                            $existing = $existing_nik;
                            $duplicate_reason = 'Dokumen sudah ada (NIK duplikat)';
                        }
                    }
                    
                    // Cek duplikat berdasarkan Passport (jika belum ditemukan duplikat dan passport ada)
                    if (!$existing && $has_valid_passport) {
                        $passport_clean = $passport_number; // Sudah di-trim sebelumnya
                        $existing_passport = $db->fetch(
                            "SELECT id FROM documents WHERE passport_number = ? AND passport_number IS NOT NULL AND passport_number != '' AND LENGTH(passport_number) > 0 AND status = 'active' LIMIT 1",
                            [$passport_clean]
                        );
                        
                        if ($existing_passport) {
                            $existing = $existing_passport;
                            $duplicate_reason = 'Dokumen sudah ada (Passport duplikat)';
                        }
                    }
                    
                    if ($existing) {
                        $failed_rows[] = [
                            'row' => $row_number,
                            'reason' => $duplicate_reason,
                            'data' => $full_name
                        ];
                        $failed_count++;
                        continue;
                    }
                    
                    // Tandai dokumen ini sebagai sudah diproses dalam batch
                    if ($batch_key !== null) {
                        $processed_in_batch[$batch_key] = true;
                    }
                } else {
                    // Jika pengecekan duplikat dinonaktifkan, tetap track dalam batch untuk statistik
                    $batch_key = null;
                    if ($nik !== null && $nik !== '') {
                        $batch_key = 'nik:' . $nik;
                    } elseif ($passport_number !== null && $passport_number !== '') {
                        $batch_key = 'passport:' . $passport_number;
                    }
                    if ($batch_key !== null) {
                        $processed_in_batch[$batch_key] = true;
                    }
                }
                
                // Generate unique document number
                do {
                    $timestamp = time();
                    $random = rand(1000, 9999);
                    $document_number = 'DOC-' . date('Ymd') . '-' . $random . '-' . $timestamp;
                    $exists = $db->fetch("SELECT id FROM documents WHERE document_number = ?", [$document_number]);
                } while ($exists);
                
                // Validasi dan ambil data lemari/rak (sederhana)
                $locker = null;
                if ($month_number) {
                    $month_number_clean = trim($month_number);
                    $locker = $db->fetch("SELECT id, code, name, max_capacity FROM lockers WHERE name = ? OR code = ?", [$month_number_clean, $month_number_clean]);
                    
                    if (!$locker) {
                        $failed_rows[] = [
                            'row' => $row_number,
                            'reason' => "Nama/Kode Rak ('$month_number') tidak ditemukan di database",
                            'data' => $full_name
                        ];
                        $failed_count++;
                        continue;
                    }
                    
                    $month_number = $locker['name'];
                } else {
                    $failed_rows[] = [
                        'row' => $row_number,
                        'reason' => "Nama/Kode Rak tidak ditemukan di CSV",
                        'data' => $full_name
                    ];
                    $failed_count++;
                    continue;
                }
                
                // Hitung document_order_number HANYA jika belum ada dari CSV
                // PENTING: Jangan overwrite nilai dari CSV!
                if ($month_number && $document_order_number === null) {
                    $last_order_row = $db->fetch("SELECT MAX(document_order_number) AS max_order FROM documents WHERE month_number = ? AND status = 'active'", [$month_number]);
                    $max_order = (int)($last_order_row['max_order'] ?? 0);
                    $document_order_number = $max_order + 1;
                }
                
                // Fallback jika masih null
                if ($document_order_number === null) {
                    $document_order_number = 1;
                }
                
                // Insert document
                $sql = "INSERT INTO documents (
                    document_number, title, full_name, nik, passport_number, 
                    birth_date, month_number, marriage_certificate, birth_certificate, 
                    divorce_certificate, custody_certificate, citizen_category, document_origin, document_order_number, document_year,
                    file_path, file_name, file_size, file_type, created_by, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                
                $params = [
                    $document_number,
                    $full_name, // title = full_name
                    $full_name,
                    $nik,
                    $passport_number,
                    $birth_date,
                    $month_number,
                    $marriage_certificate,
                    $birth_certificate,
                    $divorce_certificate,
                    $custody_certificate,
                    $citizen_category,
                    $document_origin,
                    $document_order_number, // Gunakan nilai yang sudah di-set (dari CSV, parsing, atau auto-calculate)
                    $document_year,
                    '', // file_path (kosong karena import tanpa file)
                    '', // file_name
                    0,  // file_size
                    'imported', // file_type
                    $created_by_user_id
                ];
                
                $db->execute($sql, $params);
                $document_id = $db->lastInsertId();
                
                // Log activity
                log_activity($_SESSION['user_id'], 'IMPORT_DOCUMENT', "Import dokumen: $full_name", $document_id);
                
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
        
        // Set success message
        if ($imported_count > 0) {
            $success_message = "Berhasil mengimport $imported_count dokumen.";
            if ($failed_count > 0) {
                $success_message .= " Gagal: $failed_count dokumen.";
            }
            if ($skipped_count > 0) {
                $success_message .= " Lewati: $skipped_count dokumen karena tahun tidak sesuai.";
            }
        } else {
            $error_message = "Tidak ada dokumen yang berhasil diimport. Gagal: $failed_count dokumen.";
            if ($skipped_count > 0) {
                $error_message .= " Lewati: $skipped_count dokumen karena tahun tidak sesuai.";
            }
        }
        
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}

// Redirect back to index with messages
$redirect_url = 'index.php';
if ($success_message) {
    $redirect_url .= '?success=' . urlencode($success_message);
}
if ($error_message) {
    $redirect_url .= ($success_message ? '&' : '?') . 'error=' . urlencode($error_message);
}
if ($failed_count > 0 && !empty($failed_rows)) {
    // Store failed rows in session for display
    $_SESSION['import_failed_rows'] = $failed_rows;
}

header('Location: ' . $redirect_url);
exit();
?>

