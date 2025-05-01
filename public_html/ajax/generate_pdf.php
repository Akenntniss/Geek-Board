<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_GET['id'])) {
    die('ID de réparation non fourni');
}

$reparation_id = intval($_GET['id']);

try {
    // Récupérer les informations de la réparation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email
        FROM reparations r
        INNER JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reparation) {
        die('Réparation non trouvée');
    }

    // Définir l'en-tête pour forcer le téléchargement
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="Reparation_' . $reparation_id . '.html"');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réparation #<?php echo $reparation_id; ?></title>
    <style>
        @page {
            size: landscape;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #fff;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .main-content {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .left-column {
            flex: 1;
        }
        .right-column {
            flex: 1;
        }
        .section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .section-header {
            background: #f8f9fa;
            color: #2c3e50;
            padding: 10px 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px 4px 0 0;
        }
        .section-content {
            padding: 15px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: baseline;
        }
        .label {
            font-weight: bold;
            color: #666;
            width: 140px;
            flex-shrink: 0;
        }
        .value {
            flex: 1;
        }
        .urgent {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .photos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .photo {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .price {
            font-size: 18px;
            color: #2c3e50;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        @media print {
            body {
                padding: 0;
                margin: 15mm;
            }
            .section {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .no-print {
                display: none;
            }
            .photos {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Fiche de Réparation #<?php echo $reparation_id; ?></h1>
        </div>
        
        <div class="main-content">
            <div class="left-column">
                <div class="section">
                    <div class="section-header">
                        <i class="fas fa-user"></i> Informations Client
                    </div>
                    <div class="section-content">
                        <div class="info-row">
                            <span class="label">Nom:</span>
                            <span class="value"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Téléphone:</span>
                            <span class="value"><?php echo htmlspecialchars($reparation['client_telephone']); ?></span>
                        </div>
                        <?php if ($reparation['client_email']): ?>
                        <div class="info-row">
                            <span class="label">Email:</span>
                            <span class="value"><?php echo htmlspecialchars($reparation['client_email']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section">
                    <div class="section-header">
                        <i class="fas fa-mobile-alt"></i> Informations Appareil
                    </div>
                    <div class="section-content">
                        <div class="info-row">
                            <span class="label">Type:</span>
                            <span class="value"><?php echo htmlspecialchars($reparation['type_appareil']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Marque:</span>
                            <span class="value"><?php echo htmlspecialchars($reparation['marque']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Modèle:</span>
                            <span class="value"><?php echo htmlspecialchars($reparation['modele']); ?></span>
                        </div>
                        <?php if ($reparation['mot_de_passe']): ?>
                        <div class="info-row">
                            <span class="label">Mot de passe:</span>
                            <span class="value"><?php echo htmlspecialchars($reparation['mot_de_passe']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section">
                    <div class="section-header">
                        <i class="fas fa-info-circle"></i> Détails de la Réparation
                    </div>
                    <div class="section-content">
                        <div class="info-row">
                            <span class="label">Date de réception:</span>
                            <span class="value"><?php echo date('d/m/Y', strtotime($reparation['date_reception'])); ?></span>
                        </div>
                        <?php if ($reparation['date_fin_prevue']): ?>
                        <div class="info-row">
                            <span class="label">Date de fin prévue:</span>
                            <span class="value"><?php echo date('d/m/Y', strtotime($reparation['date_fin_prevue'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="label">Statut:</span>
                            <span class="value">
                                <?php echo htmlspecialchars($reparation['statut']); ?>
                                <?php if ($reparation['urgent']): ?>
                                    <span class="urgent">URGENT</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if ($reparation['prix_reparation']): ?>
                        <div class="info-row">
                            <span class="label">Prix total:</span>
                            <span class="value price"><?php echo number_format($reparation['prix_reparation'], 2, ',', ' '); ?> €</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="right-column">
                <div class="section">
                    <div class="section-header">
                        <i class="fas fa-clipboard"></i> Description du Problème
                    </div>
                    <div class="section-content">
                        <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
                    </div>
                </div>

                <?php if ($reparation['notes_techniques']): ?>
                <div class="section">
                    <div class="section-header">
                        <i class="fas fa-tools"></i> Notes Techniques
                    </div>
                    <div class="section-content">
                        <?php echo nl2br(htmlspecialchars($reparation['notes_techniques'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($reparation['photos'] || $reparation['photo_appareil']): ?>
                <div class="section">
                    <div class="section-header">
                        <i class="fas fa-camera"></i> Photos
                    </div>
                    <div class="section-content">
                        <div class="photos">
                            <?php if ($reparation['photo_appareil'] && file_exists('../' . $reparation['photo_appareil'])): ?>
                                <img src="<?php echo '../' . $reparation['photo_appareil']; ?>" alt="Photo principale" class="photo">
                            <?php endif; ?>
                            
                            <?php
                            if ($reparation['photos']) {
                                $photos = json_decode($reparation['photos'], true);
                                if (is_array($photos)) {
                                    foreach ($photos as $photo) {
                                        if (file_exists('../' . $photo)) {
                                            echo '<img src="../' . $photo . '" alt="Photo supplémentaire" class="photo">';
                                        }
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            Document généré le <?php echo date('d/m/Y à H:i'); ?>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #2c3e50; color: white; border: none; border-radius: 4px;">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    <!-- Ajout de Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
<?php
} catch (PDOException $e) {
    die('Erreur lors de la génération du document: ' . $e->getMessage());
} 