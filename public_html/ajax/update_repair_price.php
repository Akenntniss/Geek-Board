<?php
/**
 * Script AJAX pour mettre à jour le prix d'une réparation
 */

// Activer l'affichage des erreurs en mode debug
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Démarrer la session pour récupérer l'ID du magasin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'ID du magasin depuis les paramètres POST ou GET
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
    error_log("ID du magasin récupéré depuis la requête: $shop_id_from_request");
}

// Définir le type de contenu comme JSON dès le début
header('Content-Type: application/json');

// Fonction pour envoyer une réponse JSON et terminer le script
function send_json_response($success, $message, $data = []) {
    $response = array_merge(['success' => $success, 'message' => $message], $data);
    echo json_encode($response);
    exit;
}

try {
    // Inclure les fichiers nécessaires
    require_once '../config/database.php';
    
    // Vérifier la méthode de requête
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(false, 'Méthode non autorisée');
    }
    
    // Récupérer et valider les données
    $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
    $price = isset($_POST['price']) ? intval($_POST['price']) : 0;
    
    if ($repair_id <= 0) {
        send_json_response(false, 'ID de réparation invalide');
    }
    
    if ($price < 0) {
        send_json_response(false, 'Prix invalide');
    }
    
    // Utiliser la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        send_json_response(false, 'Erreur de connexion à la base de données du magasin');
    }
    
    // Vérifier quelle base de données nous utilisons réellement
    try {
        $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Base de données connectée dans update_repair_price.php: " . ($db_info['current_db'] ?? 'Inconnue'));
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la base: " . $e->getMessage());
    }
    
    // Mettre à jour le prix
    $stmt = $shop_pdo->prepare('UPDATE reparations SET prix_reparation = :prix, date_modification = NOW() WHERE id = :id');
    $stmt->bindParam(':prix', $price, PDO::PARAM_INT);
    $stmt->bindParam(':id', $repair_id, PDO::PARAM_INT);
    $success = $stmt->execute();
    
    if ($success) {
        // Vérifier si la mise à jour a réellement affecté une ligne
        $affected_rows = $stmt->rowCount();
        if ($affected_rows === 0) {
            error_log("Avertissement: Mise à jour du prix pour réparation ID $repair_id a réussi, mais aucune ligne n'a été affectée");
        } else {
            error_log("Succès: Prix de la réparation ID $repair_id mis à jour à $price € ($affected_rows lignes affectées)");
        }
        
        // Enregistrer l'action dans les logs
        $employe_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $log_message = "Prix mis à jour: {$price} €";
        
        try {
            $log_stmt = $shop_pdo->prepare('INSERT INTO reparation_logs (reparation_id, employe_id, action_type, details, date_action) VALUES (:reparation_id, :employe_id, :action_type, :details, NOW())');
            $log_stmt->bindParam(':reparation_id', $repair_id, PDO::PARAM_INT);
            $log_stmt->bindParam(':employe_id', $employe_id, PDO::PARAM_INT);
            $log_stmt->bindParam(':action_type', $action_type, PDO::PARAM_STR);
            $log_stmt->bindParam(':details', $log_message, PDO::PARAM_STR);
            
            $action_type = 'mise_a_jour_prix';
            $log_stmt->execute();
        } catch (Exception $e) {
            // Si erreur lors de l'enregistrement du log, on continue quand même
            error_log('Erreur lors de l\'enregistrement du log: ' . $e->getMessage());
        }
        
        send_json_response(true, 'Prix mis à jour avec succès');
    } else {
        send_json_response(false, 'Erreur lors de la mise à jour du prix');
    }
} catch (Exception $e) {
    // Capturer toutes les exceptions et erreurs
    error_log('Erreur dans update_repair_price.php: ' . $e->getMessage());
    send_json_response(false, 'Erreur: ' . $e->getMessage());
} 