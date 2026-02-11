-- Menambahkan kolom geolocation ke tabel activity_logs
-- Sistem Arsip Dokumen - HTML5 Geolocation Support

ALTER TABLE activity_logs 
ADD COLUMN latitude DECIMAL(10, 8) NULL COMMENT 'Latitude koordinat GPS',
ADD COLUMN longitude DECIMAL(11, 8) NULL COMMENT 'Longitude koordinat GPS',
ADD COLUMN accuracy DECIMAL(10, 2) NULL COMMENT 'Akurasi GPS dalam meter',
ADD COLUMN altitude DECIMAL(10, 2) NULL COMMENT 'Ketinggian dalam meter',
ADD COLUMN timezone VARCHAR(50) NULL COMMENT 'Timezone user',
ADD COLUMN address_info TEXT NULL COMMENT 'Informasi alamat dari reverse geocoding (JSON)',
ADD COLUMN geolocation_timestamp DATETIME NULL COMMENT 'Timestamp dari GPS device';

-- Index untuk pencarian berdasarkan lokasi
CREATE INDEX idx_activity_logs_location ON activity_logs(latitude, longitude);
CREATE INDEX idx_activity_logs_geo_timestamp ON activity_logs(geolocation_timestamp);

-- Contoh query untuk mencari aktivitas dalam radius tertentu (menggunakan Haversine formula)
-- SELECT *, 
--   (6371 * acos(cos(radians(-6.2088)) * cos(radians(latitude)) * cos(radians(longitude) - radians(106.8456)) + sin(radians(-6.2088)) * sin(radians(latitude)))) AS distance 
-- FROM activity_logs 
-- WHERE latitude IS NOT NULL AND longitude IS NOT NULL
-- HAVING distance < 10 
-- ORDER BY distance;