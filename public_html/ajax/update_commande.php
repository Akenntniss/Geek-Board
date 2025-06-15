<?php
// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Définir l'en-tête JSON avant tout
header('Content-Type: application/json');

// Inclure la configuration de session avant de démarrer la session
require_once '../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Journalisation détaillée
error_log("=== Début de update_commande.php ===");
error_log("POST params: " . print_r($_POST, true));
error_log("SESSION: " . print_r($_SESSION, true));

// Inclusion du fichier de configuration et functions
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Erreur: utilisateur non connecté");
    echo json_encode([
        'success' => false,
        'message' => 'Session expirée. Veuillez vous reconnecter.',
        'redirect' => '/pages/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/index.php?page=commandes_pieces')
    ]);
    exit;
}

// Vérifier que le shop_id est défini dans la session
if (!isset($_SESSION['shop_id'])) {
    error_log("Erreur: shop_id non défini dans la session");
    echo json_encode([
        'success' => false,
        'message' => 'Session invalide. Veuillez vous reconnecter.',
        'redirect' => '/pages/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/index.php?page=commandes_pieces')
    ]);
    exit;
}

// Vérifier que le shop_id est valide
try {
    $pdo_main = getMainDBConnection();
    $stmt = $pdo_main->prepare("SELECT id FROM shops WHERE id = ? AND active = 1");
    $stmt->execute([$_SESSION['shop_id']]);
    if (!$stmt->fetch()) {
        error_log("Erreur: shop_id invalide ou inactif");
        echo json_encode([
            'success' => false,
            'message' => 'Magasin invalide. Veuillez vous reconnecter.',
            'redirect' => '/pages/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/index.php?page=commandes_pieces')
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("Erreur lors de la vérification du shop_id: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de vérification du magasin',
        'redirect' => '/pages/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/index.php?page=commandes_pieces')
    ]);
    exit;
}

// Vérifier la connexion à la base de données du magasin
try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible d'obtenir la connexion à la base de données");
    }
    error_log("Connexion à la base de données réussie");
} catch (Exception $e) {
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier si l'ID de la commande est fourni
if (!isset($_POST['commande_id'])) {
    error_log("Erreur: ID de la commande non fourni");
    echo json_encode([
        'success' => false,
        'message' => 'ID de la commande non fourni'
    ]);
    exit;
}

$commande_id = intval($_POST['commande_id']);
error_log("ID de la commande: " . $commande_id);

try {
    // Préparer les données à mettre à jour
    $data = [
        'client_id' => !empty($_POST['client_id']) ? $_POST['client_id'] : null,
        'fournisseur_id' => !empty($_POST['fournisseur_id']) ? $_POST['fournisseur_id'] : null,
        'nom_piece' => $_POST['nom_piece'],
        'code_barre' => $_POST['code_barre'],
        'quantite' => $_POST['quantite'],
        'prix_estime' => $_POST['prix_estime'],
        'date_creation' => !empty($_POST['date_creation']) ? $_POST['date_creation'] : null,
        'statut' => $_POST['statut']
    ];
    
    // Construire la requête SQL
    $sql = "UPDATE commandes_pieces SET ";
    $params = [];
    
    foreach ($data as $key => $value) {
        if ($value !== null) {
        $sql .= "$key = ?, ";
        $params[] = $value;
        }
    }
    
    // Supprimer la dernière virgule et ajouter la condition WHERE
    $sql = rtrim($sql, ", ") . " WHERE id = ?";
    $params[] = $commande_id;
    
    error_log("Requête SQL: " . $sql);
    error_log("Paramètres: " . print_r($params, true));
    
    // Exécuter la requête
    $stmt = $shop_pdo->prepare($sql);
    $success = $stmt->execute($params);
    
    if ($success) {
        error_log("Commande mise à jour avec succès");
        echo json_encode([
            'success' => true,
            'message' => 'Commande mise à jour avec succès'
        ]);
    } else {
        error_log("Erreur lors de la mise à jour de la commande");
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour de la commande'
        ]);
    }
} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour de la commande: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur générale: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur inattendue s\'est produite'
    ]);
}

error_log("=== Fin de update_commande.php ==="); 