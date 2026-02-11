<?php
session_start();

// Mock session untuk testing
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "    <title>Test Local IP API</title>\n";
echo "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container mt-4'>\n";
echo "    <h2>Test Local IP Detection API</h2>\n";
echo "    \n";
echo "    <div class='row'>\n";
echo "        <div class='col-md-6'>\n";
echo "            <div class='card'>\n";
echo "                <div class='card-header'>\n";
echo "                    <h5>Test Save Local IP</h5>\n";
echo "                </div>\n";
echo "                <div class='card-body'>\n";
echo "                    <button class='btn btn-primary' onclick='testSaveLocalIP()'>Test Save API</button>\n";
echo "                    <div id='save-result' class='mt-3'></div>\n";
echo "                </div>\n";
echo "            </div>\n";
echo "        </div>\n";
echo "        \n";
echo "        <div class='col-md-6'>\n";
echo "            <div class='card'>\n";
echo "                <div class='card-header'>\n";
echo "                    <h5>Test Get History</h5>\n";
echo "                </div>\n";
echo "                <div class='card-body'>\n";
echo "                    <button class='btn btn-success' onclick='testGetHistory()'>Test History API</button>\n";
echo "                    <div id='history-result' class='mt-3'></div>\n";
echo "                </div>\n";
echo "            </div>\n";
echo "        </div>\n";
echo "    </div>\n";
echo "    \n";
echo "    <div class='row mt-4'>\n";
echo "        <div class='col-12'>\n";
echo "            <div class='card'>\n";
echo "                <div class='card-header'>\n";
echo "                    <h5>Database Check</h5>\n";
echo "                </div>\n";
echo "                <div class='card-body'>\n";

// Check database table
require_once 'config/database.php';

try {
    $tables = $db->fetchAll("SHOW TABLES LIKE 'local_ip_detections'");
    if (!empty($tables)) {
        echo "                    <div class='alert alert-success'>✅ Table 'local_ip_detections' exists</div>\n";
        
        // Check table structure
        $structure = $db->fetchAll("DESCRIBE local_ip_detections");
        echo "                    <h6>Table Structure:</h6>\n";
        echo "                    <div class='table-responsive'>\n";
        echo "                        <table class='table table-sm'>\n";
        echo "                            <thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr></thead>\n";
        echo "                            <tbody>\n";
        foreach ($structure as $column) {
            echo "                                <tr>\n";
            echo "                                    <td><code>{$column['Field']}</code></td>\n";
            echo "                                    <td>{$column['Type']}</td>\n";
            echo "                                    <td>{$column['Null']}</td>\n";
            echo "                                    <td>{$column['Key']}</td>\n";
            echo "                                </tr>\n";
        }
        echo "                            </tbody>\n";
        echo "                        </table>\n";
        echo "                    </div>\n";
        
        // Check existing records
        $count = $db->fetch("SELECT COUNT(*) as count FROM local_ip_detections");
        echo "                    <p>Existing records: <strong>{$count['count']}</strong></p>\n";
        
    } else {
        echo "                    <div class='alert alert-danger'>❌ Table 'local_ip_detections' does not exist</div>\n";
    }
} catch (Exception $e) {
    echo "                    <div class='alert alert-danger'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

echo "                </div>\n";
echo "            </div>\n";
echo "        </div>\n";
echo "    </div>\n";
echo "</div>\n";
echo "\n";
echo "<script>\n";
echo "function testSaveLocalIP() {\n";
echo "    const resultDiv = document.getElementById('save-result');\n";
echo "    resultDiv.innerHTML = '<div class=\"spinner-border\" role=\"status\"></div> Testing...';\n";
echo "    \n";
echo "    const testData = {\n";
echo "        action: 'save_local_ip',\n";
echo "        local_ips: [\n";
echo "            {\n";
echo "                ip: '192.168.1.100',\n";
echo "                type: 'IPv4',\n";
echo "                source: 'WebRTC',\n";
echo "                timestamp: new Date().toISOString(),\n";
echo "                isLocal: true,\n";
echo "                isPublic: false\n";
echo "            },\n";
echo "            {\n";
echo "                ip: '2001:db8::1',\n";
echo "                type: 'IPv6',\n";
echo "                source: 'WebRTC',\n";
echo "                timestamp: new Date().toISOString(),\n";
echo "                isLocal: false,\n";
echo "                isPublic: true\n";
echo "            }\n";
echo "        ],\n";
echo "        network_info: {\n";
echo "            type: 'wifi',\n";
echo "            effectiveType: '4g'\n";
echo "        },\n";
echo "        client_info: {\n";
echo "            user_agent: navigator.userAgent,\n";
echo "            platform: navigator.platform,\n";
echo "            language: navigator.language\n";
echo "        }\n";
echo "    };\n";
echo "    \n";
echo "    fetch('api/save_local_ip.php', {\n";
echo "        method: 'POST',\n";
echo "        headers: {\n";
echo "            'Content-Type': 'application/json',\n";
echo "        },\n";
echo "        body: JSON.stringify(testData)\n";
echo "    })\n";
echo "    .then(response => response.json())\n";
echo "    .then(data => {\n";
echo "        if (data.success) {\n";
echo "            resultDiv.innerHTML = '<div class=\"alert alert-success\">✅ Save API works! Record ID: ' + data.record_id + '</div>';\n";
echo "        } else {\n";
echo "            resultDiv.innerHTML = '<div class=\"alert alert-danger\">❌ Save API failed: ' + data.message + '</div>';\n";
echo "        }\n";
echo "    })\n";
echo "    .catch(error => {\n";
echo "        resultDiv.innerHTML = '<div class=\"alert alert-danger\">❌ Error: ' + error.message + '</div>';\n";
echo "    });\n";
echo "}\n";
echo "\n";
echo "function testGetHistory() {\n";
echo "    const resultDiv = document.getElementById('history-result');\n";
echo "    resultDiv.innerHTML = '<div class=\"spinner-border\" role=\"status\"></div> Testing...';\n";
echo "    \n";
echo "    fetch('api/get_local_ip_history.php', {\n";
echo "        method: 'POST',\n";
echo "        headers: {\n";
echo "            'Content-Type': 'application/json',\n";
echo "        }\n";
echo "    })\n";
echo "    .then(response => response.json())\n";
echo "    .then(data => {\n";
echo "        if (data.success) {\n";
echo "            resultDiv.innerHTML = '<div class=\"alert alert-success\">✅ History API works!</div><div>' + data.html + '</div>';\n";
echo "        } else {\n";
echo "            resultDiv.innerHTML = '<div class=\"alert alert-danger\">❌ History API failed: ' + data.message + '</div>';\n";
echo "        }\n";
echo "    })\n";
echo "    .catch(error => {\n";
echo "        resultDiv.innerHTML = '<div class=\"alert alert-danger\">❌ Error: ' + error.message + '</div>';\n";
echo "    });\n";
echo "}\n";
echo "</script>\n";
echo "\n";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>\n";
echo "</body>\n";
echo "</html>\n";
?>