<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactiver l'affichage des erreurs pour éviter la corruption JSON

// Lire les données depuis JSON ou POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Si les données JSON ne sont pas disponibles, utiliser $_POST
if (!$data) {
    $data = $_POST;
}

// Récupérer les données
$repair_id = $data['repair_id'] ?? '';
$status_id = $data['status_id'] ?? '';
$send_sms = $data['send_sms'] ?? false;
$shop_id = $data['shop_id'] ?? $_GET['shop_id'] ?? '';

// Validation des données
if (empty($repair_id) || empty($status_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de réparation et nouveau statut requis'
    ]);
    exit;
}

// Validation de l'ID du magasin
if (empty($shop_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du magasin requis'
    ]);
    exit;
}

// Validation que les IDs sont numériques
if (!is_numeric($repair_id) || !is_numeric($status_id) || !is_numeric($shop_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'IDs invalides'
    ]);
    exit;
}

try {
    // Utiliser le système de configuration multi-magasin
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable.');
    }

    // Inclure la configuration de base de données
    require_once $config_path;
    
    // Utiliser la connexion à la base de données du magasin spécifique par son ID
    $pdo = getShopDBConnectionById($shop_id);
    
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }
    
    // Vérifier que la réparation existe
    $checkSQL = "SELECT id, statut FROM reparations WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSQL);
    $checkStmt->execute([$repair_id]);
    $reparation = $checkStmt->fetch();

    if (!$reparation) {
        echo json_encode([
            'success' => false,
            'message' => 'Réparation non trouvée'
        ]);
        exit;
    }

    // Mettre à jour le statut
    $updateSQL = "UPDATE reparations SET statut = ?, date_modification = NOW() WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSQL);
    $result = $updateStmt->execute([$status_id, $repair_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => [
                'repair_id' => $repair_id,
                'old_status' => $reparation['statut'],
                'new_status' => $status_id,
                'shop_id' => $shop_id,
                'send_sms' => $send_sms,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du statut'
        ]);
    }

} catch (PDOException $e) {
    error_log("Erreur PDO dans update_repair_status.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    error_log("Erreur générale dans update_repair_status.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
?> 