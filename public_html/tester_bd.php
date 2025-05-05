<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de base de données
require_once 'config/database.php';

// Définir les styles CSS
echo "
<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
    h1 { color: #2c3e50; }
    h2 { color: #3498db; margin-top: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .actions { margin-top: 30px; }
    .btn { 
        display: inline-block; 
        padding: 10px 15px; 
        background-color: #3498db; 
        color: white; 
        text-decoration: none; 
        border-radius: 4px;
        margin-right: 10px;
    }
    .btn:hover { background-color: #2980b9; }
</style>
";

echo "<h1>Test des connexions aux bases de données</h1>";

// Afficher les informations de session
echo "<h2>Informations de session</h2>";
echo "<table><tr><th>Clé</th><th>Valeur</th></tr>";
foreach ($_SESSION as $key => $value) {
    // Ne pas afficher de mots de passe ou informations sensibles
    if (strpos(strtolower($key), 'pass') !== false || strpos(strtolower($key), 'token') !== false) {
        $display_value = '********';
    } else if (is_array($value) || is_object($value)) {
        $display_value = json_encode($value);
    } else {
        $display_value = htmlspecialchars($value);
    }
    echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . $display_value . "</td></tr>";
}
echo "</table>";

// Fonction pour afficher les informations d'une connexion BD
function testDatabase($pdo, $title) {
    echo "<h2>$title</h2>";
    
    try {
        // Test de la connexion
        echo "<table>";
        echo "<tr><td>État de la connexion</td><td>";
        if ($pdo instanceof PDO) {
            echo "<span class='success'>OK</span>";
        } else {
            echo "<span class='error'>ÉCHEC</span>";
            echo "</td></tr></table>";
            return;
        }
        echo "</td></tr>";
        
        // Obtenir le nom de la base de données active
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<tr><td>Base de données active</td><td>" . htmlspecialchars($result['db_name']) . "</td></tr>";
        
        // Version MySQL
        $stmt = $pdo->query("SELECT version() as version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<tr><td>Version MySQL</td><td>" . htmlspecialchars($result['version']) . "</td></tr>";
        
        // Vérification de l'existence de la table clients
        $stmt = $pdo->query("SHOW TABLES LIKE 'clients'");
        $table_exists = $stmt->rowCount() > 0;
        echo "<tr><td>Table 'clients' existe</td><td>" . ($table_exists ? "<span class='success'>OUI</span>" : "<span class='error'>NON</span>") . "</td></tr>";
        
        if ($table_exists) {
            // Nombre de clients
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<tr><td>Nombre de clients</td><td>" . htmlspecialchars($result['count']) . "</td></tr>";
            
            // Exemples de clients
            echo "<tr><td>Exemples de clients</td><td>";
            $stmt = $pdo->query("SELECT id, firstname, lastname FROM clients ORDER BY id DESC LIMIT 5");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "ID: " . htmlspecialchars($row['id']) . " - " . 
                     htmlspecialchars($row['firstname']) . " " . 
                     htmlspecialchars($row['lastname']) . "<br>";
            }
            echo "</td></tr>";
        }
        
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "<tr><td>Erreur</td><td><span class='error'>" . htmlspecialchars($e->getMessage()) . "</span></td></tr>";
        echo "</table>";
    }
}

// Tester la connexion à la base de données principale
$main_pdo = getMainDBConnection();
testDatabase($main_pdo, "Connexion à la base de données principale");

// Tester la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();
testDatabase($shop_pdo, "Connexion à la base de données du magasin");

// Test après forçage de la connexion au magasin
echo "<h2>Test après forçage de la connexion au magasin</h2>";

if (!isset($_SESSION['shop_id'])) {
    echo "<p class='warning'>Aucun magasin sélectionné en session</p>";
} else {
    $shop_id = $_SESSION['shop_id'];
    
    // Récupérer les infos du magasin depuis la base principale
    try {
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch();
        
        if ($shop) {
            echo "<p>Magasin en session : <b>" . htmlspecialchars($shop['name']) . "</b> (ID: " . htmlspecialchars($shop_id) . ")</p>";
            echo "<p>Base de données configurée : <b>" . htmlspecialchars($shop['db_name']) . "</b></p>";
            
            // Forcer la création d'une nouvelle connexion
            $shop_pdo = null; // Réinitialiser la connexion
            
            $shop_config = [
                'host' => $shop['db_host'],
                'port' => $shop['db_port'],
                'user' => $shop['db_user'],
                'pass' => $shop['db_pass'],
                'dbname' => $shop['db_name']
            ];
            
            // Connexion directe en utilisant les infos du magasin
            try {
                $dsn = "mysql:host=" . $shop_config['host'] . ";port=" . $shop_config['port'] . ";dbname=" . $shop_config['dbname'] . ";charset=utf8mb4";
                $forced_pdo = new PDO(
                    $dsn,
                    $shop_config['user'],
                    $shop_config['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                
                // Forcer le changement de base de données si nécessaire
                $forced_pdo->exec("USE `" . $shop_config['dbname'] . "`");
                
                testDatabase($forced_pdo, "Connexion forcée au magasin");
                
            } catch (PDOException $e) {
                echo "<p class='error'>Erreur lors de la connexion forcée : " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='error'>Impossible de trouver le magasin avec l'ID " . htmlspecialchars($shop_id) . " dans la base de données.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Erreur lors de la récupération des informations du magasin : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Actions
echo "<div class='actions'>";
echo "<h2>Actions</h2>";
echo "<p><a href='?force_reconnect=1' class='btn'>Forcer la reconnexion</a></p>";
echo "</div>";

// Si l'action de forcer la reconnexion est demandée
if (isset($_GET['force_reconnect']) && $_GET['force_reconnect'] == 1) {
    // Réinitialiser les connexions PDO
    $main_pdo = null;
    $shop_pdo = null;
    
    // Rediriger vers la même page sans le paramètre
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
?>
