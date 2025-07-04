<?php
// Configuration de débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Inclure les fichiers nécessaires
require_once('config/database.php');

// En-tête HTML
echo "<html><head><title>Diagnostic Réparations GeekBoard</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #333; }
    .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";
echo "</head><body>";
echo "<h1>Diagnostic du système de réparations</h1>";

// 1. Vérification de la session
echo "<div class='section'>";
echo "<h2>1. Informations de session</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Valeur</th></tr>";
echo "<tr><td>Session ID</td><td>" . session_id() . "</td></tr>";
echo "<tr><td>shop_id</td><td>" . ($_SESSION['shop_id'] ?? '<span class="error">Non défini</span>') . "</td></tr>";
echo "<tr><td>user_id</td><td>" . ($_SESSION['user_id'] ?? '<span class="warning">Non défini</span>') . "</td></tr>";
echo "<tr><td>magasin_nom</td><td>" . ($_SESSION['magasin_nom'] ?? '<span class="warning">Non défini</span>') . "</td></tr>";
echo "<tr><td>superadmin_mode</td><td>" . (isset($_SESSION['superadmin_mode']) && $_SESSION['superadmin_mode'] ? 'Activé' : 'Désactivé') . "</td></tr>";
echo "</table>";
echo "</div>";

// 2. Connexion à la base de données
echo "<div class='section'>";
echo "<h2>2. Test de connexion aux bases de données</h2>";

