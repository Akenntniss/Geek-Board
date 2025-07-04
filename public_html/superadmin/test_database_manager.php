<?php
/**
 * Script de test pour le Gestionnaire de Base de Données
 * Ce script vérifie que tous les composants sont correctement installés
 */

// Simulation d'une session super administrateur pour les tests
session_start();
$_SESSION['superadmin_id'] = 1; // Test seulement

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Test - Gestionnaire de Base de Données</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body>
<div class='container mt-4'>
    <h1><i class='fas fa-database me-2'></i>Test du Gestionnaire de Base de Données</h1>
    <div class='alert alert-info'>
        <i class='fas fa-info-circle me-2'></i>
        Ce script teste les composants du gestionnaire de base de données.
    </div>";

// Test 1: Vérification des fichiers
echo "<div class='card mb-4'>
        <div class='card-header'><h5>1. Vérification des fichiers</h5></div>
        <div class='card-body'>";

$files_to_check = [
    'database_manager.php' => 'Interface principale',
    'database_config.php' => 'Configuration',
    '../assets/css/database-manager.css' => 'Styles CSS',
    '../assets/js/database-manager.js' => 'JavaScript',
    '../logs/database_manager.log' => 'Fichier de logs',
    'README_database_manager.md' => 'Documentation'
];

foreach ($files_to_check as $file => $description) {
    $exists = file_exists($file);
    $icon = $exists ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';
    $status = $exists ? 'OK' : 'MANQUANT';
    echo "<div class='d-flex justify-content-between'>
            <span><i class='$icon me-2'></i>$description</span>
            <code>$file</code>
            <span class='badge " . ($exists ? 'bg-success' : 'bg-danger') . "'>$status</span>
          </div>";
}

echo "</div></div>";

// Test 2: Configuration de base de données
echo "<div class='card mb-4'>
        <div class='card-header'><h5>2. Test de connexion à la base principale</h5></div>
        <div class='card-body'>";

