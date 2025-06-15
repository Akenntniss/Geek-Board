<?php
// Inclure les fichiers nécessaires
require_once('../includes/config.php');
require_once('../includes/functions.php');

// Vérifier si c'est une requête AJAX
header('Content-Type: application/json');

// Initialiser la réponse
$response = [
    'success' => false,
    'results' => []
];

// Vérifier que les paramètres sont présents
if (!isset($_POST['query']) || empty($_POST['query'])) {
    echo json_encode($response);
    exit;
}

// Récupérer les paramètres
$query = trim($_POST['query']);
$type = isset($_POST['type']) ? trim($_POST['type']) : 'all';

// Vérifier que la requête a au moins 2 caractères
if (strlen($query) < 2) {
    echo json_encode($response);
    exit;
}

try {
    // Connexion à la base de données
    $shop_pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $shop_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $results = [];
    
    // Recherche de clients
    if ($type == 'all' || $type == 'clients') {
        $stmt = $shop_pdo->prepare("
            SELECT id, nom, prenom, telephone, email
            FROM clients
            WHERE 
                nom LIKE :query OR 
                prenom LIKE :query OR 
                telephone LIKE :query OR 
                email LIKE :query
            LIMIT 10
        ");
        $stmt->execute(['query' => "%$query%"]);
        
        while ($client = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'type' => 'client',
                'icon' => 'user',
                'title' => htmlspecialchars($client['nom'] . ' ' . $client['prenom']),
                'description' => htmlspecialchars('Tél: ' . ($client['telephone'] ?? 'Non défini') . ($client['email'] ? ' | Email: ' . $client['email'] : '')),
                'link' => 'index.php?page=modifier_client&id=' . $client['id']
            ];
        }
    }
    
    // Recherche de réparations
    if ($type == 'all' || $type == 'repairs') {
        $stmt = $shop_pdo->prepare("
            SELECT r.id, r.appareil, r.marque, r.modele, r.description_probleme, r.statut, c.nom as client_nom, c.prenom as client_prenom
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE 
                r.appareil LIKE :query OR 
                r.marque LIKE :query OR 
                r.modele LIKE :query OR 
                r.description_probleme LIKE :query OR
                CONCAT(c.nom, ' ', c.prenom) LIKE :query
            ORDER BY r.date_creation DESC
            LIMIT 10
        ");
        $stmt->execute(['query' => "%$query%"]);
        
        while ($repair = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'type' => 'repair',
                'icon' => 'tools',
                'title' => htmlspecialchars($repair['appareil'] . ' ' . $repair['marque'] . ' ' . $repair['modele']),
                'description' => htmlspecialchars(substr($repair['description_probleme'], 0, 50) . '... | Client: ' . $repair['client_nom'] . ' ' . $repair['client_prenom']),
                'link' => 'index.php?page=modifier_reparation&id=' . $repair['id']
            ];
        }
    }
    
    // Recherche par numéro de téléphone
    if ($type == 'all' || $type == 'phones') {
        $stmt = $shop_pdo->prepare("
            SELECT id, nom, prenom, telephone
            FROM clients
            WHERE telephone LIKE :query
            LIMIT 10
        ");
        $stmt->execute(['query' => "%$query%"]);
        
        while ($phone = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'type' => 'phone',
                'icon' => 'phone',
                'title' => htmlspecialchars($phone['telephone']),
                'description' => htmlspecialchars('Client: ' . $phone['nom'] . ' ' . $phone['prenom']),
                'link' => 'index.php?page=modifier_client&id=' . $phone['id']
            ];
        }
    }
    
    // Mettre à jour la réponse
    $response['success'] = true;
    $response['results'] = $results;
    
} catch (PDOException $e) {
    $response['message'] = "Erreur de base de données: " . $e->getMessage();
}

// Renvoyer la réponse
echo json_encode($response);
exit; 