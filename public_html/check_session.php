<?php
/**
 * Script de vérification de la session PHP
 * Ce script affiche des informations détaillées sur l'état de la session PHP actuelle
 */
session_start();

// Stocker un test dans la session pour vérifier la persistance
if (!isset($_SESSION['test_value'])) {
    $_SESSION['test_value'] = 'Valeur de test créée le ' . date('Y-m-d H:i:s');
}

// Fonction pour vérifier si un chemin est accessible en écriture
function checkWriteAccess($path) {
    if (is_dir($path)) {
        $testFile = $path . '/session_test_' . uniqid() . '.txt';
        $result = file_put_contents($testFile, 'test');
        if ($result !== false) {
            unlink($testFile);
            return true;
        }
    }
    return false;
}

// En-tête HTML
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de Session PHP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        pre { background: #f4f4f4; padding: 10px; overflow: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; width: 30%; }
        code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Vérification de Session PHP</h1>';

// Afficher les informations sur la session
echo '<div class="info">
    <h2>Informations de Session</h2>
    <table>
        <tr><th>ID de session</th><td>' . session_id() . '</td></tr>
        <tr><th>Nom de session</th><td>' . session_name() . '</td></tr>
        <tr><th>Statut de session</th><td>' . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . '</td></tr>
        <tr><th>Valeur de test</th><td>' . ($_SESSION['test_value'] ?? 'Non définie') . '</td></tr>
    </table>
</div>';

// Afficher les détails de la session
echo '<div class="info">
    <h2>Détails de la Session</h2>
    <table>';

// Cookie de session
$sessionCookie = isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : 'Non défini';
echo '<tr><th>Cookie de session</th><td>' . $sessionCookie . '</td></tr>';

// Paramètres du cookie
$cookieParams = session_get_cookie_params();
echo '<tr><th>Durée de vie du cookie</th><td>' . $cookieParams['lifetime'] . ' secondes</td></tr>';
echo '<tr><th>Chemin du cookie</th><td>' . $cookieParams['path'] . '</td></tr>';
echo '<tr><th>Domaine du cookie</th><td>' . ($cookieParams['domain'] ?: 'Non défini') . '</td></tr>';
echo '<tr><th>Secure</th><td>' . ($cookieParams['secure'] ? 'Oui' : 'Non') . '</td></tr>';
echo '<tr><th>HttpOnly</th><td>' . ($cookieParams['httponly'] ? 'Oui' : 'Non') . '</td></tr>';

// Tous les cookies
echo '<tr><th>Tous les cookies</th><td><pre>' . print_r($_COOKIE, true) . '</pre></td></tr>';

echo '</table>
</div>';

// Afficher la configuration PHP liée aux sessions
echo '<div class="info">
    <h2>Configuration PHP pour Sessions</h2>
    <table>';

$sessionSettings = [
    'session.save_path' => 'Chemin de stockage des sessions',
    'session.gc_maxlifetime' => 'Durée de vie maximale des sessions (secondes)',
    'session.cookie_lifetime' => 'Durée de vie du cookie de session (secondes)',
    'session.gc_probability' => 'Probabilité de nettoyage des sessions (numérateur)',
    'session.gc_divisor' => 'Probabilité de nettoyage des sessions (dénominateur)',
    'session.use_cookies' => 'Utilisation des cookies pour les sessions',
    'session.use_only_cookies' => 'Utilisation exclusive des cookies pour les sessions',
    'session.use_strict_mode' => 'Mode strict pour les sessions',
    'session.use_trans_sid' => 'Utilisation de l\'ID de session transparent',
    'session.cache_limiter' => 'Limiteur de cache pour les sessions',
    'session.cache_expire' => 'Expiration du cache (minutes)',
    'session.sid_length' => 'Longueur de l\'ID de session',
    'session.sid_bits_per_character' => 'Bits par caractère dans l\'ID de session'
];

foreach ($sessionSettings as $setting => $description) {
    $value = ini_get($setting);
    echo '<tr><th title="' . $description . '">' . $setting . '</th><td>' . $value . '</td></tr>';
}

echo '</table>
</div>';

// Vérifier les permissions du dossier de stockage des sessions
$sessionPath = ini_get('session.save_path');
echo '<div class="info">
    <h2>Vérification du Dossier de Sessions</h2>';

if (empty($sessionPath)) {
    echo '<p class="warning">Aucun chemin de stockage des sessions n\'est défini.</p>';
} else {
    echo '<p>Chemin de stockage des sessions: <code>' . $sessionPath . '</code></p>';
    
    // Vérifier si le dossier existe
    if (is_dir($sessionPath)) {
        echo '<p class="success">Le dossier existe.</p>';
        
        // Vérifier les permissions
        if (is_readable($sessionPath)) {
            echo '<p class="success">Le dossier est lisible.</p>';
        } else {
            echo '<p class="error">Le dossier n\'est pas lisible!</p>';
        }
        
        if (is_writable($sessionPath)) {
            echo '<p class="success">Le dossier est accessible en écriture.</p>';
        } else {
            echo '<p class="error">Le dossier n\'est pas accessible en écriture!</p>';
        }
        
        // Test d'écriture
        if (checkWriteAccess($sessionPath)) {
            echo '<p class="success">Test d\'écriture dans le dossier réussi.</p>';
        } else {
            echo '<p class="error">Test d\'écriture dans le dossier échoué!</p>';
        }
    } else {
        echo '<p class="error">Le dossier n\'existe pas ou n\'est pas accessible!</p>';
    }
}

echo '</div>';

// Contenu de $_SESSION
echo '<div class="info">
    <h2>Contenu de $_SESSION</h2>';

if (empty($_SESSION)) {
    echo '<p class="warning">La session est vide.</p>';
} else {
    echo '<pre>' . print_r($_SESSION, true) . '</pre>';
}

echo '</div>';

// Formulaire pour tester l'ajout de données en session
echo '<div class="info">
    <h2>Tester l\'Ajout de Données en Session</h2>
    <form method="post" action="">
        <label for="key">Clé:</label>
        <input type="text" name="key" id="key" required style="margin: 5px 0; padding: 5px;">
        <br>
        <label for="value">Valeur:</label>
        <input type="text" name="value" id="value" required style="margin: 5px 0; padding: 5px;">
        <br>
        <input type="submit" name="add_to_session" value="Ajouter à la session" style="margin-top: 10px; padding: 5px 10px;">
    </form>
</div>';

// Traitement du formulaire
if (isset($_POST['add_to_session']) && isset($_POST['key']) && isset($_POST['value'])) {
    $key = $_POST['key'];
    $value = $_POST['value'];
    $_SESSION[$key] = $value;
    
    echo '<div class="success">
        <p>Valeur ajoutée à la session. Rechargez la page pour voir le résultat.</p>
    </div>';
}

// Liens utiles
echo '<div class="info">
    <h2>Liens Utiles</h2>
    <ul>
        <li><a href="repair_auth_system.php">Réparation du Système d\'Authentification</a></li>
        <li><a href="debug_shop_connection.php">Diagnostic des Connexions aux Magasins</a></li>
        <li><a href="fix_user_shop_association.php">Réparer l\'Association Utilisateur-Magasin</a></li>
        <li><a href="index.php">Retour à l\'accueil</a></li>
    </ul>
</div>';

echo '</div></body></html>';
?> 