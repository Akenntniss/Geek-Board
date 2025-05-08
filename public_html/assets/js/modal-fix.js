/**
 * Correction des problèmes de modales
 * Script amélioré pour résoudre les problèmes d'ouverture des modales
 */

(function() {
    // Fonction exécutée au chargement complet du DOM
    function initModalFix() {
        console.log('Initialisation de la correction des modales...');
        
        // S'assurer que Bootstrap est chargé
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap non disponible, chargement forcé...');
            loadBootstrap();
            return; // On s'arrête ici, loadBootstrap rappellera initModalFix après chargement
        }
        
        // Patcher Bootstrap avant d'utiliser les modales
        patchBootstrapModal();
        
        // Réparation des modales spécifiques qui posent problème
        fixSpecificModals();
        
        // Correction générale pour tous les boutons de modale
        fixAllModalButtons();
        
        // Correction spécifique pour les appareils mobiles
        if (window.innerWidth <= 768) {
            fixMobileModals();
        }
    }
    
    // Charge Bootstrap si nécessaire
    function loadBootstrap() {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js';
        script.integrity = 'sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN';
        script.crossOrigin = 'anonymous';
        
        script.onload = function() {
            console.log('Bootstrap chargé avec succès');
            // Rappel de l'initialisation après chargement
            setTimeout(initModalFix, 100);
        };
        
        document.head.appendChild(script);
    }
    
    // Corrige les modales spécifiques qui posent problème
    function fixSpecificModals() {
        // Liste des modales problématiques et leurs boutons déclencheurs
        const problematicModals = [
            {
                modalId: 'nouvelleActionModal',
                buttonSelector: '.btn-nouvelle-action, [data-bs-target="#nouvelleActionModal"]'
            },
            {
                modalId: 'menuPrincipalModal',
                buttonSelector: 'a.dock-item[data-bs-target="#menuPrincipalModal"]'
            },
            {
                modalId: 'ajouterCommandeModal',
                buttonSelector: '[data-bs-target="#ajouterCommandeModal"]'
            },
            {
                modalId: 'rechercheClientModal',
                buttonSelector: '[data-bs-target="#rechercheClientModal"]'
            },
            {
                modalId: 'changeStatusModal',
                buttonSelector: '[data-bs-target="#changeStatusModal"], .status-badge'
            },
            {
                modalId: 'editCommandeModal',
                buttonSelector: '[data-bs-target="#editCommandeModal"]'
            },
            {
                modalId: 'editFieldModal',
                buttonSelector: '[data-bs-target="#editFieldModal"], .editable-field'
            },
            {
                modalId: 'editFournisseurModal',
                buttonSelector: '[data-bs-target="#editFournisseurModal"]'
            },
            {
                modalId: 'fournisseursModal',
                buttonSelector: '[data-bs-target="#fournisseursModal"], #fournisseurBouton'
            },
            {
                modalId: 'periodesModal',
                buttonSelector: '[data-bs-target="#periodesModal"], #periodeButton'
            }
        ];
        
        // Corriger chaque modale problématique
        problematicModals.forEach(item => {
            const modal = document.getElementById(item.modalId);
            if (!modal) {
                console.log(`Modale #${item.modalId} non trouvée, ignorée`);
                return;
            }
            
            console.log(`Correction de la modale #${item.modalId}...`);
            
            // S'assurer que la modale a les bons attributs
            ensureModalAttributes(modal);
            
            // Corriger les boutons qui ouvrent cette modale
            const buttons = document.querySelectorAll(item.buttonSelector);
            console.log(`- ${buttons.length} boutons trouvés pour la modale #${item.modalId}`);
            
            buttons.forEach((button, index) => {
                console.log(`  - Correction du bouton ${index + 1} : ${button.outerHTML.substring(0, 50)}...`);
                fixModalButton(button, modal);
            });
            
            console.log(`Modale #${item.modalId} corrigée (${buttons.length} boutons)`);
        });
    }
    
    // S'assure que la modale a tous les attributs nécessaires
    function ensureModalAttributes(modalElement) {
        if (!modalElement.classList.contains('modal')) {
            modalElement.classList.add('modal');
        }
        if (!modalElement.classList.contains('fade')) {
            modalElement.classList.add('fade');
        }
        
        // Autres attributs importants
        modalElement.setAttribute('tabindex', '-1');
        modalElement.setAttribute('aria-hidden', 'true');
        
        // S'assurer que le dialog existe
        let dialogElement = modalElement.querySelector('.modal-dialog');
        if (!dialogElement) {
            console.warn(`Élément .modal-dialog manquant dans #${modalElement.id}, création...`);
            dialogElement = document.createElement('div');
            dialogElement.className = 'modal-dialog modal-dialog-centered';
            
            // Déplacer le contenu existant dans le dialog
            const contentElements = Array.from(modalElement.childNodes);
            contentElements.forEach(node => {
                if (node.nodeType === 1 && !node.classList.contains('modal-backdrop')) {
                    dialogElement.appendChild(node);
                }
            });
            
            modalElement.appendChild(dialogElement);
        }
        
        // S'assurer que le content existe
        let contentElement = dialogElement.querySelector('.modal-content');
        if (!contentElement) {
            console.warn(`Élément .modal-content manquant dans #${modalElement.id}, création...`);
            contentElement = document.createElement('div');
            contentElement.className = 'modal-content';
            
            // Déplacer le contenu existant dans le content
            const dialogContentElements = Array.from(dialogElement.childNodes);
            dialogContentElements.forEach(node => {
                if (node.nodeType === 1 && 
                    !node.classList.contains('modal-content') && 
                    !node.classList.contains('modal-dialog')) {
                    contentElement.appendChild(node);
                }
            });
            
            dialogElement.appendChild(contentElement);
        }
        
        // Définir explicitement backdrop et keyboard
        modalElement.setAttribute('data-bs-backdrop', 'static');
        modalElement.setAttribute('data-bs-keyboard', 'false');
        
        // Ajouter un attribut data-fixed="true" pour marquer les modales corrigées
        modalElement.setAttribute('data-fixed', 'true');
        
        // Réinitialiser l'instance Bootstrap
        try {
            const oldInstance = bootstrap.Modal.getInstance(modalElement);
            if (oldInstance) {
                oldInstance.dispose();
            }
        } catch (e) {
            console.warn('Erreur lors de la réinitialisation de la modale:', e);
        }
    }
    
    // Corrige tous les boutons de modale de la page
    function fixAllModalButtons() {
        const modalButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
        modalButtons.forEach(button => {
            const targetId = button.getAttribute('data-bs-target');
            if (!targetId) return;
            
            const modalElement = document.querySelector(targetId);
            if (!modalElement) return;
            
            fixModalButton(button, modalElement);
        });
        
        console.log(`${modalButtons.length} boutons de modale corrigés globalement`);
    }
    
    // Corrige un bouton de modale spécifique
    function fixModalButton(button, modalElement) {
        // Cloner et remplacer pour éliminer les gestionnaires d'événements existants
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Ajouter un nouveau gestionnaire d'événements direct
        newButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log(`Clic sur bouton pour ouvrir la modale #${modalElement.id}`);
            
            try {
                // S'assurer que la modale est correctement configurée avant de l'ouvrir
                ensureModalAttributes(modalElement);
                
                // Créer une nouvelle instance avec des options explicites
                const modalOptions = {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                };
                
                // Créer une nouvelle instance à chaque fois avec les options
                const modalInstance = new bootstrap.Modal(modalElement, modalOptions);
                modalInstance.show();
                console.log(`Modale #${modalElement.id} ouverte avec succès via Bootstrap`);
            } catch (error) {
                console.error(`Erreur lors de l'ouverture de la modale #${modalElement.id}:`, error);
                // En cas d'échec, utiliser la méthode manuelle
                manuallyOpenModal(modalElement);
            }
        });
    }
    
    // Ouvre manuellement une modale sans utiliser Bootstrap
    function manuallyOpenModal(modalElement) {
        console.log(`Ouverture manuelle de la modale #${modalElement.id}`);
        
        // S'assurer que la structure de la modale est correcte
        ensureModalAttributes(modalElement);
        
        // Ajouter les classes nécessaires pour afficher la modale
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.setAttribute('aria-modal', 'true');
        modalElement.setAttribute('role', 'dialog');
        modalElement.removeAttribute('aria-hidden');
        
        // Ajouter le backdrop s'il n'existe pas déjà
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
        backdrop.classList.add('modal-backdrop', 'fade', 'show');
        document.body.appendChild(backdrop);
        }
        
        // Empêcher le défilement du body
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
        
        // Gestionnaire pour fermer la modale
        const closeModal = function() {
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            modalElement.setAttribute('aria-hidden', 'true');
            modalElement.removeAttribute('aria-modal');
            modalElement.removeAttribute('role');
            
            // Supprimer le backdrop
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(el => el.remove());
            
            // Restaurer le défilement
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            
            console.log(`Modale #${modalElement.id} fermée manuellement`);
        };
        
        // Fermer la modale en cliquant sur les boutons de fermeture
        const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
        closeButtons.forEach(button => {
            // Réinitialiser les gestionnaires d'événements
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            newButton.addEventListener('click', closeModal);
        });
        
        // Fermer la modale en cliquant sur le backdrop
        backdrop.addEventListener('click', closeModal);
        
        // Fermer la modale avec la touche Escape
        const escHandler = function(event) {
            if (event.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
        
        console.log(`Modale #${modalElement.id} ouverte manuellement avec succès`);
    }
    
    // Correction spécifique pour les modales sur mobile
    function fixMobileModals() {
        console.log('Application des corrections spécifiques pour mobile...');
        
        // Liste des modales critiques sur mobile
        const mobileModalIds = ['ajouterCommandeModal', 'rechercheClientModal', 'changeStatusModal'];
        
        mobileModalIds.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.log(`Modale #${modalId} non trouvée pour correction mobile`);
                return;
            }
            
            console.log(`Correction de la modale #${modalId} pour mobile...`);
            
            // Ajouter une classe spécifique pour le style mobile
            modal.classList.add('mobile-optimized-modal');
            
            // Correction spécifique pour s'assurer que le backdrop fonctionne sur mobile
            if (modal._backdrop === undefined || modal._backdrop === null) {
                try {
                    // Réinitialiser l'instance
                    const oldInstance = bootstrap.Modal.getInstance(modal);
                    if (oldInstance) {
                        oldInstance.dispose();
                    }
                    
                    // S'assurer que la structure est correcte
                    ensureModalAttributes(modal);
                    
                    // Créer une nouvelle instance avec options explicites
                    const modalOptions = {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    };
                    new bootstrap.Modal(modal, modalOptions);
                } catch (e) {
                    console.warn(`Erreur lors de la réinitialisation de la modale mobile #${modalId}:`, e);
                }
            }
            
            // Pour la modale de commande, on s'assure que tous les boutons qui l'ouvrent fonctionnent
            if (modalId === 'ajouterCommandeModal') {
                // Ajouter une correction spécifique sur mobile pour les actions rapides
                const actionButtons = document.querySelectorAll('.action-card[data-bs-target="#ajouterCommandeModal"]');
                
                actionButtons.forEach((button, index) => {
                    console.log(`  - Correction du bouton d'action rapide ${index + 1} pour mobile`);
                    
                    // Cloner et remplacer pour éliminer les gestionnaires existants
                    const newButton = button.cloneNode(true);
                    button.parentNode.replaceChild(newButton, button);
                    
                    // Ajouter un nouveau gestionnaire pour le clic
                    newButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        console.log(`Clic sur bouton d'action rapide pour #${modalId} sur mobile`);
                        
                        try {
                            // Créer une nouvelle instance avec des options explicites
                            const modalOptions = {
                                backdrop: true,
                                keyboard: true,
                                focus: true
                            };
                            
                            // Assurez-vous que la modale a la structure requise
                            ensureModalAttributes(modal);
                            
                            const modalInstance = new bootstrap.Modal(modal, modalOptions);
                                modalInstance.show();
                            } catch (error) {
                            console.error(`Erreur lors de l'ouverture de #${modalId} depuis l'action rapide:`, error);
                            // En cas d'échec, utiliser la méthode manuelle
                                manuallyOpenModal(modal);
                            }
                    });
                });
            }
        });
    }
    
    // Patch de secours : remplacer les fonctions problématiques dans bootstrap.Modal
    function patchBootstrapModal() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            console.log('Bootstrap Modal non disponible pour patch');
            return;
        }
        
        try {
            console.log('Application du patch pour bootstrap.Modal...');
            
            // Patch pour _initializeFocusTrap (erreur trapElement)
            if (bootstrap.Modal.prototype._initializeFocusTrap) {
                const originalInitializeFocusTrap = bootstrap.Modal.prototype._initializeFocusTrap;
                
                bootstrap.Modal.prototype._initializeFocusTrap = function() {
                    try {
                        // Vérifier que this._element existe
                        if (!this._element) {
                            console.warn('Element manquant dans _initializeFocusTrap, création');
                            this._element = document.createElement('div');
                            this._element.className = 'modal';
                            document.body.appendChild(this._element);
                        }
                        
                        return originalInitializeFocusTrap.call(this);
                    } catch (e) {
                        console.warn('Erreur évitée dans _initializeFocusTrap:', e);
                        // Ne pas initialiser le trap si ça échoue
                        this._focustrap = { activate: function() {}, deactivate: function() {} };
                    }
                };
            }
            
            // Sauvegarde des méthodes originales
            const originalInitializeBackDrop = bootstrap.Modal.prototype._initializeBackDrop;
            const originalIsAnimated = bootstrap.Modal.prototype._isAnimated;
            
            // Patch pour _isAnimated
            bootstrap.Modal.prototype._isAnimated = function() {
                try {
                    if (!this._element) {
                        console.warn('Element manquant dans _isAnimated');
                        return false;
                    }
                    
                    if (!this._element.classList) {
                        console.warn('ClassList manquante dans _isAnimated');
                        return false;
                    }
                    
                    return originalIsAnimated.call(this);
                } catch (e) {
                    console.warn('Erreur évitée dans _isAnimated:', e);
                    return false;
                }
            };
            
            // Patch pour _initializeBackDrop
            bootstrap.Modal.prototype._initializeBackDrop = function() {
                try {
                    // Si this._config est undefined, créer un objet par défaut
                    if (!this._config) {
                        console.warn('Config manquante dans _initializeBackDrop, création');
                        this._config = {
                            backdrop: true,
                            keyboard: true,
                            focus: true
                        };
                    }
                    
                    // S'assurer que backdrop est défini
                    if (this._config.backdrop === undefined) {
                        this._config.backdrop = true;
                    }
                    
                    return originalInitializeBackDrop.call(this);
                } catch (e) {
                    console.warn('Erreur évitée dans _initializeBackDrop:', e);
                    // Implémentation minimale de secours
                    this._backdrop = document.createElement('div');
                    this._backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(this._backdrop);
                }
            };
            
            // Patch pour getOrCreateInstance
            const originalGetOrCreateInstance = bootstrap.Modal.getOrCreateInstance;
            
            bootstrap.Modal.getOrCreateInstance = function(element, config) {
                try {
                    if (!element) {
                        throw new Error('Élément manquant pour getOrCreateInstance');
                    }
                    
                    // Vérifier si l'élément est un sélecteur de chaîne
                    if (typeof element === 'string') {
                        element = document.querySelector(element);
                        if (!element) {
                            throw new Error(`Élément "${element}" non trouvé`);
                        }
                    }
                    
                    // S'assurer que l'élément a la structure nécessaire
                    if (element.classList && !element.classList.contains('modal')) {
                        element.classList.add('modal');
                    }
                    
                    // Configurer un objet config par défaut
                    const safeConfig = config || {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    };
                    
                    return originalGetOrCreateInstance.call(this, element, safeConfig);
                } catch (e) {
                    console.error('Erreur dans getOrCreateInstance:', e);
                    // Créer une nouvelle instance en cas d'erreur
                    return new bootstrap.Modal(element, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                }
            };
            
            console.log('Patch appliqué avec succès à bootstrap.Modal');
        } catch (e) {
            console.error('Erreur lors de l\'application du patch bootstrap.Modal:', e);
        }
    }
    
    // Exécuter l'initialisation et les correctifs quand le DOM est chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initModalFix();
        });
    } else {
        initModalFix();
    }
    
    // Appliquer à nouveau les correctifs lors des changements de taille d'écran
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            fixMobileModals();
        }
    });
    
    // Observer les modifications du DOM pour réparer les modales ajoutées dynamiquement
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            for (const mutation of mutations) {
                if (mutation.type === 'childList') {
                    for (const node of mutation.addedNodes) {
                        if (node.nodeType === 1) { // Élément
                            // Vérifier si c'est une modale ou si elle contient des modales
                            if (node.classList && node.classList.contains('modal')) {
                                console.log('Nouvelle modale détectée dans le DOM:', node.id);
                                ensureModalAttributes(node);
                            } else if (node.querySelectorAll) {
                                const modals = node.querySelectorAll('.modal');
                                if (modals.length > 0) {
                                    console.log(`${modals.length} nouvelles modales détectées dans les éléments ajoutés`);
                                    modals.forEach(modal => ensureModalAttributes(modal));
                                }
                            }
                        }
                    }
                }
            }
        });
        
        // Observer les modifications du body
        observer.observe(document.body, { childList: true, subtree: true });
        console.log('Observateur de modales configuré');
    }
    
    // Réexporter les fonctions pour pouvoir les utiliser depuis d'autres scripts
    window.modalFixUtils = {
        fixModalButton: fixModalButton,
        manuallyOpenModal: manuallyOpenModal,
        ensureModalAttributes: ensureModalAttributes,
        patchBootstrapModal: patchBootstrapModal,
        fixSpecificModals: fixSpecificModals
    };
})(); 