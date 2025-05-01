/**
 * Script JavaScript pour interactions modernes
 * Ajoute des interactions utilisateur avancées et des animations fluides
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Détecter si l'appareil est tactile
    detectTouchDevice();

    // Initialiser les effets de vague pour les boutons
    initRippleEffect();

    // Initialiser les animations au défilement
    initScrollAnimations();

    // Initialiser les animations séquentielles
    initStaggeredAnimations();

    // Initialiser les effets de parallaxe
    initParallaxEffects();

    // Initialiser les effets 3D
    init3DEffects();

    // Initialiser les formulaires améliorés
    initEnhancedForms();

    // Initialiser le mode sombre
    initDarkMode();

    // Initialiser les effets de survol
    initHoverEffects();

    // Initialiser les effets de carte
    initCardEffects();

    // Initialiser les effets de texte
    initTextEffects();

    // Initialiser les optimisations pour appareils tactiles
    initTouchDeviceOptimizations();

    // Initialiser les optimisations pour interface professionnelle sur desktop
    initProfessionalDesktopOptimizations();
    
    // Initialiser la fonctionnalité de glisser vers le bas pour rafraîchir
    initPullToRefresh();

    // Écouter les changements de taille d'écran
    window.addEventListener('resize', debounce(function() {
        // Aucune référence aux boutons flottants ici
    }, 250));

    console.log('Initialisation des interactions modernes...');
    
    // Éléments du DOM
    const topNav = document.querySelector('.top-nav');
    const navTriggerZone = document.querySelector('.nav-trigger-zone');
    
    // Si les éléments nécessaires n'existent pas, ne pas continuer
    if (!topNav) {
        console.warn('Éléments de navigation non trouvés');
        return;
    }
    
    // Variables pour le défilement
    let lastScrollTop = 0;
    const scrollThreshold = 20;
    let isScrolling = false;
    let scrollDebounceTimer;
    let navVisibleTimer;
    let preventHideNav = false;
    
    // Variables pour les événements tactiles
    let touchStartY = 0;
    let touchEndY = 0;
    
    /**
     * Fonction principale pour gérer le comportement de défilement
     */
    function handleScroll() {
        if (!isScrolling) {
            window.requestAnimationFrame(function() {
                const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                
                // Ne pas masquer la barre si on vient de l'afficher (évite le clignotement)
                if (preventHideNav) {
                    lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
                    isScrolling = false;
                    return;
                }
                
                // Logique pour afficher/masquer la barre de navigation
                
                // Toujours afficher en haut de la page
                if (currentScroll <= 10) {
                    showNavbar(false);
                } 
                // Masquer en défilant vers le bas
                else if (currentScroll > lastScrollTop && currentScroll > scrollThreshold) {
                    hideNavbar();
                }
                // Afficher en défilant vers le haut
                else if (currentScroll < lastScrollTop - 5) {
                    showNavbar(true);
                }
                
                // Ajuster l'apparence selon la position de défilement
                if (currentScroll > 10) {
                    topNav.classList.add('glass', 'scrolled');
                } else {
                    topNav.classList.remove('glass', 'scrolled');
                }
                
                lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
                isScrolling = false;
            });
        }
        isScrolling = true;
    }
    
    /**
     * Affiche la barre de navigation
     * @param {boolean} withAnimation - Si true, ajoute l'animation d'élévation
     */
    function showNavbar(withAnimation = false) {
        if (topNav.classList.contains('hidden')) {
            topNav.classList.remove('hidden');
            
            if (withAnimation) {
                topNav.classList.add('elevation');
                
                // Supprimer la classe d'élévation après l'animation
                setTimeout(() => {
                    topNav.classList.remove('elevation');
                }, 500);
            }
            
            // Empêcher de masquer la barre pendant un court instant
            preventHideNav = true;
            clearTimeout(navVisibleTimer);
            navVisibleTimer = setTimeout(() => {
                preventHideNav = false;
            }, 800);
        }
    }
    
    /**
     * Masque la barre de navigation
     */
    function hideNavbar() {
        if (!topNav.classList.contains('elevation') && !preventHideNav) {
            topNav.classList.add('hidden');
        }
    }
    
    // Gestionnaires d'événements
    
    // Défilement avec debounce pour les performances
    window.addEventListener('scroll', function() {
        handleScroll();
        
        clearTimeout(scrollDebounceTimer);
        scrollDebounceTimer = setTimeout(function() {
            // Si stationnaire en haut de page, s'assurer que la barre est visible
            if (window.pageYOffset < 50) {
                showNavbar(false);
            }
        }, 150);
    }, { passive: true });
    
    // Zone de déclenchement pour faire réapparaître la barre
    if (navTriggerZone) {
        navTriggerZone.addEventListener('mouseenter', function() {
            showNavbar(true);
        });
    }
    
    // Événements tactiles pour le mobile
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });
    
    document.addEventListener('touchmove', function(e) {
        const currentY = e.changedTouches[0].screenY;
        const deltaY = currentY - touchStartY;
        
        // Afficher la barre lors d'un balayage vers le bas près du haut de la page
        if (deltaY > 40 && window.pageYOffset < 120) {
            showNavbar(true);
        }
        // Masquer la barre lors d'un balayage vers le haut rapide
        else if (deltaY < -50 && window.pageYOffset > 50) {
            hideNavbar();
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndY = e.changedTouches[0].screenY;
        const deltaY = touchEndY - touchStartY;
        
        // Geste de balayage vers le bas pour faire apparaître la barre
        if (deltaY > 70) {
            showNavbar(true);
        }
        // Geste de balayage vers le haut pour masquer la barre
        else if (deltaY < -70 && window.pageYOffset > 50) {
            hideNavbar();
        }
    }, { passive: true });
    
    // Gestionnaire d'événement pour le clic sur le document
    document.addEventListener('click', function(e) {
        // Toujours montrer la barre lors d'un clic sur un élément interactif
        if (e.target.tagName === 'BUTTON' || 
            e.target.tagName === 'A' || 
            e.target.tagName === 'INPUT' ||
            e.target.closest('button') ||
            e.target.closest('a') ||
            e.target.closest('input')) {
            showNavbar(false);
        }
    });
    
    // Initialisation - S'assurer que la barre est visible au chargement
    showNavbar(false);
    
    // Gestion de la barre de navigation mobile (dock)
    const mobileDock = document.getElementById('mobile-dock');
    let lastScrollY = window.scrollY;
    let isMobileDockHidden = false;
    
    // Fonction pour afficher le dock mobile
    function showMobileDock() {
        if (mobileDock && isMobileDockHidden) {
            mobileDock.classList.remove('hidden');
            mobileDock.classList.add('show');
            isMobileDockHidden = false;
        }
    }
    
    // Fonction pour cacher le dock mobile
    function hideMobileDock() {
        if (mobileDock && !isMobileDockHidden) {
            mobileDock.classList.remove('show');
            mobileDock.classList.add('hidden');
            isMobileDockHidden = true;
        }
    }
    
    // Gestionnaire d'événement de défilement pour le dock mobile
    window.addEventListener('scroll', function() {
        if (!mobileDock) return;
        
        const currentScrollY = window.scrollY;
        const isScrollingDown = currentScrollY > lastScrollY;
        
        // Cacher le dock lors du défilement vers le bas, après avoir défilé un peu
        if (isScrollingDown && currentScrollY > 50) {
            hideMobileDock();
        } 
        // Montrer le dock lors du défilement vers le haut
        else if (!isScrollingDown) {
            showMobileDock();
        }
        
        lastScrollY = currentScrollY;
    }, { passive: true });
    
    // Montrer le dock mobile lors d'un tap sur l'écran
    document.addEventListener('touchend', function(e) {
        // Si on touche un élément interactif
        if (e.target.tagName === 'BUTTON' || 
            e.target.tagName === 'A' || 
            e.target.tagName === 'INPUT' ||
            e.target.closest('button') ||
            e.target.closest('a') ||
            e.target.closest('input')) {
            showMobileDock();
        }
    }, { passive: true });
    
    // S'assurer que le dock est visible au chargement initial
    if (mobileDock) {
        mobileDock.classList.add('show');
    }
    
    console.log('Interactions modernes initialisées avec succès');

    // Navigation mobile toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const topNavMenu = document.querySelector('.top-nav-menu');
    const launchpadMenu = document.querySelector('.launchpad-menu');
    const launchpadItems = document.querySelectorAll('.launchpad-item');
    const launchpadHeader = document.querySelector('.launchpad-header');
    
    // Fonction pour réinitialiser les animations du launchpad
    function resetLaunchpadAnimations() {
        // Retirer temporairement les classes liées aux animations
        if (launchpadHeader) {
            launchpadHeader.style.opacity = "0";
            launchpadHeader.style.animation = "none";
        }
        
        launchpadItems.forEach(item => {
            item.style.opacity = "0";
            item.style.animation = "none";
        });
        
        // Forcer un reflow pour que les changements soient appliqués
        if (launchpadMenu) {
            launchpadMenu.offsetHeight;
        }
        
        // Réappliquer les animations après un court délai
        setTimeout(() => {
            if (launchpadHeader) {
                launchpadHeader.style.animation = "";
                launchpadHeader.style.opacity = "";
            }
            
            launchpadItems.forEach(item => {
                item.style.animation = "";
                item.style.opacity = "";
            });
        }, 10);
    }
    
    // Suppression du gestionnaire d'événements pour le bouton hamburger qui entre en conflit
    // avec celui de header.php
    
    // Fermer le launchpad menu quand on clique sur un élément
    launchpadItems.forEach(item => {
        item.addEventListener('click', function() {
            if (launchpadMenu) {
                launchpadMenu.classList.remove('active');
            }
            if (mobileMenuToggle) {
                mobileMenuToggle.classList.remove('active');
            }
            document.body.style.overflow = '';
        });
    });
    
    // Fermer le launchpad menu si on redimensionne l'écran à une taille plus grande
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1023 && launchpadMenu && launchpadMenu.classList.contains('active')) {
            launchpadMenu.classList.remove('active');
            if (mobileMenuToggle) {
                mobileMenuToggle.classList.remove('active');
            }
            document.body.style.overflow = '';
        }
    });
    
    // Navbar scroll handling
    if (!window.topNav) {
        window.topNav = document.querySelector('.top-nav');
    }
    
    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Add scrolled class when page is scrolled
        if (scrollTop > 10) {
            window.topNav.classList.add('scrolled');
            window.topNav.classList.add('glass');
        } else {
            window.topNav.classList.remove('scrolled');
            window.topNav.classList.remove('glass');
        }
        
        // Hide navbar when scrolling down, show when scrolling up
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down
            window.topNav.classList.add('hidden');
        } else {
            // Scrolling up
            window.topNav.classList.remove('hidden');
            if (window.topNav.classList.contains('hidden')) {
                window.topNav.classList.add('elevation');
                setTimeout(() => {
                    window.topNav.classList.remove('elevation');
                }, 500);
            }
        }
        
        lastScrollTop = scrollTop;
    }, { passive: true });
});

