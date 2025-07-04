Table	Create Table
kb_tags	CREATE TABLE `kb_tags` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `name` varchar(50) NOT NULL,\n  `created_at` datetime NOT NULL DEFAULT current_timestamp(),\n  PRIMARY KEY (`id`),\n  UNIQUE KEY `name` (`name`)\n) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
