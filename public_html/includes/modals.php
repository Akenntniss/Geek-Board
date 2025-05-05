<?php
/**
 * Fichier contenant les modaux utilisés dans l'application
 * Modaux pour la barre de navigation:
 * 1. Modal pour les nouvelles actions (Réparation, Tâche, Commande)
 * 2. Modal pour le menu principal de navigation
 */

// Détecter le mode nuit
$dark_mode = false;
if (isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] === true) {
    $dark_mode = true;
} elseif (isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true') {
    $dark_mode = true;
}

// Récupérer la page courante
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Définir une fonction de secours pour count_active_tasks si elle n'existe pas
if (!function_exists('count_active_tasks')) {
    function count_active_tasks($user_id) {
        // Fonction temporaire pour éviter les erreurs
        return 0;
    }
}

// Récupérer le nombre de tâches en cours (si la fonction existe)
$tasks_count = 0;
if (isset($_SESSION['user_id'])) {
    $tasks_count = count_active_tasks($_SESSION['user_id']);
}
?>

<!-- MODAL NOUVELLES ACTIONS (Réparation, Tâche, Commande) -->
<div class="modal fade futuristic-modal" id="nouvelles_actions_modal" tabindex="-1" aria-labelledby="nouvelles_actions_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="nouvelles_actions_modal_label">
                    <i class="fas fa-plus-circle me-2 text-primary"></i>
                    Nouvelle action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 fade-in-sequence">
                <div class="list-group list-group-flush">
                    <a href="index.php?page=ajouter_reparation" class="list-group-item list-group-item-action p-3 holographic">
                        <div class="d-flex align-items-center">
                            <div class="action-icon bg-primary-light text-primary rounded-circle me-3 pulse-effect">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Ajouter réparation</h6>
                                <p class="small mb-0 text-muted">Créer un nouveau dossier de réparation</p>
                            </div>
                        </div>
                    </a>
                    <a href="index.php?page=ajouter_tache" class="list-group-item list-group-item-action p-3 holographic">
                        <div class="d-flex align-items-center">
                            <div class="action-icon bg-success-light text-success rounded-circle me-3">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Ajouter tâche</h6>
                                <p class="small mb-0 text-muted">Ajouter une tâche à réaliser</p>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action p-3 holographic" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
                        <div class="d-flex align-items-center">
                            <div class="action-icon bg-warning-light text-warning rounded-circle me-3">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Ajouter commande</h6>
                                <p class="small mb-0 text-muted">Commander des pièces ou fournitures</p>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action p-3 holographic" data-bs-toggle="modal" data-bs-target="#scanner_etiquette_modal">
                        <div class="d-flex align-items-center">
                            <div class="action-icon bg-info-light text-info rounded-circle me-3">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Scanner une étiquette</h6>
                                <p class="small mb-0 text-muted">Scanner un QR code pour voir le statut</p>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action p-3 holographic" data-bs-toggle="modal" data-bs-target="#ajouterBugModal">
                        <div class="d-flex align-items-center">
                            <div class="action-icon bg-danger-light text-danger rounded-circle me-3">
                                <i class="fas fa-bug"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Ajouter un bug</h6>
                                <p class="small mb-0 text-muted">Signaler un problème à l'équipe</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL MENU PRINCIPAL DE NAVIGATION -->
