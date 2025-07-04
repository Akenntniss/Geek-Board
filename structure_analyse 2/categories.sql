Table	Create Table
categories	CREATE TABLE `categories` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `nom` varchar(100) NOT NULL,\n  `description` text DEFAULT NULL,\n  `created_at` timestamp NULL DEFAULT current_timestamp(),\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
