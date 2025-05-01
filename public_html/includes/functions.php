<?php
/**
 * Fonctions utilitaires pour l'application
 */

// S'assurer que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Nettoie une chaîne de caractères pour éviter les injections
 * @param string $data La chaîne à nettoyer
 * @return string La chaîne nettoyée
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_NOQUOTES, 'UTF-8');
    return $data;
}

/**
 * Nettoie une chaîne de caractères pour éviter les injections
 * @param string $data La chaîne à nettoyer
 * @return string La chaîne nettoyée
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Nettoie une chaîne d'entrée pour éviter les injections XSS
 * @param string $input La chaîne à nettoyer
 * @return string La chaîne nettoyée
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_NOQUOTES, 'UTF-8');
    return $input;
}

// Fonction pour compter les réparations par statut
function get_reparations_count_by_status() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM reparations GROUP BY statut");
        $results = $stmt->fetchAll();
        
        // Convertir les statuts en anglais vers le français
        $converted_results = [];
        foreach ($results as $result) {
            $statut = $result['statut'];
            switch ($statut) {
                case 'En attente':
                    $converted_results[] = ['statut' => 'en_attente', 'count' => $result['count']];
                    break;
                case 'En cours':
                    $converted_results[] = ['statut' => 'en_cours', 'count' => $result['count']];
                    break;
                case 'Terminé':
                    $converted_results[] = ['statut' => 'termine', 'count' => $result['count']];
                    break;
                default:
                    $converted_results[] = $result;
            }
        }
        return $converted_results;
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des réparations : " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir le nombre total de clients
function get_total_clients() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
        return $stmt->fetch()['total'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des clients : " . $e->getMessage());
        return 0;
    }
}

// Fonction pour obtenir les réparations récentes
function get_recent_reparations($limit = 5) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, c.nom as client_nom 
            FROM reparations r 
            JOIN clients c ON r.client_id = c.id 
            WHERE r.statut IN ('nouveau_diagnostique', 'nouvelle_intervention', 'nouvelle_commande')
            ORDER BY r.date_reception DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des réparations récentes : " . $e->getMessage());
        return [];
    }
}

// Fonction pour formater la date
function format_date($date) {
    if ($date === null || empty($date)) {
        return 'Non définie';
    }
    return date('d/m/Y', strtotime($date));
}

/**
 * Détermine l'icône Font Awesome à utiliser en fonction du type d'appareil
 * @param string $device_type Type d'appareil
 * @return string Classe d'icône Font Awesome
 */
function get_device_icon($device_type) {
    if (empty($device_type)) {
        return 'fa-tools';
    }
    
    $device_type = strtolower($device_type);
    
    if (strpos($device_type, 'phone') !== false || strpos($device_type, 'téléphone') !== false || strpos($device_type, 'iphone') !== false) {
        return 'fa-mobile-alt';
    } elseif (strpos($device_type, 'tablet') !== false || strpos($device_type, 'tablette') !== false || strpos($device_type, 'ipad') !== false) {
        return 'fa-tablet-alt';
    } elseif (strpos($device_type, 'laptop') !== false || strpos($device_type, 'portable') !== false || strpos($device_type, 'macbook') !== false) {
        return 'fa-laptop';
    } elseif (strpos($device_type, 'desktop') !== false || strpos($device_type, 'bureau') !== false || strpos($device_type, 'imac') !== false) {
        return 'fa-desktop';
    } elseif (strpos($device_type, 'watch') !== false || strpos($device_type, 'montre') !== false || strpos($device_type, 'apple watch') !== false) {
        return 'fa-clock';
    } else {
        return 'fa-tools';
    }
}

/**
 * Récupère tous les statuts organisés par catégorie
 * @return array Tableau associatif des statuts par catégorie
 */
