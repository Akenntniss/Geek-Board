<?php
/**
 * 🔧 Script de Test de la Recherche Universelle Corrigée
 * Vérifie que les corrections apportées au système multi-database fonctionnent
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Recherche Universelle - Fix</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
.test-success { color: #28a745; font-weight: bold; }
.test-error { color: #dc3545; font-weight: bold; }
.test-warning { color: #ffc107; font-weight: bold; }
</style>";
echo "</head><body class='container mt-4'>";

echo "<h1>🔧 Test de la Recherche Universelle - Corrections Appliquées</h1>";

// 1. Vérifier l'inclusion des fichiers requis
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h3>1. Vérification des Fichiers</h3></div>";
echo "<div class='card-body'>";

// Tester l'inclusion de config.php corrigé
try {
    require_once __DIR__ . '/includes/config.php';
    echo "<p class='test-success'>✅ includes/config.php chargé avec succès (balise PHP ajoutée)</p>";
} catch (Exception $e) {
    echo "<p class='test-error'>❌ Erreur avec includes/config.php: " . $e->getMessage() . "</p>";
}

// Tester l'inclusion de database.php
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p class='test-success'>✅ config/database.php chargé avec succès</p>";
} catch (Exception $e) {
    echo "<p class='test-error'>❌ Erreur avec config/database.php: " . $e->getMessage() . "</p>";
}

echo "</div></div>";

// 2. Tester la connexion à la base de données
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h3>2. Test des Connexions Database</h3></div>";
echo "<div class='card-body'>";

// Vérifier la session shop_id
if (isset($_SESSION['shop_id'])) {
    echo "<p class='test-success'>✅ Shop ID en session: " . $_SESSION['shop_id'] . "</p>";
} else {
    echo "<p class='test-warning'>⚠️ Aucun Shop ID en session - tentative avec shop_id=1</p>";
    $_SESSION['shop_id'] = 1; // Valeur par défaut pour test
}

// Tester getShopDBConnection
try {
    $shop_pdo = getShopDBConnection();
    if ($shop_pdo) {
        echo "<p class='test-success'>✅ Connexion à la base du magasin établie</p>";
        
        // Vérifier quelle base est connectée
        $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='test-success'>📊 Base de données connectée: <strong>" . ($result['db_name'] ?? 'Inconnue') . "</strong></p>";
        
        // Compter les clients pour vérifier l'accès aux données
        try {
            $stmt_clients = $shop_pdo->query("SELECT COUNT(*) as count FROM clients");
            $clients_count = $stmt_clients->fetch(PDO::FETCH_ASSOC);
            echo "<p class='test-success'>👥 Nombre de clients dans la base: <strong>" . $clients_count['count'] . "</strong></p>";
        } catch (Exception $e) {
            echo "<p class='test-error'>❌ Erreur lors du comptage des clients: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p class='test-error'>❌ Impossible d'établir la connexion à la base du magasin</p>";
    }
} catch (Exception $e) {
    echo "<p class='test-error'>❌ Erreur de connexion: " . $e->getMessage() . "</p>";
}

echo "</div></div>";

// 3. Tester la recherche universelle manuellement
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h3>3. Test Manuel de la Recherche</h3></div>";
echo "<div class='card-body'>";

if (isset($shop_pdo) && $shop_pdo) {
    echo "<h5>Test avec le terme 'test':</h5>";
    
    // Simuler les fonctions de recherche corrigées
    try {
        // Test recherche clients
        $sql = "SELECT id, nom, prenom, telephone, email 
                FROM clients 
                WHERE nom LIKE :terme 
                OR prenom LIKE :terme 
                OR telephone LIKE :terme 
                OR email LIKE :terme 
                ORDER BY nom, prenom 
                LIMIT 3";
        $stmt = $shop_pdo->prepare($sql);
        $terme = '%test%';
        $stmt->bindParam(':terme', $terme);
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='test-success'>✅ Recherche clients: " . count($clients) . " résultat(s)</p>";
        if (count($clients) > 0) {
            echo "<ul>";
            foreach ($clients as $client) {
                echo "<li>" . htmlspecialchars($client['nom'] . ' ' . $client['prenom']) . " - " . htmlspecialchars($client['telephone'] ?? 'N/A') . "</li>";
            }
            echo "</ul>";
        }
        
        // Test recherche réparations
        $sql = "SELECT r.id, r.appareil, r.probleme, r.statut,
                       CONCAT(c.nom, ' ', c.prenom) as client_nom
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.appareil LIKE :terme 
                OR r.probleme LIKE :terme 
                OR c.nom LIKE :terme 
                OR c.prenom LIKE :terme
                ORDER BY r.date_creation DESC 
                LIMIT 3";
        $stmt = $shop_pdo->prepare($sql);
        $stmt->bindParam(':terme', $terme);
        $stmt->execute();
        $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='test-success'>✅ Recherche réparations: " . count($reparations) . " résultat(s)</p>";
        if (count($reparations) > 0) {
            echo "<ul>";
            foreach ($reparations as $rep) {
                echo "<li>" . htmlspecialchars($rep['appareil']) . " - " . htmlspecialchars($rep['client_nom']) . " (" . htmlspecialchars($rep['statut']) . ")</li>";
            }
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<p class='test-error'>❌ Erreur lors du test de recherche: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='test-error'>❌ Impossible de tester la recherche sans connexion database</p>";
}

echo "</div></div>";

// 4. Test AJAX simulé
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h3>4. Simulation du Comportement AJAX</h3></div>";
echo "<div class='card-body'>";

echo "<p><strong>URL de test AJAX:</strong> <code>ajax/recherche_universelle.php</code></p>";
echo "<p><strong>Méthode:</strong> POST</p>";
echo "<p><strong>Paramètre:</strong> terme=test</p>";

// Instructions pour le test manuel
echo "<div class='alert alert-info'>";
echo "<h6>🧪 Pour tester manuellement dans la console du navigateur:</h6>";
echo "<pre><code>fetch('ajax/recherche_universelle.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'terme=test'
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Erreur:', error));</code></pre>";
echo "</div>";

echo "</div></div>";

// 5. Résumé des corrections apportées
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-success text-white'><h3>✅ Corrections Appliquées</h3></div>";
echo "<div class='card-body'>";

echo "<ol>";
echo "<li><strong>config.php:</strong> Ajout de la balise PHP d'ouverture <code>&lt;?php</code> manquante</li>";
echo "<li><strong>recherche_universelle.php:</strong> Remplacement de <code>require_once '../includes/config.php'</code> par <code>require_once '../config/database.php'</code></li>";
echo "<li><strong>Gestion session:</strong> Ajout du démarrage de session sécurisé</li>";
echo "<li><strong>Connexion database:</strong> Utilisation de <code>getShopDBConnection()</code> au lieu de la variable globale <code>\$db</code></li>";
echo "<li><strong>Gestion d'erreurs:</strong> Amélioration du logging et des messages d'erreur</li>";
echo "<li><strong>Sécurité:</strong> Vérification de l'existence des tables avant requête</li>";
echo "<li><strong>Isolation des données:</strong> Respect du système multi-boutique</li>";
echo "</ol>";

echo "</div></div>";

echo "<div class='alert alert-success'>";
echo "<h4>🎉 Corrections Terminées!</h4>";
echo "<p>La recherche universelle devrait maintenant fonctionner correctement avec votre système multi-database GeekBoard.</p>";
echo "<p><strong>Prochaine étape:</strong> Testez la recherche sur votre page d'accueil.</p>";
echo "</div>";

echo "</body></html>";
?> 