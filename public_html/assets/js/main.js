/**
 * Fichier JavaScript principal pour gérer les interactions côté client
 */

document.addEventListener('DOMContentLoaded', function() {
    // ===== Initialisation du mode sombre =====
    initDarkMode();
    
    // ===== Force le rechargement des styles CSS =====
    forceStyleReload();
    
    // ===== Initialisation des tooltips =====
    initTooltips();
    
    // ===== Gestion des toasts de notification =====
    initToasts();
    
    // ===== Gestion de la recherche en temps réel =====
    initRealTimeSearch();
    
    // ===== Gestion des modales imbriquées =====
    initNestedModals();
    
    // ===== Gestion des formulaires avec validation =====
    initFormValidation();
    
    // ===== Gestion des animations de chargement =====
    initLoadingAnimations();
});

/**
 * Initialise le mode sombre
 */
function initDarkMode() {
    const toggleDarkMode = document.getElementById('toggleDarkMode');
    
    if (toggleDarkMode) {
        // Ajouter la classe d'apparence au bouton
        toggleDarkMode.classList.add('theme-toggle-btn');
        
        // Vérifier d'abord les préférences système
        const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
        
        // Vérifier si le mode sombre est déjà activé dans le localStorage
        const savedDarkMode = localStorage.getItem('darkMode');
        
        // Appliquer le mode sombre selon les préférences
        if (savedDarkMode === 'true' || (savedDarkMode === null && prefersDarkScheme.matches)) {
            document.body.classList.add('dark-mode');
            updateDarkModeIcon(toggleDarkMode, true);
            localStorage.setItem('darkMode', 'true');
        } else {
            document.body.classList.remove('dark-mode');
            updateDarkModeIcon(toggleDarkMode, false);
        }
        
        // Ajouter l'événement de clic pour basculer le mode
        toggleDarkMode.addEventListener('click', function() {
            // Ajouter une animation lorsque le bouton est cliqué
            toggleDarkMode.classList.add('theme-toggle-animate');
            
            // Retirer la classe d'animation après la fin de l'animation
            setTimeout(() => {
                toggleDarkMode.classList.remove('theme-toggle-animate');
            }, 500);
            
            const isDarkMode = document.body.classList.toggle('dark-mode');
            updateDarkModeIcon(toggleDarkMode, isDarkMode);
            localStorage.setItem('darkMode', isDarkMode);
        });
        
        // Écouter les changements de préférences système
        prefersDarkScheme.addEventListener('change', (e) => {
            if (localStorage.getItem('darkMode') === null) {
                if (e.matches) {
                    document.body.classList.add('dark-mode');
                    updateDarkModeIcon(toggleDarkMode, true);
                } else {
                    document.body.classList.remove('dark-mode');
                    updateDarkModeIcon(toggleDarkMode, false);
                }
            }
        });
    }
}

/**
 * Met à jour l'icône du mode sombre/clair
 * 
 * @param {HTMLElement} button Le bouton de toggle
 * @param {boolean} isDarkMode Si le mode sombre est activé
 */
function updateDarkModeIcon(button, isDarkMode) {
    const icon = button.querySelector('i');
    if (icon) {
        if (isDarkMode) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
            icon.style.color = '#fbbf24'; // couleur jaune soleil
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
            icon.style.color = '#4b5563'; // couleur grise lune
        }
    }
    
    // Mettre à jour l'attribut title pour l'accessibilité
    button.setAttribute('title', isDarkMode ? 'Passer au mode clair' : 'Passer au mode sombre');
    
    // Optionnel : Annoncer le changement pour les lecteurs d'écran
    const announcement = document.createElement('div');
    announcement.setAttribute('role', 'status');
    announcement.setAttribute('aria-live', 'polite');
    announcement.className = 'sr-only';
    announcement.textContent = isDarkMode ? 'Mode sombre activé' : 'Mode clair activé';
    document.body.appendChild(announcement);
    
    // Supprimer l'annonce après 1 seconde
    setTimeout(() => {
        document.body.removeChild(announcement);
    }, 1000);
}

/**
 * Initialise les tooltips Bootstrap
 */
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-bs-tooltip="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        });
    });
}

/**
 * Initialise les toasts de notification
 */
function initToasts() {
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function (toastEl) {
        var toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
        toast.show();
    });
}

/**
 * Affiche une notification toast
 * 
 * @param {string} message Message à afficher
 * @param {string} type Type de notification (success, danger, warning, info)
 */
