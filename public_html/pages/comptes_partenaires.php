<?php
// Définir la page actuelle pour le menu
$current_page = 'comptes_partenaires';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Récupérer les partenaires
$partenaires = [];
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("SELECT p.*, 
        COALESCE(s.solde_actuel, 0) as solde
        FROM partenaires p 
        LEFT JOIN soldes_partenaires s ON p.id = s.partenaire_id
        WHERE p.actif = TRUE
        ORDER BY p.nom");
    $partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des partenaires: " . $e->getMessage(), "danger");
}

// Calculer les statistiques
$total_solde_positif = 0;
$total_solde_negatif = 0;
$nombre_partenaires_actifs = 0;

foreach ($partenaires as $partenaire) {
    if ($partenaire['solde'] > 0) {
        $total_solde_positif += $partenaire['solde'];
    } else {
        $total_solde_negatif += abs($partenaire['solde']);
    }
    if ($partenaire['actif']) {
        $nombre_partenaires_actifs++;
    }
}
?>

<div class="content-wrapper" style="margin-top: 60px;">
    <!-- En-tête et actions principales -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-handshake text-primary me-2"></i>
            Comptes Partenaires
        </h1>
        <div>
            <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#gererPartenairesModal">
                <i class="fas fa-users-cog me-1"></i> Gérer les Partenaires
            </button>
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#ajouterTransactionModal">
                <i class="fas fa-plus me-1"></i> Nouvelle Transaction
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ajouterServiceModal">
                <i class="fas fa-tools me-1"></i> Nouveau Service
            </button>
        </div>
    </div>

    <?php echo display_message(); ?>

    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-users me-2"></i>
                        Partenaires Actifs
                    </h6>
                    <h3 class="mb-0"><?php echo $nombre_partenaires_actifs; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-arrow-up me-2"></i>
                        Total Créances
                    </h6>
                    <h3 class="mb-0"><?php echo number_format($total_solde_positif, 2); ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-arrow-down me-2"></i>
                        Total Dettes
                    </h6>
                    <h3 class="mb-0"><?php echo number_format($total_solde_negatif, 2); ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-balance-scale me-2"></i>
                        Balance Globale
                    </h6>
                    <h3 class="mb-0"><?php echo number_format($total_solde_positif - $total_solde_negatif, 2); ?> €</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des partenaires et leurs soldes -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Liste des Partenaires</h5>
                </div>
                <div class="col-auto">
                    <div class="input-group">
                        <input type="text" id="searchPartenaire" class="form-control" placeholder="Rechercher un partenaire...">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tablePartenaires">
                    <thead>
                        <tr>
                            <th>Partenaire</th>
                            <th>Solde Actuel</th>
                            <th>Dernière Transaction</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($partenaires)): ?>
                            <?php foreach ($partenaires as $partenaire): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-building text-primary me-2"></i>
                                            <?php echo htmlspecialchars($partenaire['nom']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo ($partenaire['solde'] < 0) ? 'bg-danger' : 'bg-success'; ?> rounded-pill">
                                            <?php echo number_format($partenaire['solde'] ?? 0, 2); ?> €
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        try {
                                            $stmt = $shop_pdo->prepare("
                                                SELECT date_transaction, montant, type 
                                                FROM transactions_partenaires 
                                                WHERE partenaire_id = ? 
                                                ORDER BY date_transaction DESC 
                                                LIMIT 1
                                            ");
                                            $stmt->execute([$partenaire['id']]);
                                            $derniere_transaction = $stmt->fetch();
                                            
                                            if ($derniere_transaction) {
                                                echo date('d/m/Y H:i', strtotime($derniere_transaction['date_transaction']));
                                                echo ' - ';
                                                echo number_format($derniere_transaction['montant'], 2) . ' €';
                                                echo ' (' . $derniere_transaction['type'] . ')';
                                            } else {
                                                echo '<span class="text-muted">Aucune transaction</span>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<span class="text-danger">Erreur</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-transactions" 
                                                    data-partenaire-id="<?php echo $partenaire['id']; ?>"
                                                    data-partenaire-nom="<?php echo htmlspecialchars($partenaire['nom']); ?>">
                                                <i class="fas fa-history me-1"></i> Historique
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <p class="mb-0">Aucun partenaire enregistré</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Transaction -->
<div class="modal fade" id="ajouterTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Nouvelle Transaction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterTransactionForm" action="ajax/add_transaction_partenaire.php" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="partenaire_id" class="form-label">
                                    <i class="fas fa-user me-1"></i> Partenaire*
                                </label>
                                <select class="form-select" id="partenaire_id" name="partenaire_id" required>
                                    <option value="">Sélectionner un partenaire</option>
                                    <?php foreach ($partenaires as $partenaire): ?>
                                        <option value="<?php echo $partenaire['id']; ?>" 
                                                data-solde="<?php echo $partenaire['solde']; ?>">
                                            <?php echo htmlspecialchars($partenaire['nom']); ?>
                                            (Solde: <?php echo number_format($partenaire['solde'] ?? 0, 2); ?> €)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="soldeActuel" class="form-text mt-2"></div>
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">
                                    <i class="fas fa-tag me-1"></i> Type de transaction*
                                </label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="type" id="typeAvance" value="AVANCE" required checked>
                                    <label class="btn btn-outline-success" for="typeAvance">
                                        <i class="fas fa-arrow-up me-1"></i> Avance
                                    </label>
                                    <input type="radio" class="btn-check" name="type" id="typeRemboursement" value="REMBOURSEMENT" required>
                                    <label class="btn btn-outline-danger" for="typeRemboursement">
                                        <i class="fas fa-arrow-down me-1"></i> Remboursement
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="montant" class="form-label">
                                    <i class="fas fa-euro-sign me-1"></i> Montant*
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control" id="montant" name="montant" required inputmode="decimal" pattern="[0-9]*[.,]?[0-9]*">
                                    <span class="input-group-text">€</span>
                                </div>
                                <div id="nouveauSolde" class="form-text mt-2"></div>
                                <!-- Clavier virtuel -->
                                <div class="mt-3 virtual-keyboard">
                                    <div class="row g-2">
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">1</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">2</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">3</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">4</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">5</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">6</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">7</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">8</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">9</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">.</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">0</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-warning w-100 key-btn-clear">C</button></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i> Description
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                          placeholder="Détails de la transaction..."></textarea>
                            </div>
                        </div>

                        <!-- Champ date caché -->
                        <input type="hidden" id="date_transaction" name="date_transaction" value="<?php echo date('Y-m-d H:i:s'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <div class="d-flex justify-content-between w-100">
                    <div id="transactionInfo" class="text-muted">
                        <!-- Les informations de la transaction seront affichées ici -->
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Annuler
                        </button>
                        <button type="button" id="btnEnregistrerTransaction" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Service -->
<div class="modal fade" id="ajouterServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tools me-2 text-primary"></i>
                    Nouveau Service
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterServiceForm" action="ajax/add_service_partenaire.php" method="POST">
                    <div class="mb-3">
                        <label for="service_partenaire_id" class="form-label">Partenaire</label>
                        <select class="form-select" id="service_partenaire_id" name="partenaire_id" required>
                            <option value="">Sélectionner un partenaire</option>
                            <?php foreach ($partenaires as $partenaire): ?>
                                <option value="<?php echo $partenaire['id']; ?>">
                                    <?php echo htmlspecialchars($partenaire['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description_service" class="form-label">Description du service</label>
                        <textarea class="form-control" id="description_service" name="description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="montant_service" class="form-label">Montant (€)</label>
                        <input type="number" step="0.01" class="form-control" id="montant_service" name="montant" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="ajouterServiceForm" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Historique des Transactions -->
<div class="modal fade" id="historiqueTransactionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2 text-primary"></i>
                    Historique des Transactions - <span id="partenaireNom"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historiqueTransactions" class="table-responsive">
                    <!-- L'historique sera chargé dynamiquement ici -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gérer les Partenaires -->
<div class="modal fade" id="gererPartenairesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-users-cog me-2 text-primary"></i>
                    Gérer les Partenaires
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterPartenaireModal">
                        <i class="fas fa-user-plus me-1"></i> Ajouter un Partenaire
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="listePartenaires">
                            <!-- La liste des partenaires sera chargée ici dynamiquement -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter Partenaire -->
<div class="modal fade" id="ajouterPartenaireModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2 text-primary"></i>
                    Ajouter un Partenaire
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterPartenaireForm" action="ajax/add_partenaire.php" method="POST">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom*</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone">
                    </div>
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="ajouterPartenaireForm" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<style>
.virtual-keyboard .btn {
    font-size: 1.2rem;
    padding: 0.75rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.virtual-keyboard .key-btn:active {
    transform: translateY(2px);
}

.virtual-keyboard .key-btn-clear {
    font-weight: bold;
}

/* Ajout du décalage de 57px uniquement sur les écrans larges (PC) */
@media (min-width: 992px) {
    .content-wrapper {
        margin-top: 68px !important; /* 60px + 57px */
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des variables
    const transactionForm = document.getElementById('ajouterTransactionForm');
    const btnEnregistrer = document.getElementById('btnEnregistrerTransaction');
    const montantInput = document.getElementById('montant');
    const virtualKeyboard = document.querySelectorPr('.virtual-keyboard');
    const partenaireSelect = document.getElementById('partenaire_id');
    const typeAvance = document.getElementById('typeAvance');
    const typeRemboursement = document.getElementById('typeRemboursement');

    // Gestionnaire pour les boutons d'historique
    document.querySelectorAll('.view-transactions').forEach(button => {
        button.addEventListener('click', function() {
            const partenaireId = this.dataset.partenaireId;
            const partenaireNom = this.dataset.partenaireNom;
            
            // Mettre à jour le nom du partenaire dans le modal
            document.getElementById('partenaireNom').textContent = partenaireNom;
            
            // Charger l'historique des transactions
            fetch(`ajax/get_transactions_partenaire.php?partenaire_id=${partenaireId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const historiqueDiv = document.getElementById('historiqueTransactions');
                        let html = `
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Montant</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        if (data.transactions && data.transactions.length > 0) {
                            data.transactions.forEach(transaction => {
                                const date = new Date(transaction.date_transaction).toLocaleString('fr-FR');
                                const montant = parseFloat(transaction.montant).toFixed(2);
                                const typeClass = transaction.type === 'REMBOURSEMENT' ? 'text-danger' : 'text-success';
                                const montantPrefix = transaction.type === 'REMBOURSEMENT' ? '-' : '+';
                                
                                html += `
                                    <tr>
                                        <td>${date}</td>
                                        <td>${transaction.type}</td>
                                        <td class="${typeClass}">${montantPrefix}${montant} €</td>
                                        <td>${transaction.description || ''}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            html += `
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-history fa-3x mb-3"></i>
                                            <p class="mb-0">Aucune transaction</p>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }
                        
                        html += `
                                </tbody>
                            </table>
                        `;
                        
                        historiqueDiv.innerHTML = html;
                        
                        // Afficher le solde actuel
                        if (data.solde !== undefined) {
                            const soldeClass = parseFloat(data.solde) >= 0 ? 'text-success' : 'text-danger';
                            historiqueDiv.insertAdjacentHTML('beforebegin', `
                                <div class="alert alert-info mb-3">
                                    <strong>Solde actuel : </strong>
                                    <span class="${soldeClass}">${parseFloat(data.solde).toFixed(2)} €</span>
                                </div>
                            `);
                        }
                    } else {
                        document.getElementById('historiqueTransactions').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Erreur lors du chargement des transactions
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('historiqueTransactions').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erreur lors du chargement des transactions
                        </div>
                    `;
                });
            
            // Ouvrir le modal
            const modal = new bootstrap.Modal(document.getElementById('historiqueTransactionsModal'));
            modal.show();
        });
    });

    // Gestionnaire du bouton Enregistrer
    if (btnEnregistrer) {
        btnEnregistrer.addEventListener('click', function() {
            console.log('Bouton Enregistrer cliqué');
            
            if (!transactionForm) {
                console.error('Le formulaire n\'existe pas');
                return;
            }
            
            // Vérifier la validité du formulaire
            if (!transactionForm.checkValidity()) {
                console.log('Formulaire invalide');
                transactionForm.reportValidity();
                return;
            }

            // Log des données du formulaire
            const formData = new FormData(transactionForm);
            console.log('Données du formulaire:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            // Désactiver le bouton pendant la soumission
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Traitement...';

            console.log('URL de soumission:', transactionForm.action);

            // Envoyer la requête
            fetch(transactionForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Status de la réponse:', response.status);
                return response.json().catch(error => {
                    console.error('Erreur parsing JSON:', error);
                    throw new Error('Erreur lors du parsing de la réponse JSON');
                });
            })
            .then(data => {
                console.log('Réponse du serveur:', data);
                if (data.success) {
                    showNotification('Transaction enregistrée avec succès', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterTransactionModal'));
                    modal.hide();
                    transactionForm.reset();
                    window.location.reload();
                } else {
                    console.error('Erreur serveur:', data.message);
                    showNotification(data.message || 'Erreur lors de l\'enregistrement de la transaction', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur fetch:', error);
                showNotification('Une erreur est survenue lors de l\'enregistrement', 'danger');
            })
            .finally(() => {
                console.log('Requête terminée');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
            });
        });
    }

    // Gestion du clavier virtuel
    if (virtualKeyboard && montantInput) {
        virtualKeyboard.addEventListener('click', function(e) {
            const button = e.target.closest('.key-btn, .key-btn-clear');
            if (!button) return;
            
            e.preventDefault();
            
            if (button.classList.contains('key-btn-clear')) {
                montantInput.value = '';
            } else {
                const key = button.textContent;
                const currentValue = montantInput.value;
                
                if (key === '.' && currentValue.includes('.')) return;
                if (currentValue.includes('.') && currentValue.split('.')[1]?.length >= 2) return;
                
                montantInput.value = currentValue + key;
            }
            
            montantInput.dispatchEvent(new Event('input'));
            updateSoldeInfo();
        });
    }

    // Mise à jour des informations de solde
    function updateSoldeInfo() {
        if (!partenaireSelect || !montantInput) return;

        const soldeActuelDiv = document.getElementById('soldeActuel');
        const nouveauSoldeDiv = document.getElementById('nouveauSolde');
        const transactionInfoDiv = document.getElementById('transactionInfo');
        const selectedOption = partenaireSelect.selectedOptions[0];

        if (selectedOption && selectedOption.value) {
            const soldeActuel = parseFloat(selectedOption.dataset.solde);
            const montant = parseFloat(montantInput.value) || 0;
            const isAvance = typeAvance.checked;

            if (!isNaN(soldeActuel)) {
                soldeActuelDiv.innerHTML = `
                    <i class="fas fa-info-circle me-1"></i>
                    Solde actuel: <strong class="${soldeActuel < 0 ? 'text-danger' : 'text-success'}">
                        ${soldeActuel.toFixed(2)} €
                    </strong>`;

                if (montant > 0) {
                    const nouveauSolde = isAvance ? soldeActuel + montant : soldeActuel - montant;
                    nouveauSoldeDiv.innerHTML = `
                        <i class="fas fa-calculator me-1"></i>
                        Nouveau solde estimé: <strong class="${nouveauSolde < 0 ? 'text-danger' : 'text-success'}">
                            ${nouveauSolde.toFixed(2)} €
                        </strong>`;

                    transactionInfoDiv.innerHTML = `
                        <i class="fas fa-info-circle me-1"></i>
                        Impact: <strong class="${isAvance ? 'text-success' : 'text-danger'}">
                            ${isAvance ? '+' : '-'}${montant.toFixed(2)} €
                        </strong>`;
                }
            }
        }
    }

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // Événements pour mettre à jour les informations
    if (partenaireSelect) partenaireSelect.addEventListener('change', updateSoldeInfo);
    if (montantInput) {
        montantInput.addEventListener('input', updateSoldeInfo);
        montantInput.addEventListener('keydown', e => e.preventDefault());
    }
    if (typeAvance) typeAvance.addEventListener('change', updateSoldeInfo);
    if (typeRemboursement) typeRemboursement.addEventListener('change', updateSoldeInfo);
});
</script> 