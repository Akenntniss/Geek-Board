<?php
/**
 * Composant des boutons d'action rapide
 * Utilise les styles définis dans dashboard-new.css
 */
?>

<div class="quick-actions-grid">
    <!-- Rechercher (remplace Rechercher client) -->
    <a href="#" class="action-card action-primary" data-bs-toggle="modal" data-bs-target="#rechercheAvanceeModal">
        <div class="action-icon">
            <i class="fas fa-search"></i>
        </div>
        <div class="action-text">Rechercher</div>
    </a>

    <!-- Nouvelle tâche -->
    <a href="index.php?page=ajouter_tache" class="action-card action-info">
        <div class="action-icon">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="action-text">Nouvelle tâche</div>
    </a>

    <!-- Nouvelle réparation -->
    <a href="index.php?page=ajouter_reparation" class="action-card action-success">
        <div class="action-icon">
            <i class="fas fa-tools"></i>
        </div>
        <div class="action-text">Nouvelle réparation</div>
    </a>

    <!-- Nouvelle commande - Modifié pour ouvrir le modal -->
    <a href="#" class="action-card action-warning" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
        <div class="action-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="action-text">Nouvelle commande</div>
    </a>
</div>

<!-- Modal Recherche Avancée -->
<div class="modal fade" id="rechercheAvanceeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center fw-bold">
                    <i class="fas fa-search me-2"></i>
                    Recherche universelle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Barre de recherche -->
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 rounded-start-3 shadow-sm">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg border-0 shadow-sm search-input" id="recherche_avancee" placeholder="Rechercher client, réparation, commande, appareil...">
                        <button class="btn btn-primary rounded-end-3 shadow-sm px-4" id="btn-recherche-avancee">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                    </div>
                    <div class="mt-2 text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Recherchez par nom, prénom, téléphone, ID de réparation, modèle, appareil ou problème
                    </div>
                </div>

                <!-- Résultats de recherche - Onglets simplifiés -->
                <div class="recherche-resultats d-none" id="resultats_recherche">
                    <!-- Boutons de navigation simplifiés -->
                    <div class="d-flex gap-2 mb-4" id="rechercheBtns">
                        <button class="btn btn-primary flex-fill py-2" id="btn-clients" onclick="showResultTab('clients-container')">
                            <i class="fas fa-users me-2"></i>Clients <span class="badge bg-white text-primary ms-1" id="count-clients">0</span>
                        </button>
                        <button class="btn btn-outline-primary flex-fill py-2" id="btn-reparations" onclick="showResultTab('reparations-container')">
                            <i class="fas fa-tools me-2"></i>Réparations <span class="badge bg-white text-primary ms-1" id="count-reparations">0</span>
                        </button>
                        <button class="btn btn-outline-primary flex-fill py-2" id="btn-commandes" onclick="showResultTab('commandes-container')">
                            <i class="fas fa-shopping-cart me-2"></i>Commandes <span class="badge bg-white text-primary ms-1" id="count-commandes">0</span>
                        </button>
                    </div>

                    <!-- Conteneurs de résultats -->
                    <div class="position-relative">
                        <!-- Clients -->
                        <div id="clients-container" class="result-container">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th class="ps-3">Nom</th>
                                                <th>Prénom</th>
                                                <th>Téléphone</th>
                                                <th class="text-end pe-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="liste_clients_recherche">
                                            <!-- Les résultats seront ajoutés ici -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Réparations -->
                        <div id="reparations-container" class="result-container" style="display: none;">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th class="ps-3">ID</th>
                                                <th>Client</th>
                                                <th>Appareil</th>
                                                <th>Modèle</th>
                                                <th>Statut</th>
                                                <th class="text-end pe-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="liste_reparations_recherche">
                                            <!-- Les résultats seront ajoutés ici -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Commandes -->
                        <div id="commandes-container" class="result-container" style="display: none;">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th class="ps-3">Référence</th>
                                                <th>Client</th>
                                                <th>Pièce</th>
                                                <th>Date</th>
                                                <th>Statut</th>
                                                <th class="text-end pe-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="liste_commandes_recherche">
                                            <!-- Les résultats seront ajoutés ici -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message aucun résultat -->
                <div id="aucun_resultat_trouve" class="alert alert-light border-0 shadow-sm d-none rounded-4">
                    <div class="d-flex align-items-center">
                        <div class="alert-icon bg-warning bg-opacity-10 text-warning rounded-circle p-3 me-3">
                            <i class="fas fa-exclamation-circle fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="alert-heading mb-1">Aucun résultat trouvé</h6>
                            <p class="mb-0 text-muted">Essayez d'autres termes de recherche ou vérifiez l'orthographe</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Ajouter la classe 'futuristic-modal' au modal de recherche universelle au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        const rechercheModal = document.getElementById('rechercheAvanceeModal');
        if (rechercheModal) {
            rechercheModal.classList.add('futuristic-modal');
            
            // Créer des particules flottantes si la fonction existe
            if (typeof createParticles === 'function') {
                createParticles(rechercheModal);
            }
            
            // Ajouter des effets de pulsation aux boutons
            const actionButtons = rechercheModal.querySelectorAll('#rechercheBtns button, #btn-recherche-avancee');
            actionButtons.forEach(btn => {
                btn.classList.add('pulse-effect');
            });
        }
    });

    function showResultTab(tabId) {
        // Masquer tous les conteneurs
        document.querySelectorAll('.result-container').forEach(container => {
            container.style.display = 'none';
        });
        
        // Afficher le conteneur demandé
        const targetContainer = document.getElementById(tabId);
        if (targetContainer) {
            targetContainer.style.display = 'block';
        }
        
        // Mettre à jour les boutons
        document.querySelectorAll('#rechercheBtns button').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        // Activer le bouton correspondant
        if (tabId === 'clients-container') {
            document.getElementById('btn-clients').classList.remove('btn-outline-primary');
            document.getElementById('btn-clients').classList.add('btn-primary');
        } else if (tabId === 'reparations-container') {
            document.getElementById('btn-reparations').classList.remove('btn-outline-primary');
            document.getElementById('btn-reparations').classList.add('btn-primary');
        } else if (tabId === 'commandes-container') {
            document.getElementById('btn-commandes').classList.remove('btn-outline-primary');
            document.getElementById('btn-commandes').classList.add('btn-primary');
        }
        
        // Ajouter un effet holographique temporaire
        if (typeof startProcessingEffect === 'function') {
            startProcessingEffect('rechercheAvanceeModal');
        }
    }
</script>

<!-- Ancien Modal Recherche Client - Gardé pour référence mais remplacé par la recherche avancée -->
<div class="modal fade" id="rechercheClientModal" tabindex="-1" style="display: none;">
    <!-- Ce modal est masqué et conservé temporairement pour compatibilité -->
</div>

