/**
 * PWA Test Mode Handler
 * Gère les paramètres d'URL pour simuler le mode PWA
 */

document.addEventListener('DOMContentLoaded', function() {
    // Analyser les paramètres d'URL
    const urlParams = new URLSearchParams(window.location.search);
    const testPwa = urlParams.get('test_pwa');
    const device = urlParams.get('device');
    const hasNotch = urlParams.get('notch');
    
    // Appliquer le mode test PWA si le paramètre est présent
    if (testPwa === 'true') {
        document.body.classList.add('test-pwa-mode');
        document.body.classList.add('pwa-mode'); // Ajouter la classe standard PWA
        
        console.log('Mode test PWA activé');
        
        // Simuler un appareil iOS
        if (device === 'ios') {
            document.body.classList.add('test-ios-device');
            document.body.classList.add('ios-pwa'); // Ajouter la classe standard iOS
            
            console.log('Simulation appareil iOS activée');
            
            // Simuler un iPhone avec notch/Dynamic Island
            if (hasNotch === 'true') {
                document.body.classList.add('test-ios-notch');
                document.body.classList.add('ios-dynamic-island'); // Ajouter la classe standard notch
                
                console.log('Simulation iPhone avec notch/Dynamic Island activée');
            }
        }
        
        // Pour les liens dans la page, préserver les paramètres de test
        preserveTestParams();
    }
});

/**
 * Préserve les paramètres de test dans tous les liens de la page
 */
function preserveTestParams() {
    // Attendre que tous les éléments soient chargés
    setTimeout(() => {
        const links = document.querySelectorAll('a[href]');
        const currentUrl = window.location.href;
        const urlParams = new URLSearchParams(window.location.search);
        
        // Créer la chaîne de requête à ajouter
        let testParams = '?test_pwa=true';
        if (urlParams.get('device')) {
            testParams += '&device=' + urlParams.get('device');
        }
        if (urlParams.get('notch')) {
            testParams += '&notch=' + urlParams.get('notch');
        }
        
        // Parcourir tous les liens et ajouter les paramètres
        links.forEach(link => {
            let href = link.getAttribute('href');
            
            // Ignorer les liens externes, les ancres et les liens JavaScript
            if (href && !href.startsWith('#') && !href.startsWith('javascript:') && 
                !href.startsWith('http://') && !href.startsWith('https://') && 
                !href.startsWith('tel:') && !href.startsWith('mailto:')) {
                
                // Enlever tout paramètre existant
                if (href.includes('?')) {
                    href = href.split('?')[0];
                }
                
                // Ajouter les nouveaux paramètres
                link.setAttribute('href', href + testParams);
            }
        });
        
        console.log('Paramètres de test PWA préservés dans tous les liens');
    }, 500);
} 