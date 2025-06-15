<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Récupérer les données
$reparation_id = $_POST['reparation_id'] ?? '';
$new_status = $_POST['new_status'] ?? '';

// Validation des données
if (empty($reparation_id) || empty($new_status)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de réparation et nouveau statut requis'
    ]);
    exit;
}

// Validation de l'ID (doit être numérique)
if (!is_numeric($reparation_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de réparation invalide'
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
    
    // Utiliser la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }
    
    // Vérifier que la réparation existe
    $checkSQL = "SELECT id, statut FROM reparations WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSQL);
    $checkStmt->execute([$reparation_id]);
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
    $result = $updateStmt->execute([$new_status, $reparation_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => [
                'reparation_id' => $reparation_id,
                'old_status' => $reparation['statut'],
                'new_status' => $new_status,
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
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    error_log("Erreur générale dans update_repair_status.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
?> 