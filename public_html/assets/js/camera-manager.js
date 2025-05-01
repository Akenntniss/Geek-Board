/**
 * Camera Manager pour formulaire de réparation
 * Gère la prise de photo pour les appareils à réparer
 */

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si les éléments existent sur la page
    if (!document.getElementById('rep_camera')) return;
    
    // Configuration de la caméra
    let stream;
    const camera = document.getElementById('rep_camera');
    const canvas = document.getElementById('rep_canvas');
    const photoPreview = document.getElementById('rep_photo_preview');
    const startButton = document.getElementById('rep_startCamera');
    const captureButton = document.getElementById('rep_takePhoto');
    const retakeButton = document.getElementById('rep_retakePhoto');
    const cameraContainer = document.querySelector('.camera-container');
    const photoInput = document.getElementById('rep_photo_appareil');

    startButton.addEventListener('click', async function() {
        try {
            const constraints = { 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 800 },
                    height: { ideal: 600 }
                } 
            };
            
            // Vérifier si on est sur iOS
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            camera.srcObject = stream;
            camera.style.display = 'block';
            captureButton.style.display = 'inline-block';
            startButton.style.display = 'none';
            
            // Pour iOS, empêcher le comportement plein écran et garder l'interface visible
            if (isIOS) {
                // Désactiver le mode plein écran automatique sur iOS
                camera.setAttribute('playsinline', 'playsinline');
                camera.setAttribute('controls', false);
                camera.setAttribute('autoplay', true);
                camera.setAttribute('muted', true);
                
                // S'assurer que le conteneur de la caméra est bien visible
                cameraContainer.style.zIndex = "100";
                cameraContainer.style.position = "relative";
                
                // Empêcher l'ouverture en plein écran
                camera.addEventListener('webkitbeginfullscreen', function(e) {
                    e.preventDefault();
                    camera.webkitExitFullscreen();
                });
            }
            
            camera.play();
        } catch (err) {
            console.error("Erreur d'accès à la caméra:", err);
            alert("Impossible d'accéder à la caméra. Veuillez vérifier les permissions.");
        }
    });

    captureButton.addEventListener('click', function() {
        try {
            // Définir des dimensions maximales pour la capture
            const maxWidth = 800;
            const maxHeight = 600;
            
            // Calculer les dimensions proportionnelles
            let targetWidth = camera.videoWidth;
            let targetHeight = camera.videoHeight;
            
            if (targetWidth > maxWidth) {
                const ratio = maxWidth / targetWidth;
                targetWidth = maxWidth;
                targetHeight = Math.floor(targetHeight * ratio);
            }
            
            if (targetHeight > maxHeight) {
                const ratio = maxHeight / targetHeight;
                targetHeight = maxHeight;
                targetWidth = Math.floor(targetWidth * ratio);
            }
            
            // Configurer le canvas avec les dimensions calculées
            canvas.width = targetWidth;
            canvas.height = targetHeight;
            
            // Dessiner l'image avec les dimensions réduites
            const ctx = canvas.getContext('2d');
            ctx.drawImage(camera, 0, 0, targetWidth, targetHeight);
            
            // Sur iOS, on réduit encore la qualité pour éviter les problèmes de mémoire
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const compression = isIOS ? 0.7 : 0.85; // Compression plus forte sur iOS
            
            const photo = canvas.toDataURL('image/jpeg', compression);
            photoPreview.src = photo;
            photoPreview.style.display = 'block';
            photoInput.value = photo;
            
            // Arrêter la caméra
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            camera.style.display = 'none';
            captureButton.style.display = 'none';
            retakeButton.style.display = 'inline-block';
            
            // Mettre à jour la validation des champs si la fonction existe
            if (typeof checkEtape3Fields === 'function') {
                checkEtape3Fields();
            }
        } catch (err) {
            console.error("Erreur lors de la capture:", err);
            alert("Une erreur est survenue lors de la capture de la photo.");
        }
    });

    retakeButton.addEventListener('click', function() {
        photoPreview.style.display = 'none';
        retakeButton.style.display = 'none';
        startButton.style.display = 'inline-block';
        photoInput.value = '';
        
        // Mettre à jour la validation des champs si la fonction existe
        if (typeof checkEtape3Fields === 'function') {
            checkEtape3Fields();
        }
    });
}); 