<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login
require_login();

$page_title = "Panduan Import Dokumen";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistem Arsip Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .step-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .step-number {
            background: #007bff;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
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
                        <i class="fas fa-book me-2"></i>
                        <?php echo $page_title; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <!-- Alert Info -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Penting:</strong> Ikuti langkah-langkah di bawah ini dengan benar untuk memastikan import dokumen berhasil.
                </div>

                <!-- Langkah 1 -->
                <div class="card step-card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <span class="step-number">1</span>
                            Hapus Data yang Tidak Valid (Jika Ada)
                        </h5>
                        <div class="ms-5">
                            <p>Jika Anda sudah pernah mengimport data yang tidak valid (seperti nama file export), hapus terlebih dahulu:</p>
                            <ol>
                                <li>Buka halaman <strong>"Daftar Dokumen"</strong></li>
                                <li>Cari dokumen dengan nama seperti:
                                    <ul>
                                        <li><code>export_dokumen_terpilih_2025-12-09_032610</code></li>
                                        <li><code>export_semua_dokumen_2025-12-09_032136</code></li>
                                        <li>Atau nama lain yang terlihat seperti nama file</li>
                                    </ul>
                                </li>
                                <li>Centang checkbox pada dokumen yang tidak valid</li>
                                <li>Klik tombol <strong>"Hapus Terpilih"</strong></li>
                                <li>Konfirmasi penghapusan</li>
                            </ol>
                            <div class="warning-box">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Peringatan:</strong> Jangan gunakan file export yang sudah ada untuk diimport kembali. File export tidak berisi data yang benar.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Langkah 2 -->
                <div class="card step-card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <span class="step-number">2</span>
                            Buat File CSV Baru
                        </h5>
                        <div class="ms-5">
                            <p>Buat file CSV baru dengan format yang benar. Anda bisa menggunakan Excel, Google Sheets, atau text editor.</p>
                            
                            <h6 class="mt-3">Format Header (Baris Pertama):</h6>
                            <div class="code-block">
Nama Lengkap,NIK,No Passport,Kode Lemari,Dokumen Berasal,Kategori
                            </div>
                            
                            <h6 class="mt-3">Contoh Data (Baris Kedua dan Seterusnya):</h6>
                            <div class="code-block">
Budi Santoso,1234567890123456,AB123456,A01,Imigrasi ULP Semanggi,WNI
Siti Nurhaliza,9876543210987654,CD789012,B02,Imigrasi Jakarta Pusat Kemayoran,WNI
Ahmad Dahlan,1111222233334444,EF345678,C03,Imigrasi Lounge Senayan City,WNI
                            </div>

                            <div class="success-box mt-3">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Kolom yang Didukung:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Wajib:</strong> Nama Lengkap (atau "Nama")</li>
                                    <li><strong>Disarankan:</strong> NIK, No Passport, Kode Lemari, Dokumen Berasal, Kategori</li>
                                    <li><strong>Opsional:</strong> Tanggal Lahir, No Surat Nikah, No Akta Lahir, No Surat Cerai, No Surat Hak Asuh, Urutan Dokumen</li>
                                </ul>
                            </div>

                            <div class="warning-box mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Catatan Penting:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Baris pertama HARUS berisi header (nama kolom)</li>
                                    <li>Kolom "Nama Lengkap" harus berisi nama orang yang benar (bukan nama file)</li>
                                    <li>Gunakan koma (,) sebagai pemisah kolom</li>
                                    <li>Jika data mengandung koma, gunakan tanda kutip (") untuk membungkus data</li>
                                    <li>Pastikan file disimpan dengan format CSV (bukan Excel .xlsx)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Langkah 3 -->
                <div class="card step-card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <span class="step-number">3</span>
                            Konversi dari Excel ke CSV (Jika Perlu)
                        </h5>
                        <div class="ms-5">
                            <p>Jika Anda menggunakan Excel (.xlsx atau .xls), konversi ke CSV terlebih dahulu:</p>
                            
                            <h6 class="mt-3">Menggunakan Microsoft Excel:</h6>
                            <ol>
                                <li>Buka file Excel Anda</li>
                                <li>Klik <strong>File</strong> → <strong>Save As</strong></li>
                                <li>Pilih format <strong>"CSV (Comma delimited) (*.csv)"</strong></li>
                                <li>Klik <strong>Save</strong></li>
                                <li>Jika muncul peringatan, klik <strong>Yes</strong></li>
                            </ol>

                            <h6 class="mt-3">Menggunakan Google Sheets:</h6>
                            <ol>
                                <li>Buka file di Google Sheets</li>
                                <li>Klik <strong>File</strong> → <strong>Download</strong> → <strong>Comma Separated Values (.csv)</strong></li>
                                <li>File CSV akan terdownload</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Langkah 4 -->
                <div class="card step-card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <span class="step-number">4</span>
                            Import File CSV
                        </h5>
                        <div class="ms-5">
                            <ol>
                                <li>Buka halaman <strong>"Daftar Dokumen"</strong></li>
                                <li>Klik tombol <strong>"Import"</strong> (tombol biru dengan icon upload)</li>
                                <li>Klik <strong>"Pilih File CSV"</strong> dan pilih file CSV yang sudah dibuat</li>
                                <li>Klik tombol <strong>"Import"</strong></li>
                                <li>Tunggu proses import selesai</li>
                            </ol>

                            <div class="success-box mt-3">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Jika Berhasil:</strong> Anda akan melihat pesan "Berhasil mengimport X dokumen" dan data akan muncul di tabel.
                            </div>

                            <div class="warning-box mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Jika Gagal:</strong> 
                                <ul class="mb-0 mt-2">
                                    <li>Periksa pesan error yang muncul</li>
                                    <li>Pastikan header CSV sesuai dengan format yang didukung</li>
                                    <li>Pastikan data di kolom "Nama Lengkap" berisi nama orang yang benar</li>
                                    <li>Pastikan file adalah CSV (bukan Excel)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Langkah 5 -->
                <div class="card step-card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <span class="step-number">5</span>
                            Verifikasi Data
                        </h5>
                        <div class="ms-5">
                            <p>Setelah import berhasil, verifikasi data yang terimport:</p>
                            <ol>
                                <li>Periksa tabel dokumen di halaman "Daftar Dokumen"</li>
                                <li>Pastikan kolom "Nama Lengkap" berisi nama orang yang benar</li>
                                <li>Pastikan kolom lain (NIK, No Passport, Kode Lemari, dll) terisi dengan benar</li>
                                <li>Jika ada data yang salah, klik tombol <strong>"Edit"</strong> untuk memperbaikinya</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Contoh File CSV Lengkap -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-file-csv me-2"></i>
                            Contoh File CSV Lengkap
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Berikut adalah contoh file CSV lengkap yang bisa Anda gunakan sebagai template:</p>
                        <div class="code-block">
Nama Lengkap,NIK,No Passport,Kode Lemari,Dokumen Berasal,Kategori,Tanggal Lahir
Budi Santoso,1234567890123456,AB123456,A01,Imigrasi ULP Semanggi,WNI,15/01/1990
Siti Nurhaliza,9876543210987654,CD789012,B02,Imigrasi Jakarta Pusat Kemayoran,WNI,20/05/1985
Ahmad Dahlan,1111222233334444,EF345678,C03,Imigrasi Lounge Senayan City,WNI,10/12/1992
                        </div>
                        <p class="mt-3">
                            <strong>Catatan:</strong> Copy contoh di atas, paste ke file CSV baru, dan ganti dengan data Anda yang sebenarnya.
                        </p>
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Troubleshooting (Pemecahan Masalah)
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Masalah: "Kolom wajib tidak ditemukan"</h6>
                        <p><strong>Solusi:</strong> Pastikan baris pertama file CSV berisi header dengan kolom "Nama Lengkap" atau "Nama".</p>

                        <h6 class="mt-3">Masalah: "Nama Lengkap tidak valid"</h6>
                        <p><strong>Solusi:</strong> Pastikan kolom "Nama Lengkap" berisi nama orang yang benar, bukan nama file export atau kode.</p>

                        <h6 class="mt-3">Masalah: Data tidak terisi (kolom kosong)</h6>
                        <p><strong>Solusi:</strong> 
                            <ul>
                                <li>Pastikan header CSV sesuai dengan format yang didukung</li>
                                <li>Pastikan data di setiap baris terisi dengan benar</li>
                                <li>Pastikan tidak ada karakter khusus yang mengganggu</li>
                            </ul>
                        </p>

                        <h6 class="mt-3">Masalah: File Excel tidak bisa diimport</h6>
                        <p><strong>Solusi:</strong> Konversi file Excel ke CSV terlebih dahulu (lihat Langkah 3).</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 mb-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Dokumen
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak Panduan
                    </button>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>



