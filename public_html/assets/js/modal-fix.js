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
        
        // On s'assure que le backdrop est statique pour éviter les problèmes
        modalElement.setAttribute('data-bs-backdrop', 'static');
        modalElement.setAttribute('data-bs-keyboard', 'false');
        
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
                // Créer une nouvelle instance à chaque fois
                const modalInstance = new bootstrap.Modal(modalElement);
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
        
        // Ajouter les classes nécessaires pour afficher la modale
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.setAttribute('aria-modal', 'true');
        modalElement.setAttribute('role', 'dialog');
        modalElement.removeAttribute('aria-hidden');
        
        // Ajouter le backdrop
        const backdrop = document.createElement('div');
        backdrop.classList.add('modal-backdrop', 'fade', 'show');
        document.body.appendChild(backdrop);
        
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
        const mobileModalIds = ['ajouterCommandeModal', 'rechercheClientModal'];
        
        mobileModalIds.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.log(`Modale #${modalId} non trouvée pour correction mobile`);
                return;
            }
            
            console.log(`Correction de la modale #${modalId} pour mobile...`);
            
            // Ajouter une classe spécifique pour le style mobile
            modal.classList.add('mobile-optimized-modal');
            
            // Pour la modale de commande, on s'assure que tous les boutons qui l'ouvrent fonctionnent
            if (modalId === 'ajouterCommandeModal') {
                // Ajouter une correction spécifique sur mobile pour les actions rapides
                const actionButtons = document.querySelectorAll('.action-card[data-bs-target="#ajouterCommandeModal"]');
                
                actionButtons.forEach((button, index) => {
                    console.log(`- Correction de l'action rapide ${index + 1} pour la modale #${modalId}`);
                    
                    // Ajouter une classe pour le debugging
                    button.classList.add('mobile-fixed-button');
                    
                    // Supprimer tous les gestionnaires d'événements existants
                    const newButton = button.cloneNode(true);
                    button.parentNode.replaceChild(newButton, button);
                    
                    // Ajouter un gestionnaire d'événement direct avec feedback tactile
                    newButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        console.log(`Clic sur action rapide pour ouvrir #${modalId} sur mobile`);
                        
                        // Feedback tactile sur appareils mobiles
                        if ('vibrate' in navigator) {
                            navigator.vibrate(50);
                        }
                        
                        // Forcer l'ouverture du modal
                        setTimeout(() => {
                            try {
                                // Tenter d'utiliser l'API Bootstrap
                                let modalInstance = bootstrap.Modal.getInstance(modal);
                                if (!modalInstance) {
                                    modalInstance = new bootstrap.Modal(modal);
                                }
                                modalInstance.show();
                                console.log(`Modale #${modalId} ouverte avec succès via Bootstrap sur mobile`);
                            } catch (error) {
                                console.error(`Échec de l'ouverture Bootstrap sur mobile:`, error);
                                manuallyOpenModal(modal);
                            }
                        }, 100);
                    });
                });
            }
        });
    }
    
    // Exécuter la fonction d'initialisation après le chargement du DOM
    document.addEventListener('DOMContentLoaded', initModalFix);
    
    // Réexécuter après un court délai pour s'assurer que tout est chargé
    setTimeout(initModalFix, 1000);
    
    // Réappliquer lorsque la page change de taille (orientation mobile)
    window.addEventListener('resize', function() {
        console.log('Détection de changement de taille d\'écran, réapplication des corrections...');
        setTimeout(initModalFix, 300);
    });
})(); 