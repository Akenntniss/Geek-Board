<?php
/**
 * Script de débogage pour identifier les problèmes dans l'API
 */

// Afficher les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Informations de débogage</h1>";

// Afficher la version PHP et les extensions
echo "<h2>Environnement PHP</h2>";
echo "Version PHP: " . phpversion() . "<br>";
echo "<h3>Extensions chargées:</h3>";
echo "<ul>";
$extensions = get_loaded_extensions();
foreach ($extensions as $extension) {
    echo "<li>$extension</li>";
}
echo "</ul>";

// Tester les fonctions JSON
echo "<h2>Fonctions JSON</h2>";
echo "json_encode existe: " . (function_exists('json_encode') ? 'Oui' : 'Non') . "<br>";
echo "json_decode existe: " . (function_exists('json_decode') ? 'Oui' : 'Non') . "<br>";

// Tester la connexion à la base de données
echo "<h2>Test de base de données</h2>";
try {
    require_once '../includes/functions.php';
    global $pdo;
    
    if ($pdo) {
        echo "Connexion à la base de données: <span style='color:green'>OK</span><br>";
        echo "Type de base de données: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
        echo "Version serveur: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
        
        // Vérifier si les tables existent
        $tables = ["conversations", "conversation_participants", "messages", "message_reads", "message_attachments", "message_reactions"];
        echo "<h3>Vérification des tables:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE :table");
            $stmt->execute([':table' => $table]);
            $exists = $stmt->rowCount() > 0;
            echo "<li>$table: " . ($exists ? '<span style="color:green">Existe</span>' : '<span style="color:red">N\'existe pas</span>') . "</li>";
        }
        echo "</ul>";
        
        // Tester une requête JSON
        echo "<h3>Test fonctions JSON SQL:</h3>";
        try {
            $stmt = $pdo->prepare("SELECT JSON_OBJECT('test', 'value') as json_test");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Test JSON_OBJECT: " . ($result ? '<span style="color:green">OK</span>' : '<span style="color:red">Échec</span>') . "<br>";
            echo "Résultat: " . print_r($result, true) . "<br>";
        } catch (PDOException $e) {
            echo "Erreur lors du test JSON_OBJECT: <span style='color:red'>" . $e->getMessage() . "</span><br>";
        }
        
        try {
            $stmt = $pdo->prepare("SELECT JSON_ARRAYAGG('test') as json_test");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Test JSON_ARRAYAGG: " . ($result ? '<span style="color:green">OK</span>' : '<span style="color:red">Échec</span>') . "<br>";
            echo "Résultat: " . print_r($result, true) . "<br>";
        } catch (PDOException $e) {
            echo "Erreur lors du test JSON_ARRAYAGG: <span style='color:red'>" . $e->getMessage() . "</span><br>";
        }
    } else {
        echo "Connexion à la base de données: <span style='color:red'>Échec</span><br>";
    }
} catch (Exception $e) {
    echo "Erreur de connexion à la base de données: <span style='color:red'>" . $e->getMessage() . "</span><br>";
}

// Afficher les informations de session
echo "<h2>Informations de session</h2>";
echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'Oui' : 'Non') . "<br>";
echo "ID de session: " . session_id() . "<br>";
echo "<h3>Variables de session:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Afficher les variables serveur
echo "<h2>Variables serveur</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>"; 