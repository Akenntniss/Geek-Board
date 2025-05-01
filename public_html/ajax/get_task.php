<?php
// Désactiver l'affichage d'erreurs pour éviter de corrompre le JSON
error_reporting(0);
ini_set('display_errors', 0);

// Enregistrer les erreurs dans un fichier de log
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Log des informations de débogage
error_log("=== Début de l'exécution de get_task.php ===");
error_log("Paramètres GET reçus: " . print_r($_GET, true));
error_log("Version PHP: " . phpversion());

// Inclure les fichiers requis
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Fonction pour envoyer une réponse JSON et terminer le script
function send_json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['task'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    error_log("Erreur: Utilisateur non connecté");
    send_json_response(false, 'Vous devez être connecté pour effectuer cette action.');
}

// Vérifier si l'ID de la tâche est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    error_log("Erreur: ID de tâche non spécifié");
    send_json_response(false, 'ID de tâche non spécifié');
}

$tache_id = (int)$_GET['id'];
error_log("ID de tâche: " . $tache_id);

try {
    // Vérifier que la connexion à la base de données est établie
    if (!isset($pdo) || !$pdo) {
        error_log("Connexion PDO non disponible globalement, création d'une nouvelle connexion");
        
        // Recréer une connexion directement
        $db_pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // Log la nouvelle connexion
        error_log("Nouvelle connexion PDO créée avec succès");
    } else {
        $db_pdo = $pdo;
        error_log("Utilisation de la connexion PDO existante");
    }
    
    // Utiliser la connexion PDO locale
    error_log("Type de la connexion PDO utilisée: " . get_class($db_pdo));
    
    // Récupérer les informations de la tâche avec une requête simplifiée
    $stmt = $db_pdo->prepare("SELECT * FROM taches WHERE id = ?");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log des résultats
    error_log("Résultat de la requête: " . ($tache ? "Tâche trouvée" : "Tâche non trouvée"));
    
    // Vérifier si la tâche existe
    if (!$tache) {
        error_log("Erreur: Tâche non trouvée avec ID " . $tache_id);
        send_json_response(false, 'Tâche non trouvée');
    }
    
    // Log des données de la tâche
    error_log("Données de la tâche: " . print_r($tache, true));
    
    // Récupérer les informations de l'employé si nécessaire
    if (!empty($tache['employe_id'])) {
        $stmt = $db_pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$tache['employe_id']]);
        $employe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employe) {
            $tache['employe_nom'] = $employe['full_name'];
        }
    }
    
    // Récupérer le créateur si nécessaire
    if (!empty($tache['created_by'])) {
        $stmt = $db_pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$tache['created_by']]);
        $createur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($createur) {
            $tache['createur_nom'] = $createur['full_name'];
        }
    }
    
    // Formater les données
    if (isset($tache['date_limite']) && $tache['date_limite']) {
        // Formater la date au format YYYY-MM-DD pour l'input date
        $tache['date_limite'] = date('Y-m-d', strtotime($tache['date_limite']));
    }
    
    // Log final avant l'envoi
    error_log("Envoi de la réponse: success=true");
    
    // Retourner les données au format JSON
    send_json_response(true, 'Tâche récupérée avec succès', $tache);
    
} catch (Exception $e) {
    // Log l'erreur pour le débogage
    error_log("Erreur dans get_task.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    send_json_response(false, 'Erreur lors de la récupération de la tâche: ' . $e->getMessage());
}

error_log("=== Fin de l'exécution de get_task.php (cette ligne ne devrait jamais être atteinte) ==="); 