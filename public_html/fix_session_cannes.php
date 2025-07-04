<?php
/**
 * 🔧 CORRECTIF SESSION - Forcer Cannes Phones
 * Script pour définir manuellement la session Cannes Phones et tester la recherche
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers de configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>🔧 Correctif Session Cannes Phones</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
.status-ok { color: #28a745; font-weight: bold; }
.status-error { color: #dc3545; font-weight: bold; }
.status-warning { color: #ffc107; font-weight: bold; }
.info-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; }
</style>";
echo "</head><body class='container mt-4'>";

echo "<h1>🔧 Correctif Session - Cannes Phones</h1>";

// 1. FORCER LA SESSION CANNES PHONES
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-primary text-white'><h3>1. 🏪 Configuration Session Cannes Phones</h3></div>";
echo "<div class='card-body'>";

// Configuration directe pour Cannes Phones
$_SESSION['shop_id'] = 1; // Supposons que Cannes Phones a l'ID 1
$_SESSION['shop_name'] = 'Cannes Phones';

echo "<p class='status-ok'>✅ Session forcée: Shop ID = 1 (Cannes Phones)</p>";
echo "<p class='status-ok'>✅ Shop Name = Cannes Phones</p>";

// Afficher le contenu complet de la session
echo "<div class='info-box'>";
echo "<h5>Contenu de \$_SESSION après correction :</h5>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

echo "</div></div>";

// 2. VÉRIFIER LA TABLE SHOPS
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'><h3>2. 📊 Vérification Table Shops</h3></div>";
echo "<div class='card-body'>";

try {
    $main_pdo = getMainDBConnection();
    
    if ($main_pdo) {
        echo "<p class='status-ok'>✅ Connexion base principale établie</p>";
        
        // Vérifier la base connectée
        $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='status-ok'>📊 Base principale: <strong>" . $result['db_name'] . "</strong></p>";
        
        // Lister tous les magasins
        $stmt = $main_pdo->query("SELECT * FROM shops ORDER BY id");
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='status-ok'>🏪 Magasins trouvés: " . count($shops) . "</p>";
        
        if (count($shops) > 0) {
            echo "<div class='info-box'>";
            echo "<h5>Liste des magasins :</h5>";
            echo "<table class='table table-sm'>";
            echo "<tr><th>ID</th><th>Nom</th><th>DB Host</th><th>DB Name</th><th>Actif</th></tr>";
            foreach ($shops as $shop) {
                echo "<tr>";
                echo "<td>" . $shop['id'] . "</td>";
                echo "<td>" . htmlspecialchars($shop['name']) . "</td>";
                echo "<td>" . htmlspecialchars($shop['db_host'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($shop['db_name'] ?? 'N/A') . "</td>";
                echo "<td>" . ($shop['active'] ? '✅' : '❌') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
            
            // Vérifier si Cannes Phones existe
            $cannes_shop = null;
            foreach ($shops as $shop) {
                if (stripos($shop['name'], 'cannes') !== false || $shop['db_name'] === 'geekboard_cannesphones') {
                    $cannes_shop = $shop;
                    break;
                }
            }
            
            if ($cannes_shop) {
                echo "<p class='status-ok'>✅ Magasin Cannes trouvé: ID=" . $cannes_shop['id'] . ", Nom=" . htmlspecialchars($cannes_shop['name']) . "</p>";
                
                // Corriger la session avec le vrai ID
                $_SESSION['shop_id'] = $cannes_shop['id'];
                $_SESSION['shop_name'] = $cannes_shop['name'];
                
                echo "<p class='status-ok'>✅ Session corrigée avec le vrai ID: " . $cannes_shop['id'] . "</p>";
            } else {
                echo "<p class='status-warning'>⚠️ Magasin Cannes non trouvé dans la base. Création nécessaire.</p>";
                
                // Créer le magasin Cannes Phones
                echo "<h5>🔧 Création du magasin Cannes Phones :</h5>";
                try {
                    $stmt = $main_pdo->prepare("
                        INSERT INTO shops (name, db_host, db_port, db_user, db_pass, db_name, active) 
                        VALUES (?, ?, ?, ?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE 
                        db_host = VALUES(db_host),
                        db_port = VALUES(db_port),
                        db_user = VALUES(db_user),
                        db_pass = VALUES(db_pass),
                        db_name = VALUES(db_name)
                    ");
                    
                    $stmt->execute([
                        'Cannes Phones',
                        '191.96.63.103',
                        '3306',
                                    'geekboard_cannesphones',
            '',
            'geekboard_cannesphones'
                    ]);
                    
                    $new_shop_id = $main_pdo->lastInsertId();
                    if ($new_shop_id) {
                        echo "<p class='status-ok'>✅ Magasin Cannes Phones créé avec l'ID: " . $new_shop_id . "</p>";
                        $_SESSION['shop_id'] = $new_shop_id;
                        $_SESSION['shop_name'] = 'Cannes Phones';
                    }
                } catch (Exception $e) {
                    echo "<p class='status-error'>❌ Erreur création magasin: " . $e->getMessage() . "</p>";
                }
            }
        }
        
    } else {
        echo "<p class='status-error'>❌ Impossible de se connecter à la base principale</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='status-error'>❌ Erreur table shops: " . $e->getMessage() . "</p>";
}

echo "</div></div>";

// 3. TEST getShopDBConnection AVEC LA SESSION CORRIGÉE
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-success text-white'><h3>3. 🔌 Test getShopDBConnection() avec Session Corrigée</h3></div>";
echo "<div class='card-body'>";

try {
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo) {
        echo "<p class='status-ok'>✅ getShopDBConnection() fonctionne avec la session corrigée!</p>";
        
        // Vérifier quelle base est connectée
        $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='status-ok'>📊 Base connectée: <strong>" . $result['db_name'] . "</strong></p>";
        
        // Vérifier si c'est la bonne base
        if ($result['db_name'] === 'geekboard_cannesphones') {
            echo "<p class='status-ok'>✅ PARFAIT! Connexion à la base Cannes Phones</p>";
            
            // Test de recherche universelle simulée
            echo "<h5>🔍 Test Recherche Universelle Simulée :</h5>";
            
            try {
                // Simuler un appel de recherche
                $terme = '%a%';
                $sql = "SELECT id, nom, prenom, telephone, email 
                        FROM clients 
                        WHERE nom LIKE :terme 
                        OR prenom LIKE :terme 
                        OR telephone LIKE :terme 
                        OR email LIKE :terme 
                        ORDER BY nom, prenom 
                        LIMIT 5";
                $stmt = $shop_pdo->prepare($sql);
                $stmt->bindParam(':terme', $terme);
                $stmt->execute();
                $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p class='status-ok'>✅ Recherche clients réussie: " . count($clients) . " résultat(s)</p>";
                
                if (count($clients) > 0) {
                    echo "<div class='info-box'>";
                    echo "<h6>Exemples trouvés :</h6>";
                    echo "<ul>";
                    foreach (array_slice($clients, 0, 3) as $client) {
                        echo "<li>" . htmlspecialchars($client['nom'] . ' ' . $client['prenom']) . " - " . htmlspecialchars($client['telephone'] ?? 'N/A') . "</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                }
                
                // Test du JSON comme dans l'AJAX
                $result_json = [
                    'clients' => $clients,
                    'reparations' => [],
                    'commandes' => []
                ];
                
                echo "<div class='info-box'>";
                echo "<h6>JSON qui sera retourné (format AJAX) :</h6>";
                echo "<pre>" . json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<p class='status-error'>❌ Erreur test recherche: " . $e->getMessage() . "</p>";
            }
            
        } else {
            echo "<p class='status-warning'>⚠️ Connexion à une base différente: " . $result['db_name'] . "</p>";
        }
        
    } else {
        echo "<p class='status-error'>❌ getShopDBConnection() retourne null même avec session corrigée</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='status-error'>❌ Erreur getShopDBConnection(): " . $e->getMessage() . "</p>";
}

echo "</div></div>";

// 4. TEST AJAX DIRECT
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-warning text-dark'><h3>4. 🧪 Test AJAX en Direct</h3></div>";
echo "<div class='card-body'>";

echo "<div class='alert alert-info'>";
echo "<h5>🧪 Test dans la Console du Navigateur :</h5>";
echo "<p>Maintenant que la session est corrigée, testez ce code dans la console :</p>";
echo "<pre><code>fetch('ajax/recherche_universelle.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'terme=a'
})
.then(response => {
    console.log('Status:', response.status);
    return response.text();
})
.then(data => {
    console.log('Réponse brute:', data);
    try {
        const json = JSON.parse(data);
        console.log('✅ JSON valide:', json);
        console.log('Clients trouvés:', json.clients.length);
    } catch (e) {
        console.error('❌ JSON invalide:', e);
    }
})
.catch(error => console.error('❌ Erreur:', error));</code></pre>";
echo "</div>";

echo "<div class='alert alert-success'>";
echo "<h4>🎉 Session Cannes Phones Configurée!</h4>";
echo "<p><strong>Shop ID:</strong> " . ($_SESSION['shop_id'] ?? 'non défini') . "</p>";
echo "<p><strong>Shop Name:</strong> " . ($_SESSION['shop_name'] ?? 'non défini') . "</p>";
echo "<p>Maintenant retournez à votre page d'accueil et testez la recherche universelle!</p>";
echo "</div>";

echo "</div></div>";

echo "</body></html>";
?> 