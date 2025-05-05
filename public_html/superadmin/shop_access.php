<?php
// Page d'accès à un magasin spécifique pour le super administrateur
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

// Récupérer l'ID du magasin
$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($shop_id <= 0) {
    $_SESSION['error'] = "ID de magasin invalide.";
    header('Location: index.php');
    exit;
}

// Récupérer les informations du magasin
$pdo = getMainDBConnection();
$stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch();

if (!$shop) {
    $_SESSION['error'] = "Magasin non trouvé.";
    header('Location: index.php');
    exit;
}

// Vérifier si le magasin est actif
if (!$shop['active']) {
    $_SESSION['error'] = "Ce magasin est actuellement inactif.";
    header('Location: index.php');
    exit;
}

// Récupérer le super administrateur connecté
$stmt = $pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();

// Stocker l'ID du magasin en session pour les accès futurs
$_SESSION['shop_id'] = $shop_id;
$_SESSION['shop_name'] = $shop['name'];

// Tester la connexion à la base de données du magasin
$shop_config = [
    'host' => $shop['db_host'],
    'port' => $shop['db_port'],
    'dbname' => $shop['db_name'],
    'user' => $shop['db_user'],
    'pass' => $shop['db_pass']
];

$shop_db = connectToShopDB($shop_config);

if (!$shop_db) {
    $connection_error = "Impossible de se connecter à la base de données du magasin. Vérifiez les informations de connexion.";
}

// Vérifier si la base de données est initialisée
$db_initialized = false;
$missing_tables = [];
$required_tables = ['users', 'clients', 'reparations', 'employes'];

if ($shop_db) {
    $result = $shop_db->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    // Vérifier si les tables essentielles existent
    foreach ($required_tables as $table) {
        if (!in_array($table, $tables)) {
            $missing_tables[] = $table;
        }
    }
    
    $db_initialized = empty($missing_tables);
}

// Récupérer des statistiques du magasin si la base est initialisée
$stats = [
    'reparations' => 0,
    'clients' => 0,
    'employes' => 0,
    'nouveau' => 0
];

