<?php
/**
 * API pour récupérer les réparations d'un client spécifique
 * Utilisé pour filtrer les réparations dans le modal de commande
 */

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Vérifier si l'ID du client est fourni
if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du client non fourni'
    ]);
    exit;
}

$client_id = (int)$_GET['client_id'];

// Journalisation des requêtes
error_log("Requête pour récupérer les réparations du client ID: " . $client_id);

try {
    // Vérifier la connexion à la base de données
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Construire la requête SQL pour récupérer les réparations du client non archivées
    $sql = "
        SELECT r.id, r.type_appareil, r.marque, r.modele, r.client_id, 
               c.nom AS client_nom, c.prenom AS client_prenom
        FROM reparations r
        INNER JOIN clients c ON r.client_id = c.id
        WHERE r.client_id = :client_id
        AND r.archive = 'NON' 
        AND r.statut != 'Livré'
        AND r.statut != 'restitue'
        AND r.statut != 'annule'
        AND r.statut != 'archive'
        ORDER BY r.date_reception DESC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . implode(' ', $pdo->errorInfo()));
    }
    
    // Lier le paramètre
    $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
    
    // Exécuter la requête
    $stmt->execute();
    
    // Récupérer les résultats
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Journaliser le nombre de réparations trouvées
    error_log("Nombre de réparations trouvées pour le client " . $client_id . ": " . count($reparations));
    
    // Retourner les réparations au format JSON
    echo json_encode([
        'success' => true,
        'reparations' => $reparations,
        'count' => count($reparations)
    ]);
    
} catch (PDOException $e) {
    // Journaliser l'erreur
    error_log("Erreur PDO lors de la récupération des réparations: " . $e->getMessage());
    
    // Retourner une erreur au format JSON
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Journaliser l'erreur
    error_log("Exception lors de la récupération des réparations: " . $e->getMessage());
    
    // Retourner une erreur au format JSON
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 