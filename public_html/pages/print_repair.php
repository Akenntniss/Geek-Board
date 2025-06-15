<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    echo 'ID de réparation manquant';
    exit;
}

$repair_id = (int)$_GET['id'];

try {
    // Récupérer les détails de la réparation
    $sql = "
        SELECT r.*, 
               c.nom as client_nom, 
               c.prenom as client_prenom, 
               c.telephone as client_telephone, 
               c.email as client_email,
               e.nom as employe_nom,
               e.prenom as employe_prenom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN employes e ON r.employe_id = e.id
        WHERE r.id = ?
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        echo 'Réparation non trouvée';
        exit;
    }
    
    // Récupérer les photos de la réparation
    $photos_sql = "SELECT * FROM photos_reparation WHERE reparation_id = ? ORDER BY date_upload DESC";
    $photos_stmt = $shop_pdo->prepare($photos_sql);
    $photos_stmt->execute([$repair_id]);
    $photos = $photos_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les pièces commandées pour cette réparation
    $pieces_sql = "SELECT * FROM commandes_pieces WHERE reparation_id = ? ORDER BY date_creation DESC";
    $pieces_stmt = $shop_pdo->prepare($pieces_sql);
    $pieces_stmt->execute([$repair_id]);
    $pieces = $pieces_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo 'Erreur lors de la récupération des données: ' . $e->getMessage();
    exit;
}

// Fonction pour formater les dates
function format_print_date($date) {
    if (!$date) return 'Non spécifiée';
    return date('d/m/Y H:i', strtotime($date));
}

// Fonction pour formater les prix
function format_print_price($price) {
    if (!$price) return 'Non spécifié';
    return number_format($price, 2, ',', ' ') . ' €';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche de réparation #<?php echo $repair_id; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .company-logo {
            max-width: 150px;
            height: auto;
        }
        
        .repair-id {
            font-size: 16px;
            margin-top: 5px;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            margin-right: 5px;
        }
        
        .qr-code {
            text-align: center;
            margin-top: 20px;
        }
        
        .print-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .signature-section {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .signature-box {
            border: 1px solid #ddd;
            height: 100px;
            padding: 5px;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 15mm;
            }
            
            a {
                text-decoration: none;
                color: #000;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête d'impression -->
        <div class="print-header">
            <img src="assets/img/logo.png" alt="Logo" class="company-logo">
            <h1>Fiche de réparation</h1>
            <div class="repair-id">N° <?php echo $repair_id; ?></div>
            <div>Date d'impression: <?php echo date('d/m/Y H:i'); ?></div>
        </div>
        
        <div class="row">
            <!-- Informations client -->
            <div class="col-md-6 section">
                <h2 class="section-title">Client</h2>
                <div class="info-item">
                    <span class="info-label">Nom:</span>
                    <span><?php echo htmlspecialchars($repair['client_nom'] . ' ' . $repair['client_prenom']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Téléphone:</span>
                    <span><?php echo htmlspecialchars($repair['client_telephone']); ?></span>
                </div>
                <?php if (!empty($repair['client_email'])): ?>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span><?php echo htmlspecialchars($repair['client_email']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Informations appareil -->
            <div class="col-md-6 section">
                <h2 class="section-title">Appareil</h2>
                <div class="info-item">
                    <span class="info-label">Type:</span>
                    <span><?php echo htmlspecialchars($repair['type_appareil']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Marque:</span>
                    <span><?php echo htmlspecialchars($repair['marque']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Modèle:</span>
                    <span><?php echo htmlspecialchars($repair['modele']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Détails réparation -->
            <div class="col-12 section">
                <h2 class="section-title">Détails de la réparation</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Date de réception:</span>
                        <span><?php echo format_print_date($repair['date_reception']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Statut:</span>
                        <span><?php echo htmlspecialchars($repair['statut']); ?></span>
                    </div>
                    <?php if (!empty($repair['date_fin_prevue'])): ?>
                    <div class="info-item">
                        <span class="info-label">Date de fin prévue:</span>
                        <span><?php echo format_print_date($repair['date_fin_prevue']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Prix:</span>
                        <span><?php echo format_print_price($repair['prix_reparation']); ?></span>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="info-label">Description du problème:</div>
                    <div><?php echo nl2br(htmlspecialchars($repair['description_probleme'])); ?></div>
                </div>
                
                <?php if (!empty($repair['notes_techniques'])): ?>
                <div class="mt-3">
                    <div class="info-label">Notes techniques:</div>
                    <div><?php echo nl2br(htmlspecialchars($repair['notes_techniques'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($pieces)): ?>
        <!-- Pièces utilisées -->
        <div class="row">
            <div class="col-12 section">
                <h2 class="section-title">Pièces utilisées</h2>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Quantité</th>
                            <th>Prix estimé</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pieces as $piece): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($piece['nom_piece']); ?></td>
                            <td><?php echo htmlspecialchars($piece['description'] ?? ''); ?></td>
                            <td><?php echo $piece['quantite']; ?></td>
                            <td><?php echo format_print_price($piece['prix_estime']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Section signature -->
        <div class="signature-section">
            <div>
                <div class="signature-label">Signature technicien:</div>
                <div class="signature-box"></div>
            </div>
            <div>
                <div class="signature-label">Signature client:</div>
                <div class="signature-box"></div>
            </div>
        </div>
        
        <!-- QR Code -->
        <div class="qr-code">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode('repair_id=' . $repair_id); ?>" alt="QR Code">
            <div>Scannez pour vérifier la réparation</div>
        </div>
        
        <!-- Pied de page d'impression -->
        <div class="print-footer">
            <div>Document généré le <?php echo date('d/m/Y à H:i'); ?></div>
            <div>© <?php echo date('Y'); ?> - Tous droits réservés</div>
        </div>
    </div>
    
    <!-- Boutons d'impression - ne seront pas imprimés -->
    <div class="container mt-4 no-print">
        <div class="d-flex justify-content-center">
            <button class="btn btn-primary me-2" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Imprimer
            </button>
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="fas fa-times me-2"></i> Fermer
            </button>
        </div>
    </div>
</body>
</html> 