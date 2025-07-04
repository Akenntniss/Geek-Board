<?php
/**
 * 🔍 DIAGNOSTIC SPÉCIAL - Session Shop ID & Connexion Cannes Phones
 * Script pour diagnostiquer le problème de recherche universelle
 */

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html>";
echo "<html><head><title>🔍 Diagnostic Session & Database</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
.status-ok { color: #28a745; font-weight: bold; }
.status-error { color: #dc3545; font-weight: bold; }
.status-warning { color: #ffc107; font-weight: bold; }
.info-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; }
</style>";
echo "</head><body class='container mt-4'>";

echo "<h1>🔍 Diagnostic Complet - GeekBoard Cannes Phones</h1>";

// 1. VÉRIFICATION DE LA SESSION
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-primary text-white'><h3>1. 📋 État de la Session Actuelle</h3></div>";
echo "<div class='card-body'>";

echo "<div class='info-box'>";
echo "<h5>Informations Session :</h5>";
echo "<ul>";
echo "<li><strong>Session ID:</strong> " . session_id() . "</li>";
echo "<li><strong>Session Status:</strong> " . session_status() . " (1=disabled, 2=active)</li>";
echo "<li><strong>Session Name:</strong> " . session_name() . "</li>";
echo "</ul>";
echo "</div>";

// Vérifier shop_id
if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
    echo "<p class='status-ok'>✅ Shop ID défini en session: <strong>" . $_SESSION['shop_id'] . "</strong></p>";
    $shop_id = $_SESSION['shop_id'];
} else {
    echo "<p class='status-error'>❌ Shop ID NON DÉFINI en session</p>";
    echo "<div class='alert alert-danger'>";
    echo "<h6>🚨 PROBLÈME PRINCIPAL IDENTIFIÉ:</h6>";
    echo "<p>L'utilisateur doit se connecter et sélectionner son magasin via le menu déroulant de la page de login.</p>";
    echo "<p><strong>Simulation:</strong> Définition temporaire du shop_id=1 pour test</p>";
    echo "</div>";
    $_SESSION['shop_id'] = 1; // Simulation pour test
    $shop_id = 1;
}

// Afficher toutes les variables de session
echo "<div class='info-box'>";
echo "<h5>Contenu complet de \$_SESSION :</h5>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

echo "</div></div>";

// 2. VÉRIFICATION DES FICHIERS DE CONFIGURATION
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-secondary text-white'><h3>2. ⚙️ Configuration des Fichiers</h3></div>";
echo "<div class='card-body'>";

// Tester config.php
try {
    require_once __DIR__ . '/includes/config.php';
    echo "<p class='status-ok'>✅ includes/config.php chargé avec succès</p>";
} catch (Exception $e) {
    echo "<p class='status-error'>❌ Erreur config.php: " . $e->getMessage() . "</p>";
}

// Tester database.php
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p class='status-ok'>✅ config/database.php chargé avec succès</p>";
} catch (Exception $e) {
    echo "<p class='status-error'>❌ Erreur database.php: " . $e->getMessage() . "</p>";
}

echo "</div></div>";

// 3. TEST DE CONNEXION DIRECTE À CANNES PHONES
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'><h3>3. 🔌 Test Connexion Directe - Cannes Phones</h3></div>";
echo "<div class='card-body'>";

$cannes_config = [
    'host' => '191.96.63.103',
    'port' => '3306',
                'user' => 'root',
            'pass' => '',
            'dbname' => 'geekboard_cannesphones'
];

echo "<div class='info-box'>";
echo "<h5>Configuration Cannes Phones :</h5>";
echo "<ul>";
foreach ($cannes_config as $key => $value) {
    if ($key === 'pass') {
        echo "<li><strong>$key:</strong> " . str_repeat('*', strlen($value)) . "</li>";
    } else {
        echo "<li><strong>$key:</strong> $value</li>";
    }
}
echo "</ul>";
echo "</div>";

try {
    $dsn = "mysql:host={$cannes_config['host']};port={$cannes_config['port']};dbname={$cannes_config['dbname']};charset=utf8mb4";
    $cannes_pdo = new PDO(
        $dsn,
        $cannes_config['user'],
        $cannes_config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    );
    
    echo "<p class='status-ok'>✅ Connexion directe à Cannes Phones réussie!</p>";
    
    // Vérifier la base
    $stmt = $cannes_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='status-ok'>📊 Base connectée: <strong>" . $result['db_name'] . "</strong></p>";
    
    // Compter les clients
    $stmt = $cannes_pdo->query("SELECT COUNT(*) as count FROM clients");
    $clients_count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='status-ok'>👥 Nombre de clients: <strong>" . $clients_count['count'] . "</strong></p>";
    
    // Compter les réparations
    $stmt = $cannes_pdo->query("SELECT COUNT(*) as count FROM reparations");
    $reparations_count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='status-ok'>🔧 Nombre de réparations: <strong>" . $reparations_count['count'] . "</strong></p>";
    
} catch (PDOException $e) {
    echo "<p class='status-error'>❌ Erreur connexion Cannes Phones: " . $e->getMessage() . "</p>";
}

echo "</div></div>";

