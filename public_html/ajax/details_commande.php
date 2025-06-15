<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Non autorisé');
}

// Vérifier si l'ID de la commande est fourni
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('ID de commande manquant');
}

$commande_id = intval($_GET['id']);

try {
    // Récupérer les informations de la commande
    $stmt = $shop_pdo->prepare("
        SELECT cf.*, f.nom as fournisseur_nom, f.email as fournisseur_email, 
               f.telephone as fournisseur_telephone, f.adresse as fournisseur_adresse,
               u.nom as createur_nom
        FROM commandes_fournisseurs cf 
        JOIN fournisseurs f ON cf.fournisseur_id = f.id 
        LEFT JOIN users u ON cf.created_by = u.id
        WHERE cf.id = ?
    ");
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch();
    
    if (!$commande) {
        http_response_code(404);
        exit('Commande non trouvée');
    }
    
    // Récupérer les lignes de la commande
    $stmt = $shop_pdo->prepare("
        SELECT lcf.*, p.nom as produit_nom, p.reference as produit_reference
        FROM lignes_commande_fournisseur lcf 
        JOIN produits p ON lcf.produit_id = p.id 
        WHERE lcf.commande_id = ?
        ORDER BY p.nom ASC
    ");
    $stmt->execute([$commande_id]);
    $lignes = $stmt->fetchAll();
    
    // Calculer les totaux
    $total_produits = count($lignes);
    $total_quantite = array_sum(array_column($lignes, 'quantite'));
    $total_montant = array_sum(array_map(function($ligne) {
        return $ligne['quantite'] * $ligne['prix_unitaire'];
    }, $lignes));
    
    // Formater le statut
    $statut_classes = [
        'en_cours' => 'warning',
        'livree' => 'success',
        'annulee' => 'danger'
    ];
    
    $statut_labels = [
        'en_cours' => 'En cours',
        'livree' => 'Livrée',
        'annulee' => 'Annulée'
    ];
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h6 class="text-muted mb-3">Informations de la Commande</h6>
        <table class="table table-sm">
            <tr>
                <th style="width: 40%">Référence</th>
                <td>CMD-<?php echo str_pad($commande['id'], 6, '0', STR_PAD_LEFT); ?></td>
            </tr>
            <tr>
                <th>Date de commande</th>
                <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
            </tr>
            <tr>
                <th>Livraison prévue</th>
                <td>
                    <?php echo date('d/m/Y', strtotime($commande['date_livraison_prevue'])); ?>
                    <?php if ($commande['statut'] === 'en_cours' && strtotime($commande['date_livraison_prevue']) < time()): ?>
                        <span class="badge bg-danger ms-2">En retard</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Statut</th>
                <td>
                    <span class="badge bg-<?php echo $statut_classes[$commande['statut']] ?? 'secondary'; ?>">
                        <?php echo $statut_labels[$commande['statut']] ?? ucfirst($commande['statut']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Créée par</th>
                <td><?php echo htmlspecialchars($commande['createur_nom']); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-muted mb-3">Informations du Fournisseur</h6>
        <table class="table table-sm">
            <tr>
                <th style="width: 40%">Nom</th>
                <td><?php echo htmlspecialchars($commande['fournisseur_nom']); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td>
                    <a href="mailto:<?php echo htmlspecialchars($commande['fournisseur_email']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($commande['fournisseur_email']); ?>
                    </a>
                </td>
            </tr>
            <tr>
                <th>Téléphone</th>
                <td>
                    <a href="tel:<?php echo htmlspecialchars($commande['fournisseur_telephone']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($commande['fournisseur_telephone']); ?>
                    </a>
                </td>
            </tr>
            <tr>
                <th>Adresse</th>
                <td><?php echo nl2br(htmlspecialchars($commande['fournisseur_adresse'])); ?></td>
            </tr>
        </table>
    </div>
</div>

<h6 class="text-muted mb-3">Produits Commandés</h6>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Référence</th>
                <th>Produit</th>
                <th class="text-end">Prix unitaire</th>
                <th class="text-end">Quantité</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $ligne): ?>
            <tr>
                <td><?php echo htmlspecialchars($ligne['produit_reference']); ?></td>
                <td><?php echo htmlspecialchars($ligne['produit_nom']); ?></td>
                <td class="text-end"><?php echo number_format($ligne['prix_unitaire'], 2); ?> €</td>
                <td class="text-end"><?php echo $ligne['quantite']; ?></td>
                <td class="text-end"><?php echo number_format($ligne['quantite'] * $ligne['prix_unitaire'], 2); ?> €</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-light">
                <td colspan="3">
                    <strong>Total: <?php echo $total_produits; ?> produit(s)</strong>
                </td>
                <td class="text-end">
                    <strong><?php echo $total_quantite; ?></strong>
                </td>
                <td class="text-end">
                    <strong><?php echo number_format($total_montant, 2); ?> €</strong>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<?php if ($commande['notes']): ?>
<div class="mt-4">
    <h6 class="text-muted mb-2">Notes</h6>
    <div class="card">
        <div class="card-body">
            <?php echo nl2br(htmlspecialchars($commande['notes'])); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erreur lors de la récupération des détails de la commande: ' . $e->getMessage());
} 