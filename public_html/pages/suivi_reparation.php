<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser les variables
$resultats = [];
$recherche_effectuee = false;
$message_erreur = '';
$reparation_id = '';
$client_email = '';

// Traiter le formulaire de recherche
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recherche_effectuee = true;
    
    if (isset($_POST['reparation_id']) && !empty($_POST['reparation_id'])) {
        // Recherche par ID de réparation
        $reparation_id = cleanInput($_POST['reparation_id']);
        
        try {
            $shop_pdo = getShopDBConnection();
            $stmt = $shop_pdo->prepare("
                SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                       s.nom as statut_nom, sc.nom as statut_categorie_nom, sc.couleur as statut_couleur,
                       (SELECT COUNT(*) FROM photos_reparation WHERE reparation_id = r.id) as nb_photos,
                       (SELECT COUNT(*) FROM reparation_logs WHERE reparation_id = r.id) as nb_logs
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                LEFT JOIN statuts s ON r.statut = s.code
                LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
                WHERE r.id = ?
            ");
            $stmt->execute([$reparation_id]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($resultats)) {
                $message_erreur = "Aucune réparation trouvée avec cet identifiant.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la recherche: " . $e->getMessage();
        }
    } elseif (isset($_POST['client_email']) && !empty($_POST['client_email'])) {
        // Recherche par email du client
        $client_email = cleanInput($_POST['client_email']);
        
        try {
            $stmt = $shop_pdo->prepare("
                SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                       s.nom as statut_nom, sc.nom as statut_categorie_nom, sc.couleur as statut_couleur,
                       (SELECT COUNT(*) FROM photos_reparation WHERE reparation_id = r.id) as nb_photos,
                       (SELECT COUNT(*) FROM reparation_logs WHERE reparation_id = r.id) as nb_logs
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                LEFT JOIN statuts s ON r.statut = s.code
                LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
                WHERE c.email = ?
                ORDER BY r.date_reception DESC
            ");
            $stmt->execute([$client_email]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($resultats)) {
                $message_erreur = "Aucune réparation trouvée pour cette adresse email.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la recherche: " . $e->getMessage();
        }
    } else {
        $message_erreur = "Veuillez saisir un identifiant de réparation ou une adresse email.";
    }
}

// Fonction pour obtenir le dernier log de réparation
function getDernierLog($reparation_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->prepare("
            SELECT rl.*, e.full_name as employe_nom 
            FROM reparation_logs rl
            LEFT JOIN employes e ON rl.employe_id = e.id
            WHERE rl.reparation_id = ?
            ORDER BY rl.date_action DESC
            LIMIT 1
        ");
        $stmt->execute([$reparation_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Fonction pour obtenir les photos d'une réparation
function getPhotosReparation($reparation_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM photos_reparation
            WHERE reparation_id = ?
            ORDER BY date_upload DESC
        ");
        $stmt->execute([$reparation_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Fonction pour obtenir l'historique des statuts
function getHistoriqueStatuts($reparation_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->prepare("
            SELECT rl.*, e.full_name as employe_nom 
            FROM reparation_logs rl
            LEFT JOIN employes e ON rl.employe_id = e.id
            WHERE rl.reparation_id = ? AND rl.action_type = 'changement_statut'
            ORDER BY rl.date_action DESC
        ");
        $stmt->execute([$reparation_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Suivi de réparation - MD Geek</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0078e8;
            --secondary-color: #37a1ff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
            padding-top: 20px;
            padding-bottom: 50px;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            text-align: center;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .search-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-control, .btn {
            border-radius: 8px;
            padding: 12px 15px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            font-weight: 600;
        }
        
        .repair-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .repair-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .repair-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .repair-card .card-body {
            padding: 20px;
        }
        
        .status-badge {
            font-size: 14px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 25px;
        }
        
        .repair-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .repair-info-item {
            display: flex;
            align-items: flex-start;
        }
        
        .repair-info-item i {
            margin-right: 10px;
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }
        
        .timeline {
            position: relative;
            padding-left: 40px;
            margin-top: 30px;
        }
        
        .timeline:before {
            content: "";
            position: absolute;
            left: 16px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-point {
            position: absolute;
            left: -40px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
            top: 5px;
        }
        
        .timeline-content {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        
        .timeline-date {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .photos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        
        .photo-item {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .photo-item:hover {
            transform: scale(1.05);
        }
        
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 20px 0;
            }
            
            .search-container {
                padding: 15px;
            }
            
            .form-control, .btn {
                padding: 10px;
            }
            
            .photos-container {
                gap: 5px;
            }
            
            .photo-item {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="mb-2">Suivi de réparation</h1>
            <p class="mb-0">Consultez l'état d'avancement de votre réparation</p>
        </div>
        
        <div class="search-container">
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="reparation_id" class="form-label">Numéro de réparation</label>
                        <input type="text" class="form-control" id="reparation_id" name="reparation_id" placeholder="Ex: 12345" value="<?php echo htmlspecialchars($reparation_id); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="client_email" class="form-label">OU votre adresse email</label>
                        <input type="email" class="form-control" id="client_email" name="client_email" placeholder="Ex: client@exemple.com" value="<?php echo htmlspecialchars($client_email); ?>">
                    </div>
                    <div class="col-12 text-center mt-4">
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($recherche_effectuee): ?>
            <?php if (!empty($message_erreur)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $message_erreur; ?>
                </div>
            <?php elseif (!empty($resultats)): ?>
                <h3 class="mb-4"><?php echo count($resultats) > 1 ? 'Vos réparations' : 'Votre réparation'; ?></h3>
                
                <?php foreach ($resultats as $reparation): ?>
                    <div class="repair-card">
                        <div class="card-header">
                            <h4 class="mb-0">Réparation #<?php echo $reparation['id']; ?></h4>
                            <?php
                            $couleur = !empty($reparation['statut_couleur']) ? $reparation['statut_couleur'] : '#6c757d';
                            $statut_nom = !empty($reparation['statut_nom']) ? $reparation['statut_nom'] : $reparation['statut'];
                            ?>
                            <span class="status-badge" style="background-color: <?php echo $couleur; ?>; color: #fff;">
                                <?php echo htmlspecialchars($statut_nom); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="repair-info">
                                        <div class="repair-info-item">
                                            <i class="fas fa-laptop"></i>
                                            <div>
                                                <strong>Appareil:</strong><br>
                                                <?php echo htmlspecialchars($reparation['type_appareil']); ?> <?php echo htmlspecialchars($reparation['marque']); ?> <?php echo htmlspecialchars($reparation['modele']); ?>
                                            </div>
                                        </div>
                                        <div class="repair-info-item">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <div>
                                                <strong>Problème:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
                                            </div>
                                        </div>
                                        <div class="repair-info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <div>
                                                <strong>Date de réception:</strong><br>
                                                <?php echo date('d/m/Y', strtotime($reparation['date_reception'])); ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($reparation['prix_reparation'])): ?>
                                        <div class="repair-info-item">
                                            <i class="fas fa-euro-sign"></i>
                                            <div>
                                                <strong>Prix estimé:</strong><br>
                                                <?php echo number_format($reparation['prix_reparation'], 2, ',', ' '); ?> €
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Progression</h5>
                                    <div class="timeline">
                                        <?php 
                                        $historique = getHistoriqueStatuts($reparation['id']);
                                        foreach ($historique as $log): 
                                        ?>
                                        <div class="timeline-item">
                                            <div class="timeline-point"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-date">
                                                    <?php echo date('d/m/Y H:i', strtotime($log['date_action'])); ?>
                                                </div>
                                                <div class="timeline-text">
                                                    Statut modifié: 
                                                    <strong><?php echo $log['statut_avant'] ? htmlspecialchars($log['statut_avant']) : 'Initial'; ?></strong> 
                                                    <i class="fas fa-arrow-right mx-1"></i> 
                                                    <strong><?php echo htmlspecialchars($log['statut_apres']); ?></strong>
                                                </div>
                                                <?php if (!empty($log['details'])): ?>
                                                <div class="timeline-details mt-2">
                                                    <?php echo nl2br(htmlspecialchars($log['details'])); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($historique)): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-point"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-date">
                                                    <?php echo date('d/m/Y H:i', strtotime($reparation['date_reception'])); ?>
                                                </div>
                                                <div class="timeline-text">
                                                    Réception de l'appareil
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php 
                                    $photos = getPhotosReparation($reparation['id']);
                                    if (!empty($photos)): 
                                    ?>
                                    <h5 class="mb-3 mt-4">Photos</h5>
                                    <div class="photos-container">
                                        <?php foreach ($photos as $index => $photo): ?>
                                        <div class="photo-item" data-bs-toggle="modal" data-bs-target="#photoModal<?php echo $reparation['id']; ?>" data-index="<?php echo $index; ?>">
                                            <img src="<?php echo htmlspecialchars($photo['url']); ?>" alt="Photo <?php echo $index + 1; ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Modal pour les photos -->
                                    <div class="modal fade" id="photoModal<?php echo $reparation['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Photos de la réparation</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <img id="modalImage<?php echo $reparation['id']; ?>" src="" class="img-fluid" alt="Photo agrandie">
                                                    <p id="photoDescription<?php echo $reparation['id']; ?>" class="mt-3"></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des modals de photos
        <?php foreach ($resultats as $reparation): ?>
        const photoItems<?php echo $reparation['id']; ?> = document.querySelectorAll('[data-bs-target="#photoModal<?php echo $reparation['id']; ?>"]');
        const modalImage<?php echo $reparation['id']; ?> = document.getElementById('modalImage<?php echo $reparation['id']; ?>');
        const photoDescription<?php echo $reparation['id']; ?> = document.getElementById('photoDescription<?php echo $reparation['id']; ?>');
        
        if (photoItems<?php echo $reparation['id']; ?>.length) {
            const photos<?php echo $reparation['id']; ?> = <?php echo json_encode(getPhotosReparation($reparation['id'])); ?>;
            
            photoItems<?php echo $reparation['id']; ?>.forEach(item => {
                item.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    const photo = photos<?php echo $reparation['id']; ?>[index];
                    
                    modalImage<?php echo $reparation['id']; ?>.src = photo.url;
                    photoDescription<?php echo $reparation['id']; ?>.textContent = photo.description || 'Aucune description disponible';
                });
            });
        }
        <?php endforeach; ?>
        
        // Empêcher la soumission du formulaire si les deux champs sont vides
        document.querySelector('form').addEventListener('submit', function(e) {
            const repairId = document.getElementById('reparation_id').value.trim();
            const clientEmail = document.getElementById('client_email').value.trim();
            
            if (!repairId && !clientEmail) {
                e.preventDefault();
                alert('Veuillez entrer un numéro de réparation ou une adresse email.');
            }
        });
    });
    </script>
</body>
</html> 