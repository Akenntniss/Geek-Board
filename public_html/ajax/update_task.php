<?php
// Désactiver l'affichage d'erreurs pour éviter de corrompre le JSON
error_reporting(0);
ini_set('display_errors', 0);

// Enregistrer les erreurs dans un fichier de log
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

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
    
    // Vérifier si les données sont valides
    if (!$data || !isset($data['task_id']) || empty($data['task_id'])) {
        send_json_response(false, 'ID de tâche manquant ou invalide');
    }

    // Récupérer et nettoyer les données
    $task_id = (int)$data['task_id'];
    $titre = isset($data['titre']) ? cleanInput($data['titre']) : '';
    $description = isset($data['description']) ? cleanInput($data['description']) : '';
    $priorite = isset($data['priorite']) ? cleanInput($data['priorite']) : '';
    $statut = isset($data['statut']) ? cleanInput($data['statut']) : '';
    $date_limite = isset($data['date_limite']) && !empty($data['date_limite']) ? cleanInput($data['date_limite']) : null;
    $employe_id = isset($data['employe_id']) && !empty($data['employe_id']) ? (int)$data['employe_id'] : null;

    // Validation des données
    $errors = [];

    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }

    if (empty($description)) {
        $errors[] = "La description est obligatoire";
    }

    if (empty($priorite)) {
        $errors[] = "La priorité est obligatoire";
    }

    if (empty($statut)) {
        $errors[] = "Le statut est obligatoire";
    }

    // Si des erreurs sont détectées
    if (!empty($errors)) {
        send_json_response(false, implode(', ', $errors));
    }

    // Vérifier que la connexion à la base de données est établie
    if (!isset($shop_pdo) || !$shop_pdo) {
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
        $db_pdo = $shop_pdo;
        error_log("Utilisation de la connexion PDO existante");
    }
    
    // Vérifier d'abord si la tâche existe
    $stmt = $db_pdo->prepare("SELECT id FROM taches WHERE id = ?");
    $stmt->execute([$task_id]);
    if (!$stmt->fetch()) {
        send_json_response(false, 'Tâche non trouvée');
    }
    
    // Mettre à jour la tâche
    $query = "UPDATE taches SET titre = ?, description = ?, priorite = ?, statut = ?";
    $params = [$titre, $description, $priorite, $statut];
    
    // Ajouter les paramètres optionnels
    if ($date_limite !== null) {
        $query .= ", date_limite = ?";
        $params[] = $date_limite;
    } else {
        $query .= ", date_limite = NULL";
    }
    
    if ($employe_id !== null) {
        $query .= ", employe_id = ?";
        $params[] = $employe_id;
    } else {
        $query .= ", employe_id = NULL";
    }
    
    $query .= " WHERE id = ?";
    $params[] = $task_id;
    
    $stmt = $db_pdo->prepare($query);
    $result = $stmt->execute($params);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'exécution de la requête SQL");
    }
    
    // Vérifier si des modifications ont été effectuées
    if ($stmt->rowCount() > 0) {
        send_json_response(true, 'Tâche mise à jour avec succès!');
    } else {
        send_json_response(true, 'Aucune modification n\'a été apportée à la tâche.');
    }
    
} catch (Exception $e) {
    // Log l'erreur pour le débogage
    error_log("Erreur dans update_task.php: " . $e->getMessage());
    send_json_response(false, 'Erreur lors de la mise à jour de la tâche: ' . $e->getMessage());
} 