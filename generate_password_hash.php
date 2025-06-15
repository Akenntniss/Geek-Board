<?php
/**
 * Utilitaire pour générer des hashs de mots de passe pour GeekBoard
 */

// Fonction pour générer le hash d'un mot de passe
function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fonction pour vérifier un mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Mots de passe à hasher
$passwords = [
    'Admin123!',
    'password',
    'superadmin123',
    'GeekBoard2024!'
];

echo "<!DOCTYPE html>\n";
echo "<html lang='fr'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>Générateur de Hash - GeekBoard</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }\n";
echo "        .hash-result { background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; margin: 10px 0; }\n";
echo "        .password { font-weight: bold; color: #007bff; }\n";
echo "        .hash { font-family: monospace; word-break: break-all; color: #28a745; }\n";
echo "        .sql { background: #e9ecef; padding: 10px; border-radius: 3px; font-family: monospace; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h1>🔐 Générateur de Hash de Mots de Passe - GeekBoard</h1>\n";

foreach ($passwords as $password) {
    $hash = generatePasswordHash($password);
    
    echo "<div class='hash-result'>\n";
    echo "<h3>Mot de passe : <span class='password'>{$password}</span></h3>\n";
    echo "<p><strong>Hash :</strong><br>\n";
    echo "<span class='hash'>{$hash}</span></p>\n";
    
    // Générer la requête SQL
    echo "<h4>Requête SQL :</h4>\n";
    echo "<div class='sql'>\n";
    echo "INSERT INTO superadmins (username, password, full_name, email, active)<br>\n";
    echo "VALUES ('superadmin', '{$hash}', 'Super Administrateur', 'admin@geekboard.fr', 1);\n";
    echo "</div>\n";
    
    // Test de vérification
    $verification = verifyPassword($password, $hash);
    echo "<p><strong>Vérification :</strong> " . ($verification ? "✅ Valide" : "❌ Erreur") . "</p>\n";
    echo "</div>\n";
}

// Section pour tester un mot de passe personnalisé
if (isset($_POST['custom_password']) && !empty($_POST['custom_password'])) {
    $custom_password = $_POST['custom_password'];
    $custom_hash = generatePasswordHash($custom_password);
    
    echo "<div class='hash-result' style='border-color: #ffc107;'>\n";
    echo "<h3>Mot de passe personnalisé : <span class='password'>{$custom_password}</span></h3>\n";
    echo "<p><strong>Hash :</strong><br>\n";
    echo "<span class='hash'>{$custom_hash}</span></p>\n";
    
    echo "<h4>Requête SQL :</h4>\n";
    echo "<div class='sql'>\n";
    echo "UPDATE superadmins SET password = '{$custom_hash}' WHERE username = 'superadmin';\n";
    echo "</div>\n";
    echo "</div>\n";
}

// Formulaire pour mot de passe personnalisé
echo "<form method='post' style='background: #e7f3ff; padding: 20px; border-radius: 5px; margin-top: 20px;'>\n";
echo "<h3>Générer un hash personnalisé :</h3>\n";
echo "<input type='text' name='custom_password' placeholder='Entrez votre mot de passe' style='width: 300px; padding: 8px;'>\n";
echo "<button type='submit' style='padding: 8px 15px; margin-left: 10px;'>Générer Hash</button>\n";
echo "</form>\n";

echo "<hr>\n";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>\n";
echo "<h4>⚠️ Instructions d'utilisation :</h4>\n";
echo "<ol>\n";
echo "<li>Choisissez un mot de passe dans la liste ci-dessus ou générez-en un personnalisé</li>\n";
echo "<li>Copiez le hash généré</li>\n";
echo "<li>Utilisez la requête SQL fournie pour insérer le superadmin dans votre base de données</li>\n";
echo "<li>Connectez-vous avec le nom d'utilisateur 'superadmin' et le mot de passe correspondant</li>\n";
echo "<li><strong>Supprimez ce fichier après utilisation pour la sécurité !</strong></li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 15px;'>\n";
echo "<h4>📋 Informations de connexion finale :</h4>\n";
echo "<ul>\n";
echo "<li><strong>URL :</strong> https://votre-domaine.com/superadmin/login.php</li>\n";
echo "<li><strong>Username :</strong> superadmin</li>\n";
echo "<li><strong>Password :</strong> [Le mot de passe que vous avez choisi]</li>\n";
echo "<li><strong>Base de données :</strong> 191.96.63.103 / u139954273_Vscodetest</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</body>\n";
echo "</html>\n";
?> 