/**
 * Initialise l'effet de vague pour les boutons
 */
function initRippleEffect() {
    // Sélectionner tous les boutons avec la classe btn-ripple
    const rippleButtons = document.querySelectorAll('.btn-ripple');
    
    rippleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Créer l'élément de vague
            const rippleCircle = document.createElement('span');
            rippleCircle.classList.add('ripple-circle');
            this.appendChild(rippleCircle);
            
            // Positionner la vague à l'endroit du clic
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            rippleCircle.style.left = x + 'px';
            rippleCircle.style.top = y + 'px';
            
            // Supprimer l'élément de vague après l'animation
            setTimeout(() => {
                rippleCircle.remove();
            }, 500);
        });
    });
    
    // Ajouter l'effet de vague à tous les boutons qui n'ont pas la classe btn-ripple
    const allButtons = document.querySelectorAll('.btn:not(.btn-ripple)');
    
    allButtons.forEach(button => {
        button.classList.add('btn-ripple');
    });
}

/**
 * Initialise les animations au défilement
 */
function initScrollAnimations() {
    // Sélectionner tous les éléments avec des classes d'animation
    // IMPORTANT: Exclure les éléments principaux comme main, .container-fluid, .row, etc.
    const animatedElements = document.querySelectorAll(
        '.card.fade-in-up, .card.fade-in-down, .card.fade-in-left, .card.fade-in-right, .card.zoom-in, .table-responsive.fade-in-up'
    );

    // Si IntersectionObserver est disponible
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Ajouter la classe visible pour déclencher l'animation
                    entry.target.classList.add('visible');
                    entry.target.style.opacity = '1';

                    // Arrêter d'observer l'élément une fois qu'il est visible
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1, // Déclencher lorsque 10% de l'élément est visible
            rootMargin: '0px 0px -50px 0px' // Déclencher un peu avant que l'élément soit visible
        });

        // Observer chaque élément, mais ne pas masquer les éléments principaux
        animatedElements.forEach(element => {
            // Ne pas masquer les éléments principaux ou les conteneurs
            if (!element.classList.contains('container-fluid') &&
                !element.classList.contains('row') &&
                !element.tagName.toLowerCase() === 'main') {
                // Masquer l'élément initialement mais avec une opacité faible plutôt que nulle
                element.style.opacity = '0.1';
            }

            // Observer l'élément
            observer.observe(element);
        });
    } else {
        // Fallback pour les navigateurs qui ne supportent pas IntersectionObserver
        animatedElements.forEach(element => {
            element.classList.add('visible');
            element.style.opacity = '1';
        });
    }
    
    // Ajouter un gestionnaire d'événements pour le défilement avec l'option passive
    document.addEventListener('scroll', debounce(function() {
        animatedElements.forEach(element => {
            if (isElementInViewport(element) && !element.classList.contains('visible')) {
                element.classList.add('visible');
                element.style.opacity = '1';
            }
        });
    }, 100), { passive: true });
}