<div class="modal fade futuristic-modal" id="menu_navigation_modal" tabindex="-1" aria-labelledby="menu_navigation_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="menu_navigation_modal_label">
                    <img src="assets/images/logo/logo.png" alt="GeekBoard" height="30" class="me-2">
                    Menu Principal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="launchpad-container">
                    <!-- Section Gestion Principale -->
                    <div class="launchpad-section">
                        <h3 class="launchpad-section-title">Gestion Principale</h3>
                        <div class="launchpad-section-content">
                            <a href="index.php" class="launchpad-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-home">
                                    <i class="fas fa-home"></i>
                                </div>
                                <span>Accueil</span>
                            </a>
                            
                            <a href="index.php?page=reparations" class="launchpad-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-repair">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <span>Réparations</span>
                            </a>
                            
                            <a href="index.php?page=ajouter_reparation" class="launchpad-item <?php echo $currentPage == 'ajouter_reparation' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-add">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <span>Nouvelle Réparation</span>
                            </a>
                            
                            <a href="index.php?page=commandes_pieces" class="launchpad-item <?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-order">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <span>Commandes</span>
                            </a>
                            
                            <a href="index.php?page=taches" class="launchpad-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-task">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <span>Tâches</span>
                                <?php if (isset($tasks_count) && $tasks_count > 0): ?>
                                    <span class="badge rounded-pill bg-danger position-absolute top-0 end-0 translate-middle"><?php echo $tasks_count; ?></span>
                                <?php endif; ?>
                            </a>
                            
                            <a href="index.php?page=rachat_appareils" class="launchpad-item <?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-trade">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <span>Rachat</span>
                            </a>
                            
                            <a href="index.php?page=base_connaissances" class="launchpad-item <?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-knowledge">
                                    <i class="fas fa-book"></i>
                                </div>
                                <span>Base de connaissance</span>
                            </a>
                            
                            <a href="index.php?page=clients" class="launchpad-item <?php echo $currentPage == 'clients' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-client">
                                    <i class="fas fa-users"></i>
                                </div>
                                <span>Clients</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Section Communication -->
                    <div class="launchpad-section">
                        <h3 class="launchpad-section-title">Communication</h3>
                        <div class="launchpad-section-content">
                            <a href="index.php?page=campagne_sms" class="launchpad-item <?php echo $currentPage == 'campagne_sms' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-sms">
                                    <i class="fas fa-sms"></i>
                                </div>
                                <span>Campagne SMS</span>
                            </a>
                            
                            <a href="index.php?page=template_sms" class="launchpad-item <?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-template">
                                    <i class="fas fa-comment-dots"></i>
                                </div>
                                <span>Template SMS</span>
                            </a>
                            
                            <a href="index.php?page=sms_historique" class="launchpad-item <?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-history">
                                    <i class="fas fa-history"></i>
                                </div>
                                <span>Historique SMS</span>
                            </a>
                        </div>
                    </div>

                    <!-- Section Administration -->
                    <div class="launchpad-section">
                        <h3 class="launchpad-section-title">Administration</h3>
                        <div class="launchpad-section-content">
                            <a href="index.php?page=employes" class="launchpad-item <?php echo $currentPage == 'employes' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-employee">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <span>Employés</span>
                            </a>
                            
                            <a href="index.php?page=reparation_logs" class="launchpad-item <?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-logs">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <span>Journaux de réparation</span>
                            </a>
                            
                            <a href="#" class="launchpad-item" data-bs-toggle="modal" data-bs-target="#ajouterBugModal">
                                <div class="launchpad-icon launchpad-icon-bug">
                                    <i class="fas fa-bug"></i>
                                </div>
                                <span>Signaler un bug</span>
                            </a>
                            
                            <a href="index.php?page=parametre" class="launchpad-item <?php echo $currentPage == 'parametre' ? 'active' : ''; ?>">
                                <div class="launchpad-icon launchpad-icon-settings">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <span>Parametre</span>
                            </a>
                            
                            <a href="index.php?action=logout" class="launchpad-item">
                                <div class="launchpad-icon launchpad-icon-logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <span>Déconnexion</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SCANNER ÉTIQUETTE QR CODE -->
