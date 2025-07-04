<?php
/**
 * Script de vérification de la migration GeekBoard
 * Vérifie que toutes les références aux bases de données Hostinger ont été remplacées
 */

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Vérification Migration GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .info { color: #004085; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
    </style>
</head>
<body>";

echo "<h1>🔍 Vérification de la Migration GeekBoard</h1>";
echo "<p>Ce script vérifie que toutes les références aux bases de données Hostinger ont été correctement remplacées.</p>";

// Liste des fichiers à vérifier
$fichiers_a_verifier = [
    'public_html/config/config.php',
    'public_html/config/database.php',
    'public_html/includes/config.php',
    'public_html/includes/db.php',
    'public_html/superadmin/create_superadmin.php',
    'public_html/superadmin/diagnostic_superadmin.php',
    'public_html/ajax/direct_recherche_clients.php',
    'public_html/ajax/search_reparations.php',
    'public_html/ajax/update_task_direct.php',
    'public_html/ajax/check_table_structure.php',
    'public_html/ajax/get_users_direct.php',
    'public_html/ajax/log_activity.php',
    'public_html/ajax/get_task_direct.php',
    'public_html/pages/bug_reports.php',
    'public_html/pages/signalements_bugs.php',
    'public_html/fix_session_cannes.php',
    'public_html/debug_session_shop.php',
    'public_html/pages/debug_repair_connection.php',
    'public_html/debug_repair_connection.php'
];

// Patterns à rechercher (références Hostinger)
$patterns_hostinger = [
    'u139954273',
    'srv931.hstgr.io',
    '191.96.63.103',
    'Maman01#',
    'Merguez01#'
];

// Patterns localhost attendus
$patterns_localhost = [
    'localhost',
    'geekboard_',
    'root',
    'geekboard_main',
    'geekboard_cannesphones',
    'geekboard_pscannes',
    'geekboard_mdgeek'
];

echo "<h2>1. Vérification des fichiers de configuration</h2>";

$fichiers_problematiques = [];
$fichiers_ok = [];

foreach ($fichiers_a_verifier as $fichier) {
    if (file_exists($fichier)) {
        $contenu = file_get_contents($fichier);
        $problemes = [];
        
        // Vérifier les patterns Hostinger (ne devraient plus exister)
        foreach ($patterns_hostinger as $pattern) {
            if (strpos($contenu, $pattern) !== false) {
                $problemes[] = "Référence Hostinger trouvée : $pattern";
            }
        }
        
        if (empty($problemes)) {
            $fichiers_ok[] = $fichier;
            echo "<div class='success'>✅ $fichier - Migration OK</div>";
        } else {
            $fichiers_problematiques[] = ['fichier' => $fichier, 'problemes' => $problemes];
            echo "<div class='error'>❌ $fichier - Problèmes détectés :</div>";
            foreach ($problemes as $probleme) {
                echo "<div class='warning'>⚠️ $probleme</div>";
            }
        }
    } else {
        echo "<div class='warning'>⚠️ Fichier non trouvé : $fichier</div>";
    }
}

echo "<h2>2. Test de connexion à la base de données</h2>";

try {
    // Test de connexion à la base principale
    $dsn = "mysql:host=localhost;port=3306;dbname=geekboard_main;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='success'>✅ Connexion à geekboard_main réussie</div>";
    
    // Vérifier la table shops
    $stmt = $pdo->query("SHOW TABLES LIKE 'shops'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Table shops trouvée</div>";
        
        // Afficher la configuration des magasins
        $shops = $pdo->query("SELECT id, name, subdomain, db_host, db_name, db_user, active FROM shops ORDER BY id")->fetchAll();
        
        if (!empty($shops)) {
            echo "<h3>Configuration des magasins :</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Hôte DB</th><th>Base DB</th><th>Utilisateur</th><th>Actif</th></tr>";
            
            foreach ($shops as $shop) {
                $status_class = $shop['active'] ? 'success' : 'warning';
                $host_class = ($shop['db_host'] === 'localhost') ? 'success' : 'error';
                $db_class = (strpos($shop['db_name'], 'geekboard_') === 0) ? 'success' : 'error';
                
                echo "<tr>";
                echo "<td>{$shop['id']}</td>";
                echo "<td>{$shop['name']}</td>";
                echo "<td>{$shop['subdomain']}</td>";
                echo "<td class='$host_class'>{$shop['db_host']}</td>";
                echo "<td class='$db_class'>{$shop['db_name']}</td>";
                echo "<td>{$shop['db_user']}</td>";
                echo "<td class='$status_class'>" . ($shop['active'] ? 'Oui' : 'Non') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Vérifier si toutes les bases utilisent localhost et geekboard_
            $localhost_ok = true;
            foreach ($shops as $shop) {
                if ($shop['db_host'] !== 'localhost' || strpos($shop['db_name'], 'geekboard_') !== 0) {
                    $localhost_ok = false;
                    break;
                }
            }
            
            if ($localhost_ok) {
                echo "<div class='success'>✅ Toutes les bases de données utilisent localhost et les conventions geekboard_*</div>";
            } else {
                echo "<div class='error'>❌ Certaines bases utilisent encore l'ancienne configuration</div>";
            }
        } else {
            echo "<div class='warning'>⚠️ Aucun magasin configuré dans la table shops</div>";
        }
    } else {
        echo "<div class='error'>❌ Table shops non trouvée</div>";
    }
    
    // Test des autres bases
    $bases_test = ['geekboard_cannesphones', 'geekboard_pscannes', 'geekboard_mdgeek'];
    
    echo "<h3>Test des bases de données des magasins :</h3>";
    foreach ($bases_test as $base) {
        try {
            $dsn_test = "mysql:host=localhost;port=3306;dbname=$base;charset=utf8mb4";
            $pdo_test = new PDO($dsn_test, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            echo "<div class='success'>✅ Connexion à $base réussie</div>";
        } catch (PDOException $e) {
            echo "<div class='warning'>⚠️ Base $base non accessible : " . $e->getMessage() . "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erreur de connexion à la base principale : " . $e->getMessage() . "</div>";
}

echo "<h2>3. Résumé de la vérification</h2>";

echo "<div class='info'>";
echo "<h3>📊 Statistiques :</h3>";
echo "<ul>";
echo "<li>Fichiers vérifiés : " . count($fichiers_a_verifier) . "</li>";
echo "<li>Fichiers OK : " . count($fichiers_ok) . "</li>";
echo "<li>Fichiers problématiques : " . count($fichiers_problematiques) . "</li>";
echo "</ul>";
echo "</div>";

if (empty($fichiers_problematiques)) {
    echo "<div class='success'>";
    echo "<h3>🎉 Migration réussie !</h3>";
    echo "<p>Tous les fichiers ont été correctement migrés vers la configuration localhost avec les conventions geekboard_*.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Migration incomplète</h3>";
    echo "<p>Les fichiers suivants nécessitent encore des corrections :</p>";
    echo "<ul>";
    foreach ($fichiers_problematiques as $probleme) {
        echo "<li><strong>{$probleme['fichier']}</strong>";
        echo "<ul>";
        foreach ($probleme['problemes'] as $detail) {
            echo "<li>$detail</li>";
        }
        echo "</ul>";
        echo "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h2>4. Actions recommandées</h2>";

echo "<div class='info'>";
echo "<h3>🔧 Prochaines étapes :</h3>";
echo "<ol>";
echo "<li>Exécuter le script SQL <code>update_shops_geekboard.sql</code> si ce n'est pas fait</li>";
echo "<li>Tester les fonctionnalités principales de l'application</li>";
echo "<li>Vérifier que toutes les pages se chargent correctement</li>";
echo "<li>Tester les connexions aux différents magasins</li>";
echo "<li>Effectuer des sauvegardes des nouvelles bases de données</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Important :</h3>";
echo "<ul>";
echo "<li>Assurez-vous que MySQL est démarré sur localhost</li>";
echo "<li>Vérifiez que l'utilisateur root a les permissions nécessaires</li>";
echo "<li>Testez l'application dans un navigateur</li>";
echo "<li>Gardez une sauvegarde des anciennes configurations</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Vérification terminée</strong> - " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 
/**
 * Script de vérification de la migration GeekBoard
 * Vérifie que toutes les références aux bases de données Hostinger ont été remplacées
 */

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Vérification Migration GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .info { color: #004085; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
    </style>
</head>
<body>";

echo "<h1>🔍 Vérification de la Migration GeekBoard</h1>";
echo "<p>Ce script vérifie que toutes les références aux bases de données Hostinger ont été correctement remplacées.</p>";

// Liste des fichiers à vérifier
$fichiers_a_verifier = [
    'public_html/config/config.php',
    'public_html/config/database.php',
    'public_html/includes/config.php',
    'public_html/includes/db.php',
    'public_html/superadmin/create_superadmin.php',
    'public_html/superadmin/diagnostic_superadmin.php',
    'public_html/ajax/direct_recherche_clients.php',
    'public_html/ajax/search_reparations.php',
    'public_html/ajax/update_task_direct.php',
    'public_html/ajax/check_table_structure.php',
    'public_html/ajax/get_users_direct.php',
    'public_html/ajax/log_activity.php',
    'public_html/ajax/get_task_direct.php',
    'public_html/pages/bug_reports.php',
    'public_html/pages/signalements_bugs.php',
    'public_html/fix_session_cannes.php',
    'public_html/debug_session_shop.php',
    'public_html/pages/debug_repair_connection.php',
    'public_html/debug_repair_connection.php'
];

// Patterns à rechercher (références Hostinger)
$patterns_hostinger = [
    'u139954273',
    'srv931.hstgr.io',
    '191.96.63.103',
    'Maman01#',
    'Merguez01#'
];

// Patterns localhost attendus
$patterns_localhost = [
    'localhost',
    'geekboard_',
    'root',
    'geekboard_main',
    'geekboard_cannesphones',
    'geekboard_pscannes',
    'geekboard_mdgeek'
];

echo "<h2>1. Vérification des fichiers de configuration</h2>";

$fichiers_problematiques = [];
$fichiers_ok = [];

foreach ($fichiers_a_verifier as $fichier) {
    if (file_exists($fichier)) {
        $contenu = file_get_contents($fichier);
        $problemes = [];
        
        // Vérifier les patterns Hostinger (ne devraient plus exister)
        foreach ($patterns_hostinger as $pattern) {
            if (strpos($contenu, $pattern) !== false) {
                $problemes[] = "Référence Hostinger trouvée : $pattern";
            }
        }
        
        if (empty($problemes)) {
            $fichiers_ok[] = $fichier;
            echo "<div class='success'>✅ $fichier - Migration OK</div>";
        } else {
            $fichiers_problematiques[] = ['fichier' => $fichier, 'problemes' => $problemes];
            echo "<div class='error'>❌ $fichier - Problèmes détectés :</div>";
            foreach ($problemes as $probleme) {
                echo "<div class='warning'>⚠️ $probleme</div>";
            }
        }
    } else {
        echo "<div class='warning'>⚠️ Fichier non trouvé : $fichier</div>";
    }
}

echo "<h2>2. Test de connexion à la base de données</h2>";

try {
    // Test de connexion à la base principale
    $dsn = "mysql:host=localhost;port=3306;dbname=geekboard_main;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='success'>✅ Connexion à geekboard_main réussie</div>";
    
    // Vérifier la table shops
    $stmt = $pdo->query("SHOW TABLES LIKE 'shops'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Table shops trouvée</div>";
        
        // Afficher la configuration des magasins
        $shops = $pdo->query("SELECT id, name, subdomain, db_host, db_name, db_user, active FROM shops ORDER BY id")->fetchAll();
        
        if (!empty($shops)) {
            echo "<h3>Configuration des magasins :</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Hôte DB</th><th>Base DB</th><th>Utilisateur</th><th>Actif</th></tr>";
            
            foreach ($shops as $shop) {
                $status_class = $shop['active'] ? 'success' : 'warning';
                $host_class = ($shop['db_host'] === 'localhost') ? 'success' : 'error';
                $db_class = (strpos($shop['db_name'], 'geekboard_') === 0) ? 'success' : 'error';
                
                echo "<tr>";
                echo "<td>{$shop['id']}</td>";
                echo "<td>{$shop['name']}</td>";
                echo "<td>{$shop['subdomain']}</td>";
                echo "<td class='$host_class'>{$shop['db_host']}</td>";
                echo "<td class='$db_class'>{$shop['db_name']}</td>";
                echo "<td>{$shop['db_user']}</td>";
                echo "<td class='$status_class'>" . ($shop['active'] ? 'Oui' : 'Non') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Vérifier si toutes les bases utilisent localhost et geekboard_
            $localhost_ok = true;
            foreach ($shops as $shop) {
                if ($shop['db_host'] !== 'localhost' || strpos($shop['db_name'], 'geekboard_') !== 0) {
                    $localhost_ok = false;
                    break;
                }
            }
            
            if ($localhost_ok) {
                echo "<div class='success'>✅ Toutes les bases de données utilisent localhost et les conventions geekboard_*</div>";
            } else {
                echo "<div class='error'>❌ Certaines bases utilisent encore l'ancienne configuration</div>";
            }
        } else {
            echo "<div class='warning'>⚠️ Aucun magasin configuré dans la table shops</div>";
        }
    } else {
        echo "<div class='error'>❌ Table shops non trouvée</div>";
    }
    
    // Test des autres bases
    $bases_test = ['geekboard_cannesphones', 'geekboard_pscannes', 'geekboard_mdgeek'];
    
    echo "<h3>Test des bases de données des magasins :</h3>";
    foreach ($bases_test as $base) {
        try {
            $dsn_test = "mysql:host=localhost;port=3306;dbname=$base;charset=utf8mb4";
            $pdo_test = new PDO($dsn_test, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            echo "<div class='success'>✅ Connexion à $base réussie</div>";
        } catch (PDOException $e) {
            echo "<div class='warning'>⚠️ Base $base non accessible : " . $e->getMessage() . "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erreur de connexion à la base principale : " . $e->getMessage() . "</div>";
}

echo "<h2>3. Résumé de la vérification</h2>";

echo "<div class='info'>";
echo "<h3>📊 Statistiques :</h3>";
echo "<ul>";
echo "<li>Fichiers vérifiés : " . count($fichiers_a_verifier) . "</li>";
echo "<li>Fichiers OK : " . count($fichiers_ok) . "</li>";
echo "<li>Fichiers problématiques : " . count($fichiers_problematiques) . "</li>";
echo "</ul>";
echo "</div>";

if (empty($fichiers_problematiques)) {
    echo "<div class='success'>";
    echo "<h3>🎉 Migration réussie !</h3>";
    echo "<p>Tous les fichiers ont été correctement migrés vers la configuration localhost avec les conventions geekboard_*.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Migration incomplète</h3>";
    echo "<p>Les fichiers suivants nécessitent encore des corrections :</p>";
    echo "<ul>";
    foreach ($fichiers_problematiques as $probleme) {
        echo "<li><strong>{$probleme['fichier']}</strong>";
        echo "<ul>";
        foreach ($probleme['problemes'] as $detail) {
            echo "<li>$detail</li>";
        }
        echo "</ul>";
        echo "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h2>4. Actions recommandées</h2>";

echo "<div class='info'>";
echo "<h3>🔧 Prochaines étapes :</h3>";
echo "<ol>";
echo "<li>Exécuter le script SQL <code>update_shops_geekboard.sql</code> si ce n'est pas fait</li>";
echo "<li>Tester les fonctionnalités principales de l'application</li>";
echo "<li>Vérifier que toutes les pages se chargent correctement</li>";
echo "<li>Tester les connexions aux différents magasins</li>";
echo "<li>Effectuer des sauvegardes des nouvelles bases de données</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Important :</h3>";
echo "<ul>";
echo "<li>Assurez-vous que MySQL est démarré sur localhost</li>";
echo "<li>Vérifiez que l'utilisateur root a les permissions nécessaires</li>";
echo "<li>Testez l'application dans un navigateur</li>";
echo "<li>Gardez une sauvegarde des anciennes configurations</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Vérification terminée</strong> - " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 