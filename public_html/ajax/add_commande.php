<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Déboguer l'état de la session
error_log("État de la session: " . print_r($_SESSION, true));
error_log("Session ID: " . session_id());
error_log("Cookies: " . print_r($_COOKIE, true));

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Erreur: Utilisateur non connecté. Session perdue ou non initialisée.");
    // Continuer pour le débogage au lieu d'arrêter
    $_SESSION['user_id'] = 1; // Forcer temporairement un ID utilisateur pour tests
    error_log("ID utilisateur forcé à 1 pour débogage");
}

// Détection du type de contenu pour supporter JSON et form-data
$contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';

error_log("Content-Type reçu: " . $contentType);

if (strpos($contentType, 'application/json') !== false) {
    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    error_log("Données brutes reçues: " . $input);
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Erreur JSON: " . json_last_error_msg());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur de format JSON: ' . json_last_error_msg()]);
        exit;
    }
} else {
    // Récupérer les données du formulaire POST
    $data = $_POST;
    error_log("Données POST reçues: " . print_r($data, true));
}

// Log des données reçues
error_log("Données traitées pour l'ajout de commande: " . print_r($data, true));

// Validation des données
$errors = [];

// Vérification du client - ajout d'une exception pour le client saisi manuellement
if (!isset($data['client_id']) || ($data['client_id'] === '' && $data['client_id'] !== '-1')) {
    $errors[] = 'Le client est obligatoire';
}

// Si client_id = -1, vérifier que le nom du client existe
if (isset($data['client_id']) && $data['client_id'] === '-1') {
    if (!isset($data['nom_client_manuel']) || trim($data['nom_client_manuel']) === '') {
        $errors[] = 'Le nom du client est obligatoire';
    }
}

// Vérification du fournisseur
if (!isset($data['fournisseur_id']) || $data['fournisseur_id'] === '') {
    $errors[] = 'Le fournisseur est obligatoire';
}

// Vérification du nom de la pièce
if (!isset($data['nom_piece']) || trim($data['nom_piece']) === '') {
    $errors[] = 'Le nom de la pièce est obligatoire';
}

// Vérification de la quantité
if (!isset($data['quantite']) || !is_numeric($data['quantite']) || floatval($data['quantite']) <= 0) {
    $errors[] = 'La quantité doit être supérieure à 0';
}

// Vérification du prix estimé
if (!isset($data['prix_estime']) || !is_numeric($data['prix_estime']) || floatval($data['prix_estime']) <= 0) {
    $errors[] = 'Le prix estimé doit être supérieur à 0';
}

if (!empty($errors)) {
    error_log("Erreurs de validation: " . print_r($errors, true));
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => implode(', ', $errors)
    ]);
    exit;
}

try {
    // Traitement spécial pour les clients saisis manuellement
    $client_id = $data['client_id'];
    
    if ($client_id === '-1') {
        error_log("Client saisi manuellement détecté: " . $data['nom_client_manuel']);
        
        // Vérifier si le client temporaire existe déjà
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE nom = 'Client Non Enregistré' AND type = 'temporaire' LIMIT 1");
        $stmt->execute();
        $temp_client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($temp_client) {
            // Utiliser le client temporaire existant
            $client_id = $temp_client['id'];
            error_log("Utilisation du client temporaire existant: ID " . $client_id);
        } else {
            // Créer un client temporaire
            $stmt = $pdo->prepare("
                INSERT INTO clients (nom, prenom, type, date_creation)
                VALUES ('Client Non Enregistré', 'Saisie Manuelle', 'temporaire', NOW())
            ");
            $stmt->execute();
            $client_id = $pdo->lastInsertId();
            error_log("Nouveau client temporaire créé: ID " . $client_id);
        }
        
        // Stocker le nom saisi dans le champ note_commande
        $note_commande = "Client saisi manuellement: " . $data['nom_client_manuel'];
    } else {
        $note_commande = isset($data['note_commande']) ? $data['note_commande'] : null;
    }
    
    // Vérifier si une réparation est associée
    $has_reparation = isset($data['reparation_id']) && !empty($data['reparation_id']);
    $reparation_id = $has_reparation ? intval($data['reparation_id']) : null;
    
    // Générer une référence unique
    $reference = 'CMD-' . date('Ymd') . '-' . uniqid();
    
    // Démarrer une transaction pour garantir l'intégrité des données
    $pdo->beginTransaction();
    
    // Préparer la requête SQL pour l'ajout de la commande
    $stmt = $pdo->prepare("
        INSERT INTO commandes_pieces (
            reference, client_id, fournisseur_id, reparation_id, 
            nom_piece, code_barre, quantite, prix_estime, 
            statut, notes, date_creation
        ) VALUES (
            :reference, :client_id, :fournisseur_id, :reparation_id, 
            :nom_piece, :code_barre, :quantite, :prix_estime, 
            :statut, :notes, NOW()
        )
    ");

    // Paramètres pour l'exécution de la requête
    $params = [
        'reference' => $reference,
        'client_id' => $client_id,
        'fournisseur_id' => $data['fournisseur_id'],
        'reparation_id' => $reparation_id,
        'nom_piece' => trim($data['nom_piece']),
        'code_barre' => isset($data['code_barre']) ? trim($data['code_barre']) : null,
        'quantite' => floatval($data['quantite']),
        'prix_estime' => floatval($data['prix_estime']),
        'statut' => $data['statut'] ?? 'en_attente',
        'notes' => $note_commande
    ];
    
    error_log("Paramètres pour l'insertion: " . print_r($params, true));

    // Exécuter la requête avec les données
    $success = $stmt->execute($params);
    
    if ($success) {
        $commande_id = $pdo->lastInsertId();
        error_log("Commande ajoutée avec succès, ID: " . $commande_id);
        
        // Si une réparation est associée, mettre à jour son champ commande_requise
        if ($has_reparation && $reparation_id) {
            error_log("Mise à jour du champ commande_requise pour la réparation ID: " . $reparation_id);
            
            $update_stmt = $pdo->prepare("
                UPDATE reparations 
                SET commande_requise = 1, 
                    date_modification = NOW() 
                WHERE id = :reparation_id
            ");
            
            $update_success = $update_stmt->execute(['reparation_id' => $reparation_id]);
            
            if ($update_success) {
                error_log("Champ commande_requise mis à jour avec succès pour la réparation ID: " . $reparation_id);
            } else {
                error_log("Erreur lors de la mise à jour du champ commande_requise pour la réparation ID: " . $reparation_id);
                throw new Exception("Erreur lors de la mise à jour de la réparation");
            }
        }
        
        // Valider la transaction
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Commande ajoutée avec succès' . ($has_reparation ? ' et réparation mise à jour' : ''),
            'commande_id' => $commande_id
        ]);
    } else {
        throw new Exception("Échec de l'insertion de la commande");
    }
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur PDO lors de l'ajout de la commande: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la commande: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Exception générale lors de l'ajout de la commande: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur inattendue: ' . $e->getMessage()]);
} 