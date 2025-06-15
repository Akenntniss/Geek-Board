<?php
// Inclure la configuration de session avant de démarrer la session
require_once dirname(__DIR__) . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

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
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
$montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
$date_transaction = filter_input(INPUT_POST, 'date_transaction', FILTER_SANITIZE_STRING);

error_log("Données reçues - partenaire_id: " . var_export($partenaire_id, true));
error_log("Données reçues - type: " . var_export($type, true));
error_log("Données reçues - montant: " . var_export($montant, true));
error_log("Données reçues - description: " . var_export($description, true));
error_log("Données reçues - date_transaction: " . var_export($date_transaction, true));

if (!$partenaire_id || !$type || !$montant) {
    error_log("Validation échouée - partenaire_id: " . ($partenaire_id ? "OK" : "MANQUANT") . 
              ", type: " . ($type ? "OK" : "MANQUANT") . 
              ", montant: " . ($montant ? "OK" : "MANQUANT"));
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Valider le type de transaction
$types_valides = ['AVANCE', 'REMBOURSEMENT', 'SERVICE'];
if (!in_array($type, $types_valides)) {
    error_log("Type de transaction invalide: " . $type);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Type de transaction invalide']);
    exit;
}

try {
    error_log("Début de la transaction SQL");
    // Démarrer une transaction
    $shop_pdo->beginTransaction();

    // Vérifier la structure de la table
    $stmt = $shop_pdo->prepare("DESCRIBE transactions_partenaires");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Structure de la table transactions_partenaires: " . json_encode($columns));

    // Insérer la transaction
    $stmt = $shop_pdo->prepare("
        INSERT INTO transactions_partenaires 
        (partenaire_id, type, montant, description, date_transaction, reference_document) 
        VALUES (?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP), NULL)
    ");
    error_log("Exécution de l'insertion - Paramètres: " . json_encode([
        $partenaire_id, $type, $montant, $description, $date_transaction
    ]));
    $stmt->execute([$partenaire_id, $type, $montant, $description, $date_transaction]);
    error_log("ID de la transaction insérée: " . $shop_pdo->lastInsertId());

    // Mettre à jour le solde du partenaire
    $montant_final = $type === 'REMBOURSEMENT' ? -$montant : $montant;
    error_log("Calcul du montant final: " . $montant_final);
    
    // Vérifier si un solde existe déjà pour ce partenaire
    $stmt = $shop_pdo->prepare("SELECT solde_actuel FROM soldes_partenaires WHERE partenaire_id = ?");
    $stmt->execute([$partenaire_id]);
    $solde = $stmt->fetch();
    error_log("Solde existant trouvé: " . var_export($solde, true));

    if ($solde) {
        // Mettre à jour le solde existant
        $stmt = $shop_pdo->prepare("
            UPDATE soldes_partenaires 
            SET solde_actuel = solde_actuel + ?, 
                derniere_mise_a_jour = CURRENT_TIMESTAMP 
            WHERE partenaire_id = ?
        ");
        error_log("Mise à jour du solde existant - Paramètres: " . json_encode([
            $montant_final, $partenaire_id
        ]));
        $stmt->execute([$montant_final, $partenaire_id]);
        error_log("Nombre de lignes affectées par la mise à jour: " . $stmt->rowCount());
    } else {
        // Créer un nouveau solde
        $stmt = $shop_pdo->prepare("
            INSERT INTO soldes_partenaires 
            (partenaire_id, solde_actuel) 
            VALUES (?, ?)
        ");
        error_log("Création d'un nouveau solde - Paramètres: " . json_encode([
            $partenaire_id, $montant_final
        ]));
        $stmt->execute([$partenaire_id, $montant_final]);
        error_log("ID du nouveau solde créé: " . $shop_pdo->lastInsertId());
    }

    // Valider la transaction
    $shop_pdo->commit();
    error_log("Transaction SQL validée avec succès");

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Transaction enregistrée avec succès']);

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
        error_log("Transaction SQL annulée");
    }
    
    error_log("Erreur PDO détaillée: " . $e->getMessage());
    error_log("Code d'erreur PDO: " . $e->getCode());
    error_log("État SQL: " . implode(', ', $e->errorInfo));
    error_log("Trace de l'erreur: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de la transaction']);
} 