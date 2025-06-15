<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/sms_test.log';
file_put_contents($logFile, "=== Début du test SMS ===\n", FILE_APPEND);

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    file_put_contents($logFile, "Config path: " . $config_path . "\n", FILE_APPEND);
    file_put_contents($logFile, "Functions path: " . $functions_path . "\n", FILE_APPEND);

    if (!file_exists($config_path) || !file_exists($functions_path)) {
        throw new Exception('Fichiers de configuration introuvables.');
    }

    require_once $config_path;
    require_once $functions_path;
    
    // Vérifier que la fonction send_sms existe
    if (!function_exists('send_sms')) {
        throw new Exception('La fonction send_sms n\'existe pas');
    }
    
    // Vérifier la connexion à la base de données
    if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Récupérer un modèle de SMS actif
    $stmt = $shop_pdo->query("SELECT id, nom, contenu, statut_id FROM sms_templates WHERE est_actif = 1 LIMIT 1");
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Aucun modèle de SMS actif trouvé');
    }
    
    // Récupérer un client avec un numéro de téléphone
    $stmt = $shop_pdo->query("
        SELECT c.id, c.nom, c.prenom, c.telephone, r.id as reparation_id 
        FROM clients c 
        JOIN reparations r ON c.id = r.client_id 
        WHERE c.telephone IS NOT NULL AND c.telephone != '' 
        LIMIT 1
    ");
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        throw new Exception('Aucun client avec numéro de téléphone trouvé');
    }
    
    // Infos pour le log
    file_put_contents($logFile, "Template: " . print_r($template, true) . "\n", FILE_APPEND);
    file_put_contents($logFile, "Client: " . print_r($client, true) . "\n", FILE_APPEND);
    
    // Retourner les infos en JSON
    echo json_encode([
        'success' => true,
        'message' => 'Test de diagnostic réussi',
        'template' => $template,
        'client' => array_merge($client, ['telephone' => substr($client['telephone'], 0, 4) . '****' . substr($client['telephone'], -2)]), // Masquer le numéro pour la confidentialité
        'php_version' => PHP_VERSION,
        'functions_available' => [
            'send_sms' => function_exists('send_sms'),
            'format_date' => function_exists('format_date')
        ]
    ]);
    
} catch (Exception $e) {
    // Gérer l'erreur
    file_put_contents($logFile, "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'php_version' => PHP_VERSION
    ]);
} 