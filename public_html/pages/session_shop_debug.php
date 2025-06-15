<?php
/**
 * Script de diagnostic pour les problèmes de sessions et de bases de données
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

echo '<h1>Diagnostic des Sessions et Bases de données</h1>';

// Afficher les informations de session
echo '<h2>Informations de Session</h2>';
echo '<pre>';
echo 'Session ID: ' . session_id() . "\n";
echo 'Session Data: ' . print_r($_SESSION, true) . "\n";
echo '</pre>';

// Afficher le shop_id en session, si présent
echo '<h2>ID du Magasin en Session</h2>';
if (isset($_SESSION['shop_id'])) {
    echo '<p style="color:green">shop_id est défini en session: ' . $_SESSION['shop_id'] . '</p>';
    
    // Définir un nouveau shop_id si demandé
    if (isset($_GET['shop_id'])) {
        $old_id = $_SESSION['shop_id'];
        $_SESSION['shop_id'] = (int)$_GET['shop_id'];
        echo '<p style="color:blue">shop_id a été mis à jour de ' . $old_id . ' à ' . $_SESSION['shop_id'] . '</p>';
    }
    
    // Option pour effacer le shop_id
    echo '<p><a href="?clear_shop_id=1">Effacer le shop_id de la session</a></p>';
} else {
    echo '<p style="color:red">shop_id n\'est pas défini en session</p>';
    
    // Définir un shop_id si demandé
    if (isset($_GET['shop_id'])) {
        $_SESSION['shop_id'] = (int)$_GET['shop_id'];
        echo '<p style="color:blue">shop_id a été défini à ' . $_SESSION['shop_id'] . '</p>';
    } else {
        echo '<p>Vous pouvez définir un shop_id avec: <a href="?shop_id=1">?shop_id=1</a></p>';
    }
}

// Effacer shop_id si demandé
if (isset($_GET['clear_shop_id'])) {
    unset($_SESSION['shop_id']);
    echo '<p style="color:orange">shop_id a été effacé de la session</p>';
    echo '<meta http-equiv="refresh" content="1;url=session_shop_debug.php">';
    exit;
}

// Vérifier le mode superadmin
echo '<h2>Mode Superadmin</h2>';
if (isset($_SESSION['superadmin_mode']) && $_SESSION['superadmin_mode'] === true) {
    echo '<p style="color:red">Mode superadmin est ACTIF</p>';
    
    // Option pour désactiver le mode superadmin
    echo '<p><a href="?disable_superadmin=1">Désactiver le mode superadmin</a></p>';
} else {
    echo '<p style="color:green">Mode superadmin est INACTIF</p>';
}

// Désactiver le mode superadmin si demandé
if (isset($_GET['disable_superadmin'])) {
    $_SESSION['superadmin_mode'] = false;
    echo '<p style="color:green">Mode superadmin a été désactivé</p>';
    echo '<meta http-equiv="refresh" content="1;url=session_shop_debug.php">';
    exit;
}

// Si nous avons un shop_id, vérifier les informations du magasin et les connexions
if (isset($_SESSION['shop_id'])) {
    echo '<h2>Test des Connexions</h2>';
    
    try {
        // Inclure la configuration de la base de données
        require_once 'config/database.php';
        
        // Vérifier la connexion principale
        $main_pdo = getMainDBConnection();
        $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<p>Connexion principale: <strong>' . $result['db_name'] . '</strong></p>';
        
        // Récupérer les informations du magasin
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$_SESSION['shop_id']]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shop) {
            echo '<h3>Informations du Magasin</h3>';
            echo '<pre>';
            echo 'ID: ' . $shop['id'] . "\n";
            echo 'Nom: ' . $shop['name'] . "\n";
            echo 'Base de données: ' . $shop['db_name'] . "\n";
            echo 'Hôte: ' . $shop['db_host'] . "\n";
            echo 'Port: ' . ($shop['db_port'] ?: '3306') . "\n";
            echo 'Utilisateur: ' . $shop['db_user'] . "\n";
            echo '</pre>';
            
            // Mettre à jour le nom du magasin en session
            if (!isset($_SESSION['shop_name']) || $_SESSION['shop_name'] !== $shop['name']) {
                $_SESSION['shop_name'] = $shop['name'];
                echo '<p style="color:blue">shop_name a été mis à jour en session: ' . $shop['name'] . '</p>';
            }
            
            // Vérifier la connexion au magasin via getShopDBConnection()
            $shop_pdo = getShopDBConnection();
            $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
            $shop_db_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shop_db_result['db_name'] === $shop['db_name']) {
                echo '<p style="color:green">Connexion magasin correcte: <strong>' . $shop_db_result['db_name'] . '</strong></p>';
            } else {
                echo '<p style="color:red">ERREUR: La connexion magasin pointe vers <strong>' . $shop_db_result['db_name'] . '</strong> au lieu de <strong>' . $shop['db_name'] . '</strong></p>';
            }
            
            // Vérifier si les scripts PHP d'ajout utilisent la bonne base
            echo '<h3>Test des Scripts d\'Ajout</h3>';
            echo '<p>Cette section vérifie les scripts utilisés lors de l\'ajout de clients.</p>';
            
            // Vérifier direct_add_client.php
            echo '<h4>direct_add_client.php</h4>';
            if (file_exists('ajax/direct_add_client.php')) {
                echo '<p>Le fichier direct_add_client.php existe</p>';
                
                // Vérifier si le fichier contient les bons changements
                $file_content = file_get_contents('ajax/direct_add_client.php');
                
                if (strpos($file_content, 'Vérifier que nous sommes bien connectés à la bonne base de données') !== false) {
                    echo '<p style="color:green">Le fichier direct_add_client.php contient les modifications nécessaires pour vérifier la base de données</p>';
                } else {
                    echo '<p style="color:orange">Le fichier direct_add_client.php pourrait ne pas contenir toutes les modifications nécessaires</p>';
                }
                
                if (strpos($file_content, 'USE ') !== false) {
                    echo '<p style="color:green">Le fichier direct_add_client.php contient la commande USE pour changer explicitement de base</p>';
                } else {
                    echo '<p style="color:orange">Le fichier direct_add_client.php ne contient pas de commande USE</p>';
                }
            } else {
                echo '<p style="color:red">Le fichier direct_add_client.php n\'existe pas!</p>';
            }
            
            // Vérifier ajouter_client.php
            echo '<h4>ajouter_client.php</h4>';
            if (file_exists('ajax/ajouter_client.php')) {
                echo '<p>Le fichier ajouter_client.php existe</p>';
            } else {
                echo '<p style="color:orange">Le fichier ajouter_client.php n\'existe pas</p>';
            }
            
            // Vérifier la fonction getShopDBConnection()
            echo '<h4>getShopDBConnection()</h4>';
            if (strpos(file_get_contents('config/database.php'), 'Mode superadmin détecté mais ignoré') !== false) {
                echo '<p style="color:green">La fonction getShopDBConnection() a été modifiée pour ignorer le mode superadmin</p>';
            } else {
                echo '<p style="color:red">La fonction getShopDBConnection() ne semble pas avoir été modifiée pour ignorer le mode superadmin</p>';
            }
        } else {
            echo '<p style="color:red">Aucun magasin trouvé avec l\'ID ' . $_SESSION['shop_id'] . '</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color:red">Erreur lors des tests: ' . $e->getMessage() . '</p>';
    }
}

// Ajouter un formulaire pour tester direct_add_client.php
echo '<h2>Test d\'Ajout de Client</h2>';
echo '<form action="ajax/direct_add_client.php" method="post" target="_blank">';
echo '<input type="hidden" name="shop_id" value="' . ($_SESSION['shop_id'] ?? '') . '">';
echo '<div style="margin-bottom: 10px;">';
echo '<label style="display: block; margin-bottom: 5px;">Nom:</label>';
echo '<input type="text" name="nom" value="Test_' . date('His') . '" style="padding: 5px; width: 300px;">';
echo '</div>';
echo '<div style="margin-bottom: 10px;">';
echo '<label style="display: block; margin-bottom: 5px;">Prénom:</label>';
echo '<input type="text" name="prenom" value="Debug_' . rand(1000, 9999) . '" style="padding: 5px; width: 300px;">';
echo '</div>';
echo '<div style="margin-bottom: 10px;">';
echo '<label style="display: block; margin-bottom: 5px;">Téléphone:</label>';
echo '<input type="text" name="telephone" value="07' . rand(10000000, 99999999) . '" style="padding: 5px; width: 300px;">';
echo '</div>';
echo '<button type="submit" style="padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Tester direct_add_client.php</button>';
echo '</form>';

// Liens utiles
echo '<h2>Liens Utiles</h2>';
echo '<ul>';
echo '<li><a href="/test_shop_client.php">Test complet d\'ajout client</a></li>';
echo '<li><a href="/debug_shop_connection.php">Diagnostic des connexions</a></li>';
echo '<li><a href="/fix_connections.php">Correction des connexions</a></li>';
echo '<li><a href="/index.php?page=ajouter_reparation">Ajouter une réparation</a></li>';
echo '</ul>';
?> 