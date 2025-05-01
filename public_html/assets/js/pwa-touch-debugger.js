/**
 * Outil de débogage pour les interactions tactiles sur PWA
 * Particulièrement utile pour diagnostiquer les problèmes sur iPad
 */

(function() {
    // Attendre que le DOM soit chargé
    document.addEventListener('DOMContentLoaded', function() {
        // Activer uniquement si le paramètre debug=touch est présent dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const debugTouch = urlParams.get('debug') === 'touch';
        
        if (!debugTouch) return;
        
        console.log("🔍 Touch debugger activé");
        
        // Créer une console de débogage visuelle
        createDebugConsole();
        
        // Détecter le dispositif
        const deviceInfo = {
            isIPad: /iPad/.test(navigator.userAgent) || (/Macintosh/.test(navigator.userAgent) && 'ontouchend' in document),
            isIOS: /iPhone|iPad|iPod/.test(navigator.userAgent) && !window.MSStream,
            isPWA: window.matchMedia('(display-mode: standalone)').matches || 
                   window.navigator.standalone || 
                   document.body.classList.contains('pwa-mode'),
            isLandscape: window.innerWidth > window.innerHeight
        };
        
        logDebug("Infos appareil:");
        logDebug(`- iPad: ${deviceInfo.isIPad}`);
        logDebug(`- iOS: ${deviceInfo.isIOS}`);
        logDebug(`- PWA: ${deviceInfo.isPWA}`);
        logDebug(`- Paysage: ${deviceInfo.isLandscape}`);
        logDebug(`- UA: ${navigator.userAgent}`);
        
        // Analyser et afficher les éléments critiques
        const criticalElements = ['.navbar', 'header', '#desktop-navbar', '.fixed-top', '.sticky-top', 'header .btn', '.navbar .btn', '.nav-link'];
        
        logDebug("Éléments critiques:");
        criticalElements.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            logDebug(`- ${selector}: ${elements.length} trouvés`);
            
            elements.forEach((el, index) => {
                // Obtenir les informations sur l'élément
                const computedStyle = window.getComputedStyle(el);
                const rect = el.getBoundingClientRect();
                
                // Ajouter un identifiant pour le débogage
                const debugId = `debug-id-${selector.replace(/[^a-z0-9]/gi, '')}-${index}`;
                el.setAttribute('data-debug-id', debugId);
                
                // Ajouter une couche visuelle
                const visualLayer = document.createElement('div');
                visualLayer.className = 'debug-visual-layer';
                visualLayer.style.position = 'absolute';
                visualLayer.style.left = `${rect.left}px`;
                visualLayer.style.top = `${rect.top}px`;
                visualLayer.style.width = `${rect.width}px`;
                visualLayer.style.height = `${rect.height}px`;
                visualLayer.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                visualLayer.style.border = '1px solid red';
                visualLayer.style.zIndex = '9999';
                visualLayer.style.pointerEvents = 'none';
                visualLayer.style.fontSize = '10px';
                visualLayer.style.color = 'white';
                visualLayer.innerHTML = debugId;
                document.body.appendChild(visualLayer);
                
                // Afficher les informations sur l'élément
                logDebug(`  [${debugId}] z-index: ${computedStyle.zIndex}, position: ${computedStyle.position}, pointer-events: ${computedStyle.pointerEvents}`);
                logDebug(`  [${debugId}] rect: ${Math.round(rect.left)},${Math.round(rect.top)} - ${Math.round(rect.width)}x${Math.round(rect.height)}`);
                
                // Ajouter des écouteurs d'événements pour le débogage
                el.addEventListener('touchstart', function(e) {
                    logDebug(`👆 touchstart sur ${debugId}`);
                    visualLayer.style.backgroundColor = 'rgba(0, 255, 0, 0.5)';
                    setTimeout(() => {
                        visualLayer.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                    }, 500);
                });
                
                el.addEventListener('click', function(e) {
                    logDebug(`🖱️ click sur ${debugId}`);
                    visualLayer.style.backgroundColor = 'rgba(0, 0, 255, 0.5)';
                    setTimeout(() => {
                        visualLayer.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                    }, 500);
                });
            });
        });
        
        // Surveiller les changements d'orientation
        window.addEventListener('orientationchange', function() {
            logDebug("📱 Changement d'orientation");
            setTimeout(() => {
                deviceInfo.isLandscape = window.innerWidth > window.innerHeight;
                logDebug(`- Nouvelle orientation: ${deviceInfo.isLandscape ? 'Paysage' : 'Portrait'}`);
                updateVisualLayers();
            }, 300);
        });
        
        // Surveiller les redimensionnements
        window.addEventListener('resize', debounce(function() {
            logDebug("🔄 Redimensionnement");
            deviceInfo.isLandscape = window.innerWidth > window.innerHeight;
            logDebug(`- Dimensions: ${window.innerWidth}x${window.innerHeight}`);
            updateVisualLayers();
        }, 250));
        
        // Fonction pour mettre à jour les couches visuelles
        function updateVisualLayers() {
            document.querySelectorAll('.debug-visual-layer').forEach(layer => {
                const debugId = layer.textContent;
                const el = document.querySelector(`[data-debug-id="${debugId}"]`);
                if (el) {
                    const rect = el.getBoundingClientRect();
                    layer.style.left = `${rect.left}px`;
                    layer.style.top = `${rect.top}px`;
                    layer.style.width = `${rect.width}px`;
                    layer.style.height = `${rect.height}px`;
                }
            });
        }
        
        // Fonction pour débouncer les appels de fonction
        function debounce(func, wait) {
            let timeout;
            return function() {
                clearTimeout(timeout);
                timeout = setTimeout(func, wait);
            };
        }
        
        // Créer la console de débogage
        function createDebugConsole() {
            const console = document.createElement('div');
            console.className = 'touch-debug-console';
            console.style.position = 'fixed';
            console.style.left = '10px';
            console.style.bottom = '10px';
            console.style.width = 'calc(100% - 20px)';
            console.style.maxHeight = '200px';
            console.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            console.style.color = 'white';
            console.style.fontFamily = 'monospace';
            console.style.fontSize = '12px';
            console.style.padding = '10px';
            console.style.borderRadius = '5px';
            console.style.zIndex = '10000';
            console.style.overflowY = 'auto';
            
            const content = document.createElement('div');
            content.id = 'debug-console-content';
            console.appendChild(content);
            
            // Ajouter des boutons de contrôle
            const controls = document.createElement('div');
            controls.style.marginTop = '10px';
            controls.style.display = 'flex';
            controls.style.gap = '5px';
            
            const clearBtn = document.createElement('button');
            clearBtn.textContent = 'Effacer';
            clearBtn.style.padding = '5px';
            clearBtn.style.backgroundColor = '#333';
            clearBtn.style.color = 'white';
            clearBtn.style.border = 'none';
            clearBtn.style.borderRadius = '3px';
            clearBtn.addEventListener('click', function() {
                document.getElementById('debug-console-content').innerHTML = '';
            });
            
            const toggleBtn = document.createElement('button');
            toggleBtn.textContent = 'Masquer/Afficher';
            toggleBtn.style.padding = '5px';
            toggleBtn.style.backgroundColor = '#333';
            toggleBtn.style.color = 'white';
            toggleBtn.style.border = 'none';
            toggleBtn.style.borderRadius = '3px';
            toggleBtn.addEventListener('click', function() {
                const content = document.getElementById('debug-console-content');
                content.style.display = content.style.display === 'none' ? 'block' : 'none';
            });
            
            const visualBtn = document.createElement('button');
            visualBtn.textContent = 'Couches visuelles';
            visualBtn.style.padding = '5px';
            visualBtn.style.backgroundColor = '#333';
            visualBtn.style.color = 'white';
            visualBtn.style.border = 'none';
            visualBtn.style.borderRadius = '3px';
            visualBtn.addEventListener('click', function() {
                const layers = document.querySelectorAll('.debug-visual-layer');
                layers.forEach(layer => {
                    layer.style.display = layer.style.display === 'none' ? 'block' : 'none';
                });
            });
            
            controls.appendChild(clearBtn);
            controls.appendChild(toggleBtn);
            controls.appendChild(visualBtn);
            console.appendChild(controls);
            
            document.body.appendChild(console);
        }
        
        // Fonction pour ajouter un message à la console de débogage
        function logDebug(message) {
            console.log(message); // log dans la console standard
            
            const consoleContent = document.getElementById('debug-console-content');
            if (consoleContent) {
                const logEntry = document.createElement('div');
                logEntry.style.marginBottom = '5px';
                logEntry.style.borderBottom = '1px solid #333';
                logEntry.style.paddingBottom = '5px';
                
                const timestamp = new Date().toLocaleTimeString('fr-FR', { hour12: false });
                logEntry.innerHTML = `<span style="color: #999;">[${timestamp}]</span> ${message}`;
                
                consoleContent.appendChild(logEntry);
                consoleContent.scrollTop = consoleContent.scrollHeight;
            }
        }
    });
})(); 