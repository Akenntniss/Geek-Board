document.addEventListener('DOMContentLoaded', function() {
    // Gestion du modal scanner
    const scannerModal = document.getElementById('scannerModal');
    if (scannerModal) {
        scannerModal.addEventListener('show.bs.modal', function() {
            // Réinitialiser le scanner lors de l'ouverture du modal
            const scannerContainer = document.getElementById('scanner-container');
            const startScanButton = document.getElementById('startScanButton');
            const pauseScanButton = document.getElementById('pauseScanButton');
            
            if (scannerContainer && startScanButton && pauseScanButton) {
                scannerContainer.classList.add('d-none');
                startScanButton.classList.remove('d-none');
                pauseScanButton.classList.add('d-none');
            }
        });

        scannerModal.addEventListener('hidden.bs.modal', function() {
            // Arrêter le scanner lors de la fermeture du modal
            const scanner = document.getElementById('scanner');
            if (scanner && scanner.srcObject) {
                scanner.srcObject.getTracks().forEach(track => track.stop());
            }
        });
    }

    // Gestion des boutons de quantité
    const quantityInput = document.getElementById('quantity-input');
    const quantityPlus = document.getElementById('quantity-plus');
    const quantityMinus = document.getElementById('quantity-minus');

    if (quantityPlus) {
        quantityPlus.addEventListener('click', function() {
            if (quantityInput) {
                quantityInput.value = parseInt(quantityInput.value) + 1;
            }
        });
    }

    if (quantityMinus) {
        quantityMinus.addEventListener('click', function() {
            if (quantityInput && parseInt(quantityInput.value) > 1) {
                quantityInput.value = parseInt(quantityInput.value) - 1;
            }
        });
    }

    // Gestion de la saisie manuelle du code-barres
    const barcodeInput = document.getElementById('barcode-input');
    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchButton = document.getElementById('search-button');
                if (searchButton) {
                    searchButton.click();
                }
            }
        });
    }
}); 