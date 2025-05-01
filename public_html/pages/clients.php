<?php
// Récupérer la liste des clients avec le nombre de réparations
try {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(r.id) as nombre_reparations 
        FROM clients c 
        LEFT JOIN reparations r ON c.id = r.client_id 
        GROUP BY c.id 
        ORDER BY c.nom ASC
    ");
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des clients : " . $e->getMessage());
    $clients = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-3 mb-md-0">Liste des Clients</h1>
    <a href="index.php?page=ajouter_client" class="btn btn-primary d-none d-md-inline-block">
        <i class="fas fa-user-plus me-2"></i>Ajouter un client
    </a>
</div>

<!-- Barre de recherche rapide pour mobile -->
<div class="card mb-4 d-md-none">
    <div class="card-body">
        <div class="input-group">
            <input type="text" id="searchClient" class="form-control form-control-lg" placeholder="Rechercher un client...">
            <button class="btn btn-primary" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th class="d-none d-md-table-cell">ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Téléphone</th>
                        <th>Réparations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clients)): ?>
                        <?php foreach ($clients as $client): ?>
                        <tr class="client-row">
                            <td class="d-none d-md-table-cell"><?php echo $client['id']; ?></td>
                            <td>
                                <div class="d-block d-md-none">
                                    <small class="text-muted"><?php echo htmlspecialchars($client['telephone']); ?></small>
                                </div>
                                <?php echo htmlspecialchars($client['nom']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($client['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($client['telephone']); ?></td>
                            <td>
                                <?php if ($client['nombre_reparations'] > 0): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#historiqueModal<?php echo $client['id']; ?>">
                                        <?php echo $client['nombre_reparations']; ?> réparation(s)
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="index.php?page=modifier_client&id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" title="Supprimer" onclick="confirmDelete(<?php echo $client['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-users text-muted fa-3x mb-3"></i>
                                <p class="text-muted">Aucun client trouvé.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce client ?')) {
        window.location.href = 'index.php?page=clients&action=delete&id=' + id;
    }
}
</script>

<?php
// Traitement de la suppression d'un client
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Vérifier si le client a des réparations
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reparations WHERE client_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            // Le client a des réparations, on ne peut pas le supprimer
            set_message("Impossible de supprimer ce client car il a des réparations associées.", "danger");
        } else {
            // Supprimer le client
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            
            set_message("Client supprimé avec succès.", "success");
        }
    } catch (PDOException $e) {
        set_message("Erreur lors de la suppression du client: " . $e->getMessage(), "danger");
    }
    
    // Rediriger pour éviter de re-exécuter l'action lors d'un rafraîchissement
    redirect("clients");
}
?>

