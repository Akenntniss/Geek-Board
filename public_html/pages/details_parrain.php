<?php
// Vérifier si un ID de parrain est spécifié
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Rediriger vers la page de recherche
    redirect("recherche_parrainage");
    exit;
}

$parrain_id = (int)$_GET['id'];

// Récupérer les informations du client
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$parrain_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        set_message("Client non trouvé.", "danger");
        redirect("recherche_parrainage");
        exit;
    }
    
    // Vérifier si le client est inscrit au programme de parrainage
    if (!isset($client['inscrit_parrainage']) || !$client['inscrit_parrainage']) {
        set_message("Ce client n'est pas inscrit au programme de parrainage.", "warning");
    }
    
    // Récupérer les informations de parrainage
    $info_parrain = get_info_parrain($parrain_id);
    
    // Récupérer la liste des filleuls
    $filleuls = get_filleuls_info($parrain_id);
    
    // Récupérer l'historique des réductions
    $stmt_reductions = $shop_pdo->prepare("
        SELECT pr.*, r.prix_reparation, r.id as reparation_id, r.date_reception
        FROM parrainage_reductions pr
        LEFT JOIN reparations r ON pr.reparation_utilisee_id = r.id
        WHERE pr.parrain_id = ?
        ORDER BY pr.date_creation DESC
    ");
    $stmt_reductions->execute([$parrain_id]);
    $reductions = $stmt_reductions->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations: " . $e->getMessage(), "danger");
    redirect("gestion_parrainage");
    exit;
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Détails du Parrain: <?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?>
        </h1>
        <a href="index.php?page=gestion_parrainage" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Retour
        </a>
    </div>

    <div class="row">
        <!-- Informations du client -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informations du client</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="text-gray-900"><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></h5>
                        <?php if ($client['inscrit_parrainage']): ?>
                            <span class="badge bg-success mb-2">Inscrit au programme</span>
                        <?php else: ?>
                            <span class="badge bg-danger mb-2">Non inscrit au programme</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-phone me-2"></i> Téléphone:</strong> 
                        <span><?php echo htmlspecialchars($client['telephone']); ?></span>
                    </div>
                    
                    <?php if (!empty($client['email'])): ?>
                    <div class="mb-3">
                        <strong><i class="fas fa-envelope me-2"></i> Email:</strong> 
                        <span><?php echo htmlspecialchars($client['email']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($client['inscrit_parrainage'] && !empty($client['code_parrainage'])): ?>
                    <div class="mb-3">
                        <strong><i class="fas fa-tag me-2"></i> Code de parrainage:</strong> 
                        <span class="badge bg-info"><?php echo htmlspecialchars($client['code_parrainage']); ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-calendar me-2"></i> Date d'inscription:</strong> 
                        <span><?php echo !empty($client['date_inscription_parrainage']) ? format_date($client['date_inscription_parrainage']) : 'Non disponible'; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <a href="index.php?page=modifier_client&id=<?php echo $parrain_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-edit me-1"></i> Modifier le client
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques de parrainage -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistiques de parrainage</h6>
                </div>
                <div class="card-body">
                    <?php if ($client['inscrit_parrainage']): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fas fa-users me-2"></i> Nombre de filleuls:</span>
                            <span class="badge bg-primary" style="font-size: 1rem;"><?php echo count($filleuls); ?></span>
                        </div>
                        
                        <?php
                        // Récupérer la configuration
                        $stmt_config = $shop_pdo->query("SELECT nombre_filleuls_requis FROM parrainage_config ORDER BY id DESC LIMIT 1");
                        $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
                        $filleuls_requis = $config['nombre_filleuls_requis'] ?? 1;
                        
                        // Calculer le nombre de filleuls manquants
                        $filleuls_manquants = max(0, $filleuls_requis - count($filleuls));
                        $pourcentage = $filleuls_requis > 0 ? min(100, (count($filleuls) / $filleuls_requis) * 100) : 0;
                        ?>
                        
                        <div class="mb-3">
                            <span><i class="fas fa-chart-bar me-2"></i> Progression:</span>
                            <div class="progress mt-2" style="height: 15px;">
                                <div class="progress-bar progress-bar-striped <?php echo count($filleuls) >= $filleuls_requis ? 'bg-success' : 'bg-info'; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $pourcentage; ?>%;" 
                                     aria-valuenow="<?php echo count($filleuls); ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="<?php echo $filleuls_requis; ?>">
                                    <?php echo count($filleuls); ?> / <?php echo $filleuls_requis; ?>
                                </div>
                            </div>
                            
                            <?php if ($filleuls_manquants > 0): ?>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i> 
                                    Encore <?php echo $filleuls_manquants; ?> filleul(s) pour bénéficier des réductions.
                                </small>
                            <?php else: ?>
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i> 
                                    Ce parrain a atteint le nombre requis de filleuls.
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fas fa-percentage me-2"></i> Réductions générées:</span>
                            <span class="badge bg-info" style="font-size: 1rem;"><?php echo count($reductions); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check-circle me-2"></i> Réductions utilisées:</span>
                            <span class="badge bg-success" style="font-size: 1rem;">
                                <?php 
                                $utilisees = array_filter($reductions, function($r) { return $r['utilise'] == 1; });
                                echo count($utilisees); 
                                ?>
                            </span>
                        </div>
                        
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                            <p class="text-muted">Ce client n'est pas inscrit au programme de parrainage.</p>
                            <button class="btn btn-primary btn-sm mt-2" id="inscrireParrainBtn">
                                <i class="fas fa-user-plus me-1"></i> Inscrire au programme
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Liste des filleuls -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Liste des filleuls</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addFilleulModal">
                        <i class="fas fa-user-plus me-1"></i> Ajouter un filleul
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($filleuls)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="filleulsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Téléphone</th>
                                        <th>Email</th>
                                        <th>Date de parrainage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filleuls as $filleul): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($filleul['prenom'] . ' ' . $filleul['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($filleul['telephone']); ?></td>
                                            <td><?php echo !empty($filleul['email']) ? htmlspecialchars($filleul['email']) : '-'; ?></td>
                                            <td><?php echo format_date($filleul['date_parrainage']); ?></td>
                                            <td>
                                                <a href="index.php?page=modifier_client&id=<?php echo $filleul['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">Ce parrain n'a pas encore de filleuls.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Historique des réductions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Historique des réductions</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($reductions)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="reductionsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Pourcentage</th>
                                        <th>Montant max</th>
                                        <th>Utilisée</th>
                                        <th>Réparation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reductions as $reduction): ?>
                                        <tr>
                                            <td><?php echo format_date($reduction['date_creation']); ?></td>
                                            <td><?php echo $reduction['pourcentage_reduction']; ?> %</td>
                                            <td><?php echo number_format($reduction['montant_reduction_max'], 2, ',', ' '); ?> €</td>
                                            <td>
                                                <?php if ($reduction['utilise']): ?>
                                                    <span class="badge bg-success">Oui</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Non</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($reduction['utilise'] && $reduction['reparation_id']): ?>
                                                    <a href="index.php?page=reparations&open_modal=<?php echo $reduction['reparation_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye me-1"></i> #<?php echo $reduction['reparation_id']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-percentage fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">Aucune réduction générée pour ce parrain.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un filleul -->
<div class="modal fade" id="addFilleulModal" tabindex="-1" aria-labelledby="addFilleulModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFilleulModalLabel">Ajouter un filleul</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="searchFilleul" class="form-label">Rechercher un client existant</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchFilleul" placeholder="Nom, prénom ou téléphone...">
                        <button class="btn btn-outline-secondary" type="button" id="searchFilleulBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div id="filleulSearchResults" class="d-none mb-3">
                    <!-- Les résultats seront affichés ici -->
                </div>
                
                <div class="text-center py-3 d-none" id="noFilleulResultsMsg">
                    <i class="fas fa-user-slash text-warning mb-2"></i>
                    <p>Aucun client trouvé avec ces critères.</p>
                    <button type="button" class="btn btn-primary btn-sm" id="createNewFilleulBtn">
                        <i class="fas fa-user-plus me-1"></i> Créer un nouveau client
                    </button>
                </div>
                
                <form id="newFilleulForm" class="d-none">
                    <h6 class="text-primary mb-3">Nouveau client</h6>
                    <div class="mb-3">
                        <label for="newFilleulNom" class="form-label">Nom*</label>
                        <input type="text" class="form-control" id="newFilleulNom" required>
                    </div>
                    <div class="mb-3">
                        <label for="newFilleulPrenom" class="form-label">Prénom*</label>
                        <input type="text" class="form-control" id="newFilleulPrenom" required>
                    </div>
                    <div class="mb-3">
                        <label for="newFilleulTelephone" class="form-label">Téléphone*</label>
                        <input type="tel" class="form-control" id="newFilleulTelephone" required>
                    </div>
                    <div class="mb-3">
                        <label for="newFilleulEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="newFilleulEmail">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="newFilleulParrainage" checked>
                        <label class="form-check-label" for="newFilleulParrainage">
                            Inscrire au programme de parrainage
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary d-none" id="saveNewFilleulBtn">Enregistrer</button>
                <button type="button" class="btn btn-success d-none" id="confirmFilleulBtn">Confirmer le parrainage</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for the page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const parrainId = <?php echo $parrain_id; ?>;
    let selectedFilleulId = null;
    
    // Initialiser DataTables
    if (document.getElementById('filleulsTable')) {
        $('#filleulsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
            }
        });
    }
    
    if (document.getElementById('reductionsTable')) {
        $('#reductionsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
            },
            order: [[0, 'desc']]
        });
    }
    
    // Recherche de filleul
    document.getElementById('searchFilleulBtn').addEventListener('click', searchFilleul);
    document.getElementById('searchFilleul').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchFilleul();
        }
    });
    
    // Bouton pour créer un nouveau filleul
    document.getElementById('createNewFilleulBtn').addEventListener('click', function() {
        document.getElementById('noFilleulResultsMsg').classList.add('d-none');
        document.getElementById('newFilleulForm').classList.remove('d-none');
        document.getElementById('saveNewFilleulBtn').classList.remove('d-none');
        document.getElementById('confirmFilleulBtn').classList.add('d-none');
    });
    
    // Bouton pour sauvegarder un nouveau filleul
    document.getElementById('saveNewFilleulBtn').addEventListener('click', saveNewFilleul);
    
    // Bouton pour confirmer un parrainage
    document.getElementById('confirmFilleulBtn').addEventListener('click', confirmParrainage);
    
    // Fonction pour rechercher un client
    function searchFilleul() {
        const searchTerm = document.getElementById('searchFilleul').value.trim();
        if (searchTerm.length < 2) {
            alert('Veuillez saisir au moins 2 caractères pour la recherche');
            return;
        }
        
        const resultsContainer = document.getElementById('filleulSearchResults');
        const noResultsMsg = document.getElementById('noFilleulResultsMsg');
        
        resultsContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Recherche en cours...</div>';
        resultsContainer.classList.remove('d-none');
        noResultsMsg.classList.add('d-none');
        
        // Masquer les autres éléments
        document.getElementById('newFilleulForm').classList.add('d-none');
        document.getElementById('saveNewFilleulBtn').classList.add('d-none');
        document.getElementById('confirmFilleulBtn').classList.add('d-none');
        
        // Appel AJAX pour rechercher
        fetch('ajax/search_clients.php?term=' + encodeURIComponent(searchTerm))
            .then(response => response.json())
            .then(data => {
                resultsContainer.innerHTML = '';
                
                if (data.length === 0) {
                    // Aucun résultat
                    resultsContainer.classList.add('d-none');
                    noResultsMsg.classList.remove('d-none');
                } else {
                    // Afficher les résultats
                    const table = document.createElement('table');
                    table.className = 'table table-hover';
                    table.innerHTML = `
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Téléphone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `;
                    
                    const tbody = table.querySelector('tbody');
                    
                    data.forEach(client => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${client.prenom} ${client.nom}</td>
                            <td>${client.telephone}</td>
                            <td>
                                <button class="btn btn-sm btn-primary select-filleul" data-id="${client.id}">
                                    <i class="fas fa-user-plus"></i> Sélectionner
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                    
                    resultsContainer.appendChild(table);
                    
                    // Ajouter des écouteurs d'événements aux boutons de sélection
                    const selectButtons = resultsContainer.querySelectorAll('.select-filleul');
                    selectButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const clientId = this.getAttribute('data-id');
                            selectFilleul(clientId);
                        });
                    });
                }
            })
            .catch(error => {
                console.error('Erreur de recherche:', error);
                resultsContainer.innerHTML = '<div class="alert alert-danger">Erreur lors de la recherche. Veuillez réessayer.</div>';
            });
    }
    
    // Fonction pour sélectionner un filleul
    function selectFilleul(clientId) {
        selectedFilleulId = clientId;
        document.getElementById('filleulSearchResults').classList.add('d-none');
        document.getElementById('newFilleulForm').classList.add('d-none');
        document.getElementById('saveNewFilleulBtn').classList.add('d-none');
        document.getElementById('confirmFilleulBtn').classList.remove('d-none');
    }
    
    // Fonction pour enregistrer un nouveau filleul
    function saveNewFilleul() {
        const nom = document.getElementById('newFilleulNom').value.trim();
        const prenom = document.getElementById('newFilleulPrenom').value.trim();
        const telephone = document.getElementById('newFilleulTelephone').value.trim();
        const email = document.getElementById('newFilleulEmail').value.trim();
        const inscritParrainage = document.getElementById('newFilleulParrainage').checked;
        
        if (!nom || !prenom || !telephone) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        // Appel AJAX pour créer le client
        fetch('ajax/ajouter_client.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                nom: nom,
                prenom: prenom,
                telephone: telephone,
                email: email,
                inscrit_parrainage: inscritParrainage,
                parrain_id: parrainId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Client ajouté avec succès!');
                // Recharger la page pour montrer le nouveau filleul
                window.location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'ajout du client');
        });
    }
    
    // Fonction pour confirmer un parrainage
    function confirmParrainage() {
        if (!selectedFilleulId) {
            alert('Veuillez sélectionner un client');
            return;
        }
        
        // Appel AJAX pour créer la relation de parrainage
        fetch('ajax/creer_parrainage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                parrain_id: parrainId,
                filleul_id: selectedFilleulId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Parrainage créé avec succès!');
                // Recharger la page pour montrer le nouveau filleul
                window.location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la création du parrainage');
        });
    }
    
    // Bouton pour inscrire un client au programme
    if (document.getElementById('inscrireParrainBtn')) {
        document.getElementById('inscrireParrainBtn').addEventListener('click', function() {
            if (confirm('Voulez-vous vraiment inscrire ce client au programme de parrainage?')) {
                // Appel AJAX pour inscrire le client
                fetch('ajax/inscrire_parrainage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        client_id: parrainId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Client inscrit avec succès!');
                        window.location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de l\'inscription du client');
                });
            }
        });
    }
});
</script> 