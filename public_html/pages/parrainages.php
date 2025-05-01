<?php
// Vérifier si la page est déjà chargée (pour éviter les inclusions multiples)
if (defined('PAGE_PARRAINAGES_LOADED')) {
    echo '<div class="alert alert-danger">Erreur: La page est déjà chargée une fois. Vérifiez votre système d\'inclusion.</div>';
    return;
}
define('PAGE_PARRAINAGES_LOADED', true);

// Récupérer les parrainages avec les informations des clients
$query_parrainages = "
    SELECT p.*, 
           c1.nom AS parrain_nom, c1.prenom AS parrain_prenom, c1.telephone AS parrain_telephone,
           c2.nom AS filleul_nom, c2.prenom AS filleul_prenom, c2.telephone AS filleul_telephone
    FROM parrainages p
    JOIN clients c1 ON p.parrain_id = c1.id
    JOIN clients c2 ON p.filleul_id = c2.id
    ORDER BY p.date_parrainage DESC
";

try {
    $stmt = $pdo->query($query_parrainages);
    $parrainages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur lors de la récupération des parrainages: ' . $e->getMessage() . '</div>';
    $parrainages = [];
}

// Récupérer les paliers de réduction
$query_paliers = "SELECT * FROM parrainage_paliers WHERE actif = 'OUI' ORDER BY montant_min ASC";
try {
    $stmt = $pdo->query($query_paliers);
    $paliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur lors de la récupération des paliers: ' . $e->getMessage() . '</div>';
    $paliers = [];
}

// Traitement de la mise à jour du montant dépensé par un filleul
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_montant') {
    $parrainage_id = isset($_POST['parrainage_id']) ? (int)$_POST['parrainage_id'] : 0;
    $montant = isset($_POST['montant']) ? (float)$_POST['montant'] : 0;
    
    if ($parrainage_id > 0) {
        try {
            // Mettre à jour le montant
            $stmt = $pdo->prepare("UPDATE parrainages SET montant_depense_filleul = ? WHERE id = ?");
            $stmt->execute([$montant, $parrainage_id]);
            
            // Calculer la réduction en fonction des paliers
            $reduction = 0;
            foreach ($paliers as $palier) {
                if ($montant >= $palier['montant_min'] && $montant <= $palier['montant_max']) {
                    $reduction = $montant * ($palier['taux_reduction'] / 100);
                    break;
                }
            }
            
            // Mettre à jour la réduction
            $stmt = $pdo->prepare("UPDATE parrainages SET reduction_appliquee = ? WHERE id = ?");
            $stmt->execute([$reduction, $parrainage_id]);
            
            set_message("Montant et réduction mis à jour avec succès.", "success");
            redirect("parrainages");
        } catch (PDOException $e) {
            set_message("Erreur lors de la mise à jour du montant: " . $e->getMessage(), "danger");
        }
    }
}

// Marquer une réduction comme utilisée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'use_reduction') {
    $parrainage_id = isset($_POST['parrainage_id']) ? (int)$_POST['parrainage_id'] : 0;
    $reparation_id = isset($_POST['reparation_id']) ? (int)$_POST['reparation_id'] : 0;
    
    if ($parrainage_id > 0 && $reparation_id > 0) {
        try {
            // Récupérer le montant de la réduction
            $stmt = $pdo->prepare("SELECT reduction_appliquee FROM parrainages WHERE id = ?");
            $stmt->execute([$parrainage_id]);
            $reduction = $stmt->fetchColumn();
            
            // Marquer la réduction comme utilisée
            $stmt = $pdo->prepare("
                UPDATE parrainages 
                SET reduction_utilisee = 'OUI', 
                    date_utilisation = CURDATE(), 
                    reparation_utilisation_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$reparation_id, $parrainage_id]);
            
            // Ajouter dans l'historique
            $stmt = $pdo->prepare("
                INSERT INTO parrainage_historique (
                    parrainage_id, reparation_id, montant_reduction, date_application
                ) VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$parrainage_id, $reparation_id, $reduction]);
            
            set_message("Réduction appliquée avec succès à la réparation #$reparation_id.", "success");
            redirect("parrainages");
        } catch (PDOException $e) {
            set_message("Erreur lors de l'application de la réduction: " . $e->getMessage(), "danger");
        }
    }
}

// Récupérer les clients inscrits au programme de fidélité
$query_clients = "
    SELECT id, nom, prenom, telephone 
    FROM clients 
    WHERE programme_fidelite = 'OUI'
    ORDER BY nom, prenom
";

try {
    $stmt = $pdo->query($query_clients);
    $clients_fidelite = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur lors de la récupération des clients: ' . $e->getMessage() . '</div>';
    $clients_fidelite = [];
}

