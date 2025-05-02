<?php
// Script de test pour la fonctionnalité de sous-domaine
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les configurations nécessaires
require_once __DIR__ . '/config/domain_config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_config.php';

// Fonction pour afficher les informations
function printInfo($label, $value) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($label) . "</strong></td>";
    echo "<td>" . htmlspecialchars($value) . "</td>";
    echo "</tr>";
}

// Récupérer les informations sur le domaine actuel
$host = $_SERVER['HTTP_HOST'];
$subdomain = getCurrentSubdomain();

// Vérifier si c'est un sous-domaine système
$is_system = $subdomain ? isSystemSubdomain($subdomain) : false;

// Récupérer les informations sur le magasin si un sous-domaine est détecté
$shop_info = "Aucun magasin détecté";
if ($subdomain && !$is_system) {
    $pdo = getMainDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1");
    $stmt->execute([$subdomain]);
    $shop = $stmt->fetch();
    
    if ($shop) {
        $shop_info = "ID: {$shop['id']}, Nom: {$shop['name']}";
        
        // Définir le magasin en session si ce n'est pas déjà fait
        if (!isset($_SESSION['shop_id']) || $_SESSION['shop_id'] != $shop['id']) {
            $_SESSION['shop_id'] = $shop['id'];
            $_SESSION['shop_name'] = $shop['name'];
        }
    } else {
        $shop_info = "Sous-domaine non associé à un magasin actif";
    }
}

// Récupérer la liste de tous les magasins avec leurs sous-domaines
$pdo = getMainDBConnection();
$shops = $pdo->query("SELECT id, name, subdomain FROM shops WHERE active = 1 ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test des sous-domaines - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { padding: 20px; }
        .table-info { background-color: #f8f9fa; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">
            <i class="fas fa-globe me-2"></i>
            Test des sous-domaines
        </h1>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Informations sur le domaine actuel</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <tbody>
                                <?php 
                                printInfo("Hôte actuel", $host);
                                printInfo("Domaine principal configuré", MAIN_DOMAIN);
                                printInfo("Sous-domaine détecté", $subdomain ?: "Aucun (domaine principal)");
                                if ($subdomain) {
                                    printInfo("Type de sous-domaine", $is_system ? "Système (ignoré)" : "Magasin");
                                }
                                printInfo("Magasin associé", $shop_info);
                                
                                if (isset($_SESSION['shop_id'])) {
                                    printInfo("Magasin en session", "ID: {$_SESSION['shop_id']}, Nom: {$_SESSION['shop_name']}");
                                } else {
                                    printInfo("Magasin en session", "Aucun");
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Liste des magasins et sous-domaines</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($shops) > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom du magasin</th>
                                        <th>Sous-domaine</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shops as $shop): ?>
                                        <tr <?php echo (isset($_SESSION['shop_id']) && $_SESSION['shop_id'] == $shop['id']) ? 'class="table-info"' : ''; ?>>
                                            <td><?php echo htmlspecialchars($shop['id']); ?></td>
                                            <td><?php echo htmlspecialchars($shop['name']); ?></td>
                                            <td>
                                                <?php if ($shop['subdomain']): ?>
                                                    <?php echo htmlspecialchars($shop['subdomain']); ?>.<?php echo MAIN_DOMAIN; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Non défini</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($shop['subdomain']): ?>
                                                    <a href="<?php echo buildSubdomainUrl($shop['subdomain']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="fas fa-external-link-alt me-1"></i> Visiter
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Non disponible</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Aucun magasin actif trouvé dans la base de données.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Tests et actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-1"></i> Aller à la page d'accueil
                            </a>
                            <a href="pages/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt me-1"></i> Aller à la page de connexion
                            </a>
                            <?php if (isset($_SESSION['shop_id'])): ?>
                                <form method="post" action="">
                                    <input type="hidden" name="clear_shop" value="1">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-times-circle me-1"></i> Effacer le magasin de la session
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="">
                                <input type="hidden" name="clear_session" value="1">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-trash-alt me-1"></i> Effacer toute la session
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_shop'])) {
        // Effacer uniquement les informations du magasin
        unset($_SESSION['shop_id']);
        unset($_SESSION['shop_name']);
        
        // Rediriger pour afficher les changements
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['clear_session'])) {
        // Effacer toute la session
        session_destroy();
        
        // Rediriger pour afficher les changements
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?> 