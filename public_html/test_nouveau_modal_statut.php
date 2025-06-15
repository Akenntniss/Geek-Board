<?php
/**
 * 🧪 Script de test pour le nouveau modal de changement de statut
 */

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/database.php';

// 🔐 Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    echo "❌ Vous devez être connecté pour tester le modal de statut.";
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test Nouveau Modal Statut</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-test-tube me-2"></i>
                            Test du Nouveau Modal de Changement de Statut
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- ✅ Informations du test -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Informations du test</h6>
                            <ul class="mb-0">
                                <li><strong>Shop ID:</strong> <?php echo $_SESSION['shop_id'] ?? 'Non défini'; ?></li>
                                <li><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Non défini'; ?></li>
                                <li><strong>Magasin:</strong> <?php echo $_SESSION['shop_name'] ?? 'Non défini'; ?></li>
                            </ul>
                        </div>

                        <!-- 🧪 Tests des fichiers AJAX -->
                        <div class="mb-4">
                            <h6><i class="fas fa-file-code me-2"></i>Vérification des fichiers</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php if (file_exists(__DIR__ . '/ajax/get_statuts.php')): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check me-2"></i>get_statuts.php ✅
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-times me-2"></i>get_statuts.php ❌
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if (file_exists(__DIR__ . '/ajax/update_statut_reparation.php')): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check me-2"></i>update_statut_reparation.php ✅
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-times me-2"></i>update_statut_reparation.php ❌
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 📊 Test de récupération des statuts -->
                        <div class="mb-4">
                            <h6><i class="fas fa-database me-2"></i>Test de récupération des statuts</h6>
                            <button type="button" class="btn btn-outline-primary" onclick="testerRecuperationStatuts()">
                                <i class="fas fa-play me-2"></i>Tester la récupération
                            </button>
                            <div id="resultatStatuts" class="mt-3"></div>
                        </div>

                        <!-- 🎭 Simulation d'une réparation -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="fas fa-tools me-2"></i>Simulation d'ouverture du modal</h6>
                                <p class="text-muted">Cliquez sur le bouton pour tester le nouveau modal avec des données fictives.</p>
                                
                                <button type="button" class="btn btn-success btn-lg" onclick="openStatusModal(999, 'En attente', 'Jean DUPONT', '0123456789')">
                                    <i class="fas fa-external-link-alt me-2"></i>
                                    Ouvrir le Modal de Test
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 📱 Inclure le modal depuis components/quick-actions.php -->
    <?php 
    // Inclusion du modal seulement (extraire la partie modal du fichier quick-actions.php)
    echo '<!-- 🔄 NOUVEAU MODAL CHANGEMENT DE STATUT AVEC BASE DE DONNÉES -->';
    echo '<div class="modal fade" id="statusUpdateModal" tabindex="-1">';
    echo '    <div class="modal-dialog modal-lg">';
    echo '        <div class="modal-content">';
    echo '            <div class="modal-header bg-gradient-primary text-white">';
    echo '                <h5 class="modal-title">';
    echo '                    <i class="fas fa-tasks me-2"></i>';
    echo '                    Changement de Statut';
    echo '                </h5>';
    echo '                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>';
    echo '            </div>';
    echo '            <div class="modal-body">';
    echo '                <div class="card mb-4">';
    echo '                    <div class="card-body">';
    echo '                        <div class="row">';
    echo '                            <div class="col-md-6">';
    echo '                                <h6 class="fw-bold text-primary">';
    echo '                                    <i class="fas fa-tools me-2"></i>';
    echo '                                    Réparation #<span id="statusReparationId">-</span>';
    echo '                                </h6>';
    echo '                                <p class="text-muted mb-1">';
    echo '                                    <i class="fas fa-user me-2"></i>';
    echo '                                    Client: <span id="statusClientName">-</span>';
    echo '                                </p>';
    echo '                            </div>';
    echo '                            <div class="col-md-6">';
    echo '                                <label class="form-label fw-bold">Statut actuel</label>';
    echo '                                <div>';
    echo '                                    <span class="badge badge-status fs-6" id="currentStatusDisplay">-</span>';
    echo '                                </div>';
    echo '                            </div>';
    echo '                        </div>';
    echo '                    </div>';
    echo '                </div>';
    echo '                <div class="mb-4">';
    echo '                    <h6 class="fw-bold mb-3">';
    echo '                        <i class="fas fa-list me-2"></i>';
    echo '                        Sélectionner le nouveau statut';
    echo '                    </h6>';
    echo '                    <div id="statusLoadingMessage" class="text-center py-4">';
    echo '                        <div class="spinner-border text-primary" role="status">';
    echo '                            <span class="visually-hidden">Chargement...</span>';
    echo '                        </div>';
    echo '                        <p class="mt-2 text-muted">Chargement des statuts...</p>';
    echo '                    </div>';
    echo '                    <div id="statusGrid" class="row g-3" style="display: none;"></div>';
    echo '                    <div id="statusErrorMessage" class="alert alert-danger" style="display: none;">';
    echo '                        <i class="fas fa-exclamation-triangle me-2"></i>';
    echo '                        Erreur lors du chargement des statuts.';
    echo '                    </div>';
    echo '                </div>';
    echo '            </div>';
    echo '            <div class="modal-footer">';
    echo '                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">';
    echo '                    <i class="fas fa-times me-2"></i>Annuler';
    echo '                </button>';
    echo '                <button type="button" class="btn btn-success" id="confirmStatusButton" onclick="confirmerChangementStatut()" disabled>';
    echo '                    <i class="fas fa-check me-2"></i>Confirmer le changement';
    echo '                </button>';
    echo '            </div>';
    echo '        </div>';
    echo '    </div>';
    echo '</div>';
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Styles pour le modal -->
    <style>
        /* === STYLES POUR LE NOUVEAU MODAL DE STATUT === */
        #statusUpdateModal .modal-content {
            border: none !important;
            border-radius: 15px !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
        }

        #statusUpdateModal .modal-header {
            border-radius: 15px 15px 0 0 !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .status-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-card:hover {
            border-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.15);
        }

        .status-card.selected {
            border-color: #28a745 !important;
            background: linear-gradient(135deg, #d4edda 0%, #ffffff 100%) !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25) !important;
        }

        .status-card.current-status {
            border-color: #6c757d !important;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .status-card h6 {
            margin: 0;
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
        }

        .status-card.selected h6 {
            color: #28a745 !important;
        }

        .status-card.current-status h6 {
            color: #6c757d !important;
        }

        .badge-status {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            color: white !important;
            padding: 0.5rem 1rem !important;
            border-radius: 8px !important;
        }

        #statusGrid {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>

    <!-- JavaScript pour le test -->
    <script>
        // Variables globales
        let currentRepairData = {
            id: null,
            currentStatus: '',
            clientName: '',
            clientPhone: ''
        };

        let selectedStatusId = null;
        let availableStatuts = [];

        // Test de récupération des statuts
        async function testerRecuperationStatuts() {
            const resultat = document.getElementById('resultatStatuts');
            resultat.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Test en cours...';
            
            try {
                const response = await fetch('ajax/get_statuts.php');
                const result = await response.json();
                
                if (result.success) {
                    resultat.innerHTML = `
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check me-2"></i>Succès!</h6>
                            <p><strong>Statuts trouvés:</strong> ${result.count}</p>
                            <details>
                                <summary>Voir les détails</summary>
                                <pre>${JSON.stringify(result.data, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                } else {
                    resultat.innerHTML = `
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-times me-2"></i>Erreur</h6>
                            <p>${result.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultat.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreur de connexion</h6>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }

        // Inclure toutes les fonctions du modal (copiées depuis quick-actions.php)
        function openStatusModal(reparationId, currentStatus, clientName, clientPhone) {
            console.log('🔄 Ouverture nouveau modal statut:', {reparationId, currentStatus, clientName, clientPhone});
            
            currentRepairData = {
                id: reparationId,
                currentStatus: currentStatus,
                clientName: clientName,
                clientPhone: clientPhone
            };
            
            selectedStatusId = null;
            
            document.getElementById('statusReparationId').textContent = reparationId;
            document.getElementById('statusClientName').textContent = clientName;
            document.getElementById('currentStatusDisplay').textContent = currentStatus;
            
            document.getElementById('confirmStatusButton').disabled = true;
            
            const statusModal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));
            statusModal.show();
            
            setTimeout(() => {
                chargerStatuts();
            }, 200);
        }

        async function chargerStatuts() {
            console.log('📊 Chargement des statuts...');
            
            document.getElementById('statusLoadingMessage').style.display = 'block';
            document.getElementById('statusGrid').style.display = 'none';
            document.getElementById('statusErrorMessage').style.display = 'none';
            
            try {
                const response = await fetch('ajax/get_statuts.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                console.log('📊 Résultat chargement statuts:', result);
                
                if (result.success && result.data) {
                    availableStatuts = result.data;
                    afficherStatuts(result.data);
                } else {
                    throw new Error(result.message || 'Erreur lors du chargement des statuts');
                }
                
            } catch (error) {
                console.error('❌ Erreur chargement statuts:', error);
                document.getElementById('statusLoadingMessage').style.display = 'none';
                document.getElementById('statusErrorMessage').style.display = 'block';
                document.getElementById('statusErrorMessage').innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erreur: ${error.message}
                `;
            }
        }

        function afficherStatuts(statuts) {
            console.log('🎨 Affichage des statuts:', statuts);
            
            const statusGrid = document.getElementById('statusGrid');
            statusGrid.innerHTML = '';
            
            statuts.forEach(statut => {
                const isCurrentStatus = statut.nom === currentRepairData.currentStatus;
                
                const statusCard = document.createElement('div');
                statusCard.className = 'col-md-4 col-sm-6';
                statusCard.innerHTML = `
                    <div class="status-card ${isCurrentStatus ? 'current-status' : ''}" 
                         data-statut-id="${statut.id}" 
                         data-statut-nom="${statut.nom}"
                         onclick="${isCurrentStatus ? '' : `selectionnerStatut(${statut.id}, '${statut.nom}')`}">
                        <h6>${statut.nom}</h6>
                        ${isCurrentStatus ? '<small class="text-muted">(Statut actuel)</small>' : ''}
                    </div>
                `;
                
                statusGrid.appendChild(statusCard);
            });
            
            document.getElementById('statusLoadingMessage').style.display = 'none';
            document.getElementById('statusGrid').style.display = 'block';
        }

        function selectionnerStatut(statutId, statutNom) {
            console.log('✅ Sélection statut:', {statutId, statutNom});
            
            document.querySelectorAll('.status-card.selected').forEach(card => {
                card.classList.remove('selected');
            });
            
            const selectedCard = document.querySelector(`[data-statut-id="${statutId}"]`);
            if (selectedCard && !selectedCard.classList.contains('current-status')) {
                selectedCard.classList.add('selected');
                selectedStatusId = statutId;
                
                document.getElementById('confirmStatusButton').disabled = false;
            }
        }

        async function confirmerChangementStatut() {
            if (!selectedStatusId) {
                alert('⚠️ Veuillez sélectionner un nouveau statut.');
                return;
            }
            
            const selectedStatut = availableStatuts.find(s => s.id == selectedStatusId);
            if (!selectedStatut) {
                alert('❌ Erreur: statut sélectionné introuvable.');
                return;
            }
            
            console.log('🔄 Confirmation changement statut:', {
                reparationId: currentRepairData.id,
                statutId: selectedStatusId,
                statutNom: selectedStatut.nom
            });
            
            const confirmButton = document.getElementById('confirmStatusButton');
            const originalText = confirmButton.innerHTML;
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mise à jour...';
            
            try {
                const response = await fetch('ajax/update_statut_reparation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'same-origin',
                    body: `reparation_id=${currentRepairData.id}&statut_id=${selectedStatusId}`
                });
                
                const result = await response.json();
                console.log('✅ Résultat changement statut:', result);
                
                if (result.success) {
                    const statusModal = bootstrap.Modal.getInstance(document.getElementById('statusUpdateModal'));
                    statusModal.hide();
                    
                    alert(`✅ Statut mis à jour avec succès!\n\n📋 Réparation #${currentRepairData.id}\n🔄 Nouveau statut: ${selectedStatut.nom}`);
                } else {
                    throw new Error(result.message || 'Erreur lors de la mise à jour');
                }
                
            } catch (error) {
                console.error('❌ Erreur changement statut:', error);
                alert(`❌ Erreur lors de la mise à jour du statut:\n${error.message}`);
                
                confirmButton.disabled = false;
                confirmButton.innerHTML = originalText;
            }
        }
    </script>
</body>
</html> 