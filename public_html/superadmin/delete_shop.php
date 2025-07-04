<?php
// Supprimer un magasin
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier si l'ID du magasin est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'ID de magasin invalide.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$shop_id = (int)$_GET['id'];

try {
    $pdo = getMainDBConnection();
    
    // Vérifier si le magasin existe
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch();
    
    if (!$shop) {
        $_SESSION['message'] = 'Magasin introuvable.';
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
    
    // Si c'est une confirmation de suppression
    if (isset($_POST['confirm_delete'])) {
        // Commencer une transaction
        $pdo->beginTransaction();
        
        try {
            // Supprimer le magasin de la base de données
            $stmt = $pdo->prepare("DELETE FROM shops WHERE id = ?");
            $stmt->execute([$shop_id]);
            
            // Supprimer le logo s'il existe
            if (!empty($shop['logo']) && file_exists('../uploads/logos/' . $shop['logo'])) {
                unlink('../uploads/logos/' . $shop['logo']);
            }
            
            // Valider la transaction
            $pdo->commit();
            
            $_SESSION['message'] = 'Magasin "' . htmlspecialchars($shop['name']) . '" supprimé avec succès.';
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollback();
            throw $e;
        }
    }
    
} catch (Exception $e) {
    $_SESSION['message'] = 'Erreur lors de la suppression : ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer un magasin - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        .header-section {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header-section h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .content-section {
            padding: 40px;
        }
        .shop-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }
        .shop-logo {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 20px;
        }
        .warning-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffecb5;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }
        .warning-icon {
            font-size: 3rem;
            color: #856404;
            margin-bottom: 15px;
        }
        .btn-danger-custom {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
        .btn-danger-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
            color: white;
        }
        .btn-secondary-custom {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
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
        .btn-secondary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.4);
            color: white;
            text-decoration: none;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h1><i class="fas fa-trash-alt"></i>Supprimer un magasin</h1>
        </div>
        
        <div class="content-section">
            <div class="shop-info">
                <div class="shop-logo">
                    <?php if (!empty($shop['logo'])): ?>
                        <img src="<?php echo htmlspecialchars('../uploads/logos/' . $shop['logo']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 15px;" alt="Logo">
                    <?php else: ?>
                        <i class="fas fa-store"></i>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($shop['name']); ?></h3>
                <?php if (!empty($shop['subdomain'])): ?>
                    <p class="text-muted"><?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top</p>
                <?php endif; ?>
                <?php if (!empty($shop['description'])): ?>
                    <p class="text-muted"><?php echo htmlspecialchars($shop['description']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="warning-box">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4>Attention !</h4>
                <p><strong>Cette action est irréversible.</strong></p>
                <p>La suppression de ce magasin entraînera :</p>
                <ul class="text-left" style="display: inline-block;">
                    <li>Suppression définitive de toutes les données du magasin</li>
                    <li>Suppression du logo et des fichiers associés</li>
                    <li>Arrêt de l'accès au sous-domaine</li>
                    <li>Perte de toutes les configurations</li>
                </ul>
            </div>
            
            <form method="POST" style="display: inline;">
                <div class="action-buttons">
                    <button type="submit" name="confirm_delete" class="btn-danger-custom" onclick="return confirm('Êtes-vous absolument sûr de vouloir supprimer ce magasin ? Cette action ne peut pas être annulée.');">
                        <i class="fas fa-trash-alt"></i>Supprimer définitivement
                    </button>
                    <a href="index.php" class="btn-secondary-custom">
                        <i class="fas fa-arrow-left"></i>Annuler
                    </a>
                </div>
            </form>
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
        });
    </script>
</body>
</html> 