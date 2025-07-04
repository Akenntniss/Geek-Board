<?php
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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = trim($_POST['shop_name'] ?? '');
    $subdomain = trim($_POST['subdomain'] ?? '');
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = trim($_POST['admin_password'] ?? '');
    $logo = $_FILES['logo'] ?? null;
    
    // Validation
    if (empty($shop_name)) {
        $errors[] = 'Le nom du magasin est obligatoire.';
    }
    
    if (empty($subdomain)) {
        $errors[] = 'Le sous-domaine est obligatoire.';
    }
    
    if (empty($admin_username)) {
        $errors[] = 'Le nom d\'utilisateur admin est obligatoire.';
    }
    
    if (empty($admin_password)) {
        $errors[] = 'Le mot de passe admin est obligatoire.';
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
            // Générer automatiquement les informations de base de données
            $db_name = 'geekboard_' . strtolower($subdomain);
            $db_user = 'gb_' . strtolower($subdomain);
            $db_pass = bin2hex(random_bytes(12)); // Mot de passe aléatoire sécurisé
            $db_host = 'localhost';
            
            // Traitement du logo
            $logo_path = null;
            if ($logo && $logo['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../assets/images/logo/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($logo['name'], PATHINFO_EXTENSION);
                $logo_filename = $subdomain . '_logo.' . $file_extension;
                $logo_path = $upload_dir . $logo_filename;
                
                if (move_uploaded_file($logo['tmp_name'], $logo_path)) {
                    $logo_path = 'assets/images/logo/' . $logo_filename;
                }
            }
            
            // Connexion à MySQL pour créer la base de données
            $pdo_mysql = new PDO("mysql:host=$db_host", 'root', '');
            $pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer la base de données
            $pdo_mysql->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            
            // Connexion à la nouvelle base
            $shop_pdo = new PDO("mysql:host=$db_host;dbname=$db_name", 'root', '');
            $shop_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Insertion dans la base principale des shops
            $stmt = $pdo->prepare("INSERT INTO shops (name, subdomain, logo, db_host, db_port, db_name, db_user, db_pass, active) VALUES (?, ?, ?, ?, '3306', ?, ?, ?, 1)");
            $stmt->execute([$shop_name, $subdomain, $logo_path, $db_host, $db_name, $db_user, $db_pass]);
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
            $sql_queries = array_filter(
                array_map('trim', explode(';', $sql_content)),
                function($query) { return !empty($query) && strtoupper(substr($query, 0, 6)) === 'CREATE'; }
            );
            
            $created_tables = [];
            
            // Désactiver la vérification des clés étrangères temporairement
            $shop_pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            foreach ($sql_queries as $sql_query) {
                try {
                    $shop_pdo->exec($sql_query);
                    if (preg_match('/CREATE TABLE `?([^`\s]+)`?\s*\(/i', $sql_query, $matches)) {
                        $created_tables[] = $matches[1];
                    }
                } catch (PDOException $e) {
                    // Continuer même en cas d'erreur sur une table
                }
            }
            
            // Réactiver la vérification des clés étrangères
            $shop_pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Créer l'utilisateur admin
            $password_md5 = md5($admin_password);
            $admin_full_name = 'Administrateur ' . ucfirst($subdomain);
            
            $shop_pdo->exec("INSERT INTO users (username, password, full_name, role, shop_id, created_at) VALUES ('$admin_username', '$password_md5', '$admin_full_name', 'admin', '$shop_id', NOW())");
            
            $success_data = [
                'shop_name' => htmlspecialchars($shop_name),
                'subdomain' => htmlspecialchars($subdomain),
                'url' => 'https://' . htmlspecialchars($subdomain) . '.mdgeek.top',
                'logo_path' => $logo_path,
                'db_name' => $db_name,
                'db_user' => $db_user,
                'db_pass' => $db_pass,
                'admin_username' => $admin_username,
                'admin_password' => $admin_password,
                'shop_id' => $shop_id,
                'tables_created' => count($created_tables)
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
        }
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 40px auto;
            max-width: 800px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        .form-container {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
            border-radius: 0 10px 10px 0;
            color: #6c757d;
        }
        .btn-create {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: transform 0.3s ease;
            width: 100%;
        }
        .btn-create:hover {
            transform: translateY(-2px);
            color: white;
        }
        .success-container {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .info-card {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            backdrop-filter: blur(10px);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            opacity: 0.9;
        }
        .info-value {
            font-family: 'Courier New', monospace;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .test-button {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        .test-button:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }
        .logo-preview {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <div class="header">
                <h1><i class="fas fa-store me-3"></i>Créer un nouveau magasin</h1>
                <p class="mb-0">Configuration automatique complète</p>
            </div>
            
            <?php if ($success_data): ?>
                <div class="form-container">
                    <div class="success-container">
                        <h3><i class="fas fa-check-circle me-2"></i>Magasin créé avec succès !</h3>
                        <p class="mb-4">Votre magasin <strong><?php echo $success_data['shop_name']; ?></strong> est maintenant opérationnel avec <?php echo $success_data['tables_created']; ?> tables créées.</p>
                        
                        <div class="info-card">
                            <h5><i class="fas fa-database me-2"></i>Informations de la base de données</h5>
                            <div class="info-row">
                                <span class="info-label">Database Name:</span>
                                <span class="info-value"><?php echo $success_data['db_name']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Database User:</span>
                                <span class="info-value"><?php echo $success_data['db_user']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Database Password:</span>
                                <span class="info-value"><?php echo $success_data['db_pass']; ?></span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h5><i class="fas fa-user-shield me-2"></i>Utilisateur Administrateur</h5>
                            <div class="info-row">
                                <span class="info-label">Username:</span>
                                <span class="info-value"><?php echo $success_data['admin_username']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Password:</span>
                                <span class="info-value"><?php echo $success_data['admin_password']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">URL du magasin:</span>
                                <span class="info-value"><?php echo $success_data['url']; ?></span>
                            </div>
                        </div>
                        
                        <a href="<?php echo $success_data['url']; ?>" target="_blank" class="test-button">
                            <i class="fas fa-external-link-alt me-2"></i>Tester le magasin
                        </a>
                        <a href="?" class="test-button ms-3">
                            <i class="fas fa-plus me-2"></i>Créer un autre magasin
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-container">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Erreurs détectées</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Configuration automatique</h6>
                        <p class="mb-0">La base de données, les 82 tables GeekBoard et toutes les configurations seront créées automatiquement.</p>
                    </div>
                    
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="shop_name" class="form-label">
                                        <i class="fas fa-store me-2"></i>Nom du magasin *
                                    </label>
                                    <input type="text" class="form-control" id="shop_name" name="shop_name" 
                                           value="<?php echo htmlspecialchars($_POST['shop_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subdomain" class="form-label">
                                        <i class="fas fa-link me-2"></i>Sous-domaine *
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="subdomain" name="subdomain" 
                                               value="<?php echo htmlspecialchars($_POST['subdomain'] ?? ''); ?>" required>
                                        <span class="input-group-text">.mdgeek.top</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="logo" class="form-label">
                                <i class="fas fa-image me-2"></i>Logo (optionnel)
                            </label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_username" class="form-label">
                                        <i class="fas fa-user me-2"></i>Utilisateur Admin *
                                    </label>
                                    <input type="text" class="form-control" id="admin_username" name="admin_username" 
                                           value="<?php echo htmlspecialchars($_POST['admin_username'] ?? 'admin'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Mot de passe Admin *
                                    </label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-create">
                            <i class="fas fa-rocket me-2"></i>Créer le magasin complet
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Générer automatiquement le sous-domaine basé sur le nom du magasin
        document.getElementById('shop_name').addEventListener('input', function() {
            const shopName = this.value.toLowerCase()
                .replace(/[^a-z0-9]/g, '')
                .substring(0, 15);
            document.getElementById('subdomain').value = shopName;
        });
        
        // Prévisualisation du logo
        document.getElementById('logo').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.logo-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'logo-preview';
                        document.getElementById('logo').parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 