/**
 * Bootstrap Fixer - S'assure que Bootstrap est correctement chargé et fonctionnel
 * Ce script est une solution alternative qui fonctionne même si bootstrap n'est pas chargé correctement
 */

(function() {
    // Fonction pour vérifier si Bootstrap est disponible
    function isBootstrapAvailable() {
        return typeof bootstrap !== 'undefined';
    }

    // Fonction pour charger Bootstrap si nécessaire
    function loadBootstrap() {
        return new Promise((resolve, reject) => {
            if (isBootstrapAvailable()) {
                console.log('Bootstrap est déjà chargé.');
                resolve(window.bootstrap);
                return;
            }

            console.log('Chargement de Bootstrap...');
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js';
            script.integrity = 'sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN';
            script.crossOrigin = 'anonymous';
            
            script.onload = () => {
                console.log('Bootstrap chargé avec succès.');
                resolve(window.bootstrap);
            };
            
            script.onerror = (error) => {
                console.error('Erreur lors du chargement de Bootstrap:', error);
                reject(error);
            };
            
            document.head.appendChild(script);
        });
    }

    // Fonction pour initialiser manuellement les modales
    function initializeModals() {
        if (!isBootstrapAvailable()) return;
        
        console.log('Initialisation des modales...');
        
        // Trouver toutes les modales
        const modalElements = document.querySelectorAll('.modal');
        
        modalElements.forEach(modalElement => {
            try {
                // Vérifier si cette modale est déjà initialisée
                if (!bootstrap.Modal.getInstance(modalElement)) {
                    // Initialiser la modale
                    new bootstrap.Modal(modalElement);
                    console.log(`Modale initialisée: ${modalElement.id || 'sans ID'}`);
                }
            } catch (error) {
                console.error(`Erreur lors de l'initialisation de la modale ${modalElement.id || 'sans ID'}:`, error);
            }
        });
    }

    // Fonction pour réparer les boutons qui utilisent des modales
    function fixModalToggleButtons() {
        console.log('Réparation des boutons de modale...');
        
        // Sélectionner tous les éléments avec data-bs-toggle="modal"
        const modalToggleButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
        
        modalToggleButtons.forEach(button => {
            const modalTargetId = button.getAttribute('data-bs-target');
            if (!modalTargetId) return;
            
            const modalElement = document.querySelector(modalTargetId);
            if (!modalElement) return;
            
            // Supprimer et recréer l'écouteur d'événements pour éviter les doublons
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', (event) => {
                event.preventDefault();
                
                try {
                    if (isBootstrapAvailable()) {
                        const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                        modalInstance.show();
                    } else {
                        console.warn('Bootstrap n\'est pas disponible, impossible d\'ouvrir la modale');
                        // Chargement asynchrone de Bootstrap puis ouverture de la modale
                        loadBootstrap().then(() => {
                            const modalInstance = new bootstrap.Modal(modalElement);
                            modalInstance.show();
                        });
                    }
                } catch (error) {
                    console.error(`Erreur lors de l'ouverture de la modale ${modalTargetId}:`, error);
                }
            });
            
            console.log(`Bouton réparé pour la modale: ${modalTargetId}`);
        });
    }

    // Exécuter les fonctions de réparation après le chargement du DOM
    document.addEventListener('DOMContentLoaded', () => {
        if (isBootstrapAvailable()) {
            console.log('Bootstrap est disponible, initialisation...');
            initializeModals();
            fixModalToggleButtons();
        } else {
            console.log('Bootstrap n\'est pas disponible, chargement...');
            loadBootstrap()
                .then(() => {
                    initializeModals();
                    fixModalToggleButtons();
                })
                .catch(error => {
                    console.error('Impossible de charger Bootstrap:', error);
                });
        }
    });

    // Réexécuter les fonctions de réparation après un délai (pour s'assurer que tout est chargé)
    setTimeout(() => {
        if (isBootstrapAvailable()) {
            initializeModals();
            fixModalToggleButtons();
        } else {
            loadBootstrap()
                .then(() => {
                    initializeModals();
                    fixModalToggleButtons();
                });
        }
    }, 2000); // Attendre 2 secondes pour s'assurer que tout est chargé
})(); 