function get_all_statuts() {
    global $pdo;
    try {
        $query = "
            SELECT s.*, c.nom as categorie_nom, c.code as categorie_code, c.couleur
            FROM statuts s
            JOIN statut_categories c ON s.categorie_id = c.id
            WHERE s.est_actif = TRUE
            ORDER BY c.ordre, s.ordre
        ";
        $stmt = $pdo->query($query);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser les résultats par catégorie
        $statuts_par_categorie = [];
        foreach ($result as $statut) {
            if (!isset($statuts_par_categorie[$statut['categorie_code']])) {
                $statuts_par_categorie[$statut['categorie_code']] = [
                    'nom' => $statut['categorie_nom'],
                    'couleur' => $statut['couleur'],
                    'statuts' => []
                ];
            }
            
            $statuts_par_categorie[$statut['categorie_code']]['statuts'][] = [
                'id' => $statut['id'],
                'nom' => $statut['nom'],
                'code' => $statut['code']
            ];
        }
        
        return $statuts_par_categorie;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statuts: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère un statut par son code
 * @param string $code Le code du statut
 * @return array|false Les informations du statut ou false si non trouvé
 */
function get_statut_by_code($code) {
    global $pdo;
    try {
        $query = "
            SELECT s.*, c.nom as categorie_nom, c.code as categorie_code, c.couleur
            FROM statuts s
            JOIN statut_categories c ON s.categorie_id = c.id
            WHERE s.code = ?
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du statut: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un badge HTML pour un statut de réparation
 * @param string $status_code Code du statut
 * @param int $reparation_id ID de la réparation (optionnel, utilisé pour le drag & drop)
 * @return string Badge HTML formaté
 */
function get_status_badge($status_code, $reparation_id = null) {
    // Récupérer les informations du statut depuis la base de données
    $statut = get_statut_by_code($status_code);
    
    // Construire les attributs draggables (si ID de réparation fourni)
    $draggable_attrs = '';
    if ($reparation_id) {
        // Attributs pour le drag & drop
        $draggable_attrs = 'draggable="true" ' .
                           'class="status-badge badge bg-' . 
                           ($statut ? $statut['couleur'] : determine_color($status_code)) . 
                           '" data-repair-id="' . $reparation_id . '" ' .
                           'data-status-code="' . $status_code . '"';
    } else {
        // Sans drag & drop
        $draggable_attrs = 'class="badge bg-' . 
                           ($statut ? $statut['couleur'] : determine_color($status_code)) . '"';
    }
    
    if ($statut) {
        return '<span ' . $draggable_attrs . '>' . $statut['nom'] . '</span>';
    }
    
    // Fallback pour les anciens statuts ou si le statut n'est pas trouvé
    // Déterminer le texte à afficher
    $display_text = determine_display_text($status_code);
    
    return '<span ' . $draggable_attrs . '>' . $display_text . '</span>';
}

/**
 * Génère un badge HTML pour un statut de réparation à partir de la valeur ENUM statut
 * @param string $statut Valeur ENUM du statut (En attente, En cours, Terminé, etc.)
 * @param int $reparation_id ID de la réparation (optionnel, utilisé pour le drag & drop)
 * @return string Badge HTML formaté
 */
function get_enum_status_badge($statut, $reparation_id = null) {
    // Définir les couleurs pour chaque statut ENUM
    $colors = [
        'En attente' => 'warning',
        'En cours' => 'primary',
        'Terminé' => 'success',
        'Livré' => 'info',
        'nouvelle_intervention' => 'info',
        'nouveau_diagnostique' => 'primary',
        'en_cours_diagnostique' => 'primary',
        'en_cours_intervention' => 'primary',
        'nouvelle_commande' => 'danger',
        'en_attente_accord_client' => 'warning',
        'en_attente_livraison' => 'warning',
        'en_attente_responsable' => 'warning',
        'reparation_effectue' => 'success',
        'reparation_annule' => 'danger',
        'restitue' => 'danger',
        'gardiennage' => 'danger',
        'annule' => 'danger'
    ];
    
    // Obtenir la couleur du statut
    $color = isset($colors[$statut]) ? $colors[$statut] : 'secondary';
    
    // Obtenir le texte d'affichage en utilisant determine_display_text
    $display_text = determine_display_text($statut);
    
    // Si un ID de réparation est fourni, ajouter les attributs pour le drag & drop
    if ($reparation_id) {
        $badge_attrs = 'class="status-badge badge bg-' . $color . '" ' .
                      'draggable="true" ' .
                      'data-repair-id="' . $reparation_id . '" ' .
                      'data-status-code="' . $statut . '"';
    } else {
        // Sans drag & drop
        $badge_attrs = 'class="badge bg-' . $color . '"';
    }
    
    return '<span ' . $badge_attrs . '>' . htmlspecialchars($display_text) . '</span>';
}

/**
 * Détermine la couleur d'un badge en fonction du code de statut
 * @param string $status_code Code du statut
 * @return string Classe de couleur Bootstrap
 */
function determine_color($status_code) {
    // Définition des couleurs pour chaque catégorie
    $colors = [
        'nouvelle' => 'info',
        'en_cours' => 'primary',
        'en_attente' => 'warning',
        'termine' => 'success',
        'annule' => 'danger'
    ];
    
    // Correspondance entre les statuts spécifiques et leurs catégories
    $categories = [
        // Nouvelle
        'nouveau_diagnostique' => 'en_cours',
        'nouvelle_intervention' => 'nouvelle',
        'nouvelle_commande' => 'annule',
        
        // En cours
        'en_cours_diagnostique' => 'en_cours',
        'en_cours_intervention' => 'en_cours',
        
        // En attente
        'en_attente_accord_client' => 'en_attente',
        'en_attente_livraison' => 'en_attente',
        'en_attente_responsable' => 'en_attente',
        
        // Terminé
        'reparation_effectue' => 'termine',
        'reparation_annule' => 'termine',
        
        // Annulé
        'restitue' => 'annule',
        'gardiennage' => 'annule',
        'annule' => 'annule',
        
        // Compatibilité avec les anciens statuts
        'en_attente' => 'en_attente',
        'en_cours' => 'en_cours',
        'termine' => 'termine'
    ];
    
    // Déterminer la catégorie et la couleur du statut
    $category = isset($categories[$status_code]) ? $categories[$status_code] : 'secondary';
    return isset($colors[$category]) ? $colors[$category] : 'secondary';
}

/**
 * Détermine le texte à afficher pour un statut
 * @param string $status_code Code du statut
 * @return string Texte à afficher
 */
function determine_display_text($status_code) {
    // Noms d'affichage pour chaque statut
    $display_names = [
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
        
        // Compatibilité avec les anciens statuts
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'termine' => 'Terminé'
    ];
    
    return isset($display_names[$status_code]) ? $display_names[$status_code] : ucfirst(str_replace('_', ' ', $status_code));
}

// Fonction pour définir un message
function set_message($message, $type = 'success') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type
    ];
}

// Fonction pour afficher un message
function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return '<div class="alert alert-' . $message['type'] . ' alert-dismissible fade show" role="alert">
                    ' . $message['text'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    }
    return '';
}

// Fonction pour rediriger vers une page
function redirect($page, $params = []) {
    $url = "index.php?page=" . urlencode($page);
    
    // Ajouter les paramètres supplémentaires s'il y en a
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $url .= "&" . urlencode($key) . "=" . urlencode($value);
        }
    }
    
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        echo "<script>window.location.href = '" . $url . "';</script>";
        exit();
    }
}

/**
 * Formate un mois et une année en français
 * @param int $timestamp Le timestamp de la date
 * @return string Le mois et l'année en français
 */
function format_mois_annee($timestamp) {
    $mois = [
        'January' => 'Janvier',
        'February' => 'Février',
        'March' => 'Mars',
        'April' => 'Avril',
        'May' => 'Mai',
        'June' => 'Juin',
        'July' => 'Juillet',
        'August' => 'Août',
        'September' => 'Septembre',
        'October' => 'Octobre',
        'November' => 'Novembre',
        'December' => 'Décembre'
    ];
    
    $date = new DateTime();
    $date->setTimestamp($timestamp);
    $mois_anglais = $date->format('F');
    $annee = $date->format('Y');
    
    return $mois[$mois_anglais] . ' ' . $annee;
} 

/**
 * Récupère les tâches en cours avec une limite optionnelle
 * @param int $limit Nombre maximum de tâches à récupérer
 * @return array Tableau des tâches en cours
 */
