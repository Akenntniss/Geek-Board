<?php
// Script de configuration des sous-domaines pour les magasins existants
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

$pdo = getMainDBConnection();
$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['shop_id']) && isset($_POST['subdomain'])) {
        $shop_id = (int)$_POST['shop_id'];
        $subdomain = trim($_POST['subdomain']);
        
        // Validation du sous-domaine
        if (empty($subdomain)) {
            $error = 'Le sous-domaine ne peut pas être vide.';
        } elseif (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            $error = 'Le sous-domaine ne peut contenir que des lettres minuscules, des chiffres et des tirets.';
        } else {
            try {
                // Vérifier que le sous-domaine est unique
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM shops WHERE subdomain = ? AND id != ?");
                $stmt->execute([$subdomain, $shop_id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Ce sous-domaine est déjà utilisé par un autre magasin.';
                } else {
                    // Mettre à jour le sous-domaine du magasin
                    $stmt = $pdo->prepare("UPDATE shops SET subdomain = ? WHERE id = ?");
                    $stmt->execute([$subdomain, $shop_id]);
                    
                    $message = 'Le sous-domaine a été mis à jour avec succès.';
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de la mise à jour du sous-domaine: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['generate_subdomains'])) {
        // Générer automatiquement des sous-domaines pour les magasins qui n'en ont pas
        try {
            // Récupérer tous les magasins sans sous-domaine
            $stmt = $pdo->query("SELECT id, name FROM shops WHERE subdomain IS NULL OR subdomain = ''");
            $shops_without_subdomain = $stmt->fetchAll();
            
            $updated_count = 0;
            
            foreach ($shops_without_subdomain as $shop) {
                // Générer un sous-domaine basé sur le nom du magasin
                $base_subdomain = strtolower(preg_replace('/[^a-z0-9]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $shop['name'])));
                $base_subdomain = trim($base_subdomain, '-');
                
                // S'assurer que le sous-domaine est unique
                $subdomain = $base_subdomain;
                $counter = 1;
                
                while (true) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shops WHERE subdomain = ?");
                    $stmt->execute([$subdomain]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        break;
                    }
                    
                    // Ajouter un compteur au sous-domaine
                    $subdomain = $base_subdomain . '-' . $counter;
                    $counter++;
                }
                
                // Mettre à jour le sous-domaine du magasin
                $stmt = $pdo->prepare("UPDATE shops SET subdomain = ? WHERE id = ?");
                $stmt->execute([$subdomain, $shop['id']]);
                
                $updated_count++;
            }
            
            if ($updated_count > 0) {
                $message = $updated_count . ' magasin(s) ont été mis à jour avec des sous-domaines générés automatiquement.';
            } else {
                $message = 'Tous les magasins ont déjà un sous-domaine.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la génération des sous-domaines: ' . $e->getMessage();
        }
    }
}

// Récupérer la liste des magasins
try {
    $stmt = $pdo->query("SELECT id, name, subdomain FROM shops ORDER BY name");
    $shops = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des magasins: ' . $e->getMessage();
    $shops = [];
}

// Récupérer l'administrateur connecté
$stmt = $pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();

// Statistiques
$total_shops = count($shops);
$shops_with_subdomain = count(array_filter($shops, function($shop) { return !empty($shop['subdomain']); }));
$shops_without_subdomain = $total_shops - $shops_with_subdomain;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Configuration des sous-domaines</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 95%;
            width: 100%;
            margin: 0 auto;
        }
        @media (min-width: 1400px) {
            .main-container {
                max-width: 1600px;
            }
        }
        @media (min-width: 1200px) and (max-width: 1399px) {
            .main-container {
                max-width: 90%;
            }
        }
        @media (max-width: 768px) {
            .main-container {
                max-width: 95%;
                margin: 10px;
                border-radius: 15px;
            }
            body {
                padding: 10px 0;
            }
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header-section h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header-section p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .user-info {
            background: rgba(255,255,255,0.15);
            border-radius: 15px;
            padding: 12px 20px;
            margin-top: 15px;
            backdrop-filter: blur(10px);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        .content-section {
            padding: 40px;
        }
        @media (min-width: 1400px) {
            .content-section {
                padding: 50px 60px;
            }
        }
        @media (max-width: 768px) {
            .content-section {
                padding: 30px 20px;
            }
        }
        .action-buttons {
            margin-bottom: 40px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        .btn-secondary:hover {
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.4);
        }
        .stats-row {
            margin-bottom: 40px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: none;
            height: 100%;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #667eea;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 600;
        }
        .config-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .config-section h3 {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .auto-generate-section {
            background: rgba(102, 126, 234, 0.1);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .auto-generate-section h4 {
            color: #667eea;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-generate {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-generate:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.4);
            color: white;
            text-decoration: none;
        }
        .shops-table-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid #e9ecef;
        }
        .shops-table-section h3 {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        .table {
            margin: 0;
            font-size: 0.95rem;
        }
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        .table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .subdomain-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .no-subdomain-badge {
            background: #fff3cd;
            color: #856404;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-update {
            background: #667eea;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        .btn-update:hover {
            background: #5a6fd8;
            color: white;
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: none;
        }
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        .shop-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .shop-link:hover {
            color: #5a6fd8;
            text-decoration: none;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            background: #f8f9fa;
            border-radius: 15px;
            margin-top: 30px;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h1><i class="fas fa-globe"></i>Configuration des Sous-domaines</h1>
            <p>Gestion des URL d'accès aux magasins</p>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>Connecté en tant que <strong><?php echo htmlspecialchars($superadmin['full_name']); ?></strong></span>
            </div>
        </div>
        
        <div class="content-section">
            <div class="action-buttons">
                <a href="index.php" class="btn-action btn-secondary">
                    <i class="fas fa-arrow-left"></i>Retour à l'accueil
                </a>
                <a href="create_shop.php" class="btn-action">
                    <i class="fas fa-plus-circle"></i>Nouveau magasin
                </a>
                <a href="database_manager.php" class="btn-action btn-secondary">
                    <i class="fas fa-database"></i>Base de données
                </a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="stats-row">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_shops; ?></div>
                            <div class="stat-label">Total Magasins</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $shops_with_subdomain; ?></div>
                            <div class="stat-label">Avec Sous-domaine</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-number"><?php echo $shops_without_subdomain; ?></div>
                            <div class="stat-label">Sans Sous-domaine</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($shops_without_subdomain > 0): ?>
                <div class="auto-generate-section">
                    <h4><i class="fas fa-magic"></i>Génération Automatique</h4>
                    <p style="margin-bottom: 20px; color: #666;">
                        <strong><?php echo $shops_without_subdomain; ?> magasin(s)</strong> n'ont pas encore de sous-domaine configuré.
                        Vous pouvez générer automatiquement des sous-domaines basés sur le nom de chaque magasin.
                    </p>
                    <form method="post" action="" style="display: inline;">
                        <button type="submit" name="generate_subdomains" class="btn-generate" 
                                onclick="return confirm('Êtes-vous sûr de vouloir générer automatiquement les sous-domaines manquants ?')">
                            <i class="fas fa-magic"></i>Générer les sous-domaines manquants
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="shops-table-section">
                <h3><i class="fas fa-list"></i>Liste des Magasins (<?php echo $total_shops; ?>)</h3>
                
                <?php if (count($shops) > 0): ?>
                    <!-- Barre de recherche -->
                    <div class="search-section" style="margin-bottom: 20px;">
                        <div style="position: relative; max-width: 500px;">
                            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #667eea; font-size: 1.1rem;"></i>
                            <input type="text" id="shops-search" class="form-control" placeholder="Rechercher un magasin par nom ou sous-domaine..." 
                                   style="padding-left: 45px; border-radius: 25px; box-shadow: 0 3px 10px rgba(0,0,0,0.08);">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Nom du Magasin</th>
                                    <th>Sous-domaine Actuel</th>
                                    <th>URL d'Accès</th>
                                    <th style="width: 300px;">Modifier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shops as $shop): ?>
                                    <tr>
                                        <td>
                                            <span style="background: #e9ecef; padding: 4px 8px; border-radius: 6px; font-weight: 600; color: #495057;">
                                                #<?php echo $shop['id']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($shop['name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($shop['subdomain'])): ?>
                                                <span class="subdomain-badge">
                                                    <i class="fas fa-link"></i>
                                                    <?php echo htmlspecialchars($shop['subdomain']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="no-subdomain-badge">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    Non configuré
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($shop['subdomain'])): ?>
                                                <a href="https://<?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top" 
                                                   target="_blank" class="shop-link">
                                                    <i class="fas fa-external-link-alt"></i>
                                                    <?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-times"></i>
                                                    URL non disponible
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" action="" style="display: flex; gap: 10px; align-items: center;">
                                                <input type="hidden" name="shop_id" value="<?php echo $shop['id']; ?>">
                                                <input type="text" name="subdomain" class="form-control" 
                                                       value="<?php echo htmlspecialchars($shop['subdomain'] ?? ''); ?>" 
                                                       placeholder="ex: mon-magasin" 
                                                       style="flex: 1; font-size: 0.9rem; padding: 8px 12px;"
                                                       pattern="[a-z0-9-]+" 
                                                       title="Uniquement des lettres minuscules, chiffres et tirets">
                                                <button type="submit" class="btn-update">
                                                    <i class="fas fa-save me-1"></i>Sauver
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Message aucun résultat -->
                    <div id="no-results-shops" style="display: none; text-align: center; padding: 40px 20px; color: #666;">
                        <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 15px; color: #ccc;"></i>
                        <h4>Aucun magasin trouvé</h4>
                        <p>Aucun magasin ne correspond à votre recherche.</p>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-store"></i>
                        <h3>Aucun magasin trouvé</h3>
                        <p>Il n'y a actuellement aucun magasin dans le système.</p>
                        <a href="create_shop.php" class="btn-action" style="margin-top: 20px;">
                            <i class="fas fa-plus-circle"></i>Créer un magasin
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_shops > 0): ?>
                <div class="config-section">
                    <h3><i class="fas fa-info-circle"></i>Informations Importantes</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div style="background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                <h5 style="color: #28a745; margin-bottom: 10px;">
                                    <i class="fas fa-check-circle me-2"></i>Format des sous-domaines
                                </h5>
                                <ul style="margin: 0; color: #666;">
                                    <li>Lettres minuscules uniquement</li>
                                    <li>Chiffres autorisés</li>
                                    <li>Tirets (-) autorisés</li>
                                    <li>Pas d'espaces ou caractères spéciaux</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="background: rgba(102, 126, 234, 0.1); border-left: 4px solid #667eea; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                <h5 style="color: #667eea; margin-bottom: 10px;">
                                    <i class="fas fa-globe me-2"></i>URL finale
                                </h5>
                                <p style="margin: 0; color: #666;">
                                    Tous les sous-domaines pointent vers<br>
                                    <strong>[sous-domaine].mdgeek.top</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.main-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(50px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // Animation des cartes de statistiques
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 200 + (index * 100));
            });
            
            // Auto-suggestion de sous-domaine basé sur le nom
            const shopNameInputs = document.querySelectorAll('input[name="subdomain"]');
            shopNameInputs.forEach(input => {
                input.addEventListener('input', function() {
                    // Nettoyer la valeur pour respecter le format
                    let value = this.value.toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '') // Enlever les caractères spéciaux
                        .replace(/\s+/g, '-') // Remplacer les espaces par des tirets
                        .replace(/-+/g, '-') // Éviter les tirets multiples
                        .replace(/^-|-$/g, ''); // Enlever les tirets en début/fin
                    
                    this.value = value;
                });
                
                // Validation en temps réel
                input.addEventListener('blur', function() {
                    const value = this.value.trim();
                    if (value && !/^[a-z0-9-]+$/.test(value)) {
                        this.style.borderColor = '#dc3545';
                    } else {
                        this.style.borderColor = '#e9ecef';
                    }
                });
            });
            
            // Fonctionnalité de recherche des magasins
            const shopsSearch = document.getElementById('shops-search');
            if (shopsSearch) {
                shopsSearch.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    const tableRows = document.querySelectorAll('.shops-table-section tbody tr');
                    const noResults = document.getElementById('no-results-shops');
                    const tableContainer = document.querySelector('.shops-table-section .table-responsive');
                    let visibleRows = 0;
                    
                    tableRows.forEach(function(row) {
                        // Récupérer le nom du magasin et le sous-domaine
                        const shopName = row.children[1]?.textContent.toLowerCase() || '';
                        const subdomain = row.children[2]?.textContent.toLowerCase() || '';
                        
                        // Vérifier si le terme de recherche correspond
                        if (searchTerm === '' || 
                            shopName.includes(searchTerm) || 
                            subdomain.includes(searchTerm)) {
                            row.style.display = '';
                            visibleRows++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Afficher le message "aucun résultat" si nécessaire
                    if (visibleRows === 0 && searchTerm !== '') {
                        tableContainer.style.display = 'none';
                        noResults.style.display = 'block';
                    } else {
                        tableContainer.style.display = 'block';
                        noResults.style.display = 'none';
                    }
                });
                
                // Style au focus
                shopsSearch.addEventListener('focus', function() {
                    this.style.borderColor = '#667eea';
                    this.style.boxShadow = '0 0 0 0.2rem rgba(102, 126, 234, 0.25), 0 3px 10px rgba(0,0,0,0.08)';
                });
                
                shopsSearch.addEventListener('blur', function() {
                    this.style.borderColor = '#e9ecef';
                    this.style.boxShadow = '0 3px 10px rgba(0,0,0,0.08)';
                });
            }
        });
    </script>
</body>
</html> 