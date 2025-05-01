<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit();
}

// Vérifier si l'ID du client est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de client invalide.";
    header('Location: /index.php?page=clients');
    exit();
}

$client_id = (int)$_GET['id'];

try {
    // Vérifier si le client existe
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Client non trouvé.";
        header('Location: /index.php?page=clients');
        exit();
    }

    // Vérifier si le client a des réparations en cours
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM reparations 
        WHERE client_id = ? 
        AND statut NOT IN ('termine', 'livre', 'annule', 'refuse')
    ");
    $stmt->execute([$client_id]);
    $reparations_en_cours = $stmt->fetchColumn();

    if ($reparations_en_cours > 0) {
        $_SESSION['error'] = "Impossible de supprimer ce client car il a des réparations en cours.";
        header('Location: /index.php?page=clients');
        exit();
    }

    // Supprimer le client
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);

    $_SESSION['success'] = "Client supprimé avec succès.";
} catch (PDOException $e) {
    error_log("Erreur lors de la suppression du client : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la suppression du client.";
}

header('Location: /index.php?page=clients');
exit(); 