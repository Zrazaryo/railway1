<?php
require_once __DIR__ . '/bootstrap_vercel.php';
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

$action = $input['action'] ?? '';

try {
    if ($action === 'save_local_ip') {
        // Get data from input
        $local_ips = $input['local_ips'] ?? [];
        $network_info = $input['network_info'] ?? [];
        $client_info = $input['client_info'] ?? [];
        
        // Get server-detected IP
        $server_ip = get_client_ip();
        
        // Get user ID if logged in
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Prepare data for storage
        $ip_data = [
            'server_detected_ip' => $server_ip,
            'local_ips' => $local_ips,
            'network_info' => $network_info,
            'client_info' => $client_info,
            'detection_timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id()
        ];
        
        // Create table if not exists (using correct column names)
        $create_table_sql = "
            CREATE TABLE IF NOT EXISTS local_ip_detections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                session_id VARCHAR(255),
                server_detected_ip VARCHAR(45),
                local_ips_data LONGTEXT,
                network_info TEXT,
                client_info TEXT,
                total_local_ips INT DEFAULT 0,
                ipv4_count INT DEFAULT 0,
                ipv6_count INT DEFAULT 0,
                local_count INT DEFAULT 0,
                public_count INT DEFAULT 0,
                detection_methods TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_session_id (session_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->execute($create_table_sql);
        
        // Calculate statistics
        $stats = [
            'total_local_ips' => count($local_ips),
            'ipv4_count' => 0,
            'ipv6_count' => 0,
            'local_count' => 0,
            'public_count' => 0
        ];
        
        foreach ($local_ips as $ip_info) {
            if ($ip_info['type'] === 'IPv4') $stats['ipv4_count']++;
            if ($ip_info['type'] === 'IPv6') $stats['ipv6_count']++;
            if ($ip_info['isLocal']) $stats['local_count']++;
            if ($ip_info['isPublic']) $stats['public_count']++;
        }
        
        // Check if record exists for this session
        $existing = $db->fetch(
            "SELECT id FROM local_ip_detections WHERE session_id = ? ORDER BY created_at DESC LIMIT 1",
            [session_id()]
        );
        
        if ($existing) {
            // Update existing record
            $sql = "UPDATE local_ip_detections SET 
                    user_id = ?, 
                    server_detected_ip = ?,
                    local_ips_data = ?,
                    network_info = ?,
                    client_info = ?,
                    total_local_ips = ?,
                    ipv4_count = ?,
                    ipv6_count = ?,
                    local_count = ?,
                    public_count = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $db->execute($sql, [
                $user_id,
                $server_ip,
                json_encode($local_ips),
                json_encode($network_info),
                json_encode($client_info),
                $stats['total_local_ips'],
                $stats['ipv4_count'],
                $stats['ipv6_count'],
                $stats['local_count'],
                $stats['public_count'],
                $existing['id']
            ]);
            
            $record_id = $existing['id'];
        } else {
            // Insert new record
            $sql = "INSERT INTO local_ip_detections 
                    (user_id, session_id, server_detected_ip, local_ips_data, network_info, 
                     client_info, total_local_ips, ipv4_count, ipv6_count, local_count, public_count) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $db->execute($sql, [
                $user_id,
                session_id(),
                $server_ip,
                json_encode($local_ips),
                json_encode($network_info),
                json_encode($client_info),
                $stats['total_local_ips'],
                $stats['ipv4_count'],
                $stats['ipv6_count'],
                $stats['local_count'],
                $stats['public_count']
            ]);
            
            $record_id = $db->lastInsertId();
        }
        
        // Log activity if user is logged in
        if ($user_id) {
            $description = "Local IP detection: " . count($local_ips) . " IPs detected";
            log_activity($user_id, 'LOCAL_IP_DETECTION', $description);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Local IP data saved successfully',
            'record_id' => $record_id,
            'server_detected_ip' => $server_ip,
            'statistics' => $stats,
            'local_ips_detected' => count($local_ips)
        ]);
        
    } elseif ($action === 'get_ip_info') {
        // Return server-detected IP and other info
        $server_ip = get_client_ip();
        
        // Analyze the IP
        $ip_analysis = [];
        if (filter_var($server_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip_analysis = analyze_ipv6($server_ip);
            $ip_analysis['type'] = 'IPv6';
        } elseif (filter_var($server_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_analysis['type'] = 'IPv4';
            $ip_analysis['is_public'] = filter_var($server_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            $ip_analysis['is_private'] = !$ip_analysis['is_public'];
        }
        
        echo json_encode([
            'success' => true,
            'server_detected_ip' => $server_ip,
            'ip_analysis' => $ip_analysis,
            'headers' => [
                'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
                'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
                'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
                'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null
            ]
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>