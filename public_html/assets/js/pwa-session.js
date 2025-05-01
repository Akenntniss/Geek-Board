// Gestion des sessions PWA
document.addEventListener('DOMContentLoaded', function() {
    // Détecter si l'application est en mode standalone (PWA)
    const isInStandaloneMode = () => 
        (window.matchMedia('(display-mode: standalone)').matches) || 
        (window.navigator.standalone) || 
        document.referrer.includes('android-app://');
    
    // Si nous sommes en mode PWA, configurer la session
    if (isInStandaloneMode()) {
        // Définir un cookie pour indiquer le mode PWA
        document.cookie = 'pwa_mode=1; path=/; max-age=2592000'; // 30 jours
        
        // Ajouter la classe pwa-mode au body
        document.body.classList.add('pwa-mode');
        
        // Vérifier si l'utilisateur est connecté
        fetch('/api/check_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.logged_in) {
                    // Si l'utilisateur n'est pas connecté, vérifier s'il y a un token de session
                    const token = getCookie('mdgeek_remember');
                    if (token) {
                        // Tenter de restaurer la session
                        fetch('/api/restore_session.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ token: token })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Session restaurée avec succès
                                console.log('Session PWA restaurée');
                                // Recharger la page si nécessaire
                                if (window.location.pathname === '/pages/login.php') {
                                    window.location.href = '/index.php';
                                }
                            }
                        });
                    }
                }
            });
    }
});

// Fonction utilitaire pour récupérer un cookie par son nom
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
} 