// Récupérer les réparations actives
$query_reparations = "
    SELECT r.id, r.type_appareil, r.modele, c.nom, c.prenom
    FROM reparations r
    JOIN clients c ON r.client_id = c.id
    WHERE r.statut IN ('nouvelle_intervention', 'en_cours', 'diagnostique')
    ORDER BY r.date_reception DESC
    LIMIT 50
";

try {
    $stmt = $pdo->query($query_reparations);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur lors de la récupération des réparations: ' . $e->getMessage() . '</div>';
    $reparations = [];
}

// Regrouper les filleuls par parrain pour l'affichage des statistiques
$parrains_stats = [];
foreach ($parrainages as $parrainage) {
    $parrain_id = $parrainage['parrain_id'];
    if (!isset($parrains_stats[$parrain_id])) {
        $parrains_stats[$parrain_id] = [
            'nom' => $parrainage['parrain_nom'] . ' ' . $parrainage['parrain_prenom'],
            'telephone' => $parrainage['parrain_telephone'],
            'filleuls' => [],
            'total_reductions' => 0,
            'reductions_utilisees' => 0
        ];
    }
    
    $parrains_stats[$parrain_id]['filleuls'][] = [
        'nom' => $parrainage['filleul_nom'] . ' ' . $parrainage['filleul_prenom'],
        'montant' => $parrainage['montant_depense_filleul'],
        'reduction' => $parrainage['reduction_appliquee'],
        'utilisee' => $parrainage['reduction_utilisee']
    ];
    
    $parrains_stats[$parrain_id]['total_reductions'] += $parrainage['reduction_appliquee'];
    if ($parrainage['reduction_utilisee'] === 'OUI') {
        $parrains_stats[$parrain_id]['reductions_utilisees'] += $parrainage['reduction_appliquee'];
    }
}
?>

<div class="container-fluid">
    <h3 class="page-title my-3">Gestion des parrainages</h3>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Statistiques des parrainages</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body text-center">
                                    <h3 class="text-primary mb-0"><?= count($parrainages) ?></h3>
                                    <p class="mb-0">Parrainages totaux</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body text-center">
                                    <h3 class="text-success mb-0"><?= count($clients_fidelite) ?></h3>
                                    <p class="mb-0">Clients au programme</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Paliers de réduction</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Montant minimum</th>
                                    <th>Montant maximum</th>
                                    <th>Taux de réduction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paliers as $palier): ?>
                                <tr>
                                    <td><?= number_format($palier['montant_min'], 2, ',', ' ') ?> €</td>
                                    <td><?= number_format($palier['montant_max'], 2, ',', ' ') ?> €</td>
                                    <td><?= $palier['taux_reduction'] ?> %</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <ul class="nav nav-tabs mb-4" id="parrainageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="parrainages-tab" data-bs-toggle="tab" data-bs-target="#parrainages-content" type="button" role="tab" aria-controls="parrainages-content" aria-selected="true">Liste des parrainages</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="parrains-tab" data-bs-toggle="tab" data-bs-target="#parrains-content" type="button" role="tab" aria-controls="parrains-content" aria-selected="false">Statistiques par parrain</button>
        </li>
    </ul>
    
    <div class="tab-content" id="parrainageTabsContent">
        <!-- Onglet Liste des parrainages -->
        <div class="tab-pane fade show active" id="parrainages-content" role="tabpanel" aria-labelledby="parrainages-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Parrain</th>
                                    <th>Filleul</th>
                                    <th>Date</th>
                                    <th>Montant dépensé</th>
                                    <th>Réduction</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($parrainages)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">Aucun parrainage trouvé.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($parrainages as $parrainage): ?>
                                    <tr>
                                        <td><?= $parrainage['id'] ?></td>
                                        <td>
                                            <strong><?= $parrainage['parrain_nom'] ?> <?= $parrainage['parrain_prenom'] ?></strong><br>
                                            <small class="text-muted"><?= $parrainage['parrain_telephone'] ?></small>
                                        </td>
                                        <td>
                                            <strong><?= $parrainage['filleul_nom'] ?> <?= $parrainage['filleul_prenom'] ?></strong><br>
                                            <small class="text-muted"><?= $parrainage['filleul_telephone'] ?></small>
                                        </td>
                                        <td><?= format_date($parrainage['date_parrainage']) ?></td>
                                        <td>
                                            <?php if ($parrainage['reduction_utilisee'] === 'NON'): ?>
                                            <form method="post" class="montant-form">
                                                <input type="hidden" name="action" value="update_montant">
                                                <input type="hidden" name="parrainage_id" value="<?= $parrainage['id'] ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="montant" class="form-control" step="0.01" value="<?= $parrainage['montant_depense_filleul'] ?>">
                                                    <span class="input-group-text">€</span>
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </form>
                                            <?php else: ?>
                                            <?= number_format($parrainage['montant_depense_filleul'], 2, ',', ' ') ?> €
                                            <?php endif; ?>
                                        </td>
                                        <td class="<?= $parrainage['reduction_appliquee'] > 0 ? 'text-success' : '' ?>">
                                            <?= number_format($parrainage['reduction_appliquee'], 2, ',', ' ') ?> €
                                        </td>
                                        <td>
                                            <?php if ($parrainage['reduction_utilisee'] === 'OUI'): ?>
                                                <span class="badge bg-success">Utilisée</span>
                                                <?php if ($parrainage['date_utilisation']): ?>
                                                    <br><small class="text-muted">le <?= format_date($parrainage['date_utilisation']) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($parrainage['reduction_utilisee'] === 'NON' && $parrainage['reduction_appliquee'] > 0): ?>
                                            <button type="button" class="btn btn-sm btn-success appliquer-reduction" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#appliquerReductionModal"
                                                    data-parrainage-id="<?= $parrainage['id'] ?>"
                                                    data-reduction="<?= $parrainage['reduction_appliquee'] ?>"
                                                    data-parrain="<?= $parrainage['parrain_nom'] ?> <?= $parrainage['parrain_prenom'] ?>">
                                                <i class="fas fa-check-circle me-1"></i> Appliquer
                                            </button>
                                            <?php elseif ($parrainage['reduction_utilisee'] === 'OUI' && $parrainage['reparation_utilisation_id']): ?>
                                            <a href="index.php?page=details_reparation&id=<?= $parrainage['reparation_utilisation_id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye me-1"></i> Voir réparation
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Onglet Statistiques par parrain -->
        <div class="tab-pane fade" id="parrains-content" role="tabpanel" aria-labelledby="parrains-tab">
            <div class="row">
                <?php foreach ($parrains_stats as $parrain_id => $parrain): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?= $parrain['nom'] ?></h5>
                            <small><?= $parrain['telephone'] ?></small>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="text-center">
                                    <h4><?= count($parrain['filleuls']) ?></h4>
                                    <span class="text-muted">Filleuls</span>
                                </div>
                                <div class="text-center">
                                    <h4><?= number_format($parrain['total_reductions'] - $parrain['reductions_utilisees'], 2, ',', ' ') ?> €</h4>
                                    <span class="text-muted">Réductions disponibles</span>
                                </div>
                            </div>
                            
                            <h6 class="border-top pt-2">Liste des filleuls :</h6>
                            <ul class="list-group">
                                <?php foreach ($parrain['filleuls'] as $filleul): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?= $filleul['nom'] ?>
                                        <br><small class="text-muted">Montant : <?= number_format($filleul['montant'], 2, ',', ' ') ?> €</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge rounded-pill <?= $filleul['utilisee'] === 'OUI' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= number_format($filleul['reduction'], 2, ',', ' ') ?> €
                                            <?= $filleul['utilisee'] === 'OUI' ? ' (Utilisée)' : '' ?>
                                        </span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour appliquer une réduction -->
