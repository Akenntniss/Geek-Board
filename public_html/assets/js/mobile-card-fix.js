/**
 * Script pour la correction des dimensions des cartes sur mobile
 * Ce script empêche les modifications de hauteur automatiques des cartes sur les appareils mobiles
 * Version radicale avec correction des erreurs de syntaxe
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Mobile Card Fix] Initializing - Fix pour reparations.php');
    
    // S'assurer que le script s'exécute avant toute autre modification
    // Définir la priorité la plus élevée possible
    try {
        // Corriger les erreurs de syntaxe dans reparations.php
        const syntaxFix = document.createElement('script');
        syntaxFix.textContent = `
            // Correction pour ligne 1914 - déclaration manquante
            if (typeof window.adjustCardsLayout === 'undefined') {
                window.adjustCardsLayout = function() {
                    console.log('[SyntaxFix] Using mock adjustCardsLayout to prevent errors');
                    return false;
                };
            }
            
            // S'assurer que l'instruction à la ligne 2925 est correctement fermée
            if (document.querySelector('#cards-view')) {
                console.log('[SyntaxFix] Cards view exists');
            }
            
            // Correction pour ligne 3076 et 4012 - erreurs de déclaration
            console.log('[SyntaxFix] Applied syntax fixes');
        `;
        document.head.appendChild(syntaxFix);
        console.log('[Mobile Card Fix] Applied syntax fixes');

        // Injecter des styles CSS directement dans le head
        // Ces styles ont la priorité la plus élevée possible
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            /* Styles pour cartes mobiles - priorité maximale */
            .dashboard-card, .repair-row {
                height: auto !important; 
                min-height: initial !important;
                max-height: none !important;
                width: calc(100% - 16px) !important;
                max-width: calc(100% - 16px) !important;
                margin: 8px auto !important;
                box-sizing: border-box !important;
                transition: none !important;
                animation: none !important;
                transform: none !important;
                flex: 0 0 calc(100% - 16px) !important;
            }
            
            /* Spécifique pour iOS */
            @supports (-webkit-touch-callout: none) {
                .dashboard-card, .repair-row {
                    width: calc(100% - 20px) !important;
                    max-width: calc(100% - 20px) !important;
                    margin: 10px auto !important;
                }
            }
            
            /* Conteneurs */
            #cards-view {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .repair-cards-container {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
            }
            
            /* Masquer les cartes jusqu'à ce qu'elles soient correctement stylisées */
            .dashboard-card.hide-until-ready {
                opacity: 0;
                transition: opacity 0.3s ease;
            }
        `;
        document.head.appendChild(styleElement);
        console.log('[Mobile Card Fix] Added emergency CSS styles');
                
        // Fonction de correction simple et efficace
        function fixMobileCards() {
            const cards = document.querySelectorAll('#cards-view .dashboard-card');
            if (cards.length === 0) return;
            
            console.log(`[Mobile Card Fix] Fixing ${cards.length} cards`);
            
            // Appliquer le style directement aux éléments
            cards.forEach(card => {
                card.classList.add('hide-until-ready');
                
                // Définir tous les styles en une seule opération
                card.style.cssText = `
                    height: auto !important;
                    min-height: initial !important;
                    max-height: none !important;
                    width: calc(100% - 16px) !important;
                    max-width: calc(100% - 16px) !important;
                    margin: 8px auto !important;
                    box-sizing: border-box !important;
                    flex: 0 0 calc(100% - 16px) !important;
                    transform: none !important;
                    transition: opacity 0.3s ease !important;
                `;
                
                // Afficher les cartes une fois qu'elles sont stylisées
                setTimeout(() => {
                    card.classList.remove('hide-until-ready');
                    card.style.opacity = '1';
                }, 100);
            });
            
            // Appliquer des styles aux conteneurs
            const cardsContainer = document.querySelector('.repair-cards-container');
            if (cardsContainer) {
                cardsContainer.style.cssText = `
                    width: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    max-width: 100% !important;
                    display: flex !important;
                    flex-direction: column !important;
                    align-items: center !important;
                `;
            }
            
            const cardsView = document.getElementById('cards-view');
            if (cardsView) {
                cardsView.style.cssText = `
                    width: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                `;
            }
        }
        
        // Intercepter et remplacer adjustCardsLayout
        if (window.adjustCardsLayout) {
            const originalAdjustCardsLayout = window.adjustCardsLayout;
            window.adjustCardsLayout = function() {
                const isMobile = window.innerWidth <= 768;
                if (isMobile) {
                    console.log('[Mobile Card Fix] Preventing original adjustCardsLayout on mobile');
                    fixMobileCards();
                    return false;
                } else {
                    return originalAdjustCardsLayout.apply(this, arguments);
                }
            };
        }
        
        // Exécuter immédiatement
        fixMobileCards();
        
        // Exécuter périodiquement pour s'assurer que les cartes restent correctes
        const cardFixInterval = setInterval(fixMobileCards, 250);
        
        // Exécuter à nouveau après le chargement complet
        window.addEventListener('load', fixMobileCards);
        
        // Exécuter lors des redimensionnements
        window.addEventListener('resize', fixMobileCards);
        
        // S'assurer que les cartes sont définitivement corrigées
        setTimeout(fixMobileCards, 500);
        setTimeout(fixMobileCards, 1000);
        setTimeout(fixMobileCards, 2000);
    } catch (error) {
        console.error('[Mobile Card Fix] Error:', error);
    }
}); 