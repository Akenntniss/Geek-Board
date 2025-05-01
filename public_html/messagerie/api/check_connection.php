<?php
/**
 * Script de vérification de la connexion pour le module de messagerie
 */
session_start();
header('Content-Type: application/json');

// Initialiser le tableau de résultats
$result = [
    'session' => [],
    'database' => [],
    'env' => []
];

// Vérifier les informations de session
$result['session']['active'] = session_status() === PHP_SESSION_ACTIVE;
$result['session']['user_id'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$result['session']['logged_in'] = isset($_SESSION['user_id']);
$result['session']['all_data'] = $_SESSION;

// Vérifier le chemin du fichier de configuration de la base de données
$config_file = realpath('../../config/database.php');
$result['database']['config_file_exists'] = file_exists($config_file);
$result['database']['config_file_path'] = $config_file;

try {
    // Tenter d'inclure le fichier de configuration
    require_once('../../config/database.php');
    $result['database']['config_included'] = true;
    
    // Vérifier si la variable de connexion PDO existe
    $result['database']['pdo_exists'] = isset($pdo) && $pdo instanceof PDO;
    
    if (isset($pdo) && $pdo instanceof PDO) {
        // Tester la connexion avec une requête simple
        try {
            $stmt = $pdo->query("SELECT 1");
            $result['database']['connection_test'] = $stmt->fetchColumn() == 1;
            
            // Vérifier les tables de messagerie
            $tables = [
                'conversations' => false,
                'messages' => false, 
                'conversation_participants' => false
            ];
            
            foreach (array_keys($tables) as $table) {
                try {
                    $check = $pdo->query("SELECT 1 FROM $table LIMIT 1");
                    $tables[$table] = true;
                } catch (PDOException $e) {
                    $tables[$table] = false;
                }
            }
            
            $result['database']['tables'] = $tables;
            
            // Vérifier les utilisateurs disponibles
            try {
                $users = $pdo->query("SELECT id, full_name FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                $result['database']['users_sample'] = $users;
            } catch (PDOException $e) {
                $result['database']['users_error'] = $e->getMessage();
            }
            
        } catch (PDOException $e) {
            $result['database']['connection_error'] = $e->getMessage();
        }
    }
} catch (Exception $e) {
    $result['database']['include_error'] = $e->getMessage();
}

// Informations d'environnement
$result['env']['server'] = $_SERVER['SERVER_NAME'];
$result['env']['php_version'] = phpversion();
$result['env']['request_uri'] = $_SERVER['REQUEST_URI'];
$result['env']['script_filename'] = $_SERVER['SCRIPT_FILENAME'];
$result['env']['document_root'] = $_SERVER['DOCUMENT_ROOT'];

// Sortie formatée
echo json_encode($result, JSON_PRETTY_PRINT);
?> 