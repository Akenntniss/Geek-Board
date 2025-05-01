<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer les paramètres
$status_id = isset($_GET['status_id']) ? (int)$_GET['status_id'] : 0;
$reparation_id = isset($_GET['reparation_id']) ? (int)$_GET['reparation_id'] : 0;

if (!$status_id || !$reparation_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

try {
    // Récupérer les informations de la réparation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }

    // Récupérer le template SMS
    $stmt = $pdo->prepare("
        SELECT contenu 
        FROM sms_templates 
        WHERE statut_id = ? AND est_actif = 1
        LIMIT 1
    ");
    $stmt->execute([$status_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        throw new Exception('Template SMS non trouvé');
    }

    // Remplacer les variables dans le template
    $message = $template['contenu'];
    $message = str_replace('{NOM}', $reparation['client_nom'], $message);
    $message = str_replace('{PRENOM}', $reparation['client_prenom'], $message);
    $message = str_replace('{TYPE_APPAREIL}', $reparation['type_appareil'], $message);
    $message = str_replace('{MARQUE}', $reparation['marque'], $message);
    $message = str_replace('{MODELE}', $reparation['modele'], $message);

    echo json_encode([
        'success' => true,
        'template' => $message
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 