/**
 * Initialise les animations séquentielles
 */
function initStaggeredAnimations() {
    // Sélectionner tous les conteneurs avec la classe staggered-animation
    // IMPORTANT: Exclure les conteneurs principaux
    const staggeredContainers = document.querySelectorAll('.card.staggered-animation, .table-responsive.staggered-animation');

    staggeredContainers.forEach(container => {
        // Sélectionner tous les enfants directs
        const children = Array.from(container.children);

        // Si IntersectionObserver est disponible
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    // Animer chaque enfant avec un délai
                    children.forEach((child, index) => {
                        setTimeout(() => {
                            child.classList.add('visible');
                            child.style.opacity = '1';
                        }, index * 100); // 100ms de délai entre chaque enfant
                    });

                    // Arrêter d'observer le conteneur une fois qu'il est visible
                    observer.unobserve(container);
                }
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            // Masquer uniquement les enfants des conteneurs spécifiques (pas les conteneurs principaux)
            if (!container.classList.contains('container-fluid') &&
                !container.classList.contains('row') &&
                !container.tagName.toLowerCase() === 'main') {

                children.forEach(child => {
                    // Ne pas masquer complètement, utiliser une opacité faible
                    child.style.opacity = '0.1';
                    child.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

                    // Ajouter une classe d'animation en fonction de la position
                    if (!child.classList.contains('fade-in-up') &&
                        !child.classList.contains('fade-in-down') &&
                        !child.classList.contains('fade-in-left') &&
                        !child.classList.contains('fade-in-right') &&
                        !child.classList.contains('zoom-in')) {
                        child.classList.add('fade-in-up');
                    }
                });

                // Observer le conteneur
                observer.observe(container);
            }
        } else {
            // Fallback pour les navigateurs qui ne supportent pas IntersectionObserver
            children.forEach(child => {
                child.classList.add('visible');
                child.style.opacity = '1';
            });
        }
    });
}

