<?php
// Inclure la configuration de session et la base de données
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Utilisateur non connecté'
    ]);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier les données requises
if (!isset($data['task_id']) || !isset($data['status'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Données manquantes'
    ]);
    exit;
}

$taskId = intval($data['task_id']);
$newStatus = $data['status'];

// Valider le statut
$validStatuses = ['à faire', 'en_cours', 'terminée'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode([
        'success' => false,
        'error' => 'Statut invalide'
    ]);
    exit;
}

try {
    // Mettre à jour le statut de la tâche
    $stmt = $pdo->prepare("
        UPDATE taches 
        SET statut = ?, 
            date_modification = NOW(),
            modifie_par = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$newStatus, $_SESSION['user_id'], $taskId]);

    if ($result) {
        // Ajouter une entrée dans les logs
        $stmt = $pdo->prepare("
            INSERT INTO taches_logs (
                tache_id, 
                utilisateur_id, 
                action, 
                details, 
                date_action
            ) VALUES (?, ?, 'changement_statut', ?, NOW())
        ");
        
        $details = "Changement de statut à '$newStatus'";
        $stmt->execute([$taskId, $_SESSION['user_id'], $details]);

        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }

} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour du statut de la tâche: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour du statut'
    ]);
} 