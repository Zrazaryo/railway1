<?php
require_once __DIR__ . '/bootstrap_vercel.php';
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
require_login();

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit();
    }
    
    $action = $input['action'] ?? 'LOCATION_UPDATE';
    $latitude = isset($input['latitude']) ? floatval($input['latitude']) : null;
    $longitude = isset($input['longitude']) ? floatval($input['longitude']) : null;
    $accuracy = isset($input['accuracy']) ? floatval($input['accuracy']) : null;
    $altitude = isset($input['altitude']) ? floatval($input['altitude']) : null;
    $timestamp = isset($input['timestamp']) ? intval($input['timestamp']) : time() * 1000;
    $user_agent = $input['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
    $timezone = $input['timezone'] ?? null;
    
    // Validasi koordinat
    if ($latitude === null || $longitude === null) {
        echo json_encode(['success' => false, 'message' => 'Latitude dan longitude diperlukan']);
        exit();
    }
    
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        echo json_encode(['success' => false, 'message' => 'Koordinat tidak valid']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Cek apakah kolom geolocation sudah ada di database
    $has_geolocation_columns = has_geolocation_columns();
    
    if (!$has_geolocation_columns) {
        echo json_encode([
            'success' => false,
            'message' => 'Geolocation columns not available in database'
        ]);
        exit();
    }
    
    // Prepare geolocation data
    $geolocation_timestamp = date('Y-m-d H:i:s', $timestamp / 1000);
    $address_info = null;
    
    // Get reverse geocoding untuk aksi GPS_ENABLED
    if ($action === 'GPS_ENABLED') {
        try {
            // Coba reverse geocoding sederhana
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'SistemArsipDokumen/1.0'
                ]
            ]);
            
            $response = @file_get_contents(
                "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=18&addressdetails=1",
                false,
                $context
            );
            
            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data && isset($data['address'])) {
                    $addr = $data['address'];
                    $address_info = json_encode([
                        'formatted_address' => $data['display_name'],
                        'road' => $addr['road'] ?? '',
                        'suburb' => $addr['suburb'] ?? $addr['neighbourhood'] ?? '',
                        'city' => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? '',
                        'state' => $addr['state'] ?? '',
                        'country' => $addr['country'] ?? '',
                        'postcode' => $addr['postcode'] ?? ''
                    ]);
                }
            }
        } catch (Exception $e) {
            // Ignore geocoding errors
        }
    }
    
    // Deteksi perubahan lokasi yang mencurigakan
    $location_anomaly = null;
    if ($action === 'REALTIME_UPDATE' || $action === 'GPS_ENABLED') {
        $anomaly_result = detect_location_anomaly($user_id, $latitude, $longitude);
        if ($anomaly_result['is_suspicious']) {
            $location_anomaly = json_encode($anomaly_result);
        }
    }
    
    // Simpan ke activity_logs
    $description = '';
    switch ($action) {
        case 'GPS_ENABLED':
            $description = 'GPS berhasil diaktifkan pada dashboard';
            break;
        case 'REALTIME_UPDATE':
            $description = 'Update lokasi real-time';
            break;
        case 'LOCATION_UPDATE':
            $description = 'Update lokasi manual';
            break;
        case 'GEOLOCATION_UPDATE':
            $description = 'Update geolocation';
            break;
        default:
            $description = 'Update lokasi GPS';
    }
    
    // Tambahkan informasi anomaly ke description jika ada
    if ($location_anomaly) {
        $anomaly_data = json_decode($location_anomaly, true);
        $description .= ' - ALERT: ' . $anomaly_data['message'];
    }
    
    $sql = "INSERT INTO activity_logs (
        user_id, action, description, ip_address, user_agent,
        latitude, longitude, accuracy, altitude, timezone, address_info, geolocation_timestamp
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $ip_address = get_client_ip();
    
    $db->execute($sql, [
        $user_id,
        $action,
        $description,
        $ip_address,
        $user_agent,
        $latitude,
        $longitude,
        $accuracy,
        $altitude,
        $timezone,
        $address_info,
        $geolocation_timestamp
    ]);
    
    // Update session untuk GPS enabled actions
    if ($action === 'GPS_ENABLED' || $action === 'REALTIME_UPDATE') {
        $_SESSION['gps_enabled'] = true;
        $_SESSION['last_location'] = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'timestamp' => $timestamp,
            'address' => $address_info ? json_decode($address_info, true) : null
        ];
        $_SESSION['last_gps_update'] = time();
    }
    
    // Response data
    $response_data = [
        'success' => true,
        'message' => 'Lokasi berhasil disimpan',
        'data' => [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'timestamp' => $geolocation_timestamp,
            'action' => $action
        ]
    ];
    
    // Tambahkan address info jika ada
    if ($address_info) {
        $response_data['data']['address'] = json_decode($address_info, true);
    }
    
    // Tambahkan warning jika ada anomaly
    if ($location_anomaly) {
        $response_data['warning'] = json_decode($location_anomaly, true);
    }
    
    echo json_encode($response_data);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error menyimpan lokasi: ' . $e->getMessage()
    ]);
}
?>