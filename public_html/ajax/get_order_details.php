<?php
// Désactiver l'affichage des erreurs PHP
error_reporting(0);
ini_set('display_errors', 0);

// Activer le logging des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Définir l'en-tête JSON
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/functions.php';

// Fonction pour logger les erreurs
function logError($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    error_log($logMessage);
}

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Non autorisé');
    }

    // Vérifier si l'ID de la réparation est fourni
    if (!isset($_GET['reparation_id'])) {
        throw new Exception('ID de réparation manquant');
    }

    $reparation_id = (int)$_GET['reparation_id'];
    logError("Tentative de récupération des détails pour la réparation ID: " . $reparation_id);

    // Vérifier si la réparation existe
    $check_sql = "SELECT id FROM reparations WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$reparation_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception('Réparation non trouvée');
    }

    // Récupérer les détails de la commande associée à la réparation
    $sql = "
        SELECT 
            cp.*,
            f.nom as fournisseur_nom,
            DATE_FORMAT(cp.date_commande, '%d/%m/%Y %H:%i') as date_commande
        FROM commandes_pieces cp
        LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id
        WHERE cp.reparation_id = ?
        ORDER BY cp.date_commande DESC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reparation_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Formater les prix
        $order['prix_unitaire'] = number_format($order['prix_unitaire'], 2, ',', ' ');
        $order['prix_total'] = number_format($order['prix_total'], 2, ',', ' ');
        
        // Formater le statut
        $order['statut'] = ucfirst(str_replace('_', ' ', $order['statut']));
        
        $response = [
            'success' => true,
            'order' => $order
        ];
        
        logError("Commande trouvée", $response);
        echo json_encode($response);
    } else {
        $response = [
            'success' => false,
            'error' => 'Aucune commande trouvée pour cette réparation'
        ];
        
        logError("Aucune commande trouvée", $response);
        echo json_encode($response);
    }
} catch (PDOException $e) {
    logError("Erreur SQL", [
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des détails de la commande'
    ]);
} catch (Exception $e) {
    logError("Erreur générale", [
        'message' => $e->getMessage()
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 