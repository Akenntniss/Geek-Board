<?php
/**
 * Script AJAX pour mettre à jour le prix d'une réparation
 */

// Activer l'affichage des erreurs en mode debug
ini_set('display_errors', 0);
error_reporting(E_ALL);

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
    require_once '../config/config.php';
    
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
    
    // Connexion à la base de données
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mettre à jour le prix
    $stmt = $db->prepare('UPDATE reparations SET prix_reparation = :prix, date_modification = NOW() WHERE id = :id');
    $stmt->bindParam(':prix', $price, PDO::PARAM_INT);
    $stmt->bindParam(':id', $repair_id, PDO::PARAM_INT);
    $success = $stmt->execute();
    
    if ($success) {
        // Enregistrer l'action dans les logs s'il y a une session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $employe_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $log_message = "Prix mis à jour: {$price} €";
        
        try {
            $log_stmt = $db->prepare('INSERT INTO reparation_logs (reparation_id, employe_id, action_type, details, date_action) VALUES (:reparation_id, :employe_id, :action_type, :details, NOW())');
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