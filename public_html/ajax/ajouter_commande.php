<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier le token de soumission pour éviter les doubles soumissions
if (!isset($_POST['submission_token']) || !isset($_SESSION['last_submission_token']) || 
    $_POST['submission_token'] !== $_SESSION['last_submission_token']) {
    echo json_encode(['success' => false, 'message' => 'Token de soumission invalide']);
    exit;
}

// Supprimer le token utilisé pour empêcher sa réutilisation
unset($_SESSION['last_submission_token']);

// Log des données reçues
error_log("Données reçues pour l'ajout de commande (POST): " . print_r($_POST, true));

// Validation des données
$errors = [];

// Vérification du client
if (!isset($_POST['client_id']) || $_POST['client_id'] === '') {
    $errors[] = 'Le client est obligatoire';
}

// Vérification du fournisseur
if (!isset($_POST['fournisseur_id']) || $_POST['fournisseur_id'] === '') {
    $errors[] = 'Le fournisseur est obligatoire';
}

// Vérification du nom de la pièce
if (!isset($_POST['nom_piece']) || trim($_POST['nom_piece']) === '') {
    $errors[] = 'Le nom de la pièce est obligatoire';
}

// Vérification de la quantité
if (!isset($_POST['quantite']) || !is_numeric($_POST['quantite']) || floatval($_POST['quantite']) <= 0) {
    $errors[] = 'La quantité doit être supérieure à 0';
}

// Vérification du prix estimé
if (!isset($_POST['prix_estime']) || !is_numeric($_POST['prix_estime']) || floatval($_POST['prix_estime']) < 0) {
    $errors[] = 'Le prix estimé doit être supérieur ou égal à 0';
}

if (!empty($errors)) {
    error_log("Erreurs de validation : " . print_r($errors, true));
    echo json_encode([
        'success' => false, 
        'message' => implode(', ', $errors)
    ]);
    exit;
}

try {
    // Générer une référence unique
    $reference = 'CMD-' . date('Ymd') . '-' . uniqid();
    
    // Log de l'ID de réparation
    error_log("ID de réparation reçu: " . (isset($_POST['reparation_id']) ? $_POST['reparation_id'] : 'non défini'));
    
    // Préparer la requête SQL
    $stmt = $shop_pdo->prepare("
        INSERT INTO commandes_pieces (
            reference, client_id, fournisseur_id, reparation_id, 
            nom_piece, code_barre, quantite, prix_estime, 
            statut, date_creation
        ) VALUES (
            :reference, :client_id, :fournisseur_id, :reparation_id, 
            :nom_piece, :code_barre, :quantite, :prix_estime, 
            :statut, NOW()
        )
    ");

    // Préparer les données
    $data = [
        'reference' => $reference,
        'client_id' => $_POST['client_id'],
        'fournisseur_id' => $_POST['fournisseur_id'],
        'reparation_id' => isset($_POST['reparation_id']) && !empty($_POST['reparation_id']) ? $_POST['reparation_id'] : null,
        'nom_piece' => trim($_POST['nom_piece']),
        'code_barre' => isset($_POST['code_barre']) ? trim($_POST['code_barre']) : null,
        'quantite' => floatval($_POST['quantite']),
        'prix_estime' => floatval($_POST['prix_estime']),
        'statut' => isset($_POST['statut']) ? $_POST['statut'] : 'en_attente'
    ];

    // Log des données à insérer
    error_log("Données à insérer : " . print_r($data, true));

    // Exécuter la requête avec les données
    $stmt->execute($data);

    // Log du succès
    error_log("Commande ajoutée avec succès. ID: " . $shop_pdo->lastInsertId());

    echo json_encode(['success' => true, 'message' => 'Commande ajoutée avec succès']);
} catch (PDOException $e) {
    error_log("Erreur lors de l'ajout de la commande : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la commande: ' . $e->getMessage()]);
} 