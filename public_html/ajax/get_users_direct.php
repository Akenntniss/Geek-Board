<?php
// Désactiver l'affichage d'erreurs pour éviter de corrompre le JSON
error_reporting(0);
ini_set('display_errors', 0);

// Définir les en-têtes pour éviter le cache et spécifier le type de contenu
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Créer la connexion à la base de données directement
    $db_pdo = new PDO(
        "mysql:host=srv931.hstgr.io;port=3306;dbname=u139954273_Vscodetest;charset=utf8mb4",
        "u139954273_Vscodetest",
        "Maman01#",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Récupérer la liste des utilisateurs actifs
    $stmt = $db_pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC");
    $users = $stmt->fetchAll();
    
    // Retourner les données en JSON
    echo json_encode([
        'success' => true,
        'message' => 'Utilisateurs récupérés avec succès',
        'users' => $users
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage()
    ]);
} 