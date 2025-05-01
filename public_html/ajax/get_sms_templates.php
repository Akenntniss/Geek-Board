<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Récupérer les chemins des fichiers includes
$config_path = realpath(__DIR__ . '/../config/database.php');
$functions_path = realpath(__DIR__ . '/../includes/functions.php');

if (!file_exists($config_path) || !file_exists($functions_path)) {
    echo json_encode([
        'success' => false,
        'error' => 'Fichiers de configuration introuvables.'
    ]);
    exit;
}

// Inclure les fichiers nécessaires
require_once $config_path;
require_once $functions_path;

try {
    // Récupérer tous les modèles de SMS actifs
    $stmt = $pdo->query("
        SELECT id, nom, contenu
        FROM sms_templates 
        WHERE est_actif = 1
        ORDER BY nom ASC
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si aucun modèle actif n'est trouvé
    if (empty($templates)) {
        echo json_encode([
            'success' => true,
            'templates' => []
        ]);
        exit;
    }
    
    // Retourner les informations des modèles
    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur dans get_sms_templates.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception dans get_sms_templates.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
} 