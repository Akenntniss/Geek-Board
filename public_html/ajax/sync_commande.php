<?php
// Vérifier si la requête est en JSON
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
} else {
    // Fallback pour les requêtes non-JSON
    $data = $_POST;
}

// Vérifier les données requises
if (empty($data) || !isset($data['action']) || !isset($data['data'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

// Inclure les fichiers nécessaires
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Vérifier l'authentification
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Traiter selon l'action
$action = $data['action'];
$commandeData = $data['data'];

switch ($action) {
    case 'add':
        // Ajouter une nouvelle commande
        $result = add_commande($commandeData);
        break;
    case 'update':
        // Mettre à jour une commande existante
        $result = update_commande($commandeData);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

// Retourner le résultat
echo json_encode($result);

// Fonction pour ajouter une commande
function add_commande($data) {
    global $db;
    
    // Préparer les données
    $client_id = isset($data['client_id']) ? intval($data['client_id']) : 0;
    $fournisseur_id = isset($data['fournisseur_id']) ? intval($data['fournisseur_id']) : 0;
    $nom_piece = isset($data['nom_piece']) ? $db->real_escape_string($data['nom_piece']) : '';
    $quantite = isset($data['quantite']) ? intval($data['quantite']) : 1;
    $prix_estime = isset($data['prix_estime']) ? floatval($data['prix_estime']) : 0;
    $code_barre = isset($data['code_barre']) ? $db->real_escape_string($data['code_barre']) : '';
    $statut = isset($data['statut']) ? $db->real_escape_string($data['statut']) : 'en_attente';
    $reparation_id = isset($data['reparation_id']) ? intval($data['reparation_id']) : 0;
    
    // Insérer dans la base de données
    $query = "INSERT INTO commandes_pieces "
           . "(client_id, fournisseur_id, nom_piece, quantite, prix_estime, code_barre, statut, date_creation, reparation_id) "
           . "VALUES "
           . "($client_id, $fournisseur_id, '$nom_piece', $quantite, $prix_estime, '$code_barre', '$statut', NOW(), $reparation_id)";
    
    if ($db->query($query)) {
        $id = $db->insert_id;
        return ['success' => true, 'message' => 'Commande ajoutée avec succès', 'id' => $id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout de la commande: ' . $db->error];
    }
}

// Fonction pour mettre à jour une commande
function update_commande($data) {
    global $db;
    
    // Vérifier l'ID de la commande
    if (!isset($data['id'])) {
        return ['success' => false, 'message' => 'ID de commande manquant'];
    }
    
    $id = intval($data['id']);
    
    // Préparer les données
    $updates = [];
    
    if (isset($data['client_id'])) {
        $updates[] = "client_id = " . intval($data['client_id']);
    }
    
    if (isset($data['fournisseur_id'])) {
        $updates[] = "fournisseur_id = " . intval($data['fournisseur_id']);
    }
    
    if (isset($data['nom_piece'])) {
        $updates[] = "nom_piece = '" . $db->real_escape_string($data['nom_piece']) . "'";
    }
    
    if (isset($data['quantite'])) {
        $updates[] = "quantite = " . intval($data['quantite']);
    }
    
    if (isset($data['prix_estime'])) {
        $updates[] = "prix_estime = " . floatval($data['prix_estime']);
    }
    
    if (isset($data['code_barre'])) {
        $updates[] = "code_barre = '" . $db->real_escape_string($data['code_barre']) . "'";
    }
    
    if (isset($data['statut'])) {
        $updates[] = "statut = '" . $db->real_escape_string($data['statut']) . "'";
    }
    
    if (isset($data['reparation_id'])) {
        $updates[] = "reparation_id = " . intval($data['reparation_id']);
    }
    
    // S'il n'y a rien à mettre à jour
    if (empty($updates)) {
        return ['success' => false, 'message' => 'Aucune donnée à mettre à jour'];
    }
    
    // Construire la requête
    $query = "UPDATE commandes_pieces SET " . implode(", ", $updates) . " WHERE id = $id";
    
    if ($db->query($query)) {
        return ['success' => true, 'message' => 'Commande mise à jour avec succès', 'id' => $id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour de la commande: ' . $db->error];
    }
}
?>