function get_taches_en_cours($limit = 10) {
    global $pdo;
    try {
        // Vérifier si la connexion PDO est établie
        if (!isset($pdo) || !$pdo) {
            error_log("Erreur: La connexion à la base de données n'est pas établie");
            return [];
        }
        
        $query = "SELECT t.*, 
                       u.full_name as employe_nom,
                       c.full_name as createur_nom
                FROM taches t 
                LEFT JOIN users u ON t.employe_id = u.id 
                LEFT JOIN users c ON t.created_by = c.id 
                WHERE t.statut IN ('en_cours', 'a_faire')
                AND (t.employe_id = ? OR t.employe_id IS NULL)
                ORDER BY t.date_limite ASC, t.priorite DESC";
        
        if ($limit > 0) {
            $query .= " LIMIT ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_SESSION['user_id'], $limit]);
        } else {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_SESSION['user_id']]);
        }
        
        $taches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les données pour l'affichage
        foreach ($taches as &$tache) {
            // Convertir priorité en urgence pour la compatibilité avec l'interface
            $tache['urgence'] = $tache['priorite'];
            
            // Ajouter une valeur de progression basée sur le statut
            if ($tache['statut'] == 'a_faire') {
                $tache['progression'] = 0;
            } else {
                // par défaut 50% pour les tâches en cours
                $tache['progression'] = 50;
            }
            
            // Renommer date_limite en date_echeance pour la compatibilité avec l'interface
            // S'assurer que date_echeance est toujours définie, même si date_limite est null
            $tache['date_echeance'] = isset($tache['date_limite']) && !empty($tache['date_limite']) ? $tache['date_limite'] : null;
        }
        
        return $taches;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tâches en cours : " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les tâches urgentes avec une limite optionnelle
 * @param int $limit Nombre maximum de tâches à récupérer
 * @return array Tableau des tâches urgentes
 */
function get_taches_urgentes($limit = 5) {
    global $pdo;
    try {
        $query = "SELECT t.*, 
                       u.full_name as employe_nom,
                       c.full_name as createur_nom
                FROM taches t 
                LEFT JOIN users u ON t.employe_id = u.id 
                LEFT JOIN users c ON t.created_by = c.id 
                WHERE t.priorite = 'haute' OR t.priorite = 'urgente'
                ORDER BY t.date_limite ASC";
        
        if ($limit > 0) {
            $query .= " LIMIT ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$limit]);
        } else {
            $stmt = $pdo->query($query);
        }
        
        $taches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les données pour l'affichage
        foreach ($taches as &$tache) {
            // Convertir priorité en urgence pour la compatibilité avec l'interface
            $tache['urgence'] = 'haute';
            
            // Calculer une valeur de progression basée sur le statut
            if ($tache['statut'] == 'a_faire') {
                $tache['progression'] = 0;
            } elseif ($tache['statut'] == 'en_cours') {
                $tache['progression'] = 50;
            } elseif ($tache['statut'] == 'termine') {
                $tache['progression'] = 100;
            } else {
                $tache['progression'] = 25; // Valeur par défaut
            }
            
            // Renommer date_limite en date_echeance pour la compatibilité avec l'interface
            // S'assurer que date_echeance est toujours définie, même si date_limite ne l'est pas
            $tache['date_echeance'] = isset($tache['date_limite']) ? $tache['date_limite'] : null;
        }
        
        return $taches;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tâches urgentes : " . $e->getMessage());
        return [];
    }
}

/**
 * Génère une date formatée en français
 * @param string $date Date au format Y-m-d
 * @return string Date formatée
 */
function formatDate($date) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    $mois = [
        'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];
    
    $jour = date('j', $timestamp);
    $mois_numero = date('n', $timestamp) - 1;
    $annee = date('Y', $timestamp);
    
    return $jour . ' ' . $mois[$mois_numero] . ' ' . $annee;
}

/**
 * Formate un prix avec symbole €
 * @param float $prix Le prix à formater
 * @return string Le prix formaté
 */
function formatPrix($prix) {
    if (empty($prix) || !is_numeric($prix)) return 'N/A';
    return number_format($prix, 2, ',', ' ') . ' €';
}

/**
 * Génère un token CSRF
 * @return string Le token généré
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * @param string $token Le token à vérifier
 * @return bool Vrai si le token est valide
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Fonction pour obtenir le nombre de réparations par catégorie de statut
 * @return array Tableau associatif avec les comptages par catégorie 
 */
