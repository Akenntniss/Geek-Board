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
$nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
$adresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING);

if (!$nom) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Le nom est requis']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insérer le nouveau partenaire
    $stmt = $pdo->prepare("
        INSERT INTO partenaires 
        (nom, email, telephone, adresse) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$nom, $email, $telephone, $adresse]);

    // Créer un solde initial à 0
    $partenaire_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        INSERT INTO soldes_partenaires 
        (partenaire_id, solde_actuel) 
        VALUES (?, 0)
    ");
    $stmt->execute([$partenaire_id]);

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Partenaire ajouté avec succès',
        'partenaire_id' => $partenaire_id
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur lors de l'ajout du partenaire : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'ajout du partenaire',
        'error' => $e->getMessage()
    ]);
} 