<?php
/**
 * API de recherche universelle
 * Recherche dans les clients, réparations et commandes
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier la méthode de la requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer le terme de recherche
$terme = isset($_POST['terme']) ? trim($_POST['terme']) : '';

if (empty($terme) || strlen($terme) < 2) {
    echo json_encode([
        'clients' => [],
        'reparations' => [],
        'commandes' => []
    ]);
    exit;
}

try {
    // Recherche des clients
    $clients = searchClients($terme);
    
    // Recherche des réparations
    $reparations = searchReparations($terme);
    
    // Recherche des commandes
    $commandes = searchCommandes($terme);
    
    // Retourner les résultats
    echo json_encode([
        'clients' => $clients,
        'reparations' => $reparations,
        'commandes' => $commandes
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la recherche']);
}

/**
 * Recherche des clients
 */
function searchClients($terme) {
    global $db;
    
    $sql = "SELECT id, nom, prenom, telephone, email 
            FROM clients 
            WHERE nom LIKE :terme 
            OR prenom LIKE :terme 
            OR telephone LIKE :terme 
            OR email LIKE :terme 
            ORDER BY nom, prenom 
            LIMIT 10";
            
    $stmt = $db->prepare($sql);
    $terme = "%{$terme}%";
    $stmt->bindParam(':terme', $terme);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Recherche des réparations
 */
function searchReparations($terme) {
    global $db;
    
    $sql = "SELECT r.id, r.appareil, r.probleme, r.statut,
                   CONCAT(c.nom, ' ', c.prenom) as client_nom
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            WHERE r.appareil LIKE :terme 
            OR r.probleme LIKE :terme 
            OR c.nom LIKE :terme 
            OR c.prenom LIKE :terme
            ORDER BY r.date_creation DESC 
            LIMIT 10";
            
    $stmt = $db->prepare($sql);
    $terme = "%{$terme}%";
    $stmt->bindParam(':terme', $terme);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Recherche des commandes
 */
function searchCommandes($terme) {
    global $db;
    
    $sql = "SELECT c.id, c.reference, c.date_commande, c.montant, c.statut,
                   CONCAT(cl.nom, ' ', cl.prenom) as client_nom
            FROM commandes c
            JOIN clients cl ON c.client_id = cl.id
            WHERE c.reference LIKE :terme 
            OR cl.nom LIKE :terme 
            OR cl.prenom LIKE :terme
            ORDER BY c.date_commande DESC 
            LIMIT 10";
            
    $stmt = $db->prepare($sql);
    $terme = "%{$terme}%";
    $stmt->bindParam(':terme', $terme);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
} 