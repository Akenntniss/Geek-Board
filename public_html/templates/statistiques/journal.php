<?php
// Template minimal pour statistiques

// Récupérer quelques données de base pour éviter les erreurs
$type_stats_actuel = basename(__FILE__, '.php');
?>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Les statistiques <?= $type_stats_actuel ?> sont en cours de développement.
</div>

<div class="stats-card p-4">
    <h3 class="mb-4">Statistiques <?= ucfirst($type_stats_actuel) ?></h3>
    <p>Cette section affichera bientôt les statistiques détaillées pour la catégorie <?= $type_stats_actuel ?>.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Template de statistiques <?= $type_stats_actuel ?> chargé');
});
</script>