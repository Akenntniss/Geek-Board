<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'accueil' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'clients' ? 'active' : ''; ?>" href="index.php?page=clients">
                            <i class="fas fa-users me-2"></i>Liste des clients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'ajouter_client' ? 'active' : ''; ?>" href="index.php?page=ajouter_client">
                            <i class="fas fa-user-plus me-2"></i>Ajouter un client
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'reparations' ? 'active' : ''; ?>" href="index.php?page=reparations">
                            <i class="fas fa-wrench me-2"></i>Réparations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'rachat_appareils' ? 'active' : ''; ?>" href="index.php?page=rachat_appareils">
                            <i class="fas fa-hand-holding-usd me-2"></i>
                            Rachat Appareils
                        </a>
                    </li>
                    
<li class="nav-item">
                        <a class="nav-link <?php echo $page == 'ajouter_reparation' ? 'active' : ''; ?>" href="index.php?page=ajouter_reparation">
                            <i class="fas fa-plus-circle me-2"></i>Ajouter une réparation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'employes' ? 'active' : ''; ?>" href="index.php?page=employes">
                            <i class="fas fa-users-cog me-2"></i>
                            Gestion Employés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'messagerie' ? 'active' : ''; ?>" href="index.php?page=messagerie">
                            <i class="fas fa-comments me-2"></i>
                            Messagerie
                            <?php
                            if (function_exists('count_unread_messages') && isset($_SESSION['user_id'])) {
                                $unread_count = count_unread_messages($_SESSION['user_id']);
                                if ($unread_count > 0) {
                                    echo '<span class="badge bg-danger rounded-pill ms-1">' . $unread_count . '</span>';
                                }
                            }
                            ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array($page, ['base_connaissances', 'article_kb', 'ajouter_article_kb', 'modifier_article_kb', 'gestion_kb']) ? 'active' : ''; ?>" href="index.php?page=base_connaissances">
                            <i class="fas fa-book me-2"></i>
                            Base de Connaissances
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'reparation_logs' ? 'active' : ''; ?>" href="index.php?page=reparation_logs">
                            <i class="fas fa-history me-2"></i>
                            Logs Réparations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'sms_templates' ? 'active' : ''; ?>" href="index.php?page=sms_templates">
                            <i class="fas fa-sms me-2"></i>
                            SMS Auto.
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (in_array($page, ['sms_historique']) || strpos($_SERVER['REQUEST_URI'], 'messagerie/envoyer-sms.php') !== false || strpos($_SERVER['REQUEST_URI'], 'messagerie/sms.php') !== false) ? 'active' : ''; ?>" href="#" id="smsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sms me-2"></i> 
                            <span>SMS Tous</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="smsDropdown">
                            <li><a class="dropdown-item" href="<?php echo get_base_url(); ?>messagerie/envoyer-sms.php">
                                <i class="fas fa-paper-plane me-2"></i> Envoyer SMS</a>
                            </li>
                            <li><a class="dropdown-item" href="<?php echo get_base_url(); ?>messagerie/sms.php">
                                <i class="fas fa-history me-2"></i> Historique SMS</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=sms_templates">
                                <i class="fas fa-clipboard-list me-2"></i> Modèles automatiques</a>
                            </li>
                            <li><a class="dropdown-item" href="index.php?page=sms_historique">
                                <i class="fas fa-history me-2"></i> Historique Automatisations</a>
                            </li>
                        </ul>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'bug_reports' ? 'active' : ''; ?>" href="index.php?page=bug_reports">
                            <i class="fas fa-bug me-2"></i>
                            Rapports de bugs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'admin_notifications' ? 'active' : ''; ?>" href="index.php?page=admin_notifications">
                            <i class="fas fa-bell me-2"></i>
                            Notifications PWA
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="container mt-4">
                <?php echo display_message(); ?>