/**
 * Modern Card Animations
 * Animations et effets pour les cartes de réparation modernes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée séquentielle
    animateCardsSequentially();
    
    // Initialiser effets de carte 3D
    initializeCards3DEffects();
    
    // Initialiser le glisser-déposer amélioré
    initializeEnhancedDragDrop();
    
    // Diagnostic pour le glisser-déposer
    dragDropDiagnostic();
});

/**
 * Anime les cartes séquentiellement à leur entrée
 */
function animateCardsSequentially() {
    const cards = document.querySelectorAll('.modern-card');
    if (!cards.length) return;
    
    // Masquer toutes les cartes au départ
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
    });
    
    // Animer les cartes une par une
    let delay = 100;
    const staggerDelay = 50; // Délai entre chaque carte
    
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, delay + (index * staggerDelay));
    });
}

/**
 * Initialise les effets 3D sur les cartes
 */
function initializeCards3DEffects() {
    const cards = document.querySelectorAll('.modern-card');
    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    
    // N'appliquer l'effet 3D que sur les appareils non mobiles
    if (isMobile) return;
    
    cards.forEach(card => {
        // Effet de rotation 3D au survol
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Calculer les angles de rotation en fonction de la position de la souris
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateY = ((x - centerX) / centerX) * 5; // Max ±5deg
            const rotateX = ((centerY - y) / centerY) * 5; // Max ±5deg
            
            // Appliquer la transformation 3D
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
            
            // Effet de lumière/ombre dynamique
            updateLightEffect(this, rotateX, rotateY);
        });
        
        // Réinitialiser la rotation quand la souris quitte la carte
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
            this.style.transition = 'all 0.5s ease';
            
            // Réinitialiser l'effet de lumière
            this.style.backgroundImage = '';
            
            // Réinitialiser les éléments internes
            const elements = this.querySelectorAll('.card-header, .card-footer, .device-info, .client-avatar, .action-btn');
            elements.forEach(el => {
                el.style.transform = '';
                el.style.boxShadow = '';
            });
        });
        
        // Assurer une transition fluide quand la souris entre sur la carte
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.1s ease';
        });
    });
}

/**
 * Met à jour l'effet de lumière sur la carte en fonction de la position de la souris
 */
function updateLightEffect(card, rotateX, rotateY) {
    // Variables pour l'effet de lumière
    const lightX = (rotateY / 5) * 100 + 50; // 0-100%
    const lightY = (rotateX / 5) * 100 + 50; // 0-100%
    const intensity = 0.15; // Intensité de l'effet de lumière
    
    // Appliquer un dégradé radial pour simuler l'effet de lumière
    card.style.backgroundImage = `radial-gradient(circle at ${lightX}% ${lightY}%, rgba(255,255,255,${intensity}) 0%, rgba(255,255,255,0) 50%)`;
    
    // Effet de profondeur sur les éléments internes
    const cardHeader = card.querySelector('.card-header');
    const cardFooter = card.querySelector('.card-footer');
    const deviceInfo = card.querySelector('.device-info');
    const clientAvatar = card.querySelector('.client-avatar');
    const actionButtons = card.querySelectorAll('.action-btn');
    
    if (cardHeader) {
        cardHeader.style.transform = `translateZ(5px)`;
    }
    
    if (cardFooter) {
        cardFooter.style.transform = `translateZ(5px)`;
    }
    
    if (deviceInfo) {
        deviceInfo.style.transform = `translateZ(10px) translateX(${rotateY * -0.2}px) translateY(${rotateX * -0.2}px)`;
        deviceInfo.style.boxShadow = `${rotateY * -0.2}px ${rotateX * -0.2}px 5px rgba(0,0,0,0.05)`;
    }
    
    if (clientAvatar) {
        clientAvatar.style.transform = `translateZ(15px) translateX(${rotateY * -0.3}px) translateY(${rotateX * -0.3}px)`;
        clientAvatar.style.boxShadow = `${rotateY * -0.3}px ${rotateX * -0.3}px 10px rgba(0,0,0,0.1)`;
    }
    
    actionButtons.forEach(btn => {
        btn.style.transform = `translateZ(20px) translateX(${rotateY * -0.4}px) translateY(${rotateX * -0.4}px)`;
        btn.style.boxShadow = `${rotateY * -0.4}px ${rotateX * -0.4}px 10px rgba(0,0,0,0.1)`;
    });
}

/**
 * Améliore le comportement de glisser-déposer pour les cartes
 */
