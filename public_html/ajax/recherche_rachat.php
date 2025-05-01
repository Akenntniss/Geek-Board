<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier si la session est déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à ces données
// Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Accès non autorisé. Veuillez vous connecter en tant qu\'administrateur.']);
    exit;
}
*/

// Vérifier le terme de recherche
// Recherche avec terme vide par défaut
$searchTerm = isset($_POST['search']) ? '%' . cleanInput($_POST['search']) . '%' : '%%';

try {
    // Vérifier que la connexion à la base de données est établie
    if (!isset($pdo) || $pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }
    
    $stmt = $pdo->prepare("SELECT 
            r.id, r.type_appareil, r.date_rachat, r.photo_appareil, r.photo_identite,
            r.modele, r.sin, r.fonctionnel, r.prix,
            c.nom, c.prenom
        FROM rachat_appareils r
        JOIN clients c ON r.client_id = c.id
        WHERE (c.nom LIKE ? OR c.prenom LIKE ?) AND (r.type_appareil LIKE ? OR r.modele LIKE ? OR r.sin LIKE ?)
        ORDER BY r.date_rachat DESC
        LIMIT 50");
        
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()]);
}
?>