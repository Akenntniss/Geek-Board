<?php
/**
 * Script simplifié pour l'ajout d'un bug
 */

// Empêcher l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(0);

// Toujours définir l'en-tête JSON d'abord
header('Content-Type: application/json');

// Démarrer la session
session_start();

try {
    // Vérifier la présence de la description
    if (!isset($_POST['description']) || trim($_POST['description']) === '') {
        echo json_encode(['success' => false, 'message' => 'Veuillez décrire le problème rencontré']);
        exit;
    }

    // Nettoyer et récupérer les données du formulaire
    $description = trim($_POST['description']);
    $page_url = isset($_POST['page_url']) ? trim($_POST['page_url']) : '';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    // Récupérer les paramètres de base de données
    require_once '../config/database.php';
    
    // Connexion à la base de données
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Requête d'insertion
    $sql = "INSERT INTO bug_reports (user_id, description, page_url, user_agent, priorite, status, date_creation) 
            VALUES (?, ?, ?, ?, 'basse', 'nouveau', NOW())";
            
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$user_id, $description, $page_url, $user_agent]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Merci pour votre signalement !']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du bug']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans simple_add_bug.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Exception dans simple_add_bug.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue']);
} 