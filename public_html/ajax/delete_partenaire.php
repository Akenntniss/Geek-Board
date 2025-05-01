<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Récupérer l'ID du partenaire
$partenaire_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$partenaire_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID du partenaire invalide']);
    exit;
}

try {
    // Vérifier s'il y a des transactions liées
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM transactions_partenaires 
        WHERE partenaire_id = ?
    ");
    $stmt->execute([$partenaire_id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        // Si des transactions existent, désactiver le partenaire au lieu de le supprimer
        $stmt = $pdo->prepare("
            UPDATE partenaires 
            SET actif = FALSE 
            WHERE id = ?
        ");
        $stmt->execute([$partenaire_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Partenaire désactivé']);
    } else {
        // Si aucune transaction, supprimer le partenaire et son solde
        $pdo->beginTransaction();
        
        // Supprimer le solde
        $stmt = $pdo->prepare("DELETE FROM soldes_partenaires WHERE partenaire_id = ?");
        $stmt->execute([$partenaire_id]);
        
        // Supprimer le partenaire
        $stmt = $pdo->prepare("DELETE FROM partenaires WHERE id = ?");
        $stmt->execute([$partenaire_id]);
        
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Partenaire supprimé']);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur lors de la suppression du partenaire : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du partenaire']);
} 