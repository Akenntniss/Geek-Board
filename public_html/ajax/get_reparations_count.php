<?php
// Désactiver l'affichage des erreurs pour éviter de corrompre le JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Définir le type de contenu en JSON immédiatement
header('Content-Type: application/json');

session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

try {
    // Définir les correspondances entre les types d'onglets et les statuts en BDD
    $statusMapping = [
        'nouvelles' => ['nouveau_diagnostique', 'nouvelle_intervention'],
        'enattente' => ['en_attente_responsable', 'nouvelle_commande'],
        'encours' => ['en_cours', 'en_attente_client']
    ];
    
    // Préparer la requête SQL pour compter les réparations par catégorie
    $countSql = "SELECT 
        SUM(CASE WHEN statut IN ('nouveau_diagnostique', 'nouvelle_intervention') THEN 1 ELSE 0 END) as nouvelles,
        SUM(CASE WHEN statut IN ('en_attente_responsable', 'nouvelle_commande') THEN 1 ELSE 0 END) as enattente,
        SUM(CASE WHEN statut IN ('en_cours', 'en_attente_client') THEN 1 ELSE 0 END) as encours
    FROM reparations
    WHERE archive = 'NON'";
    
    $countStmt = $shop_pdo->query($countSql);
    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    // Convertir les valeurs en entiers
    foreach ($counts as $key => $value) {
        $counts[$key] = (int)$value;
    }
    
    // Log des compteurs
    error_log("Compteurs de réparations: " . json_encode($counts));
    
    // Envoyer les résultats
    echo json_encode([
        'success' => true,
        'counts' => $counts
    ]);
} catch (PDOException $e) {
    error_log("Erreur lors du comptage des réparations: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du comptage des réparations: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception non PDO: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur inattendue: ' . $e->getMessage()
    ]);
} 