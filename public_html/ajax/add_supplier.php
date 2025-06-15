<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Vérifier si les fichiers de configuration existent
$configFile = dirname(__DIR__) . '/config/database.php';
$functionsFile = dirname(__DIR__) . '/includes/functions.php';

if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fichier de configuration manquant: ' . $configFile]);
    exit;
}

if (!file_exists($functionsFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fichier de fonctions manquant: ' . $functionsFile]);
    exit;
}

require_once $configFile;
require_once $functionsFile;

// Vérifier si les données sont présentes
if (!isset($_POST['nom']) || empty($_POST['nom'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le nom du fournisseur est requis']);
    exit;
}

$nom = cleanInput($_POST['nom']);
$url = isset($_POST['url']) ? cleanInput($_POST['url']) : '';

try {
    // Vérifier si le fournisseur existe déjà
    $stmt = $shop_pdo->prepare("SELECT id FROM fournisseurs WHERE nom = ?");
    $stmt->execute([$nom]);
    if ($stmt->fetch()) {
        throw new Exception('Un fournisseur avec ce nom existe déjà');
    }

    // Insérer le nouveau fournisseur
    $sql = "INSERT INTO fournisseurs (nom, url) VALUES (?, ?)";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$nom, $url]);

    echo json_encode([
        'success' => true,
        'message' => 'Fournisseur ajouté avec succès',
        'id' => $shop_pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 