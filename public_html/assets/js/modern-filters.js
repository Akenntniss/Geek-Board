/**
 * Modern Filters JS
 * Effets et interactions pour les filtres modernes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les effets pour les filtres
    initializeModernFilters();
    
    // Initialiser la barre de recherche
    initializeModernSearch();
    
    // Initialiser les boutons de vue
    initializeViewButtons();
});

/**
 * Initialise les effets pour les filtres modernes
 */
function initializeModernFilters() {
    const filters = document.querySelectorAll('.modern-filter');
    
    filters.forEach(filter => {
        // Effet de ripple (ondulation) au clic
        filter.addEventListener('click', function(e) {
            // Vérifier si l'élément a déjà une animation en cours
            const existingRipple = this.querySelector('.ripple-active');
            if (existingRipple) {
                existingRipple.remove();
            }
            
            // Créer l'élément de ripple
            const ripple = this.querySelector('.ripple');
            ripple.classList.add('ripple-active');
            
            // Positionner l'animation au point de clic
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            // Retirer la classe ripple-active après l'animation
            setTimeout(() => {
                ripple.classList.remove('ripple-active');
            }, 1000);
            
            // Récupérer les informations du filtre
            const categoryId = this.getAttribute('data-category-id');
            
            // Déterminer les IDs de statuts à filtrer
            let statusIds;
            switch (categoryId) {
                case '1': statusIds = '1,2,3'; break;
                case '2': statusIds = '4,5'; break;
                case '3': statusIds = '6,7,8'; break;
                case '4': statusIds = '9,10'; break;
                case '5': statusIds = '11,12,13'; break;
                default: statusIds = '1,2,3,4,5';
            }
            
            // Appliquer le filtre
            applyStatusFilter(statusIds);
        });
        
        // Effet de survol avancé
        filter.addEventListener('mouseenter', function() {
            // Animation du compte
            const count = this.querySelector('.filter-count');
            if (count) {
                count.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.2)' },
                    { transform: 'scale(1)' }
                ], {
                    duration: 500,
                    easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)'
                });
            }
            
            // Animation de l'icône
            const icon = this.querySelector('.filter-icon');
            if (icon) {
                icon.animate([
                    { transform: 'translateY(0)' },
                    { transform: 'translateY(-5px)' },
                    { transform: 'translateY(0)' }
                ], {
                    duration: 600,
                    easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)'
                });
            }
        });
    });
    
    /**
     * Applique un filtre par statut
     */
    function applyStatusFilter(statusIds) {
        // Récupérer le mode d'affichage actuel
        const viewMode = localStorage.getItem('repairViewMode') || 'cards';
        
        // Construire l'URL avec tous les paramètres
        let url = `index.php?page=reparations&statut_ids=${statusIds}&view=${viewMode}`;
        
        // Si une recherche est active, la conserver
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search');
        if (searchParam) {
            url += `&search=${encodeURIComponent(searchParam)}`;
        }
        
        // Animation de transition de page
        document.querySelector('.page-container').style.opacity = '0.7';
        document.querySelector('.page-container').style.transform = 'scale(0.98)';
        
        // Rediriger avec les bons paramètres
        setTimeout(() => {
            window.location.href = url;
        }, 300);
    }
}

/**
 * Initialise la barre de recherche moderne
 */
