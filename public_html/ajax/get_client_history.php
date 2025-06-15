<?php
/**
 * Fichier AJAX pour récupérer l'historique des réparations d'un client
 * Retourne du HTML pour affichage dans la modal
 */

// Headers pour HTML
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser shop_id si non défini (pour les tests et l'accès direct)
if (!isset($_SESSION['shop_id'])) {
    // Essayer de récupérer depuis l'URL
    if (isset($_GET['shop_id'])) {
        $_SESSION['shop_id'] = (int)$_GET['shop_id'];
    } else {
        // Valeur par défaut pour les tests
        $_SESSION['shop_id'] = 1;
    }
}

// Inclusion de la configuration de base de données
require_once '../config/database.php';

// Vérification de la méthode et des paramètres
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['client_id'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Paramètres invalides</div>';
    exit;
}

$client_id = (int)$_GET['client_id'];

if ($client_id <= 0) {
    http_response_code(400);
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>ID client invalide</div>';
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception("Impossible d'obtenir la connexion à la base de données");
    }
    
    // Vérifier que le client existe
    $stmt = $shop_pdo->prepare("SELECT nom, prenom FROM clients WHERE id = :client_id");
    $stmt->execute(['client_id' => $client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        echo '<div class="alert alert-warning"><i class="fas fa-user-slash me-2"></i>Client non trouvé</div>';
        exit;
    }
    
    // Récupérer les réparations
    $stmt = $shop_pdo->prepare("
        SELECT 
            r.id,
            r.date_reception,
            r.type_appareil,
            r.modele,
            r.statut,
            r.prix_reparation,
            r.description_probleme,
            r.notes_techniques,
            r.date_fin_prevue,
            r.marque,
            r.urgent,
            CASE 
                WHEN r.statut IN ('termine', 'livre') THEN 'Terminé'
                WHEN r.statut IN ('annule', 'refuse') THEN 'Annulé'
                WHEN r.statut IN ('en_cours_diagnostique', 'en_cours_intervention') THEN 'En cours'
                WHEN r.statut IN ('en_attente_accord_client', 'en_attente_livraison', 'en_attente_responsable') THEN 'En attente'
                ELSE 'Nouvelle'
            END as statut_affichage
        FROM reparations r 
        WHERE r.client_id = :client_id 
        ORDER BY r.date_reception DESC
    ");
    $stmt->execute(['client_id' => $client_id]);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reparations)) {
        echo '
            <div class="text-center py-5">
                <i class="fas fa-tools text-muted" style="font-size: 3rem;"></i>
                <h5 class="text-muted mt-3 mb-2">Aucune réparation</h5>
                <p class="text-muted">Ce client n\'a pas encore de réparations enregistrées.</p>
                <a href="index.php?page=ajouter_reparation&client_id=' . $client_id . '" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Ajouter une réparation
                </a>
            </div>
        ';
        exit;
    }
    
    // Calcul des statistiques
    $total_reparations = count($reparations);
    $reparations_terminees = count(array_filter($reparations, function($r) {
        return in_array($r['statut'], ['termine', 'livre']);
    }));
    $reparations_en_cours = count(array_filter($reparations, function($r) {
        return in_array($r['statut'], ['en_cours_diagnostique', 'en_cours_intervention', 'en_attente_accord_client', 'en_attente_livraison']);
    }));
    $total_ca = array_sum(array_column($reparations, 'prix_reparation'));
    
    // Affichage des statistiques
    echo '<div class="row mb-4">';
    echo '<div class="col-md-3 text-center">';
    echo '<div class="card bg-primary text-white">';
    echo '<div class="card-body py-2">';
    echo '<h6 class="card-title mb-1">' . $total_reparations . '</h6>';
    echo '<small>Total</small>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3 text-center">';
    echo '<div class="card bg-success text-white">';
    echo '<div class="card-body py-2">';
    echo '<h6 class="card-title mb-1">' . $reparations_terminees . '</h6>';
    echo '<small>Terminées</small>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3 text-center">';
    echo '<div class="card bg-warning text-white">';
    echo '<div class="card-body py-2">';
    echo '<h6 class="card-title mb-1">' . $reparations_en_cours . '</h6>';
    echo '<small>En cours</small>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3 text-center">';
    echo '<div class="card bg-info text-white">';
    echo '<div class="card-body py-2">';
    echo '<h6 class="card-title mb-1">' . number_format($total_ca, 2) . ' €</h6>';
    echo '<small>CA Total</small>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Tableau des réparations
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover">';
    echo '<thead class="table-light">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Date</th>';
    echo '<th>Appareil</th>';
    echo '<th>Marque/Modèle</th>';
    echo '<th>Statut</th>';
    echo '<th class="text-end">Prix</th>';
    echo '<th class="text-center">Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($reparations as $reparation) {
        $badge_class = match($reparation['statut_affichage']) {
            'Terminé' => 'success',
            'Annulé' => 'danger',
            'En cours' => 'primary',
            'En attente' => 'warning',
            default => 'secondary'
        };
        
        $date_formatted = date('d/m', strtotime($reparation['date_reception']));
        $prix_formatted = number_format($reparation['prix_reparation'], 2);
        
        echo '<tr>';
        echo '<td>';
        echo '<span class="badge bg-light text-dark">#' . htmlspecialchars($reparation['id']) . '</span>';
        if ($reparation['urgent']) {
            echo ' <i class="fas fa-exclamation-triangle text-danger ms-1" title="Urgent"></i>';
        }
        echo '</td>';
        echo '<td>' . $date_formatted . '</td>';
        echo '<td>' . htmlspecialchars($reparation['type_appareil']) . '</td>';
        echo '<td>';
        echo '<div>' . htmlspecialchars($reparation['marque']) . '</div>';
        echo '<small class="text-muted">' . htmlspecialchars($reparation['modele']) . '</small>';
        echo '</td>';
        echo '<td><span class="badge bg-' . $badge_class . '">' . htmlspecialchars($reparation['statut_affichage']) . '</span></td>';
        echo '<td class="text-end">' . $prix_formatted . ' €</td>';
        echo '<td class="text-center">';
        echo '<button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="toggleDetails(' . $reparation['id'] . ')" title="Voir/Masquer les détails">';
        echo '<i class="fas fa-eye"></i>';
        echo '</button>';
        echo '<a href="index.php?page=modifier_reparation&id=' . $reparation['id'] . '" class="btn btn-sm btn-outline-warning" title="Modifier">';
        echo '<i class="fas fa-edit"></i>';
        echo '</a>';
        echo '</td>';
        echo '</tr>';
        
        // Ligne de détails (masquée par défaut)
        echo '<tr class="d-none" id="details-' . $reparation['id'] . '">';
        echo '<td colspan="7">';
        echo '<div class="card card-body bg-light">';
        echo '<div class="row">';
        
        echo '<div class="col-md-6">';
        echo '<h6><i class="fas fa-clipboard-list me-2"></i>Description du problème</h6>';
        echo '<p class="mb-2">' . nl2br(htmlspecialchars($reparation['description_probleme'])) . '</p>';
        echo '</div>';
        
        if (!empty($reparation['notes_techniques'])) {
            echo '<div class="col-md-6">';
            echo '<h6><i class="fas fa-wrench me-2"></i>Notes techniques</h6>';
            echo '<p class="mb-2">' . nl2br(htmlspecialchars($reparation['notes_techniques'])) . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Informations supplémentaires
        echo '<div class="row mt-3">';
        echo '<div class="col-md-6">';
        echo '<h6><i class="fas fa-calendar me-2"></i>Dates importantes</h6>';
        echo '<ul class="list-unstyled mb-0">';
        echo '<li><small><strong>Réception :</strong> ' . date('d/m/Y à H:i', strtotime($reparation['date_reception'])) . '</small></li>';
        if (!empty($reparation['date_fin_prevue'])) {
            echo '<li><small><strong>Fin prévue :</strong> ' . date('d/m/Y', strtotime($reparation['date_fin_prevue'])) . '</small></li>';
        }
        echo '</ul>';
        echo '</div>';
        
        echo '<div class="col-md-6">';
        echo '<h6><i class="fas fa-info-circle me-2"></i>Informations</h6>';
        echo '<ul class="list-unstyled mb-0">';
        echo '<li><small><strong>Statut technique :</strong> ' . htmlspecialchars($reparation['statut']) . '</small></li>';
        if ($reparation['urgent']) {
            echo '<li><small><strong class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Réparation urgente</strong></small></li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // JavaScript pour les détails
    echo '
    <script>
    function toggleDetails(id) {
        const detailsRow = document.getElementById("details-" + id);
        if (detailsRow) {
            detailsRow.classList.toggle("d-none");
            
            // Changer l\'icône
            const button = event.target.closest("button");
            const icon = button.querySelector("i");
            if (detailsRow.classList.contains("d-none")) {
                icon.className = "fas fa-eye";
                button.title = "Voir les détails";
            } else {
                icon.className = "fas fa-eye-slash";
                button.title = "Masquer les détails";
            }
        }
    }
    </script>
    ';
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans get_client_history.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    echo '<div class="alert alert-danger"><i class="fas fa-database me-2"></i>Erreur de base de données: ' . htmlspecialchars($e->getMessage()) . '</div>';
} catch (Exception $e) {
    error_log("Erreur générale dans get_client_history.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur serveur: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?> 