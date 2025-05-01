<?php
/**
 * API pour récupérer la liste des fournisseurs
 * Compatible avec le format attendu par dashboard-commands.js
 */

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Journalisation des requêtes
error_log("Requête pour récupérer les fournisseurs");

try {
    // Vérifier la connexion à la base de données
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Construire la requête SQL pour récupérer les fournisseurs
    $sql = "SELECT id, nom FROM fournisseurs ORDER BY nom";
    
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . implode(' ', $pdo->errorInfo()));
    }
    
    // Exécuter la requête
    $stmt->execute();
    
    // Récupérer les résultats
    $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Journaliser le nombre de fournisseurs trouvés
    error_log("Nombre de fournisseurs trouvés: " . count($fournisseurs));
    
    // Retourner les fournisseurs au format JSON attendu par dashboard-commands.js
    echo json_encode([
        'success' => true,
        'fournisseurs' => $fournisseurs,
        'count' => count($fournisseurs)
    ]);
    
} catch (PDOException $e) {
    // Journaliser l'erreur
    error_log("Erreur PDO lors de la récupération des fournisseurs: " . $e->getMessage());
    
    // Retourner une erreur au format JSON
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Journaliser l'erreur
    error_log("Exception lors de la récupération des fournisseurs: " . $e->getMessage());
    
    // Retourner une erreur au format JSON
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 