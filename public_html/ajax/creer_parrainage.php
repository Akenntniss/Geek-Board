<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once '../config/database.php';
require_once '../includes/functions.php';

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Démarrer ou récupérer la session existante
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Définir le type de contenu avant toute sortie
header('Content-Type: application/json');

// Récupérer les données selon le type de requête
$input_data = $_POST;

// Si c'est une requête JSON
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($content_type, 'application/json') !== false) {
    $json_data = file_get_contents('php://input');
    $decoded_data = json_decode($json_data, true);
    
    if ($decoded_data !== null) {
        $input_data = $decoded_data;
    }
}

// Vérifier que les données requises sont fournies
if (!isset($input_data['parrain_id']) || !isset($input_data['filleul_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Les IDs du parrain et du filleul sont requis'
    ]);
    exit;
}

try {
    // Vérifier si la connexion PDO est disponible
    if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
        throw new Exception("Connexion à la base de données non disponible");
    }
    
    // Récupérer les IDs
    $parrain_id = (int)$input_data['parrain_id'];
    $filleul_id = (int)$input_data['filleul_id'];
    
    // Vérifier que le parrain et le filleul existent
    $stmt = $shop_pdo->prepare("SELECT id FROM clients WHERE id IN (?, ?)");
    $stmt->execute([$parrain_id, $filleul_id]);
    $found_clients = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($found_clients) !== 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Parrain ou filleul non trouvé'
        ]);
        exit;
    }
    
    // Vérifier que le parrain est inscrit au programme
    $stmt = $shop_pdo->prepare("SELECT inscrit_parrainage FROM clients WHERE id = ?");
    $stmt->execute([$parrain_id]);
    $parrain = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$parrain || !$parrain['inscrit_parrainage']) {
        echo json_encode([
            'success' => false,
            'message' => 'Le parrain n\'est pas inscrit au programme de parrainage'
        ]);
        exit;
    }
    
    // Vérifier que le filleul n'a pas déjà un parrain
    $stmt = $shop_pdo->prepare("SELECT id FROM parrainage_relations WHERE filleul_id = ?");
    $stmt->execute([$filleul_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ce client a déjà un parrain'
        ]);
        exit;
    }
    
    // Créer la relation de parrainage
    $result = creer_relation_parrainage($parrain_id, $filleul_id);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Relation de parrainage créée avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la création de la relation de parrainage'
        ]);
    }
    
} catch (PDOException $e) {
    // Log l'erreur détaillée
    error_log("Erreur PDO lors de la création d'une relation de parrainage: " . $e->getMessage());
    
    // Retourner une erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    // Log l'erreur détaillée
    error_log("Exception lors de la création d'une relation de parrainage: " . $e->getMessage());
    
    // Retourner une erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 