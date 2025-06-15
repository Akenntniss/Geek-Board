<?php
// Forcer le type de contenu en JSON
header('Content-Type: application/json');

// Activer l'affichage des erreurs en mode développement
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
    exit;
}

// Inclure la configuration de la base de données
require_once '../config/db.php';

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer le corps de la requête JSON
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
    }

    // Valider les données requises
    if (!isset($data['client_id']) || !isset($data['pieces']) || !is_array($data['pieces'])) {
        throw new Exception('Données invalides ou manquantes');
    }

    // Commencer une transaction
    $shop_pdo->beginTransaction();

    // Préparer la requête d'insertion
    $stmt = $shop_pdo->prepare("
        INSERT INTO commandes_pieces (
            client_id, reparation_id, fournisseur_id, 
            nom_piece, code_barre, quantite, 
            prix_estime, statut, date_creation,
            user_id
        ) VALUES (
            :client_id, :reparation_id, :fournisseur_id,
            :nom_piece, :code_barre, :quantite,
            :prix_estime, :statut, NOW(),
            :user_id
        )
    ");

    // Compteur pour les commandes insérées
    $commandesInserees = 0;

    // Insérer chaque pièce
    foreach ($data['pieces'] as $piece) {
        $params = [
            ':client_id' => $data['client_id'],
            ':reparation_id' => $data['reparation_id'] ?? null,
            ':fournisseur_id' => $piece['fournisseur_id'],
            ':nom_piece' => $piece['nom_piece'],
            ':code_barre' => $piece['code_barre'] ?? null,
            ':quantite' => $piece['quantite'],
            ':prix_estime' => $piece['prix_estime'] ?? null,
            ':statut' => $piece['statut'] ?? 'en_attente',
            ':user_id' => $_SESSION['user_id']
        ];

        $stmt->execute($params);
        $commandesInserees++;
    }

    // Valider la transaction
    $shop_pdo->commit();

    // Envoyer la réponse
    echo json_encode([
        'success' => true,
        'message' => $commandesInserees . ' commande(s) créée(s) avec succès'
    ]);

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }

    // Logger l'erreur
    error_log('Erreur dans save_multi_commandes.php: ' . $e->getMessage());

    // Envoyer la réponse d'erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création des commandes: ' . $e->getMessage()
    ]);
} 