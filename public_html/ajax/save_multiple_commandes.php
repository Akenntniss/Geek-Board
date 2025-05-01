<?php
// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Inclusion de la connexion à la base de données
require_once '../config/database.php';

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données du formulaire
$client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
$reparation_id = isset($_POST['reparation_id']) ? intval($_POST['reparation_id']) : null;
$pieces = isset($_POST['pieces']) ? $_POST['pieces'] : [];

// Vérifier les données obligatoires
if ($client_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Client non spécifié']);
    exit;
}

if (empty($pieces)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Aucune pièce spécifiée']);
    exit;
}

try {
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    // Insérer chaque pièce comme une commande séparée
    $commandes_ids = [];
    foreach ($pieces as $piece) {
        // Vérifier les données obligatoires pour chaque pièce
        if (empty($piece['fournisseur_id']) || empty($piece['nom_piece']) || !isset($piece['prix_estime'])) {
            continue; // Ignorer les pièces incomplètes
        }
        
        // Préparer la requête d'insertion
        $stmt = $pdo->prepare("
            INSERT INTO commandes_pieces (
                client_id, reparation_id, fournisseur_id, nom_piece, 
                code_barre, quantite, prix_estime, statut, date_creation
            ) VALUES (
                :client_id, :reparation_id, :fournisseur_id, :nom_piece, 
                :code_barre, :quantite, :prix_estime, :statut, NOW()
            )
        ");
        
        // Exécuter la requête
        $stmt->execute([
            'client_id' => $client_id,
            'reparation_id' => $reparation_id,
            'fournisseur_id' => intval($piece['fournisseur_id']),
            'nom_piece' => trim($piece['nom_piece']),
            'code_barre' => isset($piece['code_barre']) ? trim($piece['code_barre']) : null,
            'quantite' => isset($piece['quantite']) ? intval($piece['quantite']) : 1,
            'prix_estime' => floatval($piece['prix_estime']),
            'statut' => isset($piece['statut']) ? $piece['statut'] : 'en_attente'
        ]);
        
        // Récupérer l'ID de la commande insérée
        $commandes_ids[] = $pdo->lastInsertId();
    }
    
    // Valider la transaction
    $pdo->commit();
    
    // Retourner une réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Commandes enregistrées avec succès',
        'commandes_ids' => $commandes_ids
    ]);
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    
    // Retourner une réponse d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'enregistrement des commandes: ' . $e->getMessage()
    ]);
} 