/**
 * Initialise les effets de parallaxe
 */
function initParallaxEffects() {
    // Sélectionner tous les éléments avec la classe parallax-effect
    const parallaxElements = document.querySelectorAll('.parallax-effect');
    
    // Ajouter un gestionnaire d'événements pour le défilement
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset;
        
        parallaxElements.forEach(element => {
            const speed = element.getAttribute('data-parallax-speed') || 0.5;
            const yPos = -(scrollTop * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    });
    
    // Sélectionner tous les éléments avec la classe mouse-parallax-effect
    const mouseParallaxElements = document.querySelectorAll('.mouse-parallax-effect');
    
    // Ajouter un gestionnaire d'événements pour le mouvement de la souris
    document.addEventListener('mousemove', function(e) {
        const mouseX = e.clientX;
        const mouseY = e.clientY;
        
        mouseParallaxElements.forEach(element => {
            const speed = element.getAttribute('data-mouse-speed') || 0.05;
            const rect = element.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            
            const moveX = (mouseX - centerX) * speed;
            const moveY = (mouseY - centerY) * speed;
            
            element.style.transform = `translate(${moveX}px, ${moveY}px)`;
        });
    });
}

/**
 * Initialise les effets 3D
 */
function init3DEffects() {
    // Sélectionner tous les éléments avec la classe card-3d
    const card3dElements = document.querySelectorAll('.card-3d');
    
    card3dElements.forEach(card => {
        // Ajouter un gestionnaire d'événements pour le mouvement de la souris
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            
            const mouseX = e.clientX - centerX;
            const mouseY = e.clientY - centerY;
            
            const rotateX = mouseY / -10;
            const rotateY = mouseX / 10;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
        });
        
        // Réinitialiser la transformation lorsque la souris quitte l'élément
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
        });
    });
}

/**
 * Initialise les formulaires améliorés
 */
function initEnhancedForms() {
    // Sélectionner tous les éléments avec la classe form-floating-label
    const floatingLabelContainers = document.querySelectorAll('.form-floating-label');
    
    floatingLabelContainers.forEach(container => {
        const input = container.querySelector('input, select, textarea');
        const label = container.querySelector('label');
        
        if (input && label) {
            // Vérifier si l'input a déjà une valeur
            if (input.value !== '' || 
                (input.tagName === 'SELECT' && input.value !== '' && input.value !== '0')) {
                label.classList.add('active');
            }
            
            // Ajouter un gestionnaire d'événements pour le focus
            input.addEventListener('focus', function() {
                label.classList.add('active');
            });
            
            // Ajouter un gestionnaire d'événements pour le blur
            input.addEventListener('blur', function() {
                if (this.value === '' || 
                    (this.tagName === 'SELECT' && (this.value === '' || this.value === '0'))) {
                    label.classList.remove('active');
                }
            });
        }
    });
    
    // Sélectionner tous les éléments avec la classe input-focus-effect
    const focusEffectContainers = document.querySelectorAll('.input-focus-effect');
    
    focusEffectContainers.forEach(container => {
        const input = container.querySelector('input, select, textarea');
        
        if (input) {
            // Ajouter un gestionnaire d'événements pour le focus
            input.addEventListener('focus', function() {
                container.classList.add('is-focused');
            });
            
            // Ajouter un gestionnaire d'événements pour le blur
            input.addEventListener('blur', function() {
                container.classList.remove('is-focused');
            });
        }
    });
    
    // Améliorer la validation des formulaires
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Ajouter un gestionnaire d'événements pour la soumission
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
        
        // Ajouter un gestionnaire d'événements pour la validation en temps réel
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
    });
}

