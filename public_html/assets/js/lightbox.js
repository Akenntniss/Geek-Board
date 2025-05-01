// Créer la structure HTML de la lightbox
const lightboxHTML = `
    <div class="lightbox-overlay">
        <div class="lightbox-container">
            <img src="" alt="Image en plein écran" class="lightbox-image">
            <button class="lightbox-close">
                <i class="fas fa-times"></i>
            </button>
            <div class="lightbox-nav">
                <button class="lightbox-prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="lightbox-next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="lightbox-counter"></div>
        </div>
    </div>
`;

// Ajouter la lightbox au document
document.body.insertAdjacentHTML('beforeend', lightboxHTML);

// Sélectionner les éléments de la lightbox
const lightbox = {
    overlay: document.querySelector('.lightbox-overlay'),
    image: document.querySelector('.lightbox-image'),
    closeBtn: document.querySelector('.lightbox-close'),
    prevBtn: document.querySelector('.lightbox-prev'),
    nextBtn: document.querySelector('.lightbox-next'),
    counter: document.querySelector('.lightbox-counter')
};

// Variables pour gérer la galerie
let currentImages = [];
let currentIndex = 0;

// Fonction pour ouvrir la lightbox
function openLightbox(images, startIndex = 0) {
    currentImages = images;
    currentIndex = startIndex;
    updateLightboxImage();
    lightbox.overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Fonction pour fermer la lightbox
function closeLightbox() {
    lightbox.overlay.classList.remove('show');
    document.body.style.overflow = '';
}

// Fonction pour mettre à jour l'image affichée
function updateLightboxImage() {
    const imagePath = currentImages[currentIndex];
    lightbox.image.src = imagePath;
    updateCounter();
    updateNavigationButtons();
}

// Fonction pour mettre à jour le compteur
function updateCounter() {
    lightbox.counter.textContent = `${currentIndex + 1} / ${currentImages.length}`;
}

// Fonction pour mettre à jour les boutons de navigation
function updateNavigationButtons() {
    lightbox.prevBtn.style.display = currentIndex > 0 ? '' : 'none';
    lightbox.nextBtn.style.display = currentIndex < currentImages.length - 1 ? '' : 'none';
}

// Fonction pour afficher l'image précédente
function showPrevImage() {
    if (currentIndex > 0) {
        currentIndex--;
        updateLightboxImage();
    }
}

// Fonction pour afficher l'image suivante
function showNextImage() {
    if (currentIndex < currentImages.length - 1) {
        currentIndex++;
        updateLightboxImage();
    }
}

// Ajouter les écouteurs d'événements
lightbox.closeBtn.addEventListener('click', closeLightbox);
lightbox.prevBtn.addEventListener('click', showPrevImage);
lightbox.nextBtn.addEventListener('click', showNextImage);
lightbox.overlay.addEventListener('click', (e) => {
    if (e.target === lightbox.overlay) {
        closeLightbox();
    }
});

// Ajouter la navigation au clavier
document.addEventListener('keydown', (e) => {
    if (!lightbox.overlay.classList.contains('show')) return;
    
    switch (e.key) {
        case 'ArrowLeft':
            showPrevImage();
            break;
        case 'ArrowRight':
            showNextImage();
            break;
        case 'Escape':
            closeLightbox();
            break;
    }
});

// Ajouter les écouteurs d'événements aux images dans le modal de détails
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const clickedImage = e.target.closest('.img-thumbnail');
        if (!clickedImage) return;

        const photoContainer = clickedImage.closest('.row');
        if (!photoContainer) return;

        const allImages = Array.from(photoContainer.querySelectorAll('.img-thumbnail'));
        if (allImages.length === 0) return;

        const imageUrls = allImages.map(img => img.src);
        const clickedIndex = imageUrls.indexOf(clickedImage.src);

        e.preventDefault();
        openLightbox(imageUrls, clickedIndex);
    });
});