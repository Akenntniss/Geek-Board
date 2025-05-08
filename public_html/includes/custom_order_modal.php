<?php
/**
 * Modal moderne de commande de pièces avec support des modes jour/nuit
 */

// S'assurer que la variable dark_mode est définie
$dark_mode = isset($dark_mode) ? $dark_mode : (isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] === true);
?>

<!-- Modal Ajouter Commande - Design Moderne -->
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1" aria-labelledby="ajouterCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content order-container" style="background-color: <?php echo $dark_mode ? '#1f2937' : '#ffffff'; ?>; opacity: 1 !important;">
            <!-- En-tête du formulaire -->
            <div class="order-header">
                <h2><i class="fas fa-shopping-cart"></i> Nouvelle commande de pièces</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du formulaire -->
            <div class="modal-body p-0">
                <form id="ajouterCommandeForm" method="post" action="ajax/add_commande.php">
                    <!-- Section Client -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-user-circle"></i> Client
                        </div>
                        <div class="order-grid">
                            <div class="form-group">
                                <div class="client-field">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control" id="nom_client_selectionne" placeholder="Saisir ou rechercher un client" aria-label="Rechercher un client">
                                    <input type="hidden" name="client_id" id="client_id" value="">
                                </div>
                                <div id="client_selectionne" class="selected-item-info d-none mt-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-icon me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <span class="fw-medium nom_client"></span>
                                            <span class="d-block small text-muted tel_client"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Résultats de recherche client inline -->
                                <div id="resultats_recherche_client_inline" class="mt-2 d-none">
                                    <div class="card border-0 shadow-sm">
                                        <div class="list-group list-group-flush" id="liste_clients_recherche_inline">
                                            <!-- Les résultats seront ajoutés ici -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="button" class="btn btn-outline-primary w-100" id="newClientBtn" data-bs-toggle="modal" data-bs-target="#nouveauClientModal_commande">
                                    <i class="fas fa-user-plus"></i> Créer un nouveau client
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Section Réparation liée -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-tools"></i> Réparation liée (optionnel)
                        </div>
                        <div class="form-group">
                            <select class="form-select" name="reparation_id" id="reparation_id" onchange="getClientFromReparation(this.value)">
                                <option value="">Sélectionner une réparation...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Section Fournisseur -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-truck"></i> Fournisseur
                        </div>
                        <div class="form-group">
                            <div class="supplier-select">
                                <select class="form-select" name="fournisseur_id" id="fournisseur_id_ajout" required>
                                    <option value="">Sélectionner un fournisseur</option>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                                        while ($fournisseur = $stmt->fetch()) {
                                            echo "<option value='{$fournisseur['id']}'>" . 
                                                htmlspecialchars($fournisseur['nom']) . "</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<option value=''>Erreur de chargement des fournisseurs</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section Pièce commandée -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-microchip"></i> Pièce commandée
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="nom_piece" id="nom_piece" placeholder="Désignation de la pièce" required>
                        </div>
                    </div>

                    <!-- Section Code barre et Quantité -->
                    <div class="order-section">
                        <div class="order-grid">
                            <div class="form-group">
                                <div class="order-section-title">
                                    <i class="fas fa-barcode"></i> Code barre
                                </div>
                                <div class="barcode-field">
                                    <input type="text" class="form-control" name="code_barre" id="code_barre" placeholder="Saisir le code barre">
                                    <button type="button" class="barcode-scan-btn" id="scanBarcodeBtn" title="Scanner">
                                        <i class="fas fa-barcode"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="order-section-title">
                                    <i class="fas fa-sort-amount-up"></i> Quantité
                                </div>
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-decrease" id="decreaseQuantity">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantite" id="quantite" value="1" min="1" max="99">
                                    <button type="button" class="quantity-increase" id="increaseQuantity">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Prix estimé et Statut -->
                    <div class="order-section">
                        <div class="order-grid">
                            <div class="form-group">
                                <div class="order-section-title">
                                    <i class="fas fa-tag"></i> Prix estimé (€)
                                </div>
                                <div class="price-field">
                                    <input type="number" class="form-control" name="prix_estime" id="prix_estime" placeholder="0.00" step="0.01" min="0" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="order-section-title">
                                    <i class="fas fa-info-circle"></i> Statut
                                </div>
                                <div class="status-options">
                                    <div class="status-option status-option-pending">
                                        <input type="radio" name="statut" id="statusPending" value="en_attente" checked>
                                        <label for="statusPending">
                                            <i class="fas fa-clock"></i>
                                            <span>En attente</span>
                                        </label>
                                    </div>
                                    <div class="status-option status-option-ordered">
                                        <input type="radio" name="statut" id="statusOrdered" value="commande">
                                        <label for="statusOrdered">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span>Commandé</span>
                                        </label>
                                    </div>
                                    <div class="status-option status-option-received">
                                        <input type="radio" name="statut" id="statusReceived" value="recue">
                                        <label for="statusReceived">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Reçu</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Container pour les pièces additionnelles -->
                    <div id="pieces-additionnelles"></div>

                    <!-- Bouton pour ajouter une autre pièce -->
                    <button type="button" class="add-item-btn" id="ajouter-piece-btn">
                        <i class="fas fa-plus-circle"></i> Ajouter une autre pièce
                    </button>

                    <!-- Bouton pour activer/désactiver l'envoi de SMS -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-sms"></i> Notification client
                        </div>
                        <button id="smsToggleButtonAjout" type="button" class="btn btn-danger w-100 py-3" style="font-weight: bold; font-size: 1rem; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                            <i class="fas fa-ban me-2"></i>
                            NE PAS ENVOYER DE SMS AU CLIENT
                        </button>
                        <input type="hidden" id="sendSmsSwitchAjout" name="send_sms" value="0">
                    </div>

                    <!-- Pied de page avec boutons d'actions -->
                    <div class="order-footer">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Annuler
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-primary" id="debugSessionBtn">
                                <i class="fas fa-bug"></i> Debug Session
                            </button>
                            <button type="submit" class="btn btn-primary" id="saveCommandeBtn">
                                <i class="fas fa-save"></i> Enregistrer la commande
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript pour la gestion des interactions du formulaire
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du compteur de quantité
    const quantityInput = document.getElementById('quantite');
    const decreaseBtn = document.getElementById('decreaseQuantity');
    const increaseBtn = document.getElementById('increaseQuantity');

    if (decreaseBtn && increaseBtn && quantityInput) {
        decreaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
            updateDecreaseBtnState();
        });

        increaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
            updateDecreaseBtnState();
        });

        function updateDecreaseBtnState() {
            decreaseBtn.disabled = parseInt(quantityInput.value) <= 1;
        }

        // Initialisation de l'état du bouton de diminution
        updateDecreaseBtnState();
    }

    // Gestion des boutons radio de statut
    const statusRadios = document.querySelectorAll('input[name="statut"]');
    if (statusRadios.length) {
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Réinitialiser tous les statuts
                document.querySelectorAll('.status-option').forEach(option => {
                    option.classList.remove('active');
                });
                
                // Activer l'option sélectionnée
                if (this.checked) {
                    this.closest('.status-option').classList.add('active');
                }
            });
        });
        
        // Initialiser le statut actif
        const checkedRadio = document.querySelector('input[name="statut"]:checked');
        if (checkedRadio) {
            checkedRadio.closest('.status-option').classList.add('active');
        }
    }
    
    // Corrige le problème du backdrop qui bloque les interactions
    const fixModalBackdrop = function() {
        const modal = document.getElementById('ajouterCommandeModal');
        
        // Ajuster le modal quand il est ouvert
        modal.addEventListener('shown.bs.modal', function() {
            // Mettre le z-index du contenu modal au-dessus du backdrop
            const modalContent = this.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.zIndex = '1056';
            }
            
            // Vérifier s'il y a un backdrop et ajuster son comportement
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 0) {
                backdrops.forEach(backdrop => {
                    backdrop.style.pointerEvents = 'none';
                    backdrop.style.zIndex = '1040';
                });
            }
        });
        
        // Quand le modal est fermé, nettoyer les backdrops et restaurer le scroll
        modal.addEventListener('hidden.bs.modal', function() {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 0) {
                backdrops.forEach(backdrop => {
                    backdrop.remove(); // Supprimer tous les backdrops restants
                });
            }
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    };
    
    // Initialiser le correctif pour le backdrop
    fixModalBackdrop();
});
</script> 