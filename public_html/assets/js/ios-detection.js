/**
 * Détection des appareils iOS et Safari
 * Ce script détecte si l'utilisateur est sur un appareil iOS (iPad/iPhone)
 * et applique les classes CSS nécessaires pour adapter la barre de menu
 */

document.addEventListener('DOMContentLoaded', function() {
    // Détecter si on est sur iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                 (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    
    // Détecter si on est sur Safari
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    
    // Détecter si on est en mode PWA
    const isPWA = window.matchMedia('(display-mode: standalone)').matches || 
                 window.matchMedia('(display-mode: fullscreen)').matches ||
                 window.navigator.standalone === true;
    
    // Appliquer les classes au body
    if (isIOS) {
        document.body.classList.add('ios-device');
    }
    
    if (isSafari) {
        document.body.classList.add('safari-browser');
    }
    
    if (isPWA && isIOS) {
        document.body.classList.add('ios-pwa');
    }
    
    // Détecter l'orientation
    checkOrientation();
    
    // Écouter les changements d'orientation
    window.addEventListener('orientationchange', checkOrientation);
    window.addEventListener('resize', checkOrientation);
});

/**
 * Vérifie et applique les classes selon l'orientation de l'appareil
 */
function checkOrientation() {
    // Détecter si on est en mode portrait ou paysage
    const isPortrait = window.matchMedia('(orientation: portrait)').matches;
    
    // Supprimer les classes existantes
    document.body.classList.remove('orientation-portrait', 'orientation-landscape');
    
    // Appliquer la classe correspondante
    if (isPortrait) {
        document.body.classList.add('orientation-portrait');
    } else {
        document.body.classList.add('orientation-landscape');
    }
} 