<div class="modal fade" id="scanner_etiquette_modal" tabindex="-1" aria-labelledby="scanner_etiquette_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow <?php echo $dark_mode ? 'bg-dark text-light' : ''; ?>">
            <div class="modal-header <?php echo $dark_mode ? 'border-secondary' : ''; ?>">
                <h5 class="modal-title" id="scanner_etiquette_modal_label">
                    <i class="fas fa-qrcode me-2 text-info"></i>
                    Scanner une étiquette
                </h5>
                <button type="button" class="btn-close <?php echo $dark_mode ? 'btn-close-white' : ''; ?>" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="text-center mb-3 p-3">
                    <p class="mb-0"><strong>Scannez le QR code</strong> d'une étiquette de réparation pour accéder à son statut.</p>
                </div>
                
                <div id="qr-scanner-container" class="mb-4">
                    <div class="position-relative">
                        <!-- Élément qui contiendra le scanner -->
                        <div id="reader"></div>
                        
                        <!-- Overlay avec zone de scan (guide visuel uniquement) -->
                        <div id="qr-scan-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
                            <div class="qr-scanner-region" style="width: 250px; height: 250px;">
                                <div class="qr-scanner-line" style="width: 100%; height: 3px; position: absolute; top: 50%; animation: scan 2s infinite ease-in-out;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message de statut du scan -->
                    <div class="mt-3 text-center p-3">
                        <span id="qr-scanner-status" class="d-block p-2 fs-5 fw-semibold text-muted">Montrez un QR code à la caméra</span>
                    </div>
                </div>
                
                <div class="text-center p-3 pb-4">
                    <button id="start-qr-scan" class="btn btn-primary btn-lg px-4 d-none">
                        <i class="fas fa-camera me-2"></i>Activer la caméra
                    </button>
                    <button id="stop-qr-scan" class="btn btn-secondary btn-lg px-4">
                        <i class="fas fa-stop me-2"></i>Arrêter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter Commande -->
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1" aria-labelledby="ajouterCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title" id="ajouterCommandeModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nouvelle commande de pièces
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="ajouterCommandeForm" method="post" action="ajax/add_commande.php">
                    <div class="row g-4">
                        <!-- Sélection du client -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Client</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-primary" type="button" id="searchClientBtn" data-bs-toggle="modal" data-bs-target="#rechercheClientModal">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <input type="text" id="nom_client_selectionne" class="form-control border-0 shadow-sm" value="" placeholder="Saisir ou rechercher un client...">
                                    <input type="hidden" name="client_id" id="client_id" value="">
                                </div>
                                <div id="client_selectionne" class="selected-item-info d-none mt-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-icon me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <span class="fw-medium nom_client"></span>
                                            <span class="d-block small text-muted tel_client"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Résultats de recherche client inline -->
                                <div id="resultats_recherche_client_inline" class="mt-2 d-none">
                                    <div class="card border-0 shadow-sm">
                                        <div class="list-group list-group-flush" id="liste_clients_recherche_inline">
                                            <!-- Les résultats seront ajoutés ici -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sélection de la réparation liée -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Réparation liée (optionnel)</label>
                                <select class="form-select form-select-lg border-0 shadow-sm rounded-3" name="reparation_id" id="reparation_id" onchange="getClientFromReparation(this.value)">
                                    <option value="">Sélectionner une réparation...</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Informations de la première pièce -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Fournisseur</label>
                                <select class="form-select form-select-lg border-0 shadow-sm rounded-3" name="fournisseur_id" id="fournisseur_id_ajout" required>
                                    <option value="">Sélectionner un fournisseur</option>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                                        while ($fournisseur = $stmt->fetch()) {
                                            echo "<option value='{$fournisseur['id']}'>" . 
                                                 htmlspecialchars($fournisseur['nom']) . "</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<option value=''>Erreur de chargement des fournisseurs</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Pièce commandée</label>
                                <input type="text" class="form-control form-control-lg border-0 shadow-sm rounded-3" name="nom_piece" id="nom_piece" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Code barre</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg border-0 shadow-sm rounded-start-3" name="code_barre" id="code_barre" placeholder="Saisir le code barre">
                                    <button type="button" class="btn btn-outline-primary btn-lg rounded-end-3 scan-code-btn">
                                        <i class="fas fa-barcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Quantité</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-primary btn-lg rounded-start-3 decrement-btn">-</button>
                                    <input type="number" class="form-control form-control-lg text-center border-0 shadow-sm quantite-input" name="quantite" id="quantite" min="1" value="1" required>
                                    <button type="button" class="btn btn-outline-primary btn-lg rounded-end-3 increment-btn">+</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Prix estimé (€)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control form-control-lg border-0 shadow-sm rounded-start-3" name="prix_estime" id="prix_estime" step="0.01" required>
                                    <span class="input-group-text bg-light rounded-end-3 border-0 shadow-sm">€</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Statut</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-warning flex-grow-1 status-btn active rounded-3" data-status="en_attente">
                                        <i class="fas fa-clock me-1"></i> En attente
                                    </button>
                                    <button type="button" class="btn btn-outline-primary flex-grow-1 status-btn rounded-3" data-status="commande">
                                        <i class="fas fa-shopping-cart fa-lg"></i> Commandé
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-grow-1 status-btn rounded-3" data-status="recue">
                                        <i class="fas fa-box fa-lg"></i> Reçu
                                    </button>
                                </div>
                                <input type="hidden" name="statut" id="statut_input" value="en_attente">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Container pour les pièces additionnelles -->
                    <div id="pieces-additionnelles"></div>
                    
                    <!-- Bouton pour ajouter une pièce supplémentaire -->
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-outline-primary btn-lg rounded-pill" id="ajouter-piece-btn">
                            <i class="fas fa-plus-circle me-2"></i>Ajouter une autre pièce
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-outline-info" id="debugSessionBtn">
                    <i class="fas fa-bug me-2"></i>Debug Session
                </button>
                <button type="submit" form="ajouterCommandeForm" class="btn btn-primary" id="saveCommandeBtn">
                    <i class="fas fa-save me-2"></i>Enregistrer la commande
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Signaler un Bug -->
<div class="modal fade" id="ajouterBugModal" tabindex="-1" aria-labelledby="ajouterBugModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 <?php echo $dark_mode ? 'bg-dark text-light' : ''; ?>">
            <div class="modal-header bg-danger text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title" id="ajouterBugModalLabel">
                    <i class="fas fa-bug me-2"></i>Signaler un problème
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="ajouterBugForm" method="post" action="ajax/add_bug_report.php">
                    <div class="mb-4">
                        <label for="bug_description" class="form-label fw-medium">Problème</label>
                        <textarea class="form-control form-control-lg border-0 shadow-sm rounded-3" 
                            id="bug_description" name="description" rows="5" 
                            placeholder="Décrivez le problème rencontré..."
                            required></textarea>
                        <input type="hidden" name="page_url" id="bug_page_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-lg rounded-3">
                            <i class="fas fa-save me-2"></i>Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container pour les notifications -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script>
// Fonction pour créer un toast de notification
function createToast(title, message, type = 'info') {
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    document.getElementById('toast-container').appendChild(toast);
    return new bootstrap.Toast(toast);
}

document.addEventListener('DOMContentLoaded', function() {
    // Gestion des boutons de statut dans le modal
    document.querySelectorAll('#ajouterCommandeModal .status-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Désélectionner tous les boutons
            document.querySelectorAll('#ajouterCommandeModal .status-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Sélectionner le bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur cachée
            const status = this.getAttribute('data-status');
            document.querySelector('#statut_input').value = status;
        });
    });
    
    // Gestion des boutons +/- pour la quantité
    const setupQuantityButtons = function() {
        // Boutons de diminution
        document.querySelectorAll('.decrement-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });
        });
        
        // Boutons d'augmentation
        document.querySelectorAll('.increment-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                input.value = value + 1;
            });
        });
    };
    
    // Initialiser les boutons de quantité
    setupQuantityButtons();
    
    // Référence au modal pour vérifier son existence
    const modal = document.getElementById('ajouterCommandeModal');
    if (!modal) {
        console.error("Erreur: Le modal 'ajouterCommandeModal' n'a pas été trouvé");
    }

    // Modifier le comportement du formulaire pour inclure le nom du client saisi manuellement
    const ajouterCommandeForm = document.getElementById('ajouterCommandeForm');
    if (ajouterCommandeForm) {
        let isSubmitting = false; // Variable pour suivre l'état de soumission

        ajouterCommandeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérifier si une soumission est déjà en cours
            if (isSubmitting) {
                console.log("Soumission déjà en cours, ignorée");
                return false;
            }
            
            console.log("Début de la soumission du formulaire");
            isSubmitting = true;
            
            // Obtenir les données du formulaire
            const formData = new FormData(this);
            const jsonData = {};
            
            // Convertir FormData en objet JSON
            formData.forEach((value, key) => {
                jsonData[key] = value;
            });
            
            // Vérifier si c'est un client saisi manuellement
            const clientId = document.getElementById('client_id').value;
            const nomClientSelectionne = document.getElementById('nom_client_selectionne').value;
            
            if (clientId === '-1' && nomClientSelectionne.trim() !== '') {
                jsonData.nom_client_manuel = nomClientSelectionne;
            }
            
            // Désactiver le bouton de soumission pour éviter les doubles clics
            const submitButton = document.getElementById('saveCommandeBtn');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';
            }
            
            // Envoyer les données via fetch API
            fetch('ajax/add_commande.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(jsonData),
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) modalInstance.hide();
                    
                    // Afficher une notification de succès
                    createToast('Succès', 'Commande ajoutée avec succès', 'success').show();
                    
                    // Recharger la page après un court délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Réactiver le bouton de soumission
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer la commande';
                    }
                    
                    // Afficher une notification d'erreur
                    createToast('Erreur', data.message || 'Une erreur est survenue', 'danger').show();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton de soumission
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer la commande';
                }
                
                createToast('Erreur', 'Une erreur est survenue lors de la communication avec le serveur', 'danger').show();
            })
            .finally(() => {
                // Réinitialiser l'état de soumission
                isSubmitting = false;
            });
            
            return false;
        });
    } else {
        console.error("Formulaire ajouterCommandeForm non trouvé");
    }

    // Corriger le problème de modal qui ne se ferme pas correctement
    const menuModal = document.getElementById('menu_navigation_modal');
    if (menuModal) {
        menuModal.addEventListener('hidden.bs.modal', function () {
            // S'assurer que le backdrop est supprimé correctement
            const backdrops = document.getElementsByClassName('modal-backdrop');
            while(backdrops.length > 0) {
                backdrops[0].remove();
            }
            
            // Restaurer la scrollabilité du body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }
    
    // Assurer que tous les autres modals se ferment correctement aussi
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            // Vérifier s'il n'y a plus de modals visibles avant de nettoyer
            const visibleModals = document.querySelectorAll('.modal.show');
            if (visibleModals.length === 0) {
                // Supprimer tous les backdrops restants
                const backdrops = document.getElementsByClassName('modal-backdrop');
                while(backdrops.length > 0) {
                    backdrops[0].remove();
                }
                
                // Restaurer le scroll
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        });
    });

    // Script pour la gestion du modal de bug
    const updateBugPageUrl = () => {
        const bugPageUrlInput = document.getElementById('bug_page_url');
        if (bugPageUrlInput) {
            bugPageUrlInput.value = window.location.href;
        }
    };
    
    // Initialiser le formulaire de bug
    const bugReportForm = document.getElementById('ajouterBugForm');
    if (bugReportForm) {
        bugReportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Envoi du formulaire via AJAX
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterBugModal'));
                    modal.hide();
                    
                    // Afficher une notification
                    createToast('Bug signalé avec succès', 'Le problème a été enregistré et sera examiné par l\'équipe.', 'success').show();
                    
                    // Réinitialiser le formulaire
                    bugReportForm.reset();
                } else {
                    // Afficher l'erreur
                    createToast('Erreur', data.error || 'Une erreur est survenue lors de l\'envoi du rapport.', 'danger').show();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                createToast('Erreur', 'Une erreur est survenue lors de l\'envoi du rapport.', 'danger').show();
            });
        });
    }
    
    // Mettre à jour l'URL à chaque ouverture du modal
    const bugModal = document.getElementById('ajouterBugModal');
    if (bugModal) {
        bugModal.addEventListener('show.bs.modal', updateBugPageUrl);
    }

    // Ajouter un gestionnaire pour le bouton de debug de session
    const debugSessionBtn = document.getElementById('debugSessionBtn');
    if (debugSessionBtn) {
        debugSessionBtn.addEventListener('click', function() {
            // Faire une requête AJAX pour obtenir les informations de session
            fetch('ajax/debug_session.php', {
                method: 'GET',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Informations de session:', data);
                // Créer une alerte avec les informations
                const message = `
                Session ID: ${data.session_id || 'Non disponible'}
                User ID: ${data.user_id || 'Non disponible'}
                Session valide: ${data.is_valid ? 'Oui' : 'Non'}
                Cookies: ${JSON.stringify(data.cookies || {})}
                `;
                createToast('Informations de session', message, 'info').show();
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des informations de session:', error);
                createToast('Erreur', 'Impossible de récupérer les informations de session', 'danger').show();
            });
        });
    }

    // Gestion de la recherche client dans le modal commande
    const clientSearchInput = document.getElementById('nom_client_selectionne');
    const resultsContainer = document.getElementById('resultats_recherche_client_inline');
    const clientsList = document.getElementById('liste_clients_recherche_inline');
    const clientIdInput = document.getElementById('client_id');
    const clientSelectionneDiv = document.getElementById('client_selectionne');
    
    if (clientSearchInput) {
        let searchTimeout;
        
        clientSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            // Cacher les résultats si le champ est vide
            if (query.length < 2) {
                if (resultsContainer) resultsContainer.classList.add('d-none');
                return;
            }
            
            // Attendre 300ms après la saisie pour rechercher
            searchTimeout = setTimeout(() => {
                // Requête AJAX pour rechercher les clients
                fetch('ajax/recherche_clients.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `terme=${encodeURIComponent(query)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.clients && data.clients.length > 0) {
                        // Afficher les résultats
                        if (clientsList) {
                            clientsList.innerHTML = '';
                            
                            data.clients.forEach(client => {
                                const item = document.createElement('a');
                                item.href = '#';
                                item.className = 'list-group-item list-group-item-action py-2';
                                item.innerHTML = `
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-icon me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <span class="fw-medium">${client.nom} ${client.prenom}</span>
                                            <span class="d-block small text-muted">${client.telephone || 'Pas de téléphone'}</span>
                                        </div>
                                    </div>
                                `;
                                
                                // Ajouter un gestionnaire de clic pour sélectionner le client
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    
                                    // Mettre à jour le champ caché avec l'ID du client
                                    if (clientIdInput) clientIdInput.value = client.id;
                                    
                                    // Mettre à jour l'affichage avec le nom du client
                                    if (clientSearchInput) clientSearchInput.value = `${client.nom} ${client.prenom}`;
                                    
                                    // Afficher la section "client sélectionné"
                                    if (clientSelectionneDiv) {
                                        const nomClientSpan = clientSelectionneDiv.querySelector('.nom_client');
                                        const telClientSpan = clientSelectionneDiv.querySelector('.tel_client');
                                        
                                        if (nomClientSpan) nomClientSpan.textContent = `${client.nom} ${client.prenom}`;
                                        if (telClientSpan) telClientSpan.textContent = client.telephone || 'Pas de téléphone';
                                        
                                        clientSelectionneDiv.classList.remove('d-none');
                                    }
                                    
                                    // Cacher les résultats
                                    if (resultsContainer) resultsContainer.classList.add('d-none');
                                });
                                
                                clientsList.appendChild(item);
                            });
                            
                            // Afficher le conteneur des résultats
                            if (resultsContainer) resultsContainer.classList.remove('d-none');
                        }
                    } else {
                        // Aucun résultat trouvé - Proposer de créer un client
                        if (clientsList) {
                            clientsList.innerHTML = `
                                <span class="list-group-item py-2 text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun client trouvé
                                </span>
                                <a href="#" class="list-group-item list-group-item-action py-3 text-center" id="btn_nouveau_client_commande">
                                    <i class="fas fa-user-plus me-2 text-primary"></i>
                                    <span class="fw-medium">Créer un nouveau client</span>
                                </a>
                            `;
                            
                            // Ajouter l'écouteur d'événement pour le bouton de création de client
                            const btnNouveauClient = document.getElementById('btn_nouveau_client_commande');
                            if (btnNouveauClient) {
                                btnNouveauClient.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    
                                    // Pré-remplir le nom si la recherche contient des espaces (potentiellement nom prénom)
                                    if (query.includes(' ')) {
                                        const parts = query.split(' ');
                                        document.getElementById('nouveau_nom_commande').value = parts[0];
                                        document.getElementById('nouveau_prenom_commande').value = parts.slice(1).join(' ');
                                    } else {
                                        document.getElementById('nouveau_nom_commande').value = query;
                                    }
                                    
                                    // Cacher les résultats
                                    resultsContainer.classList.add('d-none');
                                    
                                    // Ouvrir le modal d'ajout de client
                                    const modal = new bootstrap.Modal(document.getElementById('nouveauClientModal_commande'));
                                    modal.show();
                                });
                            }
                        }
                        
                        // Afficher le conteneur des résultats
                        if (resultsContainer) resultsContainer.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la recherche des clients:', error);
                    
                    // Afficher un message d'erreur
                    if (clientsList) {
                        clientsList.innerHTML = `
                            <span class="list-group-item py-2 text-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Erreur lors de la recherche
                            </span>
                        `;
                    }
                    
                    // Afficher le conteneur des résultats
                    if (resultsContainer) resultsContainer.classList.remove('d-none');
                });
            }, 300);
        });
        
        // Masquer les résultats quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if (!clientSearchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.classList.add('d-none');
            }
        });
    }
    
    // Sauvegarder un nouveau client depuis le modal de commande
    const btnSauvegarderClientCommande = document.getElementById('btn_sauvegarder_client_commande');
    if (btnSauvegarderClientCommande) {
        btnSauvegarderClientCommande.addEventListener('click', function() {
            const nom = document.getElementById('nouveau_nom_commande').value.trim();
            const prenom = document.getElementById('nouveau_prenom_commande').value.trim();
            const telephone = document.getElementById('nouveau_telephone_commande').value.trim();
            const email = document.getElementById('nouveau_email_commande').value.trim();
            const adresse = document.getElementById('nouveau_adresse_commande').value.trim();
            
            // Validation des champs
            if (!nom || !prenom || !telephone) {
                alert('Veuillez remplir tous les champs obligatoires');
                return;
            }
            
            // Désactiver le bouton pendant l'envoi
            const btnSave = this;
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';
            
            // Construire les données du formulaire
            const formData = new FormData();
            formData.append('nom', nom);
            formData.append('prenom', prenom);
            formData.append('telephone', telephone);
            formData.append('email', email);
            formData.append('adresse', adresse);
            
            // Récupérer le shop_id depuis PHP pour l'envoyer explicitement
            <?php if (isset($_SESSION['shop_id'])): ?>
            formData.append('shop_id', '<?php echo $_SESSION['shop_id']; ?>');
            <?php endif; ?>
            
            // Enregistrement AJAX avec connexion directe à la base du magasin
            fetch('ajax/direct_add_client_rapide.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour les champs du client dans le modal de commande
                    document.getElementById('client_id').value = data.client_id;
                    document.getElementById('nom_client_selectionne').value = nom + ' ' + prenom;
                    
                    // Afficher les informations du client sélectionné
                    const clientSelectionne = document.getElementById('client_selectionne');
                    const nomClientSpan = clientSelectionne.querySelector('.nom_client');
                    const telClientSpan = clientSelectionne.querySelector('.tel_client');
                    
                    if (nomClientSpan) nomClientSpan.textContent = `${nom} ${prenom}`;
                    if (telClientSpan) telClientSpan.textContent = telephone || 'Pas de téléphone';
                    
                    clientSelectionne.classList.remove('d-none');
                    
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('nouveauClientModal_commande'));
                    modal.hide();
                    
                    // Réinitialiser le formulaire
                    document.getElementById('formNouveauClient_commande').reset();
                    
                    // Afficher une notification de succès
                    createToast('Client ajouté', 'Le client a été créé avec succès', 'success').show();
                } else {
                    // Afficher une notification d'erreur
                    createToast('Erreur', data.message || 'Erreur lors de l\'ajout du client', 'danger').show();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                createToast('Erreur', 'Erreur de connexion lors de l\'ajout du client', 'danger').show();
            })
            .finally(() => {
                // Réactiver le bouton
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fas fa-save me-2"></i>Sauvegarder';
            });
        });
    }
});
</script>

