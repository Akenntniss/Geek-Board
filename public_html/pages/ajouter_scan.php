<?php
// Définir la page actuelle pour le menu
$current_page = 'ajouter_scan';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Récupérer les catégories pour le formulaire
$categories = [];
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("SELECT id, nom FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des catégories: " . $e->getMessage(), "danger");
}

// Vérifier s'il y a un code-barres passé en paramètre (depuis le scanner)
$barcode_param = isset($_GET['barcode']) ? cleanInput($_GET['barcode']) : '';

// Traitement du formulaire d'ajout de produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = cleanInput($_POST['barcode']);
    $name = cleanInput($_POST['name']);
    $category = cleanInput($_POST['category']);
    $price = (float)str_replace(',', '.', $_POST['price']);
    $quantity = (int)$_POST['quantity'];
    $description = cleanInput($_POST['description']);
    $user_id = $_SESSION['user_id'];
    
    // Validation des données
    $errors = [];
    
    if (empty($barcode)) {
        $errors[] = "Le code-barres est obligatoire.";
    }
    
    if (empty($name)) {
        $errors[] = "Le nom du produit est obligatoire.";
    }
    
    // Vérifier si le code-barres existe déjà
    try {
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM stock WHERE barcode = ?");
        $stmt->execute([$barcode]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ce code-barres existe déjà dans l'inventaire.";
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la vérification du code-barres: " . $e->getMessage();
    }
    
    if (empty($errors)) {
        try {
            // Insérer le nouveau produit dans la table stock
            $stmt = $shop_pdo->prepare("
                INSERT INTO stock (barcode, name, category, price, quantity, description, date_created, date_updated) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$barcode, $name, $category, $price, $quantity, $description]);
            
            $product_id = $shop_pdo->lastInsertId();
            
            // Enregistrer le mouvement de stock si la quantité est > 0
            if ($quantity > 0) {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, date_mouvement, motif, user_id) 
                    VALUES (?, 'entree', ?, NOW(), ?, ?)
                ");
                $stmt->execute([$product_id, $quantity, "Ajout initial du produit", $user_id]);
            }
            
            set_message("Le produit a été ajouté avec succès!", "success");
            
            // Rediriger vers la page scanner ou inventaire
            header('Location: index.php?page=scanner');
            exit();
        } catch (PDOException $e) {
            set_message("Erreur lors de l'ajout du produit: " . $e->getMessage(), "danger");
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
    <h1 class="my-4">Ajouter un nouveau produit</h1>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Informations du produit</h5>
                    <a href="index.php?page=scanner" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Retour au scanner
                    </a>
                </div>
                <div class="card-body">
                    <form method="post" action="" id="product-form">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="barcode" class="form-label fw-bold">Code-barres *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg" id="barcode" name="barcode" value="<?php echo !empty($barcode_param) ? htmlspecialchars($barcode_param) : (isset($_POST['barcode']) ? htmlspecialchars($_POST['barcode']) : ''); ?>" required>
                                    <button class="btn btn-outline-primary" type="button" id="scan-barcode-button" title="Scanner un code-barres">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="generate-barcode-button" title="Générer un code-barres aléatoire">
                                        <i class="fas fa-random"></i>
                                    </button>
                                </div>
                                <div class="form-text"><i class="fas fa-info-circle me-1"></i>Scanner ou saisir le code-barres du produit</div>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-bold">Nom du produit *</label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label fw-bold">Catégorie</label>
                                <div class="input-group">
                                    <select class="form-select" id="category" name="category">
                                        <option value="">- Sélectionner une catégorie -</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category['nom']); ?>" <?php echo (isset($_POST['category']) && $_POST['category'] === $category['nom']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-secondary" type="button" id="add-category-button" title="Ajouter une nouvelle catégorie">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="price" class="form-label fw-bold">Prix de vente</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="price" name="price" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '0.00'; ?>">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="quantity" class="form-label fw-bold">Quantité initiale *</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary" type="button" id="quantity-minus">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" id="quantity" name="quantity" value="<?php echo isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1; ?>" min="0" required>
                                    <button class="btn btn-outline-secondary" type="button" id="quantity-plus">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="threshold" class="form-label fw-bold">Seuil d'alerte</label>
                                <input type="number" class="form-control" id="threshold" name="threshold" value="<?php echo isset($_POST['threshold']) ? (int)$_POST['threshold'] : 5; ?>" min="0">
                                <div class="form-text"><i class="fas fa-bell me-1"></i>Une alerte sera émise quand le stock sera inférieur à ce seuil</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Description détaillée du produit..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card mb-3 border-primary">
                                    <div class="card-body bg-light">
                                        <h6 class="card-title"><i class="fas fa-tags me-2"></i>Aperçu du produit</h6>
                                        <div id="barcode-preview" class="text-center my-3">
                                            <div id="barcode-placeholder" class="border p-3 d-inline-block">
                                                <div id="barcode-container"></div>
                                                <div id="barcode-text" class="mt-2 small"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3 border-success">
                                    <div class="card-body bg-light">
                                        <h6 class="card-title"><i class="fas fa-info-circle me-2"></i>Informations</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check-circle text-success me-2"></i>Le produit sera ajouté à l'inventaire</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i>Un mouvement de stock sera enregistré</li>
                                            <li><i class="fas fa-exclamation-circle text-warning me-2"></i>Une fois ajouté, vous pourrez scanner le produit pour modifier son stock</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Ajouter le produit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter une nouvelle catégorie -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addCategoryModalLabel"><i class="fas fa-folder-plus me-2"></i>Ajouter une nouvelle catégorie</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new-category-name" class="form-label">Nom de la catégorie</label>
                    <input type="text" class="form-control" id="new-category-name">
                </div>
                <div class="mb-3">
                    <label for="new-category-description" class="form-label">Description (optionnelle)</label>
                    <textarea class="form-control" id="new-category-description" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="save-category-button">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<!-- Scanner de code-barres modal -->
