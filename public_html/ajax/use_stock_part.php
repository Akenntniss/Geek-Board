<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérifier si tous les champs requis sont présents
if (!isset($_POST['reparation_id']) || !isset($_POST['piece_id']) || !isset($_POST['quantity'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$reparation_id = (int)$_POST['reparation_id'];
$piece_id = (int)$_POST['piece_id'];
$quantity = (int)$_POST['quantity'];
$note = isset($_POST['note']) ? cleanInput($_POST['note']) : '';

try {
    // Utiliser la connexion à la base de données du magasin actuel
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la connexion à la base de données
    if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données du magasin non disponible');
    }
    
    // Vérifier si la pièce existe et si la quantité est disponible
    $sql = "SELECT quantite FROM pieces_detachees WHERE id = ?";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$piece_id]);
    $piece = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$piece) {
        throw new Exception('Pièce non trouvée');
    }

    if ($piece['quantite'] < $quantity) {
        throw new Exception('Quantité insuffisante dans le stock');
    }

    // Démarrer la transaction
    $shop_pdo->beginTransaction();

    // Mettre à jour la quantité dans le stock
    $sql = "UPDATE pieces_detachees SET quantite = quantite - ? WHERE id = ?";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$quantity, $piece_id]);

    // Enregistrer l'utilisation de la pièce
    $sql = "INSERT INTO pieces_utilisees (reparation_id, piece_id, quantite, note, date_utilisation) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$reparation_id, $piece_id, $quantity, $note]);

    // Valider la transaction
    $shop_pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 