<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['reparation_id']) || !isset($data['new_status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$reparation_id = (int)$data['reparation_id'];
$new_status_id = (int)$data['new_status'];
$send_sms = isset($data['send_sms']) ? (bool)$data['send_sms'] : false;
$sms_template = isset($data['sms_template']) ? $data['sms_template'] : '';

try {
    // Démarrer une transaction
    $shop_pdo->beginTransaction();

    // Récupérer le code du nouveau statut
    $stmt = $shop_pdo->prepare("SELECT code FROM statuts_reparation WHERE id = ?");
    $stmt->execute([$new_status_id]);
    $new_status = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$new_status) {
        throw new Exception('Statut non trouvé');
    }

    // Mettre à jour le statut de la réparation
    $stmt = $shop_pdo->prepare("
        UPDATE reparations 
        SET statut = ?, 
            statut_id = ?,
            date_modification = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$new_status['code'], $new_status_id, $reparation_id]);

    // Si l'envoi de SMS est demandé
    if ($send_sms && !empty($sms_template)) {
        // Récupérer les informations du client
        $stmt = $shop_pdo->prepare("
            SELECT c.telephone, c.nom, c.prenom
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reparation_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($client && !empty($client['telephone'])) {
            // Insérer le SMS dans la table sms_reparation
            $stmt = $shop_pdo->prepare("
                INSERT INTO reparation_sms (
                    reparation_id,
                    telephone,
                    message,
                    statut_id,
                    date_creation
                ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $reparation_id,
                $client['telephone'],
                $sms_template,
                $new_status_id
            ]);
        }
    }

    // Valider la transaction
    $shop_pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 