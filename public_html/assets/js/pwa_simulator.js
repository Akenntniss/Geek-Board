/**
 * Script pour simuler différents appareils iOS en mode PWA
 */

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes en mode test PWA
    if (typeof isPwaTestMode !== 'undefined' && isPwaTestMode) {
        // Créer l'interface de simulation
        createPwaSimulator();
        
        // Appliquer le style de l'appareil sélectionné
        applyDeviceStyle(currentDevice);
    }
});

/**
 * Crée l'interface de simulation PWA
 */
function createPwaSimulator() {
    // Créer le conteneur principal
    const simulator = document.createElement('div');
    simulator.className = 'ios-simulator';
    document.body.appendChild(simulator);
    
    // Créer le panneau de contrôle
    const controls = document.createElement('div');
    controls.className = 'pwa-simulator-controls';
    controls.innerHTML = `
        <h4>Simulateur PWA iOS</h4>
        <select id="device-selector">
            <option value="iphone" ${currentDevice === 'iphone' ? 'selected' : ''}>iPhone Standard</option>
            <option value="iphone-notch" ${currentDevice === 'iphone-notch' ? 'selected' : ''}>iPhone avec Notch</option>
            <option value="iphone-dynamic-island" ${currentDevice === 'iphone-dynamic-island' ? 'selected' : ''}>iPhone avec Dynamic Island</option>
        </select>
        <button id="disable-simulator">Désactiver le simulateur</button>
    `;
    document.body.appendChild(controls);
    
    // Ajouter les événements
    document.getElementById('device-selector').addEventListener('change', function(e) {
        applyDeviceStyle(e.target.value);
        
        // Stocker la préférence dans sessionStorage
        sessionStorage.setItem('pwa_test_device', e.target.value);
        
        // Recharger la page avec le nouveau paramètre
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('device', e.target.value);
        window.location.href = currentUrl.toString();
    });
    
    document.getElementById('disable-simulator').addEventListener('click', function() {
        // Rediriger vers la même page avec le paramètre pour désactiver
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('disable_test_pwa', 'true');
        window.location.href = currentUrl.toString();
    });
}

/**
 * Applique le style correspondant à l'appareil sélectionné
 * @param {string} deviceType - Type d'appareil à simuler
 */
function applyDeviceStyle(deviceType) {
    // Supprimer les éléments existants
    const existingElements = document.querySelectorAll('.ios-status-bar, .ios-notch, .ios-dynamic-island, .ios-home-indicator');
    existingElements.forEach(el => el.remove());
    
    // Réinitialiser les classes de padding sur le body
    document.body.classList.remove('ios-standard-padding', 'ios-notch-padding', 'ios-dynamic-island-padding');
    
    // Ajouter l'indicateur home en bas (commun à tous les appareils)
    const homeIndicator = document.createElement('div');
    homeIndicator.className = 'ios-home-indicator';
    document.body.appendChild(homeIndicator);
    
    switch (deviceType) {
        case 'iphone-notch':
            // Ajouter la notch
            const notch = document.createElement('div');
            notch.className = 'ios-notch';
            document.body.appendChild(notch);
            
            // Ajouter le padding approprié
            document.body.classList.add('ios-notch-padding');
            
            // Ajuster les éléments de l'interface si nécessaire
            adjustInterfaceForNotch();
            break;
            
        case 'iphone-dynamic-island':
            // Ajouter la Dynamic Island
            const dynamicIsland = document.createElement('div');
            dynamicIsland.className = 'ios-dynamic-island';
            document.body.appendChild(dynamicIsland);
            
            // Ajouter le padding approprié
            document.body.classList.add('ios-dynamic-island-padding');
            
            // Ajuster les éléments de l'interface si nécessaire
            adjustInterfaceForDynamicIsland();
            break;
            
        default: // iPhone standard
            // Ajouter la barre de statut standard
            const statusBar = document.createElement('div');
            statusBar.className = 'ios-status-bar';
            statusBar.innerHTML = `
                <span>${getCurrentTime()}</span>
                <span>
                    <i class="fas fa-wifi"></i>
                    <i class="fas fa-battery-three-quarters"></i>
                </span>
            `;
            document.body.appendChild(statusBar);
            
            // Ajouter le padding approprié
            document.body.classList.add('ios-standard-padding');
            break;
    }
}

/**
 * Ajuste l'interface pour les appareils avec notch
 */
function adjustInterfaceForNotch() {
    // Ajuster les éléments de navigation si nécessaire
    const navbar = document.getElementById('desktop-navbar');
    if (navbar) {
        navbar.style.paddingTop = '20px';
    }
}

/**
 * Ajuste l'interface pour les appareils avec Dynamic Island
 */
function adjustInterfaceForDynamicIsland() {
    // Ajuster les éléments de navigation si nécessaire
    const navbar = document.getElementById('desktop-navbar');
    if (navbar) {
        navbar.style.paddingTop = '30px';
    }
}

/**
 * Retourne l'heure actuelle au format HH:MM
 * @returns {string} Heure actuelle
 */
function getCurrentTime() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
}