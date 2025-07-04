<?php
/**
 * API de Recherche Universelle - Version 2.0
 * Recherche dans clients, réparations et commandes
 * 
 * @author GeekBoard
 * @version 2.0
 */

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Headers pour JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Fonction pour logger les erreurs
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    error_log("[$timestamp] RECHERCHE_UNIVERSELLE: $message $contextStr");
}

// Fonction pour répondre avec erreur
function respondWithError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'error' => true,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondWithError('Méthode non autorisée', 405);
    }

    // Démarrer la session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Inclure les fichiers nécessaires
    require_once '../config/database.php';
    require_once '../includes/functions.php';

    // Déterminer le shop_id avec amélioration de la détection
    $shop_id = null;
    
    // Priorité aux paramètres URL
    if (isset($_GET['shop_id']) && (int)$_GET['shop_id'] > 0) {
        $shop_id = (int)$_GET['shop_id'];
        $_SESSION['shop_id'] = $shop_id;
    }
    // Sinon utiliser la session
    elseif (isset($_SESSION['shop_id'])) {
        $shop_id = (int)$_SESSION['shop_id'];
    }
    // Sinon détecter à partir du sous-domaine
    else {
        try {
            $main_pdo = getMainDBConnection();
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $subdomain = '';
            
            // Extraire le sous-domaine
            if (preg_match('/^([^.]+)\.mdgeek\.top$/', $host, $matches)) {
                $subdomain = $matches[1];
            }
            
            if ($subdomain) {
                // Rechercher le shop par sous-domaine
                $stmt = $main_pdo->prepare("SELECT id FROM shops WHERE subdomain = ? AND active = 1");
                $stmt->execute([$subdomain]);
                $shop = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($shop) {
                    $shop_id = (int)$shop['id'];
                    logError("Shop trouvé par sous-domaine: $subdomain -> ID: $shop_id");
                } else {
                    logError("Aucun shop trouvé pour le sous-domaine: $subdomain");
                    // Fallback: premier shop actif
                    $stmt = $main_pdo->query("SELECT id FROM shops WHERE active = 1 ORDER BY id ASC LIMIT 1");
                    $first_shop = $stmt->fetch(PDO::FETCH_ASSOC);
                    $shop_id = $first_shop['id'] ?? 1;
                }
            } else {
                logError("Pas de sous-domaine détecté dans: $host");
                // Pas de sous-domaine détecté, utiliser le premier shop
                $stmt = $main_pdo->query("SELECT id FROM shops WHERE active = 1 ORDER BY id ASC LIMIT 1");
                $first_shop = $stmt->fetch(PDO::FETCH_ASSOC);
                $shop_id = $first_shop['id'] ?? 1;
            }
            
            $_SESSION['shop_id'] = $shop_id;
            
        } catch (Exception $e) {
            logError("Impossible de déterminer shop_id", [
                'error' => $e->getMessage(),
                'host' => ($_SERVER['HTTP_HOST'] ?? 'unknown')
            ]);
            $shop_id = 1;
        }
    }

    logError("Recherche pour shop_id: $shop_id (host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . ")");

    // Récupérer le terme de recherche
    $terme = isset($_POST['terme']) ? trim($_POST['terme']) : '';

    if (empty($terme)) {
        respondWithError('Terme de recherche manquant');
    }

    if (strlen($terme) < 2) {
        respondWithError('Le terme de recherche doit contenir au moins 2 caractères');
    }

    // Nettoyer le terme
    $terme = htmlspecialchars($terme, ENT_QUOTES, 'UTF-8');
    $terme_like = '%' . $terme . '%';

    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection($shop_id);
    
    if (!$pdo) {
        respondWithError('Impossible de se connecter à la base de données');
    }

    // Initialiser les résultats
    $resultats = [
        'clients' => [],
        'reparations' => [],
        'commandes' => []
    ];

    // RECHERCHE CLIENTS
    try {
        $sql_clients = "
            SELECT 
                id,
                nom,
                prenom,
                telephone,
                email,
                adresse,
                ville,
                code_postal,
                date_creation
            FROM clients 
            WHERE 
                nom LIKE :terme 
                OR prenom LIKE :terme 
                OR telephone LIKE :terme 
                OR email LIKE :terme 
                OR CONCAT(nom, ' ', prenom) LIKE :terme
            ORDER BY nom ASC, prenom ASC
            LIMIT 50
        ";
        
        $stmt_clients = $pdo->prepare($sql_clients);
        $stmt_clients->execute([':terme' => $terme_like]);
        $resultats['clients'] = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);
        
        logError("Clients trouvés: " . count($resultats['clients']));
        
    } catch (Exception $e) {
        logError("Erreur recherche clients", ['error' => $e->getMessage()]);
        $resultats['clients'] = [];
    }

    // RECHERCHE RÉPARATIONS
    try {
        $sql_reparations = "
            SELECT 
                r.id,
                r.client_id,
                r.type_appareil,
                r.modele,
                r.probleme_declare,
                r.statut,
                r.date_reception,
                r.date_prevue,
                c.nom as client_nom,
                c.prenom as client_prenom,
                c.telephone as client_telephone
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE 
                r.type_appareil LIKE :terme 
                OR r.modele LIKE :terme 
                OR r.probleme_declare LIKE :terme 
                OR c.nom LIKE :terme 
                OR c.prenom LIKE :terme 
                OR CONCAT(c.nom, ' ', c.prenom) LIKE :terme
                OR r.id = :terme_exact
            ORDER BY r.date_reception DESC
            LIMIT 50
        ";
        
        $stmt_reparations = $pdo->prepare($sql_reparations);
        $stmt_reparations->execute([
            ':terme' => $terme_like,
            ':terme_exact' => is_numeric($terme) ? (int)$terme : 0
        ]);
        $resultats['reparations'] = $stmt_reparations->fetchAll(PDO::FETCH_ASSOC);
        
        logError("Réparations trouvées: " . count($resultats['reparations']));
        
    } catch (Exception $e) {
        logError("Erreur recherche réparations", ['error' => $e->getMessage()]);
        $resultats['reparations'] = [];
    }

    // RECHERCHE COMMANDES
    try {
        $sql_commandes = "
            SELECT 
                cp.id,
                cp.reparation_id,
                cp.nom_piece,
                cp.reference,
                cp.fournisseur,
                cp.prix,
                cp.statut,
                cp.date_commande,
                cp.date_prevue,
                c.nom as client_nom,
                c.prenom as client_prenom,
                r.type_appareil,
                r.modele
            FROM commandes_pieces cp
            LEFT JOIN reparations r ON cp.reparation_id = r.id
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE 
                cp.nom_piece LIKE :terme 
                OR cp.reference LIKE :terme 
                OR cp.fournisseur LIKE :terme 
                OR c.nom LIKE :terme 
                OR c.prenom LIKE :terme 
                OR CONCAT(c.nom, ' ', c.prenom) LIKE :terme
                OR cp.id = :terme_exact
            ORDER BY cp.date_commande DESC
            LIMIT 50
        ";
        
        $stmt_commandes = $pdo->prepare($sql_commandes);
        $stmt_commandes->execute([
            ':terme' => $terme_like,
            ':terme_exact' => is_numeric($terme) ? (int)$terme : 0
        ]);
        $resultats['commandes'] = $stmt_commandes->fetchAll(PDO::FETCH_ASSOC);
        
        logError("Commandes trouvées: " . count($resultats['commandes']));
        
    } catch (Exception $e) {
        logError("Erreur recherche commandes", ['error' => $e->getMessage()]);
        $resultats['commandes'] = [];
    }

    // Calculer les totaux
    $total_resultats = count($resultats['clients']) + count($resultats['reparations']) + count($resultats['commandes']);
    
    logError("Total résultats: $total_resultats");

    // Répondre avec les résultats
    $response = [
        'clients' => $resultats['clients'],
        'reparations' => $resultats['reparations'],
        'commandes' => $resultats['commandes'],
        'total' => $total_resultats,
        'terme' => $terme,
        'shop_id' => $shop_id
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    logError("Erreur générale", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    respondWithError('Erreur interne du serveur', 500);
}
?> 