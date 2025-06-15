<?php
// Désactiver l'affichage des erreurs pour éviter de corrompre la sortie JSON
ini_set('display_errors', 0);
error_reporting(0);

// Initialiser la session
session_start();

// S'assurer que la réponse est toujours en JSON
header('Content-Type: application/json');

try {
    // Vérifier le chemin d'inclusion correct depuis le répertoire actuel
    $config_file = '../config/database.php';
    
    // Vérifier si le fichier existe
    if (!file_exists($config_file)) {
        throw new Exception("Fichier de configuration non trouvé: $config_file");
    }
    
    // Inclure la configuration et les fonctions
    include_once $config_file;

    // Vérifier que les variables de connexion sont définies
    if (!isset($host) || !isset($dbname) || !isset($username) || !isset($password)) {
        throw new Exception("Paramètres de connexion à la base de données manquants");
    }

    // Connexion à la base de données
    try {
        $shop_pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $shop_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
    }

    // Les statuts à compter
    $statuts = [
        'nouveau_diagnostique',
        'nouvelle_intervention',
        'nouvelle_commande',
        'en_cours_diagnostique',
        'en_cours_intervention',
        'en_attente_responsable'
    ];

    // Préparer la requête avec des placeholders pour les statuts
    try {
        $placeholders = str_repeat('?,', count($statuts) - 1) . '?';
        $query = "SELECT COUNT(*) as count FROM reparations WHERE statut IN ($placeholders)";
        
        // Préparer et exécuter la requête
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute($statuts);
        
        // Récupérer le résultat
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Renvoyer le résultat en JSON avec un résultat par défaut à 0 si null
        echo json_encode(['success' => true, 'count' => (int)($result['count'] ?? 0)]);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de l'exécution de la requête: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?> 