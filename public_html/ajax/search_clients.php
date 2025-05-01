<?php
/**
 * API de recherche de clients
 * Accepte les requêtes POST ou GET
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Log de débogage pour vérifier les paramètres reçus
error_log("Recherche client - GET: " . print_r($_GET, true));
error_log("Recherche client - POST: " . print_r($_POST, true));

// Récupérer le terme de recherche - chercher dans différentes variables possibles
$terme = '';
if (isset($_POST['query']) && !empty($_POST['query'])) {
    $terme = trim($_POST['query']);
} elseif (isset($_GET['query']) && !empty($_GET['query'])) {
    $terme = trim($_GET['query']);
} elseif (isset($_POST['q']) && !empty($_POST['q'])) {
    $terme = trim($_POST['q']);
} elseif (isset($_GET['q']) && !empty($_GET['q'])) {
    $terme = trim($_GET['q']);
} elseif (isset($_POST['terme']) && !empty($_POST['terme'])) {
    $terme = trim($_POST['terme']);
} elseif (isset($_GET['terme']) && !empty($_GET['terme'])) {
    $terme = trim($_GET['terme']);
}

error_log("Terme de recherche trouvé: " . $terme);

// Vérifier que le terme de recherche est fourni
if (empty($terme)) {
    error_log("Aucun terme de recherche valide trouvé");
    echo json_encode([
        'success' => false, 
        'message' => 'Terme de recherche manquant'
    ]);
    exit;
}

try {
    // Vérifier la connexion à la base de données
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        error_log("Connexion à la base de données non disponible");
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Préparer la requête SQL avec des paramètres distincts
    $sql = "
        SELECT id, nom, prenom, telephone, email
        FROM clients 
        WHERE nom LIKE :terme_nom 
        OR prenom LIKE :terme_prenom 
        OR telephone LIKE :terme_tel 
        ORDER BY nom, prenom 
        LIMIT 10
    ";
    error_log("Requête SQL: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur de préparation de la requête: " . implode(' ', $pdo->errorInfo()));
        throw new Exception('Erreur de préparation de la requête: ' . implode(' ', $pdo->errorInfo()));
    }
    
    // Exécuter la requête avec les paramètres
    $termeWildcard = "%$terme%";
    error_log("Terme de recherche avec wildcards: " . $termeWildcard);
    
    $stmt->bindParam(':terme_nom', $termeWildcard);
    $stmt->bindParam(':terme_prenom', $termeWildcard);
    $stmt->bindParam(':terme_tel', $termeWildcard);
    
    error_log("Exécution de la requête...");
    $stmt->execute();
    error_log("Requête exécutée");
    
    // Récupérer les résultats
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Résultats trouvés: " . count($clients));
    error_log("Détail des clients trouvés: " . print_r($clients, true));
    
    // Fournir une réponse détaillée en cas de succès
    echo json_encode([
        'success' => true,
        'clients' => $clients,
        'count' => count($clients),
        'terme' => $terme
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la recherche des clients: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'terme' => $terme
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la recherche des clients: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'terme' => $terme
    ]);
} 