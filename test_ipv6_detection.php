<?php
require_once 'includes/functions.php';

echo "<h2>Test IPv6 Detection</h2>";

// Test fungsi get_client_ip()
echo "<h3>1. Current IP Detection</h3>";
$detected_ip = get_client_ip();
echo "<strong>Detected IP:</strong> " . $detected_ip . "<br>";

// Cek semua header yang mungkin berisi IP
echo "<h3>2. All IP Headers</h3>";
$ip_headers = [
    'HTTP_CF_CONNECTING_IP',     // Cloudflare
    'HTTP_CLIENT_IP',            // Proxy
    'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
    'HTTP_X_FORWARDED',          // Proxy
    'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
    'HTTP_FORWARDED_FOR',        // Proxy
    'HTTP_FORWARDED',            // Proxy
    'REMOTE_ADDR'                // Standard
];

foreach ($ip_headers as $header) {
    $value = $_SERVER[$header] ?? 'Not set';
    echo "<strong>$header:</strong> $value<br>";
}

// Test IPv6 validation
echo "<h3>3. IPv6 Validation Test</h3>";
$test_ipv6_addresses = [
    '2001:db8::1',                    // Documentation
    '::1',                            // Loopback
    'fe80::1',                        // Link-local
    '2001:4860:4860::8888',          // Google DNS
    'fc00::1',                        // Unique local
    'ff02::1',                        // Multicast
    '2002:c000:0204::1',             // 6to4
    '2001:0:9d38:6ab8:1c48:3a1c:a95a:b1c2' // Teredo
];

foreach ($test_ipv6_addresses as $test_ip) {
    $is_valid = filter_var($test_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    $analysis = analyze_ipv6($test_ip);
    
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc;'>";
    echo "<strong>IP:</strong> $test_ip<br>";
    echo "<strong>Valid:</strong> " . ($is_valid ? 'Yes' : 'No') . "<br>";
    echo "<strong>Type:</strong> " . $analysis['range_info'] . "<br>";
    echo "<strong>Public:</strong> " . ($analysis['is_public'] ? 'Yes' : 'No') . "<br>";
    echo "</div>";
}

// Test dengan IP yang mungkin ada di environment
echo "<h3>4. Environment IP Test</h3>";
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $forwarded_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    foreach ($forwarded_ips as $ip) {
        $ip = trim($ip);
        echo "<strong>Forwarded IP:</strong> $ip<br>";
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            echo "→ This is IPv6!<br>";
            $analysis = analyze_ipv6($ip);
            echo "→ Type: " . $analysis['range_info'] . "<br>";
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            echo "→ This is IPv4<br>";
        } else {
            echo "→ Invalid IP<br>";
        }
        echo "<br>";
    }
}

// Test manual IPv6 input
echo "<h3>5. Manual IPv6 Test</h3>";
if (isset($_GET['test_ip'])) {
    $test_ip = $_GET['test_ip'];
    echo "<strong>Testing IP:</strong> $test_ip<br>";
    
    if (filter_var($test_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        echo "<strong>Result:</strong> Valid IPv6<br>";
        $analysis = analyze_ipv6($test_ip);
        echo "<strong>Analysis:</strong><br>";
        echo "- Range: " . $analysis['range_info'] . "<br>";
        echo "- Public: " . ($analysis['is_public'] ? 'Yes' : 'No') . "<br>";
        echo "- Link-local: " . ($analysis['is_link_local'] ? 'Yes' : 'No') . "<br>";
        echo "- Unique local: " . ($analysis['is_unique_local'] ? 'Yes' : 'No') . "<br>";
        echo "- Loopback: " . ($analysis['is_loopback'] ? 'Yes' : 'No') . "<br>";
        
        // Test format function
        echo "<br><strong>Formatted display:</strong><br>";
        echo format_ipv6_info($test_ip);
        
    } elseif (filter_var($test_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        echo "<strong>Result:</strong> Valid IPv4<br>";
    } else {
        echo "<strong>Result:</strong> Invalid IP<br>";
    }
}

echo "<h3>6. Test IPv6 Input</h3>";
echo '<form method="GET">';
echo '<input type="text" name="test_ip" placeholder="Enter IPv6 address" value="' . ($_GET['test_ip'] ?? '2001:db8::1') . '">';
echo '<input type="submit" value="Test">';
echo '</form>';

// Debugging info
echo "<h3>7. Debug Info</h3>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>IPv6 Support:</strong> " . (defined('AF_INET6') ? 'Yes' : 'No') . "<br>";
echo "<strong>Filter Extension:</strong> " . (extension_loaded('filter') ? 'Yes' : 'No') . "<br>";

// Test server environment
echo "<h3>8. Server Environment</h3>";
echo "<strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "<strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "<br>";
echo "<strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";

// Check if running behind proxy/load balancer
echo "<h3>9. Proxy Detection</h3>";
$proxy_headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP'];
$behind_proxy = false;
foreach ($proxy_headers as $header) {
    if (!empty($_SERVER[$header])) {
        $behind_proxy = true;
        echo "<strong>$header:</strong> " . $_SERVER[$header] . "<br>";
    }
}
if (!$behind_proxy) {
    echo "No proxy headers detected<br>";
}

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
div { margin: 5px 0; }
</style>