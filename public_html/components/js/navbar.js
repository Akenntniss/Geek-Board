/**
 * GeekBoard Navigation JavaScript
 * Handles PWA detection, mobile navigation, and interactive behaviors
 */

document.addEventListener('DOMContentLoaded', function() {
    // ===== Détection des appareils mobiles =====
    const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobileDevice) {
        document.body.classList.add('mobile-device');
    }
    
    // ===== PWA Mode Detection =====
    const isPwa = window.matchMedia('(display-mode: standalone)').matches || 
                  window.navigator.standalone || 
                  document.referrer.includes('android-app://');
    
    if (isPwa) {
        document.body.classList.add('pwa-mode');
        
        // iOS specific adjustments
        if (/iPhone/.test(navigator.userAgent)) {
            document.body.classList.add('ios-pwa');
            
            // iPhone with Dynamic Island or notch
            if (window.screen.height >= 812 && window.screen.width >= 375) {
                document.body.classList.add('ios-dynamic-island');
            }
        }
        
        // Android specific adjustments
        if (/Android/.test(navigator.userAgent)) {
            document.body.classList.add('android-pwa');
        }
        
        // Save PWA mode to session via AJAX
        fetch('set_pwa_mode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'pwa_mode=true'
        }).catch(error => console.error('Erreur lors de la définition du mode PWA:', error));
        
        // Stocker en cookie pour la persistance
        document.cookie = "pwa_mode=true; path=/; max-age=86400";
    }
    
    // ===== Navbar Interactions =====
    
    // Close the offcanvas menu when a menu item is clicked
    document.querySelectorAll('#mainMenuOffcanvas .list-group-item').forEach(item => {
        item.addEventListener('click', function() {
            const bsOffcanvas = bootstrap.Offcanvas.getInstance('#mainMenuOffcanvas');
            if (bsOffcanvas) {
                bsOffcanvas.hide();
            }
        });
    });
    
    // Mobile Navbar Active State
    const currentPath = window.location.pathname;
    const currentSearch = window.location.search;
    
    document.querySelectorAll('#mobile-dock .dock-item').forEach(link => {
        const linkHref = link.getAttribute('href');
        
        if (linkHref === 'index.php' && (currentPath === '/' || currentPath === '/index.php') && !currentSearch) {
            link.classList.add('active');
        } else if (linkHref && currentSearch && currentSearch.includes(linkHref.split('?')[1])) {
            link.classList.add('active');
        }
    });
    
    // ===== Navbar Scroll Behavior =====
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const navbar = document.getElementById('desktop-navbar');
        
        // Skip if navbar doesn't exist or on very small scrolls
        if (!navbar || Math.abs(lastScrollTop - currentScrollTop) <= 5) {
            return;
        }
        
        // Add shadow and change background opacity on scroll
        if (currentScrollTop > 10) {
            navbar.classList.add('scrolled');
            navbar.style.boxShadow = '0 4px 10px rgba(0,0,0,0.1)';
        } else {
            navbar.classList.remove('scrolled');
            navbar.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
        }
        
        // For mobile devices, hide/show bottom navbar on scroll
        if (window.innerWidth < 992) {
            const mobileNavbar = document.getElementById('mobile-dock');
            
            if (mobileNavbar && currentScrollTop > lastScrollTop && currentScrollTop > 100) {
                // Scrolling down, hide navbar
                mobileNavbar.style.transform = 'translateY(100%)';
            } else if (mobileNavbar) {
                // Scrolling up, show navbar
                mobileNavbar.style.transform = 'translateY(0)';
            }
        }
        
        lastScrollTop = currentScrollTop;
    }, {passive: true});
    
    // ===== Dark Mode Detection =====
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add('supports-dark-mode');
    }
    
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (e.matches) {
            document.body.classList.add('supports-dark-mode');
        } else {
            document.body.classList.remove('supports-dark-mode');
        }
    });
}); 