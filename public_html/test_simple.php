<?php
echo "Test simple - fichier accessible !<br>";
echo "Date: " . date('Y-m-d H:i:s') . "<br>";
echo "Serveur: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "Script: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// Test basique de session
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session shop_id: " . ($_SESSION['shop_id'] ?? 'NON DÉFINI') . "<br>";

// Test de la configuration
$config_path = __DIR__ . '/config/database.php';
echo "Config path: " . $config_path . "<br>";
echo "Config exists: " . (file_exists($config_path) ? 'OUI' : 'NON') . "<br>";

if (file_exists($config_path)) {
    require_once $config_path;
    
    if (function_exists('getShopDBConnection')) {
        try {
            $pdo = getShopDBConnection();
            echo "Connexion DB: " . ($pdo ? 'SUCCÈS' : 'ÉCHEC') . "<br>";
            
            if ($pdo) {
                $db_stmt = $pdo->query("SELECT DATABASE() as current_db");
                $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
                echo "Base de données: " . ($db_info['current_db'] ?? 'Inconnue') . "<br>";
            }
        } catch (Exception $e) {
            echo "Erreur DB: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "Fonction getShopDBConnection non disponible<br>";
    }
}
?> 