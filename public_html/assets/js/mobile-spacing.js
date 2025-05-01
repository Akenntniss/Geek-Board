// Script pour ajouter un espace de 90px en haut de la page d'accueil sur mobile
document.addEventListener('DOMContentLoaded', function() {
  // Vérifier si nous sommes sur la page d'accueil (accueil.php)
  const isHomePage = window.location.href.includes('page=accueil') || 
                     window.location.href.endsWith('index.php') || 
                     window.location.pathname === '/' ||
                     !window.location.href.includes('page=');
  
  // Vérifier si nous sommes sur mobile (max-width: 991px)
  const isMobile = window.matchMedia('(max-width: 991px)').matches;
  
  if (isHomePage && isMobile) {
    // Sélectionner l'élément principal à déplacer (.modern-dashboard ou le premier div après main)
    const mainContent = document.querySelector('.modern-dashboard') || 
                        document.querySelector('main > div:first-child');
    
    if (mainContent) {
      // Créer un div d'espacement de 90px
      const spacer = document.createElement('div');
      spacer.style.height = '90px';
      spacer.style.width = '100%';
      spacer.style.display = 'block';
      spacer.id = 'mobile-spacer';
      
      // Insérer le spacer avant le contenu principal
      mainContent.parentNode.insertBefore(spacer, mainContent);
    }
  }
}); 