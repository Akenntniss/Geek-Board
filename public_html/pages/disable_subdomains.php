<?php
/**
 * Script pour désactiver le système de sous-domaines
 * et configurer GeekBoard pour utiliser uniquement le sélecteur de magasin
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction de journalisation
function log_message($message) {
    echo "<div style='margin: 5px 0; padding: 5px; border-left: 3px solid #0078e8;'>{$message}</div>";
    error_log("[DÉSACTIVATION SOUS-DOMAINES] " . $message);
}

echo "<h1>Désactivation du système de sous-domaines</h1>";
echo "<p>Ce script va configurer l'application pour utiliser uniquement le sélecteur de magasin dans la page de connexion.</p>";

// Vérifier si le script a déjà été exécuté
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // 1. Renommer le fichier .htaccess pour désactiver la redirection des sous-domaines
    $htaccess_path = __DIR__ . '/.htaccess';
    $htaccess_backup = __DIR__ . '/.htaccess.subdomain_backup_' . date('Y-m-d_H-i-s');
    
    if (file_exists($htaccess_path)) {
        if (copy($htaccess_path, $htaccess_backup)) {
            log_message("Sauvegarde du fichier .htaccess créée: " . basename($htaccess_backup));
            
            // Créer un nouveau fichier .htaccess sans les règles de sous-domaines
            $new_htaccess = "# GeekBoard - Configuration sans sous-domaines
# Mise à jour le " . date('Y-m-d H:i:s') . "

# Activation du module de réécriture
RewriteEngine On
RewriteBase /

# Ne pas appliquer les règles aux fichiers existants, répertoires ou liens symboliques
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d [OR]
RewriteCond %{REQUEST_FILENAME} -l
RewriteRule ^ - [L]

# Protection des répertoires sensibles
<FilesMatch \"^\\.\">
    Order allow,deny
    Deny from all
</FilesMatch>

# Compression Gzip
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json
</IfModule>

# Mise en cache des fichiers statiques
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg \"access plus 1 year\"
    ExpiresByType image/jpeg \"access plus 1 year\"
    ExpiresByType image/gif \"access plus 1 year\"
    ExpiresByType image/png \"access plus 1 year\"
    ExpiresByType image/svg+xml \"access plus 1 year\"
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
    ExpiresByType text/javascript \"access plus 1 month\"
    ExpiresByType text/html \"access plus 1 day\"
</IfModule>

# Configuration PHP
<IfModule mod_php.c>
    php_flag display_errors Off
    php_flag log_errors On
    php_value error_log /path/to/error.log
    php_value max_execution_time 60
    php_value memory_limit 256M
    php_value post_max_size 64M
    php_value upload_max_filesize 32M
</IfModule>";
            
            if (file_put_contents($htaccess_path, $new_htaccess)) {
                log_message("Nouveau fichier .htaccess créé sans les règles de sous-domaines");
            } else {
                log_message("ERREUR: Impossible de créer le nouveau fichier .htaccess");
            }
        } else {
            log_message("ERREUR: Impossible de sauvegarder le fichier .htaccess existant");
        }
    } else {
        log_message("Aucun fichier .htaccess trouvé, création d'un nouveau fichier");
        
        // Créer un nouveau fichier .htaccess de base
        $basic_htaccess = "# GeekBoard - Configuration de base
# Création le " . date('Y-m-d H:i:s') . "

# Activation du module de réécriture
RewriteEngine On
RewriteBase /

# Ne pas appliquer les règles aux fichiers existants, répertoires ou liens symboliques
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d [OR]
RewriteCond %{REQUEST_FILENAME} -l
RewriteRule ^ - [L]

# Protection des répertoires sensibles
<FilesMatch \"^\\.\">
    Order allow,deny
    Deny from all
</FilesMatch>";
        
        if (file_put_contents($htaccess_path, $basic_htaccess)) {
            log_message("Fichier .htaccess de base créé");
        } else {
            log_message("ERREUR: Impossible de créer le fichier .htaccess de base");
        }
    }
    
    // 2. Désactiver le chargement de la configuration des sous-domaines
    $subdomain_config_path = __DIR__ . '/config/subdomain_config.php';
    $subdomain_config_backup = __DIR__ . '/config/subdomain_config.php.backup_' . date('Y-m-d_H-i-s');
    
    if (file_exists($subdomain_config_path)) {
        if (copy($subdomain_config_path, $subdomain_config_backup)) {
            log_message("Sauvegarde du fichier subdomain_config.php créée");
            
            // Remplacer le contenu par une version désactivée
            $disabled_config = "<?php
/**
 * Configuration pour la gestion des sous-domaines - DÉSACTIVÉE
 * Ce fichier a été désactivé le " . date('Y-m-d H:i:s') . "
 */