<!-- Modals pour l'historique des réparations -->
<?php foreach ($clients as $client): ?>
    <?php if ($client['nombre_reparations'] > 0): ?>
        <div class="modal fade" id="historiqueModal<?php echo $client['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Historique des réparations - <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Type d'appareil</th>
                                        <th>Modèle</th>
                                        <th>Statut</th>
                                        <th>Prix</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $pdo->prepare("
                                            SELECT r.*, 
                                                   CASE 
                                                       WHEN r.statut IN ('termine', 'livre') THEN 'Terminé'
                                                       WHEN r.statut IN ('annule', 'refuse') THEN 'Annulé'
                                                       WHEN r.statut IN ('en_cours_diagnostique', 'en_cours_intervention') THEN 'En cours'
                                                       WHEN r.statut IN ('en_attente_accord_client', 'en_attente_livraison', 'en_attente_responsable') THEN 'En attente'
                                                       ELSE 'Nouvelle'
                                                   END as statut_affichage
                                            FROM reparations r 
                                            WHERE r.client_id = ? 
                                            ORDER BY r.date_reception DESC
                                        ");
                                        $stmt->execute([$client['id']]);
                                        $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (empty($reparations)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Aucune réparation trouvée</td>
                                            </tr>
                                        <?php else:
                                            foreach ($reparations as $reparation): ?>
                                                <tr>
                                                    <td><?php echo $reparation['id']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($reparation['date_reception'])); ?></td>
                                                    <td><?php echo htmlspecialchars($reparation['type_appareil']); ?></td>
                                                    <td><?php echo htmlspecialchars($reparation['modele']); ?></td>
                                                    <td>
                                                        <?php
                                                        $badge_class = match($reparation['statut_affichage']) {
                                                            'Terminé' => 'success',
                                                            'Annulé' => 'danger',
                                                            'En cours' => 'primary',
                                                            'En attente' => 'warning',
                                                            default => 'secondary'
                                                        };
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                                            <?php echo $reparation['statut_affichage']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($reparation['prix_reparation'], 2); ?> €</td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#detailsReparationModal<?php echo $reparation['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                        endif;
                                    } catch (PDOException $e) {
                                        echo '<tr><td colspan="7" class="text-center text-danger">Erreur lors de la récupération des réparations</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals pour les détails des réparations -->
        <?php if (!empty($reparations)): ?>
            <?php foreach ($reparations as $reparation): ?>
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
                        FROM reparations r
                        JOIN clients c ON r.client_id = c.id
                        WHERE r.id = ?
                    ");
                    $stmt->execute([$reparation['id']]);
                    $details = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="modal fade" id="detailsReparationModal<?php echo $reparation['id']; ?>" tabindex="-1" data-bs-backdrop="static">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Détails de la réparation #<?php echo $reparation['id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Informations client</h6>
                                            <p><strong>Client :</strong> <?php echo htmlspecialchars($details['client_nom'] . ' ' . $details['client_prenom']); ?></p>
                                            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($details['client_telephone']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Informations réparation</h6>
                                            <p><strong>Date de réception :</strong> <?php echo date('d/m/Y', strtotime($details['date_reception'])); ?></p>
                                            <p><strong>Type d'appareil :</strong> <?php echo htmlspecialchars($details['type_appareil']); ?></p>
                                            <p><strong>Modèle :</strong> <?php echo htmlspecialchars($details['modele']); ?></p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6>Description du problème</h6>
                                            <p><?php echo nl2br(htmlspecialchars($details['description_probleme'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <h6>Statut et prix</h6>
                                            <p>
                                                <strong>Statut :</strong>
                                                <?php
                                                $badge_class = match($details['statut']) {
                                                    'termine', 'livre' => 'success',
                                                    'annule', 'refuse' => 'danger',
                                                    'en_cours_diagnostique', 'en_cours_intervention' => 'primary',
                                                    'en_attente_accord_client', 'en_attente_livraison', 'en_attente_responsable' => 'warning',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $badge_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $details['statut'])); ?>
                                                </span>
                                            </p>
                                            <p><strong>Prix :</strong> <?php echo number_format($details['prix_reparation'], 2); ?> €</p>
                                        </div>
                                        <?php if (!empty($details['notes_techniques'])): ?>
                                        <div class="col-md-6">
                                            <h6>Notes techniques</h6>
                                            <p><?php echo nl2br(htmlspecialchars($details['notes_techniques'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modifierReparationModal<?php echo $reparation['id']; ?>">
                                        <i class="fas fa-edit me-2"></i>Modifier la réparation
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de modification de la réparation -->
                    <div class="modal fade" id="modifierReparationModal<?php echo $reparation['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier la réparation #<?php echo $reparation['id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="index.php?page=modifier_reparation&id=<?php echo $reparation['id']; ?>">
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="statut<?php echo $reparation['id']; ?>" class="form-label">Statut *</label>
                                                <select class="form-select" id="statut<?php echo $reparation['id']; ?>" name="statut" required>
                                                    <option value="en_attente" <?php echo $reparation['statut'] == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                                    <option value="en_cours_diagnostique" <?php echo $reparation['statut'] == 'en_cours_diagnostique' ? 'selected' : ''; ?>>En cours de diagnostic</option>
                                                    <option value="en_cours_intervention" <?php echo $reparation['statut'] == 'en_cours_intervention' ? 'selected' : ''; ?>>En cours d'intervention</option>
                                                    <option value="en_attente_accord_client" <?php echo $reparation['statut'] == 'en_attente_accord_client' ? 'selected' : ''; ?>>En attente d'accord client</option>
                                                    <option value="en_attente_livraison" <?php echo $reparation['statut'] == 'en_attente_livraison' ? 'selected' : ''; ?>>En attente de livraison</option>
                                                    <option value="termine" <?php echo $reparation['statut'] == 'termine' ? 'selected' : ''; ?>>Terminé</option>
                                                    <option value="livre" <?php echo $reparation['statut'] == 'livre' ? 'selected' : ''; ?>>Livré</option>
                                                    <option value="annule" <?php echo $reparation['statut'] == 'annule' ? 'selected' : ''; ?>>Annulé</option>
                                                    <option value="refuse" <?php echo $reparation['statut'] == 'refuse' ? 'selected' : ''; ?>>Refusé</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="prix<?php echo $reparation['id']; ?>" class="form-label">Prix (€)</label>
                                                <input type="number" step="0.01" class="form-control" id="prix<?php echo $reparation['id']; ?>" name="prix" value="<?php echo $reparation['prix_reparation']; ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="date_fin_prevue<?php echo $reparation['id']; ?>" class="form-label">Date de fin prévue</label>
                                                <input type="date" class="form-control" id="date_fin_prevue<?php echo $reparation['id']; ?>" name="date_fin_prevue" 
                                                    value="<?php echo !empty($reparation['date_fin_prevue']) ? date('Y-m-d', strtotime($reparation['date_fin_prevue'])) : ''; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="notes_techniques<?php echo $reparation['id']; ?>" class="form-label">Notes techniques</label>
                                            <textarea class="form-control" id="notes_techniques<?php echo $reparation['id']; ?>" name="notes_techniques" rows="4"><?php echo htmlspecialchars($reparation['notes_techniques'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="button" class="btn btn-secondary me-md-2" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-warning">Mettre à jour</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } catch (PDOException $e) {
                    echo '<div class="modal fade" id="detailsReparationModal' . $reparation['id'] . '" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Erreur</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-danger">Erreur lors de la récupération des détails de la réparation.</p>
                                    </div>
                                </div>
                            </div>
                        </div>';
                }
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des modals
    const modals = document.querySelectorAll('.modal');
    let isModalTransitioning = false;

    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function(e) {
            if (isModalTransitioning) {
                e.preventDefault();
                return;
            }
            isModalTransitioning = true;
            this.classList.add('modal-ready');
        });
        
        modal.addEventListener('shown.bs.modal', function() {
            isModalTransitioning = false;
        });
        
        modal.addEventListener('hide.bs.modal', function(e) {
            if (isModalTransitioning) {
                e.preventDefault();
                return;
            }
            isModalTransitioning = true;
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            isModalTransitioning = false;
            this.classList.remove('modal-ready');
        });

        // Empêcher la fermeture en cliquant en dehors
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                e.preventDefault();
            }
        });
    });

    // Empêcher la fermeture avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                e.preventDefault();
            }
        }
    });
});
</script>

<style>
.modal {
    transition: opacity 0.15s linear;
    pointer-events: none;
}
.modal.show {
    pointer-events: auto;
}
.modal-dialog {
    transition: transform 0.15s ease-out;
}
.modal.fade .modal-dialog {
    transform: scale(0.98);
}
.modal.show .modal-dialog {
    transform: scale(1);
}
.modal-backdrop {
    transition: opacity 0.15s linear;
}
</style>