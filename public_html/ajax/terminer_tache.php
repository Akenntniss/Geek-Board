<?php
// Désactiver l'affichage d'erreurs pour éviter de corrompre le JSON
error_reporting(0);
ini_set('display_errors', 0);

// Enregistrer les erreurs dans un fichier de log
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Inclure les fichiers requis
require_once '../config/database.php';
require_once '../includes/functions.php';

// Fonction pour envoyer une réponse JSON et terminer le script
function send_json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    send_json_response(false, 'Vous devez être connecté pour effectuer cette action.');
}

try {
    // Récupérer les données JSON envoyées
    $json_data = file_get_contents('php://input');
    if (empty($json_data)) {
        throw new Exception("Aucune donnée reçue");
    }
    
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erreur de décodage JSON: " . json_last_error_msg());
    }
    
    // Vérifier si l'ID de tâche est valide
    if (!isset($data['tache_id']) || empty($data['tache_id'])) {
        send_json_response(false, 'ID de tâche manquant ou invalide');
    }
    
    $tache_id = (int)$data['tache_id'];
    $commentaire = isset($data['commentaire']) ? cleanInput($data['commentaire']) : '';
    
    // Vérifier si la connexion à la base de données est établie
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
    } else {
        $db_pdo = $pdo;
    }
    
    // Vérifier d'abord si la tâche existe
    $stmt = $db_pdo->prepare("SELECT id, statut FROM taches WHERE id = ?");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch();
    
    if (!$tache) {
        send_json_response(false, 'Tâche non trouvée');
    }
    
    // Vérifier si la tâche n'est pas déjà terminée
    if ($tache['statut'] === 'termine') {
        send_json_response(false, 'Cette tâche est déjà terminée');
    }
    
    // Mettre à jour la tâche pour la marquer comme terminée
    $stmt = $db_pdo->prepare("
        UPDATE taches 
        SET statut = 'termine', 
            date_fin = NOW() 
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$tache_id]);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'exécution de la requête SQL pour terminer la tâche");
    }
    
    // Si un commentaire est fourni, l'ajouter
    if (!empty($commentaire)) {
        $stmt = $db_pdo->prepare("
            INSERT INTO commentaires_tache (tache_id, user_id, commentaire) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$tache_id, $_SESSION['user_id'], $commentaire]);
    }
    
    // Envoyer une réponse positive
    send_json_response(true, 'Tâche terminée avec succès');
    
} catch (Exception $e) {
    error_log("Erreur dans terminer_tache.php: " . $e->getMessage());
    send_json_response(false, 'Une erreur est survenue: ' . $e->getMessage());
} catch (PDOException $e) {
    error_log("Erreur PDO dans terminer_tache.php: " . $e->getMessage());
    send_json_response(false, 'Erreur de base de données: ' . $e->getMessage());
} 