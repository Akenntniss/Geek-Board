/**
 * Script pour tester les modes PWA et Dynamic Island sur desktop
 */
document.addEventListener('DOMContentLoaded', function() {
    // Créer le bouton de test dans la barre de navigation
    const createPwaTestButton = () => {
        const topNavRight = document.querySelector('.top-nav-right');
        if (!topNavRight) return;
        
        // Créer le dropdown pour les options PWA
        const pwaTestDropdown = document.createElement('div');
        pwaTestDropdown.className = 'dropdown';
        pwaTestDropdown.innerHTML = `
            <button class="nav-icon-btn dropdown-toggle" type="button" id="pwaTestDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Tester le mode PWA">
                <i class="fas fa-mobile-alt"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="pwaTestDropdown">
                <li><h6 class="dropdown-header">Tester les modes d'affichage</h6></li>
                <li><button class="dropdown-item" id="testPwaModeBtn"><i class="fas fa-mobile-alt me-2"></i> Mode PWA normal</button></li>
                <li><button class="dropdown-item" id="testDynamicIslandBtn"><i class="fas fa-mobile-screen-button me-2"></i> Mode Dynamic Island</button></li>
                <li><button class="dropdown-item" id="resetModeBtn"><i class="fas fa-undo me-2"></i> Mode navigateur normal</button></li>
            </ul>
        `;
        
        // Insérer avant le dernier élément (généralement le bouton menu sur mobile)
        const lastChild = topNavRight.lastElementChild;
        topNavRight.insertBefore(pwaTestDropdown, lastChild);
        
        // Ajouter les événements aux boutons
        document.getElementById('testPwaModeBtn').addEventListener('click', activatePwaMode);
        document.getElementById('testDynamicIslandBtn').addEventListener('click', activateDynamicIslandMode);
        document.getElementById('resetModeBtn').addEventListener('click', resetToNormalMode);
    };
    
    // Activer le mode PWA
    const activatePwaMode = () => {
        document.body.classList.remove('browser-mode', 'ios-dynamic-island');
        document.body.classList.add('pwa-mode');
        localStorage.setItem('test-mode', 'pwa');
        showToast('Mode PWA activé');
    };
    
    // Activer le mode Dynamic Island
    const activateDynamicIslandMode = () => {
        document.body.classList.remove('browser-mode');
        document.body.classList.add('pwa-mode', 'ios-dynamic-island');
        localStorage.setItem('test-mode', 'dynamic-island');
        showToast('Mode Dynamic Island activé');
    };
    
    // Revenir au mode navigateur normal
    const resetToNormalMode = () => {
        document.body.classList.remove('pwa-mode', 'ios-dynamic-island');
        document.body.classList.add('browser-mode');
        localStorage.removeItem('test-mode');
        showToast('Mode navigateur normal restauré');
    };
    
    // Afficher une notification
    const showToast = (message) => {
        if (typeof toastr !== 'undefined') {
            toastr.info(message);
        } else {
            alert(message);
        }
    };
    
    // Restaurer le mode de test précédent si présent
    const restorePreviousMode = () => {
        const testMode = localStorage.getItem('test-mode');
        if (testMode === 'pwa') {
            activatePwaMode();
        } else if (testMode === 'dynamic-island') {
            activateDynamicIslandMode();
        }
    };
    
    // Initialiser
    createPwaTestButton();
    restorePreviousMode();
}); 