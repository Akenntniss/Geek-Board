<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Fonction pour journaliser les actions
function logAction($message) {
    echo "<div style='padding: 5px 10px; margin: 5px 0; border-left: 3px solid #2196F3;'>{$message}</div>";
    error_log("FIX_CONNECTIONS: {$message}");
}

// Afficher un en-tête HTML
echo '<!DOCTYPE html>
<html>
<head>
    <title>Correction des connexions</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; }
        .info { background-color: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        button, .button { 
            padding: 10px 15px; 
            background: #4CAF50; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin: 5px 0;
        }
        button:hover, .button:hover { background: #45a049; }
        .button.blue { background: #2196F3; }
        .button.blue:hover { background: #0b7dda; }
        .button.red { background: #f44336; }
        .button.red:hover { background: #d32f2f; }
        .button.gray { background: #9e9e9e; }
        .button.gray:hover { background: #757575; }
        .result { margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 5px solid #4CAF50; }
    </style>
</head>
<body>
    <h1>Correction des connexions à la base de données</h1>
    <div class="info">
        <p>Cet outil va effectuer les actions suivantes :</p>
        <ol>
            <li>Désactiver le mode superadmin (s\'il est actif)</li>
            <li>Vérifier et mettre à jour les informations du magasin en session</li>
            <li>Réinitialiser et tester les connexions aux bases de données</li>
            <li>Vérifier que les opérations d\'insertion utilisent la bonne base</li>
        </ol>
    </div>
    <div class="result">';

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Étape 1: Désactiver le mode superadmin
if (isset($_SESSION['superadmin_mode']) && $_SESSION['superadmin_mode'] === true) {
    $_SESSION['superadmin_mode'] = false;
    logAction("✅ Mode superadmin désactivé");
} else {
    logAction("ℹ️ Le mode superadmin n'était pas actif");
}

// Étape 2: Vérifier et mettre à jour les informations du magasin en session
if (!isset($_SESSION['shop_id'])) {
    logAction("⚠️ Aucun magasin sélectionné en session");
} else {
    $shop_id = $_SESSION['shop_id'];
    logAction("ℹ️ ID du magasin en session: {$shop_id}");
    
    try {
        $main_pdo = getMainDBConnection();
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shop) {
            logAction("✅ Magasin trouvé dans la base principale: {$shop['name']}");
            
            // Mettre à jour le nom du magasin en session s'il est manquant
            if (!isset($_SESSION['shop_name']) || empty($_SESSION['shop_name'])) {
                $_SESSION['shop_name'] = $shop['name'];
                logAction("✅ Nom du magasin mis à jour en session: {$shop['name']}");
            } else {
                logAction("ℹ️ Nom du magasin déjà défini en session: {$_SESSION['shop_name']}");
            }
            
            // Stocker les informations du magasin pour les tests
            $shop_config = [
                'host' => $shop['db_host'],
                'port' => $shop['db_port'],
                'user' => $shop['db_user'],
                'pass' => $shop['db_pass'],
                'dbname' => $shop['db_name']
            ];
        } else {
            logAction("❌ ERREUR: Aucun magasin trouvé avec l'ID {$shop_id}");
        }
    } catch (Exception $e) {
        logAction("❌ Erreur lors de la récupération des informations du magasin: " . $e->getMessage());
    }
}

// Étape 3: Réinitialiser et tester les connexions
global $shop_pdo, $main_pdo;
$shop_pdo = null; // Forcer la réinitialisation de la connexion

logAction("ℹ️ Réinitialisation des connexions...");

// Tester la connexion principale
try {
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    logAction("✅ Connexion à la base principale réussie: {$result['db_name']}");
    $main_db_name = $result['db_name'];
} catch (Exception $e) {
    logAction("❌ Erreur de connexion à la base principale: " . $e->getMessage());
    $main_db_name = null;
}

// Tester la connexion au magasin
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    logAction("✅ Connexion à la base du magasin réussie: {$result['db_name']}");
    $shop_db_name = $result['db_name'];
    
    // Vérifier si c'est la même base que la principale
    if ($main_db_name && $shop_db_name === $main_db_name) {
        logAction("⚠️ PROBLÈME: La connexion du magasin utilise la même base que la principale!");
    } else {
        logAction("✅ Connexion correcte: La base du magasin est différente de la base principale.");
    }
} catch (Exception $e) {
    logAction("❌ Erreur de connexion à la base du magasin: " . $e->getMessage());
    $shop_db_name = null;
}

// Étape 4: Test d'insertion dans la base du magasin
if ($shop_db_name) {
    logAction("ℹ️ Test d'insertion dans la base du magasin...");
    
    try {
        // Créer une table temporaire pour le test
        $shop_pdo->exec("CREATE TEMPORARY TABLE IF NOT EXISTS test_insertion (id INT AUTO_INCREMENT PRIMARY KEY, test_value VARCHAR(255), timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        
        // Insérer un enregistrement de test
        $test_value = "Test à " . date('H:i:s');
        $stmt = $shop_pdo->prepare("INSERT INTO test_insertion (test_value) VALUES (?)");
        $stmt->execute([$test_value]);
        $last_id = $shop_pdo->lastInsertId();
        
        // Vérifier que l'insertion a fonctionné
        $stmt = $shop_pdo->prepare("SELECT * FROM test_insertion WHERE id = ?");
        $stmt->execute([$last_id]);
        $test_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_record && $test_record['test_value'] === $test_value) {
            logAction("✅ Test d'insertion réussi dans la base {$shop_db_name}");
        } else {
            logAction("❌ Échec du test d'insertion dans la base {$shop_db_name}");
        }
    } catch (Exception $e) {
        logAction("❌ Erreur lors du test d'insertion: " . $e->getMessage());
    }
}

// Vérifier également si la fonction getShopDBConnection a été correctement modifiée
logAction("ℹ️ Vérification de la fonction getShopDBConnection()...");
$file_content = file_get_contents('config/database.php');
if (strpos($file_content, 'Mode superadmin détecté mais ignoré') !== false) {
    logAction("✅ La fonction getShopDBConnection() a bien été modifiée pour ignorer le mode superadmin");
} else {
    logAction("⚠️ La fonction getShopDBConnection() ne semble pas avoir été modifiée pour ignorer le mode superadmin");
}

echo '</div>

<div class="actions">
    <a href="/debug_shop_connection.php" class="button blue">Vérifier les connexions en détail</a>
    <a href="/index.php?page=ajouter_reparation" class="button">Ajouter une réparation</a>
    <a href="/index.php" class="button gray">Retour à l\'application</a>
</div>

</body>
</html>';
?> 