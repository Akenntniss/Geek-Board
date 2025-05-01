/**
 * rachat-form.js - Gestion du formulaire de rachat d'appareils
 * Ce script centralise toutes les fonctionnalités du formulaire de rachat
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments du formulaire
    const rachatForm = document.getElementById('rachatForm');
    const steps = document.querySelectorAll('.rachat-step');
    const nextBtn = document.getElementById('nextStep');
    const prevBtn = document.getElementById('prevStep');
    const submitBtn = document.getElementById('submitRachat');
    const progressBar = document.querySelector('.progress-bar');
    
    // Variables pour la gestion des étapes
    let currentStep = 0;
    const totalSteps = steps.length;
    
    // Éléments pour la signature
    const signatureCanvas = document.getElementById('signatureCanvas');
    const signatureInput = document.getElementById('signatureInput');
    let signaturePad = null;
    
    // Éléments pour les photos
    const photoIdentite = document.getElementById('photo_identite');
    const idPhotoImage = document.getElementById('id-photo-image');
    const idPhotoPlaceholder = document.getElementById('id-photo-placeholder');
    
    const photoAppareil = document.getElementById('photo_appareil');
    const devicePhotoImage = document.getElementById('device-photo-image');
    const devicePhotoPlaceholder = document.getElementById('device-photo-placeholder');
    
    // Éléments pour la caméra
    const cameraVideo = document.getElementById('cameraVideo');
    const cameraCanvas = document.getElementById('cameraCanvas');
    const capturedPhoto = document.getElementById('capturedPhoto');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const clientPhotoInput = document.getElementById('clientPhotoInput');
    
    // Correction du problème des champs dupliqués
    // Si nous avons une duplication des champs, nous assurons qu'un seul est actif
    const fixDuplicateInputs = () => {
        // Pour la pièce d'identité, nous gardons uniquement celui dans l'étape 2
        const identityInputs = document.querySelectorAll('input[name="photo_identite"]');
        if (identityInputs.length > 1) {
            // Garde seulement le premier élément (celui dans la carte avec preview)
            for (let i = 1; i < identityInputs.length; i++) {
                const parent = identityInputs[i].parentElement;
                if (parent) {
                    parent.style.display = 'none';
                }
            }
        }
        
        // Correction pour le div incomplet
        const incompleteDiv = document.querySelector('.col-md-12:empty');
        if (incompleteDiv) {
            incompleteDiv.remove();
        }
        
        // Ne pas cacher les footers de modal, ils sont nécessaires
        // Assurons-nous plutôt que les boutons restent dans le modal
        document.querySelectorAll('.modal-footer').forEach(footer => {
            // S'assurer que footer a un flex container
            if (!footer.querySelector('.d-flex')) {
                const buttons = footer.querySelectorAll('button');
                if (buttons.length > 0) {
                    const container = document.createElement('div');
                    container.className = 'd-flex justify-content-between w-100';
                    
                    // Regrouper les boutons dans le container
                    Array.from(buttons).forEach(button => {
                        container.appendChild(button.cloneNode(true));
                        button.remove();
                    });
                    
                    footer.appendChild(container);
                }
            }
        });
        
        console.log('Corrections appliquées au DOM');
    };
    
    // Initialisation du formulaire
    const initForm = () => {
        fixDuplicateInputs();
        updateProgress();
        initSignaturePad();
        initPhotoPreview();
    };
    
    // Mise à jour de la barre de progression
    const updateProgress = () => {
        const progressPercentage = ((currentStep + 1) / totalSteps) * 100;
        progressBar.style.width = progressPercentage + '%';
        progressBar.setAttribute('aria-valuenow', progressPercentage);
    };
    
    // Navigation entre les étapes
    const goToStep = (step) => {
        if (step < 0 || step >= totalSteps) return;
        
        if (step > currentStep && !validateCurrentStep()) {
            return;
        }
        
        steps.forEach((s, i) => {
            s.classList.toggle('d-none', i !== step);
        });
        
        currentStep = step;
        
        // Mise à jour des boutons
        prevBtn.disabled = currentStep === 0;
        nextBtn.classList.toggle('d-none', currentStep === totalSteps - 1);
        submitBtn.classList.toggle('d-none', currentStep !== totalSteps - 1);
        
        updateProgress();
        
        // Si nous sommes à l'étape de signature, initialiser la caméra
        if (currentStep === 2) {
            initCamera();
        } else {
            stopCamera();
        }
    };
    
    // Validation de l'étape courante
    const validateCurrentStep = () => {
        const currentStepElement = steps[currentStep];
        const inputs = currentStepElement.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        // Validations spécifiques par étape
        if (currentStep === 0) {
            // Validation étape 1: client et appareil
            const clientSelect = document.getElementById('client_id');
            if (clientSelect.value === '') {
                isValid = false;
                clientSelect.classList.add('is-invalid');
            }
        } else if (currentStep === 1) {
            // Validation étape 2: photos 
            if (!photoIdentite.files.length) {
                isValid = false;
                photoIdentite.classList.add('is-invalid');
            }
            if (!photoAppareil.files.length) {
                isValid = false;
                photoAppareil.classList.add('is-invalid');
            }
        } else if (currentStep === 2) {
            // Validation étape 3: signature
            if (signaturePad && signaturePad.isEmpty()) {
                isValid = false;
                document.querySelector('.signature-pad').classList.add('border-danger');
            } else {
                document.querySelector('.signature-pad').classList.remove('border-danger');
            }
            
            if (!clientPhotoInput.value) {
                isValid = false;
                photoPlaceholder.parentElement.classList.add('border-danger');
            } else {
                photoPlaceholder.parentElement.classList.remove('border-danger');
            }
        }
        
        return isValid;
    };
    
    // Initialisation du pad de signature
    const initSignaturePad = () => {
        if (!signatureCanvas) return;
        
        signaturePad = new SignaturePad(signatureCanvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
        
        // Redimensionner le canvas pour qu'il remplisse son parent
        const resizeCanvas = () => {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            signatureCanvas.width = signatureCanvas.offsetWidth * ratio;
            signatureCanvas.height = signatureCanvas.offsetHeight * ratio;
            signatureCanvas.getContext('2d').scale(ratio, ratio);
            signaturePad.clear();
        };
        
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
    };
    
    // Effacer la signature
    window.clearSignature = function() {
        if (signaturePad) {
            signaturePad.clear();
            signatureInput.value = '';
        }
    };
    
    // Initialisation des aperçus de photos
    const initPhotoPreview = () => {
        if (photoIdentite) {
            photoIdentite.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // S'assurer que les éléments existent
                        const idPhotoImage = document.getElementById('id-photo-image');
                        const idPhotoPlaceholder = document.getElementById('id-photo-placeholder');
                        
                        if (idPhotoImage && idPhotoPlaceholder) {
                            idPhotoImage.src = e.target.result;
                            idPhotoImage.classList.remove('d-none');
                            idPhotoPlaceholder.classList.add('d-none');
                            
                            // Logging pour le débogage
                            console.log('Photo d\'identité chargée:', idPhotoImage.src.substring(0, 50) + '...');
                        } else {
                            console.error('Éléments d\'aperçu de la pièce d\'identité non trouvés');
                        }
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        } else {
            console.error('Élément photo_identite non trouvé');
        }
        
        if (photoAppareil) {
            photoAppareil.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // S'assurer que les éléments existent
                        const devicePhotoImage = document.getElementById('device-photo-image');
                        const devicePhotoPlaceholder = document.getElementById('device-photo-placeholder');
                        
                        if (devicePhotoImage && devicePhotoPlaceholder) {
                            devicePhotoImage.src = e.target.result;
                            devicePhotoImage.classList.remove('d-none');
                            devicePhotoPlaceholder.classList.add('d-none');
                            
                            // Logging pour le débogage
                            console.log('Photo d\'appareil chargée:', devicePhotoImage.src.substring(0, 50) + '...');
                        } else {
                            console.error('Éléments d\'aperçu de la photo d\'appareil non trouvés');
                        }
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        } else {
            console.error('Élément photo_appareil non trouvé');
        }
    };
    
    // Initialisation de la caméra
    const initCamera = () => {
        if (!cameraVideo) return;
        
        const cameraPreview = document.querySelector('.camera-preview');
        if (cameraPreview) {
            cameraPreview.classList.remove('d-none');
        }
        
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    cameraVideo.srcObject = stream;
                })
                .catch(function(error) {
                    console.error("Erreur d'accès à la caméra:", error);
                });
        }
    };
    
    // Arrêt de la caméra
    const stopCamera = () => {
        if (!cameraVideo) return;
        
        const cameraPreview = document.querySelector('.camera-preview');
        if (cameraPreview) {
            cameraPreview.classList.add('d-none');
        }
        
        if (cameraVideo.srcObject) {
            cameraVideo.srcObject.getTracks().forEach(track => track.stop());
            cameraVideo.srcObject = null;
        }
    };
    
    // Capture de la photo du client
    const captureClientPhoto = () => {
        if (!cameraVideo || !cameraCanvas || !capturedPhoto) return;
        
        const context = cameraCanvas.getContext('2d');
        cameraCanvas.width = cameraVideo.videoWidth;
        cameraCanvas.height = cameraVideo.videoHeight;
        context.drawImage(cameraVideo, 0, 0, cameraCanvas.width, cameraCanvas.height);
        
        const photoData = cameraCanvas.toDataURL('image/png');
        capturedPhoto.src = photoData;
        capturedPhoto.classList.remove('d-none');
        photoPlaceholder.classList.add('d-none');
        clientPhotoInput.value = photoData;
    };
    
    // Soumission du formulaire
    const submitForm = () => {
        if (!validateCurrentStep()) return;
        
        // Capture de la signature
        if (signaturePad && !signaturePad.isEmpty()) {
            signatureInput.value = signaturePad.toDataURL();
        }
        
        // Capture de la photo du client s'il n'y en a pas
        if (!clientPhotoInput.value && cameraVideo) {
            captureClientPhoto();
        }
        
        // Soumission AJAX du formulaire
        const formData = new FormData(rachatForm);
        
        fetch('/ajax/save_rachat.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès
                Swal.fire({
                    title: 'Succès !',
                    text: 'Le rachat a été enregistré avec succès.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Recharger la page ou rediriger
                    window.location.reload();
                });
            } else {
                // Afficher un message d'erreur
                Swal.fire({
                    title: 'Erreur',
                    text: data.message || 'Une erreur est survenue lors de l\'enregistrement du rachat.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                title: 'Erreur',
                text: 'Une erreur est survenue lors de la communication avec le serveur.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });
    };
    
    // Événements
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            goToStep(currentStep + 1);
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            goToStep(currentStep - 1);
        });
    }
    
    if (submitBtn) {
        submitBtn.addEventListener('click', submitForm);
    }
    
    // Capture de photo lors de la signature
    if (signatureCanvas) {
        signatureCanvas.addEventListener('mouseup', function() {
            if (signaturePad && !signaturePad.isEmpty() && cameraVideo) {
                captureClientPhoto();
            }
        });
        
        signatureCanvas.addEventListener('touchend', function() {
            if (signaturePad && !signaturePad.isEmpty() && cameraVideo) {
                captureClientPhoto();
            }
        });
    }
    
    // Initialisation
    initForm();
}); 