<!-- Styles pour les modaux -->
<style>
.action-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.menu-icon {
    width: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.menu-section-header {
    background-color: #f8f9fa;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    padding: 0.75rem 1rem;
    letter-spacing: 0.05rem;
    color: #6c757d;
}

/* Styles pour le Launchpad */
.launchpad-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    padding: 2rem;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

@media (min-width: 768px) {
    .launchpad-container {
        grid-template-columns: repeat(5, 1fr);
    }
}

@media (min-width: 992px) {
    .launchpad-container {
        grid-template-columns: repeat(6, 1fr);
        gap: 1.5rem;
        padding: 2rem;
    }
}

.launchpad-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #333;
    padding: 1rem;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    text-align: center;
    animation: scaleIn 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    animation-fill-mode: both;
}

/* Animation delay for each item */
.launchpad-item:nth-child(1) { animation-delay: 0.05s; }
.launchpad-item:nth-child(2) { animation-delay: 0.1s; }
.launchpad-item:nth-child(3) { animation-delay: 0.15s; }
.launchpad-item:nth-child(4) { animation-delay: 0.2s; }
.launchpad-item:nth-child(5) { animation-delay: 0.25s; }
.launchpad-item:nth-child(6) { animation-delay: 0.3s; }
.launchpad-item:nth-child(7) { animation-delay: 0.35s; }
.launchpad-item:nth-child(8) { animation-delay: 0.4s; }
.launchpad-item:nth-child(9) { animation-delay: 0.45s; }
.launchpad-item:nth-child(10) { animation-delay: 0.5s; }
.launchpad-item:nth-child(11) { animation-delay: 0.55s; }
.launchpad-item:nth-child(12) { animation-delay: 0.6s; }
.launchpad-item:nth-child(13) { animation-delay: 0.65s; }
.launchpad-item:nth-child(14) { animation-delay: 0.7s; }
.launchpad-item:nth-child(15) { animation-delay: 0.75s; }

