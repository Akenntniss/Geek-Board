<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once 'config/database.php';

echo "<h1>Test d'authentification Superadmin</h1>";

// Utiliser un mot de passe simple pour le test
$test_password = 'Admin123!';

try {
    // Se connecter à la base de données principale
    $pdo = getMainDBConnection();
    echo "<p>✓ Connexion à la base de données réussie</p>";
    
    // 1. Vérifier si nous pouvons accéder à la table superadmins
    echo "<h2>1. Vérification de l'accès à la table superadmins</h2>";
    $superadmin_data = $pdo->query("SELECT * FROM superadmins LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($superadmin_data) {
        echo "<p style='color:green'>✓ Accès à la table superadmins réussi</p>";
        echo "<pre>" . print_r($superadmin_data, true) . "</pre>";
    } else {
        echo "<p style='color:red'>⚠ Aucun superadmin trouvé dans la table!</p>";
    }
    
    // 2. Mettre à jour le mot de passe et tester l'authentification
    echo "<h2>2. Test d'authentification avec mot de passe simple</h2>";
    
    // Mettre à jour le mot de passe de superadmin à "Admin123!"
    $new_password = password_hash($test_password, PASSWORD_DEFAULT);
    echo "<p>Nouveau mot de passe hashé: " . $new_password . "</p>";
    
    $update = $pdo->prepare("UPDATE superadmins SET password = ? WHERE username = 'superadmin'");
    if ($update->execute([$new_password])) {
        echo "<p style='color:green'>✓ Mot de passe mis à jour avec succès</p>";
    } else {
        echo "<p style='color:red'>⚠ Échec de la mise à jour du mot de passe</p>";
    }
    
    // Tester l'authentification
    $stmt = $pdo->prepare("SELECT * FROM superadmins WHERE username = ? AND active = 1");
    $stmt->execute(['superadmin']);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "<p style='color:red'>⚠ Aucun superadmin trouvé avec le nom d'utilisateur 'superadmin'</p>";
    } else {
        echo "<p>Mot de passe stocké: " . $admin['password'] . "</p>";
        
        // Tester avec password_verify
        if (password_verify($test_password, $admin['password'])) {
            echo "<p style='color:green'>✓ Authentification réussie avec password_verify()</p>";
        } else {
            echo "<p style='color:red'>⚠ Échec de l'authentification avec password_verify()</p>";
            
            // Tester la compatibilité du hash
            echo "<h3>Vérification approfondie du hachage</h3>";
            echo "<p>PHP version: " . PHP_VERSION . "</p>";
            echo "<p>Password hash info: " . password_get_info($admin['password'])['algoName'] . "</p>";
            
            // Tester avec == pour voir si le problème est lié à timing attack protection
            if ($admin['password'] == crypt($test_password, $admin['password'])) {
                echo "<p style='color:green'>✓ Authentification réussie avec crypt() direct</p>";
            } else {
                echo "<p style='color:red'>⚠ Échec de l'authentification avec crypt() direct</p>";
            }
        }
    }
    
    echo "<h2>3. Lien pour tester la connexion</h2>";
    echo "<p>Utilisez ces identifiants pour vous connecter:</p>";
    echo "<ul>";
    echo "<li>Nom d'utilisateur: <strong>superadmin</strong></li>";
    echo "<li>Mot de passe: <strong>Admin123!</strong></li>";
    echo "</ul>";
    
    echo "<p><a href='pages/login.php?superadmin=1' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Se connecter maintenant</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Erreur: " . $e->getMessage() . "</p>";
}
?> 