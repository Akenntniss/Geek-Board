<?php
// Inclure la configuration et les fonctions
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration des headers pour les requêtes AJAX
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données du formulaire
$data = json_decode(file_get_contents('php://input'), true);

// Si les données ne sont pas au format JSON, essayer avec $_POST
if (!$data) {
    $data = $_POST;
}

// Vérifier les données reçues
if (!isset($data['produit_id']) || !is_numeric($data['produit_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID du produit invalide']);
    exit;
}

if (!isset($data['type_mouvement']) || !in_array($data['type_mouvement'], ['entree', 'sortie'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de mouvement invalide']);
    exit;
}

if (!isset($data['quantite']) || !is_numeric($data['quantite']) || $data['quantite'] <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'La quantité doit être un nombre positif']);
    exit;
}

if (!isset($data['motif']) || trim($data['motif']) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Le motif est requis']);
    exit;
}

// Vérification spécifique pour les transactions avec des partenaires
$transaction_partenaire = false;
if (($data['motif'] === 'Prêté à un partenaire' || $data['motif'] === 'Retour de prêt') && 
    (!isset($data['partenaire_id']) || !is_numeric($data['partenaire_id']))) {
    http_response_code(400);
    echo json_encode(['error' => 'ID du partenaire requis pour ce type de mouvement']);
    exit;
}

try {
    // Utiliser la connexion à la base de données du magasin actuel
    $shop_pdo = getShopDBConnection();
    
    // Vérifier que la connexion à la base de données est établie
    if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur de connexion à la base de données du magasin']);
        exit;
    }
    $produit_id = (int)$data['produit_id'];
    $type = $data['type_mouvement'];
    $quantite = (int)$data['quantite'];
    $motif = trim($data['motif']);
    $partenaire_id = isset($data['partenaire_id']) ? (int)$data['partenaire_id'] : null;
    $prix_vente = isset($data['prix_vente']) ? (float)$data['prix_vente'] : 0;
    
    // Vérifier le stock disponible si c'est une sortie
    if ($type === 'sortie') {
        $stmt = $shop_pdo->prepare("SELECT quantite, nom, prix_achat FROM produits WHERE id = ?");
        $stmt->execute([$produit_id]);
        $produit = $stmt->fetch();
        
        if (!$produit) {
            http_response_code(404);
            echo json_encode(['error' => 'Produit non trouvé']);
            exit;
        }
        
        if ($produit['quantite'] < $quantite) {
            http_response_code(400);
            echo json_encode([
                'error' => "Stock insuffisant. Il ne reste que {$produit['quantite']} unité(s).",
                'stock_actuel' => $produit['quantite']
            ]);
            exit;
        }
        
        // Pour les transactions avec des partenaires, utiliser le prix d'achat si la valeur n'est pas fournie
        if ($prix_vente <= 0 && ($motif === 'Prêté à un partenaire' || $motif === 'Retour de prêt')) {
            $prix_vente = (float)$produit['prix_achat'];
        }
    } else {
        // Pour les entrées, récupérer quand même le nom du produit et son prix d'achat
        $stmt = $shop_pdo->prepare("SELECT nom, prix_achat FROM produits WHERE id = ?");
        $stmt->execute([$produit_id]);
        $produit = $stmt->fetch();
        
        if (!$produit) {
            http_response_code(404);
            echo json_encode(['error' => 'Produit non trouvé']);
            exit;
        }
        
        // Pour les transactions avec des partenaires, utiliser le prix d'achat si la valeur n'est pas fournie
        if ($prix_vente <= 0 && ($motif === 'Prêté à un partenaire' || $motif === 'Retour de prêt')) {
            $prix_vente = (float)$produit['prix_achat'];
        }
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Enregistrer le mouvement de stock
    $sql = "INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, user_id, date_mouvement) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $shop_pdo->prepare($sql);
    
    // Si l'utilisateur n'est pas connecté, utiliser un ID par défaut
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    
    $stmt->execute([$produit_id, $type, $quantite, $motif, $user_id]);
    
    // Traitement spécifique pour les transactions avec des partenaires
    if ($partenaire_id && ($motif === 'Prêté à un partenaire' || $motif === 'Retour de prêt')) {
        // Calculer le montant total de la transaction basé sur le prix d'achat
        $montant_total = $prix_vente * $quantite; // Note: prix_vente contient en réalité le prix d'achat (passé depuis le frontend)
        
        if ($motif === 'Prêté à un partenaire') {
            $type_transaction = 'AVANCE';
            $description = "Prêt d'un " . $produit['nom'];
        } else { // Retour de prêt
            $type_transaction = 'REMBOURSEMENT';
            $description = "Retour de prêt d'un " . $produit['nom'];
        }
        
        // Enregistrer la transaction avec le partenaire
        $sql = "INSERT INTO transactions_partenaires 
                (partenaire_id, type, montant, description, date_transaction) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([$partenaire_id, $type_transaction, $montant_total, $description]);
        
        // Mettre à jour le solde du partenaire
        $montant_final = $type_transaction === 'REMBOURSEMENT' ? -$montant_total : $montant_total;
        
        // Vérifier si un solde existe déjà pour ce partenaire
        $stmt = $shop_pdo->prepare("SELECT solde_actuel FROM soldes_partenaires WHERE partenaire_id = ?");
        $stmt->execute([$partenaire_id]);
        $solde = $stmt->fetch();
        
        if ($solde) {
            // Mettre à jour le solde existant
            $stmt = $shop_pdo->prepare("
                UPDATE soldes_partenaires 
                SET solde_actuel = solde_actuel + ?, derniere_mise_a_jour = NOW() 
                WHERE partenaire_id = ?
            ");
            $stmt->execute([$montant_final, $partenaire_id]);
        } else {
            // Créer un nouveau solde
            $stmt = $shop_pdo->prepare("
                INSERT INTO soldes_partenaires 
                (partenaire_id, solde_actuel, derniere_mise_a_jour) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$partenaire_id, $montant_final]);
        }
    }
    
    // Mettre à jour le stock du produit
    if ($type === 'entree') {
        $sql = "UPDATE produits SET quantite = quantite + ?, updated_at = NOW() WHERE id = ?";
    } else {
        $sql = "UPDATE produits SET quantite = quantite - ?, updated_at = NOW() WHERE id = ?";
    }
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$quantite, $produit_id]);
    
    // Récupérer le stock mis à jour
    $stmt = $shop_pdo->prepare("SELECT id, nom, reference, quantite FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit_updated = $stmt->fetch();
    
    // Valider la transaction
    $shop_pdo->commit();
    
    // Retourner une réponse de succès
    echo json_encode([
        'success' => true,
        'message' => "Le mouvement de stock a été enregistré avec succès.",
        'produit' => $produit_updated
    ]);
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    // Enregistrer l'erreur dans les logs
    error_log("Erreur dans update_stock.php: " . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de l\'enregistrement du mouvement: ' . $e->getMessage()
    ]);
} 