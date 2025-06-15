<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier les données requises
if (!isset($_POST['colis_id']) || !isset($_POST['nouveau_statut'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    $shop_pdo->beginTransaction();
    
    // Mettre à jour le statut du colis
    $query = "UPDATE colis SET statut = :nouveau_statut WHERE id = :colis_id";
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([
        'nouveau_statut' => $_POST['nouveau_statut'],
        'colis_id' => $_POST['colis_id']
    ]);
    
    // Ajouter l'entrée dans l'historique des statuts
    $query = "INSERT INTO colis_statuts (colis_id, statut, modifie_par, date_modification) 
              VALUES (:colis_id, :statut, :modifie_par, NOW())";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([
        'colis_id' => $_POST['colis_id'],
        'statut' => $_POST['nouveau_statut'],
        'modifie_par' => $_SESSION['user_id']
    ]);
    
    $shop_pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);
    
} catch (PDOException $e) {
    $shop_pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut']);
} 