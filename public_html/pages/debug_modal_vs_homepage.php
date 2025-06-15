<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Diagnostic Modal vs Page d'accueil</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { background: #e3f2fd; padding: 10px; border-left: 4px solid #2196f3; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .test-button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-button:hover { background: #0056b3; }
    </style>
</head>
<body>";

echo "<h1>🔍 Diagnostic: Modal vs Page d'accueil - Connexions Database</h1>";

function displayInfo($label, $value, $isError = false) {
    $class = $isError ? 'error' : 'success';
    echo "<tr><td><strong>$label</strong></td><td class='$class'>$value</td></tr>";
}

// Inclure la configuration
require_once 'config/database.php';

echo "<div class='section'>
    <h2>📋 Informations de Session</h2>
    <table>";

displayInfo("Session active", session_status() === PHP_SESSION_ACTIVE ? "Oui" : "Non");
displayInfo("User ID", $_SESSION['user_id'] ?? 'Non défini', !isset($_SESSION['user_id']));
displayInfo("Shop ID", $_SESSION['shop_id'] ?? 'Non défini', !isset($_SESSION['shop_id']));
displayInfo("Shop Name", $_SESSION['shop_name'] ?? 'Non défini');

echo "</table></div>";

// Test des connexions
echo "<div class='section'>
    <h2>🔗 Test des Connexions Database</h2>
    <table>";

// 1. Test connexion principale
try {
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    displayInfo("Connexion principale", "✅ Connecté à: " . $result['db_name']);
} catch (Exception $e) {
    displayInfo("Connexion principale", "❌ Échec: " . $e->getMessage(), true);
}

// 2. Test connexion magasin via getShopDBConnection()
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    $shop_db_name = $result['db_name'];
    displayInfo("Connexion magasin (getShopDBConnection)", "✅ Connecté à: " . $shop_db_name);
    
    // Compter les clients dans cette base
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM clients");
    $stmt->execute();
    $client_count = $stmt->fetch()['count'];
    displayInfo("Nombre de clients", $client_count);
    
} catch (Exception $e) {
    displayInfo("Connexion magasin", "❌ Échec: " . $e->getMessage(), true);
}

echo "</table></div>";

// Test simulation appel AJAX modal
if (isset($_SESSION['shop_id']) && isset($_SESSION['user_id'])) {
    echo "<div class='section'>
        <h2>🧪 Simulation Appel Modal</h2>
        <div class='info'>
            <p><strong>Test:</strong> Simulation de l'appel AJAX get_client_details.php</p>
        </div>";
    
    try {
        // Simuler l'inclusion exacte du fichier modal
        $_POST['client_id'] = 1; // ID de test
        
        echo "<p><strong>Simulation avec client_id = 1</strong></p>";
        
        // Reproduire exactement la logique de get_client_details.php
        $config_path = realpath(__DIR__ . '/config/database.php');
        require_once $config_path;
        $pdo = getShopDBConnection();
        
        $db_stmt = $pdo->query("SELECT DATABASE() as current_db");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        $dbname = $db_info['current_db'] ?? 'Inconnue';
        
        echo "<table>";
        displayInfo("Base utilisée par modal", $dbname);
        
        // Tester si client existe
        $stmt = $pdo->prepare("SELECT id, nom, prenom FROM clients WHERE id = ?");
        $stmt->execute([1]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            displayInfo("Client ID=1 trouvé", $client['nom'] . " " . $client['prenom']);
        } else {
            displayInfo("Client ID=1", "Non trouvé dans cette base", true);
        }
        
        echo "</table>";
        
        unset($_POST['client_id']); // Nettoyer
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur lors de la simulation: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// Test simulation page d'accueil
echo "<div class='section'>
    <h2>🏠 Simulation Page d'Accueil</h2>
    <div class='info'>
        <p><strong>Test:</strong> Simulation de l'appel AJAX recherche_universelle.php</p>
    </div>";

try {
    // Simuler recherche_universelle.php
    $_POST['terme'] = 'test';
    
    $config_path = realpath(__DIR__ . '/config/database.php');
    require_once $config_path;
    $pdo = getShopDBConnection();
    
    $db_stmt = $pdo->query("SELECT DATABASE() as current_db");
    $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
    $dbname = $db_info['current_db'] ?? 'Inconnue';
    
    echo "<table>";
    displayInfo("Base utilisée par recherche", $dbname);
    
    // Compter les clients
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM clients");
    $stmt->execute();
    $client_count = $stmt->fetch()['count'];
    displayInfo("Clients trouvés", $client_count);
    
    echo "</table>";
    
    unset($_POST['terme']);
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de la simulation recherche: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Recommandations
echo "<div class='section'>
    <h2>💡 Tests à Effectuer</h2>
    <div class='info'>
        <p><strong>Étapes de diagnostic:</strong></p>
        <ol>
            <li>Ouvrez la page d'accueil et effectuez une recherche</li>
            <li>Notez quelle base de données s'affiche dans les logs</li>
            <li>Ouvrez la modal d'un client</li>
            <li>Vérifiez les logs pour voir quelle base est utilisée</li>
            <li>Comparez les deux résultats</li>
        </ol>
    </div>
    
    <h3>🔍 Commandes de Vérification</h3>
    <pre>
# Vérifier les logs en temps réel:
tail -f /path/to/error.log | grep \"DEBUG\"

# Ou dans le navigateur, ouvrir les outils de développement
# et regarder l'onglet Console pour les erreurs AJAX
    </pre>
    
    <h3>📊 Points de Contrôle</h3>
    <table>
        <tr><th>Composant</th><th>Base Attendue</th><th>Base Réelle</th></tr>
        <tr><td>Page d'accueil (recherche)</td><td>Base du magasin</td><td><em>À vérifier</em></td></tr>
        <tr><td>Modal client</td><td>Base du magasin</td><td><em>À vérifier</em></td></tr>
    </table>
</div>";

echo "</body></html>";
?> 