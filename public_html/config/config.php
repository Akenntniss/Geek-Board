<?php
// Paramètres de base de données
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'u139954273_Vscodetest');
define('DB_PASS', 'Maman01#');
define('DB_NAME', 'u139954273_Vscodetest');

// Paramètres de l'application
define('APP_NAME', 'GeekBoard - Gestion des Réparations');
define('APP_URL', 'https://mdgeek.top');
define('DEBUG_MODE', true);

// Configuration des chemins
define('ROOT_DIR', dirname(dirname(__FILE__)));
define('UPLOAD_DIR', ROOT_DIR . '/uploads');
define('IMAGES_DIR', UPLOAD_DIR . '/images');
define('TEMP_DIR', UPLOAD_DIR . '/temp');

// Fonction pour afficher les messages d'erreur ou les masquer selon le mode debug
function debug_log($message, $type = 'info') {
    if (DEBUG_MODE) {
        error_log("[{$type}] " . $message);
    }
} 