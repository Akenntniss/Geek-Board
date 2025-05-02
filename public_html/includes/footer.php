<?php
// Fichier footer.php - Les fonctionnalités de navigation ont été supprimées mais on garde la structure HTML correcte
?>

<!-- Scripts nécessaires -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>

<!-- Script pour les notifications toastr -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Configuration de Toastr si disponible
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
    }
        });
    </script>

<!-- Inclure les modaux -->
<?php include_once 'includes/modals.php'; ?>

<!-- Script de correction pour les modales -->
<script src="assets/js/modal-fix.js"></script>

<!-- Script de gestion des modaux -->
<script src="assets/js/modals-handler.js"></script>

<!-- Script de correctifs pour les appareils mobiles -->
<script src="assets/js/mobile-fix.js"></script>

<!-- Scanner d'étiquettes QR Code -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="assets/js/scanner-etiquette.js"></script>

<!-- Module de recherche avancée -->
<script src="assets/js/recherche-avancee.js"></script>

<!-- Modal Client Info (accessible globalement) -->
<div class="modal fade" id="clientInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-user me-2"></i>
                    Détails du client
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- En-tête avec infos de base et actions -->
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="card-header bg-light py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="avatar-lg bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-user fa-lg"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 client-nom">Nom du client</h4>
                                    <p class="mb-0 text-muted client-telephone">
                                        <i class="fas fa-phone-alt me-1"></i> 
                                        Téléphone
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-primary rounded-pill px-4 btn-appeler">
                                    <i class="fas fa-phone-alt me-2"></i>Appeler
                                </a>
                                <a href="#" class="btn btn-outline-primary rounded-pill px-4 btn-sms">
                                    <i class="fas fa-sms me-2"></i>SMS
                                </a>
                                <a href="#" class="btn btn-light rounded-pill px-4 btn-editer-client">
                                    <i class="fas fa-pen me-2"></i>Éditer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation par onglets simplifiée (sans Bootstrap tabs) -->
                <div class="mb-4">
                    <div class="d-flex gap-2" id="clientHistoryBtns">
                        <button class="btn btn-primary flex-fill py-2" id="btn-client-reps" onclick="showClientTab('reparationsClient')">
                            <i class="fas fa-tools me-2"></i>Réparations
                        </button>
                        <button class="btn btn-outline-primary flex-fill py-2" id="btn-client-cmds" onclick="showClientTab('commandesClient')">
                            <i class="fas fa-shopping-cart me-2"></i>Commandes
                        </button>
                    </div>
                </div>

                <!-- Contenu des onglets simplifiés -->
                <div class="position-relative">
                    <!-- Historique des réparations -->
                    <div id="reparationsClient" class="client-tab-container">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Historique des réparations</h6>
                                    <a href="#" class="btn btn-sm btn-primary" id="nouvelle-reparation-client">
                                        <i class="fas fa-plus me-1"></i>Nouvelle réparation
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Appareil</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historique_reparations">
                                        <!-- Les données seront chargées ici -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des commandes -->
                    <div id="commandesClient" class="client-tab-container" style="display: none;">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Historique des commandes</h6>
                                    <a href="#" class="btn btn-sm btn-primary" id="nouvelle-commande-client">
                                        <i class="fas fa-plus me-1"></i>Nouvelle commande
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th>Pièce</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historique_commandes">
                                        <!-- Les données seront chargées ici -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour gérer l'affichage des onglets du client -->
<script>
    function showClientTab(tabId) {
        // Masquer tous les conteneurs
        document.querySelectorAll('.client-tab-container').forEach(container => {
            container.style.display = 'none';
        });
        
        // Afficher le conteneur demandé
        const targetContainer = document.getElementById(tabId);
        if (targetContainer) {
            targetContainer.style.display = 'block';
        }
        
        // Mettre à jour les boutons
        document.querySelectorAll('#clientHistoryBtns button').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        // Activer le bouton correspondant
        if (tabId === 'reparationsClient') {
            document.getElementById('btn-client-reps').classList.remove('btn-outline-primary');
            document.getElementById('btn-client-reps').classList.add('btn-primary');
        } else if (tabId === 'commandesClient') {
            document.getElementById('btn-client-cmds').classList.remove('btn-outline-primary');
            document.getElementById('btn-client-cmds').classList.add('btn-primary');
        }
    }