.dark-mode .launchpad-item {
    color: #eee;
}

.launchpad-item:hover {
    transform: translateY(-5px) scale(1.05);
    background-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    z-index: 1;
}

.launchpad-item:active {
    transform: translateY(0) scale(0.95);
    transition: all 0.1s ease;
}

.dark-mode .launchpad-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.launchpad-item.active {
    color: var(--primary-color);
}

.launchpad-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background-color: var(--primary-light);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
    font-size: 1.5rem;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.launchpad-icon::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 50%, rgba(0,0,0,0.1) 100%);
    border-radius: 15px;
}

.launchpad-item:hover .launchpad-icon {
    transform: scale(1.1);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15);
}

.launchpad-item span {
    font-weight: 500;
    margin-top: 0.5rem;
    transition: all 0.3s ease;
}

.launchpad-item:hover span {
    transform: scale(1.05);
}

.launchpad-icon-danger {
    background-color: var(--danger-light);
    color: var(--danger-color);
}

/* Modal style improvements */
#menu_navigation_modal .modal-dialog {
    transition: transform 0.3s ease;
}

#menu_navigation_modal.show .modal-dialog {
    transform: translateY(0) !important;
}

#menu_navigation_modal .modal-content {
    background-color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

#menu_navigation_modal .modal-header {
    position: relative;
    padding: 1.5rem;
    border-bottom: none;
    z-index: 1050;
}

