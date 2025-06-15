<?php
// Définir la page actuelle pour le menu
$current_page = 'nouveau_rachat';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Récupérer les clients pour le formulaire
$clients = [];
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("SELECT id, nom, prenom, telephone FROM clients ORDER BY nom, prenom");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des clients: " . $e->getMessage(), "danger");
}

// Traitement du formulaire d'ajout de rachat d'appareil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $type_appareil = cleanInput($_POST['type_appareil']);
    $modele = cleanInput($_POST['modele']);
    $numero_serie = cleanInput($_POST['numero_serie']);
    $fonctionnel = isset($_POST['fonctionnel']) ? 1 : 0;
    $prix = (float)str_replace(',', '.', $_POST['prix']);
    $sin = cleanInput($_POST['sin']); // IMEI ou autre identifiant
    $signature_data = isset($_POST['signature_data']) ? $_POST['signature_data'] : '';
    $user_id = $_SESSION['user_id'];

    // Validation
    $errors = [];

    if ($client_id <= 0) {
        $errors[] = "Veuillez sélectionner un client.";
    }

    if (empty($type_appareil)) {
        $errors[] = "Le type d'appareil est obligatoire.";
    }

    if (empty($modele)) {
        $errors[] = "Le modèle de l'appareil est obligatoire.";
    }

    if ($prix <= 0) {
        $errors[] = "Le prix du rachat doit être supérieur à 0.";
    }
    
    if (empty($signature_data)) {
        $errors[] = "La signature du client est obligatoire.";
    }

    // Traitement des images
    $photo_identite = '';
    $photo_appareil = '';
    $client_photo = '';

    // Fonction pour traiter et sauvegarder une image
    function processImage($file_key, $error_message) {
        global $errors;
        
        if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = $error_message;
            return '';
        }
        
        $file = $_FILES[$file_key];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur lors du téléchargement de l'image: " . $file['error'];
            return '';
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Le format de l'image n'est pas valide. Formats acceptés: JPG, JPEG, PNG.";
            return '';
        }
        
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            $errors[] = "La taille de l'image est trop grande. Maximum: 5MB.";
            return '';
        }
        
        $upload_dir = 'uploads/rachats/' . date('Y/m/');
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
            $errors[] = "Erreur lors de la création du répertoire de téléchargement.";
            return '';
        }
        
        $filename = uniqid() . '_' . basename($file['name']);
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            return $upload_path;
        } else {
            $errors[] = "Erreur lors de l'enregistrement de l'image.";
            return '';
        }
    }

    // Traiter les images si aucune erreur jusqu'ici
    if (empty($errors)) {
        $photo_identite = processImage('photo_identite', "La photo de la pièce d'identité est obligatoire.");
        $photo_appareil = processImage('photo_appareil', "La photo de l'appareil est obligatoire.");
        
        // Photo du client (optionnelle)
        if (isset($_FILES['client_photo']) && $_FILES['client_photo']['error'] === UPLOAD_ERR_OK) {
            $client_photo = processImage('client_photo', "");
        }

        // Traiter la signature (Base64)
        $signature_file = '';
        if (!empty($signature_data)) {
            $upload_dir = 'uploads/rachats/signatures/' . date('Y/m/');
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
                $errors[] = "Erreur lors de la création du répertoire pour la signature.";
            } else {
                $signature_file = $upload_dir . uniqid() . '.png';
                
                // Décoder l'image Base64
                $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
                $signature_data = str_replace(' ', '+', $signature_data);
                $signature_data = base64_decode($signature_data);
                
                if (file_put_contents($signature_file, $signature_data) === false) {
                    $errors[] = "Erreur lors de l'enregistrement de la signature.";
                    $signature_file = '';
                }
            }
        }
    }

    // Enregistrer le rachat si aucune erreur
    if (empty($errors)) {
        try {
            // Insérer le rachat dans la base de données
            $stmt = $shop_pdo->prepare("
                INSERT INTO rachat_appareils (
                    client_id, type_appareil, modele, numero_serie, sin,
                    fonctionnel, prix, photo_identite, photo_appareil,
                    client_photo, signature, date_rachat, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $client_id, $type_appareil, $modele, $numero_serie, $sin,
                $fonctionnel, $prix, $photo_identite, $photo_appareil,
                $client_photo, $signature_file, $user_id
            ]);
            
            $rachat_id = $shop_pdo->lastInsertId();
            
            set_message("Le rachat a été enregistré avec succès.", "success");
            header('Location: index.php?page=rachat_appareils');
            exit();
        } catch (PDOException $e) {
            set_message("Erreur lors de l'enregistrement du rachat: " . $e->getMessage(), "danger");
        }
    } else {
        // Afficher les erreurs
        foreach ($errors as $error) {
            set_message($error, "danger");
        }
    }
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="mb-0"><i class="fas fa-exchange-alt me-2 text-success"></i>Nouveau rachat d'appareil</h1>
        <a href="index.php?page=rachat_appareils" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour aux rachats
        </a>
    </div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Formulaire de rachat</h5>
        </div>
        <div class="card-body">
            <form method="post" action="" id="rachat-form" enctype="multipart/form-data">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Informations client</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="client_id" class="form-label fw-bold">Client *</label>
                                    <select class="form-select form-select-lg" id="client_id" name="client_id" required>
                                        <option value="">- Sélectionner un client -</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value="<?php echo $client['id']; ?>" <?php echo (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom'] . ' - ' . $client['telephone']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>Sélectionnez le client qui vend l'appareil
                                        </div>
                                        <a href="index.php?page=ajouter_client" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus-circle me-1"></i>Nouveau client
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="photo_identite" class="form-label fw-bold">Photo de la pièce d'identité *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="file" class="form-control" id="photo_identite" name="photo_identite" accept="image/*" required>
                                    </div>
                                    <div class="form-text">Prendre une photo recto/verso de la pièce d'identité</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="client_photo" class="form-label fw-bold">Photo du client (optionnel)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-camera"></i></span>
                                        <input type="file" class="form-control" id="client_photo" name="client_photo" accept="image/*">
                                    </div>
                                    <div class="form-text">Photo du client avec son appareil (recommandé)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-info mb-3">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Informations de l'appareil</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="type_appareil" class="form-label fw-bold">Type d'appareil *</label>
                                    <select class="form-select" id="type_appareil" name="type_appareil" required>
                                        <option value="">- Sélectionner un type -</option>
                                        <option value="Smartphone" <?php echo (isset($_POST['type_appareil']) && $_POST['type_appareil'] === 'Smartphone') ? 'selected' : ''; ?>>Smartphone</option>
                                        <option value="Tablette" <?php echo (isset($_POST['type_appareil']) && $_POST['type_appareil'] === 'Tablette') ? 'selected' : ''; ?>>Tablette</option>
                                        <option value="Ordinateur portable" <?php echo (isset($_POST['type_appareil']) && $_POST['type_appareil'] === 'Ordinateur portable') ? 'selected' : ''; ?>>Ordinateur portable</option>
                                        <option value="Montre connectée" <?php echo (isset($_POST['type_appareil']) && $_POST['type_appareil'] === 'Montre connectée') ? 'selected' : ''; ?>>Montre connectée</option>
                                        <option value="Console de jeux" <?php echo (isset($_POST['type_appareil']) && $_POST['type_appareil'] === 'Console de jeux') ? 'selected' : ''; ?>>Console de jeux</option>
                                        <option value="Autre" <?php echo (isset($_POST['type_appareil']) && $_POST['type_appareil'] === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="modele" class="form-label fw-bold">Modèle *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                                        <input type="text" class="form-control" id="modele" name="modele" value="<?php echo isset($_POST['modele']) ? htmlspecialchars($_POST['modele']) : ''; ?>" required>
                                    </div>
                                    <div class="form-text">Exemple: iPhone 12 Pro, Samsung Galaxy S21, MacBook Pro 2019...</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="numero_serie" class="form-label fw-bold">Numéro de série</label>
                                            <input type="text" class="form-control" id="numero_serie" name="numero_serie" value="<?php echo isset($_POST['numero_serie']) ? htmlspecialchars($_POST['numero_serie']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="sin" class="form-label fw-bold">IMEI / SN</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="sin" name="sin" value="<?php echo isset($_POST['sin']) ? htmlspecialchars($_POST['sin']) : ''; ?>">
                                                <button class="btn btn-outline-primary" type="button" id="scan-imei-button">
                                                    <i class="fas fa-camera"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Pour les smartphones: IMEI, pour les autres appareils: numéro d'identification</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="fonctionnel" name="fonctionnel" <?php echo (isset($_POST['fonctionnel'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="fonctionnel">L'appareil est fonctionnel</label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="photo_appareil" class="form-label fw-bold">Photo de l'appareil *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-camera"></i></span>
                                        <input type="file" class="form-control" id="photo_appareil" name="photo_appareil" accept="image/*" required>
                                    </div>
                                    <div class="form-text">Prendre une photo de l'appareil montrant son état</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-success mb-3">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-euro-sign me-2"></i>Détails du rachat</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="prix" class="form-label fw-bold">Prix du rachat *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                        <input type="text" class="form-control form-control-lg" id="prix" name="prix" value="<?php echo isset($_POST['prix']) ? htmlspecialchars($_POST['prix']) : '0.00'; ?>" required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="etat" class="form-label fw-bold">État de l'appareil</label>
                                    <select class="form-select" id="etat" name="etat">
                                        <option value="Excellent" <?php echo (isset($_POST['etat']) && $_POST['etat'] === 'Excellent') ? 'selected' : ''; ?>>Excellent - Comme neuf</option>
                                        <option value="Bon" <?php echo (isset($_POST['etat']) && $_POST['etat'] === 'Bon') ? 'selected' : ''; ?>>Bon - Quelques traces d'usure</option>
                                        <option value="Moyen" <?php echo (isset($_POST['etat']) && $_POST['etat'] === 'Moyen') ? 'selected' : ''; ?>>Moyen - Usure visible</option>
                                        <option value="Mauvais" <?php echo (isset($_POST['etat']) && $_POST['etat'] === 'Mauvais') ? 'selected' : ''; ?>>Mauvais - Dommages importants</option>
                                        <option value="Pièces" <?php echo (isset($_POST['etat']) && $_POST['etat'] === 'Pièces') ? 'selected' : ''; ?>>Pour pièces uniquement</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="commentaires" class="form-label fw-bold">Commentaires</label>
                                    <textarea class="form-control" id="commentaires" name="commentaires" rows="3" placeholder="Détails sur l'état, accessoires inclus, etc..."><?php echo isset($_POST['commentaires']) ? htmlspecialchars($_POST['commentaires']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-danger mb-3">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="fas fa-signature me-2"></i>Signature du client *</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="border rounded p-3 bg-light">
                                        <canvas id="signature-pad" class="signature-pad w-100" height="200"></canvas>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-signature">
                                            <i class="fas fa-eraser me-1"></i>Effacer
                                        </button>
                                        <div class="form-text">Veuillez signer pour confirmer le rachat</div>
                                    </div>
                                    <input type="hidden" name="signature_data" id="signature_data">
                                </div>
                                
                                <div class="alert alert-warning">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="conditions" name="conditions" required>
                                        <label class="form-check-label" for="conditions">
                                            <strong>Je certifie que :</strong>
                                            <ul class="mt-1 mb-0">
                                                <li>Je suis le propriétaire légitime de cet appareil</li>
                                                <li>Cet appareil n'est pas volé</li>
                                                <li>Je renonce à tous droits sur cet appareil après le rachat</li>
                                            </ul>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=rachat_appareils'">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="submit-btn">
                        <i class="fas fa-save me-2"></i>Enregistrer le rachat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Scanner IMEI -->
<div class="modal fade" id="scannerIMEIModal" tabindex="-1" aria-labelledby="scannerIMEIModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="scannerIMEIModalLabel"><i class="fas fa-barcode me-2"></i>Scanner IMEI</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="position-relative">
                    <video id="imei-scanner" class="w-100 border rounded"></video>
                    <div id="imei-scan-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
                        <div class="scanner-region" style="width: 250px; height: 150px; border: 2px solid #3498db; position: relative;">
                            <div class="scanner-line" style="width: 100%; height: 2px; background-color: #3498db; position: absolute; top: 50%; animation: scan 2s infinite ease-in-out;"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <span id="imei-scanner-status" class="text-muted">Initialisation de la caméra...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes scan {
        0% { top: 0; }
        50% { top: 100%; }
        100% { top: 0; }
    }
    
    .signature-pad {
        background-color: white;
        border: 1px solid #e3e3e3;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le pad de signature
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0.9)',
        penColor: 'rgb(0, 0, 128)'
    });
    
    // Ajuster la taille du canvas lors du redimensionnement
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear(); // Nécessaire pour effacer le contenu après le redimensionnement
    }
    
    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();
    
    // Bouton pour effacer la signature
    document.getElementById('clear-signature').addEventListener('click', function() {
        signaturePad.clear();
    });
    
    // Variables pour le scanner d'IMEI
    const scanIMEIButton = document.getElementById('scan-imei-button');
    const scannerIMEIModal = new bootstrap.Modal(document.getElementById('scannerIMEIModal'));
    const imeiScannerStatus = document.getElementById('imei-scanner-status');
    const sinField = document.getElementById('sin');
    let isScanning = false;
    
    // Ouvrir le modal de scan d'IMEI
    if (scanIMEIButton) {
        scanIMEIButton.addEventListener('click', function() {
            scannerIMEIModal.show();
            
            // Initialiser le scanner après l'ouverture du modal
            document.getElementById('scannerIMEIModal').addEventListener('shown.bs.modal', function() {
                initIMEIScanner();
            });
            
            // Arrêter le scanner à la fermeture du modal
            document.getElementById('scannerIMEIModal').addEventListener('hidden.bs.modal', function() {
                if (Quagga && Quagga.isRunning()) {
                    Quagga.stop();
                }
            });
        });
    }
    
    // Initialiser le scanner d'IMEI
    function initIMEIScanner() {
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.getElementById('imei-scanner'),
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: "environment"
                },
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
            numOfWorkers: 4,
            frequency: 10,
            decoder: {
                readers: ["code_128_reader", "code_39_reader", "ean_reader"]
            },
            locate: true
        }, function(err) {
            if (err) {
                console.error(err);
                imeiScannerStatus.textContent = "Erreur d'initialisation de la caméra";
                return;
            }
            Quagga.start();
            isScanning = true;
            imeiScannerStatus.textContent = "Caméra activée, placez l'IMEI dans la zone de scan";
        });
        
        Quagga.onDetected(function(result) {
            if (!isScanning) return;
            
            const code = result.codeResult.code;
            if (code) {
                // Pause temporaire pour éviter les lectures multiples
                isScanning = false;
                imeiScannerStatus.textContent = `Code détecté: ${code}`;
                
                // Jouer un bip sonore et vibrer le téléphone si possible
                if (navigator.vibrate) {
                    navigator.vibrate(100);
                }
                
                // Remplir le champ IMEI
                sinField.value = code;
                
                // Fermer le modal
                scannerIMEIModal.hide();
            }
        });
    }
    
    // Validation du formulaire
    const form = document.getElementById('rachat-form');
    form.addEventListener('submit', function(event) {
        // Vérifier la signature
        if (signaturePad.isEmpty()) {
            event.preventDefault();
            alert('Veuillez signer le formulaire.');
            return;
        }
        
        // Sauvegarder l'image de la signature
        document.getElementById('signature_data').value = signaturePad.toDataURL();
        
        // Vérifier les photos
        const photoIdentite = document.getElementById('photo_identite');
        const photoAppareil = document.getElementById('photo_appareil');
        
        if (!photoIdentite.files || photoIdentite.files.length === 0) {
            event.preventDefault();
            alert('Veuillez prendre une photo de la pièce d\'identité.');
            return;
        }
        
        if (!photoAppareil.files || photoAppareil.files.length === 0) {
            event.preventDefault();
            alert('Veuillez prendre une photo de l\'appareil.');
            return;
        }
        
        // Vérifier les conditions
        const conditions = document.getElementById('conditions');
        if (!conditions.checked) {
            event.preventDefault();
            alert('Veuillez accepter les conditions.');
            return;
        }
        
        // Animation pendant la soumission
        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Traitement en cours...';
    });
    
    // Formater le prix pour n'accepter que les nombres
    const prixInput = document.getElementById('prix');
    prixInput.addEventListener('input', function() {
        let value = this.value;
        // Remplacer les virgules par des points et supprimer les caractères non numériques sauf le point
        value = value.replace(/,/g, '.').replace(/[^\d.]/g, '');
        
        // S'assurer qu'il n'y a qu'un seul point décimal
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        this.value = value;
    });
    
    // Prévisualisation des images
    const imageInputs = document.querySelectorAll('input[type="file"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Supprimer la prévisualisation précédente
            const previewId = `${this.id}-preview`;
            let preview = document.getElementById(previewId);
            
            if (preview) {
                preview.remove();
            }
            
            // Créer une nouvelle prévisualisation si un fichier est sélectionné
            if (this.files && this.files[0]) {
                preview = document.createElement('div');
                preview.id = previewId;
                preview.className = 'mt-2 text-center';
                
                const img = document.createElement('img');
                img.src = URL.createObjectURL(this.files[0]);
                img.className = 'img-thumbnail';
                img.style.maxHeight = '150px';
                
                preview.appendChild(img);
                this.parentNode.appendChild(preview);
            }
        });
    });
    
    // Activer les caméras lors du clic sur les champs de fichier
    const photoFields = ['photo_identite', 'photo_appareil', 'client_photo'];
    photoFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('click', function() {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    // Activer la caméra par défaut
                    field.setAttribute('capture', 'environment');
                }
            });
        }
    });
});
</script>