function get_reparations_count_by_status_categorie() {
    global $pdo;
    try {
        // Réparations en attente (catégorie 3)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE statut_categorie = ?");
        $stmt->execute([3]);
        $en_attente = $stmt->fetch()['count'];
        
        // Réparations en cours (catégorie 2)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE statut_categorie = ?");
        $stmt->execute([2]);
        $en_cours = $stmt->fetch()['count'];
        
        // Réparations nouvelles (catégorie 1)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE statut_categorie = ?");
        $stmt->execute([1]);
        $nouvelles = $stmt->fetch()['count'];
        
        // Réparations terminées (catégorie 4)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE statut_categorie = ?");
        $stmt->execute([4]);
        $terminees = $stmt->fetch()['count'];
        
        return [
            'en_attente' => $en_attente,
            'en_cours' => $en_cours,
            'nouvelles' => $nouvelles,
            'terminees' => $terminees
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des réparations par catégorie: " . $e->getMessage());
        return [
            'en_attente' => 0,
            'en_cours' => 0,
            'nouvelles' => 0,
            'terminees' => 0
        ];
    }
}

/**
 * Fonction pour obtenir le nombre de tâches récentes (à faire et en cours)
 * @return int Nombre de tâches récentes
 */
function get_taches_recentes_count() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM taches WHERE statut IN ('a_faire', 'en_cours')");
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des tâches récentes: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère les réparations récentes
 * @param int $limit Nombre maximum de réparations à récupérer
 * @return array Tableau des réparations récentes
 */
function get_recent_repairs($limit = 5) {
    global $pdo;
    try {
        // Requête simplifiée avec jointure minimale
        $stmt = $pdo->prepare("
            SELECT r.id, r.type_appareil, r.marque, r.modele, r.statut, r.date_reception,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM reparations r 
            JOIN clients c ON r.client_id = c.id 
            ORDER BY r.date_reception DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des réparations récentes : " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les tâches récentes
 * @param int $limit Nombre maximum de tâches à récupérer
 * @return array Tableau des tâches récentes
 */
function get_recent_tasks($limit = 5) {
    global $pdo;
    try {
        // Requête simplifiée sur la table taches
        $stmt = $pdo->prepare("
            SELECT t.id, t.titre, t.description, t.statut, t.priorite, t.date_limite,
                   u.full_name as employe_nom
            FROM taches t
            LEFT JOIN users u ON t.employe_id = u.id
            ORDER BY t.date_creation DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tâches récentes : " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les commandes en cours
 * @param int $limit Nombre maximum de commandes à récupérer
 * @return array Tableau des commandes en cours
 */
function get_commandes_en_cours($limit = 5) {
    global $pdo;
    try {
        // Requête simplifiée sur la table commandes_pieces
        $stmt = $pdo->prepare("
            SELECT cp.id, cp.nom_piece, cp.statut, cp.quantite, cp.prix_estime, cp.date_commande,
                   f.nom as fournisseur_nom, 
                   c.nom as client_nom, c.prenom as client_prenom
            FROM commandes_pieces cp
            LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id
            LEFT JOIN clients c ON cp.client_id = c.id
            ORDER BY cp.date_creation DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des commandes en cours : " . $e->getMessage());
        return [];
    }
}

/**
 * Génère l'URL de base du site
 * 
 * @return string L'URL de base du site
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_dir = dirname($script_name);
    
    // Si nous sommes à la racine, retourner simplement protocol://host/
    if ($base_dir == '/' || $base_dir == '\\') {
        return "$protocol://$host/";
    }
    
    // Sinon, ajouter le chemin de base
    return "$protocol://$host$base_dir/";
}

/**
 * Fonction pour envoyer un SMS
 * @param string $recipient Numéro de téléphone du destinataire (format international)
 * @param string $message Contenu du message
 * @param string $gateway_url URL de la passerelle SMS (optionnel)
 * @return array Résultat de l'opération
 */
function send_sms($recipient, $message, $gateway_url = null) {
    global $pdo; // S'assurer que la variable $pdo est accessible

    // Créer un fichier de log dans un dossier accessible
    $log_dir = BASE_PATH . '/logs/sms';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $log_file = $log_dir . '/sms_log_' . date('Y-m-d') . '.log';
    
    // Fonction de log simplifiée
    $log = function($message) use ($log_file) {
        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
    };
    
    try {
        $log("=== ENVOI SMS DÉMARRÉ ===");
        $log("Destinataire: $recipient");
        $log("Message: " . substr($message, 0, 30) . "...");
        
        // Vérification des paramètres
        if (empty($recipient) || empty($message)) {
            $log("Erreur: Paramètres manquants (destinataire ou message vide)");
            return ['success' => false, 'message' => 'Paramètres manquants'];
        }
        
        // Formatage du numéro de téléphone
        $recipient = preg_replace('/[^0-9+]/', '', $recipient); // Supprimer tous les caractères non numériques sauf +
        
        // S'assurer que le numéro commence par un + en essayant de le formater correctement
        if (substr($recipient, 0, 1) !== '+') {
            // Si le numéro commence par un 0, le remplacer par +33 (pour la France)
            if (substr($recipient, 0, 1) === '0') {
                $recipient = '+33' . substr($recipient, 1);
            }
            // Si le numéro commence déjà par 33, ajouter le +
            else if (substr($recipient, 0, 2) === '33') {
                $recipient = '+' . $recipient;
            }
            // Dans tous les autres cas, simplement ajouter le + au début
            else {
                $recipient = '+' . $recipient;
            }
        }
        
        // Configuration de l'API SMS Gateway
        $url = 'https://api.sms-gate.app/3rdparty/v1/message'; 
        $username = '-GCB75'; 
        $password = 'Mamanmaman06400'; 
        
        $log("URL API: $url");
        $log("Utilisateur: $username");
        $log("Numéro formaté: $recipient");
        
        // S'assurer que le numéro est au format international
        if (!preg_match('/^\+[0-9]{10,15}$/', $recipient)) {
            $log("Erreur: Format de numéro invalide");
            return ['success' => false, 'message' => 'Format de numéro invalide. Utilisez le format international (ex: +33612345678)'];
        }
        
        // Préparation des données pour SMS Gateway
        $sms_data = json_encode([
            'message' => $message,
            'phoneNumbers' => [$recipient]
        ]);
        
        $log("Données JSON à envoyer: $sms_data");
        
        // Envoi du SMS via l'API SMS Gateway
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $sms_data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($sms_data)
        ]);
        
        // Configuration de l'authentification Basic
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
        
        // Ajouter des options pour le débogage
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); 
        
        // Capturer les messages d'erreur détaillés
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($curl, CURLOPT_STDERR, $verbose);
        
        // Exécution de la requête
        $log("Exécution de la requête cURL...");
        $start_time = microtime(true);
        $response = curl_exec($curl);
        $time_taken = microtime(true) - $start_time;
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        $log("Code HTTP: $status");
        $log("Temps: " . round($time_taken * 1000) . " ms");
        
        // Récupérer les informations d'erreur curl si échec
        $curl_error = '';
        if ($response === false) {
            $curl_error = curl_error($curl);
            rewind($verbose);
            $verbose_log = stream_get_contents($verbose);
            $log("Erreur cURL: $curl_error");
            $log("Détails: $verbose_log");
        } else {
            $log("Réponse: $response");
        }
        
        curl_close($curl);
        if (is_resource($verbose)) {
            fclose($verbose);
        }
        
        // Enregistrement dans le fichier de log
        $log_data = date('[Y-m-d H:i:s]') . " Destinataire: $recipient, Message: " . substr($message, 0, 30) . "..., Statut: $status, Réponse: $response\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);
        
        // Enregistrement dans la base de données pour suivi
        $log_id = null;
        if ($pdo instanceof PDO) {
            try {
                // Vérifier que la table existe avant d'insérer
                if (function_exists('check_sms_logs_table')) {
                    check_sms_logs_table();
                }
                
                $stmt = $pdo->prepare("INSERT INTO sms_logs (recipient, message, status, response) VALUES (?, ?, ?, ?)");
                $stmt->execute([$recipient, $message, $status, $response ?: $curl_error]);
                $log_id = $pdo->lastInsertId();
                $log("SMS enregistré dans la base de données, ID: $log_id");
            } catch (PDOException $e) {
                $log("Erreur d'enregistrement du SMS dans la BDD: " . $e->getMessage());
            }
        } else {
            $log("AVERTISSEMENT: Connexion à la base de données non disponible");
        }
        
        // Traitement de la réponse
        $response_data = json_decode($response, true);
        
        // Le code 202 indique une acceptation (Accepted) pour traitement asynchrone
        if (($status == 200 || $status == 202) && $response_data) {
            $log("=== ENVOI SMS RÉUSSI ===");
            return [
                'success' => true, 
                'message' => 'SMS envoyé avec succès',
                'sms_id' => $response_data['id'] ?? null,
                'log_id' => $log_id,
                'status' => $status,
                'response' => $response_data
            ];
        } else {
            $error_message = 'Erreur lors de l\'envoi du SMS';
            if (!empty($curl_error)) {
                $error_message .= ' - Erreur CURL: ' . $curl_error;
            } elseif ($status > 0) {
                $error_message .= ' - Code HTTP: ' . $status;
            }
            
            $log("=== ENVOI SMS ÉCHOUÉ: $error_message ===");
            return [
                'success' => false, 
                'message' => $error_message,
                'error' => $response ?: $curl_error,
                'status' => $status,
                'log_id' => $log_id
            ];
        }
    } catch (Exception $e) {
        $log("Exception non gérée: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur interne: ' . $e->getMessage()
        ];
    }
}

/**
 * Fonction pour mettre une réparation en gardiennage
 * @param int $reparation_id ID de la réparation
 * @param float $tarif_journalier Tarif journalier (par défaut 5€)
 * @return array Résultat de l'opération
 */
function demarrer_gardiennage($reparation_id, $tarif_journalier = 5.00) {
    global $pdo;
    
    try {
        // Vérifier si la réparation existe
        $stmt = $pdo->prepare("SELECT id, client_id, statut FROM reparations WHERE id = ?");
        $stmt->execute([$reparation_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reparation) {
            return ['success' => false, 'message' => 'Réparation introuvable'];
        }
        
        // Vérifier si un gardiennage existe déjà pour cette réparation
        $stmt = $pdo->prepare("SELECT id FROM gardiennage WHERE reparation_id = ? AND est_actif = TRUE");
        $stmt->execute([$reparation_id]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Cette réparation est déjà en gardiennage'];
        }
        
        // Insérer un nouveau gardiennage
        $stmt = $pdo->prepare("
            INSERT INTO gardiennage (
                reparation_id, date_debut, date_derniere_facturation, tarif_journalier
            ) VALUES (?, CURRENT_DATE, CURRENT_DATE, ?)
        ");
        $stmt->execute([$reparation_id, $tarif_journalier]);
        
        $gardiennage_id = $pdo->lastInsertId();
        
        // Mettre à jour le statut de la réparation
        $stmt = $pdo->prepare("UPDATE reparations SET statut = 'gardiennage' WHERE id = ?");
        $stmt->execute([$reparation_id]);
        
        // Ajouter un log pour la réparation
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $stmt = $pdo->prepare("
            INSERT INTO reparation_logs (
                reparation_id, employe_id, action_type, statut_avant, statut_apres, details
            ) VALUES (?, ?, 'statut', ?, 'gardiennage', 'Mise en gardiennage')
        ");
        $stmt->execute([$reparation_id, $user_id, $reparation['statut']]);
        
        return [
            'success' => true, 
            'gardiennage_id' => $gardiennage_id,
            'message' => 'Gardiennage démarré avec succès'
        ];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors du démarrage du gardiennage : ' . $e->getMessage()];
    }
}

/**
 * Fonction pour terminer un gardiennage
 * @param int $gardiennage_id ID du gardiennage
 * @param string $notes Notes sur la clôture du gardiennage
 * @return array Résultat de l'opération
 */
function terminer_gardiennage($gardiennage_id, $notes = '') {
    global $pdo;
    
    try {
        // Récupérer les informations du gardiennage
        $stmt = $pdo->prepare("
            SELECT g.*, r.statut, r.client_id
            FROM gardiennage g
            JOIN reparations r ON g.reparation_id = r.id
            WHERE g.id = ? AND g.est_actif = TRUE
        ");
        $stmt->execute([$gardiennage_id]);
        $gardiennage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$gardiennage) {
            return ['success' => false, 'message' => 'Gardiennage introuvable ou déjà terminé'];
        }
        
        // Calculer le montant final
        $jours_non_factures = date_diff(new DateTime($gardiennage['date_derniere_facturation']), new DateTime())->days;
        $montant_non_facture = $jours_non_factures * $gardiennage['tarif_journalier'];
        $montant_total = $gardiennage['montant_total'] + $montant_non_facture;
        $jours_totaux = $gardiennage['jours_factures'] + $jours_non_factures;
        
        // Mettre à jour le gardiennage
        $stmt = $pdo->prepare("
            UPDATE gardiennage 
            SET est_actif = FALSE, 
                date_fin = CURRENT_DATE, 
                jours_factures = ?, 
                montant_total = ?, 
                notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$jours_totaux, $montant_total, $notes, $gardiennage_id]);
        
        // Mettre à jour le statut de la réparation (par exemple, le remettre en "terminé")
        $stmt = $pdo->prepare("UPDATE reparations SET statut = 'reparation_effectue' WHERE id = ?");
        $stmt->execute([$gardiennage['reparation_id']]);
        
        // Ajouter un log pour la réparation
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $stmt = $pdo->prepare("
            INSERT INTO reparation_logs (
                reparation_id, employe_id, action_type, statut_avant, statut_apres, details
            ) VALUES (?, ?, 'statut', 'gardiennage', 'reparation_effectue', ?)
        ");
        $details = 'Fin du gardiennage après ' . $jours_totaux . ' jours. Montant total : ' . $montant_total . '€';
        $stmt->execute([$gardiennage['reparation_id'], $user_id, $details]);
        
        return [
            'success' => true,
            'message' => 'Gardiennage terminé avec succès',
            'montant_total' => $montant_total,
            'jours_totaux' => $jours_totaux
        ];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de la clôture du gardiennage : ' . $e->getMessage()];
    }
}

/**
 * Fonction pour mettre à jour la facturation d'un gardiennage (à exécuter quotidiennement)
 * @param int $gardiennage_id ID du gardiennage
 * @return array Résultat de l'opération
 */
function mettre_a_jour_facturation_gardiennage($gardiennage_id) {
    global $pdo;
    
    try {
        // Récupérer les informations du gardiennage
        $stmt = $pdo->prepare("SELECT * FROM gardiennage WHERE id = ? AND est_actif = TRUE");
        $stmt->execute([$gardiennage_id]);
        $gardiennage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$gardiennage) {
            return ['success' => false, 'message' => 'Gardiennage introuvable ou déjà terminé'];
        }
        
        // Calculer le nombre de jours depuis la dernière facturation
        $jours_depuis_derniere_facturation = date_diff(new DateTime($gardiennage['date_derniere_facturation']), new DateTime())->days;
        
        if ($jours_depuis_derniere_facturation <= 0) {
            return ['success' => true, 'message' => 'Aucun jour à facturer', 'jours_factures' => 0];
        }
        
        // Calculer le nouveau montant
        $montant_a_ajouter = $jours_depuis_derniere_facturation * $gardiennage['tarif_journalier'];
        $nouveau_montant_total = $gardiennage['montant_total'] + $montant_a_ajouter;
        $nouveaux_jours_factures = $gardiennage['jours_factures'] + $jours_depuis_derniere_facturation;
        
        // Mettre à jour le gardiennage
        $stmt = $pdo->prepare("
            UPDATE gardiennage 
            SET date_derniere_facturation = CURRENT_DATE, 
                jours_factures = ?, 
                montant_total = ?
            WHERE id = ?
        ");
        $stmt->execute([$nouveaux_jours_factures, $nouveau_montant_total, $gardiennage_id]);
        
        return [
            'success' => true,
            'message' => 'Facturation mise à jour avec succès',
            'jours_factures' => $jours_depuis_derniere_facturation,
            'montant_facture' => $montant_a_ajouter,
            'montant_total' => $nouveau_montant_total
        ];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour de la facturation : ' . $e->getMessage()];
    }
}

/**
 * Fonction pour envoyer un rappel SMS pour le gardiennage
 * @param int $gardiennage_id ID du gardiennage
 * @return array Résultat de l'opération
 */
function envoyer_rappel_gardiennage($gardiennage_id) {
    global $pdo;
    
    try {
        // Récupérer les informations du gardiennage et de la réparation
        $stmt = $pdo->prepare("
            SELECT g.*, r.id as reparation_id, r.client_id, r.type_appareil, r.marque, r.modele,
                   c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
            FROM gardiennage g
            JOIN reparations r ON g.reparation_id = r.id
            JOIN clients c ON r.client_id = c.id
            WHERE g.id = ? AND g.est_actif = TRUE
        ");
        $stmt->execute([$gardiennage_id]);
        $gardiennage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$gardiennage) {
            return ['success' => false, 'message' => 'Gardiennage introuvable ou déjà terminé'];
        }
        
        // Mettre à jour la facturation avant d'envoyer le rappel
        $resultat_facturation = mettre_a_jour_facturation_gardiennage($gardiennage_id);
        if (!$resultat_facturation['success']) {
            return $resultat_facturation;
        }
        
        // Récupérer le modèle de SMS pour le gardiennage
        $stmt = $pdo->prepare("
            SELECT id, contenu 
            FROM sms_templates 
            WHERE nom = 'Rappel gardiennage' AND est_actif = 1
            LIMIT 1
        ");
        $stmt->execute();
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            // Utiliser un template par défaut si aucun n'existe
            $template = [
                'id' => 0, 
                'contenu' => "Bonjour [CLIENT_PRENOM] [CLIENT_NOM],\n\nVotre [APPAREIL_MARQUE] [APPAREIL_MODELE] est en gardiennage chez nous depuis [JOURS_GARDIENNAGE] jours.\nLe montant actuel du gardiennage s'élève à [MONTANT_GARDIENNAGE] €.\n\nMerci de venir récupérer votre appareil dès que possible.\n\nL'équipe de réparation"
            ];
        }
        
        // Formater le numéro de téléphone
        $telephone = $gardiennage['client_telephone'];
        if (!preg_match('/^\+[0-9]{10,15}$/', $telephone)) {
            // Conversion basique des numéros français
            if (preg_match('/^0[6-7][0-9]{8}$/', $telephone)) {
                $telephone = '+33' . substr($telephone, 1);
            }
        }
        
        if (empty($telephone)) {
            return ['success' => false, 'message' => 'Numéro de téléphone client invalide'];
        }
        
        // Calculer les jours de gardiennage et le montant total
        $jours_gardiennage = date_diff(new DateTime($gardiennage['date_debut']), new DateTime())->days;
        $montant_gardiennage = $resultat_facturation['montant_total'];
        
        // Préparer le contenu du SMS en remplaçant les variables
        $message = $template['contenu'];
        $replacements = [
            '[CLIENT_NOM]' => $gardiennage['client_nom'],
            '[CLIENT_PRENOM]' => $gardiennage['client_prenom'],
            '[APPAREIL_MARQUE]' => $gardiennage['marque'],
            '[APPAREIL_MODELE]' => $gardiennage['modele'],
            '[JOURS_GARDIENNAGE]' => $jours_gardiennage,
            '[MONTANT_GARDIENNAGE]' => number_format($montant_gardiennage, 2, ',', ' '),
            '[DATE_DEBUT]' => format_date($gardiennage['date_debut']),
            '[DATE_ACTUELLE]' => format_date(date('Y-m-d'))
        ];
        
        foreach ($replacements as $var => $value) {
            $message = str_replace($var, $value, $message);
        }
        
        // Envoyer le SMS
        if (function_exists('send_sms')) {
            $sms_result = send_sms($telephone, $message);
            
            if ($sms_result['success']) {
                // Enregistrer l'envoi du SMS dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO gardiennage_notifications (
                        gardiennage_id, type_notification, statut, message
                    ) VALUES (?, 'sms', 'envoyé', ?)
                ");
                $stmt->execute([$gardiennage_id, $message]);
                
                // Mettre à jour la date de dernière notification
                $stmt = $pdo->prepare("UPDATE gardiennage SET derniere_notification = CURRENT_DATE WHERE id = ?");
                $stmt->execute([$gardiennage_id]);
                
                return [
                    'success' => true,
                    'message' => 'Rappel envoyé avec succès',
                    'sms_id' => $pdo->lastInsertId()
                ];
            } else {
                // Enregistrer l'échec dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO gardiennage_notifications (
                        gardiennage_id, type_notification, statut, message
                    ) VALUES (?, 'sms', 'échec', ?)
                ");
                $stmt->execute([$gardiennage_id, $message]);
                
                return ['success' => false, 'message' => 'Erreur lors de l\'envoi du SMS : ' . $sms_result['message']];
            }
        } else {
            return ['success' => false, 'message' => 'La fonction d\'envoi SMS n\'est pas disponible'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de l\'envoi du rappel : ' . $e->getMessage()];
    }
}

/**
 * Fonction pour créer un job cron qui met à jour tous les gardiennages actifs
 * Cette fonction doit être exécutée quotidiennement
 * @return array Résultat de l'opération
 */
function mettre_a_jour_tous_gardiennages() {
    global $pdo;
    
    try {
        // Récupérer tous les gardiennages actifs
        $stmt = $pdo->query("SELECT id FROM gardiennage WHERE est_actif = TRUE");
        $gardiennages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultats = [];
        $total_mis_a_jour = 0;
        
        foreach ($gardiennages as $gardiennage) {
            $resultat = mettre_a_jour_facturation_gardiennage($gardiennage['id']);
            $resultats[] = $resultat;
            
            if ($resultat['success'] && isset($resultat['jours_factures']) && $resultat['jours_factures'] > 0) {
                $total_mis_a_jour++;
            }
        }
        
        return [
            'success' => true,
            'message' => $total_mis_a_jour . ' gardiennages mis à jour sur ' . count($gardiennages),
            'resultats' => $resultats
        ];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour des gardiennages : ' . $e->getMessage()];
    }
}

/**
 * Calcule le montant du gardiennage en fonction du nombre de jours et des tarifs configurés
 * 
 * @param int $jours_totaux Nombre total de jours de gardiennage
 * @param array $parametres Paramètres de tarification (facultatif, utilise les valeurs par défaut si non fourni)
 * @return float Montant total du gardiennage
 */
function calculer_montant_gardiennage($jours_totaux, $parametres = null) {
    global $pdo;
    
    // Si les paramètres ne sont pas fournis, les récupérer depuis la base de données
    if ($parametres === null) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM parametres_gardiennage WHERE id = 1");
            $stmt->execute();
            $parametres = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Valeurs par défaut si les paramètres n'existent pas encore
            if (!$parametres) {
                $parametres = [
                    'tarif_premiere_semaine' => 5,
                    'tarif_intermediaire' => 3,
                    'tarif_longue_duree' => 1
                ];
            }
        } catch (PDOException $e) {
            // Si la table n'existe pas encore, utiliser les valeurs par défaut
            $parametres = [
                'tarif_premiere_semaine' => 5,
                'tarif_intermediaire' => 3,
                'tarif_longue_duree' => 1
            ];
        }
    }
    
    // Calcul des jours pour chaque période tarifaire
    $jours_premiere_semaine = min(7, $jours_totaux);
    $jours_intermediaire = min(23, max(0, $jours_totaux - 7));
    $jours_longue_duree = max(0, $jours_totaux - 30);
    
    // Calcul du montant pour chaque période
    $montant_premiere_semaine = $jours_premiere_semaine * $parametres['tarif_premiere_semaine'];
    $montant_intermediaire = $jours_intermediaire * $parametres['tarif_intermediaire'];
    $montant_longue_duree = $jours_longue_duree * $parametres['tarif_longue_duree'];
    
    // Montant total
    $montant_total = $montant_premiere_semaine + $montant_intermediaire + $montant_longue_duree;
    
    return $montant_total;
}

// Fonction pour vérifier et restaurer une session à partir d'un token
function check_remember_token() {
    global $pdo;
    
    // Si l'utilisateur est déjà connecté, ne rien faire
    if (isset($_SESSION['user_id'])) {
        return;
    }
    
    // Vérifier si un cookie de session persistante existe
    if (isset($_COOKIE['mdgeek_remember'])) {
        $token = $_COOKIE['mdgeek_remember'];
        
        try {
            // Rechercher le token dans la base de données
            $stmt = $pdo->prepare('SELECT u.* FROM users u 
                                  JOIN user_sessions s ON u.id = s.user_id 
                                  WHERE s.token = ? AND s.expiry > NOW()');
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Restaurer la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Régénérer le token pour plus de sécurité
                $new_token = bin2hex(random_bytes(32));
                $expiry = time() + 259200; // 3 jours
                
                // Mettre à jour le token dans la base de données
                $stmt = $pdo->prepare('UPDATE user_sessions SET token = ?, expiry = ? WHERE token = ?');
                $stmt->execute([$new_token, date('Y-m-d H:i:s', $expiry), $token]);
                
                // Mettre à jour le cookie
                setcookie('mdgeek_remember', $new_token, $expiry, '/', '', isset($_SERVER['HTTPS']), true);
                
                // Enregistrer cette reconnexion automatique dans les logs
                error_log("Auto-login réussi pour l'utilisateur ID: " . $user['id']);
                
                return true;
            } else {
                // Token invalide ou expiré, supprimer le cookie
                setcookie('mdgeek_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                error_log("Tentative d'auto-login avec un token invalide");
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du token de session: " . $e->getMessage());
        }
    }
    
    return false;
}

// Fonction pour nettoyer les anciennes sessions
function cleanup_sessions() {
    global $pdo;
    
    try {
        // Supprimer les sessions expirées
        $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE expiry < NOW()');
        $stmt->execute();
        
        // 1% de chance de nettoyer les sessions orphelines (utilisateurs supprimés)
        if (rand(1, 100) == 1) {
            $stmt = $pdo->prepare('DELETE s FROM user_sessions s LEFT JOIN users u ON s.user_id = u.id WHERE u.id IS NULL');
            $stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("Erreur lors du nettoyage des sessions: " . $e->getMessage());
    }
}

// Fonction pour déconnecter l'utilisateur de toutes les sessions
function logout_from_all_sessions($user_id) {
    global $pdo;
    
    try {
        // Supprimer toutes les sessions de l'utilisateur
        $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE user_id = ?');
        $stmt->execute([$user_id]);
        
        // Supprimer le cookie de session persistante
        setcookie('mdgeek_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        setcookie('pwa_mode', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        
        // Détruire la session
        session_destroy();
    } catch (PDOException $e) {
        error_log("Erreur lors de la déconnexion de toutes les sessions: " . $e->getMessage());
    }
}

// Vérifier si nous sommes en mode PWA
function is_pwa_mode_client() {
    // Vérifier si l'utilisateur a accédé au site en mode standalone (PWA)
    $script = "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Détecter si l'application est en mode standalone (ajoutée à l'écran d'accueil)
        if (window.matchMedia('(display-mode: standalone)').matches || 
            window.navigator.standalone || 
            document.referrer.includes('android-app://')) {
            
            // Définir un cookie pour indiquer que c'est une PWA
            document.cookie = 'pwa_mode=true; path=/; max-age=2592000; SameSite=Lax';
            
            // Ajout d'une classe spécifique au body
            document.body.classList.add('pwa-installed');
        }
    });
    </script>
    ";
    
    return $script;
}

/**
 * Fonction pour compter les réparations selon des statuts spécifiques
 * @return int Nombre de réparations avec les statuts spécifiés
 */
function count_active_reparations() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations 
            WHERE statut IN (
                'nouveau_diagnostique', 
                'nouvelle_intervention', 
                'nouvelle_commande',
                'en_cours_diagnostique',
                'en_cours_intervention',
                'en_attente_accord_client',
                'en_attente_livraison',
                'en_attente_responsable'
            )
        ");
        return $stmt->fetch()['total'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des réparations actives : " . $e->getMessage());
        return 0;
    }
}

/**
 * Compte le nombre de réparations récentes avec les statuts spécifiés
 * @return int Nombre de réparations récentes
 */
function count_recent_reparations() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations 
            WHERE statut IN ('nouveau_diagnostique', 'nouvelle_intervention', 'nouvelle_commande')
        ");
        return $stmt->fetch()['total'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des réparations récentes : " . $e->getMessage());
        return 0;
    }
}

/**
 * Génère un lien d'acceptation de devis pour une réparation
 * 
 * @param int $reparation_id ID de la réparation
 * @param string $client_email Email du client (non utilisé mais conservé pour compatibilité)
 * @return string URL complète pour l'acceptation du devis
 */
function genererLienAcceptationDevis($reparation_id, $client_email) {
    // Construire l'URL complète sans token
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    return $protocol . '://' . $host . '/pages/accepter_devis.php?id=' . $reparation_id;
}

/**
 * Retourne l'URL du site
 * 
 * @return string URL du site
 */
function get_site_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    return $protocol . '://' . $host;
}

/**
 * Vérifie si la table sms_logs existe et la crée si nécessaire
 */
function check_sms_logs_table() {
    global $pdo;
    
    try {
        // Vérifier si la table existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'sms_logs'");
        if ($stmt->rowCount() == 0) {
            // La table n'existe pas, la créer
            $sql = "CREATE TABLE `sms_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `recipient` varchar(20) NOT NULL,
                `message` text NOT NULL,
                `status` int(11) DEFAULT NULL,
                `response` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $pdo->exec($sql);
            error_log("Table sms_logs créée avec succès");
            return true;
        }
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification/création de la table sms_logs: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère toutes les tâches en cours et à faire, qu'elles soient assignées à n'importe quel utilisateur ou non assignées
 * @param int $limit Nombre maximum de tâches à récupérer
 * @return array Tableau des tâches en cours
 */
function get_toutes_taches_en_cours($limit = 10) {
    global $pdo;
    try {
        // Vérifier si la connexion PDO est établie
        if (!isset($pdo) || !$pdo) {
            error_log("Erreur: La connexion à la base de données n'est pas établie");
            return [];
        }
        
        $query = "SELECT t.*, 
                       u.full_name as employe_nom,
                       c.full_name as createur_nom
                FROM taches t 
                LEFT JOIN users u ON t.employe_id = u.id 
                LEFT JOIN users c ON t.created_by = c.id 
                WHERE t.statut IN ('en_cours', 'a_faire')
                ORDER BY t.date_limite ASC, t.priorite DESC";
        
        if ($limit > 0) {
            $query .= " LIMIT ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$limit]);
        } else {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
        }
        
        $taches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les données pour l'affichage
        foreach ($taches as &$tache) {
            // Convertir priorité en urgence pour la compatibilité avec l'interface
            $tache['urgence'] = $tache['priorite'];
            
            // Ajouter une valeur de progression basée sur le statut
            if ($tache['statut'] == 'a_faire') {
                $tache['progression'] = 0;
            } else {
                // par défaut 50% pour les tâches en cours
                $tache['progression'] = 50;
            }
            
            // Renommer date_limite en date_echeance pour la compatibilité avec l'interface
            // S'assurer que date_echeance est toujours définie, même si date_limite est null
            $tache['date_echeance'] = isset($tache['date_limite']) && !empty($tache['date_limite']) ? $tache['date_limite'] : null;
        }
        
        return $taches;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tâches en cours : " . $e->getMessage());
        return [];
    }
}

/**
 * Retourne la classe CSS appropriée en fonction de l'urgence de la tâche
 * 
 * @param string $urgence Le niveau d'urgence de la tâche
 * @return string La classe CSS correspondante
 */
function get_urgence_class($urgence) {
    switch (strtolower($urgence)) {
        case 'basse':
            return 'bg-success';
        case 'moyenne':
            return 'bg-warning';
        case 'haute':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}