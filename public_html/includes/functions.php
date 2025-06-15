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
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->query("SELECT statut, COUNT(*) as count FROM reparations GROUP BY statut");
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
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM clients");
        return $stmt->fetch()['total'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des clients : " . $e->getMessage());
        return 0;
    }
}

// Fonction pour obtenir les réparations récentes
function get_recent_reparations($limit = 5) {
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->prepare("
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
    return date('d/m', strtotime($date));
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
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT s.*, c.nom as categorie_nom, c.code as categorie_code, c.couleur
            FROM statuts s
            JOIN statut_categories c ON s.categorie_id = c.id
            WHERE s.est_actif = TRUE
            ORDER BY c.ordre, s.ordre
        ";
        $stmt = $shop_pdo->query($query);
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
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT s.*, c.nom as categorie_nom, c.code as categorie_code, c.couleur
            FROM statuts s
            JOIN statut_categories c ON s.categorie_id = c.id
            WHERE s.code = ?
        ";
        $stmt = $shop_pdo->prepare($query);
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
 * Récupère les tâches en cours d'un employé ou toutes les tâches en cours si l'ID employé n'est pas spécifié
 * @param int $limit Nombre maximum de tâches à récupérer
 * @return array Tableau des tâches en cours
 */
function get_taches_en_cours($limit = 10) {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer l'ID de l'utilisateur connecté
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    try {
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
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$user_id, $limit]);
        } else {
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$user_id]);
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
    $shop_pdo = getShopDBConnection();
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
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$limit]);
        } else {
            $stmt = $shop_pdo->query($query);
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
 * Fonction pour compter les réparations par catégorie de statut
 * @return array Tableau associatif avec les comptages par catégorie
 */
function get_reparations_count_by_status_categorie() {
    $shop_pdo = getShopDBConnection();
    try {
        // Réparations en attente (catégorie 3)
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE statut_categorie = ?");
        $stmt->execute([3]);
        $en_attente = $stmt->fetch()['count'];
        
        // Réparations en cours (catégorie 2)
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE statut_categorie = ?");
        $stmt->execute([2]);
        $en_cours = $stmt->fetch()['count'];
        
        // Réparations nouvelles (catégorie 1)
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE statut_categorie = ?");
        $stmt->execute([1]);
        $nouvelles = $stmt->fetch()['count'];
        
        // Réparations terminées (catégorie 4)
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE statut_categorie = ?");
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
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM taches WHERE statut IN ('a_faire', 'en_cours')");
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des tâches récentes: " . $e->getMessage());
        return 0;
    }
}

/**
 * Fonction pour compter les réparations selon des statuts spécifiques
 * @return int Nombre de réparations avec les statuts spécifiés
 */
function count_active_reparations() {
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->query("
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
    $shop_pdo = getShopDBConnection();
    try {
        $stmt = $shop_pdo->query("
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
    $shop_pdo = getShopDBConnection();
    
    try {
        // Vérifier si la table existe
        $stmt = $shop_pdo->query("SHOW TABLES LIKE 'sms_logs'");
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
            
            $shop_pdo->exec($sql);
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
    $shop_pdo = getShopDBConnection();
    try {
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
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$limit]);
        } else {
            $stmt = $shop_pdo->prepare($query);
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

/**
 * Fonctions utilitaires globales pour GeekBoard
 * Fonctions utilisées dans plusieurs parties de l'application
 */

// S'assurer que les fonctions ne sont définies qu'une seule fois
if (!function_exists('getSupplierColor')) {
    /**
     * Obtenir la couleur associée à un fournisseur
     * @param int $fournisseur_id ID du fournisseur
     * @return string Couleur hexadécimale
     */
    function getSupplierColor($fournisseur_id) {
        // Palette de 10 couleurs distinctes
        $colors = [
            '#4e73df', // Bleu royal
            '#36b9cc', // Cyan
            '#1cc88a', // Vert
            '#f6c23e', // Jaune
            '#e74a3b', // Rouge
            '#8a6d3b', // Brun
            '#6610f2', // Violet foncé
            '#20c997', // Turquoise
            '#fd7e14', // Orange
            '#6f42c1'  // Violet
        ];
        
        // Utiliser le modulo pour obtenir un index entre 0 et 9
        $index = $fournisseur_id % 10;
        
        return $colors[$index];
    }
}

if (!function_exists('getDateColor')) {
    /**
     * Obtenir la couleur associée à un jour de la semaine
     * @param int $day_of_week Jour de la semaine (1-7)
     * @return string Couleur hexadécimale
     */
    function getDateColor($day_of_week) {
        // Palette de couleurs pour les jours de la semaine
        $colors = [
            1 => '#cfe2ff', // Lundi - Bleu clair
            2 => '#d1e7dd', // Mardi - Vert clair
            3 => '#f8d7da', // Mercredi - Rose clair
            4 => '#fff3cd', // Jeudi - Jaune clair
            5 => '#e7f5ff', // Vendredi - Bleu très clair
            6 => '#e2e3e5', // Samedi - Gris clair
            7 => '#e0cffc'  // Dimanche - Violet clair
        ];
        
        return $colors[$day_of_week] ?? '#f8f9fa'; // Couleur par défaut si jour invalide
    }
}

if (!function_exists('getDateColorDark')) {
    /**
     * Obtenir la couleur associée à un jour de la semaine (mode sombre)
     * @param int $day_of_week Jour de la semaine (1-7)
     * @return string Couleur hexadécimale
     */
    function getDateColorDark($day_of_week) {
        // Palette de couleurs plus foncées pour le mode nuit
        $colors = [
            1 => '#1e3a8a', // Lundi - Bleu foncé
            2 => '#166534', // Mardi - Vert foncé
            3 => '#991b1b', // Mercredi - Rouge foncé
            4 => '#a16207', // Jeudi - Jaune foncé
            5 => '#1e40af', // Vendredi - Bleu foncé
            6 => '#374151', // Samedi - Gris foncé
            7 => '#6b21a8'  // Dimanche - Violet foncé
        ];
        
        return $colors[$day_of_week] ?? '#374151'; // Couleur par défaut si jour invalide
    }
}

if (!function_exists('get_status_label')) {
    /**
     * Obtenir le libellé d'un statut
     * @param string $statut Code du statut
     * @return string Libellé traduit
     */
    function get_status_label($statut) {
        switch($statut) {
            case 'en_attente': return 'En attente';
            case 'commande': return 'Commandé';
            case 'recue': return 'Reçu';
            case 'annulee': return 'Annulé';
            case 'urgent': return 'URGENT';
            case 'utilise': return 'Utilisé';
            case 'a_retourner': return 'Retour';
            default: return $statut;
        }
    }
}

if (!function_exists('formatUrgence')) {
    /**
     * Formater le niveau d'urgence avec badge HTML
     * @param string $urgence Niveau d'urgence
     * @return string HTML du badge
     */
    function formatUrgence($urgence) {
        $classes = [
            'normal' => 'secondary',
            'urgent' => 'warning',
            'tres_urgent' => 'danger'
        ];
        
        $labels = [
            'normal' => 'Normal',
            'urgent' => 'Urgent',
            'tres_urgent' => 'Très urgent'
        ];
        
        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $classes[$urgence] ?? 'secondary',
            $labels[$urgence] ?? $urgence
        );
    }
}

if (!function_exists('get_status_class')) {
    /**
     * Obtenir la classe CSS pour un statut (version commandes_pieces)
     * @param string $statut Code du statut
     * @return string Classes CSS Bootstrap complètes
     */
    function get_status_class($statut) {
        switch($statut) {
            case 'en_attente': return 'bg-warning text-dark';
            case 'commande': return 'bg-info text-white';
            case 'recue': return 'bg-success text-white';
            case 'annulee': return 'bg-danger text-white';
            case 'urgent': return 'bg-danger text-white';
            case 'utilise': return 'bg-primary text-white';
            case 'a_retourner': return 'bg-secondary text-white';
            default: return 'bg-secondary text-white';
        }
    }
}

if (!function_exists('get_status_color')) {
    /**
     * Obtenir uniquement la couleur d'un statut
     * @param string $statut Code du statut
     * @return string Nom de la couleur Bootstrap
     */
    function get_status_color($statut) {
        switch($statut) {
            case 'en_attente': return 'warning';
            case 'commande': return 'info';
            case 'recue': return 'success'; 
            case 'annulee': return 'danger';
            case 'urgent': return 'danger';
            case 'utilise': return 'primary';
            case 'a_retourner': return 'secondary';
            default: return 'secondary';
        }
    }
}

if (!function_exists('dbDebugLog')) {
    /**
     * Logger les messages de debug avec contexte de magasin
     * @param string $message Message à logger
     */
    function dbDebugLog($message) {
        $timestamp = date('Y-m-d H:i:s');
        $shop_id = $_SESSION['shop_id'] ?? 'unknown';
        error_log("[{$timestamp}] [Shop:{$shop_id}] {$message}");
    }
}

if (!function_exists('generateSecureToken')) {
    /**
     * Générer un token sécurisé
     * @param int $length Longueur du token
     * @return string Token hexadécimal
     */
    function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('formatFileSize')) {
    /**
     * Formater une taille de fichier en bytes vers une unité lisible
     * @param int $bytes Taille en bytes
     * @return string Taille formatée
     */
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}

if (!function_exists('sanitizeFilename')) {
    /**
     * Nettoyer un nom de fichier
     * @param string $filename Nom de fichier à nettoyer
     * @return string Nom de fichier nettoyé
     */
    function sanitizeFilename($filename) {
        // Remplacer les caractères spéciaux par des underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        // Éviter les doubles underscores
        $filename = preg_replace('/_+/', '_', $filename);
        // Supprimer les underscores en début et fin
        return trim($filename, '_');
    }
}