// Fonctions de compatibilité pour éviter les erreurs
if (!function_exists('getSubdomain')) {
    function getSubdomain(\$domain_base = 'mdgeek.top') {
        // Fonction désactivée
        return null;
    }
}

if (!function_exists('loadShopBySubdomain')) {
    function loadShopBySubdomain(\$subdomain, \$pdo) {
        // Fonction désactivée
        return null;
    }
}

// Informer que le système de sous-domaines est désactivé
error_log(\"Système de sous-domaines désactivé - Utilisation du sélecteur de magasin uniquement\");
?>";
            
            if (file_put_contents($subdomain_config_path, $disabled_config)) {
                log_message("Configuration des sous-domaines désactivée");
            } else {
                log_message("ERREUR: Impossible de modifier le fichier subdomain_config.php");
            }
        } else {
            log_message("ERREUR: Impossible de sauvegarder le fichier subdomain_config.php");
        }
    } else {
        log_message("Fichier subdomain_config.php non trouvé, aucune action nécessaire");
    }
    
    // 3. Vérifier que les sélecteurs de magasin dans login.php fonctionnent correctement
    $login_path = __DIR__ . '/pages/login.php';
    
    if (file_exists($login_path)) {
        log_message("Fichier login.php trouvé, vérification de la présence du sélecteur de magasin");
        $login_content = file_get_contents($login_path);
        
        if (strpos($login_content, 'name="shop_id"') !== false) {
            log_message("Sélecteur de magasin trouvé dans le fichier login.php, aucune modification nécessaire");
        } else {
            log_message("ATTENTION: Sélecteur de magasin non trouvé dans login.php, vérifiez manuellement le fichier");
        }
    } else {
        log_message("ERREUR: Fichier login.php non trouvé");
    }
    
    echo "<h2>Opération terminée</h2>";
    echo "<p>Le système de sous-domaines a été désactivé avec succès.</p>";
    echo "<p>Désormais, les utilisateurs sélectionneront leur magasin via le menu déroulant lors de la connexion.</p>";
    echo "<p><strong>Note importante :</strong> Si vous avez configuré des règles spécifiques dans votre serveur web (Apache/Nginx) pour les sous-domaines, vous devrez les désactiver manuellement.</p>";
    
    echo "<p><a href='index.php' class='btn btn-primary'>Retour à l'accueil</a></p>";

} else {
    // Afficher un formulaire de confirmation
    echo "<div style='max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h2>Confirmation</h2>";
    echo "<p>Attention, cette action va :</p>";
    echo "<ul>";
    echo "<li>Désactiver le système de sous-domaines</li>";
    echo "<li>Créer des sauvegardes des fichiers modifiés</li>";
    echo "<li>Reconfigurer l'application pour utiliser uniquement le sélecteur de magasin</li>";
    echo "</ul>";
    echo "<p>Êtes-vous sûr de vouloir continuer ?</p>";
    echo "<a href='?confirm=yes' style='display: inline-block; padding: 10px 15px; background-color: #0078e8; color: white; text-decoration: none; border-radius: 5px;'>Oui, désactiver les sous-domaines</a>";
    echo "&nbsp;&nbsp;";
    echo "<a href='index.php' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>Annuler</a>";
    echo "</div>";
}

function add_style() {
    echo "<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        padding: 0;
        color: #333;
    }
    h1 {
        color: #0078e8;
        border-bottom: 2px solid #0078e8;
        padding-bottom: 10px;
    }
    h2 {
        color: #4a4a4a;
        margin-top: 25px;
    }
    .btn {
        display: inline-block;
        padding: 10px 15px;
        background-color: #0078e8;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 15px;
    }
    .btn:hover {
        background-color: #0056b3;
    }
    .btn-primary {
        background-color: #0078e8;
    }
    </style>";
}

add_style();
?> 