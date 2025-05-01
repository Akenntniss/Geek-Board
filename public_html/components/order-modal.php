<?php
/**
 * Modal de commande de pièces avec design moderne
 * Support des modes jour et nuit
 */
?>

<div class="order-container" id="orderForm">
  <!-- En-tête du formulaire -->
  <div class="order-header">
    <h2><i class="fas fa-shopping-cart"></i> Nouvelle commande de pièces</h2>
    <button type="button" class="btn-close" id="closeOrderBtn" aria-label="Fermer">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <!-- Corps du formulaire -->
  <form id="orderPartsForm" method="post" action="">
    <!-- Section Client -->
    <div class="order-section">
      <div class="order-section-title">
        <i class="fas fa-user-circle"></i> Client
      </div>
      <div class="order-grid">
        <div class="form-group">
          <div class="client-field">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control" id="clientSearch" placeholder="Saisir ou rechercher un client" aria-label="Rechercher un client">
          </div>
        </div>
        <div class="form-group">
          <button type="button" class="btn btn-outline-primary w-100" id="newClientBtn">
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
        <input type="text" class="form-control" id="linkedRepair" value="Réparation #626 - Informatique iPh" placeholder="Rechercher une réparation">
      </div>
    </div>

    <!-- Section Fournisseur -->
    <div class="order-section">
      <div class="order-section-title">
        <i class="fas fa-truck"></i> Fournisseur
      </div>
      <div class="form-group">
        <div class="supplier-select">
          <select class="form-select" id="supplierSelect">
            <option value="">Sélectionner un fournisseur</option>
            <option value="1">iFixit France</option>
            <option value="2">Mobile France</option>
            <option value="3">GSM55 Parts</option>
            <option value="4">Distriphone</option>
            <option value="5">MacWay</option>
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
        <input type="text" class="form-control" id="partName" placeholder="Désignation de la pièce">
      </div>
    </div>

    <!-- Section Code barre -->
    <div class="order-section">
      <div class="order-grid">
        <div class="form-group">
          <div class="order-section-title">
            <i class="fas fa-barcode"></i> Code barre
          </div>
          <div class="barcode-field">
            <input type="text" class="form-control" id="barcode" placeholder="Saisir le code barre">
            <button type="button" class="barcode-scan-btn" id="scanBarcodeBtn" title="Scanner">
              <i class="fas fa-barcode"></i>
            </button>
          </div>
        </div>

        <!-- Section Quantité -->
        <div class="form-group">
          <div class="order-section-title">
            <i class="fas fa-sort-amount-up"></i> Quantité
          </div>
          <div class="quantity-selector">
            <button type="button" class="quantity-decrease" id="decreaseQuantity">
              <i class="fas fa-minus"></i>
            </button>
            <input type="number" id="quantity" value="1" min="1" max="99">
            <button type="button" class="quantity-increase" id="increaseQuantity">
              <i class="fas fa-plus"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Section Prix estimé -->
    <div class="order-section">
      <div class="order-grid">
        <div class="form-group">
          <div class="order-section-title">
            <i class="fas fa-tag"></i> Prix estimé (€)
          </div>
          <div class="price-field">
            <input type="number" class="form-control" id="price" placeholder="0.00" step="0.01" min="0">
          </div>
        </div>

        <!-- Section Statut -->
        <div class="form-group">
          <div class="order-section-title">
            <i class="fas fa-info-circle"></i> Statut
          </div>
          <div class="status-options">
            <div class="status-option status-option-pending">
              <input type="radio" name="status" id="statusPending" value="pending" checked>
              <label for="statusPending">
                <i class="fas fa-clock"></i>
                <span>En attente</span>
              </label>
            </div>
            <div class="status-option status-option-ordered">
              <input type="radio" name="status" id="statusOrdered" value="ordered">
              <label for="statusOrdered">
                <i class="fas fa-shopping-cart"></i>
                <span>Commandé</span>
              </label>
            </div>
            <div class="status-option status-option-received">
              <input type="radio" name="status" id="statusReceived" value="received">
              <label for="statusReceived">
                <i class="fas fa-check-circle"></i>
                <span>Reçu</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bouton pour ajouter une autre pièce -->
    <button type="button" class="add-item-btn" id="addAnotherPart">
      <i class="fas fa-plus-circle"></i> Ajouter une autre pièce
    </button>

    <!-- Pied de page avec boutons d'actions -->
    <div class="order-footer">
      <div>
        <button type="button" class="btn btn-outline-secondary" id="cancelOrderBtn">
          Annuler
        </button>
      </div>
      <div>
        <button type="button" class="btn btn-outline-primary" id="debugBtn">
          <i class="fas fa-bug"></i> Debug Session
        </button>
        <button type="submit" class="btn btn-primary" id="saveOrderBtn">
          <i class="fas fa-save"></i> Enregistrer la commande
        </button>
      </div>
    </div>
  </form>
</div>

<script>
  // JavaScript pour la gestion des interactions du formulaire
  document.addEventListener('DOMContentLoaded', function() {
    // Gestion du compteur de quantité
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decreaseQuantity');
    const increaseBtn = document.getElementById('increaseQuantity');

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

    // Gestion de la fermeture du modal
    const closeBtn = document.getElementById('closeOrderBtn');
    const cancelBtn = document.getElementById('cancelOrderBtn');
    
    function closeModal() {
      // Dans une vraie implémentation, on fermerait le modal
      // Ici, on simule juste une alerte
      if (confirm('Êtes-vous sûr de vouloir fermer ce formulaire ?')) {
        console.log('Formulaire fermé');
      }
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Soumission du formulaire
    const orderForm = document.getElementById('orderPartsForm');
    if (orderForm) {
      orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        // Ici on traiterait l'envoi du formulaire
        alert('Commande enregistrée avec succès !');
      });
    }
  });
</script> 