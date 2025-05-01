<?php
/**
 * API pour recevoir les notifications de statut des SMS depuis l'appareil Android
 * Cette API est appelée par l'application Tasker/AutoNotification pour mettre à jour
 * le statut des SMS envoyés dans la base de données
 */

// Désactiver l'affichage des erreurs en production
// error_reporting(0);
header('Content-Type: application/json');

// Inclure la connexion à la base de données
include_once('../database.php');

// Vérifier la méthode de la requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Si les données ne sont pas au format JSON valide, essayer de lire depuis POST
if (!$data) {
    $data = $_POST;
}

// Vérifier l'authentification
$api_key = $data['api_key'] ?? '';
if ($api_key !== 'votre_cle_secrete') {  // Doit être la même clé que celle utilisée dans le script d'envoi
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Clé API invalide']);
    exit;
}

// Vérifier les paramètres requis
$requiredParams = ['message_id', 'recipient', 'status'];
foreach ($requiredParams as $param) {
    if (empty($data[$param])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Paramètre manquant: $param"]);
        exit;
    }
}

// Récupérer les paramètres
$message_id = $data['message_id']; // ID du message (peut être null si inconnu)
$recipient = $data['recipient'];   // Numéro de téléphone destinataire
$status = $data['status'];         // Statut du SMS (délivré, échoué, etc.)
$details = $data['details'] ?? ''; // Détails supplémentaires (facultatif)

// Mise à jour du statut dans la base de données
// Si message_id est fourni, mettre à jour par ID
if ($message_id && is_numeric($message_id)) {
    $sql = "UPDATE sms_logs SET status = ?, response_data = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $status, $details, $message_id);
} else {
    // Sinon, mettre à jour le message le plus récent pour ce destinataire
    $sql = "UPDATE sms_logs SET status = ?, response_data = ? 
            WHERE recipient = ? ORDER BY sent_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $status, $details, $recipient);
}

// Exécuter la requête
if ($stmt->execute()) {
    // Vérifier si une ligne a été mise à jour
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Statut mis à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Aucun message trouvé pour cette mise à jour'
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la mise à jour: ' . $stmt->error
    ]);
}

// Fermer la connexion
$stmt->close();
$conn->close(); 