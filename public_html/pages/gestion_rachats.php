<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Vérification des droits d'accès
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit();
}

// Récupération des statistiques
try {
    // Nombre de rachats du mois
    $month = date('m');
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM rachat_appareils WHERE MONTH(date_rachat) = ? AND YEAR(date_rachat) = ?");
    $stmt->execute([$month, $year]);
    $rachats_mois = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Nombre total de rachats
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM rachat_appareils");
    $stmt->execute();
    $total_rachats = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des statistiques: ' . $e->getMessage();
}
?>

<div class="container-fluid py-4">
    <!-- En-tête avec statistiques et bouton d'ajout -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-5">
            <div class="d-flex align-items-center">
                <div class="icon-container me-2">
                    <i class="fas fa-calendar-alt text-primary"></i>
                </div>
                <div>
                    <span class="text-muted">Rachats du mois</span>
                    <h3 class="mb-0"><?= $rachats_mois ?></h3>
                </div>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="icon-container me-2">
                    <i class="fas fa-chart-bar text-primary"></i>
                </div>
                <div>
                    <span class="text-muted">Total des rachats</span>
                    <h3 class="mb-0"><?= $total_rachats ?></h3>
                </div>
            </div>
        </div>
        
        <a href="/pages/rachat_appareils.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Nouveau Rachat
        </a>
    </div>
    
    <!-- Section historique des rachats -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">
                    <i class="fas fa-history me-2 text-primary"></i>
                    Historique des rachats
                </h3>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchRachat" placeholder="Rechercher par client ou appareil...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Modèle</th>
                            <th>SIN</th>
                            <th>FONCTIONNEL</th>
                            <th>Photo Téléphone</th>
                            <th>Pièce identité</th>
                            <th>PRIX</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="rachatsList">
                        <!-- Les résultats AJAX seront chargés ici -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2 text-primary"></i>
                    Détails du rachat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="fas fa-id-card me-2 text-primary"></i>
                                    Pièce d'identité
                                </h6>
                                <img id="modalIdentite" class="img-fluid rounded img-preview" alt="Pièce d'identité">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="fas fa-mobile-alt me-2 text-primary"></i>
                                    Photo de l'appareil
                                </h6>
                                <img id="modalAppareil" class="img-fluid rounded img-preview" alt="Appareil">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chargement initial au démarrage
window.addEventListener('DOMContentLoaded', () => {
    loadRachats();
});

// Fonction pour charger les rachats
function loadRachats(search = '') {
    fetch('/ajax/recherche_rachat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin',
        body: new URLSearchParams({ search })
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('rachatsList');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">Aucun rachat trouvé</td></tr>';
            return;
        }
        
        data.forEach(rachat => {
            const date = new Date(rachat.date_rachat);
            const formattedDate = date.toLocaleDateString('fr-FR');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${formattedDate}</td>
                <td>${rachat.prenom} ${rachat.nom}</td>
                <td>${rachat.type_appareil}</td>
                <td>${rachat.sin || 'N/A'}</td>
                <td>${rachat.fonctionnel ? 'OUI' : 'NON'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-photo" data-photo="/assets/images/rachat/${rachat.photo_appareil}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-photo" data-photo="/assets/images/rachat/${rachat.photo_identite}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
                <td>${rachat.prix ? rachat.prix + ' euro' : 'N/A'}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary view-details" data-id="${rachat.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        // Ajouter les événements pour voir les photos
        document.querySelectorAll('.view-photo').forEach(btn => {
            btn.addEventListener('click', function() {
                const photoUrl = this.getAttribute('data-photo');
                const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                document.getElementById('modalAppareil').src = photoUrl;
                modal.show();
            });
        });
        
        // Ajouter les événements pour voir les détails
        document.querySelectorAll('.view-details').forEach(btn => {
            btn.addEventListener('click', function() {
                const rachatId = this.getAttribute('data-id');
                fetch(`/ajax/details_rachat.php?id=${rachatId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('modalIdentite').src = data.photo_identite_path;
                        document.getElementById('modalAppareil').src = data.photo_appareil_path;
                        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                        modal.show();
                    });
            });
        });
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// Gestion de la recherche
const searchInput = document.getElementById('searchRachat');
searchInput.addEventListener('input', function() {
    loadRachats(this.value);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>