/**
 * Initialise le mode sombre
 */
function initDarkMode() {
    // Vérifier si l'utilisateur préfère le mode sombre
    const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Vérifier si l'utilisateur a déjà choisi un mode
    const savedMode = localStorage.getItem('darkMode');
    
    // Appliquer le mode sombre si nécessaire
    if (savedMode === 'dark' || (savedMode === null && prefersDarkMode)) {
        document.body.classList.add('dark-mode');
    }
    
    // Ajouter un bouton de bascule du mode sombre s'il existe
    const darkModeToggle = document.querySelector('.dark-mode-toggle');
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            
            // Enregistrer la préférence de l'utilisateur
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'dark');
            } else {
                localStorage.setItem('darkMode', 'light');
            }
        });
    }
    
    // Écouter les changements de préférence du système
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        // Ne changer que si l'utilisateur n'a pas explicitement choisi un mode
        if (localStorage.getItem('darkMode') === null) {
            if (e.matches) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        }
    });
}

/**
 * Initialise les effets de survol
 */
function initHoverEffects() {
    // Sélectionner tous les éléments avec la classe hover-lift
    const hoverLiftElements = document.querySelectorAll('.hover-lift');
    
    hoverLiftElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.1), 0 5px 10px rgba(0, 0, 0, 0.05)';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
    
    // Sélectionner tous les éléments avec la classe hover-zoom
    const hoverZoomElements = document.querySelectorAll('.hover-zoom');
    
    hoverZoomElements.forEach(element => {
        const img = element.querySelector('img');
        
        if (img) {
            element.addEventListener('mouseenter', function() {
                img.style.transform = 'scale(1.05)';
            });
            
            element.addEventListener('mouseleave', function() {
                img.style.transform = '';
            });
        }
    });
    
    // Sélectionner tous les éléments avec la classe hover-underline
    const hoverUnderlineElements = document.querySelectorAll('.hover-underline');
    
    hoverUnderlineElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const underline = this.querySelector('::after');
            if (underline) {
                underline.style.transform = 'scaleX(1)';
                underline.style.transformOrigin = 'bottom left';
            }
        });
        
        element.addEventListener('mouseleave', function() {
            const underline = this.querySelector('::after');
            if (underline) {
                underline.style.transform = 'scaleX(0)';
                underline.style.transformOrigin = 'bottom right';
            }
        });
    });
}

/**
 * Initialise les effets de carte
 */
function initCardEffects() {
    // Sélectionner toutes les cartes avec la classe card-glass
    const glassCards = document.querySelectorAll('.card-glass');
    
    glassCards.forEach(card => {
        // Ajouter un effet de profondeur au survol
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 12px 32px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
    
    // Sélectionner toutes les cartes avec la classe card-gradient
    const gradientCards = document.querySelectorAll('.card-gradient');
    
    gradientCards.forEach(card => {
        // Ajouter un effet de changement de dégradé au survol
        card.addEventListener('mouseenter', function() {
            this.style.backgroundPosition = 'right center';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.backgroundPosition = 'left center';
        });
    });
    
    // Sélectionner toutes les cartes avec la classe card-neomorphic
    const neomorphicCards = document.querySelectorAll('.card-neomorphic');
    
    neomorphicCards.forEach(card => {
        // Ajouter un effet de pression au survol
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(0.98)';
            this.style.boxShadow = 
                '4px 4px 8px rgba(174, 174, 192, 0.4), ' +
                '-4px -4px 8px rgba(255, 255, 255, 0.8)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = 
                '8px 8px 16px rgba(174, 174, 192, 0.4), ' +
                '-8px -8px 16px rgba(255, 255, 255, 0.8)';
        });
    });
}

/**
 * Initialise les effets de texte
 */
function initTextEffects() {
    // Sélectionner tous les éléments avec la classe text-gradient-*
    const gradientTextElements = document.querySelectorAll(
        '.text-gradient-primary, .text-gradient-success, .text-gradient-warning, .text-gradient-danger, .text-gradient-info'
    );
    
    gradientTextElements.forEach(element => {
        // Ajouter un effet de changement de dégradé au survol
        element.addEventListener('mouseenter', function() {
            this.style.backgroundPosition = 'right center';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.backgroundPosition = 'left center';
        });
    });
}

