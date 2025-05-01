<?php

function build_reparations_query($filters = []) {
    $sql = "
        SELECT r.*, 
               c.nom as client_nom, 
               c.prenom as client_prenom, 
               s.nom as statut_nom, 
               sc.couleur as statut_couleur
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut = s.code
        LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
        WHERE s.est_actif = TRUE
    ";
    
    $params = [];
    
    // Filtre simple par statut
    if (!empty($filters['statut'])) {
        $sql .= " AND r.statut = ?";
        $params[] = $filters['statut'];
    }
    
    // Filtre par type d'appareil
    if (!empty($filters['type_appareil'])) {
        $sql .= " AND r.type_appareil = ?";
        $params[] = $filters['type_appareil'];
    }
    
    $sql .= " ORDER BY r.date_reception DESC";
    
    return ['sql' => $sql, 'params' => $params];
}

function get_reparations_stats() {
    global $pdo;
    
    try {
        // Utilisation d'une seule requête pour toutes les statistiques
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN sc.code = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN sc.code = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                SUM(CASE WHEN sc.code = 'termine' THEN 1 ELSE 0 END) as terminees,
                SUM(CASE WHEN MONTH(r.date_reception) = MONTH(CURRENT_DATE()) AND YEAR(r.date_reception) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as mois_courant
            FROM reparations r
            JOIN statuts s ON r.statut = s.code
            JOIN statut_categories sc ON s.categorie_id = sc.id
            WHERE s.est_actif = TRUE
        ";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
        return [
            'total' => 0,
            'en_cours' => 0,
            'en_attente' => 0,
            'terminees' => 0,
            'mois_courant' => 0
        ];
    }
}

function get_reparations($filters = []) {
    global $pdo;
    
    try {
        $query = build_reparations_query($filters);
        $stmt = $pdo->prepare($query['sql']);
        $stmt->execute($query['params']);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des réparations: " . $e->getMessage());
        return [];
    }
}