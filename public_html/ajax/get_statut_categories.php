<?php
// Désactiver l'affichage des erreurs PHP pour la production
// mais les logger pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// S'assurer que nous envoyons du JSON
header('Content-Type: application/json');

require_once('../config/database.php');

// Vérifier si la connexion à la base de données est établie
if (!isset($shop_pdo) || $shop_pdo === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

try {
    // Récupérer toutes les catégories de statut
    $sql = "SELECT * FROM statut_categories WHERE est_actif = 1 ORDER BY ordre ASC";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier s'il y a des catégories
    if (count($categories) === 0) {
        // Si aucune catégorie n'est trouvée, renvoyer des catégories par défaut
        $categories = [
            ['id' => 1, 'nom' => 'Nouvelles réparations', 'couleur' => 'info', 'ordre' => 1],
            ['id' => 2, 'nom' => 'En cours', 'couleur' => 'primary', 'ordre' => 2],
            ['id' => 3, 'nom' => 'En attente', 'couleur' => 'warning', 'ordre' => 3],
            ['id' => 4, 'nom' => 'Terminées', 'couleur' => 'success', 'ordre' => 4],
            ['id' => 5, 'nom' => 'Annulées', 'couleur' => 'danger', 'ordre' => 5]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des catégories de statut: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur inattendue est survenue: ' . $e->getMessage()
    ]);
} 