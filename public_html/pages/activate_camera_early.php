<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Charger les paramètres de connexion si nécessaire
require_once __DIR__ . '/config/database.php';

echo '<h1>Activation anticipée de la caméra</h1>';

// Modifier le fichier JavaScript
$scriptPath = __DIR__ . '/pages/rachat_appareils.php';
if (file_exists($scriptPath)) {
    // Faire une sauvegarde
    $backupFile = $scriptPath . '.backup.' . date('YmdHis');
    $content = file_get_contents($scriptPath);
    file_put_contents($backupFile, $content);
    echo '<div style="color: blue; padding: 15px; background-color: #e6f7ff; border-radius: 5px; margin: 20px 0;">
        Sauvegarde du fichier original créée: <code>' . basename($backupFile) . '</code>
    </div>';
    
    // 1. Ajouter un event listener pour l'ouverture du modal
    $modalEventCode = '
// S\'assurer que la caméra est arrêtée lorsque le modal est fermé
document.getElementById(\'newRachatModal\').addEventListener(\'hidden.bs.modal\', function () {
    stopCamera();
    photoTaken = false;
});

// Activer la caméra dès l\'ouverture du modal
document.getElementById(\'newRachatModal\').addEventListener(\'shown.bs.modal\', function () {
    console.log("Modal ouvert, démarrage de la caméra...");
    startCamera();
});';
    
    // Remplacer l'événement de fermeture du modal existant
    if (strpos($content, 'addEventListener(\'hidden.bs.modal\'') !== false) {
        $content = preg_replace(
            '/\/\/ S\'assurer que la caméra est arrêtée lorsque le modal est fermé.*?}\);/s', 
            $modalEventCode,
            $content
        );
    } else {
        // Ajouter à la fin du script si l'événement n'existe pas
        $content = $content . $modalEventCode;
    }
    
    // 2. Modifier la fonction initSignaturePad pour capturer la photo au début de la signature
    $newInitSignaturePad = '
// Fonction d\'initialisation du pad de signature
function initSignaturePad() {
    const canvas = document.getElementById(\'signatureCanvas\');
    
    // Assurons-nous que le canvas a la bonne taille
    const container = canvas.parentElement;
    canvas.width = container.clientWidth - 20; // -20 pour le padding
    canvas.height = 200;
    
    signaturePad = new SignaturePad(canvas, {
        backgroundColor: \'rgba(255, 255, 255, 0)\',
        penColor: \'black\',
        minWidth: 1,
        maxWidth: 3
    });

    // Attacher des événements pour capturer la photo dès le début de la signature
    signaturePad.addEventListener("beginStroke", () => {
        console.log("Début de signature détecté, capture de la photo");
        capturePhoto();
    });
    
    console.log("Signature pad initialized");
    
    // Ajouter des événements de débogage
    canvas.addEventListener(\'mousedown\', (e) => {
        console.log(\'Canvas mousedown event triggered\');
    });
    
    canvas.addEventListener(\'touchstart\', (e) => {
        console.log(\'Canvas touchstart event triggered\');
    });
}';

    // Remplacer la fonction d'initialisation de signature
    $content = preg_replace(
        '/\/\/ Fonction d\'initialisation du pad de signature\s*function initSignaturePad\(\).*?}\s*}/s',
        $newInitSignaturePad,
        $content
    );
    
    // 3. Modifier la fonction startCamera pour ne pas capturer automatiquement
    $newStartCamera = '
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
            // Ne pas capturer automatiquement, attendre la signature
        };
        
        console.log("Camera started, waiting for signature to capture photo");
    } catch (err) {
        console.error("Erreur d\'accès à la caméra:", err);
    }
}';

    // Remplacer la fonction startCamera
    $content = preg_replace(
        '/\/\/ Fonction pour démarrer la caméra\s*async function startCamera\(\).*?}\s*}\s*}/s',
        $newStartCamera,
        $content
    );
    
    // Enregistrer les modifications
    file_put_contents($scriptPath, $content);
    
    echo '<div style="color: green; padding: 15px; background-color: #e6ffe6; border-radius: 5px; margin: 20px 0;">
        <h3>Modifications réussies!</h3>
        <p>Les changements suivants ont été appliqués :</p>
        <ul>
            <li><strong>La caméra s\'active automatiquement</strong> dès que le modal de rachat s\'ouvre</li>
            <li><strong>La photo est capturée</strong> exactement au moment où le client commence à signer</li>
            <li>Aucune modification n\'a été apportée au processus d\'enregistrement des données</li>
        </ul>
    </div>';
    
    echo '<div style="margin-top: 30px;">
        <h2>Que faire maintenant</h2>
        <ol>
            <li>Retournez à la <a href="index.php?page=rachat_appareils">page de gestion des rachats</a></li>
            <li>Cliquez sur "Nouveau Rachat" - la caméra devrait s\'activer immédiatement</li>
            <li>Lorsque le client commence à signer, sa photo sera capturée instantanément</li>
            <li>Terminez le formulaire comme d\'habitude</li>
        </ol>
        <p><strong>Note:</strong> Si vous rencontrez des problèmes, vous pouvez restaurer la version précédente en remplaçant le fichier actuel par la sauvegarde.</p>
    </div>';
} else {
    echo '<div style="color: red; padding: 15px; background-color: #ffe6e6; border-radius: 5px; margin: 20px 0;">
        Le fichier <code>pages/rachat_appareils.php</code> n\'a pas été trouvé dans le répertoire actuel.
    </div>';
}
?> 