// Test de la connexion principale
echo "<h3>Base de données principale</h3>";
try {
    $main_pdo = getMainDBConnection();
    if ($main_pdo) {
        $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='success'>Connexion réussie à la base principale: " . $result['db_name'] . "</p>";
        
        // Liste des magasins disponibles dans la base principale
        $shops_stmt = $main_pdo->query("SELECT id, nom, db_host, db_name FROM shops");
        if ($shops_stmt) {
            $shops = $shops_stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>Magasins configurés dans la base principale</h4>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nom</th><th>Hôte DB</th><th>Nom DB</th></tr>";
            foreach ($shops as $shop) {
                echo "<tr>";
                echo "<td>" . $shop['id'] . "</td>";
                echo "<td>" . $shop['nom'] . "</td>";
                echo "<td>" . $shop['db_host'] . "</td>";
                echo "<td>" . $shop['db_name'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>Échec de connexion à la base principale</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Erreur lors de la connexion à la base principale: " . $e->getMessage() . "</p>";
}

// Test de la connexion au magasin
echo "<h3>Base de données du magasin</h3>";
try {
    $shop_pdo = getShopDBConnection();
    if ($shop_pdo) {
        $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='success'>Connexion réussie à la base du magasin: " . $result['db_name'] . "</p>";
        
        // Vérifier l'existence de la table reparations
        $tables_stmt = $shop_pdo->query("SHOW TABLES LIKE 'reparations'");
        if ($tables_stmt->rowCount() > 0) {
            echo "<p class='success'>La table 'reparations' existe dans cette base de données</p>";
            
            // Compter les réparations
            $count_stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations");
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Nombre total de réparations: " . $count['total'] . "</p>";
            
            // Lister les 5 dernières réparations
            echo "<h4>5 dernières réparations</h4>";
            $recent_stmt = $shop_pdo->query("SELECT id, client_id, type_appareil, marque, modele, date_reception, statut FROM reparations ORDER BY id DESC LIMIT 5");
            $repairs = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($repairs) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Client ID</th><th>Type</th><th>Marque</th><th>Modèle</th><th>Date Réception</th><th>Statut</th></tr>";
                foreach ($repairs as $repair) {
                    echo "<tr>";
                    echo "<td>" . $repair['id'] . "</td>";
                    echo "<td>" . $repair['client_id'] . "</td>";
                    echo "<td>" . $repair['type_appareil'] . "</td>";
                    echo "<td>" . $repair['marque'] . "</td>";
                    echo "<td>" . $repair['modele'] . "</td>";
                    echo "<td>" . $repair['date_reception'] . "</td>";
                    echo "<td>" . $repair['statut'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>Aucune réparation trouvée</p>";
            }
        } else {
            echo "<p class='error'>La table 'reparations' n'existe PAS dans cette base de données</p>";
            
            // Lister toutes les tables
            $all_tables_stmt = $shop_pdo->query("SHOW TABLES");
            $all_tables = $all_tables_stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Tables disponibles: " . implode(", ", $all_tables) . "</p>";
        }
    } else {
        echo "<p class='error'>Échec de connexion à la base du magasin</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Erreur lors de la connexion à la base du magasin: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 3. Test spécifique pour l'ID de réparation mentionné
echo "<div class='section'>";
echo "<h2>3. Test de la réparation problématique</h2>";

// Test avec l'ID 738 mentionné dans l'erreur
$problem_id = 738;
echo "<p>Test pour la réparation ID: " . $problem_id . "</p>";

try {
    if ($shop_pdo) {
        // Vérifier si la réparation existe
        $check_stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE id = ?");
        $check_stmt->execute([$problem_id]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists['count'] > 0) {
            echo "<p class='success'>La réparation avec ID " . $problem_id . " existe dans la base de données</p>";
            
            // Tenter de récupérer les détails
            $details_stmt = $shop_pdo->prepare("
                SELECT 
                    r.*, 
                    c.nom as client_nom, 
                    c.prenom as client_prenom
                FROM reparations r
                LEFT JOIN clients c ON r.client_id = c.id
                WHERE r.id = ?
            ");
            $details_stmt->execute([$problem_id]);
            $repair = $details_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($repair) {
                echo "<h4>Détails de la réparation " . $problem_id . "</h4>";
                echo "<table>";
                foreach ($repair as $key => $value) {
                    echo "<tr><td>" . $key . "</td><td>" . (is_null($value) ? "<em>NULL</em>" : $value) . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>Impossible de récupérer les détails de la réparation (jointure clients problématique?)</p>";
                
                // Tenter sans la jointure
                $simple_stmt = $shop_pdo->prepare("SELECT * FROM reparations WHERE id = ?");
                $simple_stmt->execute([$problem_id]);
                $simple_repair = $simple_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($simple_repair) {
                    echo "<p class='warning'>Détails récupérés sans jointure clients:</p>";
                    echo "<table>";
                    foreach ($simple_repair as $key => $value) {
                        echo "<tr><td>" . $key . "</td><td>" . (is_null($value) ? "<em>NULL</em>" : $value) . "</td></tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='error'>Impossible de récupérer les détails même sans jointure!</p>";
                }
            }
        } else {
            echo "<p class='error'>La réparation avec ID " . $problem_id . " n'existe PAS dans la base de données</p>";
            
            // Vérifier les IDs proches
            $neighbors_stmt = $shop_pdo->query("SELECT id FROM reparations WHERE id BETWEEN " . ($problem_id - 5) . " AND " . ($problem_id + 5) . " ORDER BY id");
            $neighbors = $neighbors_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($neighbors) > 0) {
                echo "<p>Réparations avec IDs proches: " . implode(", ", $neighbors) . "</p>";
            } else {
                echo "<p class='warning'>Aucune réparation trouvée avec des IDs proches</p>";
            }
        }
    } else {
        echo "<p class='error'>Impossible de tester la réparation car pas de connexion à la base de données</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Erreur lors du test de la réparation: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. Instructions et diagnostics
echo "<div class='section'>";
echo "<h2>4. Diagnostics et solutions possibles</h2>";

// Résumé des problèmes détectés
echo "<h3>Problèmes détectés</h3>";
echo "<ul>";
if (!isset($_SESSION['shop_id'])) {
    echo "<li class='error'>Aucun shop_id en session. La connexion à la base de données du magasin ne peut pas fonctionner correctement.</li>";
}

if (isset($shop_pdo) && $shop_pdo && isset($result['db_name'])) {
            $is_main_db = (strpos($result['db_name'], 'geekboard_') === 0);
    if ($is_main_db) {
        echo "<li class='error'>getShopDBConnection() retourne la base principale au lieu de la base du magasin.</li>";
    }
} else {
    echo "<li class='error'>Impossible de se connecter à la base de données du magasin.</li>";
}

if (isset($tables_stmt) && $tables_stmt->rowCount() == 0) {
    echo "<li class='error'>La table 'reparations' n'existe pas dans la base de données connectée.</li>";
}

if (isset($exists) && $exists['count'] == 0) {
    echo "<li class='error'>La réparation avec ID " . $problem_id . " n'existe pas dans la base de données.</li>";
}
echo "</ul>";

// Solutions proposées
echo "<h3>Solutions possibles</h3>";
echo "<ol>";
if (!isset($_SESSION['shop_id'])) {
    echo "<li>Assurez-vous que l'utilisateur est connecté et a sélectionné un magasin.</li>";
    echo "<li>Vérifiez le processus de sélection du magasin et l'initialisation de la session.</li>";
}

if (isset($shop_pdo) && $shop_pdo && isset($result['db_name']) && $is_main_db) {
    echo "<li>Vérifiez la fonction getShopDBConnection() pour vous assurer qu'elle n'utilise pas la base principale par défaut.</li>";
    echo "<li>Assurez-vous que la table shops dans la base principale contient les bonnes informations de connexion pour ce magasin.</li>";
}

if (isset($tables_stmt) && $tables_stmt->rowCount() == 0) {
    echo "<li>Vérifiez que la structure de la base de données du magasin est correcte et contient toutes les tables nécessaires.</li>";
    echo "<li>Exécutez les scripts de migration/création de tables si nécessaire.</li>";
}

if (isset($exists) && $exists['count'] == 0) {
    echo "<li>Vérifiez si la réparation existe dans une autre base de données (peut-être la principale).</li>";
    echo "<li>Assurez-vous que les données du magasin sont correctement synchronisées.</li>";
}
echo "</ol>";
echo "</div>";

echo "</body></html>";
?> 