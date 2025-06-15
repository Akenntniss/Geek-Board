<?php
// Définir la page actuelle pour le menu
$current_page = 'scanner';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Traitement AJAX pour les scans de codes-barres
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Vérifier si c'est une requête AJAX
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    try {
        if ($_POST['action'] === 'find_product') {
            // Rechercher un produit par code-barres
            $barcode = cleanInput($_POST['barcode']);
            
            // Recherche dans la table stock
            $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM stock WHERE barcode = ?");
            $stmt->execute([$barcode]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                // Si produit non trouvé dans stock, chercher dans produits
                $stmt = $shop_pdo->prepare("SELECT * FROM produits WHERE reference = ? OR barcode = ?");
                $stmt->execute([$barcode, $barcode]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    // Adapter les champs pour correspondre au format attendu
                    $product['barcode'] = $product['reference'];
                    $product['name'] = $product['nom'];
                    $product['category'] = '';
                    if (isset($product['categorie_id'])) {
                        // Récupérer le nom de la catégorie
                        $stmt = $shop_pdo->prepare("SELECT nom FROM categories WHERE id = ?");
                        $stmt->execute([$product['categorie_id']]);
                        $category = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($category) {
                            $product['category'] = $category['nom'];
                        }
                    }
                }
            }
            
            if ($product) {
                echo json_encode(['success' => true, 'product' => $product]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
            }
        } elseif ($_POST['action'] === 'add_quantity') {
            // Ajouter une quantité à un produit existant
            $barcode = cleanInput($_POST['barcode']);
            $quantity = (int)$_POST['quantity'];
            $user_id = $_SESSION['user_id'];
            $motif = cleanInput($_POST['motif'] ?? 'Ajout par scanner');
            $is_temporaire = isset($_POST['is_temporaire']) && $_POST['is_temporaire'] === '1';
            
            // Vérifier si le produit existe
            $stmt = $shop_pdo->prepare("SELECT id, quantity FROM stock WHERE barcode = ?");
            $stmt->execute([$barcode]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // Commencer une transaction
                $shop_pdo->beginTransaction();
                
                try {
                    // Mettre à jour la quantité
                    $newQuantity = $product['quantity'] + $quantity;
                    $stmt = $shop_pdo->prepare("UPDATE stock SET quantity = ?, date_updated = NOW(), status = ? WHERE barcode = ?");
                    $status = $is_temporaire ? 'temporaire' : 'normal';
                    $stmt->execute([$newQuantity, $status, $barcode]);
                    
                    // Enregistrer le mouvement de stock
                    $stmt = $shop_pdo->prepare("INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, date_mouvement, motif, user_id) VALUES (?, 'entree', ?, NOW(), ?, ?)");
                    $motif_complet = $is_temporaire ? $motif . ' (Produit temporaire)' : $motif;
                    $stmt->execute([$product['id'], $quantity, $motif_complet, $user_id]);
                    
                    $shop_pdo->commit();
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Quantité ajoutée avec succès', 
                        'new_quantity' => $newQuantity,
                        'status' => $status
                    ]);
                } catch (Exception $e) {
                    $shop_pdo->rollBack();
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Erreur lors de l\'ajout de la quantité: ' . $e->getMessage()
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
            }
        } elseif ($_POST['action'] === 'remove_quantity') {
            // Retirer une quantité d'un produit existant
            $barcode = cleanInput($_POST['barcode']);
            $quantity = (int)$_POST['quantity'];
            $user_id = $_SESSION['user_id'];
            $motif = cleanInput($_POST['motif'] ?? 'Retrait par scanner');
            
            // Vérifier si le produit existe
            $stmt = $shop_pdo->prepare("SELECT id, quantity FROM stock WHERE barcode = ?");
            $stmt->execute([$barcode]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // Vérifier si la quantité est suffisante
                if ($product['quantity'] < $quantity) {
                    echo json_encode(['success' => false, 'message' => 'Quantité insuffisante en stock']);
                    exit();
                }
                
                // Mettre à jour la quantité
                $newQuantity = $product['quantity'] - $quantity;
                $stmt = $shop_pdo->prepare("UPDATE stock SET quantity = ?, date_updated = NOW() WHERE barcode = ?");
                $stmt->execute([$newQuantity, $barcode]);
                
                // Enregistrer le mouvement de stock
                $stmt = $shop_pdo->prepare("INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, date_mouvement, motif, user_id) VALUES (?, 'sortie', ?, NOW(), ?, ?)");
                $stmt->execute([$product['id'], $quantity, $motif, $user_id]);
                
                echo json_encode(['success' => true, 'message' => 'Quantité retirée avec succès', 'new_quantity' => $newQuantity]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
            }
        } elseif ($_POST['action'] === 'get_recent_scans') {
            // Récupérer l'historique des derniers scans
            $stmt = $shop_pdo->prepare("
                SELECT ms.*, s.barcode, s.name, u.nom as user_nom, u.prenom as user_prenom, 
                       DATE_FORMAT(ms.date_mouvement, '%d/%m/%Y %H:%i') as date_formattee
                FROM mouvements_stock ms
                JOIN stock s ON ms.produit_id = s.id
                JOIN utilisateurs u ON ms.user_id = u.id
                WHERE ms.date_mouvement > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY ms.date_mouvement DESC
                LIMIT 15
            ");
            $stmt->execute();
            $recent_scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'scans' => $recent_scans]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
    
    if ($is_ajax) {
        exit();
    }
}

// Récupérer l'historique des derniers scans
$recent_scans = [];
try {
    $stmt = $shop_pdo->prepare("
        SELECT ms.*, s.barcode, s.name, u.nom as user_nom, u.prenom as user_prenom
        FROM mouvements_stock ms
        JOIN stock s ON ms.produit_id = s.id
        JOIN utilisateurs u ON ms.user_id = u.id
        WHERE ms.date_mouvement > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY ms.date_mouvement DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération de l'historique des scans: " . $e->getMessage(), "danger");
}
?>

<div class="container">
    <h1 class="my-4">Scanner de codes-barres</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Scanner un produit</h5>
                    <div>
                        <button id="startScanButton" class="btn btn-primary">
                            <i class="fas fa-camera me-2"></i>Activer la caméra
                        </button>
                        <button id="pauseScanButton" class="btn btn-warning d-none">
                            <i class="fas fa-pause me-2"></i>Pause
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div id="scanner-container" class="mb-3 d-none">
                            <div class="position-relative">
                                <video id="scanner" class="w-100 border rounded" style="max-height: 300px;"></video>
                                <div id="scan-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
                                    <div class="scanner-region" style="width: 250px; height: 150px; border: 2px solid #3498db; position: relative;">
                                        <div class="scanner-line" style="width: 100%; height: 2px; background-color: #3498db; position: absolute; top: 50%; animation: scan 2s infinite ease-in-out;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 text-center">
                                <span id="scanner-status" class="text-muted">Placez le code-barres dans la zone de scan</span>
                            </div>
                        </div>
                        
                        <div class="input-group mb-3">
                            <input type="text" id="barcode-input" class="form-control" placeholder="Scannez ou entrez manuellement un code-barres">
                            <button class="btn btn-outline-primary" type="button" id="search-button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="product-result" class="d-none">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 id="product-name" class="card-title">Nom du produit</h5>
                                <p id="product-barcode" class="text-muted">Code-barres: </p>
                                <p id="product-category" class="text-muted">Catégorie: </p>
                                <p><strong>Quantité en stock: </strong><span id="product-quantity" class="badge bg-primary">0</span></p>
                                
                                <div class="d-flex mt-3">
                                    <div class="me-2">
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary" type="button" id="quantity-minus">-</button>
                                            <input type="number" id="quantity-input" class="form-control text-center" value="1" min="1">
                                            <button class="btn btn-outline-secondary" type="button" id="quantity-plus">+</button>
                                        </div>
                                    </div>
                                    <button id="add-quantity-button" class="btn btn-success me-2">
                                        <i class="fas fa-plus-circle me-1"></i>Ajouter
                                    </button>
                                    <button id="remove-quantity-button" class="btn btn-danger">
                                        <i class="fas fa-minus-circle me-1"></i>Retirer
                                    </button>
                                </div>
                                
                                <div class="mt-3">
                                    <input type="text" id="motif-input" class="form-control" placeholder="Motif (optionnel)">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="no-product-result" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i> Produit non trouvé. 
                        <a href="index.php?page=ajouter_scan" class="alert-link">Ajouter un nouveau produit</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Activité récente</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($recent_scans)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Aucun scan récent</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_scans as $scan): ?>
                                <div class="list-group-item py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($scan['name']); ?></h6>
                                            <p class="text-muted small mb-0">
                                                Code: <?php echo htmlspecialchars($scan['barcode']); ?> 
                                                | <?php echo $scan['type_mouvement'] === 'entree' ? '<span class="text-success">Entrée</span>' : '<span class="text-danger">Sortie</span>'; ?> 
                                                | Qté: <?php echo (int)$scan['quantite']; ?>
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <?php echo htmlspecialchars($scan['motif']); ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($scan['date_mouvement'])); ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($scan['user_prenom'] . ' ' . $scan['user_nom']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="index.php?page=inventaire" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-history me-1"></i>Voir tout l'historique
                    </a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-sm-6">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <i class="fas fa-boxes fa-3x text-primary mb-3"></i>
                            <h5>Ajouter un produit</h5>
                            <p class="text-muted">Créer une nouvelle entrée dans l'inventaire</p>
                            <a href="index.php?page=ajouter_scan" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i>Nouveau produit
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <i class="fas fa-search fa-3x text-success mb-3"></i>
                            <h5>Recherche avancée</h5>
                            <p class="text-muted">Recherche détaillée dans l'inventaire</p>
                            <a href="index.php?page=inventaire" class="btn btn-success">
                                <i class="fas fa-search me-1"></i>Rechercher
                            </a>
                        </div>
                    </div>
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
</style>

<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const startScanButton = document.getElementById('startScanButton');
    const pauseScanButton = document.getElementById('pauseScanButton');
    const scannerContainer = document.getElementById('scanner-container');
    const scannerStatus = document.getElementById('scanner-status');
    const barcodeInput = document.getElementById('barcode-input');
    const searchButton = document.getElementById('search-button');
    const productResult = document.getElementById('product-result');
    const noProductResult = document.getElementById('no-product-result');
    const productName = document.getElementById('product-name');
    const productBarcode = document.getElementById('product-barcode');
    const productCategory = document.getElementById('product-category');
    const productQuantity = document.getElementById('product-quantity');
    const quantityInput = document.getElementById('quantity-input');
    const quantityMinus = document.getElementById('quantity-minus');
    const quantityPlus = document.getElementById('quantity-plus');
    const addQuantityButton = document.getElementById('add-quantity-button');
    const removeQuantityButton = document.getElementById('remove-quantity-button');
    const motifInput = document.getElementById('motif-input');
    
    // Créer un élément de logs visible sur la page
    const logContainer = document.createElement('div');
    logContainer.id = 'debug-log-container';
    logContainer.style.cssText = 'position: fixed; bottom: 10px; left: 10px; right: 10px; max-height: 200px; overflow-y: auto; background-color: rgba(0,0,0,0.7); color: #fff; padding: 10px; font-family: monospace; font-size: 12px; z-index: 9999; border-radius: 5px;';
    document.body.appendChild(logContainer);
    
    // Fonction pour ajouter des logs
    function addLog(message, type = 'info') {
        const logEntry = document.createElement('div');
        const timestamp = new Date().toLocaleTimeString();
        let color = '#fff';
        
        switch(type) {
            case 'error':
                color = '#ff5555';
                break;
            case 'success':
                color = '#55ff55';
                break;
            case 'warning':
                color = '#ffff55';
                break;
        }
        
        logEntry.style.cssText = `color: ${color}; margin-bottom: 4px;`;
        logEntry.textContent = `[${timestamp}] ${message}`;
        logContainer.appendChild(logEntry);
        logContainer.scrollTop = logContainer.scrollHeight;
        
        // Aussi dans la console pour le développement
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
    
    // Vérifier si Quagga est chargé
    if (typeof Quagga === 'undefined') {
        addLog('ERREUR: La bibliothèque Quagga n\'est pas chargée', 'error');
        scannerStatus.textContent = "Erreur: Bibliothèque de scan non chargée";
        return;
    } else {
        addLog('Bibliothèque Quagga chargée correctement', 'success');
    }
    
    let currentBarcode = '';
    let isScanning = false;
    
    // Vérifier la compatibilité du navigateur avec les API nécessaires
    addLog('Vérification de la compatibilité du navigateur...');
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        addLog('ERREUR: Ce navigateur ne supporte pas mediaDevices.getUserMedia', 'error');
    } else {
        addLog('API MediaDevices supportée', 'success');
    }
    
    // Initialiser le scanner
    function initScanner() {
        addLog('Démarrage de initScanner()');
        
        // Vérifier si l'élément vidéo existe
        const scannerElement = document.getElementById('scanner');
        if (!scannerElement) {
            addLog('ERREUR: Élément scanner non trouvé dans le DOM', 'error');
            return;
        }
        addLog('Élément scanner trouvé', 'success');
        
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: scannerElement,
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
                readers: ["ean_reader", "ean_8_reader", "code_128_reader", "code_39_reader", "code_93_reader", "upc_reader", "upc_e_reader"]
            },
            locate: true
        }, function(err) {
            if (err) {
                addLog(`ERREUR dans Quagga.init: ${err.message || 'Erreur inconnue'}`, 'error');
                addLog(`Type d'erreur: ${err.name || 'Non spécifié'}`, 'error');
                if (err.message && err.message.includes('Permission')) {
                    addLog('Problème de permission caméra - Vérifiez les autorisations', 'error');
                }
                if (err.message && err.message.includes('constraint')) {
                    addLog('Problème de contraintes vidéo - La caméra ne supporte pas les paramètres', 'error');
                }
                scannerStatus.textContent = "Erreur d'initialisation de la caméra";
                return;
            }
            
            addLog('Quagga.init réussi, démarrage de Quagga.start()', 'success');
            
            try {
                Quagga.start();
                isScanning = true;
                scannerStatus.textContent = "Caméra activée, placez un code-barres dans la zone de scan";
                addLog('Quagga.start() terminé, scanner actif', 'success');
            } catch (startErr) {
                addLog(`ERREUR lors du démarrage de Quagga: ${startErr.message}`, 'error');
            }
        });
        
        Quagga.onDetected(function(result) {
            if (!isScanning) return;
            
            const code = result.codeResult.code;
            addLog(`Code détecté: ${code}`);
            if (code) {
                // Pause temporaire pour éviter les lectures multiples
                isScanning = false;
                scannerStatus.textContent = `Code détecté: ${code}`;
                
                // Remplir le champ de saisie
                barcodeInput.value = code;
                
                // Rechercher le produit
                searchProduct(code);
                
                // Reprendre après un court délai
                setTimeout(() => {
                    if (Quagga.isRunning()) {
                        isScanning = true;
                        scannerStatus.textContent = "Caméra activée, placez un code-barres dans la zone de scan";
                    }
                }, 2000);
            }
        });
    }
    
    // Démarrer le scanner
    startScanButton.addEventListener('click', function() {
        addLog('Bouton "Démarrer scanner" cliqué');
        
        // Afficher l'état actuel
        let quaggaStatus = '';
        try {
            quaggaStatus = Quagga.isRunning() ? 'En cours' : 'Arrêté';
        } catch (e) {
            quaggaStatus = `Erreur: ${e.message}`;
        }
        addLog(`État actuel de Quagga: ${quaggaStatus}`);
        
        scannerContainer.classList.remove('d-none');
        startScanButton.classList.add('d-none');
        pauseScanButton.classList.remove('d-none');
        
        // Vérifier si la caméra est supportée
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            addLog('ERREUR: Le navigateur ne supporte pas MediaDevices.getUserMedia', 'error');
            scannerStatus.textContent = "Erreur: Votre navigateur ne supporte pas l'accès à la caméra";
            return;
        }
        
        addLog('Test de disponibilité de la caméra...');
        navigator.mediaDevices.enumerateDevices()
            .then(devices => {
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                if (videoDevices.length === 0) {
                    addLog('ERREUR: Aucune caméra détectée sur l\'appareil', 'error');
                    scannerStatus.textContent = "Erreur: Aucune caméra détectée";
                    return Promise.reject(new Error('Aucune caméra disponible'));
                }
                
                addLog(`${videoDevices.length} caméra(s) détectée(s):`, 'success');
                videoDevices.forEach((device, index) => {
                    addLog(`Caméra ${index+1}: ${device.label || 'Caméra sans nom'}`);
                });
                
                return navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment' 
                    } 
                });
            })
            .then(function(stream) {
                addLog('Accès à la caméra réussi, flux obtenu', 'success');
                
                // Essayer d'afficher le flux brièvement pour confirmer l'accès
                const testVideo = document.createElement('video');
                testVideo.srcObject = stream;
                testVideo.muted = true;
                testVideo.onloadedmetadata = function(e) {
                    addLog(`Métadonnées vidéo chargées: ${testVideo.videoWidth}x${testVideo.videoHeight}`);
                    // Arrêter le flux de test
                    stream.getTracks().forEach(track => {
                        addLog(`Arrêt de la piste: ${track.kind} (${track.label || 'sans nom'})`);
                        track.stop();
                    });
                    
                    addLog('Initialisation du scanner Quagga...');
                    initScanner();
                };
                
                testVideo.onerror = function(e) {
                    addLog(`ERREUR lors du test vidéo: ${e}`, 'error');
                    stream.getTracks().forEach(track => track.stop());
                };
                
                try {
                    testVideo.play();
                } catch (e) {
                    addLog(`ERREUR lors de la lecture du flux test: ${e.message}`, 'error');
                    stream.getTracks().forEach(track => track.stop());
                }
            })
            .catch(function(err) {
                addLog(`ERREUR lors de l'accès à la caméra: ${err.name || 'Erreur inconnue'}`, 'error');
                addLog(`Message: ${err.message || 'Pas de message d\'erreur'}`, 'error');
                addLog(`Stack: ${err.stack || 'Pas de stack disponible'}`, 'error');
                
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    addLog('Autorisation de la caméra refusée par l\'utilisateur ou les paramètres du système', 'error');
                    scannerStatus.textContent = "Erreur: Permission caméra refusée";
                } else if (err.name === 'NotFoundError') {
                    addLog('Aucun périphérique vidéo connecté ou activé', 'error');
                    scannerStatus.textContent = "Erreur: Aucune caméra trouvée";
                } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                    addLog('La caméra est déjà utilisée par une autre application', 'error');
                    scannerStatus.textContent = "Erreur: Caméra déjà utilisée";
                } else if (err.name === 'OverconstrainedError') {
                    addLog('Les contraintes vidéo demandées ne sont pas supportées', 'error');
                    scannerStatus.textContent = "Erreur: Contraintes vidéo non supportées";
                } else {
                    scannerStatus.textContent = `Erreur d'accès à la caméra: ${err.name || 'Erreur inconnue'}`;
                }
            });
    });
    
    // Pause/Reprise du scanner
    pauseScanButton.addEventListener('click', function() {
        addLog('Bouton "Pause/Reprendre" cliqué');
        if (isScanning) {
            addLog('Mise en pause du scanner');
            Quagga.stop();
            isScanning = false;
            pauseScanButton.innerHTML = '<i class="fas fa-play me-2"></i>Reprendre';
            scannerStatus.textContent = "Scanner en pause";
        } else {
            addLog('Reprise du scanner');
            Quagga.start();
            isScanning = true;
            pauseScanButton.innerHTML = '<i class="fas fa-pause me-2"></i>Pause';
            scannerStatus.textContent = "Caméra activée, placez un code-barres dans la zone de scan";
        }
        addLog(`État actuel du scanner: ${Quagga.isRunning() ? 'En cours' : 'Arrêté'}`);
    });
    
    // Rechercher un produit par code-barres
    function searchProduct(barcode) {
        currentBarcode = barcode;
        
        // Requête AJAX pour rechercher le produit
        fetch('index.php?page=scanner', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'find_product',
                'barcode': barcode
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher les détails du produit
                productName.textContent = data.product.name;
                productBarcode.textContent = 'Code-barres: ' + data.product.barcode;
                productCategory.textContent = 'Catégorie: ' + (data.product.category || 'Non catégorisé');
                productQuantity.textContent = data.product.quantity;
                
                productResult.classList.remove('d-none');
                noProductResult.classList.add('d-none');
            } else {
                // Produit non trouvé
                productResult.classList.add('d-none');
                noProductResult.classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la recherche du produit');
        });
    }
    
    // Recherche manuelle
    searchButton.addEventListener('click', function() {
        const barcode = barcodeInput.value.trim();
        if (barcode) {
            searchProduct(barcode);
        }
    });
    
    // Entrée dans le champ de saisie du code-barres
    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const barcode = barcodeInput.value.trim();
            if (barcode) {
                searchProduct(barcode);
            }
        }
    });
    
    // Gestion des boutons de quantité
    quantityMinus.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1) {
            quantityInput.value = value - 1;
        }
    });
    
    quantityPlus.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        quantityInput.value = value + 1;
    });
    
    // Ajouter une quantité
    addQuantityButton.addEventListener('click', function() {
        if (!currentBarcode) return;
        
        const quantity = parseInt(quantityInput.value);
        if (isNaN(quantity) || quantity <= 0) {
            alert('Veuillez entrer une quantité valide');
            return;
        }
        
        const motif = motifInput.value.trim();
        
        // Requête AJAX pour ajouter la quantité
        fetch('index.php?page=scanner', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'add_quantity',
                'barcode': currentBarcode,
                'quantity': quantity,
                'motif': motif || 'Ajout par scanner'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'affichage
                productQuantity.textContent = data.new_quantity;
                
                // Réinitialiser les champs
                quantityInput.value = 1;
                motifInput.value = '';
                
                // Afficher un message de succès
                alert(`${quantity} unité(s) ajoutée(s) avec succès`);
                
                // Recharger la page pour actualiser l'historique
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'ajout de la quantité');
        });
    });
    
    // Retirer une quantité
    removeQuantityButton.addEventListener('click', function() {
        if (!currentBarcode) return;
        
        const quantity = parseInt(quantityInput.value);
        if (isNaN(quantity) || quantity <= 0) {
            alert('Veuillez entrer une quantité valide');
            return;
        }
        
        const motif = motifInput.value.trim();
        
        // Requête AJAX pour retirer la quantité
        fetch('index.php?page=scanner', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'remove_quantity',
                'barcode': currentBarcode,
                'quantity': quantity,
                'motif': motif || 'Retrait par scanner'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'affichage
                productQuantity.textContent = data.new_quantity;
                
                // Réinitialiser les champs
                quantityInput.value = 1;
                motifInput.value = '';
                
                // Afficher un message de succès
                alert(`${quantity} unité(s) retirée(s) avec succès`);
                
                // Recharger la page pour actualiser l'historique
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du retrait de la quantité');
        });
    });
    
    // Nettoyage lors de la fermeture de la page
    window.addEventListener('beforeunload', function() {
        if (Quagga.isRunning()) {
            Quagga.stop();
        }
    });
});
</script> 

<!-- Modal Ajout Quantité -->
<div class="modal fade" id="addQuantityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une quantité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addQuantityForm">
                    <input type="hidden" name="barcode" id="add_quantity_barcode">
                    <div class="mb-3">
                        <label class="form-label">Quantité</label>
                        <input type="number" class="form-control" name="quantity" required min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <textarea class="form-control" name="motif" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_temporaire" id="is_temporaire" value="1">
                            <label class="form-check-label" for="is_temporaire">
                                Produit temporaire (susceptible d'être retourné)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="addQuantityForm" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div> 