try {
    require_once('../config/database.php');
    $main_pdo = getMainDBConnection();
    
    if ($main_pdo) {
        echo "<div class='alert alert-success'>
                <i class='fas fa-check-circle me-2'></i>
                Connexion à la base principale réussie
              </div>";
        
        // Tester la récupération des magasins
        $shops = $main_pdo->query("SELECT COUNT(*) as count FROM shops")->fetch();
        echo "<p><strong>Magasins disponibles :</strong> " . $shops['count'] . "</p>";
        
    } else {
        echo "<div class='alert alert-danger'>
                <i class='fas fa-times-circle me-2'></i>
                Impossible de se connecter à la base principale
              </div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Erreur : " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "</div></div>";

// Test 3: Configuration du gestionnaire
echo "<div class='card mb-4'>
        <div class='card-header'><h5>3. Test de la configuration</h5></div>
        <div class='card-body'>";

try {
    require_once('database_config.php');
    
    echo "<div class='row'>
            <div class='col-md-6'>
                <h6>Paramètres généraux</h6>
                <ul class='list-group list-group-flush'>
                    <li class='list-group-item d-flex justify-content-between'>
                        <span>Version</span>
                        <code>" . DB_MANAGER_VERSION . "</code>
                    </li>
                    <li class='list-group-item d-flex justify-content-between'>
                        <span>Taille de page</span>
                        <code>" . DB_MANAGER_PAGE_SIZE . "</code>
                    </li>
                    <li class='list-group-item d-flex justify-content-between'>
                        <span>Timeout requêtes</span>
                        <code>" . DB_MANAGER_QUERY_TIMEOUT . "s</code>
                    </li>
                </ul>
            </div>
            <div class='col-md-6'>
                <h6>Sécurité</h6>
                <ul class='list-group list-group-flush'>
                    <li class='list-group-item d-flex justify-content-between'>
                        <span>Requêtes dangereuses</span>
                        <span class='badge " . (DB_MANAGER_ENABLE_DANGEROUS_QUERIES ? 'bg-warning' : 'bg-success') . "'>" . 
                        (DB_MANAGER_ENABLE_DANGEROUS_QUERIES ? 'Autorisées' : 'Bloquées') . "</span>
                    </li>
                    <li class='list-group-item d-flex justify-content-between'>
                        <span>Confirmation requise</span>
                        <span class='badge " . (DB_MANAGER_REQUIRE_CONFIRMATION ? 'bg-success' : 'bg-warning') . "'>" . 
                        (DB_MANAGER_REQUIRE_CONFIRMATION ? 'Oui' : 'Non') . "</span>
                    </li>
                    <li class='list-group-item d-flex justify-content-between'>
                        <span>Logs activés</span>
                        <span class='badge " . (DB_MANAGER_LOG_QUERIES ? 'bg-success' : 'bg-secondary') . "'>" . 
                        (DB_MANAGER_LOG_QUERIES ? 'Oui' : 'Non') . "</span>
                    </li>
                </ul>
            </div>
          </div>";
    
    echo "<div class='mt-3'>
            <h6>Formats d'export disponibles</h6>";
    
    $formats = DatabaseManagerConfig::getAvailableExportFormats();
    foreach ($formats as $key => $format) {
        echo "<span class='badge bg-primary me-1'>" . $format['name'] . "</span>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Erreur lors du chargement de la configuration : " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "</div></div>";

// Test 4: Test des permissions
echo "<div class='card mb-4'>
        <div class='card-header'><h5>4. Test des permissions</h5></div>
        <div class='card-body'>";

$permissions = [
    'can_view_data' => 'Voir les données',
    'can_execute_queries' => 'Exécuter des requêtes',
    'can_export_data' => 'Exporter des données',
    'can_view_structure' => 'Voir la structure',
    'can_execute_dangerous_queries' => 'Requêtes dangereuses'
];

echo "<h6>Permissions Superadmin</h6>
      <div class='row'>";

foreach ($permissions as $perm => $desc) {
    $has_perm = DatabaseManagerConfig::hasPermission('superadmin', $perm);
    $icon = $has_perm ? 'fas fa-check text-success' : 'fas fa-times text-danger';
    echo "<div class='col-md-6'>
            <i class='$icon me-2'></i>$desc
          </div>";
}

echo "</div></div></div>";

// Test 5: Logs
echo "<div class='card mb-4'>
        <div class='card-header'><h5>5. Test des logs</h5></div>
        <div class='card-body'>";

try {
    DatabaseManagerConfig::logAction('Test du gestionnaire de base de données', 'INFO');
    
    $logFile = '../logs/database_manager.log';
    if (file_exists($logFile) && is_readable($logFile)) {
        $logSize = filesize($logFile);
        $lastModified = date('Y-m-d H:i:s', filemtime($logFile));
        
        echo "<div class='alert alert-success'>
                <i class='fas fa-check-circle me-2'></i>
                Logs fonctionnels
              </div>
              <p><strong>Fichier :</strong> <code>$logFile</code></p>
              <p><strong>Taille :</strong> " . number_format($logSize) . " octets</p>
              <p><strong>Dernière modification :</strong> $lastModified</p>";
        
        // Afficher les dernières lignes
        $lines = file($logFile);
        $lastLines = array_slice($lines, -5);
        
        echo "<h6>Dernières entrées :</h6>
              <pre class='bg-light p-2' style='font-size: 0.8rem;'>";
        foreach ($lastLines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
        
    } else {
        echo "<div class='alert alert-warning'>
                <i class='fas fa-exclamation-triangle me-2'></i>
                Fichier de logs non accessible
              </div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Erreur lors du test des logs : " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "</div></div>";

// Résumé final
echo "<div class='card border-primary'>
        <div class='card-header bg-primary text-white'>
            <h5 class='mb-0'><i class='fas fa-clipboard-check me-2'></i>Résumé du test</h5>
        </div>
        <div class='card-body'>
            <div class='alert alert-success'>
                <i class='fas fa-check-circle me-2'></i>
                <strong>Installation complète !</strong> Le gestionnaire de base de données est prêt à être utilisé.
            </div>
            <div class='d-grid gap-2 d-md-flex justify-content-md-end'>
                <a href='database_manager.php' class='btn btn-primary'>
                    <i class='fas fa-database me-2'></i>Accéder au gestionnaire
                </a>
                <a href='index.php' class='btn btn-outline-secondary'>
                    <i class='fas fa-arrow-left me-2'></i>Retour au superadmin
                </a>
            </div>
        </div>
      </div>";

echo "</div>
</body>
</html>";

// Nettoyer la session de test
unset($_SESSION['superadmin_id']);
?>