#menu_navigation_modal .btn-close {
    position: absolute;
    right: 1.5rem;
    top: 42px;
    width: 44px;
    height: 44px;
    padding: 10px;
    margin: 0;
    opacity: 0.6;
    cursor: pointer;
    z-index: 1051;
    background-size: 12px;
    border-radius: 50%;
    transition: all 0.2s ease;
    touch-action: manipulation;
}

#menu_navigation_modal .btn-close:hover {
    opacity: 1;
    transform: scale(1.1);
    background-color: rgba(0, 0, 0, 0.1);
}

.dark-mode #menu_navigation_modal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.dark-mode #menu_navigation_modal .btn-close:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.dark-mode #menu_navigation_modal .modal-content {
    background-color: rgba(30, 30, 30, 0.9);
}

/* Responsabilité du modal */
@media (max-width: 576px) {
    .launchpad-icon {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }
    
    .launchpad-item span {
        font-size: 0.8rem;
    }
}

/* Ajout d'une media query pour les appareils mobiles pour rendre le bouton encore plus grand sur tactile */
@media (max-width: 768px) {
    #menu_navigation_modal .btn-close {
        width: 50px;
        height: 50px;
        padding: 12px;
        top: 38px;
        right: 1rem;
    }
}

.bg-primary-light {
    background-color: rgba(13, 110, 253, 0.15);
}

