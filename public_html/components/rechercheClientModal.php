<?php
/**
 * Composant modal de recherche de client
 * À inclure dans les pages où une recherche client est nécessaire
 */
?>

<!-- Modal Recherche Client -->
<div class="modal fade" id="rechercheClientModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-search me-2"></i>
                    Rechercher un client
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Recherche -->
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 rounded-start-4 shadow-sm">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg border-0 shadow-sm search-input" id="recherche_client_historique" placeholder="Rechercher par nom, prénom ou téléphone...">
                        <button class="btn btn-primary rounded-end-4 shadow-sm px-4" id="btn-recherche-client-historique">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                    </div>
                </div>

                <!-- Résultats de recherche -->
                <div id="resultats_clients" class="d-none">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">Résultats de la recherche</h6>
                            <span class="badge bg-primary rounded-pill" id="count_resultats"></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nom</th>
                                            <th>Prénom</th>
                                            <th>Téléphone</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="liste_clients">
                                        <!-- Les résultats seront affichés ici dynamiquement -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message aucun résultat -->
                <div id="no_results" class="d-none">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Aucun client trouvé.
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-primary" id="btn_nouveau_client">
                            <i class="fas fa-user-plus me-2"></i>Ajouter un nouveau client
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div> 