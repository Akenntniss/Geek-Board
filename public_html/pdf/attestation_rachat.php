<?php
// Protection contre l'accès direct
if (!defined('INCLUDED_FROM_INDEX')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Accès direct interdit');
}

require_once __DIR__ . '/../config/database.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

// Fonction pour récupérer les données du rachat
function getRachatDetails($id) {
    global $shop_pdo;
    
    try {
        // Debug de la requête
        $sql = "
            SELECT r.*, c.nom, c.prenom, c.telephone, c.email, c.adresse
            FROM rachat_appareils r
            JOIN clients c ON r.client_id = c.id
            WHERE r.id = ?
        ";
        error_log("SQL: $sql");
        error_log("ID: $id");
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log de débogage
        if (!$result) {
            error_log("Rachat non trouvé dans la base de données. ID: $id");
            
            // Faire une requête directe pour voir le rachat
            $directQuery = $shop_pdo->prepare("SELECT * FROM rachat_appareils WHERE id = ?");
            $directQuery->execute([$id]);
            $directResult = $directQuery->fetch(PDO::FETCH_ASSOC);
            
            if ($directResult) {
                error_log("Rachat trouvé directement sans jointure: " . json_encode($directResult));
                
                // Vérifier le client
                $clientId = $directResult['client_id'];
                $clientQuery = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
                $clientQuery->execute([$clientId]);
                $clientResult = $clientQuery->fetch(PDO::FETCH_ASSOC);
                
                if ($clientResult) {
                    error_log("Client trouvé: " . json_encode($clientResult));
                    
                    // Retourner manuellement le résultat combiné
                    return array_merge($directResult, $clientResult);
                } else {
                    error_log("Client non trouvé pour l'ID: $clientId");
                }
            } else {
                error_log("Rachat non trouvé même avec une requête directe");
            }
            
            try {
                $checkTable = $shop_pdo->query("SHOW TABLES LIKE 'rachat_appareils'");
                $tableExists = $checkTable->rowCount() > 0;
                error_log("Table 'rachat_appareils' existe: " . ($tableExists ? 'Oui' : 'Non'));
                
                if ($tableExists) {
                    // Vérifier la structure de la table
                    $columns = $shop_pdo->query("DESCRIBE rachat_appareils")->fetchAll(PDO::FETCH_COLUMN);
                    error_log("Colonnes dans rachat_appareils: " . implode(', ', $columns));
                    
                    // Vérifier si l'ID existe
                    $checkId = $shop_pdo->prepare("SELECT COUNT(*) FROM rachat_appareils WHERE id = ?");
                    $checkId->execute([$id]);
                    $idExists = $checkId->fetchColumn() > 0;
                    error_log("ID $id existe dans la table rachat_appareils: " . ($idExists ? 'Oui' : 'Non'));
                    
                    if ($idExists) {
                        // Vérifier la jointure
                        $checkJoin = $shop_pdo->prepare("
                            SELECT c.id FROM rachat_appareils r 
                            LEFT JOIN clients c ON r.client_id = c.id 
                            WHERE r.id = ?
                        ");
                        $checkJoin->execute([$id]);
                        $joinResult = $checkJoin->fetch();
                        error_log("Jointure avec client: " . ($joinResult ? "OK, client_id=" . $joinResult['id'] : "Échouée"));
                    }
                }
            } catch (PDOException $e) {
                error_log("Erreur lors de la vérification de la table: " . $e->getMessage());
            }
        } else {
            error_log("Rachat trouvé: " . json_encode($result));
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Erreur PDO dans getRachatDetails: " . $e->getMessage());
        return false;
    }
}

// Créer une fonction de récupération manuelle des données
function getFullRachatDetails($id) {
    global $shop_pdo;
    
    try {
        // 1. Récupérer les données du rachat
        $rachatQuery = $shop_pdo->prepare("SELECT * FROM rachat_appareils WHERE id = ?");
        $rachatQuery->execute([$id]);
        $rachat = $rachatQuery->fetch(PDO::FETCH_ASSOC);
        
        if (!$rachat) {
            error_log("Rachat ID $id non trouvé dans la table rachat_appareils");
            return false;
        }
        
        // 2. Récupérer les données du client
        $clientQuery = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $clientQuery->execute([$rachat['client_id']]);
        $client = $clientQuery->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            error_log("Client ID {$rachat['client_id']} non trouvé");
            // On continue quand même avec les données du rachat seulement
        } else {
            // Ajouter les colonnes du client au rachat 
            // mais sans écraser les colonnes existantes du rachat
            foreach ($client as $key => $value) {
                if (!isset($rachat[$key])) {
                    $rachat[$key] = $value;
                }
            }
        }
        
        return $rachat;
    } catch (PDOException $e) {
        error_log("Erreur dans getFullRachatDetails: " . $e->getMessage());
        return false;
    }
}

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID de rachat manquant ou invalide']);
    exit;
}

