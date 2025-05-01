/**
 * Effets JavaScript pour les modals futuristes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les effets futuristes
    initFuturisticEffects();
    
    // Observer les futurs modals qui pourraient être créés dynamiquement
    observeModalCreation();
    
    // Détecter le mode clair/sombre
    detectLightMode();
});

/**
 * Initialise tous les effets futuristes pour les modals existants
 */
function initFuturisticEffects() {
    // Ajouter la classe futuristic-modal aux modals existants
    const taskDetailsModal = document.getElementById('taskDetailsModal');
    if (taskDetailsModal) {
        taskDetailsModal.classList.add('futuristic-modal');
        
        // Ajouter la classe pour l'animation séquentielle
        const taskDetailContainer = taskDetailsModal.querySelector('.task-detail-container');
        if (taskDetailContainer) {
            taskDetailContainer.classList.add('fade-in-sequence');
        }
        
        // Ajouter des particules
        createParticles(taskDetailsModal);
        
        // Ajouter effet de pulsation aux boutons d'action
        const actionButtons = taskDetailsModal.querySelectorAll('.task-actions button');
        actionButtons.forEach(btn => {
            btn.classList.add('pulse-effect');
        });
        
        // Effet holographique pendant le chargement
        const description = taskDetailsModal.querySelector('#task-description');
        if (description && description.textContent.trim() === 'Chargement...') {
            description.classList.add('holographic');
        }
    }
}

/**
 * Détecte si l'utilisateur est en mode clair ou sombre
 */
function detectLightMode() {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const body = document.body;
    
    if (!prefersDark) {
        body.classList.add('light-mode');
    } else {
        body.classList.remove('light-mode');
    }
    
    // Écouter les changements de mode
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
        if (event.matches) {
            body.classList.remove('light-mode');
        } else {
            body.classList.add('light-mode');
        }
    });
}

/**
 * Crée des particules flottantes dans le modal
 */
function createParticles(modal) {
    const modalBody = modal.querySelector('.modal-body');
    if (!modalBody) return;
    
    // Nombre de particules
    const particleCount = 8;
    
    // Créer les particules
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Position aléatoire
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        
        // Taille aléatoire
        const size = Math.random() * 4 + 2;
        
        // Vitesse aléatoire
        const duration = Math.random() * 30 + 10;
        const delay = Math.random() * 5;
        
        // Appliquer les styles
        particle.style.cssText = `
            left: ${x}%;
            top: ${y}%;
            width: ${size}px;
            height: ${size}px;
            opacity: ${Math.random() * 0.5 + 0.2};
            animation: float ${duration}s infinite ease-in-out ${delay}s;
        `;
        
        // Ajouter au modal
        modalBody.appendChild(particle);
    }
    
    // Ajouter l'animation CSS si elle n'existe pas déjà
    if (!document.getElementById('particle-animation')) {
        const style = document.createElement('style');
        style.id = 'particle-animation';
        style.textContent = `
            @keyframes float {
                0%, 100% {
                    transform: translateY(0) translateX(0);
                }
                25% {
                    transform: translateY(-20px) translateX(10px);
                }
                50% {
                    transform: translateY(-10px) translateX(-15px);
                }
                75% {
                    transform: translateY(-25px) translateX(5px);
                }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Observe la création dynamique de nouveaux modals
 */
function observeModalCreation() {
    // Configuration de l'observateur
    const config = { childList: true, subtree: true };
    
    // Callback à exécuter quand des mutations sont observées
    const callback = function(mutationsList, observer) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    // Vérifier si le nœud ajouté est un élément et a un ID
                    if (node.nodeType === 1 && node.id === 'taskDetailsModal') {
                        // Initialiser les effets pour ce nouveau modal
                        setTimeout(() => {
                            initFuturisticEffects();
                        }, 100);
                    }
                });
            }
        }
    };
    
    // Créer un observateur d'instance lié à la fonction de callback
    const observer = new MutationObserver(callback);
    
    // Commencer à observer le document avec les options configurées
    observer.observe(document.body, config);
}

/**
 * Fonction pour ajouter un effet de secousse lors d'une erreur
 */
function shakeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const modalDialog = modal.querySelector('.modal-dialog');
    if (!modalDialog) return;
    
    // Ajouter puis retirer la classe d'animation
    modalDialog.classList.add('shake');
    setTimeout(() => {
        modalDialog.classList.remove('shake');
    }, 500);
}

/**
 * Met à jour l'état du modal pour montrer un traitement en cours
 */
function startProcessingEffect(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Ajouter l'effet holographique
    const content = modal.querySelector('.modal-content');
    if (content) {
        content.classList.add('holographic');
    }
    
    // Ajouter un délai pour simuler un traitement
    return new Promise(resolve => {
        setTimeout(() => {
            if (content) {
                content.classList.remove('holographic');
            }
            resolve();
        }, 1500);
    });
}

/**
 * Affiche une animation de succès
 */
function showSuccessEffect(button) {
    // Stocker la couleur originale
    const originalBackground = button.style.background;
    const originalBoxShadow = button.style.boxShadow;
    
    // Changer à vert
    button.style.background = 'linear-gradient(135deg, #20c997, #0ca678)';
    button.style.boxShadow = '0 4px 15px rgba(32, 201, 151, 0.5)';
    
    // Ajouter l'effet de pulsation
    button.classList.add('success-pulse');
    
    // Revenir à l'état original après un délai
    setTimeout(() => {
        button.style.background = originalBackground;
        button.style.boxShadow = originalBoxShadow;
        button.classList.remove('success-pulse');
    }, 1500);
    
    // Ajouter l'animation CSS si elle n'existe pas déjà
    if (!document.getElementById('success-pulse-animation')) {
        const style = document.createElement('style');
        style.id = 'success-pulse-animation';
        style.textContent = `
            .success-pulse {
                animation: success-pulse 1.5s !important;
            }
            @keyframes success-pulse {
                0% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.1);
                }
                100% {
                    transform: scale(1);
                }
            }
        `;
        document.head.appendChild(style);
    }
} 