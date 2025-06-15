<?php
// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Ajouter des logs pour le débogage de session
$logFile = __DIR__ . '/../logs/repair_status_debug.log';
file_put_contents($logFile, "--- Changement de statut: " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
file_put_contents($logFile, "Session status before: " . session_status() . "\n", FILE_APPEND);
file_put_contents($logFile, "Session ID before: " . session_id() . "\n", FILE_APPEND);
file_put_contents($logFile, "COOKIE data: " . print_r($_COOKIE, true) . "\n", FILE_APPEND);

// Définir l'en-tête JSON
header('Content-Type: application/json');

// Démarrer la session si nécessaire
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

file_put_contents($logFile, "Session status after: " . session_status() . "\n", FILE_APPEND);
file_put_contents($logFile, "Session ID after: " . session_id() . "\n", FILE_APPEND);
file_put_contents($logFile, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Inclure la configuration de la base de données et les fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Lire les données JSON de la requête POST
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents($logFile, "Données reçues: " . print_r($data, true) . "\n", FILE_APPEND);

// Récupérer l'ID de la réparation et le statut
$repair_id = isset($data['repair_id']) ? intval($data['repair_id']) : 0;
$status = isset($data['status']) ? $data['status'] : '';

// Récupérer l'ID de l'utilisateur
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Utilisation de l'ID 1 par défaut si non connecté

// Vérifier les données
if ($repair_id <= 0 || empty($status)) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres invalides'
    ]);
    file_put_contents($logFile, "Erreur: Paramètres invalides\n", FILE_APPEND);
    exit;
}

try {
    // Récupérer l'ancien statut
    $stmt = $shop_pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        echo json_encode([
            'success' => false,
            'message' => 'Réparation non trouvée'
        ]);
        file_put_contents($logFile, "Erreur: Réparation non trouvée\n", FILE_APPEND);
        exit;
    }
    
    $old_status = $repair['statut'];
    
    // Identifier la catégorie du statut
    $stmt = $shop_pdo->prepare("SELECT id, categorie_id FROM statuts WHERE code = ?");
    $stmt->execute([$status]);
    $statusInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $statut_id = $statusInfo ? $statusInfo['id'] : 0;
    $categorie_id = $statusInfo ? $statusInfo['categorie_id'] : 0;
    
    file_put_contents($logFile, "Statut info: " . print_r($statusInfo, true) . "\n", FILE_APPEND);
    
    // Mettre à jour le statut
    $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = ?, statut_categorie = ?, date_modification = NOW() WHERE id = ?");
    $stmt->execute([$status, $categorie_id, $repair_id]);
    
    // Enregistrer le changement dans les logs
    $stmt = $shop_pdo->prepare("
        INSERT INTO reparation_logs 
        (reparation_id, employe_id, action_type, statut_avant, statut_apres, details, date_action) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $repair_id,
        $user_id,
        'changement_statut',
        $old_status,
        $status,
        'Changement de statut via l\'API'
    ]);
    
    file_put_contents($logFile, "Succès: Statut mis à jour\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès',
        'new_status' => $status
    ]);
} catch (PDOException $e) {
    file_put_contents($logFile, "Erreur PDO: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du statut: ' . $e->getMessage()
    ]);
} 