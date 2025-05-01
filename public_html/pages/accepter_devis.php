<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser les variables
$reparation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';
$success = false;
$reparation = null;
$client = null;

// Vérifier si l'ID de réparation et le token sont fournis
if ($reparation_id && $token) {
    try {
        // Vérifier si la réparation existe et si elle est en attente d'accord client
        $stmt = $pdo->prepare("
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            WHERE r.id = ? AND r.statut = 'en_attente_accord_client'
        ");
        $stmt->execute([$reparation_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reparation) {
            // Vérifier le token (à implémenter selon votre logique de sécurité)
            // Ici nous utilisons un hash simple basé sur l'ID et l'email client
            $expected_token = md5($reparation_id . $reparation['client_email'] . 'secret_key');
            
            if ($token === $expected_token) {
                // Si le formulaire a été soumis pour accepter le devis
                if (isset($_POST['accepter_devis'])) {
                    // Récupérer le statut en cours de réparation
                    $stmt = $pdo->prepare("SELECT id FROM statuts_reparation WHERE code = 'en_cours_intervention'");
                    $stmt->execute();
                    $nouveau_statut = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($nouveau_statut) {
                        // Récupérer l'ancien statut de la réparation
                        $old_status = $reparation['statut'];
                        
                        // Mettre à jour le statut de la réparation
                        $stmt = $pdo->prepare("
                            UPDATE reparations 
                            SET statut = 'en_cours_intervention', 
                                statut_id = ?, 
                                date_modification = NOW() 
                            WHERE id = ?
                        ");
                        $result = $stmt->execute([$nouveau_statut['id'], $reparation_id]);
                        
                        if ($result) {
                            // Ajouter une entrée dans le journal des modifications
                            $stmt = $pdo->prepare("
                                INSERT INTO reparation_logs 
                                (reparation_id, action_type, statut_avant, statut_apres, date_action, details) 
                                VALUES (?, 'changement_statut', ?, 'en_cours_intervention', NOW(), ?)
                            ");
                            
                            $details = "Devis accepté par le client via la page web d'acceptation de devis.";
                            
                            $stmt->execute([
                                $reparation_id, 
                                $old_status, 
                                $details
                            ]);
                            
                            $success = true;
                            $message = "Devis accepté avec succès ! Nous allons procéder à la réparation de votre appareil.";
                        } else {
                            $message = "Une erreur s'est produite lors de la mise à jour du statut.";
                        }
                    } else {
                        $message = "Statut de réparation en cours non trouvé.";
                    }
                }
            } else {
                $message = "Token invalide. Veuillez vérifier le lien que vous avez reçu.";
                $reparation = null;
            }
        } else {
            $message = "Réparation non trouvée ou statut incorrect.";
        }
    } catch (PDOException $e) {
        $message = "Erreur lors de la vérification de la réparation: " . $e->getMessage();
    }
} else {
    $message = "Paramètres manquants. Veuillez vérifier le lien que vous avez reçu.";
}

// Fonction pour formater le prix
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Acceptation de devis - Réparation #<?php echo $reparation_id; ?></title>
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
        
        .devis-container {
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
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #1d943a 100%);
            border: none;
            font-weight: 600;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #b52d39 100%);
            border: none;
            font-weight: 600;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-item .label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .info-item .value {
            font-weight: 500;
        }
        
        .price-tag {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 20px 0;
            text-align: center;
        }
        
        .confirmation-box {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .confirmation-box.success {
            background-color: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .confirmation-box.error {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .message-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .success-icon {
            color: var(--success-color);
        }
        
        .error-icon {
            color: var(--danger-color);
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 20px 0;
            }
            
            .devis-container {
                padding: 15px;
            }
            
            .price-tag {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="mb-2">Acceptation de devis</h1>
            <p class="mb-0">Réparation #<?php echo $reparation_id; ?></p>
        </div>
        
        <?php if ($message): ?>
            <div class="confirmation-box <?php echo $success ? 'success' : 'error'; ?>">
                <div class="message-icon <?php echo $success ? 'success-icon' : 'error-icon'; ?>">
                    <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                </div>
                <h3><?php echo $success ? 'Devis accepté avec succès' : 'Une erreur est survenue'; ?></h3>
                <p><?php echo $message; ?></p>
                <?php if ($success): ?>
                    <div class="mt-4">
                        <p>Nous allons procéder à la réparation de votre appareil dans les plus brefs délais.</p>
                        <p>Vous recevrez une notification lorsque votre appareil sera prêt à être récupéré.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($reparation && !$success): ?>
            <div class="devis-container">
                <h2 class="mb-4">Détails de la réparation</h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="label">Client</div>
                            <div class="value"><?php echo htmlspecialchars($reparation['client_prenom'] . ' ' . $reparation['client_nom']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Appareil</div>
                            <div class="value"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['marque'] . ' ' . $reparation['modele']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Problème</div>
                            <div class="value"><?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Date de réception</div>
                            <div class="value"><?php echo date('d/m/Y', strtotime($reparation['date_reception'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <?php if (!empty($reparation['notes_techniques'])): ?>
                            <div class="info-item">
                                <div class="label">Diagnostic</div>
                                <div class="value"><?php echo nl2br(htmlspecialchars($reparation['notes_techniques'])); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($reparation['prix_reparation'])): ?>
                            <div class="price-tag">
                                Montant du devis: <?php echo formatPrice($reparation['prix_reparation']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            En acceptant ce devis, vous autorisez notre équipe à effectuer la réparation de votre appareil selon les modalités indiquées ci-dessus.
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <form method="POST" action="">
                            <button type="submit" name="accepter_devis" class="btn btn-success btn-lg px-5 me-3">
                                <i class="fas fa-check me-2"></i>Accepter le devis
                            </button>
                            <a href="#" class="btn btn-danger btn-lg px-5">
                                <i class="fas fa-times me-2"></i>Refuser
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 