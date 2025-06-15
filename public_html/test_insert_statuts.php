<?php
/**
 * 🛠️ Script pour insérer des statuts de test dans la table statuts
 */

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>🛠️ Insertion Statuts de Test</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";
echo "</head>";
echo "<body>";
echo "<h2>🛠️ Insertion des Statuts de Test</h2>";

try {
    // 🗄️ Utiliser la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('❌ Erreur de connexion à la base de données du magasin');
    }
    
    echo "<p class='info'>✅ Connexion à la base de données établie</p>";
    echo "<p class='info'>📊 Shop ID: " . ($_SESSION['shop_id'] ?? 'Non défini') . "</p>";
    
    // 🔍 Vérifier si la table statuts existe
    $checkTableSQL = "SHOW TABLES LIKE 'statuts'";
    $checkTableStmt = $shop_pdo->prepare($checkTableSQL);
    $checkTableStmt->execute();
    $tableExists = $checkTableStmt->fetchColumn();
    
    if (!$tableExists) {
        echo "<p class='error'>❌ La table 'statuts' n'existe pas dans cette base de données</p>";
        exit;
    }
    
    echo "<p class='info'>✅ Table 'statuts' trouvée</p>";
    
    // 🔍 Vérifier la structure de la table
    $describeSQL = "DESCRIBE statuts";
    $describeStmt = $shop_pdo->prepare($describeSQL);
    $describeStmt->execute();
    $columns = $describeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Structure de la table statuts :</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>NULL</th><th>Clé</th><th>Défaut</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 🔍 Vérifier s'il y a déjà des statuts
    $countSQL = "SELECT COUNT(*) FROM statuts";
    $countStmt = $shop_pdo->prepare($countSQL);
    $countStmt->execute();
    $statutCount = $countStmt->fetchColumn();
    
    echo "<p class='info'>📊 Nombre de statuts actuels : {$statutCount}</p>";
    
    if ($statutCount > 0) {
        // Afficher les statuts existants
        $existingSQL = "SELECT * FROM statuts ORDER BY ordre, nom";
        $existingStmt = $shop_pdo->prepare($existingSQL);
        $existingStmt->execute();
        $existingStatuts = $existingStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📝 Statuts existants :</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Code</th><th>Actif</th><th>Ordre</th></tr>";
        foreach ($existingStatuts as $statut) {
            echo "<tr>";
            echo "<td>{$statut['id']}</td>";
            echo "<td>{$statut['nom']}</td>";
            echo "<td>{$statut['code']}</td>";
            echo "<td>" . ($statut['est_actif'] ? '✅' : '❌') . "</td>";
            echo "<td>{$statut['ordre']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p class='info'>ℹ️ Des statuts existent déjà. Pas besoin d'insérer de nouveaux statuts.</p>";
    } else {
        // 📝 Insérer des statuts de test
        echo "<h3>📝 Insertion des statuts de test...</h3>";
        
        // Vérifier d'abord si la table statut_categories existe
        $checkCategoriesSQL = "SHOW TABLES LIKE 'statut_categories'";
        $checkCategoriesStmt = $shop_pdo->prepare($checkCategoriesSQL);
        $checkCategoriesStmt->execute();
        $categoriesExists = $checkCategoriesStmt->fetchColumn();
        
        if (!$categoriesExists) {
            echo "<p class='error'>⚠️ La table 'statut_categories' n'existe pas. Créons d'abord les catégories...</p>";
            
            // Créer la table des catégories si elle n'existe pas
            $createCategoriesSQL = "
                CREATE TABLE IF NOT EXISTS statut_categories (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    nom varchar(100) NOT NULL,
                    code varchar(50) NOT NULL,
                    est_actif tinyint(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (id),
                    UNIQUE KEY code (code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $shop_pdo->exec($createCategoriesSQL);
            echo "<p class='success'>✅ Table 'statut_categories' créée</p>";
            
            // Insérer une catégorie par défaut
            $insertCategorySQL = "INSERT INTO statut_categories (nom, code) VALUES ('Général', 'general')";
            $shop_pdo->exec($insertCategorySQL);
            echo "<p class='success'>✅ Catégorie 'Général' ajoutée</p>";
        }
        
        // Récupérer l'ID de la catégorie
        $getCategorySQL = "SELECT id FROM statut_categories LIMIT 1";
        $getCategoryStmt = $shop_pdo->prepare($getCategorySQL);
        $getCategoryStmt->execute();
        $categoryId = $getCategoryStmt->fetchColumn();
        
        if (!$categoryId) {
            // Insérer une catégorie par défaut
            $insertCategorySQL = "INSERT INTO statut_categories (nom, code) VALUES ('Général', 'general')";
            $shop_pdo->exec($insertCategorySQL);
            $categoryId = $shop_pdo->lastInsertId();
            echo "<p class='success'>✅ Catégorie par défaut créée avec ID: {$categoryId}</p>";
        }
        
        // 📋 Statuts de test à insérer
        $statutsTest = [
            ['nom' => 'En attente', 'code' => 'en_attente', 'ordre' => 1],
            ['nom' => 'En cours', 'code' => 'en_cours', 'ordre' => 2],
            ['nom' => 'Diagnostique en cours', 'code' => 'diagnostique', 'ordre' => 3],
            ['nom' => 'En attente de pièce', 'code' => 'attente_piece', 'ordre' => 4],
            ['nom' => 'En attente d\'accord client', 'code' => 'attente_accord', 'ordre' => 5],
            ['nom' => 'Prêt', 'code' => 'pret', 'ordre' => 6],
            ['nom' => 'Terminé', 'code' => 'termine', 'ordre' => 7],
            ['nom' => 'Livré', 'code' => 'livre', 'ordre' => 8],
            ['nom' => 'Annulé', 'code' => 'annule', 'ordre' => 9],
            ['nom' => 'Irréparable', 'code' => 'irreparable', 'ordre' => 10]
        ];
        
        $insertSQL = "INSERT INTO statuts (nom, code, categorie_id, est_actif, ordre) VALUES (?, ?, ?, 1, ?)";
        $insertStmt = $shop_pdo->prepare($insertSQL);
        
        $insertCount = 0;
        foreach ($statutsTest as $statut) {
            try {
                $insertStmt->execute([
                    $statut['nom'],
                    $statut['code'],
                    $categoryId,
                    $statut['ordre']
                ]);
                echo "<p class='success'>✅ Statut '{$statut['nom']}' ajouté</p>";
                $insertCount++;
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Erreur pour '{$statut['nom']}': " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p class='success'>🎉 {$insertCount} statuts ajoutés avec succès !</p>";
    }
    
    echo "<h3>🧪 Test du nouveau modal</h3>";
    echo "<p><a href='test_nouveau_modal_statut.php' target='_blank'>➡️ Tester le nouveau modal de changement de statut</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur PDO : " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . $e->getMessage() . "</p>";
}

echo "</body>";
echo "</html>";
?> 