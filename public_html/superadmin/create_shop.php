<?php
// Page de création d'un nouveau magasin
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

$pdo = getMainDBConnection();

// Initialisation des variables
$name = '';
$description = '';
$subdomain = '';
$address = '';
$city = '';
$postal_code = '';
$country = 'France';
$phone = '';
$email = '';
$website = '';
$db_host = 'localhost';
$db_port = '3306';
$db_name = '';
$db_user = '';
$db_pass = '';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subdomain = trim($_POST['subdomain'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'France');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_port = trim($_POST['db_port'] ?? '3306');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Le nom du magasin est obligatoire.';
    }
    
    if (empty($subdomain)) {
        $errors[] = 'Le sous-domaine est obligatoire.';
    } else {
        // Vérifier que le sous-domaine contient uniquement des caractères alphanumériques et des tirets
        if (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            $errors[] = 'Le sous-domaine ne peut contenir que des lettres minuscules, des chiffres et des tirets.';
        }
        
        // Vérifier que le sous-domaine est unique
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shops WHERE subdomain = ?");
        $stmt->execute([$subdomain]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Ce sous-domaine est déjà utilisé par un autre magasin.';
        }
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
    
    // Vérifier que le nom du magasin est unique
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shops WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Un magasin avec ce nom existe déjà.';
    }
    
    // Si pas d'erreurs, on tente de créer le magasin
    if (empty($errors)) {
        try {
            // 1. Vérifier la connexion à la base de données du magasin
            $shop_config = [
                'host' => $db_host,
                'port' => $db_port,
                'dbname' => $db_name,
                'user' => $db_user,
                'pass' => $db_pass
            ];
            
            $shop_db = connectToShopDB($shop_config);
            
            if ($shop_db === null) {
                $errors[] = 'Impossible de se connecter à la base de données du magasin. Vérifiez les informations de connexion.';
            } else {
                // 2. Insertion du magasin dans la base principale
                $stmt = $pdo->prepare("
                    INSERT INTO shops (
                        name, description, subdomain, address, city, postal_code, country, 
                        phone, email, website, active, 
                        db_host, db_port, db_name, db_user, db_pass
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?
                    )
                ");
                
                $stmt->execute([
                    $name, $description, $subdomain, $address, $city, $postal_code, $country,
                    $phone, $email, $website,
                    $db_host, $db_port, $db_name, $db_user, $db_pass
                ]);
                
                $shop_id = $pdo->lastInsertId();
                
                // 3. Gérer le logo si un fichier a été uploadé
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $logo_dir = '../uploads/logos/';
                    
                    // Créer le répertoire s'il n'existe pas
                    if (!file_exists($logo_dir)) {
                        mkdir($logo_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $logo_filename = 'shop_' . $shop_id . '_' . time() . '.' . $file_extension;
                    $logo_path = $logo_dir . $logo_filename;
                    
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                        // Mettre à jour le magasin avec le logo
                        $stmt = $pdo->prepare("UPDATE shops SET logo = ? WHERE id = ?");
                        $stmt->execute([$logo_filename, $shop_id]);
                    }
                }
                
                // Rediriger avec un message de succès
                $_SESSION['message'] = 'Le magasin "' . htmlspecialchars($name) . '" a été créé avec succès.';
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la création du magasin: ' . $e->getMessage();
        }
    }
}

// Récupérer l'administrateur connecté
$stmt = $pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Nouveau magasin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-tools me-2"></i>GeekBoard Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Magasins</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop_admins.php">Administrateurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">Paramètres</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($superadmin['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Mon profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Nouveau magasin</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post" action="" enctype="multipart/form-data">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations du magasin</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nom du magasin <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="logo" class="form-label">Logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subdomain" class="form-label">Sous-domaine <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="subdomain" name="subdomain" value="<?php echo htmlspecialchars($subdomain); ?>" placeholder="monmagasin" required>
                            <span class="input-group-text">.mdgeek.top</span>
                        </div>
                        <div class="form-text">Le sous-domaine doit contenir uniquement des lettres minuscules, des chiffres et des tirets.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Adresse</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="city" class="form-label">Ville</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="postal_code" class="form-label">Code postal</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($postal_code); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="country" class="form-label">Pays</label>
                            <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($country); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="website" class="form-label">Site web</label>
                            <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($website); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Configuration de la base de données</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Assurez-vous que la base de données existe déjà et que l'utilisateur spécifié a les droits nécessaires.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="db_host" class="form-label">Hôte <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo htmlspecialchars($db_host); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="db_port" class="form-label">Port <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_port" name="db_port" value="<?php echo htmlspecialchars($db_port); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Nom de la base de données <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($db_name); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="db_user" class="form-label">Utilisateur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_user" name="db_user" value="<?php echo htmlspecialchars($db_user); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="db_pass" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($db_pass); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="test_connection" name="test_connection" checked>
                        <label class="form-check-label" for="test_connection">
                            Tester la connexion avant de créer le magasin
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus-circle me-2"></i>Créer le magasin
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS & Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 