</script>

<!-- Modal Réparation Info -->
<div class="modal fade" id="reparationInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-tools me-2"></i>
                    Détails de la réparation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="details-reparation-content">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<script>
    // Fonction pour charger les détails d'une réparation avec le nouveau bouton
    function chargerDetailsReparation(reparationId) {
        const detailsContainer = document.getElementById('details-reparation-content');
        if (!detailsContainer) return;
        
        // Afficher un indicateur de chargement
        detailsContainer.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3">Chargement des détails...</p>
            </div>
        `;
        
        // Charger les détails via AJAX
        fetch('ajax/get_reparation_details.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${reparationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.reparation) {
                throw new Error(data.message || 'Erreur lors du chargement des détails de la réparation');
            }
            
            const rep = data.reparation;
            
            // Afficher les détails avec le nouveau bouton qui redirige vers la page des réparations
            detailsContainer.innerHTML = `
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                ${rep.appareil} ${rep.modele}
                                <span class="badge bg-${getStatusColor(rep.statut)} ms-2">${formatStatus(rep.statut)}</span>
                            </h5>
                            <div>
                                <a href="index.php?page=reparations&showRepId=${rep.id}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-external-link-alt me-1"></i>Voir page complète
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Client</p>
                                <p class="mb-3 fw-bold">${rep.client_nom} ${rep.client_prenom}</p>
                                
                                <p class="mb-1 text-muted">Date de réception</p>
                                <p class="mb-3 fw-bold">${formatDate(rep.date_reception)}</p>
                                
                                <p class="mb-1 text-muted">Prix</p>
                                <p class="mb-0 fw-bold">${rep.prix || '0'}€</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Appareil</p>
                                <p class="mb-3 fw-bold">${rep.appareil} ${rep.modele}</p>
                                
                                <p class="mb-1 text-muted">Problème</p>
                                <p class="mb-0 fw-bold">${rep.probleme || 'Non spécifié'}</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <p class="mb-1 text-muted">Diagnostic</p>
                            <p class="mb-0">${rep.diagnostic || 'Aucun diagnostic enregistré'}</p>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails de la réparation:', error);
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erreur: ${error.message}
                </div>
                <div class="text-center mt-3">
                    <a href="index.php?page=reparations&showRepId=${reparationId}" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Voir la page complète
                    </a>
                </div>
            `;
        });
    }
    
    // Fonctions utilitaires
    function formatStatus(statut) {
        const statusMap = {
            '1': 'Reçu',
            '2': 'En cours',
            '3': 'Terminé',
            '4': 'Livré',
            'en_attente': 'En attente',
            'en_cours': 'En cours',
            'termine': 'Terminé',
            'livre': 'Livré'
        };
        
        return statusMap[statut] || statut;
    }
    
    function getStatusColor(statut) {
        const colorMap = {
            '1': 'info',
            '2': 'warning',
            '3': 'success',
            '4': 'secondary',
            'en_attente': 'info',
            'en_cours': 'warning',
            'termine': 'success',
            'livre': 'secondary'
        };
        
        return colorMap[statut] || 'primary';
    }
    
    function formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; // Si la date est invalide
        
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
</script>

<!-- Modal Commande Info -->
<div class="modal fade" id="commandeInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Détails de la commande
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="details-commande-content">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts communs pour l'application -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/dock-effects.js"></script>

<!-- Script pour la recherche rapide -->
<script>
    // Initialiser la recherche rapide
</script>

</body>
</html>