<?php
/**
 * Composant des boutons d'action rapide
 * Utilise les styles définis dans dashboard-new.css
 */
?>

<div class="quick-actions-grid">
    <!-- Rechercher -->
    <a href="#" class="action-card action-primary" data-bs-toggle="modal" data-bs-target="#rechercheModal">
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

    <!-- Nouvelle commande -->
    <a href="#" class="action-card action-warning" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
        <div class="action-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="action-text">Nouvelle commande</div>
    </a>
</div>

<!-- MODAL DE RECHERCHE UNIVERSELLE -->
<div class="modal fade futuristic-modal" id="rechercheModal" tabindex="-1" aria-labelledby="rechercheModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true" data-modal-type="search">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center fw-bold" id="rechercheModalLabel">
                    <i class="fas fa-search me-2"></i>
                    Recherche Universelle
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Zone de recherche -->
                <div class="search-container mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 rounded-start-4">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="search" class="form-control form-control-lg border-0 search-input" id="searchInput" placeholder="Rechercher des clients, réparations, commandes...">
                        <button class="btn btn-primary rounded-end-4 px-4 search-button" id="btnSearch">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                    </div>
                    <div class="form-text text-muted mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Recherche dans les clients, réparations et commandes
                    </div>
                </div>

                <!-- Indicateur de chargement -->
                <div class="search-status d-flex align-items-center mb-3 d-none">
                    <div class="spinner-border text-primary me-3" role="status">
                        <span class="visually-hidden">Recherche...</span>
                    </div>
                    <span class="text-muted">Recherche en cours...</span>
                </div>

                <!-- Onglets de résultats -->
                <ul class="nav nav-tabs mb-3" id="searchTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients-container" type="button" role="tab">
                            <i class="fas fa-users me-2"></i>
                            Clients
                            <span class="badge bg-primary ms-2" id="count-clients">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reparations-tab" data-bs-toggle="tab" data-bs-target="#reparations-container" type="button" role="tab">
                            <i class="fas fa-tools me-2"></i>
                            Réparations
                            <span class="badge bg-primary ms-2" id="count-reparations">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="commandes-tab" data-bs-toggle="tab" data-bs-target="#commandes-container" type="button" role="tab">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Commandes
                            <span class="badge bg-primary ms-2" id="count-commandes">0</span>
                        </button>
                    </li>
                </ul>

                <!-- Contenu des onglets -->
                <div class="tab-content" id="searchTabContent">
                    <!-- Résultats Clients -->
                    <div class="tab-pane fade show active" id="clients-container" role="tabpanel">
                        <div class="modern-search-results">
                            <div class="results-header">
                                <h6 class="results-title">
                                    <i class="fas fa-users me-2"></i>
                                    Clients trouvés
                                </h6>
                            </div>
                            <div class="modern-table-container">
                                <div id="clientsResults" class="table-responsive">
                                    <!-- Les résultats seront injectés ici -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résultats Réparations -->
                    <div class="tab-pane fade" id="reparations-container" role="tabpanel">
                        <div class="modern-search-results">
                            <div class="results-header">
                                <h6 class="results-title">
                                    <i class="fas fa-tools me-2"></i>
                                    Réparations trouvées
                                </h6>
                            </div>
                            <div class="modern-table-container">
                                <div id="reparationsResults" class="table-responsive">
                                    <!-- Les résultats seront injectés ici -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résultats Commandes -->
                    <div class="tab-pane fade" id="commandes-container" role="tabpanel">
                        <div class="modern-search-results">
                            <div class="results-header">
                                <h6 class="results-title">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Commandes trouvées
                                </h6>
                            </div>
                            <div class="modern-table-container">
                                <div id="commandesResults" class="table-responsive">
                                    <!-- Les résultats seront injectés ici -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques pour le modal de recherche -->
<style>
.futuristic-modal .search-container {
    position: relative;
    margin-bottom: 1.5rem;
}

.futuristic-modal .search-input {
    background-color: var(--search-input-bg, rgba(255, 255, 255, 0.9));
    border: 1px solid var(--search-input-border, rgba(30, 144, 255, 0.2));
    color: var(--search-input-color, #333);
    transition: all 0.3s ease;
}

.futuristic-modal .search-input:focus {
    background-color: var(--search-input-focus-bg, #ffffff);
    border-color: var(--search-input-focus-border, #1e90ff);
    box-shadow: 0 0 0 0.2rem rgba(30, 144, 255, 0.25);
}

.futuristic-modal .search-button {
    background: linear-gradient(135deg, #1e90ff, #0066cc);
    border: none;
    transition: all 0.3s ease;
}

.futuristic-modal .search-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(30, 144, 255, 0.3);
}

.futuristic-modal .nav-tabs {
    border-bottom: 2px solid var(--tab-border-color, rgba(30, 144, 255, 0.1));
}

.futuristic-modal .nav-tabs .nav-link {
    border: none;
    color: var(--tab-color, #666);
    padding: 0.75rem 1.25rem;
    transition: all 0.3s ease;
}

.futuristic-modal .nav-tabs .nav-link:hover {
    color: var(--tab-hover-color, #1e90ff);
    background: var(--tab-hover-bg, rgba(30, 144, 255, 0.05));
}

.futuristic-modal .nav-tabs .nav-link.active {
    color: var(--tab-active-color, #1e90ff);
    background: var(--tab-active-bg, rgba(30, 144, 255, 0.1));
    border-bottom: 2px solid var(--tab-active-border, #1e90ff);
}

.futuristic-modal .modern-table-container {
    background: var(--table-bg, #ffffff);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

/* Mode sombre */
[data-theme="dark"] .futuristic-modal {
    --search-input-bg: rgba(255, 255, 255, 0.05);
    --search-input-color: #ffffff;
    --search-input-border: rgba(255, 255, 255, 0.1);
    --search-input-focus-bg: rgba(255, 255, 255, 0.1);
    --search-input-focus-border: #1e90ff;
    --tab-border-color: rgba(255, 255, 255, 0.1);
    --tab-color: #ffffff;
    --tab-hover-color: #1e90ff;
    --tab-hover-bg: rgba(30, 144, 255, 0.1);
    --tab-active-color: #1e90ff;
    --tab-active-bg: rgba(30, 144, 255, 0.15);
    --tab-active-border: #1e90ff;
    --table-bg: #1a1a1a;
}
</style>



