<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Récupérer les chemins des fichiers includes
$config_path = realpath(__DIR__ . '/../config/database.php');
$functions_path = realpath(__DIR__ . '/../includes/functions.php');

if (!file_exists($config_path) || !file_exists($functions_path)) {
    echo json_encode([
        'success' => false,
        'message' => 'Fichiers de configuration introuvables.'
    ]);
    exit;
}

// Inclure les fichiers nécessaires
require_once $config_path;
require_once $functions_path;

// Journal de logs pour le débogage
$log_dir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/sms_template_' . date('Y-m-d') . '.log';

function log_message($message) {
    global $log_file;
    $log_entry = date('[Y-m-d H:i:s] ') . $message . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Vérifier que la requête est en GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
    exit;
}

log_message("=== TRAITEMENT REQUÊTE RÉCUPÉRATION MODÈLE SMS ===");
log_message("Données reçues: " . json_encode($_GET));

// Récupérer l'ID du statut
$status_id = isset($_GET['status_id']) ? (int)$_GET['status_id'] : 0;

// Vérifier que l'ID du statut est valide
if ($status_id <= 0) {
    log_message("Erreur: ID de statut invalide");
    echo json_encode([
        'success' => false,
        'message' => 'ID de statut invalide.'
    ]);
    exit;
}

try {
    // Récupérer le modèle de SMS associé au statut
    $stmt = $pdo->prepare("
        SELECT id, nom, contenu 
        FROM sms_templates 
        WHERE statut_id = ? AND est_actif = 1
        LIMIT 1
    ");
    $stmt->execute([$status_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($template) {
        log_message("Modèle de SMS trouvé: " . $template['nom']);
        echo json_encode([
            'success' => true,
            'template' => $template
        ]);
    } else {
        log_message("Aucun modèle de SMS trouvé pour le statut ID: " . $status_id);
        echo json_encode([
            'success' => true,
            'template' => null,
            'message' => 'Aucun modèle de SMS associé à ce statut.'
        ]);
    }
} catch (PDOException $e) {
    log_message("Erreur lors de la récupération du modèle de SMS: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération du modèle de SMS: ' . $e->getMessage()
    ]);
} 