<?php
// Inclure la configuration de session et la base de données
require_once '../config/session_config.php';
require_once '../config/database.php';

// Fonction de journalisation
function debugLog($message) {
    error_log("[CHANGE SHOP] " . $message);
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer la liste des magasins disponibles
$shopList = [];
try {
    $pdo_main = getMainDBConnection();
    $shopList = $pdo_main->query("SELECT id, name, logo FROM shops WHERE active = 1 ORDER BY name")->fetchAll();
    debugLog("Liste des magasins récupérée: " . count($shopList) . " magasins trouvés");
} catch (PDOException $e) {
    debugLog("Erreur lors de la récupération des magasins: " . $e->getMessage());
    error_log("Erreur lors de la récupération des magasins: " . $e->getMessage());
}

// Traitement du formulaire
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_id'])) {
    $shop_id = (int)$_POST['shop_id'];
    
    if ($shop_id > 0) {
        try {
            // Vérifier que le magasin existe et est actif
            $stmt = $pdo_main->prepare("SELECT id, name FROM shops WHERE id = ? AND active = 1");
            $stmt->execute([$shop_id]);
            $shop = $stmt->fetch();
            
            if ($shop) {
                // Stocker les infos du magasin en session
                $_SESSION['shop_id'] = $shop['id'];
                $_SESSION['shop_name'] = $shop['name'];
                
                debugLog("Magasin changé pour: " . $shop['name'] . " (ID: " . $shop['id'] . ")");
                $success_message = "Vous êtes maintenant connecté au magasin " . htmlspecialchars($shop['name']);
                
                // Redirection après 2 secondes
                header("Refresh: 2; URL=/index.php");
            } else {
                $error_message = "Magasin non trouvé ou inactif";
                debugLog("Magasin non trouvé ou inactif: " . $shop_id);
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors du changement de magasin";
            debugLog("Erreur PDO: " . $e->getMessage());
        }
    } else {
        $error_message = "Veuillez sélectionner un magasin";
        debugLog("Aucun magasin sélectionné");
    }
}

// Titre de la page
$pageTitle = "Changer de magasin";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .shop-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .shop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        .shop-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 15px;
        }
        .shop-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        .current-shop {
            background-color: #e8f4ff;
            border-left: 4px solid #0078e8;
        }
        .btn-select {
            background: linear-gradient(135deg, #0078e8 0%, #37a1ff 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-select:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 120, 232, 0.3);
        }
        .header {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="header text-center">
                    <h1><i class="fas fa-store me-2"></i> Changer de magasin</h1>
                    <p class="text-muted">Sélectionnez le magasin auquel vous souhaitez vous connecter</p>
                </div>
                
                <?php if ($success_message): ?>
                <div class="alert alert-success mb-4">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> 
                        <?php if (isset($_SESSION['shop_id']) && isset($_SESSION['shop_name'])): ?>
                            Vous êtes actuellement connecté au magasin: <strong><?php echo htmlspecialchars($_SESSION['shop_name']); ?></strong>
                        <?php else: ?>
                            Vous n'êtes actuellement connecté à aucun magasin
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (empty($shopList)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> Aucun magasin disponible
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <?php foreach ($shopList as $shop): ?>
                            <?php $isCurrentShop = isset($_SESSION['shop_id']) && $_SESSION['shop_id'] == $shop['id']; ?>
                            <div class="shop-card d-flex align-items-center justify-content-between <?php echo $isCurrentShop ? 'current-shop' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($shop['logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($shop['logo']); ?>" alt="Logo" class="shop-logo">
                                    <?php else: ?>
                                        <div class="shop-logo d-flex align-items-center justify-content-center bg-light rounded">
                                            <i class="fas fa-store fa-2x text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="shop-name"><?php echo htmlspecialchars($shop['name']); ?></div>
                                        <?php if ($isCurrentShop): ?>
                                            <span class="badge bg-primary">Magasin actuel</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <button type="submit" name="shop_id" value="<?php echo $shop['id']; ?>" class="btn btn-select" <?php echo $isCurrentShop ? 'disabled' : ''; ?>>
                                        <?php if ($isCurrentShop): ?>
                                            <i class="fas fa-check me-1"></i> Sélectionné
                                        <?php else: ?>
                                            <i class="fas fa-sign-in-alt me-1"></i> Sélectionner
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>
                <?php endif; ?>
                
                <div class="mt-4 text-center">
                    <a href="/index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 