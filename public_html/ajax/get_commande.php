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
error_log("=== Début de get_commande.php ===");
error_log("GET params: " . print_r($_GET, true));
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

error_log("Shop ID trouvé dans la session: " . $_SESSION['shop_id']);

// Vérifier la connexion à la base de données
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
if (!isset($_GET['id'])) {
    error_log("Erreur: ID de la commande non fourni");
    echo json_encode([
        'success' => false,
        'message' => 'ID de la commande non fourni'
    ]);
    exit;
}

$commande_id = intval($_GET['id']);
error_log("ID de la commande: " . $commande_id);

try {
    // Récupérer les informations de la commande
    $query = "
        SELECT c.*, f.nom as fournisseur_nom, cl.nom as client_nom, cl.prenom as client_prenom
        FROM commandes_pieces c
        LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id
        LEFT JOIN clients cl ON c.client_id = cl.id
        WHERE c.id = ?
    ";
    error_log("Requête SQL: " . $query);
    
    $stmt = $shop_pdo->prepare($query);
    if (!$stmt) {
        throw new PDOException("Erreur lors de la préparation de la requête: " . print_r($shop_pdo->errorInfo(), true));
    }
    
    error_log("Exécution de la requête pour la commande ID: " . $commande_id);
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($commande) {
        error_log("Commande trouvée: " . print_r($commande, true));
        // Formater les dates pour l'affichage
        if (isset($commande['date_creation'])) {
            $commande['date_creation'] = date('Y-m-d H:i:s', strtotime($commande['date_creation']));
        }
        if (isset($commande['date_commande'])) {
            $commande['date_commande'] = $commande['date_commande'] ? date('Y-m-d H:i:s', strtotime($commande['date_commande'])) : null;
        }
        if (isset($commande['date_reception'])) {
            $commande['date_reception'] = $commande['date_reception'] ? date('Y-m-d H:i:s', strtotime($commande['date_reception'])) : null;
        }
        
        echo json_encode([
            'success' => true,
            'commande' => $commande
        ]);
    } else {
        error_log("Commande non trouvée pour l'ID: " . $commande_id);
        echo json_encode([
            'success' => false,
            'message' => 'Commande non trouvée'
        ]);
    }
} catch (PDOException $e) {
    error_log("Erreur PDO dans get_commande.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des informations de la commande'
    ]);
} catch (Exception $e) {
    error_log("Erreur générale dans get_commande.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur inattendue s\'est produite'
    ]);
} 

error_log("=== Fin de get_commande.php ==="); 