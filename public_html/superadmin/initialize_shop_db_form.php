<?php
// Formulaire d'initialisation de la base de données d'un magasin
require_once('../config/database.php');

// Vérifier si l'utilisateur est connecté en tant que superadmin
session_start();
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer la liste des magasins
$mainPdo = getMainDBConnection();
$shops = $mainPdo->query("SELECT id, name, subdomain, active FROM shops ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation de base de données - GeekBoard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .shop-card {
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .shop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .shop-inactive {
            opacity: 0.7;
        }
        .badge-subdomain {
            background-color: #f8f9fa;
            color: #6c757d;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Initialisation de la base de données d'un magasin</h1>
        <p class="lead">Sélectionnez un magasin pour initialiser sa base de données avec la structure complète.</p>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>Attention:</strong> 
            Cette opération va créer toutes les tables nécessaires dans la base de données du magasin sélectionné.
            Si des tables existent déjà, elles ne seront pas modifiées.
        </div>
        
        <div class="row mt-4">
            <?php foreach ($shops as $shop): ?>
                <div class="col-md-4">
                    <div class="card shop-card <?= $shop['active'] ? '' : 'shop-inactive' ?>">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?= htmlspecialchars($shop['name']) ?>
                                <?php if (!$shop['active']): ?>
                                    <span class="badge badge-secondary">Inactif</span>
                                <?php endif; ?>
                            </h5>
                            <?php if ($shop['subdomain']): ?>
                                <p><span class="badge badge-subdomain"><?= htmlspecialchars($shop['subdomain']) ?></span></p>
                            <?php endif; ?>
                            <form action="initialize_shop_db.php" method="post">
                                <input type="hidden" name="shop_id" value="<?= $shop['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm" 
                                    onclick="return confirm('Êtes-vous sûr de vouloir initialiser la base de données de ce magasin ?');">
                                    <i class="fas fa-database"></i> Initialiser la base de données
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <a href="manage_shops.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la gestion des magasins
            </a>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 