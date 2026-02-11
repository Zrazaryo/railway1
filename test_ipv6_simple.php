<?php
// Test sederhana untuk IPv6 detection
require_once 'includes/functions.php';

echo "<h1>IPv6 Detection Test</h1>";

// Simulasi berbagai skenario IPv6
$test_scenarios = [
    'REMOTE_ADDR' => '2001:db8::1',
    'HTTP_X_FORWARDED_FOR' => '2001:4860:4860::8888, 192.168.1.1',
    'HTTP_CF_CONNECTING_IP' => 'fe80::1',
    'HTTP_X_REAL_IP' => '2001:db8:85a3::8a2e:370:7334'
];

echo "<h2>Test Scenarios</h2>";

foreach ($test_scenarios as $header => $ip_value) {
    echo "<h3>Scenario: $header = $ip_value</h3>";
    
    // Backup original values
    $original_values = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0 || $key === 'REMOTE_ADDR') {
            $original_values[$key] = $value;
        }
    }
    
    // Clear all IP headers
    unset($_SERVER['HTTP_CF_CONNECTING_IP']);
    unset($_SERVER['HTTP_CLIENT_IP']);
    unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    unset($_SERVER['HTTP_X_REAL_IP']);
    unset($_SERVER['HTTP_X_FORWARDED']);
    unset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']);
    unset($_SERVER['HTTP_FORWARDED_FOR']);
    unset($_SERVER['HTTP_FORWARDED']);
    unset($_SERVER['REMOTE_ADDR']);
    
    // Set test scenario
    $_SERVER[$header] = $ip_value;
    
    // Test detection
    $detected_ip = get_client_ip();
    echo "<strong>Detected IP:</strong> $detected_ip<br>";
    
    // Analyze the IP
    if (filter_var($detected_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        echo "<strong>Type:</strong> IPv6<br>";
        $analysis = analyze_ipv6($detected_ip);
        echo "<strong>Range:</strong> " . $analysis['range_info'] . "<br>";
        echo "<strong>Public:</strong> " . ($analysis['is_public'] ? 'Yes' : 'No') . "<br>";
        
        // Test formatting
        echo "<strong>Formatted:</strong><br>";
        echo format_ipv6_info($detected_ip);
        
    } elseif (filter_var($detected_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        echo "<strong>Type:</strong> IPv4<br>";
    } else {
        echo "<strong>Type:</strong> Invalid/Unknown<br>";
    }
    
    echo "<hr>";
    
    // Restore original values
    foreach ($original_values as $key => $value) {
        $_SERVER[$key] = $value;
    }
}

// Test current environment
echo "<h2>Current Environment</h2>";
$current_ip = get_client_ip();
echo "<strong>Current Detected IP:</strong> $current_ip<br>";

if (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    echo "<strong>✅ IPv6 detected successfully!</strong><br>";
    $analysis = analyze_ipv6($current_ip);
    echo "<strong>Analysis:</strong> " . $analysis['range_info'] . "<br>";
} elseif (filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    echo "<strong>ℹ️ IPv4 detected</strong><br>";
} else {
    echo "<strong>❌ No valid IP detected</strong><br>";
}

// Show all headers
echo "<h2>All HTTP Headers</h2>";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0 || $key === 'REMOTE_ADDR') {
        echo "<strong>$key:</strong> $value<br>";
    }
}

// Manual test form
echo "<h2>Manual Test</h2>";
if (isset($_POST['manual_ip'])) {
    $manual_ip = $_POST['manual_ip'];
    echo "<h3>Testing: $manual_ip</h3>";
    
    if (filter_var($manual_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        echo "<strong>✅ Valid IPv6</strong><br>";
        $analysis = analyze_ipv6($manual_ip);
        echo "<strong>Range:</strong> " . $analysis['range_info'] . "<br>";
        echo "<strong>Public:</strong> " . ($analysis['is_public'] ? 'Yes' : 'No') . "<br>";
        echo "<strong>Formatted display:</strong><br>";
        echo format_ipv6_info($manual_ip);
    } elseif (filter_var($manual_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        echo "<strong>✅ Valid IPv4</strong><br>";
    } else {
        echo "<strong>❌ Invalid IP address</strong><br>";
    }
}

echo '<form method="POST">';
echo '<input type="text" name="manual_ip" placeholder="Enter IPv6 address" value="2001:db8::1">';
echo '<input type="submit" value="Test IP">';
echo '</form>';

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
hr { margin: 20px 0; }
form { margin: 10px 0; }
input[type="text"] { padding: 5px; width: 200px; }
input[type="submit"] { padding: 5px 10px; }
</style>