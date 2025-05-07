<?php
// Configuration de débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Inclure les fichiers nécessaires
require_once('config/database.php');

// Définir l'ID de réparation à vérifier
$repair_id = isset($_GET['id']) ? (int)$_GET['id'] : 738;

// En-tête HTML
echo "<html><head><title>Vérification de réparation</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #333; }
    .success { color: green; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .container { margin-bottom: 30px; }
</style>";
echo "</head><body>";
echo "<h1>Vérification de la réparation #$repair_id</h1>";

echo "<div class='container'>";
echo "<h2>Informations de session</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

// 1. Vérifier dans la base principale
echo "<div class='container'>";
echo "<h2>1. Vérification dans la base principale</h2>";

try {
    $main_pdo = getMainDBConnection();
    
    if (!$main_pdo) {
        echo "<p class='error'>Impossible de se connecter à la base principale</p>";
    } else {
        // Obtenir le nom de la base
        $db_name_stmt = $main_pdo->query("SELECT DATABASE() AS db_name");
        $db_info = $db_name_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Base de données: <strong>" . $db_info['db_name'] . "</strong></p>";
        
        // Vérifier si la table existe
        $check_table = $main_pdo->query("SHOW TABLES LIKE 'reparations'");
        if ($check_table->rowCount() > 0) {
            echo "<p class='success'>La table 'reparations' existe dans la base principale</p>";
            
            // Vérifier si la réparation existe
            $check_repair = $main_pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE id = ?");
            $check_repair->execute([$repair_id]);
            $exists = $check_repair->fetch(PDO::FETCH_ASSOC);
            
            if ($exists['count'] > 0) {
                echo "<p class='success'>La réparation #$repair_id existe dans la base principale!</p>";
                
                // Récupérer les détails
                $details = $main_pdo->prepare("SELECT * FROM reparations WHERE id = ?");
                $details->execute([$repair_id]);
                $repair_data = $details->fetch(PDO::FETCH_ASSOC);
                
                echo "<h3>Détails de la réparation (base principale)</h3>";
                echo "<table>";
                foreach ($repair_data as $key => $value) {
                    echo "<tr><td>$key</td><td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>La réparation #$repair_id n'existe PAS dans la base principale</p>";
            }
        } else {
            echo "<p class='error'>La table 'reparations' n'existe pas dans la base principale</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
}

echo "</div>";

// 2. Vérifier dans la base du magasin
echo "<div class='container'>";
echo "<h2>2. Vérification dans la base du magasin</h2>";

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo "<p class='error'>Impossible de se connecter à la base du magasin</p>";
    } else {
        // Obtenir le nom de la base
        $db_name_stmt = $shop_pdo->query("SELECT DATABASE() AS db_name");
        $db_info = $db_name_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Base de données: <strong>" . $db_info['db_name'] . "</strong></p>";
        
        // Vérifier si c'est la même que la base principale
        if (isset($main_pdo) && $db_info['db_name'] === $db_info['db_name']) {
            echo "<p class='error'>ATTENTION: getShopDBConnection() retourne la même base que getMainDBConnection()</p>";
        }
        
        // Vérifier si la table existe
        $check_table = $shop_pdo->query("SHOW TABLES LIKE 'reparations'");
        if ($check_table->rowCount() > 0) {
            echo "<p class='success'>La table 'reparations' existe dans la base du magasin</p>";
            
            // Vérifier si la réparation existe
            $check_repair = $shop_pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE id = ?");
            $check_repair->execute([$repair_id]);
            $exists = $check_repair->fetch(PDO::FETCH_ASSOC);
            
            if ($exists['count'] > 0) {
                echo "<p class='success'>La réparation #$repair_id existe dans la base du magasin!</p>";
                
                // Récupérer les détails
                $details = $shop_pdo->prepare("SELECT * FROM reparations WHERE id = ?");
                $details->execute([$repair_id]);
                $repair_data = $details->fetch(PDO::FETCH_ASSOC);
                
                echo "<h3>Détails de la réparation (base du magasin)</h3>";
                echo "<table>";
                foreach ($repair_data as $key => $value) {
                    echo "<tr><td>$key</td><td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>La réparation #$repair_id n'existe PAS dans la base du magasin</p>";
                
                // Trouver les IDs proches
                $neighbors = $shop_pdo->query("SELECT id FROM reparations ORDER BY id DESC LIMIT 10");
                $ids = $neighbors->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($ids) > 0) {
                    echo "<p>Derniers IDs de réparations disponibles: " . implode(", ", $ids) . "</p>";
                }
            }
        } else {
            echo "<p class='error'>La table 'reparations' n'existe pas dans la base du magasin</p>";
            
            // Lister les tables disponibles
            $tables = $shop_pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Tables disponibles: " . implode(", ", $tables) . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
}

echo "</div>";

// 3. Conclusion
echo "<div class='container'>";
echo "<h2>3. Diagnostic</h2>";

echo "<h3>Problème</h3>";
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    echo "<p class='error'>La session ne contient pas d'ID de magasin (shop_id). getShopDBConnection() utilise probablement la base principale par défaut.</p>";
    echo "<p class='warning'>Solution: Assurez-vous que l'utilisateur est connecté et a bien sélectionné un magasin.</p>";
} 
else if (isset($main_pdo) && isset($shop_pdo) && $db_info['db_name'] === $db_info['db_name']) {
    echo "<p class='error'>getShopDBConnection() retourne la base principale au lieu de la base du magasin spécifique.</p>";
    echo "<p class='warning'>Solution: Vérifiez les informations du magasin dans la table 'shops' de la base principale.</p>";
}
else if (isset($shop_pdo) && isset($check_table) && $check_table->rowCount() === 0) {
    echo "<p class='error'>La base de données du magasin ne contient pas la table 'reparations'.</p>";
    echo "<p class='warning'>Solution: Vérifiez la structure de la base de données du magasin.</p>";
}
else if (isset($exists) && $exists['count'] === 0) {
    echo "<p class='error'>La réparation #$repair_id n'existe pas dans la base sélectionnée.</p>";
    
    // Vérifier si elle existe dans la base principale
    if (isset($main_exists) && $main_exists['count'] > 0) {
        echo "<p class='warning'>La réparation existe dans la base principale mais pas dans celle du magasin.</p>";
        echo "<p>Solution: Les données doivent être synchronisées ou migrées de la base principale vers la base du magasin.</p>";
    } else {
        echo "<p class='warning'>La réparation #$repair_id n'existe ni dans la base principale ni dans celle du magasin.</p>";
        echo "<p>Solution: Vérifiez l'ID de la réparation ou si les données ont été supprimées.</p>";
    }
}
else {
    echo "<p class='success'>Aucun problème apparent avec la connexion à la base de données.</p>";
}

echo "</div>";

echo "</body></html>";
?> 