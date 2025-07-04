<!-- MODAL DE RECHERCHE UNIVERSELLE AVEC ONGLETS -->
<div class="modal fade" id="rechercheModal" tabindex="-1" aria-labelledby="rechercheModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="rechercheModalLabel">
                    <i class="fas fa-search me-2"></i>Recherche Universelle
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Zone de recherche -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-lg" id="rechercheInput" 
                               placeholder="Rechercher par nom client, téléphone, modèle, problème ou pièce commandée...">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary btn-lg w-100" id="rechercheBtn">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                </div>
                
                <!-- Zone de chargement -->
                <div id="rechercheLoading" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Recherche en cours...</span>
                    </div>
                    <p class="mt-2">Recherche en cours...</p>
                </div>
                
                <!-- Onglets des résultats -->
                <div id="rechercheResults" style="display: none;">
                    <!-- Navigation des onglets -->
                    <ul class="nav nav-tabs mb-3" id="resultTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients-pane" type="button" role="tab">
                                <i class="fas fa-users me-2"></i>Clients <span id="clientsCount" class="badge bg-secondary ms-1">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reparations-tab" data-bs-toggle="tab" data-bs-target="#reparations-pane" type="button" role="tab">
                                <i class="fas fa-wrench me-2"></i>Réparations <span id="reparationsCount" class="badge bg-secondary ms-1">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="commandes-tab" data-bs-toggle="tab" data-bs-target="#commandes-pane" type="button" role="tab">
                                <i class="fas fa-box me-2"></i>Commandes <span id="commandesCount" class="badge bg-secondary ms-1">0</span>
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Contenu des onglets -->
                    <div class="tab-content" id="resultTabContent">
                        <!-- Onglet Clients -->
                        <div class="tab-pane fade show active" id="clients-pane" role="tabpanel">
                            <div id="clientsContent">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nom</th>
                                                <th>Téléphone</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="clientsTableBody">
                                            <!-- Résultats clients -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Réparations -->
                        <div class="tab-pane fade" id="reparations-pane" role="tabpanel">
                            <div id="reparationsContent">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Client</th>
                                                <th>Appareil</th>
                                                <th>Problème</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reparationsTableBody">
                                            <!-- Résultats réparations -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Commandes -->
                        <div class="tab-pane fade" id="commandes-pane" role="tabpanel">
                            <div id="commandesContent">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Réparation</th>
                                                <th>Pièce</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="commandesTableBody">
                                            <!-- Résultats commandes -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Zone vide -->
                <div id="rechercheEmpty" style="display: none;">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun résultat trouvé</h5>
                        <p class="text-muted">Essayez avec d'autres termes de recherche</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div> 