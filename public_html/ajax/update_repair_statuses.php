<?php
// Inclure la configuration et les fonctions
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration des headers pour les requêtes AJAX
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données du formulaire
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier les données reçues
if (!isset($data['repair_ids']) || !is_array($data['repair_ids']) || empty($data['repair_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'IDs des réparations manquants ou invalides']);
    exit;
}

if (!isset($data['status']) || !in_array($data['status'], ['restitue', 'annule', 'gardiennage'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Statut invalide']);
    exit;
}

// Vérifier que la connexion à la base de données est établie
if (!isset($shop_pdo) || $shop_pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit;
}

try {
    // Déterminer le code de statut en fonction du choix
    $statut_code = '';
    $statut_id = 0;
    
    switch ($data['status']) {
        case 'restitue':
            // Récupérer le code du statut "Restitué"
            $stmt = $shop_pdo->prepare("SELECT code FROM statuts WHERE id = 11"); // ID 11 pour Restitué
            $stmt->execute();
            $result = $stmt->fetch();
            $statut_code = $result ? $result['code'] : 'RESTITUE';
            $statut_id = 11;
            break;
        case 'annule':
            // Récupérer le code du statut "Annulé"
            $stmt = $shop_pdo->prepare("SELECT code FROM statuts WHERE id = 12"); // ID 12 pour Annulé
            $stmt->execute();
            $result = $stmt->fetch();
            $statut_code = $result ? $result['code'] : 'ANNULE';
            $statut_id = 12;
            break;
        case 'gardiennage':
            // Récupérer le code du statut "Gardiennage"
            $stmt = $shop_pdo->prepare("SELECT code FROM statuts WHERE id = 13"); // ID 13 pour Gardiennage
            $stmt->execute();
            $result = $stmt->fetch();
            $statut_code = $result ? $result['code'] : 'GARDIENNAGE';
            $statut_id = 13;
            break;
    }
    
    if (empty($statut_code)) {
        throw new Exception("Impossible de déterminer le code du statut");
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Mettre à jour le statut de chaque réparation
    $repair_ids = array_map('intval', $data['repair_ids']); // Convertir en entiers pour sécurité
    $updated_count = 0;
    
    foreach ($repair_ids as $repair_id) {
        // Mettre à jour le statut
        $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$statut_code, $repair_id]);
        
        if ($result) {
            $updated_count++;
            
            // Enregistrer l'historique du changement de statut
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            $stmt = $shop_pdo->prepare("INSERT INTO historique_statuts (reparation_id, statut_id, user_id, date_changement, commentaire) 
                                VALUES (?, ?, ?, NOW(), ?)");
            $commentaire = "Mise à jour en lot via l'interface de gestion";
            $stmt->execute([$repair_id, $statut_id, $user_id, $commentaire]);
        }
    }
    
    // Valider la transaction
    $shop_pdo->commit();
    
    // Retourner une réponse de succès
    echo json_encode([
        'success' => true,
        'message' => "$updated_count réparation(s) mise(s) à jour avec succès.",
        'updated_count' => $updated_count
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    // Enregistrer l'erreur dans les logs
    error_log("Erreur dans update_repair_statuses.php: " . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour des statuts: ' . $e->getMessage()
    ]);
}