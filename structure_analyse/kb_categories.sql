Table	Create Table
kb_categories	CREATE TABLE `kb_categories` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `name` varchar(100) NOT NULL,\n  `icon` varchar(50) DEFAULT 'fas fa-folder',\n  `created_at` datetime NOT NULL DEFAULT current_timestamp(),\n  PRIMARY KEY (`id`),\n  UNIQUE KEY `name` (`name`)\n) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
