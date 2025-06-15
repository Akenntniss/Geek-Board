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

// Récupérer l'ID du partenaire
$partenaire_id = filter_input(INPUT_GET, 'partenaire_id', FILTER_VALIDATE_INT);

if (!$partenaire_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID du partenaire invalide']);
    exit;
}

try {
    // Récupérer toutes les transactions du partenaire
    $stmt = $shop_pdo->prepare("
        SELECT 
            t.*,
            p.nom as partenaire_nom
        FROM transactions_partenaires t
        JOIN partenaires p ON t.partenaire_id = p.id
        WHERE t.partenaire_id = ?
        ORDER BY t.date_transaction DESC
    ");
    $stmt->execute([$partenaire_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer le solde actuel
    $stmt = $shop_pdo->prepare("
        SELECT solde_actuel, derniere_mise_a_jour
        FROM soldes_partenaires
        WHERE partenaire_id = ?
    ");
    $stmt->execute([$partenaire_id]);
    $solde = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'solde' => $solde ? $solde['solde_actuel'] : 0,
        'derniere_mise_a_jour' => $solde ? $solde['derniere_mise_a_jour'] : null
    ]);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des transactions : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des transactions']);
} 