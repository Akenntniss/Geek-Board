<?php
// Fichier de sauvegarde des réparations

// Inclusion des fichiers requis
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Vérifier que les données ont été correctement décodées
if ($data === null) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
    exit;
}

// Vérifier que les champs obligatoires sont présents
$required_fields = ['type_appareil', 'client_id', 'modele', 'a_mot_de_passe', 'description_probleme'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'success' => false, 
        'message' => 'Champs obligatoires manquants: ' . implode(', ', $missing_fields)
    ]);
    exit;
}

// Vérifier et formater les données
$client_id = (int)$data['client_id'];
$type_appareil = sanitize_input($data['type_appareil']);
$modele = sanitize_input($data['modele']);
$a_mot_de_passe = $data['a_mot_de_passe'] === 'oui' ? 1 : 0;
$mot_de_passe = $a_mot_de_passe ? sanitize_input($data['mot_de_passe'] ?? '') : '';
$description_probleme = sanitize_input($data['description_probleme']);
$statut = sanitize_input($data['statut'] ?? 'En attente');
$prix_reparation = isset($data['prix_reparation']) ? (float)str_replace(',', '.', $data['prix_reparation']) : 0;
$photo_appareil = $data['photo_appareil'] ?? null;

// Vérifier que la référence n'est pas dupliquée
$reference = 'REP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

try {
    // Démarrer une transaction
    $shop_pdo->beginTransaction();
    
    // Insérer la réparation
    $stmt = $shop_pdo->prepare("
        INSERT INTO reparations (
            reference, client_id, type_appareil, modele, 
            a_mot_de_passe, mot_de_passe, description_probleme, 
            statut, prix_reparation, date_reception
        ) VALUES (
            :reference, :client_id, :type_appareil, :modele, 
            :a_mot_de_passe, :mot_de_passe, :description_probleme, 
            :statut, :prix_reparation, NOW()
        )
    ");
    
    $stmt->execute([
        'reference' => $reference,
        'client_id' => $client_id,
        'type_appareil' => $type_appareil,
        'modele' => $modele,
        'a_mot_de_passe' => $a_mot_de_passe,
        'mot_de_passe' => $mot_de_passe,
        'description_probleme' => $description_probleme,
        'statut' => $statut,
        'prix_reparation' => $prix_reparation
    ]);
    
    $reparation_id = $shop_pdo->lastInsertId();
    
    // Si une photo a été fournie, l'enregistrer
    if ($photo_appareil) {
        // Extraire les données de l'image
        $photo_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photo_appareil));
        
        if ($photo_data !== false) {
            // Générer un nom de fichier unique
            $photo_filename = 'reparation_' . $reparation_id . '_' . uniqid() . '.jpg';
            $photo_path = '../uploads/photos/' . $photo_filename;
            
            // Créer le répertoire s'il n'existe pas
            if (!is_dir('../uploads/photos/')) {
                mkdir('../uploads/photos/', 0755, true);
            }
            
            // Enregistrer l'image
            if (file_put_contents($photo_path, $photo_data)) {
                // Mettre à jour la réparation avec le chemin de la photo
                $stmt = $shop_pdo->prepare("UPDATE reparations SET photo_path = :photo_path WHERE id = :id");
                $stmt->execute([
                    'photo_path' => 'uploads/photos/' . $photo_filename,
                    'id' => $reparation_id
                ]);
            }
        }
    }
    
    // Si une commande de pièces est requise
    if (isset($data['commande_requise']) && $data['commande_requise'] === '1') {
        $fournisseur_id = (int)$data['fournisseur_id'];
        $nom_piece = sanitize_input($data['nom_piece']);
        $reference_piece = sanitize_input($data['reference_piece'] ?? '');
        $quantite = (int)$data['quantite'];
        $prix_piece = (float)str_replace(',', '.', $data['prix_piece']);
        
        // Générer une référence pour la commande
        $commande_reference = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        
        // Insérer la commande
        $stmt = $shop_pdo->prepare("
            INSERT INTO commandes_pieces (
                reference, client_id, fournisseur_id, reparation_id, 
                nom_piece, reference_piece, quantite, prix_estime, 
                statut, date_creation
            ) VALUES (
                :reference, :client_id, :fournisseur_id, :reparation_id, 
                :nom_piece, :reference_piece, :quantite, :prix_estime, 
                'En attente', NOW()
            )
        ");
        
        $stmt->execute([
            'reference' => $commande_reference,
            'client_id' => $client_id,
            'fournisseur_id' => $fournisseur_id,
            'reparation_id' => $reparation_id,
            'nom_piece' => $nom_piece,
            'reference_piece' => $reference_piece,
            'quantite' => $quantite,
            'prix_estime' => $prix_piece
        ]);
    }
    
    // Récupérer les informations du client pour le SMS
    $stmt = $shop_pdo->prepare("
        SELECT c.telephone, c.prenom 
        FROM clients c 
        WHERE c.id = :client_id
    ");
    $stmt->execute(['client_id' => $client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    $client_telephone = $client['telephone'] ?? '';
    $client_prenom = $client['prenom'] ?? '';
    
    // Valider la transaction
    $shop_pdo->commit();
    
    // Renvoyer une réponse de succès
    echo json_encode([
        'success' => true, 
        'message' => 'Réparation enregistrée avec succès',
        'reparation_id' => $reparation_id,
        'client_telephone' => $client_telephone,
        'client_prenom' => $client_prenom
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    $shop_pdo->rollBack();
    
    // Logger l'erreur
    error_log("Erreur lors de l'enregistrement de la réparation: " . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de la réparation: ' . $e->getMessage()]);
}

// Fonction pour nettoyer les entrées
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?> 