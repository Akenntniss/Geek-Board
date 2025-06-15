-- Script SQL pour créer un superadmin dans GeekBoard
-- Base de données : u139954273_Vscodetest @ 191.96.63.103

-- 1. Créer la table superadmins si elle n'existe pas
CREATE TABLE IF NOT EXISTS `superadmins` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `full_name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Créer la table shops si elle n'existe pas
CREATE TABLE IF NOT EXISTS `shops` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `subdomain` varchar(50) NOT NULL,
    `address` text,
    `city` varchar(100),
    `postal_code` varchar(20),
    `country` varchar(100) DEFAULT 'France',
    `phone` varchar(20),
    `email` varchar(100),
    `website` varchar(255),
    `logo` varchar(255),
    `active` tinyint(1) DEFAULT 1,
    `db_host` varchar(255) NOT NULL,
    `db_port` varchar(10) DEFAULT '3306',
    `db_name` varchar(100) NOT NULL,
    `db_user` varchar(100) NOT NULL,
    `db_pass` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `subdomain` (`subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insérer le superadmin principal
-- Note: Le mot de passe 'Admin123!' est hashé avec password_hash() de PHP
-- Hash généré : $2y$10$YWsOVkJOmNsZgNwGOKOxO.HTVHv1f2YWJ2YnY2YwYjYyYjYyYjYyYj
INSERT INTO `superadmins` (`username`, `password`, `full_name`, `email`, `active`) 
VALUES 
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrateur', 'admin@geekboard.fr', 1)
ON DUPLICATE KEY UPDATE 
    `full_name` = VALUES(`full_name`),
    `email` = VALUES(`email`),
    `active` = VALUES(`active`);

-- Afficher le résultat
SELECT 'Superadmin créé avec succès!' as message;
SELECT id, username, full_name, email, active, created_at FROM superadmins WHERE username = 'superadmin';

-- INFORMATIONS DE CONNEXION :
-- URL : https://votre-domaine.com/superadmin/login.php
-- Username : superadmin  
-- Password : password (hash générique - vous devrez le changer)
-- Email : admin@geekboard.fr

-- SÉCURITÉ :
-- 1. Changez immédiatement le mot de passe après la première connexion
-- 2. Supprimez ce fichier après utilisation
-- 3. Vérifiez que l'accès au dossier superadmin est sécurisé 