/**
 * Script de diagnostic pour les modales
 * Vérifie si les modales sont correctement initialisées et fonctionnelles
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DIAGNOSTIC DES MODALES ===');
    
    // Vérifier si Bootstrap est chargé
    console.log('Bootstrap disponible:', typeof bootstrap !== 'undefined');
    
    // Vérifier si la classe Modal de Bootstrap est disponible
    console.log('Bootstrap.Modal disponible:', typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined');
    
    // Liste des modales à vérifier
    const modalsToCheck = [
        'nouvelleActionModal',
        'menuPrincipalModal',
        'ajouterCommandeModal',
        'rechercheClientModal'
    ];
    
    // Vérifier chaque modale
    modalsToCheck.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        console.log(`Modale #${modalId}:`, {
            'Élément trouvé': modalElement !== null,
            'Classes': modalElement ? modalElement.className : 'N/A',
            'data-bs-backdrop': modalElement ? modalElement.getAttribute('data-bs-backdrop') : 'N/A',
            'tabindex': modalElement ? modalElement.getAttribute('tabindex') : 'N/A'
        });
        
        // Vérifier si la modale est initialisée par Bootstrap
        if (modalElement && typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
            const instance = bootstrap.Modal.getInstance(modalElement);
            console.log(`  Instance Bootstrap pour #${modalId}:`, instance !== null);
        }
    });
    
    // Vérifier les boutons qui ouvrent les modales
    const modalButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
    console.log('Nombre de boutons de modale trouvés:', modalButtons.length);
    
    modalButtons.forEach(button => {
        const targetId = button.getAttribute('data-bs-target');
        console.log('Bouton modal:', {
            'Target': targetId,
            'Type d\'élément': button.tagName,
            'Classes': button.className,
            'Texte': button.innerText || button.innerHTML
        });
    });
    
    // Fonction pour tester l'ouverture forcée d'une modale
    window.testOpenModal = function(modalId) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) {
            console.error(`Modale #${modalId} non trouvée!`);
            return;
        }
        
        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
            try {
                const bsModal = new bootstrap.Modal(modalElement);
                bsModal.show();
                console.log(`Modale #${modalId} ouverte via Bootstrap.Modal.show()`);
            } catch (error) {
                console.error(`Erreur lors de l'ouverture de la modale #${modalId} via Bootstrap:`, error);
            }
        } else {
            // Méthode alternative si Bootstrap n'est pas disponible
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            document.body.classList.add('modal-open');
            console.log(`Modale #${modalId} ouverte manuellement (sans Bootstrap)`);
        }
    };
    
    // Ajouter un bouton de test dans la page
    const testButtonContainer = document.createElement('div');
    testButtonContainer.style.position = 'fixed';
    testButtonContainer.style.top = '80px';
    testButtonContainer.style.right = '20px';
    testButtonContainer.style.zIndex = '9999';
    testButtonContainer.style.display = 'flex';
    testButtonContainer.style.flexDirection = 'column';
    testButtonContainer.style.gap = '10px';
    
    modalsToCheck.forEach(modalId => {
        const testButton = document.createElement('button');
        testButton.textContent = `Test ${modalId}`;
        testButton.className = 'btn btn-sm btn-info';
        testButton.addEventListener('click', () => window.testOpenModal(modalId));
        testButtonContainer.appendChild(testButton);
    });
    
    document.body.appendChild(testButtonContainer);
    
    console.log('=== FIN DU DIAGNOSTIC ===');
}); 