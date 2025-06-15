<?php
/**
 * Outil de diagnostic pour la connexion aux bases de données des magasins
 * Ce script teste la connexion à la base de données du magasin de l'utilisateur
 * et affiche des informations détaillées sur la configuration.
 */
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Mode débogage pour administrateurs (à supprimer en production)
$debug_key = 'debug123'; // Clé de sécurité temporaire
$admin_mode = false;

if (isset($_GET['debug']) && $_GET['debug'] === $debug_key) {
    $admin_mode = true;
    // Si l'ID utilisateur est fourni, l'utiliser pour le diagnostic
    if (isset($_GET['user_id'])) {
        $_SESSION['user_id'] = (int)$_GET['user_id'];
    } else {
        // Utiliser un ID administrateur par défaut
        $_SESSION['user_id'] = 1; // ID de l'administrateur par défaut
    }
    
    // Si l'ID magasin est fourni, l'utiliser pour le diagnostic
    if (isset($_GET['shop_id'])) {
        $_SESSION['shop_id'] = (int)$_GET['shop_id'];
    }
}

// Vérification de la connexion de l'utilisateur
if (!isset($_SESSION['user_id']) && !$admin_mode) {
    echo "<p>Vous devez être connecté pour accéder à cet outil.</p>";
    echo "<p>Si vous êtes administrateur, utilisez le mode de débogage avec l'URL: <code>debug_shop_connection.php?debug=debug123</code></p>";
    exit;
}

// Inclusion des fichiers nécessaires
require_once 'config/database.php';

// Fonction pour afficher les informations de manière formatée
function displayInfo($label, $value, $isError = false) {
    $color = $isError ? 'red' : 'green';
    echo "<tr><th>$label</th><td style='color: $color;'>$value</td></tr>";
}

// En-tête HTML
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Connexion Magasin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { width: 30%; background-color: #f2f2f2; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .section { margin: 20px 0; padding: 10px; background-color: #f9f9f9; border-radius: 5px; }
        .admin-mode { background-color: #ffe3e3; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Diagnostic Connexion Magasin</h1>';

// Afficher bannière mode administrateur
if ($admin_mode) {
    echo '<div class="admin-mode">
        <strong>Mode Administrateur activé</strong> - Vous utilisez l\'accès de débogage.
        <p>Utilisateur ID: ' . $_SESSION['user_id'] . ' / Magasin ID: ' . ($_SESSION['shop_id'] ?? 'Non défini') . '</p>
    </div>';
}

// Section Utilisateur
echo '<div class="section">
    <h2>Informations Utilisateur</h2>
    <table>';

displayInfo("ID Utilisateur", $_SESSION['user_id'] ?? 'Non défini', !isset($_SESSION['user_id']));
displayInfo("ID Magasin", $_SESSION['shop_id'] ?? 'Non défini', !isset($_SESSION['shop_id']));

echo '</table></div>';

// Section Magasin
echo '<div class="section">
    <h2>Informations Magasin</h2>
    <table>';

if (isset($_SESSION['shop_id'])) {
    try {
        $main_pdo = getMainDBConnection();
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$_SESSION['shop_id']]);
        $shop = $stmt->fetch();
        
        if ($shop) {
            displayInfo("Nom du magasin", $shop['name']);
            displayInfo("Base de données", $shop['db_name']);
            displayInfo("Hôte", $shop['db_host']);
            displayInfo("Port", $shop['db_port'] ?: '3306');
            displayInfo("Utilisateur BD", $shop['db_user']);
            
            // Vérifie si les informations de connexion sont complètes
            $configComplete = !empty($shop['db_host']) && !empty($shop['db_user']) && !empty($shop['db_name']);
            displayInfo("Configuration complète", $configComplete ? 'Oui' : 'Non', !$configComplete);
        } else {
            displayInfo("Magasin trouvé", "Non - ID magasin non trouvé dans la base", true);
        }
    } catch (Exception $e) {
        displayInfo("Erreur", "Impossible de récupérer les informations du magasin: " . $e->getMessage(), true);
    }
} else {
    displayInfo("Magasin", "Aucun magasin associé à l'utilisateur", true);
}

echo '</table></div>';

// Test de connexion aux bases de données
echo '<div class="section">
    <h2>Test de Connexion</h2>
    <table>';

