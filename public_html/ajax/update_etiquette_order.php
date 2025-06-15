<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et décoder les données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['etiquettes']) || !is_array($data['etiquettes'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Démarrer une transaction
    $shop_pdo->beginTransaction();

    // Mettre à jour la position de chaque étiquette
    foreach ($data['etiquettes'] as $etiquette) {
        if (!isset($etiquette['id']) || !isset($etiquette['position'])) {
            throw new Exception('Données d\'étiquette invalides');
        }

        $stmt = $shop_pdo->prepare("UPDATE etiquettes SET position = ? WHERE id = ?");
        $stmt->execute([$etiquette['position'], $etiquette['id']]);
    }

    // Valider la transaction
    $shop_pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Ordre des étiquettes mis à jour avec succès']);
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $shop_pdo->rollBack();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'ordre des étiquettes: ' . $e->getMessage()]);
} 