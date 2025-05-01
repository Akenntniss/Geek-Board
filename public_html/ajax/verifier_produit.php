<?php
// Désactiver l'affichage des erreurs dans la sortie
error_reporting(0);
ini_set('display_errors', 0);

// Inclure la configuration et les fonctions avant tout output
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Définir l'en-tête JSON
header('Content-Type: application/json');

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier le paramètre code
if (!isset($_GET['code']) || empty($_GET['code'])) {
    echo json_encode(['error' => 'Code-barres manquant']);
    exit;
}

try {
    // Vérifier que $pdo existe
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Erreur de connexion à la base de données');
    }

    // Nettoyer le code
    $code = trim($_GET['code']);
    
    // Préparer et exécuter la requête
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE reference = ?");
    $stmt->execute([$code]);
    $produit = $stmt->fetch();

    // Préparer la réponse
    if ($produit) {
        echo json_encode([
            'existe' => true,
            'id' => $produit['id'],
            'nom' => $produit['nom'],
            'reference' => $produit['reference'],
            'quantite' => $produit['quantite']
        ]);
    } else {
        echo json_encode([
            'existe' => false
        ]);
    }

} catch (Exception $e) {
    // Log l'erreur pour le débogage
    error_log("Erreur dans verifier_produit.php: " . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    echo json_encode([
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?> 