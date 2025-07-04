<?php
// Page de création d'un nouveau magasin - VERSION CORRIGÉE
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

$pdo = getMainDBConnection();

// Variables d'initialisation
$name = '';
$description = '';
$subdomain = '';
$db_name = '';
$db_user = '';
$db_pass = '';
$errors = [];
$success_data = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subdomain = trim($_POST['subdomain'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Le nom du magasin est obligatoire.';
    }
    
    if (empty($subdomain)) {
        $errors[] = 'Le sous-domaine est obligatoire.';
    }
    
    if (empty($db_name)) {
        $errors[] = 'Le nom de la base de données est obligatoire.';
    }
    
    if (empty($db_user)) {
        $errors[] = 'L\'utilisateur de la base de données est obligatoire.';
    }
    
    if (empty($db_pass)) {
        $errors[] = 'Le mot de passe de la base de données est obligatoire.';
    }
    
    // Si pas d'erreurs, créer le magasin
    if (empty($errors)) {
        try {
            // Connexion à la base du magasin
            $shop_pdo = new PDO("mysql:host=localhost;dbname=$db_name", $db_user, $db_pass);
            $shop_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Insertion dans la base principale
            $stmt = $pdo->prepare("INSERT INTO shops (name, description, subdomain, db_host, db_port, db_name, db_user, db_pass, active) VALUES (?, ?, ?, 'localhost', '3306', ?, ?, ?, 1)");
            $stmt->execute([$name, $description, $subdomain, $db_name, $db_user, $db_pass]);
            $shop_id = $pdo->lastInsertId();
            
            // Créer la table users avec la VRAIE structure GeekBoard
            $shop_pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id int NOT NULL AUTO_INCREMENT,
                username varchar(50) NOT NULL,
                password varchar(255) NOT NULL,
                full_name varchar(100) NOT NULL,
                role enum('admin','technicien') NOT NULL,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                techbusy int NULL DEFAULT 0,
                active_repair_id int NULL DEFAULT NULL,
                shop_id int NULL DEFAULT NULL,
                score_total int NULL DEFAULT 0,
                niveau int NULL DEFAULT 1,
                points_experience int NULL DEFAULT 0,
                derniere_activite datetime NULL DEFAULT NULL,
                statut_presence enum('present','absent','pause','mission_externe') NULL DEFAULT 'absent',
                preference_notifications longtext NULL,
                timezone varchar(50) NULL DEFAULT 'Europe/Paris',
                productivity_target decimal(5,2) NULL DEFAULT 80.00,
                PRIMARY KEY (id),
                UNIQUE KEY username (username),
                INDEX shop_id (shop_id),
                INDEX score_total (score_total),
                INDEX niveau (niveau),
                INDEX derniere_activite (derniere_activite),
                INDEX statut_presence (statut_presence)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            
            // Créer l'utilisateur admin avec MD5 
            $default_password = 'admin123';
            $password_md5 = md5($default_password);
            $admin_full_name = 'Administrateur ' . ucfirst($subdomain);
            
            $shop_pdo->exec("INSERT INTO users (username, password, full_name, role, shop_id, created_at) VALUES ('admin', '$password_md5', '$admin_full_name', 'admin', '$shop_id', NOW())");
            
            $success_data = [
                'name' => htmlspecialchars($name),
                'subdomain' => htmlspecialchars($subdomain),
                'db_name' => htmlspecialchars($db_name),
                'url' => 'https://' . htmlspecialchars($subdomain) . '.mdgeek.top',
                'admin_username' => 'admin',
                'admin_password' => $default_password,
                'shop_id' => $shop_id
            ];
            
        } catch (PDOException $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Nouveau magasin (Corrigé)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fixed-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
        }
        .credential-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
        }
        .credential-item {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="text-center mb-4">
            <span class="fixed-badge">
                <i class="fas fa-tools me-2"></i>VERSION CORRIGÉE - Structure GeekBoard Compatible
            </span>
        </div>
        
        <h1>Nouveau magasin <span class="badge bg-success">Corrigé</span></h1>
        
        <?php if ($success_data): ?>
            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle me-2"></i>Magasin créé avec succès !</h4>
                
                <div class="credential-card">
                    <h5><i class="fas fa-key me-2"></i>Identifiants de connexion</h5>
                    <div class="credential-item">
                        <strong>URL:</strong> <?php echo $success_data['url']; ?>
                    </div>
                    <div class="credential-item">
                        <strong>Username:</strong> <?php echo $success_data['admin_username']; ?>
                    </div>
                    <div class="credential-item">
                        <strong>Password:</strong> <?php echo $success_data['admin_password']; ?>
                    </div>
                    <div class="credential-item">
                        <strong>Shop ID:</strong> <?php echo $success_data['shop_id']; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="<?php echo $success_data['url']; ?>" target="_blank" class="btn btn-success">
                        <i class="fas fa-external-link-alt me-2"></i>Tester la connexion
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle me-2"></i>Améliorations de cette version :</h5>
                <ul class="mb-0">
                    <li><strong>Structure users corrigée</strong> : Compatible GeekBoard</li>
                    <li><strong>Mots de passe MD5</strong> : Compatible système connexion</li>
                    <li><strong>Rôles corrects</strong> : 'admin' et 'technicien'</li>
                    <li><strong>Colonnes complètes</strong> : shop_id, techbusy, scoring, etc.</li>
                </ul>
            </div>
            
            <form method="post" action="">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Informations du magasin</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du magasin *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subdomain" class="form-label">Sous-domaine *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="subdomain" name="subdomain" value="<?php echo htmlspecialchars($subdomain); ?>" required>
                                <span class="input-group-text">.mdgeek.top</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Configuration base de données</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="db_name" class="form-label">Nom de la base *</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($db_name); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_user" class="form-label">Utilisateur *</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" value="<?php echo htmlspecialchars($db_user); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="db_pass" class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-plus-circle me-2"></i>Créer le magasin (Version Corrigée)
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 