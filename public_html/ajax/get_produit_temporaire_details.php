<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier si l'ID du produit est fourni
if (!isset($_POST['produit_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du produit non fourni']);
    exit;
}

$produit_id = intval($_POST['produit_id']);

try {
    // Récupérer les détails du produit
    $query = "SELECT pt.*, p.nom as nom_produit, p.reference 
              FROM produits_temporaires pt 
              JOIN produits p ON pt.produit_id = p.id 
              WHERE pt.id = :produit_id";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute(['produit_id' => $produit_id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produit) {
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit;
    }
    
    // Récupérer les informations du colis si associé
    $colis = null;
    if ($produit['colis_id']) {
        $query = "SELECT numero_suivi, transporteur, statut 
                  FROM colis 
                  WHERE id = :colis_id";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute(['colis_id' => $produit['colis_id']]);
        $colis = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Récupérer l'historique des vérifications
    $query = "SELECT v.*, u.nom as verifie_par 
              FROM verifications v 
              JOIN utilisateurs u ON v.verifie_par = u.id 
              WHERE v.produit_temporaire_id = :produit_id 
              ORDER BY v.date_verification DESC";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute(['produit_id' => $produit_id]);
    $verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'data' => [
            'reference' => $produit['reference'],
            'nom' => $produit['nom_produit'],
            'quantite' => $produit['quantite'],
            'date_ajout' => $produit['date_ajout'],
            'date_limite' => $produit['date_limite_retour'],
            'statut' => $produit['statut'],
            'montant_rembourse' => $produit['montant_rembourse'],
            'montant_rembourse_client' => $produit['montant_rembourse_client'],
            'colis' => $colis,
            'verifications' => $verifications
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données']);
} 