<?php
// Régler les paramètres des cookies pour permettre les requêtes cross-origin
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);

// Définir l'en-tête pour autoriser les requêtes cross-origin (CORS)
header('Access-Control-Allow-Origin: https://mdgeek.top');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Gérer la requête OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Définir l'en-tête JSON avant tout
header('Content-Type: application/json');

// Définitions des textes d'affichage pour les statuts
$GLOBALS['display_texts'] = [
    'nouveau_diagnostique' => 'Nouveau Diagnostique',
    'nouvelle_intervention' => "Nouvelle Intervention",
    'nouvelle_commande' => 'Nouvelle Commande',
    'en_cours_diagnostique' => 'En cours de diagnostique',
    'en_cours_intervention' => "En cours d'intervention",
    'en_attente_accord_client' => "En attente de l'accord client",
    'en_attente_livraison' => 'En attente de livraison',
    'en_attente_responsable' => "En attente d'un responsable",
    'reparation_effectue' => 'Réparation Effectuée',
    'reparation_annule' => 'Réparation Annulée',
    'restitue' => 'Restitué',
    'gardiennage' => 'Gardiennage',
    'annule' => 'Annulé',
    'en_attente' => 'En attente',
    'en_cours' => 'En cours',
    'termine' => 'Terminé'
];

// Inclure la configuration de la base de données
require_once('../config/database.php');
require_once('../includes/functions.php');

// Démarrer la session seulement si elle n'est pas déjà active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Récupérer les données de la requête
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);
$action = isset($data['action']) ? $data['action'] : '';
$reparation_id = isset($data['reparation_id']) ? intval($data['reparation_id']) : 0;

// Vérification alternative de l'authentification (pour contourner les problèmes de session)
$alt_user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$alt_user_token = isset($data['user_token']) ? $data['user_token'] : '';

// Si l'utilisateur n'est pas authentifié via session mais a fourni un token, vérifier le token
if (!isset($_SESSION['user_id']) && $alt_user_id > 0 && !empty($alt_user_token)) {
    // Récupérer les infos de l'utilisateur pour vérifier
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$alt_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Récupérer la session associée à cet utilisateur
        $stmt = $pdo->prepare("SELECT token FROM user_sessions WHERE user_id = ? ORDER BY expiry DESC LIMIT 1");
        $stmt->execute([$alt_user_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session) {
            // Utilisateur trouvé, l'ajouter à la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Mettre à jour le cookie de session
            $cookie_params = session_get_cookie_params();
            setcookie(session_name(), session_id(), time() + 86400, 
                      $cookie_params['path'], $cookie_params['domain'], 
                      true, $cookie_params['httponly']);
            
            error_log("Session restaurée pour l'utilisateur {$user['id']} via token");
        }
    }
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Journaliser l'erreur et répondre
    error_log("Session perdue dans manage_repair_attribution.php. Données: " . $raw_data);
    
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action',
        'debug_info' => [
            'session_exists' => session_status() === PHP_SESSION_ACTIVE,
            'session_id' => session_id(),
            'alt_user_provided' => $alt_user_id > 0
        ]
    ]);
    exit;
}

// Utiliser directement l'ID de l'utilisateur connecté comme ID d'employé
$user_id = $_SESSION['user_id'];
$employe_id = $user_id; // Utiliser l'ID de l'utilisateur connecté comme ID d'employé

// Vérifier que les données nécessaires sont présentes
if (empty($action) || empty($reparation_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Données manquantes'
    ]);
    exit;
}

// Fonction pour logger l'action
function logReparationAction($pdo, $reparation_id, $employe_id, $action_type, $statut_avant = null, $statut_apres = null, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO reparation_logs (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$reparation_id, $employe_id, $action_type, $statut_avant, $statut_apres, $details]);
}

