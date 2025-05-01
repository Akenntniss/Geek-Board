<?php
// Fichier de débogage de la base de données

// Inclure le fichier de connexion
require_once dirname(__DIR__) . '/config/database.php';

// Vérifier que l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: text/plain');
    echo "Non autorisé";
    exit;
}

// Empêcher la mise en cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>Diagnostic Base de Données</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
th, td { text-align: left; padding: 8px; border: 1px solid #ddd; }
th { background-color: #f4f4f4; }
tr:nth-child(even) { background-color: #f9f9f9; }
.code { font-family: monospace; background-color: #f4f4f4; padding: 10px; border-radius: 4px; white-space: pre; overflow-x: auto; }
.error { color: red; font-weight: bold; }
.success { color: green; }
.section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
</style></head><body>";

echo "<h1>Diagnostic de la Base de Données</h1>";

try {
    echo "<div class='section'>";
    echo "<h2>Connexion à la Base de Données</h2>";
    echo "<p class='success'>✅ Connexion établie avec succès</p>";
    echo "<p>Version PHP: " . phpversion() . "</p>";
    echo "<p>PDO Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</p>";
    echo "<p>Version du serveur: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
    echo "</div>";
    
    // Tables disponibles
    echo "<div class='section'>";
    echo "<h2>Tables disponibles</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<table>";
        echo "<tr><th>Table</th></tr>";
        foreach ($tables as $table) {
            echo "<tr><td>$table</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>Aucune table trouvée</p>";
    }
    echo "</div>";
    
    // Vérification de la table statuts
    echo "<div class='section'>";
    echo "<h2>Table 'statuts'</h2>";
    $statuts_exists = in_array('statuts', $tables);
    
    if ($statuts_exists) {
        echo "<p class='success'>✅ La table 'statuts' existe</p>";
        
        // Structure de la table
        echo "<h3>Structure de la table</h3>";
        $stmt = $pdo->query("DESCRIBE statuts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . ($value === null ? 'NULL' : $value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Données de la table
        echo "<h3>Contenu de la table (max 20 lignes)</h3>";
        $stmt = $pdo->query("SELECT * FROM statuts LIMIT 20");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($data) > 0) {
            echo "<table>";
            // En-têtes
            echo "<tr>";
            foreach (array_keys($data[0]) as $header) {
                echo "<th>$header</th>";
            }
            echo "</tr>";
            
            // Données
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . ($value === null ? 'NULL' : $value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>Aucune donnée dans la table 'statuts'</p>";
        }
    } else {
        echo "<p class='error'>❌ La table 'statuts' n'existe pas</p>";
    }
    echo "</div>";
    
    // Vérification de la table reparations
    echo "<div class='section'>";
    echo "<h2>Table 'reparations'</h2>";
    $reparations_exists = in_array('reparations', $tables);
    
    if ($reparations_exists) {
        echo "<p class='success'>✅ La table 'reparations' existe</p>";
        
        // Structure de la table
        echo "<h3>Structure de la table</h3>";
        $stmt = $pdo->query("DESCRIBE reparations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . ($value === null ? 'NULL' : $value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Quelques exemples de statuts utilisés
        echo "<h3>Exemples de statuts utilisés</h3>";
        $stmt = $pdo->query("SELECT DISTINCT statut FROM reparations LIMIT 20");
        $statuts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($statuts) > 0) {
            echo "<table>";
            echo "<tr><th>Statut</th></tr>";
            foreach ($statuts as $statut) {
                echo "<tr><td>$statut</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>Aucun statut trouvé dans la table 'reparations'</p>";
        }
        
        // Compter les réparations par statut
        echo "<h3>Nombre de réparations par statut</h3>";
        $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM reparations GROUP BY statut");
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($counts) > 0) {
            echo "<table>";
            echo "<tr><th>Statut</th><th>Nombre</th></tr>";
            foreach ($counts as $count) {
                echo "<tr><td>{$count['statut']}</td><td>{$count['count']}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>Aucune donnée de réparation trouvée</p>";
        }
    } else {
        echo "<p class='error'>❌ La table 'reparations' n'existe pas</p>";
    }
    echo "</div>";
    
    // Test des requêtes spécifiques
    echo "<div class='section'>";
    echo "<h2>Tests des requêtes</h2>";
    
    // Test de la requête pour les nouvelles réparations
    echo "<h3>Test de la requête pour 'nouvelles'</h3>";
    try {
        if ($statuts_exists) {
            $sql = "SELECT r.id, r.statut 
                   FROM reparations r 
                   WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (1, 2, 3))
                   AND r.archive = 'NON'
                   LIMIT 5";
        } else {
            $sql = "SELECT r.id, r.statut 
                   FROM reparations r 
                   WHERE r.statut IN ('nouvelle', 'diagnostique', 'devis')
                   AND r.archive = 'NON'
                   LIMIT 5";
        }
        
        echo "<div class='code'>" . htmlspecialchars($sql) . "</div>";
        
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            echo "<p class='success'>✅ Requête exécutée avec succès, " . count($results) . " résultats</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Statut</th></tr>";
            foreach ($results as $result) {
                echo "<tr><td>{$result['id']}</td><td>{$result['statut']}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Requête exécutée avec succès, mais aucun résultat trouvé</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur lors de l'exécution de la requête: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='section error'>";
    echo "<h2>Erreur de connexion</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>"; 