<div class="modal fade" id="appliquerReductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appliquer une réduction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="formAppliquerReduction">
                    <input type="hidden" name="action" value="use_reduction">
                    <input type="hidden" name="parrainage_id" id="modal_parrainage_id">
                    
                    <div class="alert alert-info">
                        <p>Vous allez appliquer une réduction de <strong id="modal_reduction_montant">0.00</strong> € pour le parrain <strong id="modal_parrain_nom"></strong>.</p>
                        <p>Veuillez sélectionner la réparation à laquelle cette réduction s'applique :</p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reparation_id" class="form-label">Réparation</label>
                        <select class="form-select" name="reparation_id" id="reparation_id" required>
                            <option value="">Choisir une réparation...</option>
                            <?php foreach ($reparations as $reparation): ?>
                            <option value="<?= $reparation['id'] ?>">
                                #<?= $reparation['id'] ?> - <?= $reparation['type_appareil'] ?> <?= $reparation['modele'] ?> 
                                (<?= $reparation['nom'] ?> <?= $reparation['prenom'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn_submit_reduction">Appliquer la réduction</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du modal pour appliquer une réduction
    document.querySelectorAll('.appliquer-reduction').forEach(function(button) {
        button.addEventListener('click', function() {
            const parrainageId = this.getAttribute('data-parrainage-id');
            const reduction = this.getAttribute('data-reduction');
            const parrain = this.getAttribute('data-parrain');
            
            document.getElementById('modal_parrainage_id').value = parrainageId;
            document.getElementById('modal_reduction_montant').textContent = formatMoney(reduction);
            document.getElementById('modal_parrain_nom').textContent = parrain;
        });
    });
    
    // Soumission du formulaire d'application de réduction
    document.getElementById('btn_submit_reduction').addEventListener('click', function() {
        const form = document.getElementById('formAppliquerReduction');
        const reparationId = document.getElementById('reparation_id').value;
        
        if (!reparationId) {
            alert('Veuillez sélectionner une réparation.');
            return;
        }
        
        form.submit();
    });
    
    // Formater un montant en euros
    function formatMoney(amount) {
        return parseFloat(amount).toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});
</script> 