.bg-success-light {
    background-color: rgba(25, 135, 84, 0.15);
}

.bg-warning-light {
    background-color: rgba(255, 193, 7, 0.15);
}

.bg-dark-secondary {
    background-color: #343a40;
}

/* Styles pour mode nuit */
.dark-mode .action-icon.bg-primary-light {
    background-color: rgba(13, 110, 253, 0.5);
}

.dark-mode .action-icon.bg-success-light {
    background-color: rgba(25, 135, 84, 0.5);
}

.dark-mode .action-icon.bg-warning-light {
    background-color: rgba(255, 193, 7, 0.5);
}

.dark-mode .list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.dark-mode .menu-section-header {
    background-color: #2c3034;
    color: #adb5bd;
}

.dark-mode .launchpad-icon {
    background-color: rgba(13, 110, 253, 0.25);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
}

.dark-mode .launchpad-icon-danger {
    background-color: rgba(220, 53, 69, 0.25);
}

/* Icon specific colors */
.launchpad-icon-home {
    background-color: rgba(52, 152, 219, 0.15);
    color: #3498db; /* Blue */
}

.launchpad-icon-repair {
    background-color: rgba(231, 76, 60, 0.15);
    color: #e74c3c; /* Red */
}

.launchpad-icon-order {
    background-color: rgba(241, 196, 15, 0.15);
    color: #f1c40f; /* Yellow */
}

.launchpad-icon-task {
    background-color: rgba(46, 204, 113, 0.15);
    color: #2ecc71; /* Green */
}

.launchpad-icon-inventory {
    background-color: rgba(155, 89, 182, 0.15);
    color: #9b59b6; /* Purple */
}

.launchpad-icon-vacation {
    background-color: rgba(230, 126, 34, 0.15);
    color: #e67e22; /* Orange */
}

.launchpad-icon-client {
    background-color: rgba(41, 128, 185, 0.15); 
    color: #2980b9; /* Dark Blue */
}

.launchpad-icon-settings {
    background-color: rgba(52, 73, 94, 0.15);
    color: #34495e; /* Dark Gray/Blue */
}

.launchpad-icon-partner {
    background-color: rgba(22, 160, 133, 0.15);
    color: #16a085; /* Teal */
}

.launchpad-icon-sms {
    background-color: rgba(192, 57, 43, 0.15);
    color: #c0392b; /* Dark Red */
}

.launchpad-icon-template {
    background-color: rgba(142, 68, 173, 0.15);
    color: #8e44ad; /* Dark Purple */
}

.launchpad-icon-message {
    background-color: rgba(41, 128, 185, 0.15);
    color: #2980b9; /* Dark Blue */
}

.launchpad-icon-status {
    background-color: rgba(39, 174, 96, 0.15);
    color: #27ae60; /* Dark Green */
}

.launchpad-icon-employee {
    background-color: rgba(211, 84, 0, 0.15);
    color: #d35400; /* Dark Orange */
}

/* Dark mode adaptations */
.dark-mode .launchpad-icon-home {
    background-color: rgba(52, 152, 219, 0.25);
}

.dark-mode .launchpad-icon-repair {
    background-color: rgba(231, 76, 60, 0.25);
}

.dark-mode .launchpad-icon-order {
    background-color: rgba(241, 196, 15, 0.25);
}

.dark-mode .launchpad-icon-task {
    background-color: rgba(46, 204, 113, 0.25);
}

