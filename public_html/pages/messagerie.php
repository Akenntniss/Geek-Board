<?php
/**
 * Page de redirection vers le module de messagerie
 */

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}

// Obtenir l'URL de base du site
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $domain;

// Rediriger en JavaScript car header peut ne pas fonctionner si du contenu a déjà été envoyé
?>
<script>
    // Redirection vers le module de messagerie avec chemin absolu
    window.location.href = '<?php echo $base_url; ?>/messagerie/';
</script>

<!-- Message de redirection au cas où JavaScript est désactivé -->
<div class="container mt-5">
    <div class="alert alert-info">
        <h4><i class="fas fa-sync fa-spin me-2"></i>Redirection en cours...</h4>
        <p>Vous êtes en train d'être redirigé vers le module de messagerie.</p>
        <p>Si la redirection ne fonctionne pas, <a href="<?php echo $base_url; ?>/messagerie/">cliquez ici</a>.</p>
    </div>
</div> 