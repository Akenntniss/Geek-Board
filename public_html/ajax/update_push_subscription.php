<?php
/**
 * API pour mettre à jour l'abonnement aux notifications push
 */

// Démarrer ou reprendre une session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Inclure les fichiers nécessaires
require_once '../config/database.php';

// Récupérer les données envoyées
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['endpoint']) || !isset($data['keys'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Connexion à la base de données
    $shop_pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Vérifier si un enregistrement existe déjà pour cet endpoint
    $stmt = $shop_pdo->prepare("
        SELECT id FROM push_subscriptions 
        WHERE endpoint = ? AND user_id = ?
    ");
    $stmt->execute([$data['endpoint'], $_SESSION['user_id']]);
    $existingSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingSubscription) {
        // Mettre à jour l'abonnement existant
        $stmt = $shop_pdo->prepare("
            UPDATE push_subscriptions 
            SET auth_key = ?, p256dh_key = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([
            $data['keys']['auth'],
            $data['keys']['p256dh'],
            $existingSubscription['id']
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Abonnement mis à jour avec succès',
            'subscription_id' => $existingSubscription['id']
        ]);
    } else {
        // Créer un nouvel abonnement
        $stmt = $shop_pdo->prepare("
            INSERT INTO push_subscriptions 
            (user_id, endpoint, auth_key, p256dh_key, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['endpoint'],
            $data['keys']['auth'],
            $data['keys']['p256dh']
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Nouvel abonnement créé avec succès',
            'subscription_id' => $shop_pdo->lastInsertId()
        ]);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    exit;
} 