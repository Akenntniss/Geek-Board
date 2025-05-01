<?php
// Inclure la configuration de session avant de démarrer la session
require_once dirname(__DIR__) . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Ajouter un logging de la session pour débogage
error_log("Session data: " . json_encode($_SESSION));
error_log("Session ID: " . session_id());
error_log("Cookie info: " . json_encode($_COOKIE));

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$partenaire_id = filter_input(INPUT_POST, 'partenaire_id', FILTER_VALIDATE_INT);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
$montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);

if (!$partenaire_id || !$description || !$montant) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Démarrer une transaction
    $pdo->beginTransaction();

    // Insérer le service dans la table services_partenaires
    $stmt = $pdo->prepare("
        INSERT INTO services_partenaires 
        (partenaire_id, description, montant) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$partenaire_id, $description, $montant]);

    // Créer une transaction correspondante
    $stmt = $pdo->prepare("
        INSERT INTO transactions_partenaires 
        (partenaire_id, type, montant, description) 
        VALUES (?, 'SERVICE', ?, ?)
    ");
    $stmt->execute([$partenaire_id, $montant, "Service: " . $description]);

    // Mettre à jour le solde du partenaire
    $stmt = $pdo->prepare("SELECT solde_actuel FROM soldes_partenaires WHERE partenaire_id = ?");
    $stmt->execute([$partenaire_id]);
    $solde = $stmt->fetch();

    if ($solde) {
        // Mettre à jour le solde existant
        $stmt = $pdo->prepare("
            UPDATE soldes_partenaires 
            SET solde_actuel = solde_actuel + ?, 
                derniere_mise_a_jour = CURRENT_TIMESTAMP 
            WHERE partenaire_id = ?
        ");
        $stmt->execute([$montant, $partenaire_id]);
    } else {
        // Créer un nouveau solde
        $stmt = $pdo->prepare("
            INSERT INTO soldes_partenaires 
            (partenaire_id, solde_actuel) 
            VALUES (?, ?)
        ");
        $stmt->execute([$partenaire_id, $montant]);
    }

    // Valider la transaction
    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Service enregistré avec succès']);

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    
    error_log("Erreur lors de l'ajout du service : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du service']);
} 