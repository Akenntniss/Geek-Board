<?php
// Vérifier si l'ID du client est fourni
if (!isset($_GET['client_id'])) {
    set_message("Client non spécifié", "danger");
    redirect("clients");
}

$client_id = (int)$_GET['client_id'];

// Récupérer les informations du client
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT nom, prenom, telephone, email FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        set_message("Client non trouvé", "danger");
        redirect("clients");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations du client", "danger");
    redirect("clients");
}

// Récupérer l'historique des réparations
try {
    $stmt = $shop_pdo->prepare("
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
    $stmt->execute([$client_id]);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération de l'historique", "danger");
    redirect("clients");
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Historique des réparations</h4>
                    <a href="index.php?page=clients" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    <!-- Informations du client -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Informations du client</h5>
                            <p><strong>Nom :</strong> <?php echo htmlspecialchars($client['nom']); ?></p>
                            <p><strong>Prénom :</strong> <?php echo htmlspecialchars($client['prenom']); ?></p>
                            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($client['telephone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Contact</h5>
                            <p><strong>Email :</strong> <?php echo htmlspecialchars($client['email']); ?></p>
                        </div>
                    </div>

                    <!-- Liste des réparations -->
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
                                <?php if (empty($reparations)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune réparation trouvée pour ce client</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reparations as $reparation): ?>
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
                                                <a href="index.php?page=reparation&id=<?php echo $reparation['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
    </div>
</div> 