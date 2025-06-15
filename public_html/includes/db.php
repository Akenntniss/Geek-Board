<?php
require_once __DIR__ . '/../config/database.php';
/**
 * Connexion à la base de données
 */

// Configuration de la base de données
$db_host = 'srv931.hstgr.io';
$db_port = '3306';
$db_name = 'u139954273_Vscodetest';
$db_user = 'u139954273_Vscodetest';
$db_pass = 'Maman01#';
$db_charset = 'utf8mb4';

// Définir si on est en mode développement ou non
define('DEBUG_MODE', false);

$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();
} catch (PDOException $e) {
    // En mode développement, on affiche l'erreur
    if (DEBUG_MODE) {
        echo "Erreur de connexion à la base de données : " . $e->getMessage();
    } else {
        // En production, on affiche un message générique
        error_log("Erreur de connexion à la base de données : " . $e->getMessage());
        
        // Si la page est appelée en AJAX, on retourne une erreur JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
            exit;
        }
        
        // Sinon, on redirige vers une page d'erreur
        header("Location: erreur.php?code=db");
        exit;
    }
} 