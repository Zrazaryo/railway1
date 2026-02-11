<?php
/**
 * Halaman untuk menampilkan dokumen yang dimiliki oleh pengguna
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Pastikan pengguna sudah login
require_login();

// Ambil data pengguna
$user_id = $_SESSION['user_id'];

// Ambil daftar dokumen yang dimiliki oleh pengguna
$documents = $db->fetchAll("SELECT d.*, l.code AS locker_code, l.name AS locker_name 
                            FROM documents d
                            JOIN lockers l ON d.month_number = l.name
                            WHERE d.user_id = ? AND d.status != 'deleted'
                            ORDER BY d.created_at DESC", [$user_id]);

// Hitung jumlah dokumen
$total_documents = count($documents);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Saya</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
    <h1>Dokumen Saya</h1>

    <?php if ($total_documents > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Lemari</th>
                    <th>Nama Lemari</th>
                    <th>Bulan</th>
                    <th>Tahun</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $index => $document): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($document['locker_code']) ?></td>
                        <td><?= htmlspecialchars($document['locker_name']) ?></td>
                        <td><?= htmlspecialchars($document['month']) ?></td>
                        <td><?= htmlspecialchars($document['year']) ?></td>
                        <td><?= htmlspecialchars($document['status']) ?></td>
                        <td>
                            <a href="view.php?id=<?= $document['id'] ?>" class="btn btn-view">Lihat</a>
                            <a href="edit.php?id=<?= $document['id'] ?>" class="btn btn-edit">Ubah</a>
                            <a href="delete.php?id=<?= $document['id'] ?>" class="btn btn-delete" onclick="return confirm('Anda yakin ingin menghapus dokumen ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Tidak ada dokumen yang ditemukan.</p>
    <?php endif; ?>

    <?php if (is_admin() || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff')): ?>
    <a href="create.php" class="btn btn-primary">Tambah Dokumen</a>
    <?php endif; ?>
</div>

<script src="../assets/js/script.js"></script>
</body>
</html>