function initializeEnhancedDragDrop() {
    console.log("Initialisation du drag & drop amélioré");
    const cards = document.querySelectorAll('.modern-card, .draggable-card');
    const dropZones = document.querySelectorAll('.modern-filter.droppable');
    
    console.log("Cartes trouvées:", cards.length);
    console.log("Zones de dépôt trouvées:", dropZones.length);
    
    let draggedCard = null;
    let ghostCard = null;
    
    cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });
    
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('dragenter', handleDragEnter);
        zone.addEventListener('dragleave', handleDragLeave);
        zone.addEventListener('drop', handleDrop);
    });
    
    function handleDragStart(e) {
        console.log("Début du drag", this);
        draggedCard = this;
        
        // Récupérer les données de la carte
        const repairId = this.getAttribute('data-repair-id') || this.getAttribute('data-id');
        const status = this.getAttribute('data-status');
        
        // Vérifier que les données nécessaires sont disponibles
        if (!repairId) {
            console.error("ID de réparation manquant sur la carte:", this);
            alert("Erreur: ID de réparation manquant. Veuillez rafraîchir la page.");
            e.preventDefault();
            return false;
        }
        
        // Créer une carte fantôme
        ghostCard = this.cloneNode(true);
        ghostCard.classList.add('ghost-card');
        ghostCard.style.position = 'absolute';
        ghostCard.style.top = '-1000px'; // Placer hors écran initialement
        ghostCard.style.opacity = '0.8';
        document.body.appendChild(ghostCard);
        
        // Calculer l'offset par rapport au point de clic
        const rect = this.getBoundingClientRect();
        const offsetX = e.clientX - rect.left;
        const offsetY = e.clientY - rect.top;
        ghostCard.dataset.offsetX = offsetX;
        ghostCard.dataset.offsetY = offsetY;
        
        // Préparer les données à transférer
        const dragData = {
            repairId: repairId,
            status: status || 'undefined'
        };
        
        // Vérifier que les données peuvent être sérialisées
        try {
            const jsonData = JSON.stringify(dragData);
            console.log("Données de drag préparées:", jsonData);
            
            // Stocker les données nécessaires
            e.dataTransfer.setData('text/plain', jsonData);
            
            // Définir l'effet de déplacement
            e.dataTransfer.effectAllowed = 'move';
            
        } catch (serializeError) {
            console.error("Erreur lors de la sérialisation des données de drag:", serializeError);
            alert("Erreur: Impossible de préparer les données de glisser-déposer.");
            e.preventDefault();
            return false;
        }
        
        // Appliquer un délai pour l'effet visuel
        setTimeout(() => {
            this.classList.add('dragging');
        }, 0);
        
        // Ajouter l'écouteur pour le mouvement de la souris
        document.addEventListener('mousemove', updateGhostPosition);
    }
    
    function handleDragEnd() {
        console.log("Fin du drag");
        this.classList.remove('dragging');
        
        // Supprimer la carte fantôme
        if (ghostCard && ghostCard.parentNode) {
            document.body.removeChild(ghostCard);
            ghostCard = null;
        }
        
        // Supprimer l'écouteur de mouvement
        document.removeEventListener('mousemove', updateGhostPosition);
        
        // Réinitialiser les zones
        dropZones.forEach(zone => {
            zone.classList.remove('drag-over');
        });
    }
    
    function updateGhostPosition(e) {
        if (!ghostCard) return;
        
        const offsetX = parseInt(ghostCard.dataset.offsetX) || 0;
        const offsetY = parseInt(ghostCard.dataset.offsetY) || 0;
        
        ghostCard.style.left = `${e.pageX - offsetX}px`;
        ghostCard.style.top = `${e.pageY - offsetY}px`;
    }
    
    function handleDragOver(e) {
        e.preventDefault();
    }
    
    function handleDragEnter(e) {
        console.log("Entrée dans la zone de dépôt", this);
        this.classList.add('drag-over');
        
        // Effet de pulsation
        this.animate([
            { transform: 'scale(1)' },
            { transform: 'scale(1.05)' },
            { transform: 'scale(1)' }
        ], {
            duration: 400,
            iterations: 1
        });
        
        // Effet de brillance
        this.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.5)';
    }
    
    function handleDragLeave() {
        console.log("Sortie de la zone de dépôt");
        this.classList.remove('drag-over');
        this.style.boxShadow = '';
    }
    
    function handleDrop(e) {
        e.preventDefault();
        console.log("Drop détecté", this);
        
        // Récupérer les données
        try {
            const dataText = e.dataTransfer.getData('text/plain');
            console.log("Données reçues:", dataText);
            
            // Vérifier que les données ne sont pas vides
            if (!dataText || dataText.trim() === '') {
                console.error("Aucune donnée reçue lors du drop");
                alert("Erreur: Aucune donnée de glisser-déposer trouvée. Veuillez réessayer.");
                return;
            }
            
            let data;
            try {
                data = JSON.parse(dataText);
            } catch (jsonError) {
                console.error("Erreur lors du parsing JSON:", jsonError);
                console.error("Données brutes:", dataText);
                alert("Erreur: Données de glisser-déposer invalides. Veuillez rafraîchir la page et réessayer.");
                return;
            }
            
            const repairId = data.repairId;
            const categoryId = this.getAttribute('data-category-id');
            
            console.log("Drop avec: repairId=", repairId, "categoryId=", categoryId);
            
            // Vérifier que les données sont valides
            if (!repairId || !categoryId) {
                console.error("Données invalides pour le drop:", { repairId, categoryId });
                alert("Erreur: Données invalides pour le glisser-déposer.");
                return;
            }
            
            // Vérifier que repairId est un nombre valide
            if (isNaN(parseInt(repairId))) {
                console.error("ID de réparation invalide:", repairId);
                alert("Erreur: ID de réparation invalide.");
                return;
            }
            
            // Vérifier que categoryId est un nombre valide
            if (isNaN(parseInt(categoryId))) {
                console.error("ID de catégorie invalide:", categoryId);
                alert("Erreur: ID de catégorie invalide.");
                return;
            }
            
            // S'il y a une fonction existante pour gérer la mise à jour du statut
            if (typeof window.fetchStatusOptions === 'function') {
                console.log("Appel de fetchStatusOptions");
                
                const statusIndicator = draggedCard ? draggedCard.querySelector('.status-indicator') : null;
                if (statusIndicator) {
                    window.fetchStatusOptions(repairId, categoryId, statusIndicator);
                } else {
                    console.error("Indicateur de statut non trouvé dans la carte");
                    alert("Erreur: Impossible de trouver l'indicateur de statut de la carte.");
                    return;
                }
            } else {
                console.error("Fonction fetchStatusOptions non disponible dans le contexte global");
                alert("Erreur: La fonction fetchStatusOptions n'est pas disponible. Veuillez rafraîchir la page.");
                return;
            }
            
            // Effet visuel pour le succès
            this.classList.add('drop-success');
            setTimeout(() => {
                this.classList.remove('drop-success');
                this.style.boxShadow = '';
            }, 800);
            
            // Feedback d'animation sur la carte
            if (draggedCard) {
                // Animation de déplacement réussi
                draggedCard.classList.add('drop-complete');
                setTimeout(() => {
                    draggedCard.classList.remove('drop-complete');
                }, 800);
            }
            
        } catch (error) {
            console.error('Erreur lors du traitement des données de glisser-déposer:', error);
            alert("Erreur lors du glisser-déposer. Veuillez rafraîchir la page et réessayer.");
        }
        
        this.classList.remove('drag-over');
    }
}

