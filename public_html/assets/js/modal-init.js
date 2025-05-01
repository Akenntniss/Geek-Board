/**
 * Script d'initialisation supplémentaire pour les modaux
 * Ce script sert de solution de secours si modal-force.js échoue
 */

(function() {
    console.log('Initialisation de secours des modaux...');
    
    // Fonction d'initialisation exécutée après le chargement du DOM
    function initModals() {
        console.log('Initialisation des modaux de secours...');
        
        // Initialiser les boutons d'action
        initActionButton();
        initMenuButton();
        
        // Créer des boutons d'urgence après un délai si nécessaire
        setTimeout(checkAndCreateEmergencyButtons, 3000);
    }
    
    // Initialiser le bouton Nouvelle Action
    function initActionButton() {
        console.log('Initialisation du bouton Nouvelle Action...');
        
        // Rechercher le bouton par différents sélecteurs pour maximiser les chances de le trouver
        const actionButtons = document.querySelectorAll('.btn-nouvelle-action, #btn-nouvelle-action, [data-button-type="nouvelle-action"], button.btn-nouvelle-action');
        if (actionButtons.length > 0) {
            actionButtons.forEach(btn => {
                btn.removeEventListener('click', openActionModal);
                btn.addEventListener('click', openActionModal);
            });
            console.log('Bouton Nouvelle Action configuré');
        } else {
            console.warn('Bouton Nouvelle Action non trouvé');
        }
    }
    
    // Initialiser le bouton Menu Principal
    function initMenuButton() {
        console.log('Initialisation du bouton Menu Principal...');
        
        // Rechercher le bouton par différents sélecteurs
        const menuButtons = document.querySelectorAll('.btn-menu-principal, #btn-menu-principal, [data-button-type="menu-principal"], a.dock-item.btn-menu-principal');
        if (menuButtons.length > 0) {
            menuButtons.forEach(btn => {
                btn.removeEventListener('click', openMenuModal);
                btn.addEventListener('click', openMenuModal);
            });
            console.log('Bouton Menu Principal configuré');
        } else {
            console.warn('Bouton Menu Principal non trouvé');
        }
    }
    
    // Ouvrir le modal Nouvelle Action
    function openActionModal(e) {
        e.preventDefault();
        console.log('Tentative d\'ouverture du modal Nouvelle Action...');
        
        // Tenter d'ouvrir via Bootstrap
        const actionModal = document.getElementById('nouvelleActionModal');
        if (actionModal) {
            try {
                let bsModal = bootstrap.Modal.getInstance(actionModal);
                if (!bsModal) {
                    bsModal = new bootstrap.Modal(actionModal);
                }
                bsModal.show();
                console.log('Modal Nouvelle Action ouvert via Bootstrap');
            } catch (err) {
                console.warn('Échec de l\'ouverture via Bootstrap, utilisation de la méthode manuelle', err);
                // Méthode manuelle
                actionModal.classList.add('show');
                actionModal.style.display = 'block';
                document.body.classList.add('modal-open');
                
                // Créer un backdrop
                const backdrop = document.createElement('div');
                backdrop.classList.add('modal-backdrop', 'fade', 'show');
                document.body.appendChild(backdrop);
                
                // Gestionnaires pour fermeture
                actionModal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach(btn => {
                    btn.onclick = closeModal;
                });
                
                function closeModal() {
                    actionModal.classList.remove('show');
                    actionModal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                }
                
                // Fermer en cliquant sur le backdrop
                backdrop.onclick = closeModal;
                
                console.log('Modal Nouvelle Action ouvert manuellement');
            }
        } else {
            console.error('Modal #nouvelleActionModal non trouvé dans le DOM');
            // Créer le modal à la volée s'il n'existe pas
            createEmergencyActionModal();
        }
    }
    
    // Ouvrir le modal Menu Principal
    function openMenuModal(e) {
        e.preventDefault();
        console.log('Tentative d\'ouverture du modal Menu Principal...');
        
        // Tenter d'ouvrir via Bootstrap
        const menuModal = document.getElementById('menuPrincipalModal');
        if (menuModal) {
            try {
                let bsModal = bootstrap.Modal.getInstance(menuModal);
                if (!bsModal) {
                    bsModal = new bootstrap.Modal(menuModal);
                }
                bsModal.show();
                console.log('Modal Menu Principal ouvert via Bootstrap');
            } catch (err) {
                console.warn('Échec de l\'ouverture via Bootstrap, utilisation de la méthode manuelle', err);
                // Méthode manuelle
                menuModal.classList.add('show');
                menuModal.style.display = 'block';
                document.body.classList.add('modal-open');
                
                // Créer un backdrop
                const backdrop = document.createElement('div');
                backdrop.classList.add('modal-backdrop', 'fade', 'show');
                document.body.appendChild(backdrop);
                
                // Gestionnaires pour fermeture
                menuModal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach(btn => {
                    btn.onclick = closeModal;
                });
                
                function closeModal() {
                    menuModal.classList.remove('show');
                    menuModal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                }
                
                // Fermer en cliquant sur le backdrop
                backdrop.onclick = closeModal;
                
                console.log('Modal Menu Principal ouvert manuellement');
            }
        } else {
            console.error('Modal #menuPrincipalModal non trouvé dans le DOM');
            // Créer le modal à la volée s'il n'existe pas
            createEmergencyMenuModal();
        }
    }
    
    // Vérifier si les boutons sont présents et créer des boutons d'urgence si nécessaire
    function checkAndCreateEmergencyButtons() {
        const actionButtons = document.querySelectorAll('.btn-nouvelle-action, #btn-nouvelle-action, [data-button-type="nouvelle-action"]');
        const menuButtons = document.querySelectorAll('.btn-menu-principal, #btn-menu-principal, [data-button-type="menu-principal"]');
        
        console.log(`Vérification finale: ${actionButtons.length} boutons d'action et ${menuButtons.length} boutons de menu trouvés.`);
        
        // Créer le conteneur de boutons d'urgence si nécessaire
        if (actionButtons.length === 0 || menuButtons.length === 0) {
            console.warn('Créant des boutons d\'urgence...');
            
            // Supprimer les boutons existants
            document.getElementById('emergency-buttons-container')?.remove();
            
            // Créer le conteneur
            const container = document.createElement('div');
            container.id = 'emergency-buttons-container';
            container.style.position = 'fixed';
            container.style.bottom = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.gap = '10px';
            
            // Créer les boutons si nécessaire
            if (actionButtons.length === 0) {
                const actionButton = document.createElement('button');
                actionButton.textContent = '+ Nouvelle';
                actionButton.className = 'btn btn-primary emergency-action-btn';
                actionButton.style.padding = '10px 15px';
                actionButton.style.borderRadius = '5px';
                actionButton.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
                actionButton.addEventListener('click', openActionModal);
                container.appendChild(actionButton);
            }
            
            if (menuButtons.length === 0) {
                const menuButton = document.createElement('button');
                menuButton.textContent = 'Menu';
                menuButton.className = 'btn btn-secondary emergency-menu-btn';
                menuButton.style.padding = '10px 15px';
                menuButton.style.borderRadius = '5px';
                menuButton.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
                menuButton.addEventListener('click', openMenuModal);
                container.appendChild(menuButton);
            }
            
            document.body.appendChild(container);
            console.log('Boutons d\'urgence ajoutés');
        }
    }
    
    // Créer un modal d'action d'urgence
    function createEmergencyActionModal() {
        // Supprimer s'il existe déjà
        document.getElementById('nouvelleActionModal')?.remove();
        
        // Créer le modal à la volée
        const modalHTML = `
        <div class="modal fade" id="nouvelleActionModal" tabindex="-1" aria-labelledby="nouvelleActionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title" id="nouvelleActionModalLabel">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>
                            Ajouter
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="index.php?page=ajouter_reparation" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex align-items-center">
                                    <div class="action-icon bg-primary-light text-primary rounded-circle me-3">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Ajouter Réparation</h6>
                                        <p class="text-muted small mb-0">Créer un nouveau dossier de réparation</p>
                                    </div>
                                </div>
                            </a>
                            <a href="index.php?page=ajouter_tache" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex align-items-center">
                                    <div class="action-icon bg-success-light text-success rounded-circle me-3">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Ajouter Tâche</h6>
                                        <p class="text-muted small mb-0">Créer une nouvelle tâche à accomplir</p>
                                    </div>
                                </div>
                            </a>
                            <a href="index.php?page=nouvelle_commande" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex align-items-center">
                                    <div class="action-icon bg-warning-light text-warning rounded-circle me-3">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Ajouter Commande</h6>
                                        <p class="text-muted small mb-0">Commander des pièces ou fournitures</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('Modal Nouvelle Action créé dynamiquement');
        
        // Ouvrir immédiatement
        openActionModal(new Event('click'));
    }
    
    // Créer un modal de menu d'urgence
    function createEmergencyMenuModal() {
        // Supprimer s'il existe déjà
        document.getElementById('menuPrincipalModal')?.remove();
        
        // Créer le modal à la volée
        const modalHTML = `
        <div class="modal fade" id="menuPrincipalModal" tabindex="-1" aria-labelledby="menuPrincipalModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title" id="menuPrincipalModalLabel">
                            <i class="fas fa-bars me-2 text-primary"></i>
                            Menu Principal
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="index.php" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex align-items-center">
                                    <div class="menu-icon me-3">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <span>Accueil</span>
                                </div>
                            </a>
                            <a href="index.php?page=reparations" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex align-items-center">
                                    <div class="menu-icon me-3">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                    <span>Réparations</span>
                                </div>
                            </a>
                            <a href="index.php?page=taches" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex align-items-center">
                                    <div class="menu-icon me-3">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <span>Tâches</span>
                                </div>
                            </a>
                            <a href="index.php?page=commandes_pieces" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex align-items-center">
                                    <div class="menu-icon me-3">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <span>Commandes</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        console.log('Modal Menu Principal créé dynamiquement');
        
        // Ouvrir immédiatement
        openMenuModal(new Event('click'));
    }
    
    // Laisser un peu de temps à modal-force.js de s'initialiser d'abord
    setTimeout(initModals, 1000);
    
    // Réinitialiser les modaux périodiquement pour s'assurer qu'ils fonctionnent
    setInterval(initModals, 10000);
    
    // Exposer les fonctions globalement pour le débogage
    window.modalEmergency = {
        initModals,
        openActionModal,
        openMenuModal,
        createEmergencyActionModal,
        createEmergencyMenuModal
    };
})(); 