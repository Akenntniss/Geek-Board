<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Vérifier les paramètres
if (!isset($_POST['client_id']) || empty($_POST['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID client manquant']);
    exit;
}

$client_id = intval($_POST['client_id']);

try {
    // Utiliser le système de configuration multi-magasin
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable.');
    }
    
    // Inclure la configuration de base de données
    require_once $config_path;
    
    // Utiliser la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }
    
    // Récupérer les informations du client (même structure que recherche_universelle.php)
    $stmt = $pdo->prepare("
        SELECT id, nom, prenom, telephone, email
        FROM clients 
        WHERE id = ?
    ");
    $stmt->execute([$client_id]);
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit;
    }
    
    // Récupérer l'historique des réparations avec le prix et la date
    $stmt = $pdo->prepare("
        SELECT r.id, r.type_appareil, r.modele, r.description_probleme, 
               r.statut, r.prix_reparation, r.date_reception
        FROM reparations r
        WHERE r.client_id = ?
        ORDER BY r.id DESC
        LIMIT 50
    ");
    $stmt->execute([$client_id]);
    
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer l'historique des commandes (même structure que recherche_universelle.php)
    $stmt = $pdo->prepare("
        SELECT cmd.id, cmd.reference, cmd.nom_piece, cmd.description, cmd.statut, cmd.date_creation
        FROM commandes_pieces cmd
        WHERE cmd.client_id = ?
        ORDER BY cmd.date_creation DESC
        LIMIT 50
    ");
    $stmt->execute([$client_id]);
    
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'client' => $client,
        'history' => [
            'reparations' => $reparations,
            'commandes' => $commandes
        ],
        'counts' => [
            'reparations' => count($reparations),
            'commandes' => count($commandes)
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Erreur base de données dans get_client_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur dans get_client_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?> 