/**
 * Fonction de diagnostic pour le glisser-déposer
 */
function dragDropDiagnostic() {
    // Vérifier si les éléments nécessaires sont présents
    const cards = document.querySelectorAll('.modern-card, .draggable-card');
    const dropZones = document.querySelectorAll('.modern-filter.droppable');
    
    console.log("Diagnostic de glisser-déposer:", {
        cards: cards.length,
        dropZones: dropZones.length,
        fetchStatusOptions: typeof window.fetchStatusOptions === 'function' ? 'Disponible' : 'Non disponible'
    });
    
    // Si l'utilisateur tente de glisser une carte sans que cela ne fonctionne,
    // afficher un message d'aide
    let dragAttempts = 0;
    let dropAttempts = 0;
    
    cards.forEach(card => {
        card.addEventListener('dragstart', function(e) {
            dragAttempts++;
            console.log("Tentative de glisser-déposer #" + dragAttempts);
            
            // Vérifier que les données sont bien définies
            if (typeof e.dataTransfer !== 'undefined') {
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    repairId: this.getAttribute('data-repair-id') || this.getAttribute('data-id'),
                    status: this.getAttribute('data-status')
                }));
            } else {
                console.error("dataTransfer non disponible !");
            }
            
            // Si c'est la 3ème tentative
            if (dragAttempts === 3) {
                setTimeout(() => {
                    if (typeof window.showNotification === 'function') {
                        window.showNotification(
                            "Pour utiliser le glisser-déposer, maintenez le clic sur une carte et déplacez-la vers un des filtres de statut en haut de la page.",
                            "info",
                            10000
                        );
                    }
                }, 2000);
            }
        });
    });
    
    // Vérifier si la fonction fetchStatusOptions est disponible
    if (typeof window.fetchStatusOptions !== 'function') {
        console.error("ERREUR: La fonction fetchStatusOptions n'est pas disponible");
        
        // Tenter de définir une fonction de secours
        window.fetchStatusOptions = function(repairId, categoryId) {
            console.warn("Fonction fetchStatusOptions de secours appelée");
            alert("Mise à jour de statut temporairement indisponible. Veuillez rafraîchir la page pour réessayer.");
        };
        
        // Afficher notification après un délai
        setTimeout(() => {
            if (typeof window.showNotification === 'function') {
                window.showNotification(
                    "Le glisser-déposer peut rencontrer des problèmes. Essayez de rafraîchir la page.",
                    "warning",
                    8000
                );
            }
        }, 3000);
    }
    
    // Collecter des informations sur les drop zones pour le débogage
    dropZones.forEach((zone, index) => {
        console.log(`Zone de dépôt #${index + 1}:`, {
            category: zone.getAttribute('data-category-id'),
            hasListener: zone._dragEnterAttached ? "Oui" : "Non"
        });
        
        // Ajouter un événement pour détecter si les zones reçoivent des événements drop
        zone.addEventListener('drop', function() {
            dropAttempts++;
            console.log(`Drop détecté sur la zone #${index + 1}`);
        });
    });
}

// Exposer les fonctions globalement
window.ModernCardAnimations = {
    animateCardsSequentially,
    initializeCards3DEffects,
    initializeEnhancedDragDrop
}; 