<?php
// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Définir l'en-tête JSON avant tout
header('Content-Type: application/json');

// Inclusion du fichier de configuration et functions
require_once '../config/database.php';
require_once '../includes/functions.php';

// Démarrer la session seulement si elle n'est pas déjà active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définitions des textes d'affichage pour les statuts
$GLOBALS['display_texts'] = [
    'nouveau_diagnostique' => 'Nouveau Diagnostique',
    'nouvelle_intervention' => "Nouvelle Intervention",
    'nouvelle_commande' => 'Nouvelle Commande',
    'en_cours_diagnostique' => 'En cours de diagnostique',
    'en_cours_intervention' => "En cours d'intervention",
    'en_attente_accord_client' => "En attente de l'accord client",
    'en_attente_livraison' => 'En attente de livraison',
    'en_attente_responsable' => "En attente d'un responsable",
    'reparation_effectue' => 'Réparation Effectuée',
    'reparation_annule' => 'Réparation Annulée',
    'restitue' => 'Restitué',
    'gardiennage' => 'Gardiennage',
    'annule' => 'Annulé',
    'en_attente' => 'En attente',
    'en_cours' => 'En cours',
    'termine' => 'Terminé'
];

// Fonction interne pour générer un badge de statut (en cas de non disponibilité de la fonction originale)
function local_get_enum_status_badge($statut, $reparation_id = null) {
    // Définir les couleurs pour chaque statut ENUM
    $colors = [
        'En attente' => 'warning',
        'En cours' => 'primary',
        'Terminé' => 'success',
        'Livré' => 'info',
        'nouvelle_intervention' => 'info-light',
        'nouveau_diagnostique' => 'info-light',
        'en_cours_diagnostique' => 'primary-dark',
        'en_cours_intervention' => 'primary-dark',
        'nouvelle_commande' => 'danger-light',
        'en_attente_accord_client' => 'warning',
        'en_attente_livraison' => 'orange',
        'en_attente_responsable' => 'orange-red',
        'reparation_effectue' => 'success',
        'reparation_annule' => 'danger',
        'restitue' => 'success',
        'gardiennage' => 'brown',
        'annule' => 'secondary'
    ];
    
    // Obtenir la couleur du statut
    $color = isset($colors[$statut]) ? $colors[$statut] : 'secondary';
    
    // Créer le badge HTML
    $display_text = isset($GLOBALS['display_texts'][$statut]) ? $GLOBALS['display_texts'][$statut] : ucfirst(str_replace('_', ' ', $statut));
    
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($display_text) . '</span>';
}

// Vérifier que la requête est valide
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de réparation non valide'
    ]);
    exit;
}

$reparation_id = intval($_GET['id']);

try {
    // Récupérer les détails de la réparation
    $stmt = $shop_pdo->prepare("
        SELECT r.*, 
               CONCAT(c.nom, ' ', c.prenom) as client_nom, 
               c.prenom as client_prenom,
               c.telephone as client_telephone,
               c.email as client_email
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        echo json_encode([
            'success' => false,
            'message' => 'Réparation non trouvée'
        ]);
        exit;
    }
    
    // Récupérer le badge de statut pour l'affichage
    $reparation['statut_badge'] = local_get_enum_status_badge($reparation['statut'], $reparation_id);
    
    // Récupérer les photos de la réparation
    $stmt = $shop_pdo->prepare("SELECT id, url, description FROM photos_reparation WHERE reparation_id = ? ORDER BY date_upload DESC");
    $stmt->execute([$reparation_id]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier si la photo principale de l'appareil existe et l'ajouter au début du tableau des photos
    if (!empty($reparation['photo_appareil'])) {
        // Créer une entrée pour la photo principale de l'appareil avec une description spéciale
        array_unshift($photos, [
            'id' => 'main', // Identifiant spécial pour la photo principale
            'url' => $reparation['photo_appareil'],
            'description' => 'Photo principale de l\'appareil',
            'is_main' => true
        ]);
    }
    
    $reparation['photos'] = $photos;
    
    // Récupérer les employés qui travaillent actuellement sur cette réparation
    $stmt = $shop_pdo->prepare("
        SELECT u.id, u.full_name as nom, ra.date_debut, ra.est_principal
        FROM reparation_attributions ra
        JOIN users u ON ra.employe_id = u.id
        WHERE ra.reparation_id = ? AND ra.date_fin IS NULL
        ORDER BY ra.est_principal DESC, ra.date_debut ASC
    ");
    $stmt->execute([$reparation_id]);
    $employes_actifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reparation['employes_actifs'] = $employes_actifs;
    
    // Récupérer l'historique des attributions
    $stmt = $shop_pdo->prepare("
        SELECT ra.*, u.full_name as nom
        FROM reparation_attributions ra
        JOIN users u ON ra.employe_id = u.id
        WHERE ra.reparation_id = ?
        ORDER BY ra.date_debut DESC
    ");
    $stmt->execute([$reparation_id]);
    $historique_attributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reparation['historique_attributions'] = $historique_attributions;
    
    echo json_encode([
        'success' => true,
        'reparation' => $reparation
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des détails: ' . $e->getMessage()
    ]);
} 