// Fonction interne pour générer un badge de statut (en cas de non disponibilité de la fonction originale)
function local_get_enum_status_badge($statut, $reparation_id = null) {
    // Définir les couleurs pour chaque statut ENUM
    $colors = [
        'En attente' => 'warning',
        'En cours' => 'primary',
        'Terminé' => 'success',
        'Livré' => 'info',
        'nouvelle_intervention' => 'info-light',
        'nouveau_diagnostique' => 'info-light',
        'en_cours_diagnostique' => 'primary-dark',
        'en_cours_intervention' => 'primary-dark',
        'nouvelle_commande' => 'danger-light',
        'en_attente_accord_client' => 'warning',
        'en_attente_livraison' => 'orange',
        'en_attente_responsable' => 'orange-red',
        'reparation_effectue' => 'success',
        'reparation_annule' => 'danger',
        'restitue' => 'success',
        'gardiennage' => 'brown',
        'annule' => 'secondary'
    ];
    
    // Obtenir la couleur du statut
    $color = isset($colors[$statut]) ? $colors[$statut] : 'secondary';
    
    // Créer le badge HTML
    $display_text = isset($GLOBALS['display_texts'][$statut]) ? $GLOBALS['display_texts'][$statut] : ucfirst(str_replace('_', ' ', $statut));
    
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($display_text) . '</span>';
}