// Test connexion principale
try {
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    displayInfo("Connexion principale", "Connecté à: " . $result['db_name']);
} catch (Exception $e) {
    displayInfo("Connexion principale", "Échec: " . $e->getMessage(), true);
}

// Test connexion magasin
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    
    if (isset($_SESSION['shop_id'])) {
        $main_pdo = getMainDBConnection();
        $stmt = $main_pdo->prepare("SELECT db_name FROM shops WHERE id = ?");
        $stmt->execute([$_SESSION['shop_id']]);
        $shop = $stmt->fetch();
        
        if ($shop && $result['db_name'] === $shop['db_name']) {
            displayInfo("Connexion magasin", "Correctement connecté à: " . $result['db_name']);
        } else {
            displayInfo("Connexion magasin", "Connecté à la mauvaise base: " . $result['db_name'] . 
                      " (Attendu: " . ($shop ? $shop['db_name'] : 'inconnu') . ")", true);
        }
    } else {
        displayInfo("Connexion magasin", "Connecté à: " . $result['db_name'] . 
                  " (Mais aucun magasin n'est sélectionné)", true);
    }
} catch (Exception $e) {
    displayInfo("Connexion magasin", "Échec: " . $e->getMessage(), true);
}

echo '</table></div>';

// Section Diagnostics
echo '<div class="section">
    <h2>Diagnostics</h2>
    <table>';

// Vérifie si le cache de connexion est problématique
$diagnosis = [];

// Vérification des problèmes possibles
if (!isset($_SESSION['shop_id'])) {
    $diagnosis[] = [
        "problème" => "Aucun magasin associé à l'utilisateur",
        "solution" => "Assurez-vous que l'utilisateur est bien associé à un magasin dans la table des utilisateurs"
    ];
}

if (isset($_SESSION['shop_id'])) {
    try {
        $main_pdo = getMainDBConnection();
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$_SESSION['shop_id']]);
        $shop = $stmt->fetch();
        
        if (!$shop) {
            $diagnosis[] = [
                "problème" => "ID de magasin invalide",
                "solution" => "L'ID du magasin en session n'existe pas dans la table des magasins"
            ];
        } else {
            if (empty($shop['db_host']) || empty($shop['db_user']) || empty($shop['db_name'])) {
                $diagnosis[] = [
                    "problème" => "Configuration de base de données incomplète",
                    "solution" => "Remplissez tous les champs nécessaires dans la configuration du magasin (hôte, utilisateur, base)"
                ];
            }
        }
    } catch (Exception $e) {
        $diagnosis[] = [
            "problème" => "Erreur lors de la vérification du magasin",
            "solution" => "Erreur: " . $e->getMessage()
        ];
    }
}

// Test si la fonction getShopDBConnection fonctionne correctement
try {
    $shop_pdo = getShopDBConnection();
    $main_pdo = getMainDBConnection();
    
    $stmt_shop = $shop_pdo->query("SELECT DATABASE() as db_name");
    $shop_db = $stmt_shop->fetch()['db_name'];
    
    $stmt_main = $main_pdo->query("SELECT DATABASE() as db_name");
    $main_db = $stmt_main->fetch()['db_name'];
    
    if ($shop_db === $main_db && isset($_SESSION['shop_id'])) {
        $diagnosis[] = [
            "problème" => "getShopDBConnection() retourne la connexion principale",
            "solution" => "La fonction getShopDBConnection() ne crée pas correctement une connexion à la base du magasin"
        ];
    }
} catch (Exception $e) {
    $diagnosis[] = [
        "problème" => "Erreur lors du test des connexions",
        "solution" => "Erreur: " . $e->getMessage()
    ];
}

// Afficher les diagnostics
if (empty($diagnosis)) {
    echo "<tr><td colspan='2' class='success'>Aucun problème détecté dans la configuration.</td></tr>";
} else {
    foreach ($diagnosis as $item) {
        echo "<tr><th class='error'>" . $item['problème'] . "</th><td>" . $item['solution'] . "</td></tr>";
    }
}

echo '</table></div>';

// Pied de page
echo '<div class="section">
    <p><strong>Note:</strong> Ce fichier est un outil de diagnostic et ne doit pas être accessible en production.</p>
</div>';

echo '</body></html>';
?> 