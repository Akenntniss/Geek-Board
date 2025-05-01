/**
 * Gesture Navigation
 * Ajoute des gestes de navigation avancés pour l'interface mobile
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Configuration
    const config = {
        swipeThreshold: 100, // Distance minimale pour un swipe en pixels
        swipeRestrictedZone: 100, // Zone en pixels depuis le bord où le swipe est restreint
        longPressTime: 500, // Temps en ms pour un appui long
        doubleTapTime: 300, // Temps en ms entre deux taps pour un double tap
        vibrationFeedback: true, // Activer le retour haptique
        gestureNavigationEnabled: true // Activer la navigation par gestes
    };
    
    // Variables pour la gestion des gestes
    let touchStartX = 0;
    let touchStartY = 0;
    let touchStartTime = 0;
    let lastTapTime = 0;
    let longPressTimer = null;
    let currentTouchedElement = null;
    
    // Détection de l'environnement
    const IS_IOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
    const IS_ANDROID = /Android/.test(navigator.userAgent);
    const IS_PWA = window.matchMedia('(display-mode: standalone)').matches || 
                   window.navigator.standalone || 
                   document.referrer.includes('android-app://');
    
    // Fonction pour le retour haptique
    function vibrate(duration = 20) {
        if (config.vibrationFeedback && navigator.vibrate) {
            navigator.vibrate(duration);
        }
    }
    
    // Historique de navigation pour la gestion du retour
    const navigationHistory = {
        entries: [],
        
        addEntry(url) {
            // Ne pas ajouter d'entrées dupliquées consécutives
            if (this.entries.length > 0 && this.entries[this.entries.length - 1] === url) {
                return;
            }
            this.entries.push(url);
            
            // Limiter la taille de l'historique
            if (this.entries.length > 20) {
                this.entries.shift();
            }
        },
        
        getPreviousEntry() {
            if (this.entries.length > 1) {
                this.entries.pop(); // Supprimer l'entrée actuelle
                return this.entries[this.entries.length - 1];
            }
            return null;
        }
    };
    
    // Ajouter l'URL actuelle à l'historique au chargement
    navigationHistory.addEntry(window.location.href);
    
    // Gestionnaire d'événements touchstart
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        touchStartTime = new Date().getTime();
        currentTouchedElement = e.target;
        
        // Démarrer la détection d'appui long
        longPressTimer = setTimeout(function() {
            handleLongPress(e.target, touchStartX, touchStartY);
        }, config.longPressTime);
        
        // Gestion du double-tap
        const currentTime = new Date().getTime();
        const tapLength = currentTime - lastTapTime;
        
        if (tapLength < config.doubleTapTime && tapLength > 0) {
            // Double-tap détecté
            handleDoubleTap(e.target, touchStartX, touchStartY);
            clearTimeout(longPressTimer);
        }
        
        lastTapTime = currentTime;
    }, { passive: true });
    
    // Gestionnaire d'événements touchmove
    document.addEventListener('touchmove', function(e) {
        if (longPressTimer) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
        }
        
        // Prévenir la détection de gestes si le défilement est intentionnel
        const touchMoveY = e.touches[0].clientY;
        const touchMoveX = e.touches[0].clientX;
        const deltaY = touchMoveY - touchStartY;
        const deltaX = touchMoveX - touchStartX;
        
        // Si le mouvement vertical est important, c'est probablement un défilement
        if (Math.abs(deltaY) > Math.abs(deltaX) * 1.5) {
            return;
        }
        
        // Gestion des gestes horizontaux
        if (config.gestureNavigationEnabled) {
            // Swipe depuis le bord gauche pour revenir en arrière (comme dans iOS)
            if (touchStartX < config.swipeRestrictedZone && deltaX > config.swipeThreshold) {
                // Swipe de gauche à droite depuis le bord gauche
                handleBackSwipe();
            }
            
            // Swipe depuis le bord droit pour avancer (personnalisé)
            if (touchStartX > window.innerWidth - config.swipeRestrictedZone && deltaX < -config.swipeThreshold) {
                // Swipe de droite à gauche depuis le bord droit
                handleForwardSwipe();
            }
        }
    }, { passive: true });
    
    // Gestionnaire d'événements touchend
    document.addEventListener('touchend', function() {
        if (longPressTimer) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
        }
        
        currentTouchedElement = null;
    }, { passive: true });
    
    // Interception des clics sur les liens pour l'historique
    document.addEventListener('click', function(e) {
        // Trouver le lien le plus proche
        const link = e.target.closest('a');
        
        if (link && link.href && !link.target && !link.hasAttribute('download')) {
            // Ignorer les liens externes ou les actions JavaScript
            if (link.href.startsWith(window.location.origin) && !link.href.includes('javascript:')) {
                // Ajouter l'URL à l'historique
                navigationHistory.addEntry(link.href);
            }
        }
    });
    
    // Gestion du bouton retour du navigateur
    window.addEventListener('popstate', function() {
        // Mettre à jour l'historique de navigation
        if (document.referrer && document.referrer.startsWith(window.location.origin)) {
            navigationHistory.addEntry(document.referrer);
        }
    });
    
    // Gestionnaires pour les différents gestes
    
    // Appui long
    function handleLongPress(element, x, y) {
        vibrate(50);
        
        // Rechercher l'élément contextuel le plus proche
        const contextItem = element.closest('[data-context-menu]');
        
        if (contextItem) {
            // Récupérer l'ID du menu contextuel
            const menuId = contextItem.dataset.contextMenu;
            showContextMenu(menuId, x, y);
            
            // Créer un événement personnalisé
            const longPressEvent = new CustomEvent('app:longpress', {
                bubbles: true,
                detail: { element: contextItem, x, y }
            });
            
            contextItem.dispatchEvent(longPressEvent);
        } else {
            // Gestion par défaut de l'appui long
            handleDefaultLongPress(element, x, y);
        }
    }
    
    // Double-tap
    function handleDoubleTap(element, x, y) {
        vibrate(20);
        
        // Rechercher l'élément avec action de double-tap
        const doubleTapItem = element.closest('[data-double-tap-action]');
        
        if (doubleTapItem) {
            const action = doubleTapItem.dataset.doubleTapAction;
            executeDoubleTapAction(action, doubleTapItem);
        } else {
            // Action par défaut du double-tap : zoom sur l'image ou retour en haut
            if (element.tagName === 'IMG') {
                toggleImageZoom(element);
            } else if (window.scrollY > 100) {
                // Retourner en haut de la page
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
        
        // Créer un événement personnalisé
        const doubleTapEvent = new CustomEvent('app:doubletap', {
            bubbles: true,
            detail: { element, x, y }
        });
        
        element.dispatchEvent(doubleTapEvent);
    }
    
    // Swipe retour
    function handleBackSwipe() {
        vibrate(30);
        
        // Chercher un gestionnaire personnalisé
        const customHandler = document.querySelector('[data-back-handler]');
        
        if (customHandler) {
            // Déclencher un événement personnalisé que le gestionnaire peut écouter
            const backEvent = new CustomEvent('app:backgesture', {
                bubbles: true,
                cancelable: true
            });
            
            const eventProcessed = customHandler.dispatchEvent(backEvent);
            
            // Si l'événement a été annulé, ne pas continuer
            if (!eventProcessed) {
                return;
            }
        }
        
        // Comportement par défaut : retour à l'URL précédente
        const previousUrl = navigationHistory.getPreviousEntry();
        
        if (previousUrl) {
            window.location.href = previousUrl;
        } else {
            // Pas d'historique, revenir à l'accueil
            window.location.href = 'index.php';
        }
    }
    
    // Swipe avant
    function handleForwardSwipe() {
        vibrate(30);
        
        // Chercher un gestionnaire personnalisé
        const customHandler = document.querySelector('[data-forward-handler]');
        
        if (customHandler) {
            // Déclencher un événement personnalisé
            const forwardEvent = new CustomEvent('app:forwardgesture', {
                bubbles: true,
                cancelable: true
            });
            
            const eventProcessed = customHandler.dispatchEvent(forwardEvent);
            
            // Si l'événement a été annulé, ne pas continuer
            if (!eventProcessed) {
                return;
            }
        }
        
        // Comportement par défaut : si c'est une liste, aller à la prochaine page
        const pageNumberInput = document.querySelector('input[name="page"]');
        if (pageNumberInput) {
            const currentPage = parseInt(pageNumberInput.value) || 1;
            const maxPage = parseInt(document.querySelector('[data-max-page]')?.dataset.maxPage) || 1;
            
            if (currentPage < maxPage) {
                // Aller à la page suivante
                pageNumberInput.value = currentPage + 1;
                pageNumberInput.form.submit();
            }
        }
    }
    
    // Fonctions utilitaires
    
    // Afficher un menu contextuel
    function showContextMenu(menuId, x, y) {
        const menu = document.getElementById(menuId);
        
        if (!menu) return;
        
        // Créer un overlay pour fermer le menu au clic en dehors
        const overlay = document.createElement('div');
        overlay.classList.add('context-menu-overlay');
        document.body.appendChild(overlay);
        
        // Positionner le menu
        menu.classList.add('active');
        overlay.classList.add('active');
        
        // Gestionnaire pour fermer le menu
        function closeMenu() {
            menu.classList.remove('active');
            overlay.classList.remove('active');
            
            // Supprimer l'overlay après la transition
            setTimeout(() => {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 300);
            
            document.removeEventListener('click', closeMenu);
        }
        
        // Ajouter le gestionnaire après un court délai pour éviter la fermeture immédiate
        setTimeout(() => {
            document.addEventListener('click', closeMenu);
        }, 10);
        
        // Empêcher que le clic sur le menu ne ferme le menu
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Gérer l'appui long par défaut
    function handleDefaultLongPress(element, x, y) {
        // Vérifier si l'élément est sélectionnable
        const isText = element.nodeType === Node.TEXT_NODE ||
                      ['INPUT', 'TEXTAREA'].includes(element.tagName) ||
                      window.getComputedStyle(element).userSelect !== 'none';
        
        if (isText) {
            // Ne rien faire pour laisser la sélection de texte native
            return;
        }
        
        // Gérer les cas spécifiques
        if (element.tagName === 'IMG') {
            // Ajouter un menu contextuel pour les images
            showImageOptions(element, x, y);
        } else if (element.tagName === 'A' || element.closest('a')) {
            // Menu contextuel pour les liens
            showLinkOptions(element.closest('a') || element, x, y);
        }
    }
    
    // Exécuter l'action de double-tap
    function executeDoubleTapAction(action, element) {
        switch (action) {
            case 'zoom':
                toggleImageZoom(element.querySelector('img') || element);
                break;
            case 'edit':
                if (element.dataset.editUrl) {
                    window.location.href = element.dataset.editUrl;
                }
                break;
            case 'toggle':
                element.classList.toggle('expanded');
                break;
            default:
                // Action personnalisée
                const actionEvent = new CustomEvent('app:action', {
                    bubbles: true,
                    detail: { action, element }
                });
                element.dispatchEvent(actionEvent);
        }
    }
    
    // Zoom sur une image
    function toggleImageZoom(imgElement) {
        if (!imgElement) return;
        
        // Vérifier si l'image est déjà zoomée
        const isZoomed = imgElement.classList.contains('zoomed');
        
        if (isZoomed) {
            // Désactiver le zoom
            imgElement.classList.remove('zoomed');
            document.body.classList.remove('image-zoomed');
            
            // Supprimer l'overlay s'il existe
            const overlay = document.querySelector('.image-zoom-overlay');
            if (overlay) {
                overlay.parentNode.removeChild(overlay);
            }
        } else {
            // Activer le zoom
            imgElement.classList.add('zoomed');
            document.body.classList.add('image-zoomed');
            
            // Créer un overlay pour le zoom
            const overlay = document.createElement('div');
            overlay.classList.add('image-zoom-overlay');
            document.body.appendChild(overlay);
            
            // Fermer le zoom au clic
            overlay.addEventListener('click', function() {
                toggleImageZoom(imgElement);
            });
        }
    }
    
    // Montrer les options pour une image
    function showImageOptions(imgElement, x, y) {
        // Créer un menu contextuel dynamique
        const menuId = 'image-context-menu';
        let menu = document.getElementById(menuId);
        
        if (!menu) {
            menu = document.createElement('div');
            menu.id = menuId;
            menu.classList.add('context-menu');
            
            const menuHeader = document.createElement('div');
            menuHeader.classList.add('context-menu-header');
            menuHeader.innerHTML = '<h3 class="context-menu-title">Options de l\'image</h3>';
            
            const menuBody = document.createElement('div');
            menuBody.classList.add('context-menu-body');
            
            const menuItems = `
                <div class="context-menu-item" data-action="view">
                    <i class="fas fa-eye"></i>
                    <span>Voir l'image</span>
                </div>
                <div class="context-menu-item" data-action="save">
                    <i class="fas fa-download"></i>
                    <span>Enregistrer l'image</span>
                </div>
                <div class="context-menu-item" data-action="share">
                    <i class="fas fa-share-alt"></i>
                    <span>Partager l'image</span>
                </div>
            `;
            
            menuBody.innerHTML = menuItems;
            
            menu.appendChild(menuHeader);
            menu.appendChild(menuBody);
            document.body.appendChild(menu);
            
            // Ajouter les gestionnaires d'événements pour les actions
            menu.querySelectorAll('.context-menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    const action = this.dataset.action;
                    handleImageAction(action, imgElement);
                    
                    // Fermer le menu
                    menu.classList.remove('active');
                    document.querySelector('.context-menu-overlay')?.classList.remove('active');
                });
            });
        }
        
        // Afficher le menu
        showContextMenu(menuId, x, y);
    }
    
    // Montrer les options pour un lien
    function showLinkOptions(linkElement, x, y) {
        // Créer un menu contextuel dynamique
        const menuId = 'link-context-menu';
        let menu = document.getElementById(menuId);
        
        if (!menu) {
            menu = document.createElement('div');
            menu.id = menuId;
            menu.classList.add('context-menu');
            
            const menuHeader = document.createElement('div');
            menuHeader.classList.add('context-menu-header');
            menuHeader.innerHTML = '<h3 class="context-menu-title">Options du lien</h3>';
            
            const menuBody = document.createElement('div');
            menuBody.classList.add('context-menu-body');
            
            const menuItems = `
                <div class="context-menu-item" data-action="open">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Ouvrir le lien</span>
                </div>
                <div class="context-menu-item" data-action="share">
                    <i class="fas fa-share-alt"></i>
                    <span>Partager le lien</span>
                </div>
                <div class="context-menu-item" data-action="copy">
                    <i class="fas fa-copy"></i>
                    <span>Copier le lien</span>
                </div>
            `;
            
            menuBody.innerHTML = menuItems;
            
            menu.appendChild(menuHeader);
            menu.appendChild(menuBody);
            document.body.appendChild(menu);
            
            // Ajouter les gestionnaires d'événements pour les actions
            menu.querySelectorAll('.context-menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    const action = this.dataset.action;
                    handleLinkAction(action, linkElement);
                    
                    // Fermer le menu
                    menu.classList.remove('active');
                    document.querySelector('.context-menu-overlay')?.classList.remove('active');
                });
            });
        }
        
        // Afficher le menu
        showContextMenu(menuId, x, y);
    }
    
    // Gérer les actions sur les images
    function handleImageAction(action, imgElement) {
        const imgSrc = imgElement.src;
        
        switch (action) {
            case 'view':
                // Ouvrir l'image dans un viewer
                toggleImageZoom(imgElement);
                break;
                
            case 'save':
                // Télécharger l'image (via un lien temporaire)
                const link = document.createElement('a');
                link.href = imgSrc;
                link.download = imgSrc.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                break;
                
            case 'share':
                // Partager l'image si l'API Web Share est disponible
                if (navigator.share) {
                    navigator.share({
                        title: 'Image partagée',
                        text: 'Voici une image que je souhaite partager',
                        url: imgSrc
                    }).catch(err => {
                        console.error('Erreur lors du partage:', err);
                    });
                } else {
                    // Copier l'URL de l'image
                    copyToClipboard(imgSrc);
                    showToast('URL de l\'image copiée dans le presse-papier');
                }
                break;
        }
    }
    
    // Gérer les actions sur les liens
    function handleLinkAction(action, linkElement) {
        const url = linkElement.href;
        
        switch (action) {
            case 'open':
                // Ouvrir le lien
                window.location.href = url;
                break;
                
            case 'share':
                // Partager le lien si l'API Web Share est disponible
                if (navigator.share) {
                    navigator.share({
                        title: linkElement.textContent.trim() || 'Lien partagé',
                        text: 'Voici un lien que je souhaite partager',
                        url: url
                    }).catch(err => {
                        console.error('Erreur lors du partage:', err);
                    });
                } else {
                    // Copier l'URL du lien
                    copyToClipboard(url);
                    showToast('URL copiée dans le presse-papier');
                }
                break;
                
            case 'copy':
                // Copier l'URL du lien
                copyToClipboard(url);
                showToast('URL copiée dans le presse-papier');
                break;
        }
    }
    
    // Utilitaire pour copier du texte dans le presse-papier
    function copyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
    
    // Afficher un toast
    function showToast(message, duration = 3000) {
        let toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animer l'apparition
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Disparition
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, duration);
    }
    
    // Styles pour les éléments créés dynamiquement
    const style = document.createElement('style');
    style.textContent = `
        .toast-notification {
            position: fixed;
            bottom: 90px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background-color: rgba(50, 50, 50, 0.9);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            z-index: 2000;
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            text-align: center;
            max-width: 80%;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }
        
        .toast-notification.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        
        .image-zoom-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        img.zoomed {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            z-index: 2001;
            object-fit: contain;
        }
        
        @media (prefers-color-scheme: dark) {
            .toast-notification {
                background-color: rgba(230, 230, 230, 0.9);
                color: #222;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Initialisation supplémentaire en mode PWA
    if (IS_PWA) {
        // Intercepter le bouton retour natif
        window.addEventListener('popstate', function(e) {
            // Vérifier s'il y a un élément qui gère le retour
            const backHandler = document.querySelector('[data-back-handler]');
            
            if (backHandler) {
                // Déclencher un événement personnalisé
                const backEvent = new CustomEvent('app:back', {
                    bubbles: true,
                    cancelable: true
                });
                
                // Si l'événement est annulé, empêcher la navigation par défaut
                if (!backHandler.dispatchEvent(backEvent)) {
                    e.preventDefault();
                    history.pushState(null, document.title, window.location.href);
                }
            }
        });
        
        // Empêcher les gestes natifs sur iOS si la navigation par gestes est activée
        if (IS_IOS && config.gestureNavigationEnabled) {
            document.body.style.overscrollBehaviorX = 'none';
        }
    }
}); 