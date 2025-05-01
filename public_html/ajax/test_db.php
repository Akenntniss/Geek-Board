<?php
// Afficher toutes les erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test de connexion à la base de données</h1>";

try {
    // Charger la configuration
    echo "<p>Chargement du fichier de configuration...</p>";
    require_once __DIR__ . '/../config/database.php';
    
    echo "<p>Configuration chargée avec succès.</p>";
    
    // Vérifier si la variable $pdo est définie
    if (isset($pdo)) {
        echo "<p style='color:green'>✓ Connexion PDO établie avec succès</p>";
        
        // Test d'une simple requête
        $stmt = $pdo->query("SELECT 'Test réussi' AS test");
        $result = $stmt->fetch();
        echo "<p>Résultat de la requête test: <strong>" . $result['test'] . "</strong></p>";
        
        // Afficher les informations sur la connexion
        echo "<h2>Informations sur la connexion:</h2>";
        echo "<ul>";
        echo "<li>Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</li>";
        echo "<li>Serveur: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</li>";
        echo "<li>Client: " . $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION) . "</li>";
        echo "</ul>";
        
        // Tester une requête sur une table réelle
        echo "<h2>Test sur la table 'statuts':</h2>";
        try {
            $stmt = $pdo->query("SELECT id, nom, couleur FROM statuts LIMIT 5");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nom</th><th>Couleur</th></tr>";
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['nom'] . "</td>";
                echo "<td style='background-color:" . $row['couleur'] . "'>" . $row['couleur'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Erreur lors de la requête sur la table statuts: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color:red'>✗ La variable \$pdo n'est pas définie dans le fichier de configuration.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Erreur: " . $e->getMessage() . "</p>";
    
    // Afficher la trace pour le débogage
    echo "<pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
} 