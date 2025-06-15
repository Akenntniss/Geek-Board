<?php
// Vérification des droits de base
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("");
    exit;
}

// Variable pour déterminer le niveau d'accès
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Vérifier qu'un ID de campagne est fourni
if (!isset($_GET['id']) || !(int)$_GET['id']) {
    set_message("ID de campagne invalide.", "danger");
    redirect("campagne_sms");
    exit;
}

$campaign_id = (int)$_GET['id'];

// Récupérer les détails de la campagne
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("
        SELECT c.*, u.nom as user_nom, u.prenom as user_prenom
        FROM sms_campaigns c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        set_message("Campagne introuvable.", "danger");
        redirect("campagne_sms");
        exit;
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des détails de la campagne : " . $e->getMessage(), "danger");
    redirect("campagne_sms");
    exit;
}

// Paramètres de pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Filtres pour les détails
$statut_filter = isset($_GET['statut']) ? clean_input($_GET['statut']) : '';

// Récupérer les détails de chaque envoi
try {
    // Construction de la requête
    $sql_count = "
        SELECT COUNT(*) as total
        FROM sms_campaign_details d
        JOIN clients c ON d.client_id = c.id
        WHERE d.campaign_id = ?
    ";
    
    $sql = "
        SELECT d.*, c.nom, c.prenom
        FROM sms_campaign_details d
        JOIN clients c ON d.client_id = c.id
        WHERE d.campaign_id = ?
    ";
    
    $params = [$campaign_id];
    
    // Ajouter le filtre par statut si défini
    if (!empty($statut_filter)) {
        $sql_count .= " AND d.statut = ?";
        $sql .= " AND d.statut = ?";
        $params[] = $statut_filter;
    }
    
    // Ajout du tri et de la pagination
    $sql .= " ORDER BY d.date_envoi DESC LIMIT ? OFFSET ?";
    $params_pagination = array_merge($params, [$items_per_page, $offset]);
    
    // Exécution des requêtes
    $stmt_count = $shop_pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_items = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params_pagination);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer le nombre total de pages
    $total_pages = ceil($total_items / $items_per_page);
    
} catch (PDOException $e) {
    $details = [];
    $total_items = 0;
    $total_pages = 1;
    set_message("Erreur lors de la récupération des détails des envois : " . $e->getMessage(), "danger");
}
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>
                    <a href="index.php?page=campagne_sms" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    Détails de la campagne
                </h1>
            </div>
        </div>
    </div>
    
    <!-- Informations sur la campagne -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Nom</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($campaign['nom']); ?></dd>
                                
                                <dt class="col-sm-4">Date d'envoi</dt>
                                <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($campaign['date_envoi'])); ?></dd>
                                
                                <dt class="col-sm-4">Envoyé par</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                    if ($campaign['user_nom']) {
                                        echo htmlspecialchars($campaign['user_prenom'] . ' ' . $campaign['user_nom']);
                                    } else {
                                        echo 'Système';
                                    }
                                    ?>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Destinataires</dt>
                                <dd class="col-sm-8"><?php echo $campaign['nb_destinataires']; ?></dd>
                                
                                <dt class="col-sm-4">Envois réussis</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-success"><?php echo $campaign['nb_envoyes']; ?></span>
                                </dd>
                                
                                <dt class="col-sm-4">Échecs</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-danger"><?php echo $campaign['nb_echecs']; ?></span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Message envoyé :</h6>
                        <div class="bg-light p-3 rounded">
                            <?php echo nl2br(htmlspecialchars($campaign['message'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des envois -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Détails des envois</h5>
                    
                    <!-- Filtrage -->
                    <div>
                        <form method="get" class="d-flex align-items-center">
                            <input type="hidden" name="page" value="campagne_details">
                            <input type="hidden" name="id" value="<?php echo $campaign_id; ?>">
                            
                            <select name="statut" class="form-select form-select-sm me-2" style="width: auto;">
                                <option value="">Tous les statuts</option>
                                <option value="envoyé" <?php echo $statut_filter === 'envoyé' ? 'selected' : ''; ?>>Envoyés</option>
                                <option value="échec" <?php echo $statut_filter === 'échec' ? 'selected' : ''; ?>>Échecs</option>
                            </select>
                            
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-filter"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($details)): ?>
                    <div class="text-center py-4">
                        <p class="mb-0">Aucun détail d'envoi trouvé.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date d'envoi</th>
                                    <th>Client</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details as $detail): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($detail['date_envoi'])); ?></td>
                                    <td>
                                        <a href="index.php?page=modifier_client&id=<?php echo $detail['client_id']; ?>">
                                            <?php echo htmlspecialchars($detail['prenom'] . ' ' . $detail['nom']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($detail['telephone']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $detail['statut'] === 'envoyé' ? 'success' : 'danger'; ?>">
                                            <?php echo $detail['statut']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info view-sms" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#smsContentModal"
                                            data-content="<?php echo htmlspecialchars($detail['message']); ?>"
                                            data-client="<?php echo htmlspecialchars($detail['prenom'] . ' ' . $detail['nom']); ?>">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $page - 1; ?>)" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $i; ?>)"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $page + 1; ?>)" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour afficher le contenu d'un SMS -->
<div class="modal fade" id="smsContentModal" tabindex="-1" aria-labelledby="smsContentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="smsContentModalLabel"><i class="fas fa-sms me-2"></i>Contenu du SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <small class="text-muted">Envoyé à <span id="smsClient"></span></small>
                </div>
                <div class="card bg-light">
                    <div class="card-body">
                        <p class="mb-0" id="smsContent"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration du modal d'affichage du contenu SMS
    const smsContentModal = document.getElementById('smsContentModal');
    smsContentModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const content = button.getAttribute('data-content');
        const client = button.getAttribute('data-client');
        
        document.getElementById('smsContent').textContent = content;
        document.getElementById('smsClient').textContent = client;
    });
});

// Fonction pour changer de page
function changePage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page_num', page);
    window.location.href = url.toString();
}
</script> 