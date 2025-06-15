<?php
/**
 * API pour récupérer la liste des réparations actives
 * Utilisé pour remplir le select dans le modal de commande
 */

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de session avant de démarrer la session
require_once '../config/session_config.php';

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté'
    ]);
    exit;
}

// Vérifier que le shop_id est défini dans la session
if (!isset($_SESSION['shop_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du magasin non défini'
    ]);
    exit;
}

// Journalisation des requêtes
error_log("Requête pour récupérer les réparations actives");

try {
    // Obtenir la connexion à la base de données du shop
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la connexion à la base de données
    if (!$shop_pdo || !($shop_pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Construire la requête SQL pour récupérer les réparations avec les statuts spécifiés
    $sql = "
        SELECT r.id, r.date_reception, r.type_appareil, r.marque, r.modele, 
               r.description_probleme, r.prix_reparation, r.client_id, r.statut as statut_nom,
               c.nom AS client_nom, c.prenom AS client_prenom
        FROM reparations r
        INNER JOIN clients c ON r.client_id = c.id
        WHERE r.archive = 'NON' 
        AND r.statut IN (
            'nouveau_diagnostique',
            'nouvelle_intervention', 
            'nouvelle_commande',
            'en_attente_accord_client',
            'en_attente_livraison',
            'en_attente_responsable',
            'en_cours_diagnostique',
            'en_cours_intervention'
        )
        ORDER BY r.date_reception DESC
        LIMIT 100
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . implode(' ', $shop_pdo->errorInfo()));
    }
    
    // Exécuter la requête
    $stmt->execute();
    
    // Récupérer les résultats
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Journaliser le nombre de réparations trouvées
    error_log("Nombre de réparations trouvées: " . count($reparations));
    
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