$rachat_id = (int)$_GET['id'];

// Essayer d'abord avec la méthode de jointure (original)
$rachat = getRachatDetails($rachat_id);

// Si ça ne marche pas, essayer l'approche alternative
if (!$rachat) {
    error_log("Tentative de récupération alternative pour le rachat ID $rachat_id");
    $rachat = getFullRachatDetails($rachat_id);
}

if (!$rachat) {
    echo json_encode(['error' => 'Rachat non trouvé']);
    exit;
}

// S'assurer que toutes les colonnes nécessaires existent
if ($rachat) {
    // Liste des champs obligatoires pour le modèle
    $required_fields = [
        'type_appareil', 'modele', 'sin', 'fonctionnel', 'prix', 'date_rachat',
        'photo_appareil', 'photo_identite', 'signature', 'client_photo',
        'nom', 'prenom', 'telephone', 'email', 'adresse'
    ];
    
    // Initialiser les champs manquants
    foreach ($required_fields as $field) {
        if (!isset($rachat[$field])) {
            error_log("Champ manquant dans le rachat: $field");
            $rachat[$field] = '';
        }
    }
    
    // Journaliser les données finales
    error_log("Données finales du rachat: " . json_encode($rachat));
}

// Construire les URL complètes pour les images
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

// Formater les chemins des images comme dans details_rachat.php
$photo_appareil = $rachat['photo_appareil'] ? $base_url . '/assets/images/rachat/' . $rachat['photo_appareil'] : null;
$photo_identite = $rachat['photo_identite'] ? $base_url . '/assets/images/rachat/' . $rachat['photo_identite'] : null;

// Lire le contenu des fichiers de photo client et signature
$signature = '';
$client_photo = '';

if ($rachat['client_photo']) {
    $client_photo_path = __DIR__ . '/../assets/images/rachat/' . $rachat['client_photo'];
    if (file_exists($client_photo_path)) {
        $photo_content = base64_encode(file_get_contents($client_photo_path));
        $client_photo = 'data:image/jpeg;base64,' . $photo_content;
    }
}

if ($rachat['signature']) {
    $signature_path = __DIR__ . '/../assets/images/rachat/' . $rachat['signature'];
    if (file_exists($signature_path)) {
        $signature_content = base64_encode(file_get_contents($signature_path));
        $signature = 'data:image/png;base64,' . $signature_content;
    }
}

// Générer un nom de fichier unique
$filename = 'attestation_rachat_' . $rachat_id . '_' . date('Y-m-d_His') . '.pdf';

