-- Create table for local IP detections
CREATE TABLE IF NOT EXISTS `local_ip_detections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `server_detected_ip` varchar(45) DEFAULT NULL,
  `local_ips_data` longtext DEFAULT NULL,
  `network_info` text DEFAULT NULL,
  `client_info` text DEFAULT NULL,
  `total_local_ips` int(11) DEFAULT 0,
  `ipv4_count` int(11) DEFAULT 0,
  `ipv6_count` int(11) DEFAULT 0,
  `local_count` int(11) DEFAULT 0,
  `public_count` int(11) DEFAULT 0,
  `detection_methods` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;