<div class="modal fade" id="scannerModal" tabindex="-1" aria-labelledby="scannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="scannerModalLabel"><i class="fas fa-barcode me-2"></i>Scanner un code-barres</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="position-relative">
                    <video id="scanner" class="w-100 border rounded"></video>
                    <div id="scan-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
                        <div class="scanner-region" style="width: 250px; height: 150px; border: 2px solid #3498db; position: relative;">
                            <div class="scanner-line" style="width: 100%; height: 2px; background-color: #3498db; position: absolute; top: 50%; animation: scan 2s infinite ease-in-out;"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <span id="scanner-status" class="text-muted">Initialisation de la caméra...</span>
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
    
    #barcode-placeholder {
        min-height: 100px;
        min-width: 200px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const scanBarcodeButton = document.getElementById('scan-barcode-button');
    const generateBarcodeButton = document.getElementById('generate-barcode-button');
    const scannerModal = new bootstrap.Modal(document.getElementById('scannerModal'));
    const scannerStatus = document.getElementById('scanner-status');
    const barcodeInput = document.getElementById('barcode');
    const nameInput = document.getElementById('name');
    const addCategoryButton = document.getElementById('add-category-button');
    const addCategoryModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    const saveCategoryButton = document.getElementById('save-category-button');
    const newCategoryNameInput = document.getElementById('new-category-name');
    const newCategoryDescriptionInput = document.getElementById('new-category-description');
    const categorySelect = document.getElementById('category');
    const quantityMinus = document.getElementById('quantity-minus');
    const quantityPlus = document.getElementById('quantity-plus');
    const quantityInput = document.getElementById('quantity');
    
    let isScanning = false;
    
    // Initialiser le code-barres
    updateBarcodePreview();
    
    // Générer un code-barres aléatoire
    generateBarcodeButton.addEventListener('click', function() {
        // Générer un code EAN-13 aléatoire
        let ean = '';
        for (let i = 0; i < 12; i++) {
            ean += Math.floor(Math.random() * 10);
        }
        
        // Calculer le chiffre de contrôle
        let sum = 0;
        for (let i = 0; i < 12; i++) {
            sum += parseInt(ean[i]) * (i % 2 === 0 ? 1 : 3);
        }
        const checkDigit = (10 - (sum % 10)) % 10;
        
        // Ajouter le chiffre de contrôle
        ean += checkDigit;
        
        // Mettre à jour le champ
        barcodeInput.value = ean;
        
        // Mettre à jour l'aperçu
        updateBarcodePreview();
        
        // Focus sur le champ nom si vide
        if (!nameInput.value) {
            nameInput.focus();
        }
    });
    
    // Ouvrir le modal et activer le scanner
    scanBarcodeButton.addEventListener('click', function() {
        scannerModal.show();
        
        // Initialiser le scanner après l'ouverture du modal
        document.getElementById('scannerModal').addEventListener('shown.bs.modal', function() {
            initScanner();
        });
        
        // Arrêter le scanner à la fermeture du modal
        document.getElementById('scannerModal').addEventListener('hidden.bs.modal', function() {
            if (Quagga.isRunning()) {
                Quagga.stop();
            }
        });
    });
    
    // Initialiser le scanner de code-barres
    function initScanner() {
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.getElementById('scanner'),
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
                console.error(err);
                scannerStatus.textContent = "Erreur d'initialisation de la caméra";
                return;
            }
            Quagga.start();
            isScanning = true;
            scannerStatus.textContent = "Caméra activée, placez un code-barres dans la zone de scan";
        });
        
        Quagga.onDetected(function(result) {
            if (!isScanning) return;
            
            const code = result.codeResult.code;
            if (code) {
                // Pause temporaire pour éviter les lectures multiples
                isScanning = false;
                scannerStatus.textContent = `Code détecté: ${code}`;
                
                // Jouer un bip sonore et vibrer le téléphone si possible
                if (navigator.vibrate) {
                    navigator.vibrate(100);
                }
                
                // Remplir le champ de saisie
                barcodeInput.value = code;
                
                // Mettre à jour l'aperçu
                updateBarcodePreview();
                
                // Fermer le modal
                scannerModal.hide();
                
                // Mettre le focus sur le champ suivant
                nameInput.focus();
            }
        });
    }
    
    // Mettre à jour l'aperçu du code-barres
    function updateBarcodePreview() {
        const barcode = barcodeInput.value.trim();
        const container = document.getElementById('barcode-container');
        const textElement = document.getElementById('barcode-text');
        
        // Effacer l'aperçu actuel
        container.innerHTML = '';
        textElement.textContent = '';
        
        if (barcode) {
            try {
                // Créer un élément canvas pour le code-barres
                const canvas = document.createElement('canvas');
                container.appendChild(canvas);
                
                // Générer le code-barres
                JsBarcode(canvas, barcode, {
                    format: barcode.length === 13 ? "EAN13" : (barcode.length === 8 ? "EAN8" : "CODE128"),
                    width: 2,
                    height: 50,
                    displayValue: false
                });
                
                // Afficher le texte du code-barres
                textElement.textContent = barcode;
            } catch (e) {
                console.error("Erreur lors de la génération du code-barres:", e);
                textElement.textContent = "Code-barres invalide";
            }
        } else {
            textElement.textContent = "Saisissez un code-barres pour voir l'aperçu";
        }
    }
    
    // Surveiller les changements dans le champ du code-barres
    barcodeInput.addEventListener('input', updateBarcodePreview);
    
    // Gestion des boutons de quantité
    quantityMinus.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 0) {
            quantityInput.value = value - 1;
        }
    });
    
    quantityPlus.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        quantityInput.value = value + 1;
    });
    
    // Gérer l'ajout de catégorie
    addCategoryButton.addEventListener('click', function() {
        addCategoryModal.show();
    });
    
    saveCategoryButton.addEventListener('click', function() {
        const categoryName = newCategoryNameInput.value.trim();
        const categoryDescription = newCategoryDescriptionInput.value.trim();
        
        if (!categoryName) {
            alert('Veuillez saisir un nom de catégorie');
            return;
        }
        
        // Effectuer une requête AJAX pour ajouter la catégorie
        fetch('index.php?page=categories', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'action': 'add_category',
                'nom': categoryName,
                'description': categoryDescription
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Ajouter la nouvelle catégorie à la liste déroulante
                const option = document.createElement('option');
                option.value = categoryName;
                option.textContent = categoryName;
                option.selected = true;
                categorySelect.appendChild(option);
                
                // Réinitialiser et fermer le modal
                newCategoryNameInput.value = '';
                newCategoryDescriptionInput.value = '';
                addCategoryModal.hide();
                
                // Notification de succès
                alert('Catégorie ajoutée avec succès');
            } else {
                alert('Erreur lors de l\'ajout de la catégorie: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            
            // Ajout alternatif en cas d'erreur AJAX
            const option = document.createElement('option');
            option.value = categoryName;
            option.textContent = categoryName;
            option.selected = true;
            categorySelect.appendChild(option);
            
            newCategoryNameInput.value = '';
            newCategoryDescriptionInput.value = '';
            addCategoryModal.hide();
        });
    });
    
    // Formater le prix pour n'accepter que les nombres et le point/virgule comme séparateur
    const priceInput = document.getElementById('price');
    priceInput.addEventListener('input', function() {
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
    
    // Validation du formulaire côté client
    const form = document.getElementById('product-form');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Vérifier le code-barres
        if (barcodeInput.value.trim() === '') {
            isValid = false;
            alert('Veuillez saisir un code-barres');
            barcodeInput.focus();
        }
        
        // Vérifier le nom du produit
        else if (nameInput.value.trim() === '') {
            isValid = false;
            alert('Veuillez saisir un nom de produit');
            nameInput.focus();
        }
        
        if (!isValid) {
            e.preventDefault();
        } else {
            // Ajout d'animation lors de la soumission
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement en cours...';
        }
    });
    
    // Nettoyage lors de la fermeture de la page
    window.addEventListener('beforeunload', function() {
        if (Quagga && Quagga.isRunning()) {
            Quagga.stop();
        }
    });
});
</script> 