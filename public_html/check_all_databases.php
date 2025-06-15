<?php
/**
 * 🔍 Script pour vérifier toutes les bases de données et localiser les réparations
 */

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Vérification Toutes Bases</title></head><body>";
echo "<h1>🔍 Vérification de Toutes les Bases de Données</h1>";

// Récupérer tous les magasins
$main_pdo = getMainDBConnection();
$shops_stmt = $main_pdo->query("SELECT id, name, subdomain, db_name, db_host, db_user, db_pass FROM shops WHERE active = 1");
$shops = $shops_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>📊 Résultats par Base de Données</h2>";

foreach ($shops as $shop) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 15px; background-color: #f9f9f9;'>";
    echo "<h3>🏪 {$shop['name']} (ID: {$shop['id']})</h3>";
    echo "<p><strong>Base:</strong> {$shop['db_name']}</p>";
    
    try {
        // Connexion directe à cette base
        $dsn = "mysql:host={$shop['db_host']};dbname={$shop['db_name']};charset=utf8mb4";
        $test_pdo = new PDO($dsn, $shop['db_user'], $shop['db_pass']);
        $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p style='color:green;'>✅ Connexion réussie</p>";
        
        // Vérifier la table reparations
        $tables_stmt = $test_pdo->query("SHOW TABLES LIKE 'reparations'");
        $has_reparations_table = $tables_stmt->rowCount() > 0;
        
        if ($has_reparations_table) {
            echo "<p style='color:green;'>✅ Table 'reparations' existe</p>";
            
            // Compter les réparations
            $count_stmt = $test_pdo->query("SELECT COUNT(*) as total FROM reparations");
            $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $total_reparations = $count_result['total'];
            
            echo "<p><strong>Nombre de réparations:</strong> ";
            if ($total_reparations > 0) {
                echo "<span style='color:green; font-size:18px; font-weight:bold;'>🎯 {$total_reparations}</span>";
                
                // Afficher quelques détails des réparations récentes
                $recent_stmt = $test_pdo->query("
                    SELECT id, date_reception, type_appareil, statut, 
                           (SELECT nom FROM clients WHERE id = reparations.client_id) as client_nom
                    FROM reparations 
                    ORDER BY date_reception DESC 
                    LIMIT 3
                ");
                $recent_repairs = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h4>📋 Réparations Récentes :</h4>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr style='background-color: #e9e9e9;'><th>ID</th><th>Date</th><th>Client</th><th>Appareil</th><th>Statut</th></tr>";
                foreach ($recent_repairs as $repair) {
                    echo "<tr>";
                    echo "<td>{$repair['id']}</td>";
                    echo "<td>{$repair['date_reception']}</td>";
                    echo "<td>{$repair['client_nom']}</td>";
                    echo "<td>{$repair['type_appareil']}</td>";
                    echo "<td>{$repair['statut']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<p style='background-color: #d4edda; padding: 10px; border-left: 4px solid #28a745;'>";
                echo "🚀 <strong>TROUVÉ !</strong> Cette base contient vos réparations !<br/>";
                echo "Pour utiliser cette base, vous devez vous connecter au magasin <strong>{$shop['name']}</strong>";
                echo "</p>";
                
            } else {
                echo "<span style='color:orange;'>⚠️ 0 (vide)</span>";
            }
            echo "</p>";
            
        } else {
            echo "<p style='color:red;'>❌ Table 'reparations' n'existe pas</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Erreur de connexion: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

echo "<h2>🔧 Solutions Recommandées</h2>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>Si vous avez trouvé vos réparations dans une autre base :</h4>";
echo "<ol>";
echo "<li><strong>Option 1:</strong> Changer de magasin actuel via l'interface</li>";
echo "<li><strong>Option 2:</strong> Migrer les données vers la base actuelle</li>";
echo "<li><strong>Option 3:</strong> Corriger la configuration du magasin dans la table 'shops'</li>";
echo "</ol>";

echo "<h4>Si aucune base ne contient de réparations :</h4>";
echo "<ul>";
echo "<li>Les données ont peut-être été supprimées accidentellement</li>";
echo "<li>Vérifiez vos sauvegardes de base de données</li>";
echo "<li>Commencez par ajouter de nouvelles réparations pour tester</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='debug_reparations.php' style='color: blue;'>🔙 Retour au diagnostic</a> | ";
echo "<a href='pages/reparations.php' style='color: green;'>📋 Tester la page réparations</a></p>";

echo "</body></html>";
?> 