.dark-mode .launchpad-icon-inventory {
    background-color: rgba(155, 89, 182, 0.25);
}

.dark-mode .launchpad-icon-vacation {
    background-color: rgba(230, 126, 34, 0.25);
}

.dark-mode .launchpad-icon-client {
    background-color: rgba(41, 128, 185, 0.25);
}

.dark-mode .launchpad-icon-settings {
    background-color: rgba(52, 73, 94, 0.25);
}

.dark-mode .launchpad-icon-partner {
    background-color: rgba(22, 160, 133, 0.25);
}

.dark-mode .launchpad-icon-sms {
    background-color: rgba(192, 57, 43, 0.25);
}

.dark-mode .launchpad-icon-template {
    background-color: rgba(142, 68, 173, 0.25);
}

.dark-mode .launchpad-icon-message {
    background-color: rgba(41, 128, 185, 0.25);
}

.dark-mode .launchpad-icon-status {
    background-color: rgba(39, 174, 96, 0.25);
}

.dark-mode .launchpad-icon-employee {
    background-color: rgba(211, 84, 0, 0.25);
}

/* Hover effect to make the colors pop more */
.launchpad-item:hover .launchpad-icon {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    filter: brightness(1.1);
}

/* Styles pour les sections du Launchpad */
.launchpad-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    padding: 2rem;
    animation: fadeIn 0.3s ease-out;
}

.launchpad-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 1.5rem;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.dark-mode .launchpad-section {
    background: rgba(0, 0, 0, 0.2);
}

.launchpad-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
}

.dark-mode .launchpad-section-title {
    color: #fff;
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.launchpad-section-content {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 1rem;
}

@media (min-width: 768px) {
    .launchpad-section-content {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 1.5rem;
    }
}

@media (min-width: 992px) {
    .launchpad-section-content {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 2rem;
    }
}

.launchpad-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #333;
    padding: 1rem;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    text-align: center;
    animation: scaleIn 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    animation-fill-mode: both;
}

/* Styles pour l'animation du scanner QR */
@keyframes scan {
    0% { top: 0; }
    50% { top: 100%; }
    100% { top: 0; }
}

/* Zone de scan avec des coins visibles */
.qr-scanner-region {
    border: 4px solid #05d9ff;
    box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.7);
    border-radius: 12px;
}

/* Ligne de scan qui se déplace */
.qr-scanner-line {
    background-color: #05d9ff;
    box-shadow: 0 0 12px #05d9ff;
}

/* Overlay transparent au-dessus du scanner */
#qr-scan-overlay {
    pointer-events: none;
    z-index: 10;
}

/* Style pour le div reader */
#reader {
    border-radius: 12px;
    overflow: hidden;
    width: 100%;
    min-height: 350px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    position: relative;
}

/* Styles pour la vidéo du scanner */
.scanner-video {
    width: 100% !important;
    max-height: 350px !important;
    object-fit: cover !important;
    border-radius: 10px;
}

/* Masquer le canvas */
.scanner-canvas {
    display: none !important;
}

/* Animation d'attention pour guider l'utilisateur */
@keyframes pulse {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.05); opacity: 1; }
    100% { transform: scale(1); opacity: 0.8; }
}

/* Animation quand le scanner est actif */
.scanner-active {
    animation: pulse 2s infinite ease-in-out;
}

/* Message d'aide pour guider l'utilisateur */
.scan-helper {
    position: absolute;
    bottom: 25px;
    left: 0;
    right: 0;
    text-align: center;
    color: white;
    font-size: 16px;
    font-weight: bold;
    z-index: 20;
    text-shadow: 0 1px 3px rgba(0,0,0,0.8);
    background-color: rgba(0,0,0,0.5);
    padding: 8px;
    border-radius: 20px;
    margin: 0 auto;
    width: 80%;
    max-width: 300px;
}

/* Couleurs d'état */
.text-success {
    color: #28a745 !important;
}

.text-danger {
    color: #dc3545 !important;
}
</style>

<!-- Modal pour ajouter un nouveau client depuis commande -->
<div class="modal fade" id="nouveauClientModal_commande" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    Ajouter un nouveau client
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formNouveauClient_commande">
                    <div class="mb-3">
                        <label for="nouveau_nom_commande" class="form-label">Nom *</label>
                        <input type="text" class="form-control form-control-lg border-0 shadow-sm rounded-3" id="nouveau_nom_commande" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_prenom_commande" class="form-label">Prénom *</label>
                        <input type="text" class="form-control form-control-lg border-0 shadow-sm rounded-3" id="nouveau_prenom_commande" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_telephone_commande" class="form-label">Téléphone *</label>
                        <input type="tel" inputmode="tel" class="form-control form-control-lg border-0 shadow-sm rounded-3" id="nouveau_telephone_commande" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_email_commande" class="form-label">Email</label>
                        <input type="email" inputmode="email" class="form-control form-control-lg border-0 shadow-sm rounded-3" id="nouveau_email_commande">
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_adresse_commande" class="form-label">Adresse</label>
                        <textarea class="form-control form-control-lg border-0 shadow-sm rounded-3" id="nouveau_adresse_commande" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1 me-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary flex-grow-1" id="btn_sauvegarder_client_commande">
                        <i class="fas fa-save me-2"></i>Sauvegarder
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</body>