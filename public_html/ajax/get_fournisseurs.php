<?php
/**
 * API pour récupérer la liste des fournisseurs
 * Compatible avec le format attendu par dashboard-commands.js
 */

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de session avant de démarrer la session
require_once '../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// ⚡ OPTIMISATION: Journalisation désactivée pour de meilleures performances
// error_log("=== Début de get_fournisseurs.php ===");
// error_log("SESSION: " . print_r($_SESSION, true));

// Inclure la configuration de la base de données
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expirée. Veuillez vous reconnecter.',
        'redirect' => '/pages/login.php'
    ]);
    exit;
}

// Vérifier que le shop_id est défini dans la session
if (!isset($_SESSION['shop_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session invalide. Veuillez vous reconnecter.',
        'redirect' => '/pages/login.php'
    ]);
    exit;
}

// Vérifier que le shop_id est valide
try {
    $pdo_main = getMainDBConnection();
    $stmt = $pdo_main->prepare("SELECT id FROM shops WHERE id = ? AND active = 1");
    $stmt->execute([$_SESSION['shop_id']]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Magasin invalide. Veuillez vous reconnecter.',
            'redirect' => '/pages/login.php'
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de vérification du magasin',
        'redirect' => '/pages/login.php'
    ]);
    exit;
}

// ⚡ Log supprimé pour performance

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible d'obtenir la connexion à la base de données");
    }
    
    // Construire la requête SQL pour récupérer les fournisseurs
    $sql = "SELECT id, nom FROM fournisseurs ORDER BY nom";
    
    $stmt = $shop_pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . implode(' ', $shop_pdo->errorInfo()));
    }
    
    // Exécuter la requête
    $stmt->execute();
    
    // Récupérer les résultats
    $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les fournisseurs au format JSON attendu par dashboard-commands.js
    echo json_encode([
        'success' => true,
        'fournisseurs' => $fournisseurs,
        'count' => count($fournisseurs)
    ]);
    
} catch (PDOException $e) {
    // Retourner une erreur au format JSON
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Retourner une erreur au format JSON
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 

// ⚡ Log supprimé pour performance 