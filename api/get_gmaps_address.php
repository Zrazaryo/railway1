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
    
    if (!$input || !isset($input['latitude'], $input['longitude'])) {
        echo json_encode(['success' => false, 'message' => 'Latitude dan longitude diperlukan']);
        exit();
    }
    
    $latitude = floatval($input['latitude']);
    $longitude = floatval($input['longitude']);
    
    // Validasi koordinat
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        echo json_encode(['success' => false, 'message' => 'Koordinat tidak valid']);
        exit();
    }
    
    // Google Maps API Key (ganti dengan API key yang valid)
    // Untuk demo, kita akan menggunakan service gratis alternatif
    $google_api_key = 'YOUR_GOOGLE_MAPS_API_KEY'; // Ganti dengan API key yang valid
    
    // Cek cache terlebih dahulu
    $cache_key = 'gmaps_' . md5($latitude . '_' . $longitude);
    $cache_file = sys_get_temp_dir() . '/' . $cache_key . '.cache';
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 86400) { // Cache 24 jam
        $cached_data = file_get_contents($cache_file);
        $address_data = json_decode($cached_data, true);
        
        echo json_encode([
            'success' => true,
            'address' => $address_data,
            'source' => 'cache'
        ]);
        exit();
    }
    
    // Jika tidak ada Google API key yang valid, gunakan service alternatif
    if ($google_api_key === 'YOUR_GOOGLE_MAPS_API_KEY') {
        // Menggunakan service gratis alternatif (Nominatim OpenStreetMap)
        $address_data = getAddressFromNominatim($latitude, $longitude);
    } else {
        // Menggunakan Google Maps Geocoding API
        $address_data = getAddressFromGoogleMaps($latitude, $longitude, $google_api_key);
    }
    
    if ($address_data) {
        // Simpan ke cache
        file_put_contents($cache_file, json_encode($address_data));
        
        echo json_encode([
            'success' => true,
            'address' => $address_data,
            'source' => 'api'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Tidak dapat mendapatkan alamat dari koordinat tersebut'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Mendapatkan alamat dari Google Maps Geocoding API
 */
function getAddressFromGoogleMaps($lat, $lng, $api_key) {
    try {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$api_key}&language=id";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'SistemArsipDokumen/1.0'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && $data['status'] === 'OK' && !empty($data['results'])) {
                $result = $data['results'][0];
                
                // Parse komponen alamat
                $address_components = [];
                foreach ($result['address_components'] as $component) {
                    $types = $component['types'];
                    $address_components[implode('|', $types)] = [
                        'long_name' => $component['long_name'],
                        'short_name' => $component['short_name']
                    ];
                }
                
                return [
                    'formatted_address' => $result['formatted_address'],
                    'street_number' => getAddressComponent($address_components, 'street_number'),
                    'route' => getAddressComponent($address_components, 'route'),
                    'sublocality' => getAddressComponent($address_components, 'sublocality_level_1|sublocality'),
                    'locality' => getAddressComponent($address_components, 'locality'),
                    'administrative_area_level_2' => getAddressComponent($address_components, 'administrative_area_level_2'),
                    'administrative_area_level_1' => getAddressComponent($address_components, 'administrative_area_level_1'),
                    'country' => getAddressComponent($address_components, 'country'),
                    'postal_code' => getAddressComponent($address_components, 'postal_code'),
                    'place_id' => $result['place_id'] ?? '',
                    'types' => $result['types'] ?? [],
                    'geometry' => [
                        'location' => $result['geometry']['location'],
                        'location_type' => $result['geometry']['location_type'] ?? '',
                        'viewport' => $result['geometry']['viewport'] ?? null
                    ],
                    'source' => 'google_maps'
                ];
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log('Google Maps API Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Fallback menggunakan Nominatim OpenStreetMap
 */
function getAddressFromNominatim($lat, $lng) {
    try {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1&accept-language=id";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'SistemArsipDokumen/1.0'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && isset($data['address'])) {
                $addr = $data['address'];
                
                return [
                    'formatted_address' => $data['display_name'],
                    'street_number' => $addr['house_number'] ?? '',
                    'route' => $addr['road'] ?? '',
                    'sublocality' => $addr['suburb'] ?? $addr['neighbourhood'] ?? '',
                    'locality' => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? '',
                    'administrative_area_level_2' => $addr['county'] ?? '',
                    'administrative_area_level_1' => $addr['state'] ?? '',
                    'country' => $addr['country'] ?? '',
                    'postal_code' => $addr['postcode'] ?? '',
                    'place_id' => $data['place_id'] ?? '',
                    'types' => [$data['type'] ?? 'geocode'],
                    'geometry' => [
                        'location' => [
                            'lat' => floatval($data['lat']),
                            'lng' => floatval($data['lon'])
                        ],
                        'location_type' => 'APPROXIMATE',
                        'viewport' => null
                    ],
                    'source' => 'openstreetmap'
                ];
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log('Nominatim API Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Helper function untuk mengambil komponen alamat
 */
function getAddressComponent($components, $type) {
    $types = explode('|', $type);
    
    foreach ($components as $key => $component) {
        $component_types = explode('|', $key);
        
        foreach ($types as $search_type) {
            if (in_array($search_type, $component_types)) {
                return $component['long_name'];
            }
        }
    }
    
    return '';
}
?>