// HTML pour le PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attestation de Rachat d\'Appareil</title>
    <style>
        @page {
            size: A4;
            margin: 10mm 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            font-size: 12px;
            background-color: #fff;
        }
        .company-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .company-info {
            font-weight: bold;
            line-height: 1.3;
        }
        .company-name {
            font-size: 16px;
            color: #0d6efd;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .company-address {
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 5px;
            clear: both;
        }
        h1 {
            color: #0d6efd;
            font-size: 20px;
            margin: 0;
            font-weight: bold;
        }
        .section {
            margin-bottom: 10px;
        }
        .section-title {
            font-weight: bold;
            color: #0d6efd;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 2px;
            margin-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 5px;
        }
        .info-item {
            margin-right: 15px;
        }
        .label {
            font-weight: bold;
        }
        .image-container {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        .image-box {
            width: 48%;
            text-align: center;
        }
        .image-title {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 11px;
        }
        .image {
            max-width: 100%;
            height: 160px;
            border: 1px solid #dee2e6;
            object-fit: contain;
            background-color: #f8f9fa;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .signature-box {
            width: 48%;
            padding: 5px;
            border: 1px solid #dee2e6;
            text-align: center;
            background-color: #f8f9fa;
        }
        .signature-label {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 11px;
        }
        .signature-image {
            max-width: 100%;
            height: 110px;
            object-fit: contain;
        }
        .footer {
            margin-top: 10px;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
            text-align: center;
            color: #6c757d;
        }
        .footer p {
            margin: 2px 0;
            font-size: 11px;
        }
        .terms {
            font-size: 9px;
            margin-top: 3px;
            text-align: justify;
        }
        .document-border {
            border: 1px solid #dee2e6;
            padding: 10px 15px;
            border-radius: 5px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            min-height: 93vh;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .spacer {
            flex: 1;
            min-height: 10px;
        }
    </style>
</head>
<body>
    <div class="document-border">
        <div class="company-header">
            <div class="company-info">
                <div class="company-name">La Maison du Geek</div>
                <div class="company-address">
                    78 BD PAUL DOUMER<br>
                    06110 LE CANNET
                </div>
            </div>
            <div class="attestation-info" style="text-align: right;">
                <div style="font-weight: bold; font-size: 14px;">ATTESTATION N° ' . $rachat_id . '</div>
                <div>' . date('d/m/Y', strtotime($rachat['date_rachat'])) . '</div>
            </div>
        </div>
        
        <div class="header">
            <h1>ATTESTATION DE RACHAT D\'APPAREIL</h1>
        </div>
        
        <div class="content">
            <div class="section">
                <div class="section-title">Vendeur</div>
                <div class="info-row">
                    <div class="info-item"><span class="label">Nom :</span> ' . htmlspecialchars($rachat['nom']) . '</div>
                    <div class="info-item"><span class="label">Prénom :</span> ' . htmlspecialchars($rachat['prenom']) . '</div>
                    <div class="info-item"><span class="label">Téléphone :</span> ' . htmlspecialchars($rachat['telephone']) . '</div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Appareil</div>
                <div class="info-row">
                    <div class="info-item"><span class="label">Type :</span> ' . htmlspecialchars($rachat['type_appareil']) . '</div>
                    <div class="info-item"><span class="label">Modèle :</span> ' . htmlspecialchars($rachat['modele'] ?? 'Non renseigné') . '</div>
                    <div class="info-item"><span class="label">Prix de rachat :</span> ' . number_format($rachat['prix'], 2, ',', ' ') . ' €</div>
                </div>
                <div class="info-row">
                    <div class="info-item"><span class="label">Numéro de série :</span> ' . htmlspecialchars($rachat['sin'] ?? 'Non renseigné') . '</div>
                    <div class="info-item"><span class="label">État :</span> ' . ($rachat['fonctionnel'] ? 'Fonctionnel' : 'Non fonctionnel') . '</div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Photos</div>
                <div class="image-container">
                    <div class="image-box">
                        <div class="image-title">Photo de l\'appareil</div>
                        <img src="' . $photo_appareil . '" class="image" alt="Photo de l\'appareil">
                    </div>
                    <div class="image-box">
                        <div class="image-title">Pièce d\'identité</div>
                        <img src="' . $photo_identite . '" class="image" alt="Pièce d\'identité">
                    </div>
                </div>
            </div>
            
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-label">Signature du vendeur</div>
                    <img src="' . $signature . '" class="signature-image" alt="Signature du vendeur">
                </div>
                <div class="signature-box">
                    <div class="signature-label">Photo du vendeur</div>
                    <img src="' . $client_photo . '" class="signature-image" alt="Photo du vendeur">
                </div>
            </div>
            
            <div class="spacer"></div>
        </div>
        
        <div class="footer">
            <p>Ce document atteste que le vendeur a cédé l\'appareil décrit ci-dessus à La Maison du Geek.</p>
            <div class="terms">
                Conformément aux dispositions légales en vigueur, le vendeur certifie être le propriétaire légitime de l\'appareil 
                et garantit qu\'il est exempt de tout logiciel de verrouillage ou de localisation. Les informations recueillies 
                sont nécessaires au traitement de votre dossier et sont destinées à notre société uniquement.
            </div>
        </div>
    </div>
</body>
</html>
';

try {
    // Nous retournons simplement le HTML à convertir avec un outil comme jsPDF côté client
    echo json_encode([
        'success' => true,
        'html' => $html,
        'info' => [
            'filename' => $filename,
            'rachat_id' => $rachat_id,
            'date' => date('d/m/Y', strtotime($rachat['date_rachat']))
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()]);
} 