-- Script de création des tables pour le système de gestion de thèmes GeekBoard

-- Table principale des thèmes disponibles
CREATE TABLE IF NOT EXISTS theme_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    css_file VARCHAR(255),
    js_file VARCHAR(255),
    preview_image VARCHAR(255),
    is_active BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    supports_dark_mode BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des préférences utilisateur
CREATE TABLE IF NOT EXISTS user_theme_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    theme_id INT,
    dark_mode_enabled BOOLEAN DEFAULT FALSE,
    auto_switch_enabled BOOLEAN DEFAULT FALSE,
    custom_settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (theme_id) REFERENCES theme_management(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_theme (user_id)
);

-- Table des paramètres globaux
CREATE TABLE IF NOT EXISTS global_theme_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('boolean', 'string', 'number', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des thèmes existants
INSERT IGNORE INTO theme_management (name, display_name, description, css_file, js_file, supports_dark_mode, is_default) VALUES
('ios26-liquid-glass', 'iOS 26 Liquid Glass', 'Thème futuriste inspiré d\'iOS 26 avec effets Liquid Glass', 'assets/css/ios26-liquid-glass.css', 'assets/js/ios26-theme-manager.js', TRUE, FALSE),
('modern-theme', 'Thème Moderne', 'Design contemporain avec support jour/nuit', 'assets/css/modern-theme.css', NULL, TRUE, TRUE),
('dark-theme', 'Thème Sombre', 'Interface sombre pour un confort visuel optimal', 'assets/css/dark-theme.css', NULL, TRUE, FALSE),
('classic', 'Thème Classique', 'Design original de GeekBoard', NULL, NULL, TRUE, FALSE);

-- Paramètres globaux par défaut
INSERT IGNORE INTO global_theme_settings (setting_key, setting_value, setting_type, description) VALUES
('enable_theme_switching', 'true', 'boolean', 'Permettre aux utilisateurs de changer de thème'),
('default_theme', 'modern-theme', 'string', 'Thème par défaut pour les nouveaux utilisateurs'),
('enable_auto_dark_mode', 'true', 'boolean', 'Activer le passage automatique en mode sombre'),
('theme_cache_duration', '3600', 'number', 'Durée de cache des thèmes en secondes'); 