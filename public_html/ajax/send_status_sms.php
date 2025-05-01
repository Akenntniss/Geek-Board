<?php
require_once '../includes/functions.php';
require_once '../database.php';

// Vérifier si la requête est en GET (pour récupérer le message) ou en POST (pour envoyer le SMS)
$isGetRequest = $_SERVER['REQUEST_METHOD'] === 'GET';

// Récupérer les paramètres
$repair_id = $isGetRequest ? $_GET['repair_id'] : json_decode(file_get_contents('php://input'), true)['repair_id'];
$status_id = $isGetRequest ? $_GET['status_id'] : json_decode(file_get_contents('php://input'), true)['status_id'];

// Vérifier les paramètres
if (empty($repair_id) || empty($status_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants'
    ]);
    exit;
}

try {
    // Récupérer les informations de la réparation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$repair_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }

    // Récupérer le template SMS associé au statut
    $stmt = $pdo->prepare("
        SELECT id, nom, contenu 
        FROM sms_templates 
        WHERE statut_id = ? AND est_actif = 1
        LIMIT 1
    ");
    $stmt->execute([$status_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        echo json_encode([
            'success' => false,
            'message' => 'Aucun modèle SMS disponible pour ce statut'
        ]);
        exit;
    }

    // Si c'est une requête GET, on renvoie juste le message
    if ($isGetRequest) {
        // Préparer le contenu du SMS en remplaçant les variables
        $message = $template['contenu'];
        
        // Tableau des remplacements
        $replacements = [
            '[CLIENT_NOM]' => $reparation['client_nom'],
            '[CLIENT_PRENOM]' => $reparation['client_prenom'],
            '[CLIENT_TELEPHONE]' => $reparation['client_telephone'],
            '[REPARATION_ID]' => $reparation['id'],
            '[APPAREIL_TYPE]' => $reparation['type_appareil'],
            '[APPAREIL_MARQUE]' => $reparation['marque'],
            '[APPAREIL_MODELE]' => $reparation['modele'],
            '[DATE_RECEPTION]' => format_date($reparation['date_reception']),
            '[DATE_FIN_PREVUE]' => !empty($reparation['date_fin_prevue']) ? format_date($reparation['date_fin_prevue']) : '',
            '[PRIX]' => !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') : ''
        ];
        
        // Effectuer les remplacements
        foreach ($replacements as $var => $value) {
            $message = str_replace($var, $value, $message);
        }

        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        exit;
    }

    // Si c'est une requête POST, on envoie le SMS
    if (empty($reparation['client_telephone'])) {
        throw new Exception('Le client n\'a pas de numéro de téléphone');
    }

    // Préparer le contenu du SMS en remplaçant les variables
    $message = $template['contenu'];
    
    // Tableau des remplacements
    $replacements = [
        '[CLIENT_NOM]' => $reparation['client_nom'],
        '[CLIENT_PRENOM]' => $reparation['client_prenom'],
        '[CLIENT_TELEPHONE]' => $reparation['client_telephone'],
        '[REPARATION_ID]' => $reparation['id'],
        '[APPAREIL_TYPE]' => $reparation['type_appareil'],
        '[APPAREIL_MARQUE]' => $reparation['marque'],
        '[APPAREIL_MODELE]' => $reparation['modele'],
        '[DATE_RECEPTION]' => format_date($reparation['date_reception']),
        '[DATE_FIN_PREVUE]' => !empty($reparation['date_fin_prevue']) ? format_date($reparation['date_fin_prevue']) : '',
        '[PRIX]' => !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') : ''
    ];
    
    // Effectuer les remplacements
    foreach ($replacements as $var => $value) {
        $message = str_replace($var, $value, $message);
    }

    // Envoyer le SMS
    $sms_result = send_sms($reparation['client_telephone'], $message);
    
    if ($sms_result['success']) {
        // Enregistrer l'envoi du SMS dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $repair_id, 
            $template['id'], 
            $reparation['client_telephone'], 
            $message, 
            $status_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Le SMS a été envoyé avec succès'
        ]);
    } else {
        throw new Exception($sms_result['message'] ?? 'Erreur lors de l\'envoi du SMS');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 