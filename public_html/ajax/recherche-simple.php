<?php
// Recherche simple universelle
session_start();

header('Content-Type: application/json');

// Récupérer le terme de recherche
$terme = isset($_POST['terme']) ? trim($_POST['terme']) : '';

if (empty($terme) || strlen($terme) < 2) {
    echo json_encode(['success' => false, 'error' => 'Terme de recherche trop court']);
    exit;
}

// Déterminer le shop_id depuis le sous-domaine
$host = $_SERVER['HTTP_HOST'];
$subdomain = explode('.', $host)[0];

// Mapping des sous-domaines aux shop_id et bases de données
$shop_mapping = [
    'mkmkmk' => ['id' => 63, 'db' => 'geekboard_mkmkmk'],
    'joki' => ['id' => 64, 'db' => 'geekboard_joki'],
    'cannes' => ['id' => 65, 'db' => 'geekboard_cannes'],
    'psphonac' => ['id' => 66, 'db' => 'geekboard_psphonac']
];

$shop_info = isset($shop_mapping[$subdomain]) ? $shop_mapping[$subdomain] : $shop_mapping['mkmkmk'];
$shop_id = $shop_info['id'];
$db_name = $shop_info['db'];

// Configuration de la base de données (utilise root comme dans config/database.php)
$db_config = [
    'host' => 'localhost',
    'db' => $db_name,
    'user' => 'root',
    'pass' => 'Mamanmaman01#'
];

try {
    // Connexion à la base de données
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['db']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $resultats = [];
    
    // Log pour debug
    error_log("Recherche simple - Terme: {$terme}, Shop ID: {$shop_id}, DB: {$db_config['db']}");
    
    // Recherche dans les clients
    $sql_clients = "SELECT id, nom, prenom, telephone FROM clients 
                   WHERE (nom LIKE :terme OR prenom LIKE :terme OR telephone LIKE :terme) 
                   LIMIT 10";
    
    $stmt = $pdo->prepare($sql_clients);
    $stmt->execute([':terme' => "%{$terme}%"]);
    
    while ($row = $stmt->fetch()) {
        $resultats[] = [
            'type' => 'client',
            'id' => $row['id'],
            'nom' => $row['nom'] . ' ' . $row['prenom'],
            'telephone' => $row['telephone']
        ];
    }
    
    // Recherche dans les réparations (correction des noms de colonnes)
    $sql_reparations = "SELECT r.id, r.type_appareil, r.modele, r.description_probleme, r.statut, 
                              c.nom as client_nom, c.prenom as client_prenom 
                       FROM reparations r
                       LEFT JOIN clients c ON r.client_id = c.id
                       WHERE (r.type_appareil LIKE :terme OR r.modele LIKE :terme OR r.description_probleme LIKE :terme)
                       LIMIT 10";
    
    $stmt = $pdo->prepare($sql_reparations);
    $stmt->execute([':terme' => "%{$terme}%"]);
    
    while ($row = $stmt->fetch()) {
        $resultats[] = [
            'type' => 'reparation',
            'id' => $row['id'],
            'client' => ($row['client_nom'] ? $row['client_nom'] . ' ' . $row['client_prenom'] : 'Client inconnu'),
            'appareil' => $row['type_appareil'] . ' ' . $row['modele'],
            'probleme' => $row['description_probleme'],
            'statut' => $row['statut']
        ];
    }
    
    // Recherche dans les commandes de pièces (correction du nom de colonne)
    try {
        $sql_commandes = "SELECT cp.id, cp.reparation_id, cp.nom_piece, cp.statut
                         FROM commandes_pieces cp
                         WHERE cp.nom_piece LIKE :terme
                         LIMIT 10";
        
        $stmt = $pdo->prepare($sql_commandes);
        $stmt->execute([':terme' => "%{$terme}%"]);
        
        while ($row = $stmt->fetch()) {
            $resultats[] = [
                'type' => 'commande',
                'id' => $row['id'],
                'reparation_id' => $row['reparation_id'],
                'piece' => $row['nom_piece'],
                'statut' => $row['statut']
            ];
        }
    } catch (PDOException $e) {
        // Table commandes_pieces n'existe peut-être pas, on continue sans
        error_log("Table commandes_pieces non trouvée : " . $e->getMessage());
    }
    
    // Log des résultats
    error_log("Recherche simple - Résultats trouvés: " . count($resultats));
    
    // Retourner les résultats
    echo json_encode([
        'success' => true,
        'resultats' => $resultats,
        'total' => count($resultats),
        'terme' => $terme,
        'shop_id' => $shop_id,
        'debug' => [
            'subdomain' => $subdomain,
            'host' => $host,
            'db' => $db_config['db']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur recherche simple : " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur de base de données',
        'details' => $e->getMessage(),
        'shop_id' => $shop_id,
        'config' => $db_config['db']
    ]);
}
?> 