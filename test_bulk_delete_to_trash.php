<?php
/**
 * Test Bulk Delete to Trash Feature
 * Path: /test_bulk_delete_to_trash.php
 * 
 * Gunakan: http://localhost/PROJECT%20ARSIP%20LOKER/test_bulk_delete_to_trash.php
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek admin
require_admin();

$test_results = [];
$errors = [];

// Test 1: Check database tables
try {
    $tables = $db->fetchAll("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $table_names = array_column($tables, 'TABLE_NAME');
    
    $required_tables = ['documents', 'document_trash', 'trash_audit_logs'];
    $missing_tables = array_diff($required_tables, $table_names);
    
    if (empty($missing_tables)) {
        $test_results[] = [
            'test' => '1. Database Tables',
            'status' => '✅ PASS',
            'details' => 'Semua tabel required ada: ' . implode(', ', $required_tables)
        ];
    } else {
        $test_results[] = [
            'test' => '1. Database Tables',
            'status' => '❌ FAIL',
            'details' => 'Tabel yang hilang: ' . implode(', ', $missing_tables)
        ];
        $errors[] = 'Missing tables: ' . implode(', ', $missing_tables);
    }
} catch (Exception $e) {
    $test_results[] = [
        'test' => '1. Database Tables',
        'status' => '❌ ERROR',
        'details' => $e->getMessage()
    ];
    $errors[] = $e->getMessage();
}

// Test 2: Check documents.status column type
try {
    $result = $db->fetchAll("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='documents' AND COLUMN_NAME='status'");
    if (!empty($result)) {
        $column_type = $result[0]['COLUMN_TYPE'];
        if (strpos($column_type, 'varchar') !== false || strpos($column_type, 'VARCHAR') !== false) {
            $test_results[] = [
                'test' => '2. documents.status Column Type',
                'status' => '✅ PASS',
                'details' => 'Column type: ' . $column_type . ' (VARCHAR, dapat accept values seperti "trashed")'
            ];
        } else {
            $test_results[] = [
                'test' => '2. documents.status Column Type',
                'status' => '⚠️ WARNING',
                'details' => 'Column type: ' . $column_type . ' (Mungkin ENUM - perlu fix untuk support "trashed")'
            ];
            $errors[] = 'Column status mungkin ENUM, jalankan fix_schema.php';
        }
    }
} catch (Exception $e) {
    $test_results[] = [
        'test' => '2. documents.status Column Type',
        'status' => '❌ ERROR',
        'details' => $e->getMessage()
    ];
}

// Test 3: Check file existence
try {
    $required_files = [
        'documents/delete_multiple.php',
        'documents/trash.php',
        'documents/delete.php',
        'includes/trash_helper.php'
    ];
    
    $missing_files = [];
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        $test_results[] = [
            'test' => '3. Required Files',
            'status' => '✅ PASS',
            'details' => 'Semua file ada: ' . implode(', ', $required_files)
        ];
    } else {
        $test_results[] = [
            'test' => '3. Required Files',
            'status' => '❌ FAIL',
            'details' => 'File yang hilang: ' . implode(', ', $missing_files)
        ];
        $errors[] = 'Missing files: ' . implode(', ', $missing_files);
    }
} catch (Exception $e) {
    $test_results[] = [
        'test' => '3. Required Files',
        'status' => '❌ ERROR',
        'details' => $e->getMessage()
    ];
}

// Test 4: Check sample documents
try {
    $sample_docs = $db->fetchAll("SELECT id, title, status FROM documents LIMIT 5");
    if (!empty($sample_docs)) {
        $test_results[] = [
            'test' => '4. Sample Documents',
            'status' => '✅ PASS',
            'details' => 'Found ' . count($sample_docs) . ' sample documents'
        ];
    } else {
        $test_results[] = [
            'test' => '4. Sample Documents',
            'status' => '⚠️ WARNING',
            'details' => 'Tidak ada dokumen di database'
        ];
    }
} catch (Exception $e) {
    $test_results[] = [
        'test' => '4. Sample Documents',
        'status' => '❌ ERROR',
        'details' => $e->getMessage()
    ];
}

// Test 5: Verify document_trash structure
try {
    $columns = $db->fetchAll("SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='document_trash'");
    $required_columns = ['original_document_id', 'title', 'full_name', 'deleted_by', 'restore_deadline', 'status'];
    
    $column_names = array_column($columns, 'COLUMN_NAME');
    $missing_columns = array_diff($required_columns, $column_names);
    
    if (empty($missing_columns)) {
        $test_results[] = [
            'test' => '5. document_trash Structure',
            'status' => '✅ PASS',
            'details' => 'Total ' . count($columns) . ' kolom, semua required columns ada'
        ];
    } else {
        $test_results[] = [
            'test' => '5. document_trash Structure',
            'status' => '❌ FAIL',
            'details' => 'Kolom yang hilang: ' . implode(', ', $missing_columns)
        ];
        $errors[] = 'Missing columns in document_trash: ' . implode(', ', $missing_columns);
    }
} catch (Exception $e) {
    $test_results[] = [
        'test' => '5. document_trash Structure',
        'status' => '❌ ERROR',
        'details' => $e->getMessage()
    ];
}

// Test 6: Check session & user
try {
    if (isset($_SESSION['user_id'])) {
        $test_results[] = [
            'test' => '6. Session & Authentication',
            'status' => '✅ PASS',
            'details' => 'User ID: ' . $_SESSION['user_id'] . ', Role: ' . ($_SESSION['role'] ?? 'N/A')
        ];
    } else {
        $test_results[] = [
            'test' => '6. Session & Authentication',
            'status' => '❌ FAIL',
            'details' => 'Session user_id tidak ditemukan'
        ];
        $errors[] = 'Session not properly initialized';
    }
} catch (Exception $e) {
    $test_results[] = [
        'test' => '6. Session & Authentication',
        'status' => '❌ ERROR',
        'details' => $e->getMessage()
    ];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Bulk Delete to Trash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .test-container { max-width: 900px; margin: 0 auto; }
        .test-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-row { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .test-row:last-child { border-bottom: none; }
        .test-name { font-weight: 500; flex: 1; }
        .test-status { padding: 5px 10px; border-radius: 4px; font-weight: bold; min-width: 100px; text-align: right; }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .error { background: #f8d7da; color: #721c24; }
        .test-details { font-size: 0.9em; color: #666; margin-top: 5px; }
        .header { margin-bottom: 30px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .summary-item { background: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .summary-count { font-size: 2em; font-weight: bold; }
        .summary-label { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="header">
            <h1>🧪 Test Bulk Delete to Trash Feature</h1>
            <p class="text-muted">Verifikasi konfigurasi dan kesiapan sistem</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">⚠️ Ditemukan Error:</h4>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="summary">
            <div class="summary-item">
                <div class="summary-count" style="color: #28a745;">
                    <?php echo count(array_filter($test_results, fn($t) => strpos($t['status'], '✅') === 0)); ?>
                </div>
                <div class="summary-label">Passed</div>
            </div>
            <div class="summary-item">
                <div class="summary-count" style="color: #dc3545;">
                    <?php echo count(array_filter($test_results, fn($t) => strpos($t['status'], '❌') === 0)); ?>
                </div>
                <div class="summary-label">Failed</div>
            </div>
            <div class="summary-item">
                <div class="summary-count" style="color: #ffc107;">
                    <?php echo count(array_filter($test_results, fn($t) => strpos($t['status'], '⚠️') === 0)); ?>
                </div>
                <div class="summary-label">Warnings</div>
            </div>
        </div>

        <div class="test-card">
            <h5 class="mb-4">Test Results</h5>
            <?php foreach ($test_results as $result): ?>
                <div class="test-row">
                    <div>
                        <div class="test-name"><?php echo htmlspecialchars($result['test']); ?></div>
                        <div class="test-details"><?php echo htmlspecialchars($result['details']); ?></div>
                    </div>
                    <div class="test-status <?php 
                        if (strpos($result['status'], '✅') === 0) echo 'pass';
                        elseif (strpos($result['status'], '❌') === 0) echo 'fail';
                        elseif (strpos($result['status'], '⚠️') === 0) echo 'warning';
                        else echo 'error';
                    ?>">
                        <?php echo htmlspecialchars($result['status']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="test-card">
            <h5 class="mb-3">Next Steps</h5>
            <div class="list-group">
                <a href="documents/trash.php" class="list-group-item list-group-item-action">
                    📋 View Menu Sampah (Trash)
                </a>
                <a href="documents/setup_trash.php" class="list-group-item list-group-item-action">
                    ⚙️ Run Setup Trash (Auto-create tables)
                </a>
                <a href="documents/fix_schema.php" class="list-group-item list-group-item-action">
                    🔧 Fix Schema (ENUM → VARCHAR conversion)
                </a>
                <a href="platform/documents.php" class="list-group-item list-group-item-action">
                    📄 Test with Platform Documents
                </a>
            </div>
        </div>

        <div class="test-card alert alert-info">
            <h5>ℹ️ Testing Instructions</h5>
            <ol>
                <li>Pastikan semua tests menunjukkan ✅ PASS</li>
                <li>Jika ada ❌ FAIL atau ⚠️ WARNING, jalankan link di atas</li>
                <li>Buka <strong>Dokumen Keseluruhan</strong> (platform/documents.php)</li>
                <li>Select beberapa dokumen dengan checkbox</li>
                <li>Click button "🗑️ Hapus Terpilih"</li>
                <li>Confirm dialog akan muncul</li>
                <li>Setelah delete, dokumen harus masuk ke Menu Sampah</li>
                <li>Di Menu Sampah, bisa restore atau permanent delete</li>
            </ol>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
