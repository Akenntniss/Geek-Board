/**
 * Système de rapport de bugs
 * Permet aux utilisateurs de signaler facilement les bugs rencontrés
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des écouteurs d'événements pour le modal
    initBugReportModalEvents();
});

/**
 * Initialise tous les écouteurs d'événements nécessaires pour le modal
 */
function initBugReportModalEvents() {
    // Récupérer le formulaire
    const bugForm = document.getElementById('ajouterBugForm');
    if (!bugForm) return;
    
    // Ajouter l'écouteur d'événement pour la soumission du formulaire
    bugForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBugReport();
    });

    // Mettre à jour l'URL de la page actuelle à chaque ouverture du modal
    const bugModal = document.getElementById('ajouterBugModal');
    if (bugModal) {
        bugModal.addEventListener('show.bs.modal', function() {
            document.getElementById('bug_page_url').value = window.location.href;
        });
    }
}

/**
 * Soumet le rapport de bug au serveur
 */
function submitBugReport() {
    const form = document.getElementById('ajouterBugForm');
    const description = document.getElementById('bug_description').value.trim();
    
    if (!description) {
        alert('Veuillez décrire le problème rencontré.');
        return;
    }
    
    // Créer un objet FormData avec les données du formulaire
    const formData = new FormData(form);
    
    // Envoi des données au serveur
    fetch('ajax/add_bug_report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage();
        } else {
            if (data.message) {
                alert(data.message);
            } else {
                alert('Une erreur est survenue. Veuillez réessayer.');
            }
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue. Veuillez réessayer.');
    });
}

/**
 * Affiche le message de succès après l'envoi du rapport
 */
function showSuccessMessage() {
    // Fermer le modal
    const bugModal = bootstrap.Modal.getInstance(document.getElementById('ajouterBugModal'));
    if (bugModal) {
        bugModal.hide();
    }
    
    // Réinitialiser le formulaire
    document.getElementById('bug_description').value = '';
    
    // Afficher un message de succès avec toastr si disponible
    if (typeof toastr !== 'undefined') {
        toastr.success('Votre signalement a bien été enregistré. Merci de nous aider à améliorer l\'application !', 'Signalement envoyé');
    } else {
        alert('Votre signalement a bien été enregistré. Merci !');
    }
} 