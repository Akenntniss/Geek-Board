<?php
/**
 * Fichier de fonctions pour les statistiques
 * Crée le: 2025-04-08
 * Mis à jour: Application du système multi-magasins
 */

/**
 * Récupère les statistiques globales du système
 * @return array Tableau des statistiques globales
 */
function get_statistiques_generales() {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Nombre total de réparations
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations");
        $total_reparations = $stmt->fetch()['total'];
        
        // Nombre de réparations actives
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations 
            WHERE archive = 'NON' 
            AND statut NOT IN (SELECT code FROM statuts WHERE categorie_id = 4)
        ");
        $reparations_actives = $stmt->fetch()['total'];
        
        // Nombre total de clients
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM clients");
        $total_clients = $stmt->fetch()['total'];
        
        // Nombre total d'employés
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM users");
        $total_employes = $stmt->fetch()['total'];
        
        // Nombre de commandes en cours
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM commandes_pieces 
            WHERE statut NOT IN ('livré', 'annulé')
        ");
        $commandes_en_cours = $stmt->fetch()['total'];
        
        // Réparations en gardiennage
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM gardiennage 
            WHERE est_actif = TRUE
        ");
        $gardiennage_actif = $stmt->fetch()['total'];
        
        // Chiffre d'affaires total des réparations
        $stmt = $shop_pdo->query("
            SELECT COALESCE(SUM(prix), 0) as ca_total 
            FROM reparations 
            WHERE statut IN (SELECT code FROM statuts WHERE categorie_id = 4)
        ");
        $ca_total = $stmt->fetch()['ca_total'];
        
        return [
            'total_reparations' => $total_reparations,
            'reparations_actives' => $reparations_actives,
            'total_clients' => $total_clients,
            'total_employes' => $total_employes,
            'commandes_en_cours' => $commandes_en_cours,
            'gardiennage_actif' => $gardiennage_actif,
            'ca_total' => $ca_total
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques générales: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques des réparations par statut
 * @return array Tableau des statistiques de réparation par statut
 */
function get_reparations_par_statut() {
    $shop_pdo = getShopDBConnection();
    
    try {
        $query = "
            SELECT s.nom as statut_nom, s.code as statut_code, COUNT(r.id) as nombre, 
                   c.couleur, c.nom as categorie_nom
            FROM statuts s
            LEFT JOIN reparations r ON r.statut = s.code AND r.archive = 'NON'
            JOIN statut_categories c ON s.categorie_id = c.id
            WHERE s.est_actif = TRUE
            GROUP BY s.id, c.id
            ORDER BY c.ordre, s.ordre
        ";
        
        $stmt = $shop_pdo->query($query);
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser par catégorie
        $par_categorie = [];
        foreach ($resultats as $row) {
            $categorie = $row['categorie_nom'];
            if (!isset($par_categorie[$categorie])) {
                $par_categorie[$categorie] = [
                    'couleur' => $row['couleur'],
                    'statuts' => [],
                    'total' => 0
                ];
            }
            
            $par_categorie[$categorie]['statuts'][] = [
                'nom' => $row['statut_nom'],
                'code' => $row['statut_code'],
                'nombre' => $row['nombre']
            ];
            
            $par_categorie[$categorie]['total'] += $row['nombre'];
        }
        
        return $par_categorie;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques par statut: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques par statut: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques des réparations par type d'appareil
 * @return array Tableau des statistiques par type d'appareil
 */
function get_reparations_par_type_appareil() {
    $shop_pdo = getShopDBConnection();
    
    try {
        $query = "
            SELECT type_appareil, COUNT(*) as nombre
            FROM reparations
            WHERE archive = 'NON'
            GROUP BY type_appareil
            ORDER BY nombre DESC
        ";
        
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques par type d'appareil: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques par type d'appareil: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques des réparations par marque
 * @param string $type_appareil Type d'appareil (optionnel)
 * @return array Tableau des statistiques par marque
 */
function get_reparations_par_marque($type_appareil = null) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $query = "
            SELECT marque, COUNT(*) as nombre
            FROM reparations
            WHERE archive = 'NON'
        ";
        
        $params = [];
        
        if ($type_appareil) {
            $query .= " AND type_appareil = ?";
            $params[] = $type_appareil;
        }
        
        $query .= " GROUP BY marque ORDER BY nombre DESC LIMIT 10";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques par marque: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques par marque: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques des réparations par période
 * @param string $periode Période ('jour', 'semaine', 'mois', 'annee')
 * @param int $nombre_periodes Nombre de périodes à récupérer
 * @return array Tableau des statistiques par période
 */
function get_reparations_par_periode($periode = 'mois', $nombre_periodes = 12) {
    $shop_pdo = getShopDBConnection();
    
    try {
        switch ($periode) {
            case 'jour':
                $format_date = "%Y-%m-%d";
                $interval = "DAY";
                break;
            case 'semaine':
                $format_date = "%x-W%v"; // Format année-semaine
                $interval = "WEEK";
                break;
            case 'mois':
                $format_date = "%Y-%m";
                $interval = "MONTH";
                break;
            case 'annee':
                $format_date = "%Y";
                $interval = "YEAR";
                break;
            default:
                $format_date = "%Y-%m";
                $interval = "MONTH";
        }
        
        // Récupération des réparations par période
        $query = "
            SELECT 
                DATE_FORMAT(date_reception, '$format_date') as periode,
                COUNT(*) as nouvelles,
                COUNT(CASE WHEN statut IN (SELECT code FROM statuts WHERE categorie_id = 4) THEN 1 END) as terminees
            FROM reparations
            WHERE date_reception >= DATE_SUB(CURRENT_DATE, INTERVAL $nombre_periodes $interval)
            GROUP BY periode
            ORDER BY periode
        ";
        
        $stmt = $shop_pdo->query($query);
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $resultats;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques par période: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques par période: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère le temps moyen de réparation par type d'appareil
 * @return array Temps moyen de réparation par type d'appareil
 */
function get_temps_moyen_reparation() {
    $shop_pdo = getShopDBConnection();
    
    try {
        $query = "
            SELECT 
                type_appareil,
                AVG(DATEDIFF(date_modification, date_reception)) as temps_moyen_jours,
                COUNT(*) as nombre_reparations
            FROM reparations
            WHERE 
                statut IN (SELECT code FROM statuts WHERE categorie_id = 4)
                AND date_reception IS NOT NULL
                AND date_modification IS NOT NULL
            GROUP BY type_appareil
            HAVING COUNT(*) > 5
            ORDER BY temps_moyen_jours ASC
        ";
        
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du temps moyen de réparation: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération du temps moyen de réparation: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère le chiffre d'affaires par période
 * @param string $periode Période ('jour', 'semaine', 'mois', 'annee')
 * @param int $nombre_periodes Nombre de périodes à récupérer
 * @return array Chiffre d'affaires par période
 */
function get_chiffre_affaires_par_periode($periode = 'mois', $nombre_periodes = 12) {
    $shop_pdo = getShopDBConnection();
    
    try {
        switch ($periode) {
            case 'jour':
                $format_date = "%Y-%m-%d";
                $interval = "DAY";
                break;
            case 'semaine':
                $format_date = "%x-W%v"; // Format année-semaine
                $interval = "WEEK";
                break;
            case 'mois':
                $format_date = "%Y-%m";
                $interval = "MONTH";
                break;
            case 'annee':
                $format_date = "%Y";
                $interval = "YEAR";
                break;
            default:
                $format_date = "%Y-%m";
                $interval = "MONTH";
        }
        
        // Récupération du chiffre d'affaires par période
        $query = "
            SELECT 
                DATE_FORMAT(date_modification, '$format_date') as periode,
                SUM(prix) as chiffre_affaires,
                COUNT(*) as nombre_reparations
            FROM reparations
            WHERE 
                date_modification >= DATE_SUB(CURRENT_DATE, INTERVAL $nombre_periodes $interval)
                AND statut IN (SELECT code FROM statuts WHERE categorie_id = 4)
                AND prix > 0
            GROUP BY periode
            ORDER BY periode
        ";
        
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du chiffre d'affaires: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération du chiffre d'affaires: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques des employés (performance, charge de travail)
 * @return array Statistiques des employés
 */
function get_statistiques_employes() {
    $shop_pdo = getShopDBConnection();
    
    try {
        $query = "
            SELECT 
                u.id,
                u.full_name as nom,
                COUNT(r.id) as total_reparations,
                COUNT(CASE WHEN r.statut IN (SELECT code FROM statuts WHERE categorie_id = 4) THEN 1 END) as terminees,
                COUNT(CASE WHEN r.statut NOT IN (SELECT code FROM statuts WHERE categorie_id = 4) THEN 1 END) as en_cours,
                AVG(DATEDIFF(r.date_modification, r.date_reception)) as temps_moyen_jours,
                COALESCE(SUM(CASE WHEN r.statut IN (SELECT code FROM statuts WHERE categorie_id = 4) THEN r.prix ELSE 0 END), 0) as chiffre_affaires
            FROM users u
            LEFT JOIN reparations r ON r.employe_id = u.id
            WHERE u.role = 'technicien'
            GROUP BY u.id
            ORDER BY total_reparations DESC
        ";
        
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques employés: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques employés: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques de stock (produits les plus utilisés, alertes, etc.)
 * @return array Statistiques de stock
 */
function get_statistiques_stock() {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Total des produits
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM stock");
        $total_produits = $stmt->fetch()['total'];
        
        // Produits en alerte (quantité faible)
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM stock WHERE quantity <= 5 AND quantity > 0");
        $produits_en_alerte = $stmt->fetch()['total'];
        
        // Produits épuisés
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM stock WHERE quantity = 0");
        $produits_epuises = $stmt->fetch()['total'];
        
        // Valeur totale du stock
        $stmt = $shop_pdo->query("SELECT COALESCE(SUM(quantity * price), 0) as valeur FROM stock");
        $valeur_stock = $stmt->fetch()['valeur'];
        
        // Produits les plus utilisés
        $stmt = $shop_pdo->query("
            SELECT s.name, s.category, s.quantity, s.price, 
                COUNT(m.id) as nombre_mouvements,
                SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.quantite ELSE 0 END) as total_sorties
            FROM stock s
            LEFT JOIN mouvements_stock m ON m.produit_id = s.id
            GROUP BY s.id
            ORDER BY total_sorties DESC
            LIMIT 10
        ");
        $produits_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_produits' => $total_produits,
            'produits_en_alerte' => $produits_en_alerte,
            'produits_epuises' => $produits_epuises,
            'valeur_stock' => $valeur_stock,
            'produits_populaires' => $produits_populaires
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques de stock: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques de stock: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques clients (fidélité, acquisition, etc.)
 * @param int $nb_mois Nombre de mois pour les nouveaux clients
 * @return array Statistiques clients
 */
function get_statistiques_clients($nb_mois = 6) {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Total des clients
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM clients");
        $total_clients = $stmt->fetch()['total'];
        
        // Nouveaux clients par mois sur les derniers mois
        $query = "
            SELECT 
                DATE_FORMAT(date_creation, '%Y-%m') as mois,
                COUNT(*) as nouveaux_clients
            FROM clients
            WHERE date_creation >= DATE_SUB(CURRENT_DATE, INTERVAL $nb_mois MONTH)
            GROUP BY mois
            ORDER BY mois
        ";
        $stmt = $shop_pdo->query($query);
        $nouveaux_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clients avec plusieurs réparations (fidèles)
        $query = "
            SELECT 
                c.id, 
                c.nom,
                c.prenom,
                c.telephone,
                COUNT(r.id) as nombre_reparations,
                MAX(r.date_reception) as derniere_reparation,
                SUM(r.prix) as montant_total
            FROM clients c
            JOIN reparations r ON r.client_id = c.id
            GROUP BY c.id
            HAVING COUNT(r.id) > 1
            ORDER BY nombre_reparations DESC
            LIMIT 10
        ";
        $stmt = $shop_pdo->query($query);
        $clients_fideles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Valeur moyenne d'un client
        $query = "
            SELECT 
                AVG(total_client) as panier_moyen
            FROM (
                SELECT 
                    c.id,
                    SUM(r.prix) as total_client
                FROM clients c
                JOIN reparations r ON r.client_id = c.id
                WHERE r.statut IN (SELECT code FROM statuts WHERE categorie_id = 4)
                GROUP BY c.id
            ) as client_values
        ";
        $stmt = $shop_pdo->query($query);
        $panier_moyen = $stmt->fetch()['panier_moyen'];
        
        return [
            'total_clients' => $total_clients,
            'nouveaux_clients' => $nouveaux_clients,
            'clients_fideles' => $clients_fideles,
            'panier_moyen' => $panier_moyen
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques clients: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques clients: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques de tâches
 * @return array Statistiques des tâches
 */
function get_statistiques_taches() {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Total des tâches
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM taches");
        $total_taches = $stmt->fetch()['total'];
        
        // Tâches par statut
        $query = "
            SELECT 
                statut,
                COUNT(*) as nombre
            FROM taches
            GROUP BY statut
        ";
        $stmt = $shop_pdo->query($query);
        $taches_par_statut = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tâches par priorité
        $query = "
            SELECT 
                priorite,
                COUNT(*) as nombre
            FROM taches
            GROUP BY priorite
        ";
        $stmt = $shop_pdo->query($query);
        $taches_par_priorite = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tâches par employé
        $query = "
            SELECT 
                u.full_name as employe,
                COUNT(t.id) as nombre_taches,
                COUNT(CASE WHEN t.statut = 'termine' THEN 1 END) as terminees,
                COUNT(CASE WHEN t.statut != 'termine' THEN 1 END) as en_cours
            FROM users u
            LEFT JOIN taches t ON t.employe_id = u.id
            GROUP BY u.id
            ORDER BY nombre_taches DESC
        ";
        $stmt = $shop_pdo->query($query);
        $taches_par_employe = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_taches' => $total_taches,
            'taches_par_statut' => $taches_par_statut,
            'taches_par_priorite' => $taches_par_priorite,
            'taches_par_employe' => $taches_par_employe
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques des tâches: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques des tâches: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère l'historique des connexions utilisateurs
 * @param int $limit Limite de résultats
 * @return array Historique des connexions
 */
function get_historique_connexions($limit = 100) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $query = "
            SELECT 
                u.username,
                u.full_name,
                s.date_debut,
                s.date_derniere_activite,
                s.ip_address,
                s.user_agent
            FROM user_sessions s
            JOIN users u ON s.user_id = u.id
            ORDER BY s.date_debut DESC
            LIMIT ?
        ";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'historique des connexions: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération de l'historique des connexions: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques des SMS envoyés
 * @param int $nb_mois Nombre de mois d'historique
 * @return array Statistiques des SMS
 */
function get_statistiques_sms($nb_mois = 6) {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Total des SMS envoyés
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM sms_logs");
        $total_sms = $stmt->fetch()['total'];
        
        // SMS par statut
        $query = "
            SELECT 
                status,
                COUNT(*) as nombre
            FROM sms_logs
            GROUP BY status
        ";
        $stmt = $shop_pdo->query($query);
        $sms_par_statut = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // SMS par mois
        $query = "
            SELECT 
                DATE_FORMAT(date_envoi, '%Y-%m') as mois,
                COUNT(*) as nombre
            FROM sms_logs
            WHERE date_envoi >= DATE_SUB(CURRENT_DATE, INTERVAL $nb_mois MONTH)
            GROUP BY mois
            ORDER BY mois
        ";
        $stmt = $shop_pdo->query($query);
        $sms_par_mois = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Campagnes récentes
        $query = "
            SELECT 
                c.id,
                c.titre,
                c.date_creation,
                c.nombre_destinataires,
                COUNT(d.id) as nombre_envoyes,
                COUNT(CASE WHEN d.statut = 'envoyé' THEN 1 END) as reussis
            FROM sms_campaigns c
            LEFT JOIN sms_campaign_details d ON d.campaign_id = c.id
            GROUP BY c.id
            ORDER BY c.date_creation DESC
            LIMIT 5
        ";
        $stmt = $shop_pdo->query($query);
        $campagnes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_sms' => $total_sms,
            'sms_par_statut' => $sms_par_statut,
            'sms_par_mois' => $sms_par_mois,
            'campagnes_recentes' => $campagnes_recentes
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques des SMS: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques des SMS: " . $e->getMessage()
        ];
    }
}

/**
 * Récupère les statistiques du journal d'actions
 * @param int $limit Limite de résultats
 * @return array Statistiques du journal d'actions
 */
function get_statistiques_journal($limit = 1000) {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Total des actions
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM journal_actions");
        $total_actions = $stmt->fetch()['total'];
        
        // Actions par type
        $query = "
            SELECT 
                action_type,
                COUNT(*) as nombre
            FROM journal_actions
            GROUP BY action_type
            ORDER BY nombre DESC
        ";
        $stmt = $shop_pdo->query($query);
        $actions_par_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Actions par utilisateur
        $query = "
            SELECT 
                u.username,
                u.full_name,
                COUNT(j.id) as nombre_actions
            FROM users u
            JOIN journal_actions j ON j.user_id = u.id
            GROUP BY u.id
            ORDER BY nombre_actions DESC
        ";
        $stmt = $shop_pdo->query($query);
        $actions_par_utilisateur = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Actions récentes
        $query = "
            SELECT 
                j.id,
                j.action_type,
                j.date_action,
                j.details,
                u.username
            FROM journal_actions j
            JOIN users u ON j.user_id = u.id
            ORDER BY j.date_action DESC
            LIMIT ?
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$limit]);
        $actions_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_actions' => $total_actions,
            'actions_par_type' => $actions_par_type,
            'actions_par_utilisateur' => $actions_par_utilisateur,
            'actions_recentes' => $actions_recentes
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques du journal: " . $e->getMessage());
        return [
            'error' => true,
            'message' => "Erreur lors de la récupération des statistiques du journal: " . $e->getMessage()
        ];
    }
} 