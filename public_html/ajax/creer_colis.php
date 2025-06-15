<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier les données requises
$required_fields = ['numero_suivi', 'transporteur'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Le champ '$field' est requis"]);
        exit;
    }
}

try {
    $shop_pdo->beginTransaction();
    
    // Créer le nouveau colis
    $query = "INSERT INTO colis (numero_suivi, transporteur, statut, cree_par, date_creation) 
              VALUES (:numero_suivi, :transporteur, 'EN_ATTENTE', :cree_par, NOW())";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([
        'numero_suivi' => $_POST['numero_suivi'],
        'transporteur' => $_POST['transporteur'],
        'cree_par' => $_SESSION['user_id']
    ]);
    
    $colis_id = $shop_pdo->lastInsertId();
    
    // Ajouter le premier statut dans l'historique
    $query = "INSERT INTO colis_statuts (colis_id, statut, modifie_par, date_modification) 
              VALUES (:colis_id, 'EN_ATTENTE', :modifie_par, NOW())";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([
        'colis_id' => $colis_id,
        'modifie_par' => $_SESSION['user_id']
    ]);
    
    $shop_pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Colis créé avec succès',
        'colis_id' => $colis_id
    ]);
    
} catch (PDOException $e) {
    $shop_pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du colis']);
} 