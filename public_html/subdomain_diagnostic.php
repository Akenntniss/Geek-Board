<?php
// Script de diagnostic complet pour les sous-domaines
header('Content-Type: text/plain; charset=utf-8');

echo "DIAGNOSTIC DES SOUS-DOMAINES\n";
echo "===========================\n\n";

// 1. Informations sur la requête
echo "INFORMATIONS DE REQUÊTE:\n";
echo "Hôte: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "\n";
echo "URI: " . ($_SERVER['REQUEST_URI'] ?? 'Non défini') . "\n";
echo "IP client: " . ($_SERVER['REMOTE_ADDR'] ?? 'Non défini') . "\n";
echo "Méthode: " . ($_SERVER['REQUEST_METHOD'] ?? 'Non défini') . "\n";
echo "Serveur: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non défini') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Non défini') . "\n";

// 2. Détection du sous-domaine
echo "\nDÉTECTION DU SOUS-DOMAINE:\n";
$host = $_SERVER['HTTP_HOST'] ?? '';
$domain_base = 'mdgeek.top';

if ($host === $domain_base) {
    echo "Aucun sous-domaine détecté (domaine principal).\n";
} elseif (strpos($host, $domain_base) !== false) {
    $subdomain = str_replace('.' . $domain_base, '', $host);
    echo "Sous-domaine détecté: $subdomain\n";
} else {
    echo "Domaine non reconnu: $host\n";
}

// 3. Variables d'environnement Apache (si disponibles)
echo "\nVARIABLES D'ENVIRONNEMENT APACHE:\n";
echo "SUBDOMAIN (défini dans .htaccess): " . (getenv('SUBDOMAIN') ?: 'Non défini') . "\n";
echo "HTTP_X_SUBDOMAIN (défini dans .htaccess): " . (getenv('HTTP_X_SUBDOMAIN') ?: 'Non défini') . "\n";

// 4. Vérifier l'accès aux fichiers .htaccess
echo "\nVÉRIFICATION DES FICHIERS DE CONFIGURATION:\n";
$htaccess_path = __DIR__ . '/.htaccess';
if (file_exists($htaccess_path)) {
    echo ".htaccess principal existe\n";
    echo "Taille: " . filesize($htaccess_path) . " octets\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($htaccess_path)), -4) . "\n";
    echo "Propriétaire: " . posix_getpwuid(fileowner($htaccess_path))['name'] . "\n";
    echo "Groupe: " . posix_getgrgid(filegroup($htaccess_path))['name'] . "\n";
} else {
    echo ".htaccess principal n'existe pas!\n";
}

// 5. Test de connexion à la base de données
echo "\nTEST DE CONNEXION À LA BASE DE DONNÉES:\n";
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
    try {
        $pdo = getMainDBConnection();
        echo "Connexion à la base de données principale: SUCCÈS\n";
        
        // Vérifier la table shops et les sous-domaines
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'shops'");
            if ($stmt->rowCount() > 0) {
                echo "Table 'shops' existe dans la base de données\n";
                
                // Vérifier la structure de la table
                $stmt = $pdo->query("SHOW COLUMNS FROM shops LIKE 'subdomain'");
                if ($stmt->rowCount() > 0) {
                    echo "Colonne 'subdomain' existe dans la table 'shops'\n";
                    
                    // Récupérer les sous-domaines configurés
                    $stmt = $pdo->query("SELECT id, name, subdomain FROM shops WHERE subdomain IS NOT NULL");
                    $shops = $stmt->fetchAll();
                    
                    if (count($shops) > 0) {
                        echo "Sous-domaines configurés (" . count($shops) . "):\n";
                        foreach ($shops as $shop) {
                            echo "- ID: {$shop['id']}, Nom: {$shop['name']}, Sous-domaine: {$shop['subdomain']}\n";
                        }
                    } else {
                        echo "Aucun sous-domaine configuré dans la base de données\n";
                    }
                } else {
                    echo "ERREUR: Colonne 'subdomain' n'existe pas dans la table 'shops'\n";
                }
            } else {
                echo "ERREUR: Table 'shops' n'existe pas dans la base de données\n";
            }
        } catch (PDOException $e) {
            echo "ERREUR lors de la vérification de la structure: " . $e->getMessage() . "\n";
        }
    } catch (Exception $e) {
        echo "ERREUR de connexion à la base de données: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERREUR: Fichier de configuration de la base de données non trouvé\n";
}

// 6. Test du système de fichiers
echo "\nTEST DU SYSTÈME DE FICHIERS:\n";
$handlers_dir = __DIR__ . '/subdomain_handler.php';
if (file_exists($handlers_dir)) {
    echo "Gestionnaire de sous-domaine existe\n";
    echo "Taille: " . filesize($handlers_dir) . " octets\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($handlers_dir)), -4) . "\n";
} else {
    echo "ERREUR: Gestionnaire de sous-domaine non trouvé!\n";
}

// 7. Test des modèles
echo "\nTEST DES MODÈLES DE PAGE:\n";
$templates_dir = __DIR__ . '/templates';
if (is_dir($templates_dir)) {
    echo "Répertoire des modèles existe\n";
    if (file_exists($templates_dir . '/shop_not_found.php')) {
        echo "Modèle 'shop_not_found.php' existe\n";
    } else {
        echo "AVERTISSEMENT: Modèle 'shop_not_found.php' n'existe pas\n";
    }
    
    if (file_exists($templates_dir . '/error.php')) {
        echo "Modèle 'error.php' existe\n";
    } else {
        echo "AVERTISSEMENT: Modèle 'error.php' n'existe pas\n";
    }
} else {
    echo "AVERTISSEMENT: Répertoire des modèles n'existe pas\n";
}

// 8. Test session
echo "\nTEST DE SESSION:\n";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "Session active. ID: " . session_id() . "\n";
    echo "Contenu de la session:\n";
    foreach ($_SESSION as $key => $value) {
        if (is_array($value)) {
            echo "- $key: [ARRAY]\n";
        } else {
            echo "- $key: $value\n";
        }
    }
} else {
    echo "Aucune session active\n";
}

echo "\nDIAGNOSTIC TERMINÉ\n";
?> 