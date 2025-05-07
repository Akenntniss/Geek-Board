<?php
/**
 * Script AJAX pour mettre à jour les notes techniques d'une réparation
 */

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
    require_once '../config/database.php';
    
    // Vérifier la méthode de requête
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(false, 'Méthode non autorisée');
    }
    
    // Récupérer et valider les données
    $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    // Récupérer l'ID du magasin, soit de POST, soit de GET
    $shop_id = isset($_POST['shop_id']) ? intval($_POST['shop_id']) : null;
    if ($shop_id === null) {
        $shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : null;
    }

    error_log("Update repair notes - POST data: " . print_r($_POST, true));
    error_log("Update repair notes - GET data: " . print_r($_GET, true));
    error_log("Update repair notes - shop_id: " . ($shop_id ?? 'null'));
    
    if ($repair_id <= 0) {
        send_json_response(false, 'ID de réparation invalide');
    }
    
    // Démarrer la session si ce n'est pas déjà fait
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Si shop_id n'est pas défini dans la requête, essayer de le récupérer de la session
    if ($shop_id === null) {
        $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 1;
    }
    
    // Journaliser les informations de débogage
    error_log("Update repair notes: repair_id=$repair_id, shop_id=$shop_id");
    
    // Utiliser la connexion à la base de données du magasin
    $db = getShopDBConnection();
    
    if (!$db) {
        error_log("Impossible d'obtenir la connexion à la base de données du magasin dans update_repair_notes.php");
        send_json_response(false, "Erreur de connexion à la base de données");
    }
    
    // Mettre à jour les notes techniques
    $stmt = $db->prepare('UPDATE reparations SET notes_techniques = :notes, date_modification = NOW() WHERE id = :id');
    $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $stmt->bindParam(':id', $repair_id, PDO::PARAM_INT);
    $success = $stmt->execute();
    
    // Vérifier si la mise à jour a bien modifié une ligne
    if ($success) {
        $rowCount = $stmt->rowCount();
        error_log("Mise à jour des notes techniques - Lignes affectées: $rowCount");
        
        if ($rowCount === 0) {
            // La requête s'est exécutée mais aucune ligne n'a été modifiée
            // Vérifions si la réparation existe
            $check_stmt = $db->prepare('SELECT id FROM reparations WHERE id = :id');
            $check_stmt->bindParam(':id', $repair_id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->fetch()) {
                error_log("La réparation existe mais aucune ligne n'a été modifiée. Peut-être que les notes n'ont pas changé?");
                // C'est normal si les notes n'ont pas changé, on considère que c'est un succès
                $success = true;
            } else {
                error_log("La réparation avec ID $repair_id n'existe pas dans cette base de données");
                send_json_response(false, "Réparation non trouvée");
                exit;
            }
        }
    }
    
    if ($success) {
        // Enregistrer l'action dans les logs
        $employe_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $log_message = "Notes techniques mises à jour";
        
        try {
            $log_stmt = $db->prepare('INSERT INTO logs_reparations (reparation_id, employe_id, action, date_action) VALUES (:reparation_id, :employe_id, :action, NOW())');
            $log_stmt->bindParam(':reparation_id', $repair_id, PDO::PARAM_INT);
            $log_stmt->bindParam(':employe_id', $employe_id, PDO::PARAM_INT);
            $log_stmt->bindParam(':action', $log_message, PDO::PARAM_STR);
            $log_stmt->execute();
        } catch (Exception $e) {
            // Si erreur lors de l'enregistrement du log, on continue quand même
            error_log('Erreur lors de l\'enregistrement du log: ' . $e->getMessage());
        }
        
        send_json_response(true, 'Notes techniques mises à jour avec succès');
    } else {
        send_json_response(false, 'Erreur lors de la mise à jour des notes techniques');
    }
} catch (Exception $e) {
    // Capturer toutes les exceptions et erreurs
    error_log('Erreur dans update_repair_notes.php: ' . $e->getMessage());
    send_json_response(false, 'Erreur: ' . $e->getMessage());
} 