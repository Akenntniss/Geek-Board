<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/save_commande.log';
file_put_contents($logFile, "--- Nouvelle requête d'enregistrement de commande ---\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    file_put_contents($logFile, "Config path: " . $config_path . "\n", FILE_APPEND);
    file_put_contents($logFile, "Functions path: " . $functions_path . "\n", FILE_APPEND);

    if (!file_exists($config_path) || !file_exists($functions_path)) {
        throw new Exception('Fichiers de configuration introuvables.');
    }

    require_once $config_path;
    require_once $functions_path;

    // Vérifier si la requête est en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer les données du formulaire
    $repair_id = isset($_POST['repair_id']) ? (int)$_POST['repair_id'] : 0;
    $fournisseur_id = isset($_POST['fournisseur_id']) ? (int)$_POST['fournisseur_id'] : 0;
    $nom_piece = isset($_POST['nom_piece']) ? $_POST['nom_piece'] : '';
    $code_barre = isset($_POST['code_barre']) ? $_POST['code_barre'] : '';
    $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 1;
    $prix_estime = isset($_POST['prix_estime']) ? (float)$_POST['prix_estime'] : 0;
    $statut = isset($_POST['statut']) ? $_POST['statut'] : 'en_attente';

    // Valider les données essentielles
    if (empty($nom_piece) || $fournisseur_id <= 0) {
        throw new Exception('Données incomplètes pour la commande');
    }

    // Récupérer le client_id à partir de la réparation
    $client_id = 0;
    if ($repair_id > 0) {
        $stmt = $shop_pdo->prepare("SELECT client_id FROM reparations WHERE id = ?");
        $stmt->execute([$repair_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reparation) {
            $client_id = $reparation['client_id'];
        }
    }

    // Si aucun client n'est associé, vérifier si un client_id est fourni directement
    if ($client_id <= 0 && isset($_POST['client_id'])) {
        $client_id = (int)$_POST['client_id'];
    }

    // Générer un numéro de commande
    $numero_commande = 'CMD-' . date('Ymd') . '-' . uniqid();

    // Enregistrer la commande dans la base de données
    $stmt = $shop_pdo->prepare("
        INSERT INTO commandes_pieces (
            numero_commande, 
            client_id, 
            reparation_id, 
            fournisseur_id, 
            nom_piece, 
            code_barre, 
            quantite, 
            prix_estime, 
            statut, 
            date_creation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt->execute([
        $numero_commande,
        $client_id,
        $repair_id > 0 ? $repair_id : null,
        $fournisseur_id,
        $nom_piece,
        $code_barre,
        $quantite,
        $prix_estime,
        $statut
    ])) {
        $error = $stmt->errorInfo();
        file_put_contents($logFile, "Erreur SQL: " . print_r($error, true) . "\n", FILE_APPEND);
        throw new Exception('Erreur lors de l\'enregistrement de la commande: ' . $error[2]);
    }

    $commande_id = $shop_pdo->lastInsertId();

    // Si la commande est associée à une réparation, mettre à jour le statut de la réparation
    if ($repair_id > 0) {
        $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = 'nouvelle_commande', statut_categorie = 1 WHERE id = ?");
        
        if (!$stmt->execute([$repair_id])) {
            $error = $stmt->errorInfo();
            file_put_contents($logFile, "Erreur SQL (mise à jour statut): " . print_r($error, true) . "\n", FILE_APPEND);
            // Ne pas lancer d'exception ici car la commande a déjà été enregistrée
            file_put_contents($logFile, "Avertissement: Impossible de mettre à jour le statut de la réparation\n", FILE_APPEND);
        }
    }

    // Préparer la réponse
    $response = [
        'success' => true,
        'message' => 'Commande enregistrée avec succès',
        'data' => [
            'commande_id' => $commande_id,
            'numero_commande' => $numero_commande
        ]
    ];
    
    file_put_contents($logFile, "Réponse: Commande enregistrée avec succès (ID: $commande_id)\n", FILE_APPEND);
    echo json_encode($response);

} catch (Exception $e) {
    // Log l'erreur pour le débogage
    $error_message = "Erreur dans save_commande.php: " . $e->getMessage();
    error_log($error_message);
    file_put_contents($logFile, "Exception: " . $error_message . "\n", FILE_APPEND);
    
    // Renvoyer une réponse JSON d'erreur
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 