function showNotification(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        // Créer le conteneur s'il n'existe pas
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    // Créer le toast
    const toastEl = document.createElement('div');
    toastEl.className = 'toast';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    
    // Construire le HTML du toast
    toastEl.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-info-circle me-2"></i>
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-${type} text-white">
            ${message}
        </div>
    `;
    
    // Ajouter le toast au conteneur
    const container = document.querySelector('.toast-container');
    container.appendChild(toastEl);
    
    // Initialiser et afficher le toast
    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: 5000
    });
    
    toast.show();
    
    // Supprimer le toast après qu'il ait été caché
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

/**
 * Initialise la recherche en temps réel
 */
function initRealTimeSearch() {
    const searchInputs = document.querySelectorAll('input[type="search"], input[id*="search"]');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const targetTable = this.getAttribute('data-search-target') || 'table';
            const table = document.querySelector(targetTable);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                });
            }
        });
    });
}

/**
 * Initialise la gestion des modales imbriquées
 */
function initNestedModals() {
    // Ajuster le z-index des modales Bootstrap lorsqu'elles sont imbriquées
    document.addEventListener('show.bs.modal', function(event) {
        const modals = document.querySelectorAll('.modal');
        const openModals = Array.from(modals).filter(modal => {
            return modal !== event.target && window.getComputedStyle(modal).display !== 'none';
        });
        
        if (openModals.length > 0) {
            // Ajuster les classes pour permettre aux modales de s'empiler
            event.target.classList.add('modal-stacked');
            
            // Ajuster le z-index de la nouvelle modale pour qu'elle soit au-dessus des autres
            const zIndex = Math.max(...openModals.map(modal => parseInt(window.getComputedStyle(modal).zIndex))) + 10;
            event.target.style.zIndex = zIndex.toString();
            
            const backdrop = document.querySelector('.modal-backdrop:last-child');
            if (backdrop) {
                backdrop.style.zIndex = (zIndex - 5).toString();
            }
        }
    });
}

/**
 * Initialise la validation des formulaires
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // Afficher l'animation de chargement
                showLoading();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Initialise les animations de chargement
 */
function initLoadingAnimations() {
    // Intercepter les clics sur les liens qui ne sont pas dans des modales
    const links = document.querySelectorAll('a:not([data-bs-toggle]):not([href^="#"]):not([target="_blank"])');
    
    links.forEach(link => {
        link.addEventListener('click', function(event) {
            // Ne pas afficher le chargement pour les liens de pagination, la recherche, etc.
            if (!this.classList.contains('no-loading')) {
                showLoading();
            }
        });
    });
}

/**
 * Affiche une animation de chargement
 */
function showLoading() {
    // Éviter les duplications
    if (document.querySelector('.loading-overlay')) {
        return;
    }
    
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
    
    document.body.appendChild(loadingOverlay);
}

/**
 * Masque l'animation de chargement
 */
function hideLoading() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    
    if (loadingOverlay) {
        loadingOverlay.classList.add('fade-out');
        
        setTimeout(() => {
            loadingOverlay.remove();
        }, 300); // Temps pour l'animation de fondu
    }
}

// Fonction pour afficher l'animation de chargement
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loading);
}

// Fonction pour masquer l'animation de chargement
function hideLoading() {
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Fonction pour afficher une notification toast
function showToast(message, type = 'success') {
    const toast = document.getElementById('notificationToast');
    if (toast) {
        const toastBody = toast.querySelector('.toast-body');
        if (toastBody) {
            toastBody.textContent = message;
            toast.className = `toast border-${type}`;
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }
    }
}

// Fonction pour confirmer une action
function confirmAction(message = 'Êtes-vous sûr de vouloir effectuer cette action ?') {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" id="confirmBtn">Confirmer</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.querySelector('#confirmBtn').addEventListener('click', () => {
            bsModal.hide();
            resolve(true);
        });

        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
            resolve(false);
        });
    });
}

// Fonction pour formater un montant
function formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Fonction pour formater une date
function formatDate(date) {
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// Fonction pour gérer les erreurs AJAX
function handleAjaxError(error) {
    console.error('Erreur AJAX:', error);
    showToast('Une erreur est survenue. Veuillez réessayer.', 'danger');
}

// Fonction pour faire une requête AJAX
async function makeAjaxRequest(url, method = 'GET', data = null) {
    try {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        handleAjaxError(error);
        throw error;
    }
}

/**
 * Force le rechargement des styles CSS
 * Utile quand certaines modifications de style ne sont pas appliquées
 */
function forceStyleReload() {
    // Récupérer toutes les feuilles de style
    const styleSheets = document.querySelectorAll('link[rel="stylesheet"]');
    
    // Pour chaque feuille de style, on ajoute un paramètre aléatoire pour forcer le rechargement
    styleSheets.forEach(styleSheet => {
        const href = styleSheet.getAttribute('href');
        if (href && !href.includes('?v=')) {
            const randomValue = Math.floor(Math.random() * 1000000);
            styleSheet.setAttribute('href', `${href}?v=${randomValue}`);
        }
    });
    
    // Vérifier si le mode sombre est actif
    const isDarkMode = document.body.classList.contains('dark-mode');
    
    // Forcer la réapplication du mode
    if (isDarkMode) {
        console.log('Réapplication du mode sombre pour les styles');
        document.body.classList.remove('dark-mode');
        setTimeout(() => {
            document.body.classList.add('dark-mode');
        }, 50);
    }
} 