/**
 * Vérifie si un élément est visible dans la fenêtre
 * @param {HTMLElement} element - L'élément à vérifier
 * @returns {boolean} - True si l'élément est visible
 */
function isElementInViewport(element) {
    const rect = element.getBoundingClientRect();
    
    return (
        rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.bottom >= 0 &&
        rect.left <= (window.innerWidth || document.documentElement.clientWidth) &&
        rect.right >= 0
    );
}

/**
 * Fonction utilitaire pour limiter la fréquence d'exécution d'une fonction
 * @param {Function} func - La fonction à exécuter
 * @param {number} wait - Le délai d'attente en millisecondes
 * @returns {Function} - La fonction avec limite de fréquence
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}

/**
 * Détecte si l'appareil est tactile et ajoute une classe au body
 */
function detectTouchDevice() {
    const isTouchDevice = 'ontouchstart' in window ||
                          navigator.maxTouchPoints > 0 ||
                          navigator.msMaxTouchPoints > 0;

    if (isTouchDevice) {
        document.body.classList.add('touch-device');

        // Ajouter des attributs pour améliorer l'accessibilité tactile
        const interactiveElements = document.querySelectorAll('a, button, .btn, .nav-link, .card');
        interactiveElements.forEach(element => {
            element.classList.add('touch-friendly');
        });
    } else {
        document.body.classList.add('no-touch');
    }
}

/**
 * Initialise les optimisations pour les appareils tactiles
 */
function initTouchDeviceOptimizations() {
    // Vérifier si l'appareil est tactile
    if (!document.body.classList.contains('touch-device')) return;

    // Augmenter la taille des zones cliquables
    const touchTargets = document.querySelectorAll('a, button, .btn, .nav-link, .form-control, .form-select');
    touchTargets.forEach(element => {
        // S'assurer que les éléments sont suffisamment grands pour être facilement touchés
        if (element.offsetHeight < 44) {
            element.style.minHeight = '44px';
        }

        // Ajouter un effet de feedback tactile
        element.addEventListener('touchstart', function() {
            this.style.opacity = '0.8';
        });

        element.addEventListener('touchend', function() {
            this.style.opacity = '1';
        });
    });

    // Optimiser les tableaux pour le tactile
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        table.classList.add('table-touch-optimized');

        // Augmenter l'espacement des cellules pour faciliter le toucher
        const cells = table.querySelectorAll('td, th');
        cells.forEach(cell => {
            cell.style.padding = '12px 16px';
        });
    });

    // Optimiser le défilement
    document.documentElement.style.overscrollBehavior = 'none';
}

/**
 * Initialise les optimisations pour interfaces professionnelles sur desktop
 */
function initProfessionalDesktopOptimizations() {
    // Vérifier si l'appareil n'est pas tactile et a un écran suffisamment grand
    if (document.body.classList.contains('touch-device') || window.innerWidth < 992) return;

    // Améliorer l'aspect des tableaux
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        // Ajouter des styles professionnels
        table.classList.add('table-professional');

        // Ajouter des effets de survol améliorés
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(67, 97, 238, 0.05)';
                this.style.transform = 'translateX(5px)';
            });

            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.transform = '';
            });
        });
    });

    // Améliorer l'aspect des cartes
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        // Ajouter des transitions fluides
        card.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';

        // Ajouter des effets de survol professionnels
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 12px 32px rgba(0, 0, 0, 0.1)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Améliorer l'aspect des boutons
    const buttons = document.querySelectorAll('.btn-primary');
    buttons.forEach(button => {
        // Ajouter un dégradé si ce n'est pas déjà fait
        if (!button.classList.contains('btn-gradient-primary')) {
            button.classList.add('btn-gradient-primary');
        }

        // Ajouter des effets de survol professionnels
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 10px rgba(67, 97, 238, 0.3)';
        });

        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Améliorer l'aspect de la barre latérale
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.05)';

        // Améliorer les liens de navigation
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateX(5px)';
                    this.style.backgroundColor = 'var(--gray-100)';
                }
            });

            link.addEventListener('mouseleave', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = '';
                    this.style.backgroundColor = '';
                }
            });
        });
    }
}

/**
 * Fonction utilitaire pour exécuter une fonction à une fréquence maximale
 * @param {Function} func - La fonction à exécuter
 * @param {number} wait - Le délai d'attente en millisecondes
 * @returns {Function} - La fonction avec fréquence limitée
 */
function throttle(func, wait) {
    let timeout = null;
    let previous = 0;
    
    return function() {
        const context = this;
        const args = arguments;
        const now = Date.now();
        
        if (!previous) {
            previous = now;
        }
        
        const remaining = wait - (now - previous);
        
        if (remaining <= 0 || remaining > wait) {
            if (timeout) {
                clearTimeout(timeout);
                timeout = null;
            }
            
            previous = now;
            func.apply(context, args);
        } else if (!timeout) {
            timeout = setTimeout(function() {
                previous = Date.now();
                timeout = null;
                func.apply(context, args);
            }, remaining);
        }
    };
}

