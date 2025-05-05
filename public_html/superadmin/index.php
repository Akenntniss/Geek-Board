<?php
// Page d'accueil du super administrateur
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    // Rediriger vers la page de connexion si non connecté
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

// Récupérer la liste des magasins
$pdo = getMainDBConnection();
$shops = $pdo->query("SELECT * FROM shops ORDER BY name")->fetchAll();

// Récupérer les infos du super administrateur connecté
$stmt = $pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();

// Message de succès ou d'erreur
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Administration centrale</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .shop-card {
            transition: transform 0.2s;
        }
        .shop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: bold;
        }
        .shop-logo {
            height: 80px;
            width: 80px;
            object-fit: contain;
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
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des magasins</h1>
            <div>
                <a href="configure_domains.php" class="btn btn-primary me-2">
                    <i class="fas fa-globe me-1"></i> Configurer les sous-domaines
                </a>
                <a href="create_shop.php" class="btn btn-success">
                    <i class="fas fa-plus-circle me-1"></i> Nouveau magasin
                </a>
            </div>
        </div>

        <div class="row">
            <?php foreach ($shops as $shop): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shop-card h-100 <?php echo $shop['active'] ? '' : 'bg-light'; ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <?php if (!empty($shop['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars('../uploads/logos/' . $shop['logo']); ?>" class="shop-logo me-3" alt="Logo">
                                <?php else: ?>
                                    <div class="shop-logo bg-light d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-store fa-2x text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($shop['name']); ?></h5>
                            </div>
                            
                            <p class="card-text"><?php echo htmlspecialchars(substr($shop['description'] ?? '', 0, 100) . (strlen($shop['description'] ?? '') > 100 ? '...' : '')); ?></p>
                            
                            <div class="mb-2">
                                <?php if (!empty($shop['city'])): ?>
                                    <div><i class="fas fa-map-marker-alt me-2 text-secondary"></i><?php echo htmlspecialchars($shop['city']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($shop['phone'])): ?>
                                    <div><i class="fas fa-phone me-2 text-secondary"></i><?php echo htmlspecialchars($shop['phone']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <span class="badge <?php echo $shop['active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $shop['active'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                                <small class="text-muted">ID: <?php echo $shop['id']; ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100">
                                <a href="edit_shop.php?id=<?php echo $shop['id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="view_shop.php?id=<?php echo $shop['id']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-eye"></i> Détails
                                </a>
                                <a href="shop_access.php?id=<?php echo $shop['id']; ?>" class="btn btn-outline-success">
                                    <i class="fas fa-sign-in-alt"></i> Accéder
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($shops) === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Aucun magasin n'a été créé. Cliquez sur "Nouveau magasin" pour commencer.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS & Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 