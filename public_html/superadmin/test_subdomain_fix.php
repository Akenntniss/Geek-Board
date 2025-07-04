<?php
// Script de test pour vérifier la correction du système de sous-domaines
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

echo "<h1>Test de la correction du système de sous-domaines</h1>";
echo "<hr>";

// Inclure le fichier de configuration des sous-domaines
echo "<h2>1. Test du fichier subdomain_config.php</h2>";
try {
    require_once('../config/subdomain_config.php');
    echo "<p style='color: green;'>✅ Le fichier subdomain_config.php a été chargé avec succès</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur lors du chargement de subdomain_config.php : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test de la fonction de détection des sous-domaines
echo "<h2>2. Test de la fonction detectShopFromSubdomain()</h2>";
if (function_exists('detectShopFromSubdomain')) {
    echo "<p style='color: green;'>✅ La fonction detectShopFromSubdomain() existe</p>";
    
    // Simuler différents hosts pour tester
    $test_hosts = [
        'cannes.mdgeek.top',
        'test.mdgeek.top',
        'example.mdgeek.top',
        'localhost'
    ];
    
    echo "<h3>Tests de détection pour différents hosts :</h3>";
    foreach ($test_hosts as $host) {
        // Sauvegarder l'host original
        $original_host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Définir le host de test
        $_SERVER['HTTP_HOST'] = $host;
        
        try {
            $detected_id = detectShopFromSubdomain();
            if ($detected_id) {
                echo "<p><strong>$host</strong> : ✅ Magasin ID détecté : $detected_id</p>";
            } else {
                echo "<p><strong>$host</strong> : ⚠️ Aucun magasin détecté</p>";
            }
        } catch (Exception $e) {
            echo "<p><strong>$host</strong> : ❌ Erreur : " . $e->getMessage() . "</p>";
        }
        
        // Restaurer l'host original
        $_SERVER['HTTP_HOST'] = $original_host;
    }
} else {
    echo "<p style='color: red;'>❌ La fonction detectShopFromSubdomain() n'existe pas</p>";
}

echo "<hr>";

// Test de connexion à la base de données
echo "<h2>3. Test de connexion à la base de données</h2>";
try {
    require_once('../config/database.php');
    $pdo = getMainDBConnection();
    echo "<p style='color: green;'>✅ Connexion à la base de données principale réussie</p>";
    
    // Tester la récupération des magasins
    $shops = $pdo->query("SELECT id, name, subdomain FROM shops WHERE active = 1")->fetchAll();
    echo "<p><strong>Nombre de magasins actifs trouvés :</strong> " . count($shops) . "</p>";
    
    if (count($shops) > 0) {
        echo "<h3>Magasins actifs :</h3>";
        echo "<ul>";
        foreach ($shops as $shop) {
            $subdomain = $shop['subdomain'] ?? 'NON DÉFINI';
            echo "<li>ID " . $shop['id'] . " : " . htmlspecialchars($shop['name']) . " (sous-domaine: " . htmlspecialchars($subdomain) . ")</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion à la base de données : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test de la page index
echo "<h2>4. Test de la requête de la page index</h2>";
try {
    require_once('../config/database.php');
    $pdo = getMainDBConnection();
    
    // Même requête que dans index.php
    $shops = $pdo->query("SELECT * FROM shops ORDER BY name")->fetchAll();
    echo "<p><strong>Nombre de magasins retournés par la requête d'index :</strong> " . count($shops) . "</p>";
    
    if (count($shops) > 0) {
        echo "<p style='color: green;'>✅ La requête d'index fonctionne correctement</p>";
        echo "<h3>Tous les magasins (ordre alphabétique) :</h3>";
        echo "<ul>";
        foreach ($shops as $shop) {
            $status = $shop['active'] ? 'Actif' : 'Inactif';
            echo "<li>ID " . $shop['id'] . " : " . htmlspecialchars($shop['name']) . " ($status)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Aucun magasin trouvé</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur avec la requête d'index : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Instructions pour l'utilisateur
echo "<h2>5. Instructions</h2>";
echo "<p>Si tous les tests ci-dessus sont verts, le problème d'affichage des magasins devrait être résolu.</p>";
echo "<p><strong>Prochaines étapes :</strong></p>";
echo "<ol>";
echo "<li>Retournez à la <a href='index.php'>page d'accueil du superadmin</a></li>";
echo "<li>Vérifiez que tous les magasins s'affichent correctement</li>";
echo "<li>Si le problème persiste, utilisez le <a href='debug_shops.php'>script de diagnostic complet</a></li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php'>← Retour à l'accueil</a> | <a href='debug_shops.php'>→ Diagnostic complet</a></p>";
?> 