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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Configuration des sous-domaines</title>
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
                        <a class="nav-link" href="index.php">Magasins</a>
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
            <h1>Configuration des sous-domaines</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour
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
        
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Génération automatique</h5>
            </div>
            <div class="card-body">
                <p>
                    Cliquez sur le bouton ci-dessous pour générer automatiquement des sous-domaines pour tous les magasins qui n'en ont pas encore.
                    Les sous-domaines seront générés à partir du nom du magasin.
                </p>
                <form method="post" action="">
                    <button type="submit" name="generate_subdomains" class="btn btn-primary">
                        <i class="fas fa-magic me-2"></i>Générer les sous-domaines manquants
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Liste des magasins</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom du magasin</th>
                                <th>Sous-domaine</th>
                                <th>URL complète</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shops as $shop): ?>
                            <tr>
                                <td><?php echo $shop['id']; ?></td>
                                <td><?php echo htmlspecialchars($shop['name']); ?></td>
                                <td>
                                    <?php if (!empty($shop['subdomain'])): ?>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($shop['subdomain']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Non défini</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($shop['subdomain'])): ?>
                                        <a href="http://<?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top" target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top
                                            <i class="fas fa-external-link-alt ms-1 small"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $shop['id']; ?>">
                                        <i class="fas fa-edit me-1"></i>Modifier
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals pour l'édition des sous-domaines -->
    <?php foreach ($shops as $shop): ?>
    <div class="modal fade" id="editModal<?php echo $shop['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $shop['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel<?php echo $shop['id']; ?>">
                            Modifier le sous-domaine - <?php echo htmlspecialchars($shop['name']); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="shop_id" value="<?php echo $shop['id']; ?>">
                        <div class="mb-3">
                            <label for="subdomain<?php echo $shop['id']; ?>" class="form-label">Sous-domaine</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="subdomain<?php echo $shop['id']; ?>" name="subdomain" value="<?php echo htmlspecialchars($shop['subdomain'] ?? ''); ?>" placeholder="monmagasin" required>
                                <span class="input-group-text">.mdgeek.top</span>
                            </div>
                            <div class="form-text">
                                Le sous-domaine doit contenir uniquement des lettres minuscules, des chiffres et des tirets.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Bootstrap JS & Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 