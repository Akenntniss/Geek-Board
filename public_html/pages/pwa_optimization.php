<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit();
}

// Variables pour la détection du mode PWA
$is_pwa = isset($_SESSION['pwa_mode']) && $_SESSION['pwa_mode'] === true;
$is_ios = isset($_SESSION['test_ios']) && $_SESSION['test_ios'] === true;
$device_type = '';

// Détection du type d'appareil
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'iPhone') !== false || strpos($user_agent, 'iPad') !== false || $is_ios) {
        $device_type = 'ios';
    } elseif (strpos($user_agent, 'Android') !== false) {
        $device_type = 'android';
    } else {
        $device_type = 'desktop';
    }
}
?>

<div class="container mt-4 pwa-optimization-container">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fas fa-mobile-alt me-2"></i>
                    <h5 class="mb-0">Optimisation du Mode PWA</h5>
                </div>
                <div class="card-body">
                    <div class="pwa-status mb-4">
                        <h6 class="fw-bold">Statut Actuel</h6>
                        <div class="d-flex align-items-center mb-3">
                            <div class="status-indicator me-3 <?php echo $is_pwa ? 'active' : 'inactive'; ?>">
                                <i class="fas fa-<?php echo $is_pwa ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div>
                                <p class="mb-0">
                                    <strong>Mode PWA:</strong> 
                                    <?php echo $is_pwa ? 'Activé' : 'Désactivé'; ?>
                                </p>
                                <small class="text-muted">
                                    <?php echo $is_pwa 
                                        ? 'Vous utilisez l\'application en mode PWA optimisé' 
                                        : 'Vous utilisez l\'application dans le navigateur standard'; ?>
                                </small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="status-indicator me-3 <?php echo $device_type !== 'desktop' ? 'active' : 'inactive'; ?>">
                                <i class="fas fa-<?php echo $device_type !== 'desktop' ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div>
                                <p class="mb-0">
                                    <strong>Type d'appareil:</strong> 
                                    <?php 
                                        switch($device_type) {
                                            case 'ios':
                                                echo 'iOS (iPhone/iPad)';
                                                break;
                                            case 'android':
                                                echo 'Android';
                                                break;
                                            default:
                                                echo 'Ordinateur de bureau';
                                        }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Guide d'installation PWA -->
                    <div class="pwa-installation-guide mb-4">
                        <h6 class="fw-bold mb-3">Comment installer l'application sur votre appareil</h6>
                        
                        <?php if ($device_type === 'ios'): ?>
                        <!-- Guide iOS -->
                        <div class="ios-guide">
                            <p>Pour installer GeekBoard sur votre iPhone ou iPad:</p>
                            <ol class="guide-steps">
                                <li>Ouvrez Safari et naviguez sur cette application</li>
                                <li>Appuyez sur l'icône <strong>Partager</strong> <i class="fas fa-share-square text-primary"></i> en bas de l'écran</li>
                                <li>Faites défiler et appuyez sur <strong>"Sur l'écran d'accueil"</strong> <i class="fas fa-plus-square text-primary"></i></li>
                                <li>Appuyez sur <strong>"Ajouter"</strong> en haut à droite</li>
                            </ol>
                            <div class="mt-3 text-center">
                                <img src="/assets/images/pwa-guide/ios-install.png" alt="Guide d'installation iOS" class="img-fluid rounded shadow-sm" style="max-width: 300px;">
                            </div>
                        </div>
                        <?php elseif ($device_type === 'android'): ?>
                        <!-- Guide Android -->
                        <div class="android-guide">
                            <p>Pour installer GeekBoard sur votre appareil Android:</p>
                            <ol class="guide-steps">
                                <li>Ouvrez Chrome et naviguez sur cette application</li>
                                <li>Appuyez sur les trois points <strong>⋮</strong> en haut à droite</li>
                                <li>Sélectionnez <strong>"Ajouter à l'écran d'accueil"</strong> <i class="fas fa-plus-square text-primary"></i></li>
                                <li>Confirmez en appuyant sur <strong>"Ajouter"</strong></li>
                            </ol>
                            <div class="mt-3 text-center">
                                <img src="/assets/images/pwa-guide/android-install.png" alt="Guide d'installation Android" class="img-fluid rounded shadow-sm" style="max-width: 300px;">
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Guide Desktop -->
                        <div class="desktop-guide">
                            <p>Pour installer GeekBoard sur votre ordinateur:</p>
                            <ol class="guide-steps">
                                <li>Ouvrez Chrome, Edge ou un navigateur compatible</li>
                                <li>Naviguez sur cette application</li>
                                <li>Recherchez l'icône d'installation <i class="fas fa-plus-circle text-primary"></i> dans la barre d'adresse</li>
                                <li>Cliquez sur cette icône et suivez les instructions</li>
                            </ol>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> L'application sera installée comme une application native et accessible depuis votre bureau ou menu démarrer.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Avantages PWA -->
                    <div class="pwa-benefits mb-4">
                        <h6 class="fw-bold mb-3">Avantages du mode PWA</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="benefit-item">
                                    <i class="fas fa-bolt text-warning me-2"></i>
                                    <span>Chargement plus rapide</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-wifi-slash text-info me-2"></i>
                                    <span>Fonctionnement hors-ligne</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-mobile-alt text-success me-2"></i>
                                    <span>Interface optimisée</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-item">
                                    <i class="fas fa-bell text-danger me-2"></i>
                                    <span>Notifications</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-battery-full text-primary me-2"></i>
                                    <span>Économie de batterie</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-lock text-secondary me-2"></i>
                                    <span>Accès plus sécurisé</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Astuces de performance -->
                    <div class="pwa-tips">
                        <h6 class="fw-bold mb-3">Astuces pour des performances optimales</h6>
                        <div class="tips-list">
                            <div class="tip-item">
                                <div class="tip-icon"><i class="fas fa-sync-alt"></i></div>
                                <div class="tip-content">
                                    <strong>Mise à jour régulière</strong>
                                    <p>Vérifiez régulièrement les mises à jour de l'application en actualisant la page.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <div class="tip-icon"><i class="fas fa-trash-alt"></i></div>
                                <div class="tip-content">
                                    <strong>Nettoyage du cache</strong>
                                    <p>En cas de problème, essayez de vider le cache de votre navigateur ou de réinstaller l'application.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <div class="tip-icon"><i class="fas fa-network-wired"></i></div>
                                <div class="tip-content">
                                    <strong>Connexion stable</strong>
                                    <p>Pour la première utilisation, assurez-vous d'avoir une connexion stable pour charger toutes les ressources.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mode test -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Mode Test PWA</h5>
                </div>
                <div class="card-body">
                    <p>Vous pouvez tester les fonctionnalités PWA sans installer l'application:</p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="?page=pwa_optimization&test_pwa=true" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-vial me-1"></i> Tester le mode PWA
                        </a>
                        <a href="?page=pwa_optimization&test_ios=true" class="btn btn-outline-info btn-sm">
                            <i class="fab fa-apple me-1"></i> Simuler iOS
                        </a>
                        <a href="?page=pwa_optimization&test_dynamic_island=true" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-mobile-alt me-1"></i> Simuler Dynamic Island
                        </a>
                        <a href="?page=pwa_optimization" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-undo me-1"></i> Réinitialiser
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour la page d'optimisation PWA */
.pwa-optimization-container {
    max-width: 1000px;
}

