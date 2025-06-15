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

// Vérifier si un code de statut est fourni
if (!isset($_GET['statut']) || empty($_GET['statut'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Code statut non spécifié'
    ]);
    exit;
}

// Récupérer le code du statut
$statut_code = $_GET['statut'];

try {
    // 1. Récupérer l'ID du statut
    $stmt = $shop_pdo->prepare("
        SELECT id 
        FROM statuts 
        WHERE code = ?
    ");
    $stmt->execute([$statut_code]);
    $statut = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$statut) {
        echo json_encode([
            'success' => true,
            'has_template' => false,
            'error' => 'Statut inconnu'
        ]);
        exit;
    }
    
    // 2. Vérifier s'il existe un modèle de SMS pour ce statut
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, contenu
        FROM sms_templates 
        WHERE statut_id = ? AND est_actif = 1
    ");
    $stmt->execute([$statut['id']]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si aucun modèle actif n'est trouvé
    if (!$template) {
        echo json_encode([
            'success' => true,
            'has_template' => false
        ]);
        exit;
    }
    
    // 3. Préparer un aperçu du SMS avec des informations d'exemple
    $preview = $template['contenu'];
    
    // Récupérer les variables disponibles
    $stmt = $shop_pdo->query("SELECT nom, exemple FROM sms_template_variables");
    $variables = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Effectuer les remplacements avec les exemples
    foreach ($variables as $var => $exemple) {
        $preview = str_replace("[$var]", $exemple, $preview);
    }
    
    // Retourner les informations du modèle
    echo json_encode([
        'success' => true,
        'has_template' => true,
        'template' => [
            'id' => $template['id'],
            'nom' => $template['nom'],
            'contenu' => $template['contenu'],
            'preview' => nl2br(htmlspecialchars($preview))
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur dans check_sms_template.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception dans check_sms_template.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
} 