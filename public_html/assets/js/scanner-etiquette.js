/**
 * Scanner de QR Code basé sur jsQR - compatible sur divers appareils
 */

document.addEventListener('DOMContentLoaded', function() {
    // Références DOM
    const startButton = document.getElementById('start-qr-scan');
    const stopButton = document.getElementById('stop-qr-scan');
    const statusLabel = document.getElementById('qr-scanner-status');
    const scannerModal = document.getElementById('scanner_etiquette_modal');
    const readerContainer = document.getElementById('reader');
    
    // Variables de contrôle
    let isScanning = false;
    let scanInterval = null;
    let video = null;
    let canvasElement = null;
    let canvas = null;
    
    // Fonction pour démarrer la caméra et le scan
    function startScanner() {
        if (isScanning) return;
        
        // Mettre à jour l'interface
        statusLabel.textContent = "Initialisation de la caméra...";
        statusLabel.className = "d-block p-2 fs-5 fw-semibold text-muted";
        
        // Créer les éléments nécessaires
        if (!readerContainer) {
            statusLabel.textContent = "Erreur: Conteneur scanner introuvable";
            statusLabel.className = "d-block p-2 fs-5 fw-semibold text-danger";
            return;
        }
        
        // Nettoyer le conteneur
        readerContainer.innerHTML = '';
        
        // Créer vidéo et canvas
        video = document.createElement('video');
        video.setAttribute('playsinline', true); // Requis pour iPhone
        video.setAttribute('autoplay', true);    // Ajouter autoplay
        video.setAttribute('muted', true);       // Ajouter muted (requis pour autoplay sur certains navigateurs)
        video.classList.add('scanner-video');
        
        canvasElement = document.createElement('canvas');
        canvasElement.classList.add('scanner-canvas');
        canvasElement.style.display = 'none'; // Cache le canvas
        
        // Ajouter au DOM
        readerContainer.appendChild(video);
        readerContainer.appendChild(canvasElement);
        canvas = canvasElement.getContext('2d', { willReadFrequently: true });
        
        // Démarrer la caméra avec la caméra arrière si possible
        const constraints = {
            video: { 
                facingMode: 'environment',
                width: { ideal: 1920 },  // Augmenter la résolution
                height: { ideal: 1080 }, // pour une meilleure détection
                // Autres paramètres pour améliorer la qualité d'image
                focusMode: 'continuous',
                exposureMode: 'continuous',
                whiteBalanceMode: 'continuous'
            },
            audio: false
        };
            
        navigator.mediaDevices.getUserMedia(constraints)
        .then(function(stream) {
            video.srcObject = stream;
            
            // Démarrer la vidéo et commencer à scanner une fois prêt
            video.onloadedmetadata = function() {
                // Définir les dimensions du canvas
                canvasElement.width = video.videoWidth;
                canvasElement.height = video.videoHeight;
                
                // Mettre à jour l'interface
                startButton.classList.add('d-none');
                stopButton.classList.remove('d-none');
                statusLabel.textContent = "Caméra active - Scannez un QR code";
                statusLabel.classList.remove('text-danger');
                
                // Activer la classe scanner-active
                readerContainer.classList.add('scanner-active');
                
                // Démarrer la boucle de scan plus fréquemment (100ms au lieu de 200ms)
                isScanning = true;
                scanInterval = setInterval(scanQRCode, 100);
            };
            
            // Pour s'assurer que la vidéo démarre
            video.play().catch(err => {
                console.error("Erreur lors du démarrage de la vidéo:", err);
            });
        })
        .catch(function(err) {
            console.error("Erreur d'accès à la caméra:", err);
            statusLabel.textContent = "Impossible d'accéder à la caméra. Erreur: " + (err.name || err.message || "Inconnue");
            statusLabel.className = "d-block p-2 fs-5 fw-semibold text-danger";
            startButton.classList.remove('d-none');
            stopButton.classList.add('d-none');
        });
    }
    
    // Fonction pour numériser le flux vidéo
    function scanQRCode() {
        if (!isScanning || !video || !canvas) return;
        
        // Vérifier si la vidéo est en cours de lecture
        if (video.readyState !== video.HAVE_ENOUGH_DATA) {
            return;
        }
        
        // Dessiner la vidéo sur le canvas 
        canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
        
        // Obtenir les données d'image
        try {
            const imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
            
            // Utiliser jsQR avec des paramètres plus permissifs
            if (window.jsQR) {
                scanWithJsQR(imageData);
            } 
            // Scanner avec BarcodeDetector s'il est disponible
            else if (window.BarcodeDetector) {
                scanWithBarcodeDetector(imageData);
            } 
            // Utiliser html5-qrcode comme dernier recours
            else if (window.Html5Qrcode) {
                // Cette méthode n'est pas idéale, mais juste au cas où
                statusLabel.textContent = "Scanner QR avec faible compatibilité";
            }
            else {
                statusLabel.textContent = "Aucune bibliothèque de scan QR disponible";
                stopScanner();
            }
        } catch (error) {
            console.error("Erreur lors du scan:", error);
        }
    }
    
    // Scan avec l'API BarcodeDetector native (plus récente)
    function scanWithBarcodeDetector(imageData) {
        const barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
        
        barcodeDetector.detect(imageData)
            .then(barcodes => {
                if (barcodes.length > 0) {
                    handleSuccessfulScan(barcodes[0].rawValue);
                }
            })
            .catch(err => {
                console.error("Erreur BarcodeDetector:", err);
            });
    }
    
    // Scan avec jsQR avec des paramètres optimisés pour la sensibilité
    function scanWithJsQR(imageData) {
        const options = {
            inversionAttempts: "attemptBoth", // Essayer les deux inversions (noir sur blanc et blanc sur noir)
            dontInvert: false // Permettre l'inversion
        };
        
        const code = jsQR(imageData.data, imageData.width, imageData.height, options);
        
        if (code) {
            handleSuccessfulScan(code.data);
        }
    }
    
    // Gestion d'un scan réussi
    function handleSuccessfulScan(decodedText) {
        console.log("QR code scanné:", decodedText);
        
        try {
            // 1. Si c'est directement un nombre, c'est probablement un ID
            if (/^\d+$/.test(decodedText)) {
                processResult(decodedText);
                return;
            }
            
            // 2. Si c'est une URL avec paramètre id
            if (decodedText.includes('id=')) {
                const match = decodedText.match(/id=(\d+)/);
                if (match && match[1]) {
                    processResult(match[1]);
                    return;
                }
            }
            
            // 3. Si c'est une URL de statut rapide
            if (decodedText.includes('statut_rapide')) {
                // Extraire l'ID comme dernier recours
                const numberMatch = decodedText.match(/\b(\d+)\b/);
                if (numberMatch && numberMatch[1]) {
                    processResult(numberMatch[1]);
                    return;
                }
            }
            
            // 4. Chercher n'importe quel nombre dans le texte comme dernier recours
            const anyNumberMatch = decodedText.match(/\d+/);
            if (anyNumberMatch && anyNumberMatch[0]) {
                processResult(anyNumberMatch[0]);
                return;
            }
            
            // Aucun ID trouvé
            statusLabel.textContent = "Code QR détecté mais non reconnu";
            statusLabel.className = "d-block p-2 fs-5 fw-semibold text-danger";
        } catch (error) {
            console.error("Erreur lors du traitement du QR code:", error);
            statusLabel.textContent = "Erreur lors du traitement du QR code";
            statusLabel.className = "d-block p-2 fs-5 fw-semibold text-danger";
        }
    }
    
    // Traitement du résultat
    function processResult(id) {
        // Afficher un message de succès
        statusLabel.textContent = "QR Code détecté! Redirection...";
        statusLabel.className = "d-block p-2 fs-5 fw-semibold text-success";
        
        // Arrêter le scanner
        stopScanner();
        
        // Rediriger après un court délai
        setTimeout(() => {
            // Fermer le modal
            try {
                const modalInstance = bootstrap.Modal.getInstance(scannerModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            } catch (error) {
                console.warn("Erreur lors de la fermeture du modal:", error);
            }
            
            // Rediriger vers la page du statut
            window.location.href = `index.php?page=statut_rapide&id=${id}`;
        }, 800);
    }
    
    // Fonction pour arrêter le scanner
    function stopScanner() {
        isScanning = false;
        
        // Arrêter la boucle de scan
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }
        
        // Arrêter la vidéo et libérer la caméra
        if (video && video.srcObject) {
            const tracks = video.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            video.srcObject = null;
        }
        
        // Mettre à jour l'interface
        readerContainer.classList.remove('scanner-active');
        startButton.classList.remove('d-none');
        stopButton.classList.add('d-none');
        statusLabel.textContent = "Scanner arrêté";
        statusLabel.className = "d-block p-2 fs-5 fw-semibold text-muted";
    }
    
    // Attacher les gestionnaires d'événements
    if (startButton) startButton.addEventListener('click', startScanner);
    if (stopButton) stopButton.addEventListener('click', stopScanner);
    
    // Gérer l'ouverture/fermeture du modal
    if (scannerModal) {
        // Quand le modal est caché (fermé)
        scannerModal.addEventListener('hidden.bs.modal', stopScanner);
        
        // Quand le modal est affiché (ouvert)
        scannerModal.addEventListener('shown.bs.modal', function() {
            // Démarrer la caméra immédiatement
            startScanner();
        });
    }
}); 