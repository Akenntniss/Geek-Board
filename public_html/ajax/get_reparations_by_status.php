<?php
// Inclure le fichier de connexion à la base de données
require_once dirname(__DIR__) . '/config/database.php';

// Vérifier que l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer le type de statut demandé
$status_type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Vérifier que le type est valide
if (!in_array($status_type, ['nouvelles', 'enattente', 'encours'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Type de statut invalide']);
    exit;
}

try {
    // Vérifier les statuts existants dans la base de données
    $checkSql = "SELECT DISTINCT statut FROM reparations WHERE archive = 'NON' ORDER BY statut";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute();
    $existingStatuses = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Statuts existants dans la base de données: " . implode(", ", $existingStatuses));

    // Préparer la requête SQL en fonction du type de statut
    switch($status_type) {
        case 'nouvelles':
            // Les statuts correspondant aux nouvelles réparations
            $sql = "SELECT r.id, r.client_id, r.type_appareil, r.marque, r.modele, r.statut, 
                    r.date_reception,
                    c.nom as client_nom, c.prenom as client_prenom
                FROM reparations r
                LEFT JOIN clients c ON r.client_id = c.id
                WHERE r.statut IN ('nouvelle_intervention', 'nouveau_diagnostique', 'nouvelle_commande')
                AND r.archive = 'NON'
                ORDER BY r.date_reception DESC
                LIMIT 20";
            break;
            
        case 'enattente':
            // Les réparations en attente
            $sql = "SELECT r.id, r.client_id, r.type_appareil, r.marque, r.modele, r.statut, 
                    r.date_reception,
                    c.nom as client_nom, c.prenom as client_prenom
                FROM reparations r
                LEFT JOIN clients c ON r.client_id = c.id
                WHERE r.statut = 'en_attente_responsable'
                AND r.archive = 'NON'
                ORDER BY r.date_reception DESC
                LIMIT 20";
            break;
            
        case 'encours':
            // Les réparations en cours
            $sql = "SELECT r.id, r.client_id, r.type_appareil, r.marque, r.modele, r.statut, 
                    r.date_reception,
                    c.nom as client_nom, c.prenom as client_prenom
                FROM reparations r
                LEFT JOIN clients c ON r.client_id = c.id
                WHERE r.statut IN ('nouvelle_intervention', 'nouveau_diagnostique')
                AND r.archive = 'NON'
                ORDER BY r.date_reception DESC
                LIMIT 20";
            break;
    }
    
    // Log de la requête SQL
    error_log("Requête SQL pour $status_type: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log du nombre de réparations trouvées
    error_log("Nombre de réparations trouvées pour $status_type: " . count($reparations));
    
    // Compter le nombre de réparations par catégorie pour les badges
    $counts = [];
    $countSql = "SELECT 
                    (SELECT COUNT(*) FROM reparations WHERE statut IN ('nouvelle_intervention', 'nouveau_diagnostique', 'nouvelle_commande') AND archive = 'NON') as nouvelles,
                    (SELECT COUNT(*) FROM reparations WHERE statut = 'en_attente_responsable' AND archive = 'NON') as enattente,
                    (SELECT COUNT(*) FROM reparations WHERE statut IN ('nouvelle_intervention', 'nouveau_diagnostique') AND archive = 'NON') as encours";
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute();
    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    // Log des compteurs
    error_log("Compteurs: " . json_encode($counts));
    
    // Renvoyer les résultats au format JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'reparations' => $reparations,
        'counts' => $counts,
        'debug' => [
            'existing_statuses' => $existingStatuses,
            'sql' => $sql
        ]
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    error_log('Erreur lors de la récupération des réparations: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des réparations',
        'message' => $e->getMessage()
    ]);
} 