/**
 * Initialise la fonctionnalité de glisser vers le bas pour rafraîchir (pull-to-refresh)
 * Cette fonction n'est active qu'en mode PWA
 */
function initPullToRefresh() {
    // Vérifier si l'application est en mode PWA
    const isPwaMode = () => 
        (window.matchMedia('(display-mode: standalone)').matches) || 
        (window.navigator.standalone) || 
        document.referrer.includes('android-app://');
    
    // Ne pas initialiser si ce n'est pas en mode PWA
    if (!isPwaMode()) {
        console.log('Mode PWA non détecté, pull-to-refresh désactivé');
        return;
    }

    console.log('Initialisation du pull-to-refresh pour le mode PWA');
    
    // Créer l'indicateur de rafraîchissement
    const refreshIndicator = document.createElement('div');
    refreshIndicator.className = 'pull-to-refresh-indicator';
    refreshIndicator.innerHTML = '<div class="spinner"></div><span>Relâchez pour rafraîchir</span>';
    document.body.appendChild(refreshIndicator);
    
    // Ajouter le style CSS pour l'indicateur
    const style = document.createElement('style');
    style.textContent = `
        .pull-to-refresh-indicator {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.95);
            height: 60px;
            transform: translateY(-70px);
            transition: transform 0.3s ease;
            z-index: 9999;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding-top: env(safe-area-inset-top);
        }
        .pull-to-refresh-indicator.visible {
            transform: translateY(0);
        }
        .pull-to-refresh-indicator.refreshing {
            transform: translateY(0);
        }
        .pull-to-refresh-indicator .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid #0078e8;
            border-top-color: transparent;
            border-radius: 50%;
            margin-right: 10px;
            animation: spinner-rotate 0.8s infinite linear;
        }
        .pull-to-refresh-indicator.refreshing .spinner {
            animation: spinner-rotate 0.8s infinite linear;
        }
        @keyframes spinner-rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Zone tactile pour le pull-to-refresh */
        .ptr-zone {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 80px;
            z-index: 9998;
            background: transparent;
        }
        
        body.pull-refresh-active {
            overflow: hidden;
            position: fixed;
            width: 100%;
            height: 100%;
        }
    `;
    document.head.appendChild(style);
    
    // Créer une zone tactile spécifique en haut de l'écran
    const ptrZone = document.createElement('div');
    ptrZone.className = 'ptr-zone';
    document.body.appendChild(ptrZone);
    
    // Variables pour suivre le geste
    let touchStartY = 0;
    let touchEndY = 0;
    let refreshing = false;
    let pullDistance = 0;
    let pullThreshold = 80; // Distance minimale pour déclencher le rafraîchissement
    
    // Fonction pour déclencher le rafraîchissement
    function triggerRefresh() {
        refreshing = true;
        refreshIndicator.classList.add('refreshing');
        refreshIndicator.querySelector('span').textContent = 'Mise à jour...';
        
        console.log('Rafraîchissement déclenché');
        
        // Attendre un court délai avant de rafraîchir pour l'effet visuel
        setTimeout(() => {
            // Recharger la page
            window.location.reload();
        }, 800);
    }
    
    // Détection de l'appareil iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    if (isIOS) {
        // Méthode spécifique pour iOS qui est connue pour mieux fonctionner
        console.log('Utilisation de la méthode iOS pour pull-to-refresh');
        
        // Gérer les événements sur la zone dédiée pour iOS
        ptrZone.addEventListener('touchstart', function(e) {
            if (refreshing) return;
            touchStartY = e.touches[0].clientY;
            console.log('iOS - Début du toucher:', touchStartY);
        }, { passive: true });
        
        ptrZone.addEventListener('touchmove', function(e) {
            if (refreshing) return;
            if (window.scrollY > 10) return;
            
            const currentY = e.touches[0].clientY;
            pullDistance = currentY - touchStartY;
            
            if (pullDistance > 0) {
                e.preventDefault();
                const resistance = 0.4;
                const transformY = Math.min(pullDistance * resistance, pullThreshold);
                
                refreshIndicator.style.transform = `translateY(${transformY - 70}px)`;
                
                // Mise à jour du texte
                if (pullDistance * resistance > pullThreshold - 10) {
                    refreshIndicator.querySelector('span').textContent = 'Relâchez pour rafraîchir';
                } else {
                    refreshIndicator.querySelector('span').textContent = 'Glissez pour rafraîchir';
                }
                
                console.log('iOS - Distance tirée:', pullDistance);
            }
        }, { passive: false });
        
        ptrZone.addEventListener('touchend', function(e) {
            if (refreshing) return;
            
            touchEndY = e.changedTouches[0].clientY;
            const finalPullDistance = touchEndY - touchStartY;
            
            console.log('iOS - Fin du toucher:', finalPullDistance);
            
            if (finalPullDistance > 0 && finalPullDistance * 0.4 >= pullThreshold - 10) {
                triggerRefresh();
            } else {
                refreshIndicator.style.transform = 'translateY(-70px)';
            }
            
            pullDistance = 0;
        }, { passive: true });
    } else {
        // Méthode pour Android et autres navigateurs
        console.log('Utilisation de la méthode standard pour pull-to-refresh');
        
        // Approche 1: Gérer les événements sur le document entier
        document.addEventListener('touchstart', function(e) {
            if (refreshing) return;
            if (window.scrollY <= 10) {
                touchStartY = e.touches[0].clientY;
                console.log('Toucher détecté en haut de page:', touchStartY);
            }
        }, { passive: true });
        
        document.addEventListener('touchmove', function(e) {
            if (refreshing) return;
            if (window.scrollY > 10) return;
            
            const currentY = e.touches[0].clientY;
            pullDistance = currentY - touchStartY;
            
            if (pullDistance > 0) {
                const resistance = 0.4;
                const transformY = Math.min(pullDistance * resistance, pullThreshold);
                
                refreshIndicator.style.transform = `translateY(${transformY - 70}px)`;
                
                if (pullDistance * resistance > pullThreshold - 10) {
                    refreshIndicator.querySelector('span').textContent = 'Relâchez pour rafraîchir';
                } else {
                    refreshIndicator.querySelector('span').textContent = 'Glissez pour rafraîchir';
                }
                
                // Uniquement empêcher le défilement si on tire suffisamment
                if (pullDistance > 30) {
                    try {
                        e.preventDefault();
                        document.body.classList.add('pull-refresh-active');
                    } catch (error) {
                        console.log('Impossible d\'empêcher le défilement par défaut');
                    }
                }
                
                console.log('Distance tirée:', pullDistance);
            }
        }, { passive: false });
        
        document.addEventListener('touchend', function(e) {
            if (refreshing) return;
            
            touchEndY = e.changedTouches[0].clientY;
            const finalPullDistance = touchEndY - touchStartY;
            
            document.body.classList.remove('pull-refresh-active');
            
            console.log('Fin du toucher:', finalPullDistance);
            
            if (finalPullDistance > 0 && finalPullDistance * 0.4 >= pullThreshold - 10 && window.scrollY <= 10) {
                triggerRefresh();
            } else {
                refreshIndicator.style.transform = 'translateY(-70px)';
            }
            
            pullDistance = 0;
        }, { passive: true });
        
        // Approche 2: Utiliser aussi la zone dédiée comme secours
        ptrZone.addEventListener('touchstart', function(e) {
            if (refreshing) return;
            touchStartY = e.touches[0].clientY;
            console.log('Zone PTR - Début du toucher:', touchStartY);
        }, { passive: true });
        
        ptrZone.addEventListener('touchmove', function(e) {
            if (refreshing) return;
            
            const currentY = e.touches[0].clientY;
            pullDistance = currentY - touchStartY;
            
            if (pullDistance > 0) {
                e.preventDefault();
                const resistance = 0.4;
                const transformY = Math.min(pullDistance * resistance, pullThreshold);
                
                refreshIndicator.style.transform = `translateY(${transformY - 70}px)`;
                
                if (pullDistance * resistance > pullThreshold - 10) {
                    refreshIndicator.querySelector('span').textContent = 'Relâchez pour rafraîchir';
                } else {
                    refreshIndicator.querySelector('span').textContent = 'Glissez pour rafraîchir';
                }
                
                console.log('Zone PTR - Distance tirée:', pullDistance);
            }
        }, { passive: false });
        
        ptrZone.addEventListener('touchend', function(e) {
            if (refreshing) return;
            
            touchEndY = e.changedTouches[0].clientY;
            const finalPullDistance = touchEndY - touchStartY;
            
            console.log('Zone PTR - Fin du toucher:', finalPullDistance);
            
            if (finalPullDistance > 0 && finalPullDistance * 0.4 >= pullThreshold - 10) {
                triggerRefresh();
            } else {
                refreshIndicator.style.transform = 'translateY(-70px)';
            }
            
            pullDistance = 0;
        }, { passive: true });
    }
    
    // Ajouter un gestionnaire pour les erreurs qui pourraient survenir
    window.addEventListener('error', function(e) {
        if (e.message && e.message.includes('passive')) {
            console.warn('Erreur liée aux événements passifs détectée:', e.message);
        }
    });
}