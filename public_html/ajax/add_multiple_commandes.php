<?php
// Définir le type de contenu JSON
header('Content-Type: application/json');

// Inclure la configuration de session
require_once __DIR__ . '/../config/session_config.php';

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté',
        'redirect' => '/pages/login.php'
    ]);
    exit;
}

// Vérifier que le shop_id est défini
if (!isset($_SESSION['shop_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Shop ID non défini dans la session',
        'redirect' => '/pages/login.php'
    ]);
    exit;
}

try {
    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    error_log("Input brut reçu: " . $input);
    
    $data = json_decode($input, true);
    
    if (!$data) {
        error_log("Erreur de décodage JSON. Input: " . $input . " | Erreur JSON: " . json_last_error_msg());
        throw new Exception('Données JSON invalides: ' . json_last_error_msg());
    }
    
    error_log("Données reçues pour multiples commandes: " . print_r($data, true));
    
    // Validation des données communes
    $client_id = $data['client_id'] ?? null;
    $fournisseur_id = $data['fournisseur_id'] ?? null;
    $statut = $data['statut'] ?? 'en_attente';
    $pieces = $data['pieces'] ?? [];
    $nom_client_manuel = $data['nom_client_manuel'] ?? null;
    
    error_log("Validation - client_id: " . var_export($client_id, true));
    error_log("Validation - fournisseur_id: " . var_export($fournisseur_id, true));
    error_log("Validation - pieces count: " . count($pieces));
    
    if (empty($pieces)) {
        error_log("Erreur: Aucune pièce à commander");
        throw new Exception('Aucune pièce à commander');
    }
    
    if (empty($fournisseur_id)) {
        error_log("Erreur: Fournisseur requis");
        throw new Exception('Fournisseur requis');
    }
    
    // Connexion à la base de données du shop
    try {
        $shop_pdo = getShopDBConnection();
        error_log("Connexion à la base de données réussie");
    } catch (Exception $db_error) {
        error_log("Erreur de connexion à la base de données: " . $db_error->getMessage());
        throw new Exception('Erreur de connexion à la base de données: ' . $db_error->getMessage());
    }
    
    // Gestion du client manuel
    if ($client_id === '-1' && !empty($nom_client_manuel)) {
        error_log("Création d'un nouveau client: " . $nom_client_manuel);
        
        // Créer un nouveau client
        $stmt = $shop_pdo->prepare("
            INSERT INTO clients (nom, prenom, date_creation, statut) 
            VALUES (?, '', NOW(), 'actif')
        ");
        $stmt->execute([$nom_client_manuel]);
        $client_id = $shop_pdo->lastInsertId();
        
        error_log("Nouveau client créé avec l'ID: " . $client_id);
    } elseif (empty($client_id) || $client_id === '-1') {
        throw new Exception('Client requis');
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    $commandesCreees = 0;
    $commandeIds = [];
    
    // Traiter chaque pièce
    foreach ($pieces as $index => $piece) {
        $nom_piece = trim($piece['nom_piece'] ?? '');
        $code_barre = trim($piece['code_barre'] ?? '');
        $prix_estime = !empty($piece['prix_estime']) ? floatval($piece['prix_estime']) : 0.00;
        $quantite = intval($piece['quantite'] ?? 1);
        $reparation_id = !empty($piece['reparation_id']) ? intval($piece['reparation_id']) : null;
        
        // Validation de la pièce
        if (empty($nom_piece)) {
            throw new Exception("Le nom de la pièce " . ($index + 1) . " est requis");
        }
        
        if ($quantite < 1) {
            throw new Exception("La quantité de la pièce " . ($index + 1) . " doit être supérieure à 0");
        }
        
        // Générer une référence unique pour la commande
        $reference = 'CMD-' . date('Ymd') . '-' . time() . '-' . substr(uniqid(), -4);
        
        // Insérer la commande
        $stmt = $shop_pdo->prepare("
            INSERT INTO commandes_pieces (
                reference,
                client_id, 
                fournisseur_id, 
                nom_piece, 
                code_barre, 
                prix_estime, 
                quantite, 
                statut, 
                reparation_id,
                date_creation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $success = $stmt->execute([
            $reference,
            $client_id,
            $fournisseur_id,
            $nom_piece,
            $code_barre,
            $prix_estime,
            $quantite,
            $statut,
            $reparation_id
        ]);
        
        if ($success) {
            $commandeId = $shop_pdo->lastInsertId();
            $commandeIds[] = $commandeId;
            $commandesCreees++;
            
            error_log("Commande créée: ID=$commandeId, Pièce=$nom_piece, Quantité=$quantite");
        } else {
            throw new Exception("Erreur lors de l'insertion de la commande pour la pièce: " . $nom_piece);
        }
    }
    
    // Valider la transaction
    $shop_pdo->commit();
    
    error_log("$commandesCreees commandes créées avec succès");
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => "$commandesCreees commande(s) créée(s) avec succès",
        'commandesCreees' => $commandesCreees,
        'commandeIds' => $commandeIds
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    error_log("Erreur lors de la création des commandes multiples: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    // Capturer les erreurs fatales PHP
    error_log("Erreur fatale PHP: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur fatale: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 