// 4. TEST DE getShopDBConnection()
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-warning text-dark'><h3>4. 🏪 Test getShopDBConnection() Système</h3></div>";
echo "<div class='card-body'>";

try {
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo) {
        echo "<p class='status-ok'>✅ getShopDBConnection() fonctionne!</p>";
        
        // Vérifier quelle base est connectée
        $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='status-ok'>📊 Base système connectée: <strong>" . $result['db_name'] . "</strong></p>";
        
        // Vérifier si c'est la bonne base
        if ($result['db_name'] === 'geekboard_cannesphones') {
            echo "<p class='status-ok'>✅ Connexion à la bonne base (Cannes Phones)</p>";
        } else {
            echo "<p class='status-warning'>⚠️ Connexion à une base différente: " . $result['db_name'] . "</p>";
            echo "<div class='alert alert-warning'>";
            echo "<p>Le système se connecte à une base différente de Cannes Phones.</p>";
            echo "<p>Vérifiez la configuration de la table 'shops' dans la base principale.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p class='status-error'>❌ getShopDBConnection() retourne null</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='status-error'>❌ Erreur getShopDBConnection(): " . $e->getMessage() . "</p>";
}

echo "</div></div>";

// 5. TEST RECHERCHE UNIVERSELLE SIMULÉE
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-success text-white'><h3>5. 🔍 Test Recherche Universelle Simulée</h3></div>";
echo "<div class='card-body'>";

if (isset($cannes_pdo) && $cannes_pdo) {
    echo "<h5>Test avec la base Cannes Phones directement :</h5>";
    
    try {
        // Test recherche clients avec terme "test"
        $sql = "SELECT id, nom, prenom, telephone, email 
                FROM clients 
                WHERE nom LIKE :terme 
                OR prenom LIKE :terme 
                OR telephone LIKE :terme 
                OR email LIKE :terme 
                ORDER BY nom, prenom 
                LIMIT 5";
        $stmt = $cannes_pdo->prepare($sql);
        $terme = '%a%'; // Recherche avec 'a' pour avoir des résultats
        $stmt->bindParam(':terme', $terme);
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='status-ok'>✅ Recherche clients réussie: " . count($clients) . " résultat(s)</p>";
        if (count($clients) > 0) {
            echo "<div class='info-box'>";
            echo "<h6>Exemples de clients trouvés :</h6>";
            echo "<ul>";
            foreach (array_slice($clients, 0, 3) as $client) {
                echo "<li>" . htmlspecialchars($client['nom'] . ' ' . $client['prenom']) . " - " . htmlspecialchars($client['telephone'] ?? 'N/A') . "</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<p class='status-error'>❌ Erreur test recherche: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='status-error'>❌ Pas de connexion pour tester la recherche</p>";
}

echo "</div></div>";

// 6. RÉSUMÉ ET RECOMMANDATIONS
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-dark text-white'><h3>6. 📝 Résumé et Recommandations</h3></div>";
echo "<div class='card-body'>";

echo "<div class='alert alert-info'>";
echo "<h5>🎯 Diagnostic Principal :</h5>";

if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    echo "<div class='alert alert-danger'>";
    echo "<h6>🚨 PROBLÈME PRINCIPAL IDENTIFIÉ:</h6>";
    echo "<p><strong>Le shop_id n'est pas défini en session.</strong></p>";
    echo "<p>L'utilisateur DOIT se connecter et sélectionner son magasin via le menu déroulant de la page de login.</p>";
    echo "</div>";
    
    echo "<h6>🔧 Solutions à appliquer :</h6>";
    echo "<ol>";
    echo "<li><strong>Page de Login:</strong> Vérifier que le menu déroulant des magasins fonctionne</li>";
    echo "<li><strong>Session Management:</strong> S'assurer que \$_SESSION['shop_id'] est défini après login</li>";
    echo "<li><strong>Redirection:</strong> Rediriger vers login si shop_id manquant</li>";
    echo "</ol>";
} else {
    echo "<div class='alert alert-success'>";
    echo "<h6>✅ Session OK - Shop ID défini</h6>";
    echo "<p>Le problème pourrait venir d'une incompatibilité de configuration database.</p>";
    echo "</div>";
}

echo "</div>";

echo "<div class='alert alert-warning'>";
echo "<h5>🔄 Test AJAX Recherche Universelle :</h5>";
echo "<p>Utilisez ce code dans la console de votre navigateur pour tester :</p>";
echo "<pre><code>fetch('ajax/recherche_universelle.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'terme=test'
})
.then(response => {
    console.log('Status:', response.status);
    return response.text();
})
.then(data => {
    console.log('Réponse brute:', data);
    try {
        const json = JSON.parse(data);
        console.log('JSON valide:', json);
    } catch (e) {
        console.error('JSON invalide:', e);
    }
})
.catch(error => console.error('Erreur:', error));</code></pre>";
echo "</div>";

echo "</div></div>";

echo "<div class='text-center mt-4'>";
echo "<h4>🎯 Prochaine étape : Vérifiez votre page de login et la sélection du magasin!</h4>";
echo "</div>";

echo "</body></html>";
?> 