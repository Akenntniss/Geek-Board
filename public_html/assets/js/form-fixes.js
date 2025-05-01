// Ce script empêche les soumissions accidentelles de formulaires qui pourraient causer des redirections
document.addEventListener('DOMContentLoaded', function() {
    console.log('Form-fixes.js chargé');
    
    // Empêcher la soumission de formulaire par défaut pour tous les formulaires dans les modaux
    document.querySelectorAll('.modal form').forEach(form => {
        form.addEventListener('submit', function(e) {
            console.log('Soumission de formulaire interceptée');
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    });
    
    // Empêcher le clic sur les boutons dans les formulaires de soumettre le formulaire
    // Mais ne pas interférer avec les select et leurs options
    document.querySelectorAll('.modal form button:not([type="submit"])').forEach(button => {
        // Ne pas ajouter d'événement si le bouton est dans un select
        if (!button.closest('select')) {
            button.addEventListener('click', function(e) {
                console.log('Clic de bouton intercepté');
                // Ne pas arrêter la propagation pour les menus déroulants
                if (!this.classList.contains('dropdown-toggle') && 
                    !this.classList.contains('dropdown-item')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }
    });
    
    // Journal des valeurs des select pour débogage
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', function() {
            console.log('Select changé:', this.id, 'Valeur:', this.value);
        });
    });
    
    // Empêcher les événements de touche Entrée de soumettre les formulaires
    document.querySelectorAll('.modal form input').forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                console.log('Touche Entrée interceptée');
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });
    
    // Supprimer les gestionnaires d'événements redondants pour le formulaire de commande
    const commandeForm = document.getElementById('commande-form');
    if (commandeForm) {
        console.log('Nettoyage des gestionnaires d\'événements du formulaire de commande');
        const newForm = commandeForm.cloneNode(true);
        commandeForm.parentNode.replaceChild(newForm, commandeForm);
    }
    
    // Sécuriser spécifiquement le formulaire d'ajout de client
    const formNouveauClient = document.getElementById('formNouveauClient');
    if (formNouveauClient) {
        console.log('Sécurisation du formulaire d\'ajout de client');
        formNouveauClient.setAttribute('onsubmit', 'return false;');
        formNouveauClient.addEventListener('submit', function(e) {
            console.log('Soumission du formulaire d\'ajout de client interceptée');
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    }
}); 