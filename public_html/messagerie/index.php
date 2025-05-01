<?php
/**
 * Page principale de la messagerie
 * Version 3.0 - Design modernisé
 */

// Initialiser la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Inclure la connexion à la base de données
require_once __DIR__ . '/../config/database.php';

// Inclure les fonctions
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie | GeekBoard</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/favicon.png" type="image/png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="css/messagerie.css">
</head>
<body>
    <!-- Conteneur principal -->
    <div class="app-container">
        <!-- Barre de navigation -->
        <nav class="app-navbar">
            <div class="navbar-brand">
                <a href="../index.php" class="nav-logo">
                    <i class="fas fa-arrow-left d-md-none"></i>
                    <span class="d-none d-md-inline">GeekBoard</span>
                </a>
            </div>
            <div class="navbar-title">
                <h1>Messagerie</h1>
            </div>
            <div class="navbar-actions">
                <button class="btn-icon theme-toggle" id="themeToggle" title="Changer de thème">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="dropdown">
                    <button class="btn-icon" id="navMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="#" id="refreshAllBtn"><i class="fas fa-sync-alt me-2"></i>Actualiser tout</a></li>
                        <li><a class="dropdown-item" href="#" id="markAllReadBtn"><i class="fas fa-check-double me-2"></i>Tout marquer comme lu</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../index.php"><i class="fas fa-home me-2"></i>Tableau de bord</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Conteneur principal de l'application -->
        <div class="app-content">
            <!-- Barre latérale -->
            <div class="sidebar">
                <!-- En-tête de la barre latérale -->
                <div class="sidebar-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2><i class="fas fa-comments me-2"></i>Messages</h2>
                        <div class="header-actions">
                            <button class="btn-icon" id="searchToggleBtn" title="Rechercher">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn-icon d-md-none" id="sidebarCloseBtn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Barre de recherche -->
                    <div class="search-container" id="searchContainer">
                        <div class="input-group">
                            <span class="input-group-text border-end-0">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Rechercher...">
                            <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchBtn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Filtres de conversation -->
                <div class="conversation-filters">
                    <div class="filter-scroll">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-inbox"></i>
                            <span>Tous</span>
                        </button>
                        <button class="filter-btn" data-filter="unread">
                            <i class="fas fa-envelope"></i>
                            <span>Non lus</span>
                        </button>
                        <button class="filter-btn" data-filter="direct">
                            <i class="fas fa-user"></i>
                            <span>Directs</span>
                        </button>
                        <button class="filter-btn" data-filter="group">
                            <i class="fas fa-users"></i>
                            <span>Groupes</span>
                        </button>
                        <button class="filter-btn" data-filter="favorites">
                            <i class="fas fa-star"></i>
                            <span>Favoris</span>
                        </button>
                        <button class="filter-btn" data-filter="archived">
                            <i class="fas fa-archive"></i>
                            <span>Archivés</span>
                        </button>
                    </div>
                </div>
                
                <!-- Bouton nouvelle conversation -->
                <div class="new-conversation">
                    <button class="btn btn-primary w-100" id="newMessageBtn">
                        <i class="fas fa-plus me-2"></i>Nouvelle conversation
                    </button>
                </div>
                
                <!-- Liste des conversations -->
                <div class="conversations-list" id="conversationsList">
                    <!-- Chargé dynamiquement par JS -->
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3">Chargement des conversations...</p>
                    </div>
                </div>
            </div>
            
            <!-- Zone principale -->
            <div class="main-content">
                <!-- Bouton d'ouverture du menu latéral (mobile) -->
                <button class="menu-toggle d-md-none" id="sidebarToggleBtn">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- État vide initial -->
                <div class="empty-state" id="emptyChat">
                    <div class="empty-icon animate__animated animate__fadeIn">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2 class="animate__animated animate__fadeIn animate__delay-1s">Bienvenue dans votre messagerie</h2>
                    <p class="animate__animated animate__fadeIn animate__delay-2s">Sélectionnez une conversation existante ou créez-en une nouvelle pour commencer à discuter.</p>
                    <button class="btn btn-primary btn-lg mt-4 animate__animated animate__fadeIn animate__delay-3s" id="newChatBtn">
                        <i class="fas fa-plus me-2"></i>Nouvelle conversation
                    </button>
                </div>
                
                <!-- Chat actif (masqué initialement) -->
                <div class="chat" id="activeChat" style="display: none;">
                    <!-- En-tête de conversation -->
                    <div class="chat-header">
                        <div class="chat-info">
                            <h2 id="activeChatTitle">Titre de la conversation</h2>
                            <p id="activeChatParticipants" class="text-muted">Participants</p>
                        </div>
                        <div class="chat-actions">
                            <button class="btn-icon" id="scrollToBottomBtn" title="Descendre">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button class="btn-icon" id="chatInfoBtn" title="Informations">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <div class="dropdown">
                                <button class="btn-icon" id="chatMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item" href="#" id="searchMessagesBtn"><i class="fas fa-search me-2"></i>Rechercher dans la conversation</a></li>
                                    <li><a class="dropdown-item" href="#" id="exportMessagesBtn"><i class="fas fa-file-export me-2"></i>Exporter la conversation</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" id="toggleFavoriteBtn"><i class="far fa-star me-2"></i>Ajouter aux favoris</a></li>
                                    <li><a class="dropdown-item" href="#" id="toggleMuteBtn"><i class="far fa-bell-slash me-2"></i>Désactiver les notifications</a></li>
                                    <li><a class="dropdown-item" href="#" id="toggleArchiveBtn"><i class="fas fa-archive me-2"></i>Archiver la conversation</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" id="leaveConversationBtn"><i class="fas fa-sign-out-alt me-2"></i>Quitter la conversation</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Conteneur des messages -->
                    <div class="messages-container" id="messagesContainer">
                        <!-- Indicateur de chargement -->
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Chargement des messages...</p>
                        </div>
                        <!-- Messages chargés dynamiquement par JS -->
                    </div>
                    
                    <!-- Indicateur de frappe -->
                    <div class="typing-indicator" id="typingIndicator" style="display: none;">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="typing-text">Quelqu'un est en train d'écrire...</span>
                    </div>
                    
                    <!-- Zone de composition de message -->
                    <div class="composer">
                        <form id="messageForm">
                            <!-- Prévisualisations des pièces jointes -->
                            <div class="attachments-preview" id="composerAttachments"></div>
                            
                            <!-- Barre d'outils du compositeur -->
                            <div class="composer-toolbar">
                                <button type="button" class="btn-icon" id="attachBtn" title="Joindre un fichier">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                <button type="button" class="btn-icon" id="emojiBtn" title="Émojis">
                                    <i class="far fa-smile"></i>
                                </button>
                            </div>
                            
                            <!-- Zone de saisie du message -->
                            <div class="composer-main">
                                <textarea class="form-control" id="messageInput" placeholder="Écrivez votre message..." rows="1"></textarea>
                                <button type="submit" class="send-btn" title="Envoyer">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nouvelle Conversation -->
    <div class="modal fade" id="newConversationModal" tabindex="-1" aria-labelledby="newConversationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="newConversationModalLabel">
                        <i class="fas fa-comments me-2"></i>Nouvelle conversation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <form id="newConversationForm">
                        <!-- Type de conversation -->
                        <div class="mb-4">
                            <label class="form-label">Type de conversation</label>
                            <div class="conversation-type-selector">
                                <div class="type-option">
                                    <input class="form-check-input" type="radio" name="conversationType" id="typeMessage" value="direct" checked>
                                    <label class="form-check-label" for="typeMessage">
                                        <i class="fas fa-user"></i>
                                        <span>Message direct</span>
                                    </label>
                                </div>
                                <div class="type-option">
                                    <input class="form-check-input" type="radio" name="conversationType" id="typeGroupe" value="groupe">
                                    <label class="form-check-label" for="typeGroupe">
                                        <i class="fas fa-users"></i>
                                        <span>Groupe</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Titre de la conversation (visible uniquement pour les groupes) -->
                        <div class="mb-3 group-only" style="display: none;">
                            <label for="conversationTitle" class="form-label">Titre du groupe</label>
                            <input type="text" class="form-control" id="conversationTitle">
                            <div class="form-text">Donnez un nom à votre groupe</div>
                        </div>
                        
                        <!-- Participants -->
                        <div class="mb-4">
                            <label for="participants" class="form-label">Participants</label>
                            <select class="form-select" id="participants" multiple required>
                                <!-- Options chargées dynamiquement -->
                            </select>
                            <div class="form-text participants-help">Sélectionnez un ou plusieurs participants</div>
                        </div>
                        
                        <!-- Premier message -->
                        <div class="mb-3">
                            <label for="firstMessage" class="form-label">Premier message (optionnel)</label>
                            <textarea class="form-control" id="firstMessage" rows="3" placeholder="Écrivez votre message..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="createConversationBtn">
                        <i class="fas fa-paper-plane me-2"></i>Créer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Informations Conversation -->
    <div class="modal fade" id="conversationInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Informations
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body" id="conversationInfoContent">
                    <!-- Contenu chargé dynamiquement -->
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Initialisation de l'ID utilisateur -->
    <script>
    // Initialiser l'ID utilisateur directement depuis PHP
    var userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
    console.log('ID utilisateur initialisé depuis PHP:', userId);
    </script>
    
    <!-- Script principal de la messagerie -->
    <script src="js/messagerie.js"></script>
    
    <!-- Script de débogage -->
    <script src="js/debug.js"></script>
</body>
</html> 