if ($db_initialized) {
    try {
        // Compter le nombre de réparations
        $stmt = $shop_db->query("SELECT COUNT(*) FROM reparations");
        $stats['reparations'] = $stmt->fetchColumn();
        
        // Compter le nombre de clients
        $stmt = $shop_db->query("SELECT COUNT(*) FROM clients");
        $stats['clients'] = $stmt->fetchColumn();
        
        // Compter le nombre d'employés
        $stmt = $shop_db->query("SELECT COUNT(*) FROM employes");
        $stats['employes'] = $stmt->fetchColumn();
        
        // Compter le nombre de nouvelles réparations
        $stmt = $shop_db->query("SELECT COUNT(*) FROM reparations WHERE statut = 'nouvelle_intervention'");
        $stats['nouveau'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $db_initialized = false;
        $connection_error = "Erreur lors de la récupération des statistiques: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Magasin - <?php echo htmlspecialchars($shop['name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            transition: transform 0.2s;
            border-radius: 10px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .shop-logo {
            max-height: 120px;
            max-width: 100%;
        }
    </style>
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
                        <a class="nav-link" href="index.php">Magasins</a>
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
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="mb-0"><?php echo htmlspecialchars($shop['name']); ?></h1>
                </div>
                <p class="text-muted mt-2"><?php echo htmlspecialchars($shop['address'] ?? ''); ?> <?php echo htmlspecialchars($shop['city'] ?? ''); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <?php if (!empty($shop['logo'])): ?>
                    <img src="<?php echo htmlspecialchars('../uploads/logos/' . $shop['logo']); ?>" class="shop-logo" alt="Logo">
                <?php else: ?>
                    <div class="display-1 text-secondary">
                        <i class="fas fa-store"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($connection_error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $connection_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$db_initialized): ?>
            <div class="alert alert-warning">
                <i class="fas fa-database me-2"></i>
                La base de données de ce magasin n'est pas encore initialisée ou est incomplète.
                <?php if (!empty($missing_tables)): ?>
                    <div class="mt-2">Tables manquantes: <?php echo implode(', ', $missing_tables); ?></div>
                <?php endif; ?>
                <a href="initialize_shop_db.php?id=<?php echo $shop_id; ?>" class="btn btn-primary mt-2">
                    <i class="fas fa-cogs me-2"></i>Initialiser la base de données
                </a>
            </div>
        <?php else: ?>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Réparations</h6>
                                    <h2 class="mb-0"><?php echo $stats['reparations']; ?></h2>
                                </div>
                                <div class="fs-1">
                                    <i class="fas fa-tools"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Clients</h6>
                                    <h2 class="mb-0"><?php echo $stats['clients']; ?></h2>
                                </div>
                                <div class="fs-1">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Employés</h6>
                                    <h2 class="mb-0"><?php echo $stats['employes']; ?></h2>
                                </div>
                                <div class="fs-1">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-warning text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Nouvelles</h6>
                                    <h2 class="mb-0"><?php echo $stats['nouveau']; ?></h2>
                                </div>
                                <div class="fs-1">
                                    <i class="fas fa-bell"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informations du magasin</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped mb-0">
                            <tr>
                                <th>Nom:</th>
                                <td><?php echo htmlspecialchars($shop['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Adresse:</th>
                                <td><?php echo htmlspecialchars($shop['address'] ?? 'Non spécifiée'); ?></td>
                            </tr>
                            <tr>
                                <th>Ville:</th>
                                <td><?php echo htmlspecialchars($shop['city'] ?? 'Non spécifiée'); ?></td>
                            </tr>
                            <tr>
                                <th>Code postal:</th>
                                <td><?php echo htmlspecialchars($shop['postal_code'] ?? 'Non spécifié'); ?></td>
                            </tr>
                            <tr>
                                <th>Téléphone:</th>
                                <td><?php echo htmlspecialchars($shop['phone'] ?? 'Non spécifié'); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($shop['email'] ?? 'Non spécifié'); ?></td>
                            </tr>
                            <tr>
                                <th>Site web:</th>
                                <td>
                                    <?php if (!empty($shop['website'])): ?>
                                        <a href="<?php echo htmlspecialchars($shop['website']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($shop['website']); ?>
                                        </a>
                                    <?php else: ?>
                                        Non spécifié
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informations de la base de données</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped mb-0">
                            <tr>
                                <th>Hôte:</th>
                                <td><?php echo htmlspecialchars($shop['db_host']); ?></td>
                            </tr>
                            <tr>
                                <th>Port:</th>
                                <td><?php echo htmlspecialchars($shop['db_port']); ?></td>
                            </tr>
                            <tr>
                                <th>Base de données:</th>
                                <td><?php echo htmlspecialchars($shop['db_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Utilisateur:</th>
                                <td><?php echo htmlspecialchars($shop['db_user']); ?></td>
                            </tr>
                            <tr>
                                <th>Mot de passe:</th>
                                <td>********</td>
                            </tr>
                            <tr>
                                <th>État:</th>
                                <td>
                                    <?php if (isset($connection_error)): ?>
                                        <span class="badge bg-danger">Erreur de connexion</span>
                                    <?php elseif (!$db_initialized): ?>
                                        <span class="badge bg-warning text-dark">Non initialisée</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Connectée</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <?php if ($db_initialized): ?>
                <a href="../index.php" class="btn btn-success btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Accéder à l'interface de gestion du magasin
                </a>
            <?php endif; ?>
            
            <a href="edit_shop.php?id=<?php echo $shop_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Modifier le magasin
            </a>
            
            <?php if (!$db_initialized): ?>
                <a href="initialize_shop_db.php?id=<?php echo $shop_id; ?>" class="btn btn-warning">
                    <i class="fas fa-database me-2"></i>Initialiser la base de données
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS & Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 