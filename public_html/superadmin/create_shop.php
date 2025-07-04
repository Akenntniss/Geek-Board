<?php
// Page de création d'un nouveau magasin - VERSION SIMPLIFIÉE
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

$pdo = getMainDBConnection();

$errors = [];
$success_data = null;

/**
 * Fonction pour mettre à jour le mapping des sous-domaines dans login_auto.php
 */
function updateSubdomainMapping($subdomain, $shop_id, $shop_name, $db_name) {
    $login_auto_path = __DIR__ . '/../pages/login_auto.php';
    
    try {
        // Connexion à la base de données principale pour récupérer tous les shops
        $pdo_general = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
        $pdo_general->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Récupérer tous les shops actifs
        $stmt = $pdo_general->prepare("SELECT id, name, subdomain, db_name FROM shops WHERE active = 1 AND subdomain IS NOT NULL AND subdomain != '' AND subdomain != 'general' ORDER BY id");
        $stmt->execute();
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lire le fichier actuel
        $content = file_get_contents($login_auto_path);
        if ($content === false) {
            error_log("Erreur : Impossible de lire le fichier login_auto.php");
            return false;
        }
        
        // Créer le nouveau tableau shop_mapping complet
        $new_mapping_lines = [];
        
        // Ajouter les entrées fixes originales
        $new_mapping_lines[] = "    'pscannes' => ['id' => 2, 'name' => 'PScannes', 'db' => 'geekboard_pscannes'],";
        $new_mapping_lines[] = "    'psphonac' => ['id' => 6, 'name' => 'PSPHONAC', 'db' => 'geekboard_psphonac'],";
        
        // Ajouter toutes les entrées de la base de données
        foreach ($shops as $shop) {
            $escaped_name = addslashes($shop['name']);
            $new_mapping_lines[] = "    '" . $shop['subdomain'] . "' => ['id' => " . $shop['id'] . ", 'name' => '" . $escaped_name . "', 'db' => '" . $shop['db_name'] . "'],";
        }
        
        // Enlever la virgule de la dernière ligne
        $last_index = count($new_mapping_lines) - 1;
        $new_mapping_lines[$last_index] = rtrim($new_mapping_lines[$last_index], ',');
        
        // Construire le nouveau tableau
        $new_mapping = "// Mapping des sous-domaines vers les infos de magasin\n\$shop_mapping = [\n" . implode("\n", $new_mapping_lines) . "\n];";
        
        // Remplacer le tableau existant
        $pattern = '/\/\/\s*Mapping des sous-domaines.*?\$shop_mapping\s*=\s*\[.*?];/ms';
        $new_content = preg_replace($pattern, $new_mapping, $content);
        
        // Vérifier si le remplacement a fonctionné
        if ($new_content === $content) {
            error_log("Erreur : Pattern du mapping non trouvé dans login_auto.php");
            return false;
        }
        
        // Écrire le fichier modifié
        if (file_put_contents($login_auto_path, $new_content) !== false) {
            error_log("Mapping synchronisé avec succès - " . count($shops) . " magasins actifs");
            return true;
        } else {
            error_log("Erreur : Impossible d'écrire dans le fichier login_auto.php");
            return false;
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la synchronisation du mapping : " . $e->getMessage());
        return false;
    }
}

function updateSSLCertificate($subdomain) {
    try {
        // Déterminer le chemin du certificat actuel
        $cert_path = "/etc/letsencrypt/live/mdgeek.top-0001/fullchain.pem";
        if (!file_exists($cert_path)) {
            $cert_path = "/etc/letsencrypt/live/mdgeek.top/fullchain.pem";
        }
        
        // Récupérer les domaines actuels du certificat
        $cert_info = shell_exec("openssl x509 -in $cert_path -text -noout | grep DNS:");
        if (!$cert_info) {
            error_log("Erreur : Impossible de lire le certificat SSL existant dans $cert_path");
            return false;
        }
        
        // Extraire les domaines existants
        preg_match_all('/DNS:([^,\s]+)/', $cert_info, $matches);
        $existing_domains = $matches[1];
        
        // Ajouter le nouveau sous-domaine s'il n'existe pas déjà
        $new_domain = $subdomain . '.mdgeek.top';
        if (!in_array($new_domain, $existing_domains)) {
            $existing_domains[] = $new_domain;
        } else {
            error_log("Domaine $new_domain déjà présent dans le certificat SSL");
            return true;
        }
        
        // Créer un script temporaire pour exécuter certbot avec les bonnes permissions
        $temp_script = "/tmp/update_ssl_" . uniqid() . ".sh";
        
        // Construire la commande certbot avec tous les domaines
        $domain_args = '';
        foreach ($existing_domains as $domain) {
            $domain_args .= '-d ' . escapeshellarg($domain) . ' ';
        }
        
        // Créer le contenu du script
        $script_content = "#!/bin/bash\n";
        $script_content .= "# Script temporaire pour mise à jour SSL\n";
        $script_content .= "# Exécuter en tant que root pour éviter les problèmes de permissions\n";
        $script_content .= "/usr/bin/certbot certonly --nginx {$domain_args} --expand --non-interactive --agree-tos -m admin@mdgeek.top 2>&1\n";
        $script_content .= "CERTBOT_RESULT=\$?\n";
        $script_content .= "if [ \$CERTBOT_RESULT -eq 0 ]; then\n";
        $script_content .= "    /usr/bin/systemctl reload nginx 2>&1\n";
        $script_content .= "    echo \"SSL_SUCCESS: Certificat SSL mis à jour avec succès pour {$new_domain}\"\n";
        $script_content .= "else\n";
        $script_content .= "    echo \"SSL_ERROR: Erreur lors de la mise à jour du certificat SSL\"\n";
        $script_content .= "fi\n";
        $script_content .= "exit \$CERTBOT_RESULT\n";
        
        // Écrire le script temporaire
        if (file_put_contents($temp_script, $script_content) === false) {
            error_log("Erreur : Impossible de créer le script temporaire SSL");
            return false;
        }
        
        // Rendre le script exécutable
        chmod($temp_script, 0755);
        
        // Exécuter le script avec sudo
        $cmd = "sudo bash " . escapeshellarg($temp_script) . " 2>&1";
        error_log("Exécution de la commande SSL : " . $cmd);
        $output = shell_exec($cmd);
        
        // Nettoyer le script temporaire
        unlink($temp_script);
        
        // Vérifier le résultat
        if (strpos($output, 'SSL_SUCCESS:') !== false || 
            strpos($output, 'Successfully received certificate') !== false || 
            strpos($output, 'Certificate not yet due for renewal') !== false) {
            error_log("Certificat SSL mis à jour avec succès pour : " . $new_domain . " - Output: " . substr($output, 0, 200));
            return true;
        } else {
            error_log("Erreur lors de la mise à jour du certificat SSL : " . $output);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du certificat SSL : " . $e->getMessage());
        return false;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = trim($_POST['shop_name'] ?? '');
    $subdomain = trim($_POST['subdomain'] ?? '');
    
    // Validation
    if (empty($shop_name)) {
        $errors[] = 'Le nom du magasin est obligatoire.';
    }
    
    if (empty($subdomain)) {
        $errors[] = 'Le sous-domaine est obligatoire.';
    }
    
    // Vérifier si le sous-domaine existe déjà
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM shops WHERE subdomain = ?");
        $stmt->execute([$subdomain]);
        if ($stmt->fetch()) {
            $errors[] = 'Ce sous-domaine existe déjà.';
        }
    }
    
    // Si pas d'erreurs, créer le magasin
    if (empty($errors)) {
        try {
            // Informations de base de données avec mot de passe fixe
            $db_name = 'geekboard_' . strtolower($subdomain);
            $db_user = 'gb_' . strtolower($subdomain);
            $db_pass = 'Admin123!'; // Mot de passe fixe comme demandé
            $db_host = 'localhost';
            
            // Connexion à MySQL pour créer la base de données
            $pdo_mysql = new PDO("mysql:host=$db_host", 'root', 'Mamanmaman01#');
            $pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer la base de données
            $pdo_mysql->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            
            // Supprimer l'utilisateur MySQL s'il existe déjà
            try {
                $pdo_mysql->exec("DROP USER IF EXISTS '$db_user'@'localhost'");
            } catch (PDOException $e) {
                // Ignorer l'erreur si l'utilisateur n'existe pas
            }
            
            // Créer l'utilisateur MySQL pour ce magasin
            $pdo_mysql->exec("CREATE USER '$db_user'@'localhost' IDENTIFIED BY '$db_pass'");
            $pdo_mysql->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'");
            
            // Donner aussi les permissions à l'utilisateur geekboard_user pour la compatibilité avec login_auto.php
            $pdo_mysql->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO 'geekboard_user'@'localhost'");
            $pdo_mysql->exec("FLUSH PRIVILEGES");
            
            // Connexion à la nouvelle base
            $shop_pdo = new PDO("mysql:host=$db_host;dbname=$db_name", 'root', 'Mamanmaman01#');
            $shop_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Insertion dans la base principale des shops
            $stmt = $pdo->prepare("INSERT INTO shops (name, subdomain, db_host, db_port, db_name, db_user, db_pass, active) VALUES (?, ?, ?, '3306', ?, ?, ?, 1)");
            $stmt->execute([$shop_name, $subdomain, $db_host, $db_name, $db_user, $db_pass]);
            $shop_id = $pdo->lastInsertId();
            
            // Charger et exécuter le script SQL complet
            $sql_file = __DIR__ . '/geekboard_complete_structure.sql';
            if (!file_exists($sql_file)) {
                $sql_file = './geekboard_complete_structure.sql';
                if (!file_exists($sql_file)) {
                    throw new Exception("Fichier de structure SQL introuvable");
                }
            }
            
            $sql_content = file_get_contents($sql_file);
            if ($sql_content === false) {
                throw new Exception("Impossible de lire le fichier SQL");
            }
            
            // Nettoyer et diviser les requêtes SQL
            $sql_content = preg_replace('/^--.*$/m', '', $sql_content);
            $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
            $all_queries = array_filter(
                array_map('trim', explode(';', $sql_content)),
                function($query) { 
                    return !empty($query) && (
                        strtoupper(substr($query, 0, 6)) === 'CREATE' || 
                        strtoupper(substr($query, 0, 5)) === 'ALTER' ||
                        strtoupper(substr($query, 0, 6)) === 'INSERT'
                    );
                }
            );
            
            // Tables essentielles dont on veut copier les données
            $essential_tables = [
                'statuts', 'statut_categories', 'sms_templates', 'sms_template_variables',
                'notification_types', 'parametres', 'parametres_gardiennage', 'parrainage_config',
                'kb_categories', 'kb_tags', 'fournisseurs', 'marges_reference'
            ];
            
            // Séparer les requêtes CREATE, ALTER et INSERT pour les exécuter dans l'ordre
            $create_queries = [];
            $alter_queries = [];
            $insert_queries = [];
            
            foreach ($all_queries as $query) {
                if (strtoupper(substr($query, 0, 6)) === 'CREATE') {
                    $create_queries[] = $query;
                } elseif (strtoupper(substr($query, 0, 5)) === 'ALTER') {
                    $alter_queries[] = $query;
                } elseif (strtoupper(substr($query, 0, 6)) === 'INSERT') {
                    // Vérifier si c'est une table essentielle
                    foreach ($essential_tables as $table) {
                        if (preg_match('/INSERT INTO `?' . preg_quote($table, '/') . '`?\s/i', $query)) {
                            $insert_queries[] = $query;
                            break;
                        }
                    }
                }
            }
            
            $created_tables = [];
            
            // Désactiver la vérification des clés étrangères temporairement
            $shop_pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Exécuter d'abord les requêtes CREATE TABLE
            foreach ($create_queries as $sql_query) {
                try {
                    $shop_pdo->exec($sql_query);
                    if (preg_match('/CREATE TABLE `?([^`\s]+)`?\s*\(/i', $sql_query, $matches)) {
                        $created_tables[] = $matches[1];
                    }
                } catch (PDOException $e) {
                    error_log("Erreur CREATE: " . $e->getMessage() . " - Requête: " . substr($sql_query, 0, 100));
                }
            }
            
            // Puis exécuter les requêtes ALTER TABLE (pour AUTO_INCREMENT, INDEX, etc.)
            foreach ($alter_queries as $sql_query) {
                try {
                    $shop_pdo->exec($sql_query);
                } catch (PDOException $e) {
                    error_log("Erreur ALTER: " . $e->getMessage() . " - Requête: " . substr($sql_query, 0, 100));
                }
            }
            
            // Enfin, insérer les données essentielles
            foreach ($insert_queries as $sql_query) {
                try {
                    $shop_pdo->exec($sql_query);
                } catch (PDOException $e) {
                    error_log("Erreur INSERT: " . $e->getMessage() . " - Requête: " . substr($sql_query, 0, 100));
                }
            }
            
            // Réactiver la vérification des clés étrangères
            $shop_pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Créer uniquement l'utilisateur admin
            $admin_username = 'admin';
            $admin_password = 'Admin123!';
            $password_md5 = md5($admin_password); // Utiliser MD5 pour la compatibilité avec login_auto.php
            $admin_full_name = 'Administrateur ' . ucfirst($subdomain);
            
            $shop_pdo->exec("INSERT INTO users (username, password, full_name, role, created_at) VALUES ('$admin_username', '$password_md5', '$admin_full_name', 'admin', NOW())");
            
            // Ajouter le sous-domaine au mapping dans login_auto.php
            $mapping_updated = updateSubdomainMapping($subdomain, $shop_id, $shop_name, $db_name);
            
            // Mettre à jour le certificat SSL pour inclure le nouveau sous-domaine
            $ssl_updated = updateSSLCertificate($subdomain);
            
            $success_data = [
                'shop_name' => htmlspecialchars($shop_name),
                'subdomain' => htmlspecialchars($subdomain),
                'url' => 'https://' . htmlspecialchars($subdomain) . '.mdgeek.top',
                'db_name' => $db_name,
                'db_user' => $db_user,
                'db_pass' => $db_pass,
                'admin_username' => $admin_username,
                'admin_password' => $admin_password,
                'shop_id' => $shop_id,
                'tables_created' => count($created_tables),
                'data_inserted' => count($insert_queries),
                'mapping_updated' => $mapping_updated,
                'ssl_updated' => $ssl_updated
            ];
            
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Créer un nouveau magasin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            margin: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .card-header h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .card-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .card-body {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }
        .input-group-text {
            background: #667eea;
            border: 2px solid #667eea;
            border-left: none;
            border-radius: 0 12px 12px 0;
            color: white;
            font-weight: 600;
            padding: 15px 20px;
        }
        .btn-create {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 18px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        .btn-create:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .success-container {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .success-container h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .info-card {
            background: rgba(255,255,255,0.15);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            backdrop-filter: blur(10px);
            text-align: left;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            opacity: 0.9;
            font-size: 1rem;
        }
        .info-value {
            font-family: 'Courier New', monospace;
            background: rgba(255,255,255,0.25);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
        }
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-action {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .btn-action:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        .alert {
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .alert-danger {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }
        .alert-info {
            background: #d1ecf1;
            border: 2px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <?php if ($success_data): ?>
            <div class="success-container">
                <h2><i class="fas fa-check-circle"></i>Magasin créé avec succès !</h2>
                <p style="font-size: 1.1rem; margin-bottom: 20px;">
                    Votre magasin <strong><?php echo $success_data['shop_name']; ?></strong> est maintenant opérationnel avec <?php echo $success_data['tables_created']; ?> tables créées et <?php echo $success_data['data_inserted']; ?> jeux de données de configuration insérés.
                </p>
                
                <?php if ($success_data['mapping_updated']): ?>
                    <div style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3); border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                        <i class="fas fa-check-circle" style="color: #90EE90; margin-right: 10px;"></i>
                        <strong>Mapping automatique mis à jour !</strong><br>
                        <small>Le sous-domaine <code><?php echo $success_data['subdomain']; ?></code> a été ajouté au système de connexion automatique.</small>
                    </div>
                <?php else: ?>
                    <div style="background: rgba(255,255,255,0.15); border: 2px solid rgba(255,165,0,0.5); border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-triangle" style="color: #FFD700; margin-right: 10px;"></i>
                        <strong>Attention :</strong> Le mapping automatique n'a pas pu être mis à jour.<br>
                        <small>Vous devrez ajouter manuellement le sous-domaine au fichier login_auto.php</small>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_data['ssl_updated']): ?>
                    <div style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                        <i class="fas fa-lock" style="color: #90EE90; margin-right: 10px;"></i>
                        <strong>Certificat SSL mis à jour !</strong><br>
                        <small>Le sous-domaine <code><?php echo $success_data['subdomain']; ?>.mdgeek.top</code> a été ajouté au certificat SSL. Connexion HTTPS sécurisée disponible immédiatement.</small>
                    </div>
                <?php else: ?>
                    <div style="background: rgba(255,255,255,0.15); border: 2px solid rgba(255,165,0,0.5); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle" style="color: #FFD700; margin-right: 10px;"></i>
                        <strong>Attention :</strong> Le certificat SSL n'a pas pu être mis à jour automatiquement.<br>
                        <small>Vous devrez peut-être ajouter manuellement le sous-domaine au certificat SSL pour éviter les erreurs HTTPS.</small>
                    </div>
                <?php endif; ?>
                
                <div class="info-card">
                    <h4 style="margin-bottom: 20px;"><i class="fas fa-database me-2"></i>Informations de connexion</h4>
                    <div class="info-item">
                        <span class="info-label">Nom du magasin :</span>
                        <span class="info-value"><?php echo $success_data['shop_name']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Sous-domaine :</span>
                        <span class="info-value"><?php echo $success_data['subdomain']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">URL :</span>
                        <span class="info-value"><?php echo $success_data['url']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Base de données :</span>
                        <span class="info-value"><?php echo $success_data['db_name']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Utilisateur admin :</span>
                        <span class="info-value"><?php echo $success_data['admin_username']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mot de passe :</span>
                        <span class="info-value"><?php echo $success_data['admin_password']; ?></span>
                    </div>
                </div>
                

                
                <div class="action-buttons">
                    <a href="<?php echo $success_data['url']; ?>" target="_blank" class="btn-action">
                        <i class="fas fa-external-link-alt"></i>Accéder au magasin
                    </a>
                    <a href="?" class="btn-action">
                        <i class="fas fa-plus"></i>Créer un autre magasin
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card-header">
                <h1><i class="fas fa-store"></i>Créer un magasin</h1>
                <p>Configuration automatique complète</p>
            </div>
            
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs détectées</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Information</h6>
                    <p class="mb-0">
                        La base de données sera créée automatiquement avec le mot de passe <strong>Admin123!</strong>
                        pour la base de données et l'utilisateur administrateur.
                    </p>
                </div>
                
                <form method="post">
                    <div class="form-group">
                        <label for="shop_name" class="form-label">
                            <i class="fas fa-store"></i>Nom du magasin
                        </label>
                        <input type="text" class="form-control" id="shop_name" name="shop_name" 
                               value="<?php echo htmlspecialchars($_POST['shop_name'] ?? ''); ?>" 
                               placeholder="Ex: iPhone Repair Shop" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subdomain" class="form-label">
                            <i class="fas fa-link"></i>Sous-domaine
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="subdomain" name="subdomain" 
                                   value="<?php echo htmlspecialchars($_POST['subdomain'] ?? ''); ?>" 
                                   placeholder="Ex: iphone-repair" required>
                            <span class="input-group-text">.mdgeek.top</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-create">
                        <i class="fas fa-rocket me-2"></i>Créer la boutique
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Générer automatiquement le sous-domaine basé sur le nom du magasin
        document.getElementById('shop_name').addEventListener('input', function() {
            const shopName = this.value.toLowerCase()
                .replace(/[^a-z0-9\s]/g, '') // Enlever les caractères spéciaux sauf espaces
                .replace(/\s+/g, '-') // Remplacer les espaces par des tirets
                .substring(0, 20); // Limiter à 20 caractères
            document.getElementById('subdomain').value = shopName;
        });
        
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.card-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(50px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html> 