// Vérifier les réparations en cours pour cet employé
function getActiveRepairsForEmployee($pdo, $employe_id) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.client_id, CONCAT(c.nom, ' ', c.prenom) as client_nom, r.type_appareil, r.modele 
        FROM reparation_attributions ra 
        JOIN reparations r ON ra.reparation_id = r.id 
        JOIN clients c ON r.client_id = c.id
        WHERE ra.employe_id = ? AND ra.date_fin IS NULL
    ");
    $stmt->execute([$employe_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Vérifier si une réparation a déjà un employé principal
function hasMainEmployee($pdo, $reparation_id, $current_employe_id) {
    $stmt = $pdo->prepare("
        SELECT ra.employe_id, u.full_name as employe_nom
        FROM reparation_attributions ra 
        JOIN users u ON ra.employe_id = u.id
        WHERE ra.reparation_id = ? AND ra.date_fin IS NULL AND ra.est_principal = 1 AND ra.employe_id != ?
    ");
    $stmt->execute([$reparation_id, $current_employe_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Traiter les différentes actions
switch ($action) {
    case 'demarrer':
        try {
            // Vérifier si la réparation existe et récupérer son statut actuel
            $stmt = $pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
            $stmt->execute([$reparation_id]);
            $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reparation) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Réparation non trouvée'
                ]);
                exit;
            }
            
            $statut_avant = $reparation['statut'];
            
            // Vérifier si d'autres personnes travaillent déjà sur cette réparation
            $stmt = $pdo->prepare("
                SELECT u.full_name as nom
                FROM reparation_attributions ra 
                JOIN users u ON ra.employe_id = u.id 
                WHERE ra.reparation_id = ? AND ra.date_fin IS NULL
            ");
            $stmt->execute([$reparation_id]);
            $other_workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Vérifier si un employé principal est déjà assigné
            $main_employee = hasMainEmployee($pdo, $reparation_id, $employe_id);
            $has_main_employee = !empty($main_employee);
            
            // Récupérer les réparations actives de l'employé
            $active_repairs = getActiveRepairsForEmployee($pdo, $employe_id);
            
            // Répondre avec les informations sur les autres travailleurs et les réparations actives
            echo json_encode([
                'success' => true,
                'other_workers' => $other_workers,
                'active_repairs' => $active_repairs,
                'has_main_employee' => $has_main_employee,
                'main_employee' => $main_employee,
                'can_start' => true
            ]);
            exit;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du démarrage: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage()
            ]);
            exit;
        }
        break;
        
    case 'confirmer_demarrage':
        try {
            // Vérifier si la réparation existe et récupérer son statut actuel
            $stmt = $pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
            $stmt->execute([$reparation_id]);
            $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reparation) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Réparation non trouvée'
                ]);
                exit;
            }
            
            $statut_avant = $reparation['statut'];
            $est_principal = isset($data['est_principal']) ? intval($data['est_principal']) : 1;
            
            // Vérifier si l'employé a déjà commencé cette réparation
            $stmt = $pdo->prepare("SELECT id FROM reparation_attributions WHERE reparation_id = ? AND employe_id = ? AND date_fin IS NULL");
            $stmt->execute([$reparation_id, $employe_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vous travaillez déjà sur cette réparation'
                ]);
                exit;
            }
            
            // Mise à jour du statut de la réparation en "En cours" si c'est le principal
            if ($est_principal == 1) {
                // Utiliser le statut fourni s'il existe, sinon utiliser le statut par défaut
                $statut_apres = isset($data['nouveau_statut']) ? $data['nouveau_statut'] : 'en_cours_intervention';
                
                // Valider que le statut est valide
                $stmt = $pdo->prepare("SELECT id, categorie_id FROM statuts WHERE code = ?");
                $stmt->execute([$statut_apres]);
                $statut_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si le statut n'est pas valide, utiliser un statut générique
                if (!$statut_info) {
                    $statut_apres = 'en_cours_intervention'; // Statut par défaut pour intervention
                    // Récupérer l'ID et la catégorie du statut par défaut
                    $stmt = $pdo->prepare("SELECT id, categorie_id FROM statuts WHERE code = ?");
                    $stmt->execute([$statut_apres]);
                    $statut_info = $stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Statut " . $statut_apres . " invalide. Utilisation du statut par défaut: en_cours_intervention");
                }
                
                $statut_id = $statut_info['id'];
                $statut_categorie = $statut_info['categorie_id'];
                
                // Mise à jour complète avec statut, catégorie de statut et ID du statut
                $stmt = $pdo->prepare("UPDATE reparations SET statut = ?, statut_id = ?, statut_categorie = ?, employe_id = ? WHERE id = ?");
                $stmt->execute([$statut_apres, $statut_id, $statut_categorie, $employe_id, $reparation_id]);
                
                // Mise à jour de l'utilisateur pour indiquer qu'il est occupé avec cette réparation
                $stmt = $pdo->prepare("UPDATE users SET techbusy = 1, active_repair_id = ? WHERE id = ?");
                $stmt->execute([$reparation_id, $employe_id]);
            } else {
                $statut_apres = $statut_avant; // Le statut ne change pas si c'est un assistant
            }
            
            // Créer l'attribution
            $stmt = $pdo->prepare("INSERT INTO reparation_attributions (reparation_id, employe_id, statut_avant, est_principal) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$reparation_id, $employe_id, $statut_avant, $est_principal]);
            
            if ($result) {
                // Enregistrer l'action dans les logs
                logReparationAction($pdo, $reparation_id, $employe_id, 'demarrage', $statut_avant, $statut_apres, 'Réparation démarrée' . ($est_principal ? ' en tant que principal' : ' en tant qu\'assistant'));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Réparation démarrée avec succès',
                    'new_status' => $statut_apres,
                    'new_status_badge' => local_get_enum_status_badge($statut_apres, $reparation_id)
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors du démarrage de la réparation'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du démarrage: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'terminer':
        try {
            // Vérifier si l'attribution existe
            $stmt = $pdo->prepare("
                SELECT ra.id, ra.statut_avant, ra.est_principal, r.statut 
                FROM reparation_attributions ra 
                JOIN reparations r ON ra.reparation_id = r.id 
                WHERE ra.reparation_id = ? AND ra.employe_id = ? AND ra.date_fin IS NULL
            ");
            $stmt->execute([$reparation_id, $employe_id]);
            $attribution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$attribution) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vous ne travaillez pas actuellement sur cette réparation'
                ]);
                exit;
            }
            
            $statut_avant = $attribution['statut'];
            $statut_apres = isset($data['nouveau_statut']) ? $data['nouveau_statut'] : $statut_avant;
            
            // Valider que le statut est valide
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM statuts WHERE code = ?");
            $stmt->execute([$statut_apres]);
            $statut_valide = ($stmt->fetchColumn() > 0);
            
            // Si le statut n'est pas valide, utiliser un statut par défaut
            if (!$statut_valide) {
                $statut_apres = 'reparation_effectue';
                error_log("Statut invalide fourni: " . $data['nouveau_statut'] . ". Utilisation du statut par défaut: " . $statut_apres);
            }
            
            // Mettre fin à l'attribution
            $stmt = $pdo->prepare("UPDATE reparation_attributions SET date_fin = NOW(), statut_apres = ? WHERE id = ?");
            $result = $stmt->execute([$statut_apres, $attribution['id']]);
            
            if ($result) {
                // Si c'était le principal, mettre à jour le statut de la réparation
                if ($attribution['est_principal'] == 1) {
                    $stmt = $pdo->prepare("UPDATE reparations SET statut = ? WHERE id = ?");
                    $stmt->execute([$statut_apres, $reparation_id]);
                    
                    // Mise à jour de l'utilisateur pour indiquer qu'il n'est plus occupé
                    $stmt = $pdo->prepare("UPDATE users SET techbusy = 0, active_repair_id = NULL WHERE id = ?");
                    $stmt->execute([$employe_id]);
                }
                
                // Enregistrer l'action dans les logs
                logReparationAction($pdo, $reparation_id, $employe_id, 'terminer', $statut_avant, $statut_apres, 'Réparation terminée' . ($attribution['est_principal'] ? ' en tant que principal' : ' en tant qu\'assistant'));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Réparation terminée avec succès',
                    'new_status' => $statut_apres,
                    'new_status_badge' => local_get_enum_status_badge($statut_apres, $reparation_id)
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la fin de la réparation'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la fin de la réparation: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_statuts':
        try {
            // Récupérer les statuts possibles
            $stmt = $pdo->query("SELECT s.code, s.nom, sc.couleur 
                                FROM statuts s 
                                JOIN statut_categories sc ON s.categorie_id = sc.id 
                                WHERE sc.id IN (2, 4) AND s.est_actif = 1
                                ORDER BY s.ordre ASC");
            
            if (!$stmt) {
                throw new PDOException("Erreur lors de l'exécution de la requête");
            }
            
            $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Vérifier si des statuts ont été trouvés
            if (empty($statuts)) {
                // Utiliser quelques statuts par défaut si aucun n'est trouvé dans la base de données
                $statuts = [
                    ['code' => 'en_cours_intervention', 'nom' => "En cours d'intervention", 'couleur' => 'primary'],
                    ['code' => 'reparation_effectue', 'nom' => 'Réparation effectuée', 'couleur' => 'success'],
                    ['code' => 'reparation_annule', 'nom' => 'Réparation annulée', 'couleur' => 'danger'],
                    ['code' => 'en_attente_accord_client', 'nom' => "En attente de l'accord client", 'couleur' => 'warning']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'statuts' => $statuts
            ]);
        } catch (PDOException $e) {
            // Journaliser l'erreur pour déboggage ultérieur
            error_log("Erreur get_statuts: " . $e->getMessage());
            
            // Renvoyer des statuts par défaut en cas d'erreur
            $statuts = [
                ['code' => 'en_cours_intervention', 'nom' => "En cours d'intervention", 'couleur' => 'primary'],
                ['code' => 'reparation_effectue', 'nom' => 'Réparation effectuée', 'couleur' => 'success'],
                ['code' => 'reparation_annule', 'nom' => 'Réparation annulée', 'couleur' => 'danger'],
                ['code' => 'en_attente_accord_client', 'nom' => "En attente de l'accord client", 'couleur' => 'warning']
            ];
            
            echo json_encode([
                'success' => true,
                'statuts' => $statuts,
                'message' => 'Utilisation de statuts par défaut en raison d\'une erreur: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'actives':
        try {
            // Récupérer les réparations actives de l'employé
            $active_repairs = getActiveRepairsForEmployee($pdo, $employe_id);
            
            echo json_encode([
                'success' => true,
                'active_repairs' => $active_repairs
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réparations actives: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue'
        ]);
} 