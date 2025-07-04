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
$shops = $pdo->query("SELECT * FROM shops ORDER BY created_at DESC")->fetchAll();

// Récupérer les infos du super administrateur connecté
$stmt = $pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();

// Statistiques rapides
$total_shops = count($shops);
$active_shops = count(array_filter($shops, function($shop) { return $shop['active']; }));
$inactive_shops = $total_shops - $active_shops;

// Message de succès ou d'erreur
$message = '';
$message_type = 'success';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['message_type'])) {
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Administration centrale</title>
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
            font-size: 2.5rem;
            font-weight: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header-section p {
            margin: 15px 0 0 0;
            opacity: 0.9;
            font-size: 1.2rem;
        }
        .user-info {
            background: rgba(255,255,255,0.15);
            border-radius: 15px;
            padding: 15px 25px;
            margin-top: 20px;
            backdrop-filter: blur(10px);
            display: inline-flex;
            align-items: center;
            gap: 10px;
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
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.4);
        }
        .shops-section h2 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 300;
        }
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        .shop-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .shop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .shop-card.inactive {
            opacity: 0.7;
            background: linear-gradient(135deg, #f1f3f4 0%, #e8eaed 100%);
        }
        .shop-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .shop-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 15px;
        }
        .shop-info h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }
        .shop-info .subdomain {
            color: #667eea;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .shop-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .shop-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-shop {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }
        .btn-shop-primary {
            background: #667eea;
            color: white;
        }
        .btn-shop-primary:hover {
            background: #5a6fd8;
            color: white;
            text-decoration: none;
        }
        .btn-shop-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-shop-outline:hover {
            background: #667eea;
            color: white;
            text-decoration: none;
        }
        .btn-shop-danger {
            background: transparent;
            color: #dc3545;
            border: 2px solid #dc3545;
        }
        .btn-shop-danger:hover {
            background: #dc3545;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
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
        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        .search-section {
            margin-bottom: 30px;
        }
        .search-container {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }
        .search-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25), 0 5px 15px rgba(0,0,0,0.08);
        }
        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.2rem;
        }
        .shop-card.hidden {
            display: none;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            display: none;
        }
        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }
        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h1><i class="fas fa-tools"></i>Administration GeekBoard</h1>
            <p>Tableau de bord de gestion</p>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>Connecté en tant que <strong><?php echo htmlspecialchars($superadmin['full_name']); ?></strong></span>
            </div>
        </div>
        
        <div class="content-section">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle me-2"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle me-2"></i>
                    <?php endif; ?>
                    <?php echo $message; ?>
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
                            <div class="stat-number"><?php echo $active_shops; ?></div>
                            <div class="stat-label">Magasins Actifs</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-pause-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $inactive_shops; ?></div>
                            <div class="stat-label">Magasins Inactifs</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="create_shop.php" class="btn-action">
                    <i class="fas fa-plus-circle"></i>Nouveau magasin
                </a>
                <a href="database_manager.php" class="btn-action btn-secondary">
                    <i class="fas fa-database"></i>Base de données
                </a>
                <a href="configure_domains.php" class="btn-action btn-secondary">
                    <i class="fas fa-globe"></i>Configuration domaines
                </a>
            </div>

            <div class="shops-section">
                <h2>Mes Magasins</h2>
                
                <?php if (count($shops) > 0): ?>
                    <!-- Barre de recherche -->
                    <div class="search-section">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="shop-search" class="search-input" placeholder="Rechercher un magasin par nom ou sous-domaine...">
                        </div>
                    </div>
                    <div class="shop-grid">
                        <?php foreach ($shops as $shop): ?>
                            <div class="shop-card <?php echo $shop['active'] ? '' : 'inactive'; ?>">
                                <div class="shop-status <?php echo $shop['active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $shop['active'] ? 'Actif' : 'Inactif'; ?>
                                </div>
                                
                                <div class="shop-header">
                                    <div class="shop-logo">
                                        <?php if (!empty($shop['logo'])): ?>
                                            <img src="<?php echo htmlspecialchars('../uploads/logos/' . $shop['logo']); ?>" 
                                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-store"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="shop-info">
                                        <h3><?php echo htmlspecialchars($shop['name']); ?></h3>
                                        <?php if (!empty($shop['subdomain'])): ?>
                                            <div class="subdomain"><?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($shop['description'])): ?>
                                    <p style="color: #666; margin-bottom: 15px;">
                                        <?php echo htmlspecialchars(substr($shop['description'], 0, 100) . (strlen($shop['description']) > 100 ? '...' : '')); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($shop['city']) || !empty($shop['phone'])): ?>
                                    <div style="margin-bottom: 20px; font-size: 0.9rem; color: #666;">
                                        <?php if (!empty($shop['city'])): ?>
                                            <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($shop['city']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($shop['phone'])): ?>
                                            <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($shop['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="shop-actions">
                                    <a href="edit_shop.php?id=<?php echo $shop['id']; ?>" class="btn-shop btn-shop-primary">
                                        <i class="fas fa-edit me-1"></i>Modifier
                                    </a>
                                    <a href="view_shop.php?id=<?php echo $shop['id']; ?>" class="btn-shop btn-shop-outline">
                                        <i class="fas fa-eye me-1"></i>Détails
                                    </a>
                                    <?php if (!empty($shop['subdomain'])): ?>
                                        <a href="https://<?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top" target="_blank" class="btn-shop btn-shop-outline">
                                            <i class="fas fa-external-link-alt me-1"></i>Visiter
                                        </a>
                                    <?php endif; ?>
                                    <a href="delete_shop.php?id=<?php echo $shop['id']; ?>" class="btn-shop btn-shop-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer le magasin \"<?php echo htmlspecialchars($shop['name']); ?>\" ? Cette action est irréversible.');">
                                        <i class="fas fa-trash-alt me-1"></i>Supprimer
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Message aucun résultat -->
                    <div id="no-results" class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Aucun magasin trouvé</h3>
                        <p>Aucun magasin ne correspond à votre recherche.</p>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-store"></i>
                        <h3>Aucun magasin créé</h3>
                        <p>Commencez par créer votre premier magasin pour débuter avec GeekBoard.</p>
                        <a href="create_shop.php" class="btn-action" style="margin-top: 20px;">
                            <i class="fas fa-plus-circle"></i>Créer mon premier magasin
                        </a>
                    </div>
                <?php endif; ?>
            </div>
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
            
            // Animation des cartes de magasins
            const shopCards = document.querySelectorAll('.shop-card');
            shopCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 200 + (index * 100));
            });
            
            // Fonctionnalité de recherche des magasins
            const searchInput = document.getElementById('shop-search');
            const shopCards = document.querySelectorAll('.shop-card');
            const noResults = document.getElementById('no-results');
            
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    let visibleCards = 0;
                    
                    shopCards.forEach(function(card) {
                        // Récupérer le nom du magasin et le sous-domaine
                        const shopName = card.querySelector('.shop-info h3')?.textContent.toLowerCase() || '';
                        const shopSubdomain = card.querySelector('.shop-info .subdomain')?.textContent.toLowerCase() || '';
                        
                        // Vérifier si le terme de recherche correspond
                        if (searchTerm === '' || 
                            shopName.includes(searchTerm) || 
                            shopSubdomain.includes(searchTerm)) {
                            card.classList.remove('hidden');
                            visibleCards++;
                        } else {
                            card.classList.add('hidden');
                        }
                    });
                    
                    // Afficher le message "aucun résultat" si nécessaire
                    if (visibleCards === 0 && searchTerm !== '') {
                        noResults.style.display = 'block';
                    } else {
                        noResults.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html> 