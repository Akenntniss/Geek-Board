<?php
// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Définir l'en-tête JSON
header('Content-Type: application/json');

try {
    // Inclure les fichiers nécessaires
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Récupérer les données JSON
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id']) || !isset($data['urgent'])) {
        throw new Exception('Données invalides');
    }

    $id = (int)$data['id'];
    $urgent = (bool)$data['urgent'];

    // Mettre à jour le statut urgent
    $stmt = $pdo->prepare("UPDATE reparations SET urgent = ? WHERE id = ?");
    if (!$stmt->execute([$urgent, $id])) {
        throw new Exception('Erreur lors de la mise à jour');
    }

    // Récupérer le nouveau badge de statut
    $stmt = $pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
    $stmt->execute([$id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }

    // Créer le badge de statut
    $statut_couleurs = [
        'en_attente' => 'warning',
        'en_cours_diagnostique' => 'info',
        'en_cours_intervention' => 'primary',
        'en_attente_accord_client' => 'secondary',
        'en_attente_pieces' => 'warning',
        'termine' => 'success',
        'livre' => 'success',
        'annule' => 'danger'
    ];

    $statut_noms = [
        'en_attente' => 'En attente',
        'en_cours_diagnostique' => 'En diagnostic',
        'en_cours_intervention' => 'En intervention',
        'en_attente_accord_client' => 'Attente accord',
        'en_attente_pieces' => 'Attente pièces',
        'termine' => 'Terminé',
        'livre' => 'Livré',
        'annule' => 'Annulé'
    ];

    $couleur = $statut_couleurs[$reparation['statut']] ?? 'secondary';
    $nom_statut = $statut_noms[$reparation['statut']] ?? $reparation['statut'];

    $statut_badge = sprintf(
        '<span class="badge bg-%s">%s</span>',
        $couleur,
        htmlspecialchars($nom_statut)
    );

    echo json_encode([
        'success' => true,
        'statut_badge' => $statut_badge
    ]);

} catch (Exception $e) {
    error_log("Erreur dans toggle_urgent.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 