.status-indicator {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.status-indicator.active {
    background-color: #28a745;
}

.status-indicator.inactive {
    background-color: #dc3545;
}

.guide-steps {
    padding-left: 20px;
}

.guide-steps li {
    margin-bottom: 10px;
    position: relative;
}

.benefit-item {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    padding: 8px 12px;
    background-color: #f8f9fa;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.tip-item {
    display: flex;
    margin-bottom: 15px;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.tip-icon {
    flex: 0 0 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #495057;
    margin-right: 15px;
}

.tip-content {
    flex: 1;
}

.tip-content p {
    margin-bottom: 0;
    font-size: 14px;
    color: #6c757d;
}

/* Styles spécifiques au mode PWA actif */
body.pwa-mode .pwa-tips {
    border-left: 4px solid #4361ee;
    padding-left: 15px;
}

@media (max-width: 768px) {
    .benefit-item {
        font-size: 14px;
    }
    
    .tip-item {
        flex-direction: column;
    }
    
    .tip-icon {
        margin-bottom: 10px;
        margin-right: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'application est installée en PWA
    const isPWA = window.matchMedia('(display-mode: standalone)').matches || 
                   window.navigator.standalone ||
                   document.referrer.includes('android-app://');
    
    // Mettre à jour l'interface selon le statut réel
    if (isPWA) {
        document.querySelectorAll('.pwa-status .status-indicator').forEach(el => {
            el.classList.add('active');
            el.classList.remove('inactive');
            el.innerHTML = '<i class="fas fa-check"></i>';
        });
        
        document.querySelector('.pwa-status p strong').nextSibling.textContent = ' Activé';
        document.querySelector('.pwa-status small').textContent = 'Vous utilisez l\'application en mode PWA optimisé';
    }
    
    // Ajouter des transitions fluides
    const benefitItems = document.querySelectorAll('.benefit-item');
    benefitItems.forEach((item, index) => {
        item.style.animationDelay = (index * 0.1) + 's';
        item.classList.add('fade-in');
    });
});
</script> 