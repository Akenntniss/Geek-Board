<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à ces données
// Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}
*/

// Vérifier l'ID du rachat
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }

    // Vérifier d'abord quelles colonnes existent dans la table clients
    $columns_to_select = ['c.nom', 'c.prenom', 'c.telephone'];
    
    // Vérifier si la colonne adresse existe
    try {
        $check_adresse = $pdo->query("SHOW COLUMNS FROM clients LIKE 'adresse'");
        if ($check_adresse && $check_adresse->rowCount() > 0) {
            $columns_to_select[] = 'c.adresse';
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la colonne adresse: " . $e->getMessage());
    }
    
    $client_columns = implode(', ', $columns_to_select);
    
    $stmt = $pdo->prepare("SELECT 
        r.id,
        r.date_rachat,
        r.type_appareil,
        r.modele,
        r.sin,
        r.prix,
        r.fonctionnel,
        r.photo_identite,
        r.photo_appareil,
        r.client_photo,
        r.signature,
        {$client_columns}
    FROM rachat_appareils r
    JOIN clients c ON r.client_id = c.id
    WHERE r.id = ?");
    
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Rachat introuvable']);
        exit;
    }
    
    // Récupérer les informations de la boutique
    $shop_id = $_SESSION['shop_id'] ?? null;
    $shop_info = null;
    if ($shop_id) {
        $main_pdo = getMainDBConnection();
        $stmt_shop = $main_pdo->prepare("
            SELECT name, address, city, postal_code, country, phone, email
            FROM shops 
            WHERE id = ?
        ");
        $stmt_shop->execute([$shop_id]);
        $shop_info = $stmt_shop->fetch(PDO::FETCH_ASSOC);
    }
    
    // S'assurer que la clé adresse existe même si la colonne n'est pas en base
    if (!isset($result['adresse'])) {
        $result['adresse'] = '';
    }

    // Transformer les chemins relatifs en URL complètes
    if (!empty($result['photo_identite'])) {
        $result['photo_identite'] = '/assets/images/rachat/' . $result['photo_identite'];
    }
    if (!empty($result['photo_appareil'])) {
        $result['photo_appareil'] = '/assets/images/rachat/' . $result['photo_appareil'];
    }

    // Récupérer le contenu des photos stockées en base64
    if (!empty($result['client_photo'])) {
        $client_photo_path = __DIR__ . '/../assets/images/rachat/' . $result['client_photo'];
        if (file_exists($client_photo_path)) {
            $photo_content = base64_encode(file_get_contents($client_photo_path));
            $result['client_photo'] = 'data:image/jpeg;base64,' . $photo_content;
        } else {
            $result['client_photo'] = null;
        }
    }

    if (!empty($result['signature'])) {
        $signature_path = __DIR__ . '/../assets/images/rachat/' . $result['signature'];
        if (file_exists($signature_path)) {
            $signature_content = base64_encode(file_get_contents($signature_path));
            $result['signature'] = 'data:image/png;base64,' . $signature_content;
        } else {
            $result['signature'] = null;
        }
    }

    // Formater la date
    $date = new DateTime($result['date_rachat']);
    $result['date_formatted'] = $date->format('d/m/Y');

    // Formater le prix avec le symbole euro
    $result['prix_formatted'] = number_format($result['prix'], 2, ',', ' ') . ' €';

    // Générer le HTML de l'attestation moderne et professionnelle
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Attestation de Rachat Professionnelle #<?= $result['id'] ?></title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
                         body {
                 font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                 line-height: 1.4;
                 color: #2c3e50;
                 background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                 min-height: 100vh;
                 padding: 20px 10px;
             }
            
                         .container {
                 max-width: 210mm;
                 min-height: 297mm;
                 margin: 0 auto;
                 background: #ffffff;
                 border-radius: 8px;
                 box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                 overflow: hidden;
                 position: relative;
                 display: flex;
                 flex-direction: column;
             }
            
                         .header {
                 background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                 color: white;
                 padding: 12px 25px;
                 text-align: center;
                 position: relative;
                 flex-shrink: 0;
             }
            
            .header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
                opacity: 0.3;
            }
            
            .company-info {
                position: absolute;
                top: 15px;
                right: 20px;
                text-align: right;
                z-index: 2;
                font-size: 10px;
                line-height: 1.3;
            }
            
            .company-logo {
                width: 68px;
                height: 68px;
                border-radius: 4px;
                margin-bottom: 8px;
                margin-left: auto;
                display: block;
            }
            
            .company-details {
                color: rgba(255, 255, 255, 0.9);
                font-weight: 400;
            }
            
                         .title {
                 font-size: 20px;
                 font-weight: 700;
                 margin-bottom: 5px;
                 position: relative;
                 z-index: 1;
                 letter-spacing: 0.5px;
             }
            
            .subtitle {
                font-size: 14px;
                font-weight: 300;
                opacity: 0.9;
                position: relative;
                z-index: 1;
            }
            
            .document-number {
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.2);
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 500;
                z-index: 1;
            }
            
                         .content {
                 padding: 15px 25px;
                 flex: 1;
             }
            
            .date-badge {
                display: inline-block;
                background: #f8f9fa;
                border: 2px solid #e9ecef;
                border-radius: 20px;
                padding: 8px 16px;
                margin-bottom: 15px;
                font-weight: 600;
                color: #495057;
                font-size: 14px;
            }
            
                         .info-grid {
                 display: grid;
                 grid-template-columns: 1fr 1fr;
                 gap: 15px;
                 margin-bottom: 15px;
             }
            
                         .info-card {
                 background: #f8f9fa;
                 border-radius: 6px;
                 padding: 12px;
                 border-left: 4px solid #667eea;
                 transition: transform 0.2s ease;
             }
            
            .info-card:hover {
                transform: translateY(-2px);
            }
            
            .card-title {
                font-size: 14px;
                font-weight: 600;
                color: #6c757d;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 12px;
            }
            
            .card-content {
                font-size: 16px;
                color: #2c3e50;
                font-weight: 500;
            }
            
                         .device-info {
                 background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                 border-radius: 6px;
                 padding: 12px;
                 margin-bottom: 15px;
                 border: 1px solid #dee2e6;
             }
            
                         .device-title {
                 font-size: 16px;
                 font-weight: 600;
                 color: #2c3e50;
                 margin-bottom: 10px;
                 display: flex;
                 align-items: center;
             }
             
             .device-title::before {
                 content: '📱';
                 margin-right: 6px;
                 font-size: 16px;
             }
             
             .device-details {
                 display: grid;
                 grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                 gap: 10px;
             }
             
             .device-detail {
                 display: flex;
                 justify-content: space-between;
                 padding: 4px 0;
                 border-bottom: 1px solid #dee2e6;
             }
            
            .device-detail:last-child {
                border-bottom: none;
            }
            
            .detail-label {
                font-weight: 500;
                color: #6c757d;
            }
            
            .detail-value {
                font-weight: 600;
                color: #2c3e50;
            }
            
            
            
            
            
                         .signature-section {
                 background: #f8f9fa;
                 border-radius: 6px;
                 padding: 10px;
                 margin-top: 15px;
                 text-align: center;
             }
             
             .conditions-section {
                 background: #f8f9fa;
                 border-radius: 6px;
                 padding: 12px;
                 margin-top: 15px;
                 border: 1px solid #dee2e6;
             }
             
             .conditions-title {
                 font-size: 14px;
                 font-weight: 600;
                 color: #2c3e50;
                 margin-bottom: 8px;
                 text-align: center;
             }
             
             .conditions-content {
                 font-size: 9px;
                 line-height: 1.3;
                 color: #495057;
                 text-align: justify;
             }
             
             .conditions-content p {
                 margin-bottom: 4px;
             }
             
             .conditions-content ul {
                 margin: 4px 0;
                 padding-left: 12px;
             }
             
             .conditions-content li {
                 margin-bottom: 2px;
             }
            
                         .signature-title {
                 font-size: 14px;
                 font-weight: 600;
                 color: #2c3e50;
                 margin-bottom: 8px;
             }
             
             .signature-image {
                 max-width: 200px;
                 max-height: 80px;
                 border: 1px solid #dee2e6;
                 border-radius: 4px;
                 background: white;
                 padding: 5px;
             }
            
                         .footer {
                 background: #f8f9fa;
                 padding: 8px 25px;
                 text-align: center;
                 border-top: 1px solid #dee2e6;
                 font-size: 8px;
                 color: #6c757d;
                 flex-shrink: 0;
                 margin-top: auto;
             }
            
            .status-badge {
                display: inline-block;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .status-functional {
                background: #d4edda;
                color: #155724;
            }
            
            .status-non-functional {
                background: #f8d7da;
                color: #721c24;
            }
            
            @media print {
                body {
                    background: white;
                    padding: 0;
                }
                
                .container {
                    box-shadow: none;
                    border-radius: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="document-number">N° <?= htmlspecialchars($result['id']) ?></div>
                
                <?php if ($shop_info): ?>
                <div class="company-info">
                    <img src="/assets/images/logo/entreprise.png" alt="Logo" class="company-logo">
                    <div class="company-details">
                        <div><strong><?= htmlspecialchars($shop_info['name'] ?? '') ?></strong></div>
                        <div><?= htmlspecialchars($shop_info['address'] ?? '') ?></div>
                        <div><?= htmlspecialchars($shop_info['postal_code'] ?? '') ?> <?= htmlspecialchars($shop_info['city'] ?? '') ?></div>
                        <div><?= htmlspecialchars($shop_info['country'] ?? '') ?></div>
                        <div>📞 <?= htmlspecialchars($shop_info['phone'] ?? '') ?></div>
                        <?php if (!empty($shop_info['email'])): ?>
                        <div>✉️ <?= htmlspecialchars($shop_info['email']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="title">ATTESTATION DE RACHAT</div>
                <div class="subtitle">Document officiel de transaction</div>
            </div>
            
            <div class="content">
                <div class="date-badge">
                    📅 Date de rachat : <?= htmlspecialchars($result['date_formatted']) ?>
                </div>
                
                <div class="info-grid">
                    <div class="info-card">
                        <div class="card-title">👤 Informations Client</div>
                        <div class="card-content">
                            <strong><?= htmlspecialchars($result['nom'] . ' ' . $result['prenom']) ?></strong><br>
                            📞 <?= htmlspecialchars($result['telephone']) ?><br>
                            <?php if (!empty($result['adresse'])): ?>
                            📍 <?= htmlspecialchars($result['adresse']) ?><br>
                            <?php endif; ?>
                            <?php if (!empty($result['email'])): ?>
                            ✉️ <?= htmlspecialchars($result['email']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="card-title">💰 Montant du Rachat</div>
                        <div class="card-content">
                            <div style="font-size: 24px; font-weight: 700; color: #28a745;">
                                <?= htmlspecialchars($result['prix_formatted']) ?>
                            </div>
                            <small style="color: #6c757d;">Montant convenu et accepté</small>
                        </div>
                    </div>
                </div>
                
                <div class="device-info">
                    <div class="device-title">Détails de l'Appareil Racheté</div>
                    <div class="device-details">
                        <div class="device-detail">
                            <span class="detail-label">Type d'appareil :</span>
                            <span class="detail-value"><?= htmlspecialchars($result['type_appareil']) ?></span>
                        </div>
                        <div class="device-detail">
                            <span class="detail-label">Modèle :</span>
                            <span class="detail-value"><?= htmlspecialchars($result['modele']) ?></span>
                        </div>
                        <div class="device-detail">
                            <span class="detail-label">SIN/IMEI :</span>
                            <span class="detail-value"><?= htmlspecialchars($result['sin']) ?></span>
                        </div>
                        <div class="device-detail">
                            <span class="detail-label">État de fonctionnement :</span>
                            <span class="detail-value">
                                <span class="status-badge <?= $result['fonctionnel'] ? 'status-functional' : 'status-non-functional' ?>">
                                    <?= $result['fonctionnel'] ? '✅ Fonctionnel' : '❌ Non fonctionnel' ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
                
                                 <div class="conditions-section">
                     <div class="conditions-title">📋 Conditions Générales de Rachat</div>
                     <div class="conditions-content">
                         <p><strong>1. OBJET :</strong> La présente attestation confirme le rachat de l'appareil décrit ci-dessus dans l'état où il se trouvait au moment de la transaction.</p>
                         
                         <p><strong>2. GARANTIES :</strong></p>
                         <ul>
                             <li>Le vendeur garantit être le propriétaire légitime de l'appareil et avoir le droit de le céder.</li>
                             <li>L'appareil est vendu en l'état, sans garantie de fonctionnement ultérieur.</li>
                             <li>Le vendeur certifie que l'appareil n'est pas volé, sous gage ou sous saisie.</li>
                         </ul>
                         
                         <p><strong>3. RESPONSABILITÉS :</strong></p>
                         <ul>
                             <li>L'acheteur s'engage à procéder à l'effacement sécurisé des données.</li>
                             <li>Le vendeur déclare avoir sauvegardé toutes ses données personnelles.</li>
                             <li>Aucune réclamation ne sera acceptée après signature de la présente.</li>
                         </ul>
                         
                         <p><strong>4. PRIX :</strong> Le prix convenu est ferme et définitif. Aucun complément de prix ne pourra être réclamé.</p>
                         
                         <p><strong>5. ACCEPTATION :</strong> La signature ou l'acceptation électronique vaut acceptation pleine et entière des présentes conditions.</p>
                     </div>
                 </div>
                
                                 <?php if ($result['signature']): ?>
                 <div class="signature-section">
                     <div class="signature-title">✍️ Signature Client</div>
                     <img src="<?= htmlspecialchars($result['signature']) ?>" alt="Signature du client" class="signature-image">
                     <div style="margin-top: 4px; font-size: 8px; color: #6c757d;">
                         Signature électronique confirmant l'accord
                     </div>
                 </div>
                 <?php endif; ?>
            </div>
            
            <div class="footer">
                <p><strong>Document généré automatiquement</strong> • <?= date('d/m/Y à H:i') ?></p>
                <p>Cette attestation fait foi de la transaction de rachat réalisée selon les conditions convenues entre les parties.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    if ($html) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'id' => $result['id']
        ]);
    } else {
        throw new Exception("Erreur lors de la génération du HTML");
    }

} catch (Exception $e) {
    error_log('Erreur: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
}
?> 