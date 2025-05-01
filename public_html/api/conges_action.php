<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action']) || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    // Récupérer les informations de la demande
    $stmt = $pdo->prepare("
        SELECT cd.*, cs.solde_actuel
        FROM conges_demandes cd
        LEFT JOIN conges_solde cs ON cd.user_id = cs.user_id
        WHERE cd.id = ?
    ");
    $stmt->execute([$data['id']]);
    $demande = $stmt->fetch();

    if (!$demande) {
        throw new Exception('Demande non trouvée');
    }

    if ($data['action'] === 'approuver') {
        // Vérifier si l'employé a assez de jours
        if ($demande['solde_actuel'] < $demande['nb_jours']) {
            throw new Exception('Solde de congés insuffisant');
        }

        // Mettre à jour le statut de la demande
        $stmt = $pdo->prepare("
            UPDATE conges_demandes 
            SET statut = 'approuve', 
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $data['id']]);

        // Mettre à jour le solde de congés
        $stmt = $pdo->prepare("
            UPDATE conges_solde 
            SET solde_actuel = solde_actuel - ?,
                date_derniere_maj = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$demande['nb_jours'], $demande['user_id']]);

    } elseif ($data['action'] === 'refuser') {
        // Mettre à jour le statut de la demande
        $stmt = $pdo->prepare("
            UPDATE conges_demandes 
            SET statut = 'refuse', 
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $data['id']]);
    } else {
        throw new Exception('Action non valide');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 