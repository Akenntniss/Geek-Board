<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once 'config/database.php';

echo "<h1>Test de connexion à la base de données principale</h1>";

try {
    // Tester la connexion à la base de données principale
    $pdo = getMainDBConnection();
    echo "<div style='color: green;'>✓ Connexion à la base de données principale réussie!</div>";
    
    // Vérifier si la table superadmins existe
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables dans la base de données:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    if (in_array('superadmins', $tables)) {
        echo "<div style='color: green;'>✓ La table 'superadmins' existe.</div>";
        
        // Vérifier le contenu de la table superadmins
        $superadmins = $pdo->query("SELECT id, username, full_name, email, active FROM superadmins")->fetchAll();
        
        echo "<h2>Superadmins dans la base de données:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Active</th></tr>";
        
        foreach ($superadmins as $admin) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($admin['id']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['active']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        if (count($superadmins) == 0) {
            echo "<div style='color: red;'>⚠ Aucun superadmin trouvé dans la table!</div>";
            
            // Proposer de créer un superadmin
            echo "<h3>Créer un nouveau superadmin:</h3>";
            echo "<form method='post' action='create_superadmin.php'>";
            echo "<button type='submit' name='create' value='1' style='padding: 10px; background-color: blue; color: white;'>Créer un superadmin par défaut</button>";
            echo "</form>";
        }
    } else {
        echo "<div style='color: red;'>⚠ La table 'superadmins' n'existe pas!</div>";
        
        // Proposer de créer la table superadmins
        echo "<h3>Créer les tables nécessaires:</h3>";
        echo "<form method='post' action='superadmin/create_shops_table.php'>";
        echo "<button type='submit' style='padding: 10px; background-color: blue; color: white;'>Créer les tables</button>";
        echo "</form>";
    }
    
    // Afficher les paramètres de connexion
    echo "<h2>Paramètres de connexion:</h2>";
    echo "<ul>";
    echo "<li>MAIN_DB_HOST: " . MAIN_DB_HOST . "</li>";
    echo "<li>MAIN_DB_PORT: " . MAIN_DB_PORT . "</li>";
    echo "<li>MAIN_DB_NAME: " . MAIN_DB_NAME . "</li>";
    echo "<li>MAIN_DB_USER: " . MAIN_DB_USER . "</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<div style='color: red;'>⚠ Erreur: " . $e->getMessage() . "</div>";
}
?> 