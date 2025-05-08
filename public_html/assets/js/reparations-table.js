/**
 * Script pour ajuster la largeur et le comportement du tableau des réparations
 */
document.addEventListener("DOMContentLoaded", function() {
    // Obtenir tous les éléments importants
    const resultsContainer = document.querySelector(".results-container");
    const card = document.querySelector(".results-container .card");
    const cardBody = document.querySelector(".card-body");
    const tableView = document.querySelector("#table-view");
    const tableResponsive = document.querySelector(".table-responsive");
    const table = document.querySelector(".table-responsive .table");
    const cardsView = document.querySelector("#cards-view");
    
    console.log("Ajustement de la largeur des éléments pour le tableau...");
    
    // Appliquer les styles à tous les niveaux
    if (resultsContainer) {
        console.log("Ajustement du conteneur de résultats");
        resultsContainer.style.width = "95%";
        resultsContainer.style.margin = "0 auto";
        resultsContainer.style.maxWidth = "95%";
    }
    
    if (card) {
        console.log("Ajustement de la carte");
        card.style.width = "100%";
        card.style.margin = "0 auto";
    }
    
    if (cardBody) {
        console.log("Ajustement du corps de la carte");
        cardBody.style.padding = "1rem";
    }
    
    if (tableView) {
        console.log("Ajustement de la vue tableau");
        tableView.style.width = "100%";
        tableView.style.margin = "0";
        // On s'assure que le tableau est visible par défaut
        tableView.classList.remove("d-none");
    }
    
    if (tableResponsive) {
        console.log("Ajustement du conteneur responsive");
        tableResponsive.style.width = "100%";
        tableResponsive.style.overflowX = "auto";
    }
    
    if (table) {
        console.log("Ajustement du tableau");
        table.style.width = "100%";
    }
    
    if (cardsView) {
        console.log("Ajustement de la vue cartes");
        cardsView.style.width = "100%";
        cardsView.style.margin = "0";
        // On s'assure que la vue cartes est cachée par défaut
        cardsView.classList.add("d-none");
    }

    // Initialiser les vues correctement
    setupViewToggle();
});

/**
 * Fonction pour initialiser les bascules de vue entre tableau et cartes
 */
function setupViewToggle() {
    const tableView = document.getElementById('table-view');
    const cardsView = document.getElementById('cards-view');
    const tableBtn = document.getElementById('table-view-btn');
    const cardsBtn = document.getElementById('cards-view-btn');
    
    if (tableBtn && cardsBtn && tableView && cardsView) {
        console.log("Configuration des boutons de bascule de vue");
        
        // Déterminer la vue préférée de l'utilisateur
        let preferredView = localStorage.getItem('preferredView') || 'table';
        
        // Initialiser la vue active
        if (preferredView === 'table') {
            tableView.classList.remove('d-none');
            cardsView.classList.add('d-none');
            tableBtn.classList.add('active');
            cardsBtn.classList.remove('active');
        } else {
            tableView.classList.add('d-none');
            cardsView.classList.remove('d-none');
            tableBtn.classList.remove('active');
            cardsBtn.classList.add('active');
        }
        
        // Ajouter les écouteurs d'événements aux boutons
        tableBtn.addEventListener('click', function() {
            tableView.classList.remove('d-none');
            cardsView.classList.add('d-none');
            tableBtn.classList.add('active');
            cardsBtn.classList.remove('active');
            localStorage.setItem('preferredView', 'table');
        });
        
        cardsBtn.addEventListener('click', function() {
            tableView.classList.add('d-none');
            cardsView.classList.remove('d-none');
            tableBtn.classList.remove('active');
            cardsBtn.classList.add('active');
            localStorage.setItem('preferredView', 'cards');
        });
    }
} 