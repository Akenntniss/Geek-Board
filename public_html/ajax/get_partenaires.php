<?php
// Logging dans un fichier temporaire pour s'assurer qu'il est accessible en écriture
file_put_contents(dirname(__DIR__) . '/tmp/debug.log', "get_partenaires.php a été appelé à " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Logging pour le débogage
error_log("get_partenaires.php a été appelé");

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log de l'état de la session
$session_info = isset($_SESSION) ? json_encode($_SESSION) : 'Session vide';
file_put_contents(dirname(__DIR__) . '/tmp/debug.log', "État de la session : " . $session_info . "\n", FILE_APPEND);

// Vérifier si l'utilisateur est connecté - Désactiver temporairement pour tester
/*
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}
*/

try {
    file_put_contents(dirname(__DIR__) . '/tmp/debug.log', "Tentative de récupération des partenaires\n", FILE_APPEND);
    
    // Récupérer tous les partenaires actifs
    $stmt = $shop_pdo->query("
        SELECT p.*, 
            COALESCE(s.solde_actuel, 0) as solde_actuel
        FROM partenaires p
        LEFT JOIN soldes_partenaires s ON p.id = s.partenaire_id
        WHERE p.actif = TRUE
        ORDER BY p.nom
    ");
    
    $partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    file_put_contents(dirname(__DIR__) . '/tmp/debug.log', "Nombre de partenaires : " . count($partenaires) . "\n", FILE_APPEND);

    header('Content-Type: application/json');
    $response = json_encode([
        'success' => true,
        'partenaires' => $partenaires
    ]);
    echo $response;
    
    file_put_contents(dirname(__DIR__) . '/tmp/debug.log', "Réponse JSON envoyée : " . $response . "\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents(dirname(__DIR__) . '/tmp/debug.log', "ERREUR : " . $e->getMessage() . "\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des partenaires: ' . $e->getMessage()]);
} 