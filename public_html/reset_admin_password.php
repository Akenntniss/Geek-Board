<?php
/**
 * Script de réinitialisation du mot de passe Admin
 * 
 * Ce script réinitialise le mot de passe de l'utilisateur Admin à "Admin123!"
 * Il recherche dans la base de données du magasin sélectionné
 */

// Afficher les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Fonction pour journaliser les messages
function logMessage($message) {
    echo "<div class='log-message'>" . htmlspecialchars($message) . "</div>";
    error_log($message);
}

// Définir le nouveau mot de passe
$new_password = 'Admin123!';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Variable pour stocker le résultat
$result = false;
$shop_name = "";
$affected_rows = 0;

// Si un magasin est spécifié dans l'URL, l'utiliser
if (isset($_GET['shop_id'])) {
    $_SESSION['shop_id'] = (int)$_GET['shop_id'];
    logMessage("Utilisation du magasin ID: " . $_SESSION['shop_id'] . " depuis l'URL");
}

// Déterminer si un magasin est sélectionné
$shop_id = $_SESSION['shop_id'] ?? null;
if (!$shop_id) {
    logMessage("Erreur: Aucun magasin sélectionné. Veuillez d'abord choisir un magasin.");
    $shops = [];
    
    try {
        // Récupérer la liste des magasins depuis la base principale
        $pdo = getMainDBConnection();
        $stmt = $pdo->query("SELECT id, name FROM shops WHERE active = 1 ORDER BY name");
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logMessage("Erreur lors de la récupération des magasins: " . $e->getMessage());
    }
}

// Si un formulaire a été soumis pour confirmer la réinitialisation
if (isset($_POST['confirm_reset']) && $_POST['confirm_reset'] === 'yes') {
    try {
        // Établir une connexion à la base de données du magasin actuel
        $pdo = getShopDBConnection();
        
        // Vérifier la connexion
        if ($pdo === null) {
            throw new Exception("Erreur: Impossible de se connecter à la base de données du magasin.");
        }
        
        // Vérifier quelle base de données est actuellement utilisée
        $stmt = $pdo->query("SELECT DATABASE() as current_db");
        $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
        logMessage("Base de données connectée: " . $db_info['current_db']);
        
        // Vérifier la structure de la table users
        try {
            $describe_stmt = $pdo->query("DESCRIBE users");
            $columns = $describe_stmt->fetchAll(PDO::FETCH_COLUMN);
            logMessage("Colonnes trouvées dans la table users: " . implode(", ", $columns));
        } catch (Exception $e) {
            logMessage("Erreur lors de la vérification de la structure de la table: " . $e->getMessage());
        }
        
        // Mettre à jour le mot de passe de l'Admin
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'Admin'");
        $stmt->execute([$hashed_password]);
        $affected_rows = $stmt->rowCount();
        
        if ($affected_rows > 0) {
            logMessage("Succès: Le mot de passe de l'utilisateur 'Admin' a été réinitialisé à '$new_password'");
            $result = true;
            
            // Récupérer le nom du magasin
            if (isset($_SESSION['shop_name'])) {
                $shop_name = $_SESSION['shop_name'];
            } else {
                // Récupérer le nom du magasin depuis la base principale
                $main_pdo = getMainDBConnection();
                $stmt = $main_pdo->prepare("SELECT name FROM shops WHERE id = ?");
                $stmt->execute([$shop_id]);
                $shop_data = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($shop_data) {
                    $shop_name = $shop_data['name'];
                    $_SESSION['shop_name'] = $shop_name;
                }
            }
        } else {
            logMessage("Attention: Aucun utilisateur 'Admin' trouvé dans la base de données.");
            
            // Tenter de créer l'utilisateur Admin s'il n'existe pas
            logMessage("Tentative de création de l'utilisateur Admin...");
            
            // Adapter les colonnes pour l'insertion en fonction de la structure de la table
            $has_email = in_array('email', $columns ?? []);
            $has_active = in_array('active', $columns ?? []);
            
            if ($has_email && $has_active) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email, active) 
                                      VALUES ('Admin', ?, 'Administrateur', 'admin', 'admin@geekboard.com', 1)");
                $stmt->execute([$hashed_password]);
            } elseif ($has_email) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email) 
                                      VALUES ('Admin', ?, 'Administrateur', 'admin', 'admin@geekboard.com')");
                $stmt->execute([$hashed_password]);
            } elseif ($has_active) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, active) 
                                      VALUES ('Admin', ?, 'Administrateur', 'admin', 1)");
                $stmt->execute([$hashed_password]);
            } else {
                // Version simple avec les colonnes de base
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) 
                                      VALUES ('Admin', ?, 'Administrateur', 'admin')");
                $stmt->execute([$hashed_password]);
            }
            
            if ($stmt->rowCount() > 0) {
                logMessage("Succès: L'utilisateur 'Admin' a été créé avec le mot de passe '$new_password'");
                $result = true;
            } else {
                logMessage("Erreur: Impossible de créer l'utilisateur Admin. Vérifiez la structure de la table users.");
            }
        }
    } catch (Exception $e) {
        logMessage("Erreur: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 50px;
            background-color: #f8f9fa;
        }
        .reset-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .log-message {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .success-banner {
            background-color: #d4edda;
            color: #155724;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .shop-list {
            margin-top: 20px;
        }
        .shop-card {
            border-radius: 5px;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        .shop-card:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container reset-container">
        <h1 class="text-center mb-4">Réinitialisation du mot de passe Admin</h1>
        
        <?php if ($result): ?>
        <div class="success-banner">
            <h4>Réinitialisation réussie !</h4>
            <p>Le mot de passe de l'utilisateur Admin pour le magasin <strong><?php echo htmlspecialchars($shop_name); ?></strong> a été réinitialisé à :</p>
            <div class="p-3 mb-2 bg-light rounded text-center">
                <code class="fs-4"><?php echo htmlspecialchars($new_password); ?></code>
            </div>
            <p class="mb-0">Vous pouvez maintenant vous connecter avec ce mot de passe.</p>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
            <a href="pages/login.php" class="btn btn-success">Se connecter</a>
        </div>
        <?php elseif (isset($shop_id)): ?>
        <div class="alert alert-info">
            <p>Vous êtes sur le point de réinitialiser le mot de passe de l'utilisateur Admin pour le magasin <strong><?php echo htmlspecialchars($_SESSION['shop_name'] ?? "ID: $shop_id"); ?></strong>.</p>
            <p>Le nouveau mot de passe sera : <code><?php echo htmlspecialchars($new_password); ?></code></p>
        </div>
        
        <form method="post" action="">
            <input type="hidden" name="confirm_reset" value="yes">
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger btn-lg">Confirmer la réinitialisation</button>
                <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
        <?php else: ?>
        <div class="alert alert-warning">
            <p>Veuillez sélectionner un magasin pour réinitialiser le mot de passe Admin :</p>
        </div>
        
        <div class="shop-list">
            <?php foreach ($shops as $shop): ?>
            <a href="?shop_id=<?php echo $shop['id']; ?>" class="text-decoration-none">
                <div class="shop-card">
                    <h5 class="mb-0"><?php echo htmlspecialchars($shop['name']); ?></h5>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($shops)): ?>
        <div class="alert alert-danger">
            <p>Aucun magasin trouvé. Veuillez vérifier la configuration de la base de données.</p>
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-outline-secondary">Retour à l'accueil</a>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 