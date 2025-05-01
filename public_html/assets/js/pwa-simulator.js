/**
 * PWA Simulator
 * 
 * Ce script permet de simuler le comportement d'une PWA et différentes configurations
 * d'affichage (iOS, notch, dynamic island) directement dans le navigateur via des
 * paramètres d'URL.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier les paramètres d'URL pour activer la simulation
    const urlParams = new URLSearchParams(window.location.search);
    const pwaMode = urlParams.get('test_pwa');
    const iosMode = urlParams.get('test_ios');
    const dynamicIsland = urlParams.get('dynamic_island');
    const notch = urlParams.get('notch');
    
    // Activer le mode PWA si demandé
    if (pwaMode === 'true') {
        document.body.classList.add('pwa-mode');
        console.log('Mode PWA activé via URL parameter');
        
        // Vérifier si le mode iOS est également activé
        if (iosMode === 'true') {
            document.body.classList.add('ios-pwa');
            console.log('Mode iOS PWA activé via URL parameter');
            
            // Vérifier si l'option notch est activée
            if (notch === 'true') {
                document.body.classList.add('ios-notch');
                console.log('Mode notch iOS activé via URL parameter');
            }
            
            // Vérifier si l'option dynamic island est activée
            if (dynamicIsland === 'true') {
                document.body.classList.add('ios-dynamic-island');
                console.log('Mode dynamic island iOS activé via URL parameter');
            }
        }
        
        // Ajouter les méta-données de viewport spécifiques pour simuler le comportement PWA
        const viewportMeta = document.querySelector('meta[name="viewport"]');
        if (viewportMeta) {
            viewportMeta.setAttribute('content', 'width=device-width, initial-scale=1.0, viewport-fit=cover');
        } else {
            const meta = document.createElement('meta');
            meta.name = 'viewport';
            meta.content = 'width=device-width, initial-scale=1.0, viewport-fit=cover';
            document.head.appendChild(meta);
        }
        
        // Ajouter un message d'information pour indiquer le mode de simulation
        showPWASimulationInfo();
    }
    
    // Ajouter des liens dans le footer pour tester différentes configurations
    addPWATestLinks();
});

/**
 * Affiche une bannière d'information sur le mode PWA activé
 */
function showPWASimulationInfo() {
    const infoDiv = document.createElement('div');
    infoDiv.className = 'pwa-simulation-info';
    infoDiv.innerHTML = `
        <div class="pwa-sim-banner">
            <span>Mode simulation PWA activé</span>
            <button class="pwa-sim-close">×</button>
        </div>
    `;
    
    document.body.appendChild(infoDiv);
    
    // Style pour la bannière d'information
    const style = document.createElement('style');
    style.textContent = `
        .pwa-simulation-info {
            position: fixed;
            bottom: 80px;
            left: 10px;
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .pwa-sim-banner {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            font-size: 14px;
            max-width: 220px;
        }
        
        .pwa-sim-close {
            margin-left: 10px;
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0 5px;
        }
    `;
    
    document.head.appendChild(style);
    
    // Gérer la fermeture de la bannière
    const closeButton = infoDiv.querySelector('.pwa-sim-close');
    closeButton.addEventListener('click', function() {
        infoDiv.remove();
    });
}

/**
 * Ajoute des liens de test dans le footer pour essayer différentes configurations
 */
function addPWATestLinks() {
    // Créer le conteneur de liens de test
    const testLinksContainer = document.createElement('div');
    testLinksContainer.className = 'pwa-test-links';
    
    // Récupérer la page actuelle sans paramètres
    const currentPage = window.location.pathname;
    
    // Préserver les paramètres d'URL existants sauf ceux liés au mode PWA
    const currentParams = new URLSearchParams(window.location.search);
    const paramsToKeep = new URLSearchParams();
    
    // Copier tous les paramètres existants sauf ceux liés au PWA test
    currentParams.forEach((value, key) => {
        if (!['test_pwa', 'test_ios', 'dynamic_island', 'notch'].includes(key)) {
            paramsToKeep.append(key, value);
        }
    });
    
    // Préparer les paramètres pour chaque lien
    const baseParams = paramsToKeep.toString() ? `${paramsToKeep.toString()}&` : '';
    const pwaParams = `${baseParams}test_pwa=true`;
    const iosParams = `${baseParams}test_pwa=true&test_ios=true`;
    const notchParams = `${baseParams}test_pwa=true&test_ios=true&notch=true`;
    const dynamicIslandParams = `${baseParams}test_pwa=true&test_ios=true&dynamic_island=true`;
    
    // Ajouter les liens de test
    testLinksContainer.innerHTML = `
        <div class="test-links-container">
            <h5>Tester en mode:</h5>
            <div class="links-grid">
                <a href="${currentPage}?${pwaParams}" class="test-link">PWA Standard</a>
                <a href="${currentPage}?${iosParams}" class="test-link">iOS PWA</a>
                <a href="${currentPage}?${notchParams}" class="test-link">iOS avec Notch</a>
                <a href="${currentPage}?${dynamicIslandParams}" class="test-link">Dynamic Island</a>
                <a href="${currentPage}${paramsToKeep.toString() ? '?' + paramsToKeep.toString() : ''}" class="test-link reset">Mode Normal</a>
            </div>
        </div>
    `;
    
    // Style pour les liens de test
    const style = document.createElement('style');
    style.textContent = `
        .pwa-test-links {
            position: fixed;
            bottom: 15px;
            right: 15px;
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .test-links-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 15px;
            border: 1px solid #e0e0e0;
            max-width: 300px;
        }
        
        .test-links-container h5 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }
        
        .links-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .test-link {
            display: block;
            padding: 8px 10px;
            background-color: #0078e8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .test-link:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        
        .test-link.reset {
            grid-column: span 2;
            background-color: #6c757d;
        }
        
        .test-link.reset:hover {
            background-color: #5a6268;
        }
        
        @media (prefers-color-scheme: dark) {
            body.supports-dark-mode .test-links-container {
                background-color: rgba(45, 45, 45, 0.95);
                border-color: #444;
            }
            
            body.supports-dark-mode .test-links-container h5 {
                color: #e0e0e0;
            }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(testLinksContainer);
    
    // Ajouter un bouton pour afficher/masquer les liens de test
    const toggleButton = document.createElement('button');
    toggleButton.className = 'toggle-test-links';
    toggleButton.innerHTML = '<span>PWA Test</span>';
    
    const toggleStyle = document.createElement('style');
    toggleStyle.textContent = `
        .toggle-test-links {
            position: fixed;
            bottom: 15px;
            right: 15px;
            z-index: 10000;
            background-color: #0078e8;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .toggle-test-links:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        
        .toggle-test-links.active {
            background-color: #dc3545;
        }
        
        .pwa-test-links {
            display: none;
        }
        
        .pwa-test-links.visible {
            display: block;
        }
    `;
    
    document.head.appendChild(toggleStyle);
    document.body.appendChild(toggleButton);
    
    // Gérer le clic sur le bouton
    toggleButton.addEventListener('click', function() {
        const linksContainer = document.querySelector('.pwa-test-links');
        linksContainer.classList.toggle('visible');
        toggleButton.classList.toggle('active');
    });
} 