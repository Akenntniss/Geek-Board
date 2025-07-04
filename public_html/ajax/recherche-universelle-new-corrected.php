<?php
/**
 * API de Recherche Universelle - Version 2.2 CORRIGÉE
 * Recherche dans clients, réparations et commandes
 * 
 * @author GeekBoard
 * @version 2.2
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

// Fonction pour obtenir la configuration d'un shop
function getShopConfig($shop_id) {
    try {
        $main_pdo = new PDO(
            'mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4',
            'root',
            'Mamanmaman01#',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        
        $stmt = $main_pdo->prepare("SELECT db_host, db_port, db_name, db_user, db_pass FROM shops WHERE id = ? AND active = 1");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shop) {
            return null;
        }
        
        return [
            'host' => $shop['db_host'],
            'port' => $shop['db_port'] ?? '3306',
            'dbname' => $shop['db_name'],
            'user' => $shop['db_user'],
            'pass' => $shop['db_pass']
        ];
        
    } catch (Exception $e) {
        logError("Erreur récupération config shop", ['shop_id' => $shop_id, 'error' => $e->getMessage()]);
        return null;
    }
}

// Fonction pour se connecter à la base d'un shop
function connectToShopDB($shop_config) {
    try {
        $dsn = "mysql:host=" . $shop_config['host'] . ";port=" . $shop_config['port'] . ";dbname=" . $shop_config['dbname'] . ";charset=utf8mb4";
        
        $pdo = new PDO(
            $dsn,
            $shop_config['user'],
            $shop_config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        
        return $pdo;
        
    } catch (Exception $e) {
        logError("Erreur connexion shop DB", ['config' => $shop_config, 'error' => $e->getMessage()]);
        return null;
    }
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
            $main_pdo = new PDO(
                'mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4',
                'root',
                'Mamanmaman01#',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
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

    // Obtenir la configuration du shop et se connecter
    $shop_config = getShopConfig($shop_id);
    if (!$shop_config) {
        respondWithError("Configuration du magasin $shop_id introuvable");
    }
    
    $pdo = connectToShopDB($shop_config);
    if (!$pdo) {
        respondWithError("Impossible de se connecter à la base de données du magasin $shop_id");
    }

    logError("Connexion réussie à la base: " . $shop_config['dbname']);

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
                nom LIKE ? 
                OR prenom LIKE ? 
                OR telephone LIKE ? 
                OR email LIKE ? 
                OR CONCAT(nom, ' ', prenom) LIKE ?
            ORDER BY nom ASC, prenom ASC
            LIMIT 50
        ";
        
        $stmt_clients = $pdo->prepare($sql_clients);
        $stmt_clients->execute([$terme_like, $terme_like, $terme_like, $terme_like, $terme_like]);
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
                r.type_appareil LIKE ? 
                OR r.modele LIKE ? 
                OR r.probleme_declare LIKE ? 
                OR c.nom LIKE ? 
                OR c.prenom LIKE ? 
                OR CONCAT(c.nom, ' ', c.prenom) LIKE ?
                OR r.id = ?
            ORDER BY r.date_reception DESC
            LIMIT 50
        ";
        
        $stmt_reparations = $pdo->prepare($sql_reparations);
        $stmt_reparations->execute([
            $terme_like, $terme_like, $terme_like, $terme_like, $terme_like, $terme_like,
            is_numeric($terme) ? (int)$terme : 0
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
                cp.nom_piece LIKE ? 
                OR cp.reference LIKE ? 
                OR cp.fournisseur LIKE ? 
                OR c.nom LIKE ? 
                OR c.prenom LIKE ? 
                OR CONCAT(c.nom, ' ', c.prenom) LIKE ?
                OR cp.id = ?
            ORDER BY cp.date_commande DESC
            LIMIT 50
        ";
        
        $stmt_commandes = $pdo->prepare($sql_commandes);
        $stmt_commandes->execute([
            $terme_like, $terme_like, $terme_like, $terme_like, $terme_like, $terme_like,
            is_numeric($terme) ? (int)$terme : 0
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
        'shop_id' => $shop_id,
        'shop_db' => $shop_config['dbname']
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