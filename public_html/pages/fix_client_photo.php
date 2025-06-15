<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Charger les paramètres de connexion
require_once __DIR__ . '/config/database.php';

echo '<h1>Correction de la capture photo du client</h1>';

// Vérifier si le dossier d'upload existe, sinon le créer
$uploadDir = __DIR__ . '/assets/images/rachat/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo '<div style="color: green; padding: 15px; background-color: #e6ffe6; border-radius: 5px; margin: 20px 0;">
        Le dossier <code>/assets/images/rachat/</code> a été créé.
    </div>';
} else {
    echo '<div style="color: blue; padding: 15px; background-color: #e6f7ff; border-radius: 5px; margin: 20px 0;">
        Le dossier <code>/assets/images/rachat/</code> existe déjà.
    </div>';
}

// Correction du code JavaScript pour la capture photo
$scriptPath = __DIR__ . '/pages/rachat_appareils.php';
if (file_exists($scriptPath)) {
    $content = file_get_contents($scriptPath);
    
    // Vérifier si des modifications sont nécessaires
    $needsModification = false;
    
    // Rechercher les signes de problèmes dans le code actuel
    if (strpos($content, 'async function startCamera()') !== false && strpos($content, 'setTimeout(capturePhoto, 1500)') !== false) {
        $needsModification = true;
    }
    
    if ($needsModification) {
        // Faire une sauvegarde
        file_put_contents($scriptPath . '.backup.' . date('YmdHis'), $content);
        
        // Remplacer la fonction capturePhoto pour la rendre plus robuste
        $newCapturePhotoFunction = '
// Fonction pour capturer la photo
function capturePhoto() {
    if (!stream) return;
    
    try {
        const video = document.getElementById(\'cameraVideo\');
        
        // Vérifier que la vidéo est bien chargée
        if (video.readyState !== 4) {
            console.log("Vidéo pas encore prête, attente supplémentaire...");
            setTimeout(capturePhoto, 500); // Réessayer dans 500ms
            return;
        }
        
        console.log("Dimensions vidéo:", video.videoWidth, "x", video.videoHeight);
        
        // Créer un canvas aux dimensions de la vidéo
        const canvas = document.createElement(\'canvas\');
        const context = canvas.getContext(\'2d\');
        
        // Définir les dimensions du canvas
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        
        if (canvas.width === 0 || canvas.height === 0) {
            console.error("Dimensions de canvas invalides:", canvas.width, "x", canvas.height);
            canvas.width = 640;
            canvas.height = 480;
        }
        
        // Dessiner la vidéo sur le canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Convertir le canvas en image
        capturedPhotoData = canvas.toDataURL(\'image/jpeg\', 0.9);
        
        // Vérifier la taille des données
        console.log("Taille des données photo:", capturedPhotoData.length, "caractères");
        
        // Afficher l\'image capturée
        const capturedPhoto = document.getElementById(\'capturedPhoto\');
        capturedPhoto.src = capturedPhotoData;
        capturedPhoto.classList.remove(\'d-none\');
        
        // Cacher le placeholder
        const photoPlaceholder = document.getElementById(\'photoPlaceholder\');
        if (photoPlaceholder) photoPlaceholder.classList.add(\'d-none\');
        
        // Arrêter la caméra
        stopCamera();
        
        // Marquer que la photo a été prise
        photoTaken = true;
        
        console.log("Photo captured successfully");
        
        // Stocker également dans le champ prévu pour ça
        document.getElementById(\'clientPhotoInput\').value = capturedPhotoData;
    } catch (err) {
        console.error("Erreur lors de la capture de la photo:", err);
    }
}';

        // Remplacer la fonction startCamera pour augmenter le délai
        $newStartCameraFunction = '
// Fonction pour démarrer la caméra
async function startCamera() {
    // Ne démarrer la caméra qu\'une seule fois
    if (stream || photoTaken) return;
    
    console.log("Starting camera...");
    
    try {
        const video = document.getElementById(\'cameraVideo\');
        const cameraPreview = document.querySelector(\'.camera-preview\');
        
        // Demander l\'accès à la caméra frontale
        stream = await navigator.mediaDevices.getUserMedia({
            video: { 
                facingMode: \'user\',
                width: { ideal: 640 },
                height: { ideal: 480 }
            },
            audio: false
        });
        
        // Afficher le flux vidéo
        video.srcObject = stream;
        cameraPreview.classList.remove(\'d-none\');
        
        // Attendre que la vidéo soit chargée
        video.onloadedmetadata = function() {
            video.play();
            console.log("Vidéo démarrée, dimensions:", video.videoWidth, "x", video.videoHeight);
            // Programmer la capture de la photo après un délai plus long
            setTimeout(capturePhoto, 2500);
        };
        
        console.log("Camera started, will capture photo after metadata is loaded");
    } catch (err) {
        console.error("Erreur d\'accès à la caméra:", err);
    }
}';

        // Mettre à jour le code de soumission du formulaire
        $newSubmitCode = '
        // Ajouter la photo capturée au formulaire
        if (capturedPhotoData) {
            console.log("Ajout de la photo client au formulaire");
            // Créer un champ caché pour la photo du client
            let photoInput = document.createElement(\'input\');
            photoInput.type = \'hidden\';
            photoInput.name = \'client_photo_data\';
            photoInput.value = capturedPhotoData;
            form.appendChild(photoInput);
            
            // Vérifier la taille des données avant envoi
            console.log("Taille des données photo (avant envoi):", capturedPhotoData.length, "caractères");
        } else {
            console.warn("Pas de photo client disponible");
        }';

        // Appliquer les remplacements
        $content = preg_replace(
            '/\/\/ Fonction pour capturer la photo\s*function capturePhoto\(\)[^}]*}\s*}\s*}/s',
            '// Fonction pour capturer la photo' . $newCapturePhotoFunction,
            $content
        );
        
        $content = preg_replace(
            '/\/\/ Fonction pour démarrer la caméra\s*async function startCamera\(\)[^}]*}\s*}\s*}/s',
            '// Fonction pour démarrer la caméra' . $newStartCameraFunction,
            $content
        );
        
        $content = preg_replace(
            '/\/\/ Ajouter la photo capturée au formulaire\s*if \(capturedPhotoData\) \{[^}]*}\s*/s',
            '// Ajouter la photo capturée au formulaire' . $newSubmitCode . "\n        ",
            $content
        );
        
        // Sauvegarder les modifications
        file_put_contents($scriptPath, $content);
        
        echo '<div style="color: green; padding: 15px; background-color: #e6ffe6; border-radius: 5px; margin: 20px 0;">
            <h3>Code JavaScript amélioré !</h3>
            <p>Les modifications suivantes ont été apportées :</p>
            <ul>
                <li>Optimisation de la fonction de capture photo</li>
                <li>Augmentation du délai avant la capture (2500ms au lieu de 1500ms)</li>
                <li>Vérification que la vidéo est bien chargée avant capture</li>
                <li>Ajout de logs pour débogage</li>
                <li>Amélioration de la gestion des erreurs</li>
            </ul>
            <p>Une sauvegarde du fichier original a été créée.</p>
        </div>';
    } else {
        echo '<div style="color: orange; padding: 15px; background-color: #fff9e6; border-radius: 5px; margin: 20px 0;">
            Le code ne nécessite pas de modifications ou est déjà optimisé.
        </div>';
    }
} else {
    echo '<div style="color: red; padding: 15px; background-color: #ffe6e6; border-radius: 5px; margin: 20px 0;">
        Le fichier <code>pages/rachat_appareils.php</code> n\'a pas été trouvé.
    </div>';
}

// Vérifier que le fichier save_rachat.php traite correctement la photo du client
$saveRachatPath = __DIR__ . '/ajax/save_rachat.php';
if (file_exists($saveRachatPath)) {
    $content = file_get_contents($saveRachatPath);
    
    // Vérifier si des modifications sont nécessaires
    $needsModification = false;
    
    // Rechercher les signes de problèmes dans le code actuel
    if (strpos($content, 'client_photo_data') === false || strpos($content, 'client_photo_name') === false) {
        $needsModification = true;
    }
    
    if ($needsModification) {
        // Faire une sauvegarde
        file_put_contents($saveRachatPath . '.backup.' . date('YmdHis'), $content);
        
        // Ajouter du code pour déboguer la photo client
        $debugPhotoCode = '
    // Déboguer la photo du client
    if (isset($_POST[\'client_photo_data\'])) {
        $debug_info[] = "client_photo_data reçu - Taille: " . strlen($_POST[\'client_photo_data\']) . " caractères";
        
        // Vérifier si les données commencent correctement par data:image
        if (strpos($_POST[\'client_photo_data\'], \'data:image\') === 0) {
            $debug_info[] = "client_photo_data: format valide";
        } else {
            $debug_info[] = "client_photo_data: format invalide - Début: " . substr($_POST[\'client_photo_data\'], 0, 30);
        }
    } else {
        $debug_info[] = "client_photo_data: non reçu";
    }';
        
        // Insérer le code de débogage avant le traitement de la photo client
        if (strpos($content, '// Traiter la photo du client capturée par la webcam') !== false) {
            $content = preg_replace(
                '/\/\/ Traiter la photo du client capturée par la webcam/s',
                $debugPhotoCode . "\n\n    // Traiter la photo du client capturée par la webcam",
                $content
            );
        } elseif (strpos($content, '// Insérer l\'enregistrement dans la base de données') !== false) {
            $content = preg_replace(
                '/\/\/ Insérer l\'enregistrement dans la base de données/s',
                $debugPhotoCode . "\n\n    // Traiter la photo du client capturée par la webcam
    \$client_photo_name = null;
    if (isset(\$_POST['client_photo_data']) && !empty(\$_POST['client_photo_data'])) {
        \$photo_data = \$_POST['client_photo_data'];
        \$photo_data = str_replace('data:image/jpeg;base64,', '', \$photo_data);
        \$photo_data = str_replace('data:image/png;base64,', '', \$photo_data);
        \$photo_data = str_replace(' ', '+', \$photo_data);
        \$photo_binary = base64_decode(\$photo_data);
        
        if (\$photo_binary === false) {
            \$debug_info[] = \"Décodage base64 de la photo client échoué\";
            \$debug_info[] = \"Longueur des données: \" . strlen(\$photo_data);
        } else {
            \$debug_info[] = \"Décodage base64 réussi - Taille binaire: \" . strlen(\$photo_binary) . \" octets\";
            
            \$client_photo_name = 'client_' . time() . '_' . uniqid() . '.jpg';
            \$client_photo_path = \$upload_dir . \$client_photo_name;
            
            if (file_put_contents(\$client_photo_path, \$photo_binary) === false) {
                \$debug_info[] = \"Échec d'écriture du fichier photo client\";
            } else {
                \$debug_info[] = \"Photo du client enregistrée: \" . \$client_photo_name;
            }
        }
    } else {
        \$debug_info[] = \"Pas de données photo client disponibles pour l'enregistrement\";
    }\n\n    // Insérer l'enregistrement dans la base de données",
                $content
            );
        }
        
        // Sauvegarder les modifications
        file_put_contents($saveRachatPath, $content);
        
        echo '<div style="color: green; padding: 15px; background-color: #e6ffe6; border-radius: 5px; margin: 20px 0;">
            <h3>Code PHP de sauvegarde amélioré !</h3>
            <p>Les modifications suivantes ont été apportées :</p>
            <ul>
                <li>Ajout de code pour déboguer la photo client</li>
                <li>Amélioration du traitement de la photo</li>
                <li>Ajout de logs détaillés pour identifier les problèmes</li>
            </ul>
            <p>Une sauvegarde du fichier original a été créée.</p>
        </div>';
    } else {
        echo '<div style="color: blue; padding: 15px; background-color: #e6f7ff; border-radius: 5px; margin: 20px 0;">
            Le fichier save_rachat.php semble déjà contenir le code nécessaire pour traiter la photo du client.
        </div>';
    }
} else {
    echo '<div style="color: red; padding: 15px; background-color: #ffe6e6; border-radius: 5px; margin: 20px 0;">
        Le fichier <code>ajax/save_rachat.php</code> n\'a pas été trouvé.
    </div>';
}

echo '<div style="margin-top: 30px;">
    <h2>Instructions pour tester</h2>
    <ol>
        <li>Lancez <a href="add_client_photo_column.php" target="_blank">add_client_photo_column.php</a> pour ajouter la colonne en base de données</li>
        <li>Retournez à la <a href="index.php?page=rachat_appareils">page de gestion des rachats</a></li>
        <li>Essayez de créer un nouveau rachat et vérifiez que la photo du client est correctement capturée</li>
        <li>Consultez les logs pour voir les messages de débogage</li>
    </ol>
</div>';
?> 