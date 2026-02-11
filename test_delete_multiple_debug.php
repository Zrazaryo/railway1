<?php
/**
 * Debug: Test Checkbox & Delete Multiple
 * Akses: http://localhost/PROJECT%20ARSIP%20LOKER/test_delete_multiple_debug.php
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

require_admin();

$test_data = [
    'session_user' => $_SESSION['user_id'] ?? null,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'raw_input' => file_get_contents('php://input'),
    'json_input' => null,
    'document_ids' => [],
    'validation_result' => '',
    'database_check' => []
];

// If POST, test JSON parsing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $test_data['raw_input'] = $raw;
    
    $json = json_decode($raw, true);
    $test_data['json_input'] = $json;
    
    if ($json && isset($json['document_ids'])) {
        $test_data['document_ids'] = $json['document_ids'];
        
        // Test validation
        $doc_ids = $json['document_ids'];
        
        // Replicate the validation logic
        $validated = array_map(function($id) {
            $id = intval($id);
            return ($id > 0) ? $id : null;
        }, $doc_ids);
        
        $validated = array_filter($validated, function($id) {
            return $id !== null;
        });
        
        $test_data['validation_result'] = [
            'raw_ids' => $doc_ids,
            'validated_ids' => array_values($validated),
            'count_raw' => count($doc_ids),
            'count_validated' => count($validated),
            'is_valid' => count($validated) > 0
        ];
        
        // Check documents exist
        if (!empty($validated)) {
            try {
                $placeholders = implode(',', array_fill(0, count($validated), '?'));
                $sql = "SELECT id, full_name, status FROM documents WHERE id IN ($placeholders) LIMIT 10";
                $docs = $db->fetchAll($sql, array_values($validated));
                $test_data['database_check'] = $docs;
            } catch (Exception $e) {
                $test_data['database_check'] = ['error' => $e->getMessage()];
            }
        }
    }
}

// Get sample documents
$sample_docs = [];
try {
    $sample_docs = $db->fetchAll("SELECT id, full_name, status FROM documents LIMIT 5");
} catch (Exception $e) {
    $sample_docs = ['error' => $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug: Delete Multiple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; }
        .debug-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .code-block { background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #007bff; font-family: monospace; white-space: pre-wrap; overflow-x: auto; }
        .test-result { padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        table { font-size: 0.9em; }
        .json-output { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-bug"></i> Debug: Delete Multiple Feature</h1>
        <p class="text-muted">Test checkbox selection & document deletion</p>

        <!-- Test 1: Sample Documents -->
        <div class="debug-section">
            <h3>1️⃣ Sample Documents dari Database</h3>
            <?php if (is_array($sample_docs) && !isset($sample_docs['error'])): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Checkbox</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sample_docs as $doc): ?>
                                <tr>
                                    <td><?php echo $doc['id']; ?></td>
                                    <td><?php echo $doc['full_name'] ?? '-'; ?></td>
                                    <td><?php echo $doc['status'] ?? '-'; ?></td>
                                    <td>
                                        <input type="checkbox" class="test-checkbox" value="<?php echo $doc['id']; ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-primary" onclick="testSelectedCheckboxes()">
                    <i class="fas fa-check-circle"></i> Test Selected Checkboxes
                </button>
            <?php else: ?>
                <div class="alert alert-danger">Error loading documents: <?php echo $sample_docs['error'] ?? 'Unknown'; ?></div>
            <?php endif; ?>
        </div>

        <!-- Test 2: Manual JSON Test -->
        <div class="debug-section">
            <h3>2️⃣ Manual JSON Test</h3>
            <p class="text-muted">Send custom JSON to test backend validation</p>
            <div class="form-group mb-3">
                <label>Document IDs (comma-separated):</label>
                <input type="text" id="manualIds" class="form-control" placeholder="1,2,3" value="1,2">
            </div>
            <button class="btn btn-info" onclick="sendManualJSON()">
                <i class="fas fa-paper-plane"></i> Send Test JSON
            </button>
        </div>

        <!-- Test 3: Request/Response Log -->
        <div class="debug-section">
            <h3>3️⃣ Request/Response Log</h3>
            <div id="requestLog" class="json-output">
                // Log akan tampil di sini setelah test
            </div>
        </div>

        <!-- Test 4: Validation Test -->
        <div class="debug-section">
            <h3>4️⃣ Backend Validation Result</h3>
            <div id="validationResult"></div>
        </div>

        <!-- Test 5: Database Check -->
        <div class="debug-section">
            <h3>5️⃣ Document Verification</h3>
            <div id="databaseCheck"></div>
        </div>

        <!-- Test 6: Console Instructions -->
        <div class="debug-section">
            <h3>6️⃣ Open Browser Console (F12)</h3>
            <p class="text-muted">Lihat console output untuk detailed debugging:</p>
            <pre class="code-block">
1. Press F12 to open Developer Tools
2. Click Console tab
3. Test delete dengan checkbox
4. Lihat output console:
   - Checkbox values
   - Document IDs array
   - Fetch request details
   - Response status & data
            </pre>
        </div>

        <!-- Test 7: Quick Test Form -->
        <div class="debug-section">
            <h3>7️⃣ Quick Delete Test</h3>
            <p class="text-muted">Langsung test delete dengan checkbox di atas, atau:</p>
            <form id="quickDeleteForm" onsubmit="return testDeleteSelected();">
                <div class="form-group mb-3">
                    <label>Select Documents:</label>
                    <div id="documentCheckboxes"></div>
                </div>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Test Delete Selected
                </button>
            </form>
        </div>
    </div>

    <script>
        // Log ke console & display
        function logDebug(title, data) {
            const log = JSON.stringify(data, null, 2);
            console.log(title, data);
            document.getElementById('requestLog').innerHTML += `\n\n// ${title}\n${log}`;
        }

        // Test selected checkboxes
        function testSelectedCheckboxes() {
            const checkboxes = document.querySelectorAll('.test-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => {
                const val = cb.value.trim();
                const id = parseInt(val, 10);
                console.log(`Checkbox: "${val}" → ${id}`);
                return id;
            }).filter(id => !isNaN(id) && id > 0);

            logDebug('Selected Checkboxes', {
                count: ids.length,
                ids: ids
            });

            alert(`Selected ${ids.length} documents: ${ids.join(', ')}`);
        }

        // Send manual JSON
        function sendManualJSON() {
            const input = document.getElementById('manualIds').value;
            const ids = input.split(',').map(s => parseInt(s.trim(), 10)).filter(n => !isNaN(n) && n > 0);
            
            if (ids.length === 0) {
                alert('No valid IDs entered');
                return;
            }

            const payload = { document_ids: ids };
            logDebug('Manual JSON Payload', payload);

            fetch('documents/delete_multiple.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => {
                logDebug('Response Status', { status: r.status, ok: r.ok });
                return r.json();
            })
            .then(data => {
                logDebug('Response Data', data);
                document.getElementById('validationResult').innerHTML = `
                    <div class="test-result ${data.success ? 'pass' : 'fail'}">
                        <strong>${data.success ? '✓ SUCCESS' : '✗ FAILED'}</strong><br>
                        ${data.message}<br>
                        Deleted: ${data.deleted_count || 0}, Failed: ${data.failed_count || 0}
                    </div>
                `;
            })
            .catch(error => {
                logDebug('Fetch Error', { message: error.message });
                alert('Error: ' + error.message);
            });
        }

        // Test delete selected
        function testDeleteSelected() {
            const checkboxes = document.querySelectorAll('.test-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Select minimal 1 document');
                return false;
            }

            const ids = Array.from(checkboxes).map(cb => parseInt(cb.value, 10)).filter(n => !isNaN(n));
            
            if (confirm(`Delete ${ids.length} documents?`)) {
                const payload = { document_ids: ids };
                console.log('Test Delete Payload:', payload);

                fetch('documents/delete_multiple.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(data => {
                    console.log('Response:', data);
                    logDebug('Delete Test Response', data);
                    alert((data.success ? '✓ ' : '✗ ') + data.message);
                })
                .catch(e => {
                    console.error('Error:', e);
                    alert('Error: ' + e.message);
                });
            }

            return false;
        }

        // Load documents for quick test
        fetch('documents/delete_multiple.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ document_ids: [] })
        }).catch(() => {
            // Expected to fail, just checking endpoint exists
        });
    </script>
</body>
</html>
