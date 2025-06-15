<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration du fichier de log spécifique
ini_set('error_log', __DIR__ . '/../logs/debug/search_avancee.log');

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Log des données POST reçues
error_log("Recherche avancée - POST reçu: " . print_r($_POST, true));

// Vérifier que le terme de recherche est fourni
if (!isset($_POST['terme']) || empty($_POST['terme'])) {
    echo json_encode(['success' => false, 'message' => 'Terme de recherche manquant']);
    exit;
}

$terme = trim($_POST['terme']);
error_log("Terme de recherche avancée: " . $terme);

try {
    // Utiliser la connexion à la base de données du magasin actuel
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la connexion à la base de données
    if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données du magasin non disponible');
    }
    
    // Journaliser l'information sur la base de données utilisée
    try {
        $stmt_db = $shop_pdo->query("SELECT DATABASE() as db_name");
        $db_info = $stmt_db->fetch(PDO::FETCH_ASSOC);
        error_log("Recherche avancée - BASE DE DONNÉES UTILISÉE: " . ($db_info['db_name'] ?? 'Inconnue'));
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la base de données: " . $e->getMessage());
    }
    
    // Résultats à retourner
    $resultats = [
        'clients' => [],
        'reparations' => [],
        'commandes' => []
    ];
    
    // 1. Recherche de clients
    $sql_clients = "
        SELECT id, nom, prenom, telephone, email
        FROM clients 
        WHERE nom LIKE ? 
        OR prenom LIKE ? 
        OR telephone LIKE ? 
        ORDER BY nom, prenom 
        LIMIT 10
    ";
    
    $stmt = $shop_pdo->prepare($sql_clients);
    $terme_wildcard = "%$terme%";
    $stmt->execute([$terme_wildcard, $terme_wildcard, $terme_wildcard]);
    $resultats['clients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Recherche de réparations
    $sql_reparations = "
        SELECT r.id, 
               r.client_id,
               r.type_appareil as appareil,
               r.marque,
               r.modele,
               r.description_probleme as probleme,
               r.date_reception, 
               r.statut,
               c.nom as client_nom, 
               c.prenom as client_prenom, 
               c.id as client_id
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id LIKE ? 
        OR r.type_appareil LIKE ? 
        OR r.modele LIKE ? 
        OR r.description_probleme LIKE ?
        OR c.nom LIKE ?
        OR c.prenom LIKE ?
        ORDER BY r.date_reception DESC
        LIMIT 10
    ";
    
    error_log("Requête SQL réparations: " . $sql_reparations);
    error_log("Paramètres: " . str_repeat("'%$terme%', ", 6));
    
    $stmt = $shop_pdo->prepare($sql_reparations);
    $stmt->execute([
        $terme_wildcard,  // r.id
        $terme_wildcard,  // r.type_appareil
        $terme_wildcard,  // r.modele
        $terme_wildcard,  // r.description_probleme
        $terme_wildcard,  // c.nom
        $terme_wildcard   // c.prenom
    ]);
    
    // Log des résultats
    $resultats['reparations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Réparations trouvées: " . count($resultats['reparations']));
    if (count($resultats['reparations']) > 0) {
        error_log("Premier résultat: " . json_encode($resultats['reparations'][0]));
    }
    
    // 3. Recherche de commandes
    $sql_commandes = "
        SELECT cp.id, 
               cp.nom_piece, 
               cp.reference, 
               cp.date_creation, 
               cp.statut, 
               c.nom as client_nom, 
               c.prenom as client_prenom, 
               c.id as client_id
        FROM commandes_pieces cp
        LEFT JOIN clients c ON cp.client_id = c.id
        WHERE cp.id LIKE ?
        OR cp.nom_piece LIKE ?
        OR cp.reference LIKE ?
        OR c.nom LIKE ?
        OR c.prenom LIKE ?
        ORDER BY cp.date_creation DESC
        LIMIT 10
    ";
    
    error_log("Requête SQL commandes: " . $sql_commandes);
    error_log("Paramètres: " . str_repeat("'%$terme%', ", 5));
    
    $stmt = $shop_pdo->prepare($sql_commandes);
    $stmt->execute([
        $terme_wildcard,  // cp.id
        $terme_wildcard,  // cp.nom_piece
        $terme_wildcard,  // cp.reference
        $terme_wildcard,  // c.nom
        $terme_wildcard   // c.prenom
    ]);
    $resultats['commandes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul des nombres de résultats
    $counts = [
        'clients' => count($resultats['clients']),
        'reparations' => count($resultats['reparations']),
        'commandes' => count($resultats['commandes']),
        'total' => count($resultats['clients']) + count($resultats['reparations']) + count($resultats['commandes'])
    ];
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'resultats' => $resultats,
        'counts' => $counts,
        'terme' => $terme
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la recherche avancée: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la récupération de la recherche avancée: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 