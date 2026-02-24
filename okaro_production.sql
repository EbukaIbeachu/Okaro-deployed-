-- Okaro & Associates Database Export
-- Generated: 2026-01-30 21:32:46

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- Table structure for table `buildings`
DROP TABLE IF EXISTS `buildings`;
CREATE TABLE `buildings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line1` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line2` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_units` int(10) unsigned NOT NULL DEFAULT '0',
  `total_floors` int(10) unsigned NOT NULL DEFAULT '1',
  `image_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `buildings`
INSERT INTO `buildings` VALUES ('1', 'Sunset Heights Apartments', '123 Marina View Drive', NULL, 'Lagos', 'Lagos', '101241', '80', '4', 'buildings/e0kAwVkSvXqtXHgOdY10tbvbI22rg2GgrUPPZuBR.jpg', '2026-01-30 18:15:14', '2026-01-30 20:08:23');

-- Table structure for table `maintenance_requests`
DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE `maintenance_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('PLUMBING','ELECTRICAL','HVAC','STRUCTURAL','APPLIANCE','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OTHER',
  `priority` enum('LOW','MEDIUM','HIGH','EMERGENCY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOW',
  `status` enum('PENDING','IN_PROGRESS','RESOLVED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_requests_unit_id_foreign` (`unit_id`),
  KEY `maintenance_requests_tenant_id_foreign` (`tenant_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `maintenance_requests`
INSERT INTO `maintenance_requests` VALUES ('1', '1', '1', 'Broken pipe', 'Broken pipe', 'PLUMBING', 'MEDIUM', 'PENDING', NULL, '2026-01-30 20:10:18', '2026-01-30 20:10:18'),
('2', '1', '1', 'Bad wiring', 'Bad wiring', 'ELECTRICAL', 'MEDIUM', 'PENDING', NULL, '2026-01-30 21:13:05', '2026-01-30 21:13:05');

-- Table structure for table `migrations`
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `migrations`
INSERT INTO `migrations` VALUES ('1', '2019_12_14_000001_create_personal_access_tokens_table', '1'),
('2', '2024_01_30_000000_create_roles_table', '1'),
('3', '2024_01_30_000001_create_users_table', '1'),
('4', '2024_01_30_000002_create_buildings_table', '1'),
('5', '2024_01_30_000003_create_units_table', '1'),
('6', '2024_01_30_000004_create_tenants_table', '1'),
('7', '2024_01_30_000005_create_rents_table', '1'),
('8', '2024_01_30_000006_create_payments_table', '1'),
('9', '2026_01_30_153624_add_status_to_rents_table', '2'),
('10', '2026_01_30_153749_add_status_to_payments_table', '3'),
('11', '2026_01_30_162559_add_description_to_roles_table', '4'),
('12', '2026_01_30_162716_add_phone_to_users_table', '5'),
('13', '2026_01_30_164348_rename_monthly_amount_to_annual_amount_in_rents_table', '6'),
('14', '2026_01_30_181331_add_image_path_to_buildings_table', '7'),
('15', '2026_01_30_185039_add_signed_agreement_path_to_rents_table', '8'),
('16', '2026_01_30_200000_update_buildings_and_create_maintenance', '9'),
('17', '2026_01_30_201441_add_profile_image_to_tenants_table', '10'),
('19', '2026_01_30_203958_make_unit_id_nullable_in_tenants_table', '11'),
('20', '2026_01_30_205838_add_due_date_to_payments_table', '12');

-- Table structure for table `payments`
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rent_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETED',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_rent_id_index` (`rent_id`),
  KEY `payments_payment_date_index` (`payment_date`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `payments`
INSERT INTO `payments` VALUES ('1', '1', '2026-01-30', '2027-02-28', '250000.00', 'COMPLETED', 'BANK_TRANSFER', NULL, NULL, '2026-01-30 18:20:33', '2026-01-30 21:04:29');

-- Table structure for table `personal_access_tokens`
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `personal_access_tokens`

-- Table structure for table `rents`
DROP TABLE IF EXISTS `rents`;
CREATE TABLE `rents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `annual_amount` decimal(10,2) DEFAULT NULL,
  `due_day` tinyint(3) unsigned NOT NULL COMMENT 'Day of month rent is due (1-28)',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `signed_agreement_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rents_tenant_id_index` (`tenant_id`),
  KEY `rents_unit_id_index` (`unit_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `rents`
INSERT INTO `rents` VALUES ('1', '1', '1', '250000.00', '1', '2026-01-30', '2027-02-28', NULL, 'ACTIVE', '2026-01-30 18:19:39', '2026-01-30 18:19:39');

-- Table structure for table `roles`
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `roles`
INSERT INTO `roles` VALUES ('1', 'admin', NULL, '2026-01-30 15:22:01', '2026-01-30 15:22:01'),
('2', 'manager', NULL, '2026-01-30 15:22:01', '2026-01-30 15:22:01'),
('3', 'tenant', NULL, '2026-01-30 15:22:01', '2026-01-30 15:22:01');

-- Table structure for table `tenants`
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `move_in_date` date DEFAULT NULL,
  `move_out_date` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenants_unit_id_index` (`unit_id`),
  KEY `tenants_user_id_index` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `tenants`
INSERT INTO `tenants` VALUES ('1', '4', '1', 'Chioma Okeke', '08012345678', 'chioma.okeke@example.com', NULL, NULL, '2026-01-30', NULL, '1', '2026-01-30 18:18:44', '2026-01-30 20:52:05'),
('2', '2', NULL, 'Chioma Okeke', '08012345678', 'chioma.okeke@example.com', NULL, NULL, NULL, NULL, '1', '2026-01-30 20:44:14', '2026-01-30 20:44:14');

-- Table structure for table `units`
DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `building_id` bigint(20) unsigned NOT NULL,
  `unit_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bedrooms` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `bathrooms` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `status` enum('AVAILABLE','OCCUPIED','MAINTENANCE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AVAILABLE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_building_id_unit_number_floor_unique` (`building_id`,`unit_number`,`floor`),
  KEY `units_building_id_index` (`building_id`)
) ENGINE=MyISAM AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `units`
INSERT INTO `units` VALUES ('1', '1', 'A01', '1', '1', '1', 'OCCUPIED', '2026-01-30 18:16:56', '2026-01-30 20:11:03'),
('8', '1', 'A2', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:06:32'),
('7', '1', 'A3', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:06:48'),
('6', '1', 'A4', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:06:59'),
('9', '1', 'B1', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:07:08'),
('10', '1', 'B2', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:07:21'),
('11', '1', '202', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('12', '1', '203', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('13', '1', '204', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('14', '1', '301', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('15', '1', '302', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('16', '1', '303', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('17', '1', '304', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('18', '1', '401', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('19', '1', '402', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('20', '1', '403', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('21', '1', '404', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:05:01', '2026-01-30 20:05:01'),
('22', '1', '101', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('23', '1', '102', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('24', '1', '103', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('25', '1', '104', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('26', '1', '105', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('27', '1', '106', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('28', '1', '107', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('29', '1', '108', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('30', '1', '109', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('31', '1', '110', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('32', '1', '111', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('33', '1', '112', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('34', '1', '113', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('35', '1', '114', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('36', '1', '115', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('37', '1', '116', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('38', '1', '117', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('39', '1', '118', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('40', '1', '119', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('41', '1', '120', '1', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('42', '1', '201', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('43', '1', '205', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('44', '1', '206', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('45', '1', '207', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('46', '1', '208', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('47', '1', '209', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('48', '1', '210', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('49', '1', '211', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('50', '1', '212', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:23', '2026-01-30 20:08:23'),
('51', '1', '213', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('52', '1', '214', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('53', '1', '215', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('54', '1', '216', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('55', '1', '217', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('56', '1', '218', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('57', '1', '219', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('58', '1', '220', '2', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('59', '1', '305', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('60', '1', '306', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('61', '1', '307', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('62', '1', '308', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('63', '1', '309', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('64', '1', '310', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('65', '1', '311', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('66', '1', '312', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('67', '1', '313', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('68', '1', '314', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('69', '1', '315', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('70', '1', '316', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('71', '1', '317', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('72', '1', '318', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('73', '1', '319', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('74', '1', '320', '3', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('75', '1', '405', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('76', '1', '406', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('77', '1', '407', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('78', '1', '408', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('79', '1', '409', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('80', '1', '410', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('81', '1', '411', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('82', '1', '412', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('83', '1', '413', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('84', '1', '414', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('85', '1', '415', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('86', '1', '416', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('87', '1', '417', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('88', '1', '418', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('89', '1', '419', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24'),
('90', '1', '420', '4', '1', '1', 'AVAILABLE', '2026-01-30 20:08:24', '2026-01-30 20:08:24');

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` tinyint(3) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_index` (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES ('1', 'Super Admin', 'admin@okaro.local', NULL, '$2y$10$Ga1UAh1lN8Gv99gqO8OS5ekG3rnaW8eq7fkfZ4/eJKGwv4kiJrg8S', '1', '1', '2026-01-30 15:22:01', '2026-01-30 15:22:01'),
('4', 'Chioma Okeke', 'chioma.okeke@example.com', '08012345678', '$2y$10$pezW6Y/MgahHwjLc0T.FPu8S5Zb9x7Q/RtPiRVVfHrE92AkZQ5aK6', '3', '1', '2026-01-30 20:52:05', '2026-01-30 21:06:47'),
('3', 'Mary Maureen', 'mmaureen@gmail.com', '08134567867', '$2y$10$6McpD6859cf9ksRyzVthX.GvtNjNtNGoa8HH42dqf9YDJCDtyvCk6', '2', '1', '2026-01-30 20:41:04', '2026-01-30 21:06:10');
