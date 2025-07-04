<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'geekboard_main');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $shop_pdo = getShopDBConnection();
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
} 