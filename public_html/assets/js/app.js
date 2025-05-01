// Désactivation du service worker qui cause des problèmes
// Commenté car crée trop d'erreurs dans la console et perturbe le fonctionnement de l'application

/* 
// Code d'enregistrement du service worker désactivé
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/service-worker.js')
            .then(function(registration) {
                console.log('ServiceWorker enregistré avec succès: ', registration.scope);
            })
            .catch(function(error) {
                console.log('Échec de l\'enregistrement du ServiceWorker: ', error);
            });
    });
}
*/

// Le service worker est désormais géré par le script principal dans header.php
// La désinstallation n'est effectuée que si le paramètre URL disableSW=true est présent

/* Code désactivé pour permettre le fonctionnement de la PWA
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        for (let registration of registrations) {
            registration.unregister();
            console.log('Service worker désenregistré');
        }
    }).catch(function(error) {
        console.error('Erreur lors du désenregistrement des service workers:', error);
    });
}
*/

// Amélioration des champs de recherche
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter des effets d'animation aux champs de recherche
    const searchInputs = document.querySelectorAll('.search-form .form-control, [id*="recherche_client"]');
    
    searchInputs.forEach(input => {
        // Effet lors du focus
        input.addEventListener('focus', function() {
            const inputGroup = this.closest('.input-group');
            if (inputGroup) {
                inputGroup.style.boxShadow = '0 0 0 0.2rem rgba(13, 110, 253, 0.25)';
                
                // Animer l'icône de recherche
                const searchIcon = inputGroup.querySelector('.fa-search');
                if (searchIcon) {
                    searchIcon.classList.add('text-primary');
                }
            }
        });
        
        // Effet lors de la perte du focus
        input.addEventListener('blur', function() {
            const inputGroup = this.closest('.input-group');
            if (inputGroup) {
                inputGroup.style.boxShadow = '';
                
                // Remettre l'icône par défaut si le champ est vide
                const searchIcon = inputGroup.querySelector('.fa-search');
                if (searchIcon && this.value === '') {
                    searchIcon.classList.remove('text-primary');
                }
            }
        });
    });
});

// Assurer que la page s'affiche correctement dès le chargement
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour corriger l'espace en haut de façon définitive
    function fixTopSpacing() {
        // Forcer tous les éléments qui pourraient avoir un espace indésirable
        const elementsToFix = [
            document.documentElement,
            document.body,
            document.querySelector('.container-fluid'),
            document.querySelector('main'),
            document.querySelector('.main-container'),
            document.querySelector('.content'),
            document.querySelector('.row')
        ];
        
        // Appliquer les corrections
        elementsToFix.forEach(el => {
            if (el) {
                el.style.marginTop = '0';
                el.style.paddingTop = '0';
            }
        });
        
        // Ajuster la barre latérale
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.style.top = '0';
        }
        
        // Vérifier et ajuster spécifiquement la classe .row qui peut ajouter des marges
        const rows = document.querySelectorAll('.row');
        rows.forEach(row => {
            row.style.margin = '0';
        });
        
        // Ajouter un peu d'espace sous la navigation sur desktop uniquement
        if (window.innerWidth >= 992) {
            const content = document.querySelector('.content');
            if (content) {
                content.style.paddingTop = '1rem';
            }
        }
    }
    
    // Exécuter immédiatement
    fixTopSpacing();
    
    // Ré-exécuter après un court délai pour s'assurer que tout est chargé
    setTimeout(fixTopSpacing, 100);
    
    // Ré-exécuter après le chargement complet de la page
    window.addEventListener('load', fixTopSpacing);
    
    // Ré-exécuter lors du redimensionnement de la fenêtre
    window.addEventListener('resize', fixTopSpacing);
});