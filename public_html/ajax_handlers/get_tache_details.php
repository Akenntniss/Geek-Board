<?php
// Définir le chemin d'accès au cookie de session
$root_path = dirname(dirname($_SERVER['SCRIPT_NAME']));
if ($root_path == '/' || $root_path == '\\') {
    $root_path = '';
}
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 30, // 30 jours
    'path' => $root_path,
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Initialisation de la session (si ce n'est pas déjà fait)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Création du dossier logs s'il n'existe pas
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}

// Log de l'état de la session
$log_message = "GET_TACHE_DETAILS - Vérification session - ";
$log_message .= "session_id: " . session_id() . ", ";
$log_message .= "user_id présent: " . (isset($_SESSION['user_id']) ? "OUI (".$_SESSION['user_id'].")" : "NON");
file_put_contents('../logs/sms_debug.log', $log_message . "\n", FILE_APPEND);

// Pour les besoins de développement, désactivons temporairement la vérification d'authentification
/*
// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}
*/

// Vérification si l'ID de la tâche est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de tâche manquant']);
    exit;
}

// Inclure les fichiers nécessaires
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Création d'un fichier de log dans un dossier accessible
$log_file = '../logs/sms_debug.log';
error_log("GET_TACHE_DETAILS - Début de la requête pour la tâche ID: " . $_GET['id']);

// Nettoyage de l'ID
$tache_id = (int)$_GET['id'];
error_log("GET_TACHE_DETAILS - ID tâche après nettoyage: " . $tache_id);

try {
    // Récupération des détails de la tâche
    $query = "
        SELECT t.*, 
               u.full_name as employe_nom,
               c.full_name as createur_nom
        FROM taches t 
        LEFT JOIN users u ON t.employe_id = u.id 
        LEFT JOIN users c ON t.created_by = c.id 
        WHERE t.id = ?
    ";
    error_log("GET_TACHE_DETAILS - Requête SQL: " . $query);
    
    // Ajoutons une requête pour vérifier la valeur de description directement
    $check_query = "SELECT id, description FROM taches WHERE id = ?";
    $check_stmt = $shop_pdo->prepare($check_query);
    $check_stmt->execute([$tache_id]);
    $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check_result) {
        error_log("GET_TACHE_DETAILS - Vérification directe - ID: " . $check_result['id'] . ", Description: " . ($check_result['description'] === null ? "NULL" : "'" . $check_result['description'] . "'"));
    } else {
        error_log("GET_TACHE_DETAILS - Vérification directe - Tâche non trouvée");
    }
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tache) {
        error_log("GET_TACHE_DETAILS - Tâche non trouvée");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Tâche non trouvée']);
        exit;
    }
    
    // Loguer les détails de la tâche
    error_log("GET_TACHE_DETAILS - Tâche trouvée: " . print_r($tache, true));
    
    // Vérifier spécifiquement si la description existe
    if (isset($tache['description'])) {
        error_log("GET_TACHE_DETAILS - Description présente: " . substr($tache['description'], 0, 100) . "...");
    } else {
        error_log("GET_TACHE_DETAILS - Description ABSENTE!");
        // Vérifier les clés disponibles dans l'array
        error_log("GET_TACHE_DETAILS - Clés disponibles: " . implode(', ', array_keys($tache)));
    }
    
    // Récupération des commentaires
    $stmt = $shop_pdo->prepare("
        SELECT c.*, u.full_name as user_nom
        FROM commentaires_tache c
        JOIN users u ON c.user_id = u.id
        WHERE c.tache_id = ?
        ORDER BY c.date_creation DESC
    ");
    $stmt->execute([$tache_id]);
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer la réponse JSON
    $response = [
        'success' => true,
        'tache' => $tache,
        'commentaires' => $commentaires,
        'statut_actif' => $tache['statut']
    ];
    
    // Loguer la réponse
    error_log("GET_TACHE_DETAILS - Réponse préparée");
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode($response);
    
    error_log("GET_TACHE_DETAILS - Réponse envoyée");
    
} catch (PDOException $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    error_log("GET_TACHE_DETAILS - ERREUR PDO: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
    ]);
    
    // Journaliser l'erreur
    error_log('Erreur dans get_tache_details.php: ' . $e->getMessage());
}
?> 