function initializeModernSearch() {
    const searchForm = document.querySelector('.modern-search form');
    const searchInput = document.querySelector('.modern-search .search-input');
    const searchBtn = document.querySelector('.modern-search .search-btn');
    const resetBtn = document.querySelector('.modern-search .reset-btn');
    
    if (!searchForm || !searchInput) return;
    
    // Effet de focus amélioré
    searchInput.addEventListener('focus', function() {
        this.closest('.search-wrapper').classList.add('focus');
        
        // Animation de l'icône
        const searchIcon = this.closest('.search-wrapper').querySelector('.search-icon');
        if (searchIcon) {
            searchIcon.animate([
                { transform: 'translateY(-50%) scale(1)' },
                { transform: 'translateY(-50%) scale(1.2)' },
                { transform: 'translateY(-50%) scale(1)' }
            ], {
                duration: 500,
                easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)'
            });
        }
    });
    
    searchInput.addEventListener('blur', function() {
        this.closest('.search-wrapper').classList.remove('focus');
    });
    
    // Animation du bouton de recherche au clic
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            if (searchInput.value.trim() === '') {
                // Animation d'erreur si vide
                searchInput.classList.add('error');
                searchInput.animate([
                    { transform: 'translateX(0)' },
                    { transform: 'translateX(-5px)' },
                    { transform: 'translateX(5px)' },
                    { transform: 'translateX(-5px)' },
                    { transform: 'translateX(0)' }
                ], {
                    duration: 400,
                    easing: 'ease-in-out'
                });
                
                setTimeout(() => {
                    searchInput.classList.remove('error');
                }, 400);
                
                return false;
            }
            
            // Animation du bouton lors de la soumission
            this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            this.disabled = true;
            
            // Animation de transition de page
            document.querySelector('.page-container').style.opacity = '0.8';
            document.querySelector('.page-container').style.transform = 'scale(0.98)';
            
            // Petite pause pour l'animation
            setTimeout(() => {
                searchForm.submit();
            }, 300);
        });
    }
    
    // Bouton de réinitialisation
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            // Animation du bouton
            this.animate([
                { transform: 'translateY(-50%) rotate(0)' },
                { transform: 'translateY(-50%) rotate(90deg)' },
                { transform: 'translateY(-50%) rotate(180deg)' }
            ], {
                duration: 300,
                easing: 'ease-out'
            });
            
            // Animation de transition de page
            document.querySelector('.page-container').style.opacity = '0.8';
            document.querySelector('.page-container').style.transform = 'scale(0.98)';
            
            // Délai pour l'animation
            setTimeout(() => {
                window.location.href = this.getAttribute('onclick').replace("window.location.href='", "").replace("')", "");
            }, 300);
            
            return false;
        });
    }
    
    // Suggestions de recherche (si la fonctionnalité existe)
    searchInput.addEventListener('input', function() {
        if (this.value.length >= 2 && typeof showSearchSuggestions === 'function') {
            showSearchSuggestions(this.value);
        }
    });
}

/**
 * Initialise les boutons de vue (tableau/cartes)
 */
function initializeViewButtons() {
    const viewButtons = document.querySelectorAll('.toggle-view');
    const tableView = document.getElementById('table-view');
    const cardsView = document.getElementById('cards-view');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const viewMode = this.getAttribute('data-view');
            
            // Mise à jour des boutons
            viewButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Animation de transition
            if (tableView && cardsView) {
                if (viewMode === 'table') {
                    // Passage aux cartes
                    cardsView.style.opacity = '0';
                    cardsView.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        cardsView.classList.add('d-none');
                        tableView.classList.remove('d-none');
                        
                        // Animation d'entrée
                        tableView.style.opacity = '0';
                        tableView.style.transform = 'translateY(-20px)';
                        
                        setTimeout(() => {
                            tableView.style.transition = 'all 0.5s ease';
                            tableView.style.opacity = '1';
                            tableView.style.transform = 'translateY(0)';
                        }, 50);
                    }, 300);
                } else {
                    // Passage au tableau
                    tableView.style.opacity = '0';
                    tableView.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        tableView.classList.add('d-none');
                        cardsView.classList.remove('d-none');
                        
                        // Animation d'entrée
                        cardsView.style.opacity = '0';
                        cardsView.style.transform = 'translateY(-20px)';
                        
                        setTimeout(() => {
                            cardsView.style.transition = 'all 0.5s ease';
                            cardsView.style.opacity = '1';
                            cardsView.style.transform = 'translateY(0)';
                            
                            // Réinitialiser les animations des cartes
                            if (typeof window.ModernCardAnimations === 'object' && 
                                typeof window.ModernCardAnimations.animateCardsSequentially === 'function') {
                                window.ModernCardAnimations.animateCardsSequentially();
                            }
                        }, 50);
                    }, 300);
                }
            }
            
            // Enregistrer la préférence
            localStorage.setItem('repairViewMode', viewMode);
            
            // Mettre à jour l'URL
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('view', viewMode);
            
            // Mettre à jour l'URL sans recharger la page
            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
            history.pushState({}, '', newUrl);
        });
    });
    
    // Charger la préférence de l'utilisateur au chargement
    const savedViewMode = localStorage.getItem('repairViewMode') || 'cards';
    const viewButton = document.querySelector(`.toggle-view[data-view="${savedViewMode}"]`);
    
    if (viewButton && !viewButton.classList.contains('active')) {
        setTimeout(() => {
            viewButton.click();
        }, 100);
    }
} 