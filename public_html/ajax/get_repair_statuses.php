<?php
// Désactiver l'affichage des erreurs PHP
error_reporting(0);
ini_set('display_errors', 0);

// S'assurer que nous envoyons du JSON
header('Content-Type: application/json');

require_once('../config/database.php');

// Vérifier si la connexion à la base de données est établie
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

try {
    // Récupérer tous les statuts de réparation
    $sql = "SELECT id, code, nom, categorie FROM statuts_reparation ORDER BY categorie, nom";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new PDOException("Erreur lors de la préparation de la requête");
    }
    
    $stmt->execute();
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organiser les statuts par catégorie
    $categories = [];
    foreach ($statuses as $status) {
        $categorie = $status['categorie'];
        if (!isset($categories[$categorie])) {
            $categories[$categorie] = [];
        }
        $categories[$categorie][] = [
            'id' => $status['id'],
            'code' => $status['code'],
            'nom' => $status['nom']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statuts: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des statuts'
    ]);
} catch (Exception $e) {
    error_log("Erreur inattendue: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue'
    ]);
} 