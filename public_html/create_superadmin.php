<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Si le formulaire est soumis
if (isset($_POST['create'])) {
    try {
        $pdo = getMainDBConnection();
        
        // Vérifier si la table superadmins existe
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        // Si la table n'existe pas, la créer
        if (!in_array('superadmins', $tables)) {
            $sql = "CREATE TABLE superadmins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $pdo->exec($sql);
            echo "<div style='color: green;'>Table 'superadmins' créée avec succès.</div>";
        }
        
        // Création d'un super administrateur par défaut
        $default_password = password_hash('SuperAdmin2024!', PASSWORD_DEFAULT);
        $sql = "INSERT INTO superadmins (username, password, full_name, email, active) 
                VALUES ('superadmin', :password, 'Super Administrateur', 'admin@geekboard.com', 1)
                ON DUPLICATE KEY UPDATE 
                password = :password, 
                full_name = 'Super Administrateur', 
                email = 'admin@geekboard.com', 
                active = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['password' => $default_password]);
        
        echo "<div style='color: green; font-size: 18px; margin: 20px 0;'>✓ Super administrateur créé/mis à jour avec succès!</div>";
        echo "<div style='background-color: #f0f0f0; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<p>Vous pouvez maintenant vous connecter avec les identifiants suivants:</p>";
        echo "<ul>";
        echo "<li><strong>Nom d'utilisateur:</strong> superadmin</li>";
        echo "<li><strong>Mot de passe:</strong> SuperAdmin2024!</li>";
        echo "</ul>";
        echo "<p style='color: red;'><strong>IMPORTANT:</strong> Changez ce mot de passe immédiatement après la première connexion!</p>";
        echo "</div>";
        
        echo "<a href='pages/login.php?superadmin=1' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Se connecter maintenant</a>";
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>Erreur: " . $e->getMessage() . "</div>";
    }
} else {
    // Afficher un formulaire pour confirmer la création
    echo "<h1>Création d'un super administrateur</h1>";
    echo "<p>Cliquez sur le bouton ci-dessous pour créer un super administrateur avec les identifiants suivants:</p>";
    echo "<ul>";
    echo "<li><strong>Nom d'utilisateur:</strong> superadmin</li>";
    echo "<li><strong>Mot de passe:</strong> SuperAdmin2024!</li>";
    echo "</ul>";
    
    echo "<form method='post' action=''>";
    echo "<button type='submit' name='create' value='1' style='padding: 10px; background-color: blue; color: white;'>Créer le super administrateur</button>";
    echo "</form>";
    echo "<p><a href='debug_superadmin.php'>Retour à la page de diagnostic</a></p>";
}
?> 