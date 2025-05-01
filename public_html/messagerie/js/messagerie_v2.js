/**
 * Messagerie v2 - Script principal
 * Interface modernisée
 */

// Variables globales
let currentConversationId = null;
let lastMessageId = null;
let isLoadingMessages = false;
let searchTimeout = null;
let refreshInterval = null;
let darkMode = false;
let selectedEmojis = {};
let mobileMode = false;
let attachments = [];

/**
 * Initialisation
 */
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si on est en mode mobile
    checkMobileMode();
    
    // Initialiser le mode sombre si activé précédemment
    initDarkMode();
    
    // Événements principaux
    initEvents();
    
    // Chargement initial des conversations
    loadConversations();
    
    // Initialiser les modaux
    initModals();
    
    // Initialiser les emojis
    initEmojiPicker();
    
    // Auto-ajustement du champ de texte
    initTextareaAutoResize();
    
    // Définir l'intervalle de rafraîchissement
    refreshInterval = setInterval(refreshData, 10000); // Rafraîchir toutes les 10 secondes
    
    // Ajout de la gestion du redimensionnement pour le mode mobile
    window.addEventListener('resize', handleResize);
    
    console.log('😀 Messagerie v2 initialisée avec interface modernisée');
});

/**
 * Vérifie si nous sommes en mode mobile
 */
function checkMobileMode() {
    mobileMode = window.innerWidth < 992;
    document.body.classList.toggle('mobile-mode', mobileMode);
}

/**
 * Gère le redimensionnement de la fenêtre
 */
function handleResize() {
    checkMobileMode();
    
    // Ajuster la hauteur du conteneur de messages si nécessaire
    if (currentConversationId) {
        setTimeout(() => {
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    }
}

/**
 * Initialise le mode sombre
 */
function initDarkMode() {
    // Récupérer le paramètre stocké
    darkMode = localStorage.getItem('messagerie_dark_mode') === 'true';
    
    // Appliquer le mode
    toggleDarkMode(darkMode);
    
    // Ajouter un bouton dans le menu déroulant
    const dropdownMenu = document.querySelector('.dropdown-menu');
    if (dropdownMenu) {
        const darkModeItem = document.createElement('li');
        darkModeItem.innerHTML = `
            <a class="dropdown-item" href="#" id="darkModeToggle">
                <i class="fas fa-${darkMode ? 'sun' : 'moon'} me-2"></i>
                ${darkMode ? 'Mode clair' : 'Mode sombre'}
            </a>
        `;
        dropdownMenu.appendChild(darkModeItem);
        
        // Événement de toggle
        document.getElementById('darkModeToggle').addEventListener('click', function(e) {
            e.preventDefault();
            toggleDarkMode(!darkMode);
        });
    }
}

/**
 * Active ou désactive le mode sombre
 * @param {boolean} enable Activer le mode sombre
 */
function toggleDarkMode(enable) {
    darkMode = enable;
    document.body.classList.toggle('dark-theme', darkMode);
    localStorage.setItem('messagerie_dark_mode', darkMode);
    
    // Mettre à jour le texte du bouton s'il existe
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        const icon = darkModeToggle.querySelector('i');
        if (icon) {
            icon.className = `fas fa-${darkMode ? 'sun' : 'moon'} me-2`;
        }
        darkModeToggle.innerText = darkMode ? 'Mode clair' : 'Mode sombre';
    }
}

/**
 * Initialise tous les événements
 */
function initEvents() {
    // Boutons pour nouvelle conversation
    document.getElementById('newMessageBtn').addEventListener('click', openNewConversationModal);
    document.getElementById('newChatBtn').addEventListener('click', openNewConversationModal);
    
    // Recherche
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', debounceSearch);
    document.getElementById('searchToggleBtn').addEventListener('click', toggleSearchBar);
    document.getElementById('clearSearchBtn').addEventListener('click', clearSearch);
    
    // Filtres de conversation
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            filterConversations(this.dataset.filter);
        });
    });
    
    // Formulaire de message
    document.getElementById('messageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });
    
    // Emoji picker
    document.getElementById('emojiBtn').addEventListener('click', toggleEmojiPicker);
    document.querySelector('.emoji-picker-close').addEventListener('click', closeEmojiPicker);
    document.querySelectorAll('.emoji-category').forEach(btn => {
        btn.addEventListener('click', function() {
            switchEmojiCategory(this.dataset.category);
        });
    });
    
    // Boutons d'action
    document.getElementById('attachBtn').addEventListener('click', openFileSelector);
    document.getElementById('chatInfoBtn').addEventListener('click', openConversationInfo);
    document.getElementById('searchMessagesBtn').addEventListener('click', openSearchMessagesModal);
    document.getElementById('exportMessagesBtn').addEventListener('click', exportConversation);
    document.getElementById('leaveConversationBtn').addEventListener('click', confirmLeaveConversation);
    document.getElementById('refreshConversationsBtn').addEventListener('click', refreshConversations);
    document.getElementById('markAllReadBtn').addEventListener('click', markAllConversationsAsRead);
    
    // Auto-agrandissement du textarea
    const messageInput = document.getElementById('messageInput');
    messageInput.addEventListener('input', function() {
        autoResizeTextarea(this);
    });
    
    // En mode mobile, ajouter un gestionnaire pour le bouton retour
    if (mobileMode) {
        document.querySelector('.sidebar-back').addEventListener('click', function(e) {
            e.preventDefault();
            toggleMobileSidebar(false);
        });
    }
}

/**
 * Initialise les modaux Bootstrap
 */
function initModals() {
    // Initialiser les événements de modal
    document.getElementById('createConversationBtn').addEventListener('click', createConversation);
    document.getElementById('messageSearchBtn').addEventListener('click', searchInConversation);
}

/**
 * Initialise le sélecteur d'emoji
 */
function initEmojiPicker() {
    // Catégories d'emojis
    const emojiCategories = {
        smileys: ['😀', '😁', '😂', '🤣', '😃', '😄', '😅', '😆', '😉', '😊', '😋', '😎', '😍', '😘', '🥰', '😗', '😙', '😚', '☺️', '🙂', '🤗', '🤩', '🤔', '🤨', '😐', '😑', '😶', '🙄', '😏', '😣', '😥', '😮', '🤐', '😯', '😪', '😫', '😴', '😌', '😛', '😜', '😝', '🤤', '😒', '😓', '😔', '😕', '🙃', '🤑', '😲', '☹️', '🙁', '😖', '😞', '😟', '😤', '😢', '😭', '😦', '😧', '😨', '😩', '🤯', '😬', '😰', '😱', '🥵', '🥶', '😳', '🤪', '😵', '😡', '😠', '🤬', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '😇', '🥳', '🥴', '🥺'],
        people: ['👐', '🙌', '👏', '🤝', '👍', '👎', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🤟', '🤘', '👌', '👈', '👉', '👆', '👇', '☝️', '✋', '🤚', '🖐', '🖖', '👋', '🤙', '💪', '🦵', '🦶', '🖕', '✍️', '🙏', '💍', '💄', '💋', '👄', '👅', '👂', '👃', '👣', '👁', '👀', '🧠', '🗣', '👤', '👥'],
        animals: ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🦝', '🐻', '🐼', '🦘', '🦡', '🐨', '🐯', '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦢', '🦅', '🦉', '🦚', '🦜', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐚', '🐞', '🐜', '🦗'],
        food: ['🍏', '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍈', '🍒', '🍑', '🍍', '🥭', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥒', '🥬', '🌶', '🌽', '🥕', '🥔', '🍠', '🥐', '🍞', '🥖', '🥨', '🥯', '🧀', '🥚', '🍳', '🥞', '🥓', '🥩', '🍗', '🍖', '🌭', '🍔', '🍟', '🍕', '🥪', '🥙', '🌮', '🌯', '🥗'],
        travel: ['🚗', '🚕', '🚙', '🚌', '🚎', '🏎', '🚓', '🚑', '🚒', '🚐', '🚚', '🚛', '🚜', '🛴', '🚲', '🛵', '🏍', '🚨', '🚔', '🚍', '🚘', '🚖', '🚡', '🚠', '🚟', '🚃', '🚋', '🚞', '🚝', '🚄', '🚅', '🚈', '🚂', '🚆', '🚇', '🚊', '🚉', '✈️', '🛫', '🛬', '🛩', '💺', '🛰', '🚀', '🛸', '🚁'],
        activities: ['⚽️', '🏀', '🏈', '⚾️', '🥎', '🏐', '🏉', '🎾', '🥏', '🎱', '🏓', '🏸', '🥅', '🏒', '🏑', '🥍', '🏏', '⛳️', '🏹', '🎣', '🥊', '🥋', '🎽', '⛸', '🥌', '🛷', '🛹', '🎿', '⛷', '🏂', '🏋️‍♀️', '🏋🏻‍♂️', '🤼‍♀️', '🤼‍♂️', '🤸‍♀️', '🤸🏻‍♂️', '⛹️‍♀️', '⛹🏻‍♂️', '🤺', '🤾‍♀️', '🤾‍♂️', '🏌️‍♀️', '🏌🏻‍♂️', '🏇', '🧘‍♀️', '🧘‍♂️', '🏄‍♀️', '🏄‍♂️', '🏊‍♀️', '🏊‍♂️', '🤽‍♀️', '🤽‍♂️', '🚣‍♀️', '🚣‍♂️', '🧗‍♀️', '🧗‍♂️', '🚵‍♀️', '🚵‍♂️', '🚴‍♀️', '🚴‍♂️'],
        objects: ['⌚️', '📱', '📲', '💻', '⌨️', '🖥', '🖨', '🖱', '🖲', '🕹', '🗜', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥', '📽', '🎞', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙', '🎚', '🎛', '⏱', '⏲', '⏰', '🕰', '⌛️', '⏳', '📡', '🔋', '🔌', '💡', '🔦', '🕯', '🧯', '🛢', '💸', '💵', '💴', '💶', '💷', '💰', '💳', '🧾', '💎', '⚖️', '🔧', '🔨', '⚒', '🛠', '⛏', '🔩', '⚙️', '🧱', '⛓', '🧲', '🔫', '💣', '🧨'],
        symbols: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈️', '♉️', '♊️', '♋️', '♌️', '♍️', '♎️', '♏️', '♐️', '♑️', '♒️', '♓️', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴', '📳', '🈶', '🈚️', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️', '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️', '🆘', '❌', '⭕️', '🛑'],
        flags: ['🏳️', '🏴', '🏁', '🚩', '🏳️‍🌈', '🇦🇫', '🇦🇽', '🇦🇱', '🇩🇿', '🇦🇸', '🇦🇩', '🇦🇴', '🇦🇮', '🇦🇶', '🇦🇬', '🇦🇷', '🇦🇲', '🇦🇼', '🇦🇺', '🇦🇹', '🇦🇿', '🇧🇸', '🇧🇭', '🇧🇩', '🇧🇧', '🇧🇾', '🇧🇪', '🇧🇿', '🇧🇯', '🇧🇲', '🇧🇹', '🇧🇴', '🇧🇦', '🇧🇼', '🇧🇷', '🇧🇳', '🇧🇬', '🇧🇫', '🇧🇮', '🇰🇭', '🇨🇲', '🇨🇦', '🇮🇨', '🇨🇻', '🇧🇶', '🇰🇾', '🇨🇫', '🇹🇩', '🇨🇱', '🇨🇳', '🇨🇽', '🇨🇨', '🇨🇴', '🇰🇲', '🇨🇬', '🇨🇩', '🇨🇰', '🇨🇷', '🇨🇮', '🇭🇷', '🇨🇺', '🇨🇼', '🇨🇾', '🇨🇿']
    };
    
    // Remplir le conteneur d'emojis avec la première catégorie
    loadEmojiCategory('smileys');
    
    // Fonction pour charger une catégorie d'emojis
    function loadEmojiCategory(category) {
        const emojiContainer = document.querySelector('.emoji-container');
        emojiContainer.innerHTML = '';
        
        emojiCategories[category].forEach(emoji => {
            const emojiItem = document.createElement('div');
            emojiItem.className = 'emoji-item';
            emojiItem.innerHTML = emoji;
            emojiItem.addEventListener('click', () => insertEmoji(emoji));
            emojiContainer.appendChild(emojiItem);
        });
        
        // Mettre à jour l'onglet actif
        document.querySelectorAll('.emoji-category').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.category === category);
        });
    }
    
    // Fonction pour insérer un emoji dans le message
    window.insertEmoji = function(emoji) {
        const messageInput = document.getElementById('messageInput');
        const startPos = messageInput.selectionStart;
        const endPos = messageInput.selectionEnd;
        const text = messageInput.value;
        
        messageInput.value = text.substring(0, startPos) + emoji + text.substring(endPos);
        
        // Mettre à jour la position du curseur
        messageInput.selectionStart = messageInput.selectionEnd = startPos + emoji.length;
        
        // Donner le focus au champ de texte
        messageInput.focus();
        
        // Déclencher l'événement input pour ajuster la hauteur du textarea
        const event = new Event('input', { bubbles: true });
        messageInput.dispatchEvent(event);
    };
    
    // Fonction pour changer de catégorie d'emoji
    window.switchEmojiCategory = function(category) {
        loadEmojiCategory(category);
    };
}

/**
 * Ajuste automatiquement la hauteur du textarea
 * @param {HTMLTextAreaElement} textarea Element textarea à ajuster
 */
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = (textarea.scrollHeight) + 'px';
}

/**
 * Initialise l'auto-redimensionnement des textareas
 */
function initTextareaAutoResize() {
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.setAttribute('style', 'height:' + (textarea.scrollHeight) + 'px;overflow-y:hidden;');
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
}

/**
 * Bascule l'affichage de la barre de recherche
 */
function toggleSearchBar() {
    const searchContainer = document.getElementById('searchContainer');
    searchContainer.classList.toggle('show');
    
    if (searchContainer.classList.contains('show')) {
        document.getElementById('searchInput').focus();
    }
}

/**
 * Efface la recherche et réinitialise la liste des conversations
 */
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.value = '';
    loadConversations();
}

/**
 * Filtre les conversations selon le type spécifié
 * @param {string} filter Type de filtre (all, unread, direct, group)
 */
function filterConversations(filter) {
    // Mettre à jour l'état actif du bouton
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filter);
    });
    
    // Appliquer le filtre
    const conversations = document.querySelectorAll('.conversation-item');
    
    if (filter === 'all') {
        conversations.forEach(conv => conv.style.display = '');
        return;
    }
    
    conversations.forEach(conv => {
        let show = false;
        
        switch (filter) {
            case 'unread':
                show = conv.querySelector('.unread-badge') !== null;
                break;
            case 'direct':
                show = conv.dataset.type === 'direct';
                break;
            case 'group':
                show = conv.dataset.type === 'groupe';
                break;
        }
        
        conv.style.display = show ? '' : 'none';
    });
}

/**
 * Basculer l'affichage du sélecteur d'emoji
 */
function toggleEmojiPicker() {
    const emojiPicker = document.getElementById('emojiPicker');
    if (emojiPicker.style.display === 'flex') {
        closeEmojiPicker();
    } else {
        emojiPicker.style.display = 'flex';
    }
}

/**
 * Ferme le sélecteur d'emoji
 */
function closeEmojiPicker() {
    document.getElementById('emojiPicker').style.display = 'none';
}

/**
 * Bascule l'affichage de la sidebar en mode mobile
 * @param {boolean} show Afficher ou cacher la sidebar
 */
function toggleMobileSidebar(show) {
    const sidebar = document.querySelector('.messenger-sidebar');
    sidebar.classList.toggle('open', show);
}

/**
 * Charge les conversations
 * @param {string} search Terme de recherche optionnel
 */
function loadConversations(search = '') {
    const conversationsList = document.getElementById('conversationsList');
    
    // Afficher le chargement avec une animation fluide
    conversationsList.innerHTML = `
        <div class="loading-placeholder text-center p-4 fade-in">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mb-0 text-muted">Chargement des conversations...</p>
        </div>
    `;
    
    // Construire l'URL avec le terme de recherche si fourni
    const url = `api/get_conversations.php${search ? '?search=' + encodeURIComponent(search) : ''}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Erreur lors du chargement des conversations');
            }
            
            // Vider le conteneur
            conversationsList.innerHTML = '';
            
            if (data.conversations.length === 0) {
                conversationsList.innerHTML = `
                    <div class="p-4 text-center fade-in">
                        <div class="empty-state-icon mb-3">
                            <i class="far fa-comment-dots"></i>
                        </div>
                        <p class="text-muted">Aucune conversation${search ? ' trouvée' : ''}</p>
                        ${search ? '<button class="btn btn-sm btn-outline-primary mt-2" onclick="clearSearch()">Réinitialiser</button>' : ''}
                    </div>
                `;
                return;
            }
            
            // Afficher les conversations
            data.conversations.forEach((conversation, index) => {
                // Ajouter une légère animation d'entrée avec délai progressif
                setTimeout(() => {
                    renderConversationItem(conversation);
                }, index * 50); // Délai progressif pour l'effet cascade
            });
            
            // Si une conversation est actuellement sélectionnée, la marquer comme active
            setTimeout(() => {
                if (currentConversationId) {
                    const activeItem = document.querySelector(`.conversation-item[data-id="${currentConversationId}"]`);
                    if (activeItem) {
                        activeItem.classList.add('active');
                    }
                }
            }, data.conversations.length * 50 + 100);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des conversations:', error);
            conversationsList.innerHTML = `
                <div class="p-4 text-center">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erreur de chargement
                    </div>
                    <button class="btn btn-sm btn-outline-primary mt-3" onclick="loadConversations()">
                        <i class="fas fa-sync-alt me-1"></i> Réessayer
                    </button>
                </div>
            `;
            showNotification('Erreur: ' + error.message, 'danger');
        });
}

/**
 * Crée et ajoute un élément de conversation au DOM
 * @param {Object} conversation Données de la conversation
 */
function renderConversationItem(conversation) {
    const conversationsList = document.getElementById('conversationsList');
    const now = new Date();
    const messageDate = conversation.date_dernier_message ? new Date(conversation.date_dernier_message) : now;
    
    // Formater la date de façon intelligente
    let dateDisplay = '';
    if (messageDate.toDateString() === now.toDateString()) {
        // Aujourd'hui: afficher l'heure
        dateDisplay = messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    } else if (dateDiffInDays(messageDate, now) === 1) {
        // Hier
        dateDisplay = 'Hier';
    } else if (messageDate.getFullYear() === now.getFullYear()) {
        // Cette année: afficher le jour et le mois
        dateDisplay = messageDate.toLocaleDateString([], {day: 'numeric', month: 'short'});
    } else {
        // Autre année: afficher la date complète
        dateDisplay = messageDate.toLocaleDateString([], {day: 'numeric', month: 'short', year: 'numeric'});
    }
    
    // Badge pour les messages non lus
    const unreadBadge = conversation.unread_count > 0 
        ? `<span class="unread-badge">${conversation.unread_count}</span>` 
        : '';
    
    // Aperçu du dernier message
    let preview = 'Nouvelle conversation';
    if (conversation.dernier_message) {
        preview = conversation.dernier_expediteur ? `${conversation.dernier_expediteur}: ${conversation.dernier_message}` : conversation.dernier_message;
        
        // Tronquer le message s'il est trop long
        if (preview.length > 60) {
            preview = preview.substring(0, 57) + '...';
        }
    }
    
    // Créer l'élément DOM
    const conversationItem = document.createElement('div');
    conversationItem.className = `conversation-item ${currentConversationId == conversation.id ? 'active' : ''} slide-in-up`;
    conversationItem.dataset.id = conversation.id;
    conversationItem.dataset.type = conversation.type || 'direct';
    
    // Retarder légèrement l'animation pour qu'elle soit visible
    setTimeout(() => {
        conversationItem.classList.remove('slide-in-up');
    }, 300);
    
    // Déterminer l'icône selon le type de conversation
    const typeIcon = conversation.type === 'groupe' 
        ? '<i class="fas fa-users text-primary me-2"></i>' 
        : '<i class="fas fa-user text-primary me-2"></i>';
    
    conversationItem.innerHTML = `
        <div class="conversation-meta">
            <span class="conversation-title">${typeIcon}${conversation.titre}</span>
            <span class="conversation-date">${dateDisplay}</span>
        </div>
        <div class="d-flex justify-content-between align-items-start">
            <p class="conversation-preview">${preview}</p>
            ${unreadBadge}
        </div>
    `;
    
    // Ajouter l'événement de clic
    conversationItem.addEventListener('click', () => {
        // En mode mobile, fermer la sidebar lors de la sélection d'une conversation
        if (mobileMode) {
            toggleMobileSidebar(false);
        }
        openConversation(conversation.id);
    });
    
    // Ajouter à la liste
    conversationsList.appendChild(conversationItem);
}

/**
 * Calcule la différence en jours entre deux dates
 * @param {Date} date1 Première date
 * @param {Date} date2 Deuxième date
 * @returns {number} Différence en jours
 */
function dateDiffInDays(date1, date2) {
    const _MS_PER_DAY = 1000 * 60 * 60 * 24;
    // Arrondir les dates pour éviter les problèmes d'heures
    const utc1 = Date.UTC(date1.getFullYear(), date1.getMonth(), date1.getDate());
    const utc2 = Date.UTC(date2.getFullYear(), date2.getMonth(), date2.getDate());
    return Math.floor((utc2 - utc1) / _MS_PER_DAY);
}

/**
 * Ouvre une conversation et charge ses messages
 * @param {number} conversationId ID de la conversation
 */
function openConversation(conversationId) {
    // Si c'est la même conversation et qu'un chargement n'est pas en cours, ne rien faire
    if (conversationId === currentConversationId && !isLoadingMessages) {
        return;
    }
    
    // Mettre à jour l'ID de conversation courante
    currentConversationId = conversationId;
    lastMessageId = null;
    isLoadingMessages = true;
    
    // Mettre à jour l'interface
    document.getElementById('emptyChat').style.display = 'none';
    document.getElementById('activeChat').style.display = 'flex';
    
    // Mettre à jour la conversation active dans la liste
    const conversationItems = document.querySelectorAll('.conversation-item');
    conversationItems.forEach(item => {
        item.classList.toggle('active', item.dataset.id == conversationId);
    });
    
    // Afficher l'indicateur de chargement avec animation
    const messagesContainer = document.getElementById('messagesContainer');
    messagesContainer.innerHTML = `
        <div class="message-loading text-center p-4 fade-in">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Chargement des messages...</span>
            </div>
            <p class="text-muted">Chargement des messages...</p>
        </div>
    `;
    
    // Récupérer les messages
    fetch(`api/get_messages.php?conversation_id=${conversationId}&_=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Erreur lors du chargement des messages');
            }
            
            // Mettre à jour les infos de la conversation
            updateConversationHeader(data.conversation);
            
            // Afficher les messages
            renderMessages(data.messages);
            
            // Marquer la conversation comme lue
            markConversationAsRead(conversationId);
            
            // Mettre à jour le dernier ID de message
            if (data.messages && data.messages.length > 0) {
                lastMessageId = Math.max(...data.messages.map(m => parseInt(m.id)));
            }
            
            isLoadingMessages = false;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des messages:', error);
            messagesContainer.innerHTML = `
                <div class="p-4 text-center">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${error.message}
                    </div>
                    <button class="btn btn-outline-primary mt-3" onclick="openConversation(${conversationId})">
                        <i class="fas fa-sync-alt me-2"></i> Réessayer
                    </button>
                </div>
            `;
            isLoadingMessages = false;
            showNotification('Erreur: ' + error.message, 'danger');
        });
}

/**
 * Marque une conversation comme lue
 * @param {number} conversationId ID de la conversation
 */
function markConversationAsRead(conversationId) {
    // Mettre à jour l'interface immédiatement
    const conversationItem = document.querySelector(`.conversation-item[data-id="${conversationId}"]`);
    if (conversationItem) {
        const unreadBadge = conversationItem.querySelector('.unread-badge');
        if (unreadBadge) {
            unreadBadge.remove();
        }
    }
    
    // Envoyer la requête au serveur
    fetch(`api/mark_as_read.php?conversation_id=${conversationId}`)
        .catch(error => {
            console.error('Erreur lors du marquage comme lu:', error);
        });
}

/**
 * Marque toutes les conversations comme lues
 */
function markAllConversationsAsRead() {
    // Mettre à jour l'interface immédiatement
    document.querySelectorAll('.unread-badge').forEach(badge => {
        badge.remove();
    });
    
    // Envoyer la requête au serveur
    fetch('api/mark_all_as_read.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Toutes les conversations ont été marquées comme lues', 'success');
            }
        })
        .catch(error => {
            console.error('Erreur lors du marquage comme lu:', error);
            showNotification('Erreur lors du marquage des conversations', 'danger');
        });
}

/**
 * Met à jour les informations d'en-tête de la conversation
 * @param {Object} conversation Données de la conversation
 */
function updateConversationHeader(conversation) {
    if (!conversation) return;
    
    // Mettre à jour le titre
    const titleElement = document.getElementById('activeChatTitle');
    if (titleElement) {
        titleElement.textContent = conversation.titre || 'Conversation';
    }
    
    // Mettre à jour la liste des participants
    const participantsElement = document.getElementById('activeChatParticipants');
    if (participantsElement && conversation.participants) {
        const participantNames = conversation.participants
            .map(p => p.full_name)
            .join(', ');
        participantsElement.textContent = participantNames;
    }
}

/**
 * Affiche les messages dans le conteneur
 * @param {Array} messages Liste des messages à afficher
 */
function renderMessages(messages) {
    const messagesContainer = document.getElementById('messagesContainer');
    
    // Vider le conteneur
    messagesContainer.innerHTML = '';
    
    // Si pas de messages, afficher un message approprié
    if (!messages || messages.length === 0) {
        messagesContainer.innerHTML = `
            <div class="p-4 text-center fade-in">
                <div class="empty-state-icon mb-3">
                    <i class="far fa-comment-alt"></i>
                </div>
                <p class="text-muted">Aucun message dans cette conversation</p>
                <p class="small text-muted">Soyez le premier à écrire un message !</p>
            </div>
        `;
        return;
    }
    
    // Grouper les messages par date
    const messagesByDate = groupMessagesByDate(messages);
    
    // Parcourir chaque groupe de date
    Object.keys(messagesByDate).forEach(date => {
        // Ajouter un séparateur de date
        messagesContainer.appendChild(createDateSeparator(date));
        
        // Ajouter les messages de cette date
        messagesByDate[date].forEach(message => {
            messagesContainer.appendChild(createMessageElement(message));
        });
    });
    
    // Faire défiler jusqu'au dernier message
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
}

/**
 * Groupe les messages par date
 * @param {Array} messages Liste des messages
 * @returns {Object} Messages groupés par date
 */
function groupMessagesByDate(messages) {
    const groups = {};
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    const formatDay = (date) => {
        const messageDate = new Date(date);
        
        if (messageDate.toDateString() === today.toDateString()) {
            return "Aujourd'hui";
        } else if (messageDate.toDateString() === yesterday.toDateString()) {
            return "Hier";
        } else {
            return messageDate.toLocaleDateString('fr-FR', { 
                weekday: 'long', 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric' 
            });
        }
    };
    
    messages.forEach(message => {
        const date = message.date || formatDay(message.date_envoi);
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(message);
    });
    
    return groups;
}

/**
 * Crée un élément séparateur de date
 * @param {string} date Date à afficher
 * @returns {HTMLElement} Élément DOM du séparateur
 */
function createDateSeparator(date) {
    const separator = document.createElement('div');
    separator.className = 'message-date-separator';
    separator.innerHTML = `<span>${date}</span>`;
    return separator;
}

/**
 * Crée un élément de message
 * @param {Object} message Données du message
 * @returns {HTMLElement} Élément DOM du message
 */
function createMessageElement(message) {
    const isMine = message.is_mine;
    const messageElement = document.createElement('div');
    messageElement.className = `message ${isMine ? 'sent' : 'received'} fade-in`;
    messageElement.dataset.id = message.id;
    
    // Retarder légèrement l'animation
    setTimeout(() => {
        messageElement.classList.remove('fade-in');
    }, 300);
    
    // Contenu du message selon le type
    let messageContent = '';
    
    if (message.type === 'fichier') {
        // Message avec fichier joint
        messageContent = createAttachmentContent(message);
    } else {
        // Enrichir le texte (emojis, liens, etc.)
        let richContent = message.contenu;
        
        // Convertir les URLs en liens cliquables
        richContent = richContent.replace(
            /(https?:\/\/[^\s]+)/g, 
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
        );
        
        // Message texte simple
        messageContent = `<p class="message-text">${richContent}</p>`;
    }
    
    // Ajouter les informations sur le statut du message si c'est le mien
    let statusIndicator = '';
    if (isMine) {
        const statusIcon = message.is_read 
            ? '<i class="fas fa-check-double text-primary"></i>' 
            : (message.is_delivered ? '<i class="fas fa-check"></i>' : '<i class="far fa-clock"></i>');
        
        statusIndicator = `
            <div class="message-status">
                ${statusIcon}
            </div>
        `;
    }
    
    messageElement.innerHTML = `
        <div class="message-bubble">
            ${!isMine ? `<div class="message-sender">${message.sender_name}</div>` : ''}
            ${messageContent}
            ${statusIndicator}
        </div>
        <div class="message-time">${message.time || formatTime(message.date_envoi)}</div>
    `;
    
    // Ajouter un menu contextuel
    messageElement.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        showMessageContextMenu(e, message);
    });
    
    return messageElement;
}

/**
 * Affiche un menu contextuel pour un message
 * @param {Event} event Événement de clic droit
 * @param {Object} message Données du message
 */
function showMessageContextMenu(event, message) {
    // Supprimer tout menu contextuel existant
    const existingMenu = document.querySelector('.message-context-menu');
    if (existingMenu) {
        existingMenu.remove();
    }
    
    // Créer le menu
    const contextMenu = document.createElement('div');
    contextMenu.className = 'message-context-menu';
    contextMenu.style.position = 'absolute';
    contextMenu.style.top = `${event.pageY}px`;
    contextMenu.style.left = `${event.pageX}px`;
    contextMenu.style.zIndex = '1000';
    contextMenu.style.backgroundColor = 'white';
    contextMenu.style.boxShadow = 'var(--box-shadow)';
    contextMenu.style.borderRadius = '8px';
    contextMenu.style.padding = '8px 0';
    contextMenu.style.minWidth = '180px';
    
    // Options du menu
    const options = [
        { icon: 'fas fa-reply', text: 'Répondre', action: () => replyToMessage(message) },
        { icon: 'fas fa-copy', text: 'Copier', action: () => copyMessageText(message) }
    ];
    
    // Ajouter l'option de suppression si c'est mon message
    if (message.is_mine) {
        options.push({ 
            icon: 'fas fa-trash', 
            text: 'Supprimer', 
            action: () => confirmDeleteMessage(message),
            danger: true
        });
    }
    
    // Créer les éléments du menu
    options.forEach(option => {
        const menuItem = document.createElement('div');
        menuItem.className = 'message-context-menu-item';
        menuItem.style.padding = '8px 16px';
        menuItem.style.cursor = 'pointer';
        menuItem.style.display = 'flex';
        menuItem.style.alignItems = 'center';
        menuItem.style.color = option.danger ? 'var(--danger-color)' : 'var(--text-color)';
        
        menuItem.innerHTML = `
            <i class="${option.icon} me-2"></i>
            <span>${option.text}</span>
        `;
        
        menuItem.addEventListener('click', () => {
            contextMenu.remove();
            option.action();
        });
        
        menuItem.addEventListener('mouseover', () => {
            menuItem.style.backgroundColor = 'rgba(0,0,0,0.05)';
        });
        
        menuItem.addEventListener('mouseout', () => {
            menuItem.style.backgroundColor = 'transparent';
        });
        
        contextMenu.appendChild(menuItem);
    });
    
    // Ajouter au document
    document.body.appendChild(contextMenu);
    
    // Fermer le menu lors d'un clic ailleurs
    document.addEventListener('click', function closeMenu() {
        contextMenu.remove();
        document.removeEventListener('click', closeMenu);
    });
}

/**
 * Prépare une réponse à un message
 * @param {Object} message Message auquel répondre
 */
function replyToMessage(message) {
    // À implémenter si nécessaire
    showNotification('Fonctionnalité de réponse à venir', 'info');
}

/**
 * Copie le texte d'un message dans le presse-papier
 * @param {Object} message Message à copier
 */
function copyMessageText(message) {
    const text = message.contenu || '';
    navigator.clipboard.writeText(text)
        .then(() => {
            showNotification('Texte copié dans le presse-papier', 'success');
        })
        .catch(error => {
            console.error('Erreur de copie:', error);
            showNotification('Impossible de copier le texte', 'danger');
        });
}

/**
 * Demande confirmation pour supprimer un message
 * @param {Object} message Message à supprimer
 */
function confirmDeleteMessage(message) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        deleteMessage(message.id);
    }
}

/**
 * Supprime un message
 * @param {number} messageId ID du message à supprimer
 */
function deleteMessage(messageId) {
    // Mettre à jour l'interface immédiatement
    const messageElement = document.querySelector(`.message[data-id="${messageId}"]`);
    if (messageElement) {
        messageElement.classList.add('deleting');
        messageElement.style.opacity = '0.5';
    }
    
    // Envoyer la requête au serveur
    fetch(`api/delete_message.php?message_id=${messageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (messageElement) {
                    messageElement.remove();
                }
                showNotification('Message supprimé', 'success');
            } else {
                // Restaurer l'apparence du message
                if (messageElement) {
                    messageElement.classList.remove('deleting');
                    messageElement.style.opacity = '1';
                }
                throw new Error(data.message || 'Erreur lors de la suppression du message');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression du message:', error);
            showNotification('Erreur: ' + error.message, 'danger');
        });
}

/**
 * Crée le contenu HTML pour un message avec pièce jointe
 * @param {Object} message Données du message
 * @returns {string} HTML du contenu du message
 */
function createAttachmentContent(message) {
    const isImage = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(message.fichier_type);
    
    if (isImage) {
        return `
            <div class="message-attachment">
                <img src="${message.fichier_url}" alt="${message.fichier_nom}" class="img-fluid">
            </div>
            ${message.contenu ? `<p class="message-text">${message.contenu}</p>` : ''}
            <div class="attachment-info">
                <div class="attachment-details">
                    <div class="attachment-name">${message.fichier_nom}</div>
                </div>
                <a href="${message.fichier_url}" class="attachment-action" download title="Télécharger">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        `;
    } else {
        // Déterminer l'icône en fonction du type de fichier
        let fileIcon = 'fas fa-file';
        
        if (message.fichier_type) {
            if (message.fichier_type.includes('pdf')) {
                fileIcon = 'fas fa-file-pdf';
            } else if (message.fichier_type.includes('word') || message.fichier_type.includes('document')) {
                fileIcon = 'fas fa-file-word';
            } else if (message.fichier_type.includes('excel') || message.fichier_type.includes('sheet')) {
                fileIcon = 'fas fa-file-excel';
            } else if (message.fichier_type.includes('zip') || message.fichier_type.includes('archive') || message.fichier_type.includes('rar')) {
                fileIcon = 'fas fa-file-archive';
            } else if (message.fichier_type.includes('audio')) {
                fileIcon = 'fas fa-file-audio';
            } else if (message.fichier_type.includes('video')) {
                fileIcon = 'fas fa-file-video';
            } else if (message.fichier_type.includes('text')) {
                fileIcon = 'fas fa-file-alt';
            } else if (message.fichier_type.includes('code') || message.fichier_nom.match(/\.(js|php|html|css|py|java|c|cpp|rb|go)$/)) {
                fileIcon = 'fas fa-file-code';
            }
        }
        
        // Fichier non image
        return `
            ${message.contenu ? `<p class="message-text">${message.contenu}</p>` : ''}
            <div class="attachment-info">
                <div class="attachment-icon">
                    <i class="${fileIcon}"></i>
                </div>
                <div class="attachment-details">
                    <div class="attachment-name">${message.fichier_nom}</div>
                    <div class="attachment-size">${formatFileSize(message.fichier_taille)}</div>
                </div>
                <a href="${message.fichier_url}" class="attachment-action" download title="Télécharger">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        `;
    }
}

/**
 * Envoie un message
 */
function sendMessage() {
    if (!currentConversationId) {
        showNotification('Aucune conversation sélectionnée', 'warning');
        return;
    }
    
    const messageInput = document.getElementById('messageInput');
    const contenu = messageInput.value.trim();
    
    // Vérifier s'il y a un message ou des pièces jointes
    if (!contenu && attachments.length === 0) {
        showNotification('Veuillez saisir un message ou joindre un fichier', 'warning');
        return;
    }
    
    // Désactiver le bouton d'envoi
    const sendButton = document.querySelector('#messageForm .send-btn');
    if (sendButton) sendButton.disabled = true;
    
    // Préparer les données
    const formData = new FormData();
    formData.append('conversation_id', currentConversationId);
    formData.append('contenu', contenu);
    
    // Ajouter les pièces jointes s'il y en a
    if (attachments.length > 0) {
        attachments.forEach(file => {
            formData.append('fichiers[]', file);
        });
    }
    
    // Message temporaire pour l'interface
    const tempId = `temp-${Date.now()}`;
    const tempMessage = {
        id: tempId,
        is_mine: true,
        contenu: contenu,
        sender_name: 'Vous',
        date: "Aujourd'hui",
        time: formatTime(new Date()),
        sending: true
    };
    
    // Prévisualisation des pièces jointes
    if (attachments.length > 0) {
        tempMessage.attachments = attachments.map(file => ({
            name: file.name,
            size: file.size,
            type: file.type
        }));
    }
    
    appendTempMessage(tempMessage);
    
    // Vider le champ de saisie et les pièces jointes
    messageInput.value = '';
    clearAttachments();
    
    // Réinitialiser la hauteur du textarea
    autoResizeTextarea(messageInput);
    
    // Envoyer le message
    fetch('api/send_message.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Réactiver le bouton d'envoi
            if (sendButton) sendButton.disabled = false;
            
            if (!data.success) {
                throw new Error(data.message || 'Erreur lors de l\'envoi du message');
            }
            
            // Remplacer le message temporaire par le message réel
            replaceTempMessage(tempId, data.message);
            
            // Mettre à jour le dernier ID de message
            if (data.message) {
                lastMessageId = parseInt(data.message.id);
            }
            
            // Rafraîchir la liste des conversations pour mettre à jour l'aperçu
            refreshConversations();
        })
        .catch(error => {
            // Réactiver le bouton d'envoi
            if (sendButton) sendButton.disabled = false;
            
            console.error('Erreur lors de l\'envoi du message:', error);
            showNotification('Erreur: ' + error.message, 'danger');
            
            // Marquer le message temporaire comme échoué
            markTempMessageAsFailed(tempId);
        });
}

/**
 * Ajoute un message temporaire au conteneur de messages
 * @param {Object} message Données du message temporaire
 */
function appendTempMessage(message) {
    const messagesContainer = document.getElementById('messagesContainer');
    
    // Vérifier si un séparateur pour aujourd'hui existe déjà
    const today = "Aujourd'hui";
    let dateSection = document.querySelector(`.message-date-separator span:contains('${today}')`);
    
    // Si le séparateur n'existe pas, le créer
    if (!dateSection) {
        const separator = createDateSeparator(today);
        messagesContainer.appendChild(separator);
    }
    
    // Créer l'élément de message
    const messageElement = document.createElement('div');
    messageElement.className = 'message sent temp-message fade-in';
    messageElement.dataset.id = message.id;
    
    let attachmentPreview = '';
    
    // Si le message a des pièces jointes, les prévisualiser
    if (message.attachments && message.attachments.length > 0) {
        message.attachments.forEach(attachment => {
            const isImage = attachment.type.startsWith('image/');
            
            if (isImage) {
                // Prévisualisation pour les images
                const imageUrl = URL.createObjectURL(attachment);
                attachmentPreview += `
                    <div class="message-attachment">
                        <img src="${imageUrl}" alt="${attachment.name}" class="img-fluid">
                    </div>
                `;
            } else {
                // Icône de fichier générique
                attachmentPreview += `
                    <div class="attachment-info">
                        <div class="attachment-icon">
                            <i class="fas fa-file"></i>
                        </div>
                        <div class="attachment-details">
                            <div class="attachment-name">${attachment.name}</div>
                            <div class="attachment-size">${formatFileSize(attachment.size)}</div>
                        </div>
                    </div>
                `;
            }
        });
    }
    
    messageElement.innerHTML = `
        <div class="message-bubble">
            ${attachmentPreview}
            ${message.contenu ? `<p class="message-text">${message.contenu}</p>` : ''}
            ${message.sending ? '<div class="message-status"><i class="fas fa-circle-notch fa-spin"></i></div>' : ''}
        </div>
        <div class="message-time">${message.time}</div>
    `;
    
    // Ajouter au conteneur
    messagesContainer.appendChild(messageElement);
    
    // Faire défiler jusqu'au dernier message
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Remplace un message temporaire par le message réel
 * @param {string} tempId ID du message temporaire
 * @param {Object} realMessage Données du message réel
 */
function replaceTempMessage(tempId, realMessage) {
    const tempElement = document.querySelector(`.message[data-id="${tempId}"]`);
    if (!tempElement) return;
    
    // Créer le nouvel élément
    const newElement = createMessageElement(realMessage);
    
    // Remplacer l'ancien élément
    tempElement.parentNode.replaceChild(newElement, tempElement);
}

/**
 * Marque un message temporaire comme échoué
 * @param {string} tempId ID du message temporaire
 */
function markTempMessageAsFailed(tempId) {
    const tempElement = document.querySelector(`.message[data-id="${tempId}"]`);
    if (!tempElement) return;
    
    // Ajouter la classe d'erreur
    tempElement.classList.add('failed');
    
    // Remplacer l'indicateur de chargement par un indicateur d'erreur
    const statusElement = tempElement.querySelector('.message-status');
    if (statusElement) {
        statusElement.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i>';
    }
    
    // Ajouter un bouton pour réessayer
    const bubble = tempElement.querySelector('.message-bubble');
    const retryBtn = document.createElement('button');
    retryBtn.className = 'btn btn-sm btn-outline-danger mt-2';
    retryBtn.innerHTML = '<i class="fas fa-redo me-1"></i> Réessayer';
    retryBtn.addEventListener('click', function() {
        tempElement.remove();
        const messageInput = document.getElementById('messageInput');
        messageInput.value = tempElement.querySelector('.message-text')?.textContent || '';
        messageInput.focus();
    });
    
    bubble.appendChild(retryBtn);
}

/**
 * Ouvre le sélecteur de fichiers pour l'upload
 */
function openFileSelector() {
    if (!currentConversationId) {
        showNotification('Veuillez d\'abord sélectionner une conversation', 'warning');
        return;
    }
    
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.multiple = true;
    fileInput.accept = '*/*';
    fileInput.style.display = 'none';
    document.body.appendChild(fileInput);
    
    fileInput.addEventListener('change', function() {
        if (fileInput.files && fileInput.files.length > 0) {
            handleSelectedFiles(fileInput.files);
        }
        document.body.removeChild(fileInput);
    });
    
    fileInput.click();
}

/**
 * Gère les fichiers sélectionnés par l'utilisateur
 * @param {FileList} files Liste des fichiers sélectionnés
 */
function handleSelectedFiles(files) {
    // Vérifier la taille totale des fichiers (max 20 Mo)
    const maxTotalSize = 20 * 1024 * 1024;
    let totalSize = 0;
    
    // Ajouter les fichiers à la liste des pièces jointes
    for (let i = 0; i < files.length; i++) {
        totalSize += files[i].size;
        
        if (totalSize > maxTotalSize) {
            showNotification('La taille totale des fichiers ne doit pas dépasser 20 Mo', 'warning');
            return;
        }
        
        attachments.push(files[i]);
    }
    
    // Mettre à jour la prévisualisation des pièces jointes
    updateAttachmentPreviews();
}

/**
 * Met à jour l'affichage des pièces jointes
 */
function updateAttachmentPreviews() {
    const previewContainer = document.getElementById('composerAttachments');
    previewContainer.innerHTML = '';
    
    if (attachments.length === 0) {
        previewContainer.style.display = 'none';
        return;
    }
    
    previewContainer.style.display = 'flex';
    
    attachments.forEach((file, index) => {
        const isImage = file.type.startsWith('image/');
        const previewElement = document.createElement('div');
        previewElement.className = 'attachment-preview';
        
        if (isImage) {
            // Prévisualisation pour les images
            const imageUrl = URL.createObjectURL(file);
            previewElement.innerHTML = `
                <img src="${imageUrl}" alt="${file.name}">
                <button type="button" class="attachment-preview-remove" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
        } else {
            // Icône pour les autres types de fichiers
            let fileIcon = 'fas fa-file';
            
            if (file.type.includes('pdf')) {
                fileIcon = 'fas fa-file-pdf';
            } else if (file.type.includes('word') || file.type.includes('document')) {
                fileIcon = 'fas fa-file-word';
            } else if (file.type.includes('excel') || file.type.includes('sheet')) {
                fileIcon = 'fas fa-file-excel';
            } else if (file.type.includes('zip') || file.type.includes('archive')) {
                fileIcon = 'fas fa-file-archive';
            } else if (file.type.includes('audio')) {
                fileIcon = 'fas fa-file-audio';
            } else if (file.type.includes('video')) {
                fileIcon = 'fas fa-file-video';
            }
            
            previewElement.innerHTML = `
                <div class="file-icon">
                    <i class="${fileIcon} fa-2x"></i>
                    <span class="file-ext">${getFileExtension(file.name)}</span>
                </div>
                <button type="button" class="attachment-preview-remove" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
        }
        
        previewContainer.appendChild(previewElement);
    });
    
    // Ajouter les écouteurs d'événements pour les boutons de suppression
    document.querySelectorAll('.attachment-preview-remove').forEach(button => {
        button.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            removeAttachment(index);
        });
    });
}

/**
 * Supprime une pièce jointe
 * @param {number} index Index de la pièce jointe à supprimer
 */
function removeAttachment(index) {
    if (index >= 0 && index < attachments.length) {
        attachments.splice(index, 1);
        updateAttachmentPreviews();
    }
}

/**
 * Efface toutes les pièces jointes
 */
function clearAttachments() {
    attachments = [];
    updateAttachmentPreviews();
}

/**
 * Récupère l'extension d'un fichier
 * @param {string} filename Nom du fichier
 * @returns {string} Extension du fichier
 */
function getFileExtension(filename) {
    return filename.split('.').pop().toUpperCase();
}

/**
 * Exporte la conversation actuelle
 */
function exportConversation() {
    if (!currentConversationId) {
        showNotification('Aucune conversation sélectionnée', 'warning');
        return;
    }
    
    // Montrer une notification de chargement
    showNotification('Préparation de l\'export...', 'info');
    
    fetch(`api/export_conversation.php?conversation_id=${currentConversationId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Erreur lors de l\'export');
            }
            
            // Créer un lien de téléchargement
            const link = document.createElement('a');
            link.href = data.file_url;
            link.download = data.filename || 'conversation.txt';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showNotification('Export réussi', 'success');
        })
        .catch(error => {
            console.error('Erreur lors de l\'export:', error);
            showNotification('Erreur: ' + error.message, 'danger');
        });
}

/**
 * Confirmation avant de quitter une conversation
 */
function confirmLeaveConversation() {
    if (!currentConversationId) {
        showNotification('Aucune conversation sélectionnée', 'warning');
        return;
    }
    
    if (confirm('Êtes-vous sûr de vouloir quitter cette conversation ? Vous ne recevrez plus de messages de cette conversation.')) {
        leaveConversation();
    }
}

/**
 * Quitte une conversation
 */
function leaveConversation() {
    if (!currentConversationId) return;
    
    fetch(`api/leave_conversation.php?conversation_id=${currentConversationId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Erreur lors de la sortie de la conversation');
            }
            
            // Supprimer la conversation de la liste
            const conversationItem = document.querySelector(`.conversation-item[data-id="${currentConversationId}"]`);
            if (conversationItem) {
                conversationItem.remove();
            }
            
            // Réinitialiser l'interface
            currentConversationId = null;
            document.getElementById('activeChat').style.display = 'none';
            document.getElementById('emptyChat').style.display = 'flex';
            
            showNotification('Vous avez quitté la conversation', 'success');
        })
        .catch(error => {
            console.error('Erreur lors de la sortie de la conversation:', error);
            showNotification('Erreur: ' + error.message, 'danger');
        });
}

/**
 * Ouvre la modal de recherche dans les messages
 */
function openSearchMessagesModal() {
    if (!currentConversationId) {
        showNotification('Aucune conversation sélectionnée', 'warning');
        return;
    }
    
    // Vider les résultats précédents
    document.getElementById('messageSearchResults').innerHTML = '';
    document.getElementById('messageSearchInput').value = '';
    
    // Ouvrir la modal
    const modal = new bootstrap.Modal(document.getElementById('searchMessagesModal'));
    modal.show();
    
    // Donner le focus au champ de recherche
    setTimeout(() => {
        document.getElementById('messageSearchInput').focus();
    }, 500);
}

/**
 * Recherche dans les messages de la conversation actuelle
 */
function searchInConversation() {
    if (!currentConversationId) return;
    
    const searchTerm = document.getElementById('messageSearchInput').value.trim();
    if (!searchTerm) {
        showNotification('Veuillez saisir un terme de recherche', 'warning');
        return;
    }
    
    const resultsContainer = document.getElementById('messageSearchResults');
    resultsContainer.innerHTML = `
        <div class="text-center p-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Recherche...</span>
            </div>
            <p class="mb-0 mt-2">Recherche en cours...</p>
        </div>
    `;
    
    fetch(`api/search_messages.php?conversation_id=${currentConversationId}&search=${encodeURIComponent(searchTerm)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Erreur lors de la recherche');
            }
            
            if (data.results.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="text-center p-3">
                        <p class="text-muted mb-0">Aucun résultat trouvé pour "${searchTerm}"</p>
                    </div>
                `;
                return;
            }
            
            // Afficher les résultats
            resultsContainer.innerHTML = '';
            
            data.results.forEach(result => {
                const resultElement = document.createElement('div');
                resultElement.className = 'search-result-item';
                
                // Mettre en évidence le terme recherché
                let highlightedContent = result.contenu.replace(
                    new RegExp(searchTerm, 'gi'),
                    match => `<span class="search-result-match">${match}</span>`
                );
                
                resultElement.innerHTML = `
                    <div class="search-result-meta">
                        <span class="search-result-sender">${result.sender_name}</span>
                        <span class="search-result-date">${formatDate(result.date_envoi)} ${formatTime(result.date_envoi)}</span>
                    </div>
                    <div class="search-result-content">${highlightedContent}</div>
                `;
                
                resultElement.addEventListener('click', function() {
                    // Fermer la modal
                    bootstrap.Modal.getInstance(document.getElementById('searchMessagesModal')).hide();
                    
                    // Mettre en évidence le message dans la conversation
                    highlightMessage(result.id);
                });
                
                resultsContainer.appendChild(resultElement);
            });
        })
        .catch(error => {
            console.error('Erreur lors de la recherche:', error);
            resultsContainer.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erreur: ${error.message}
                </div>
            `;
        });
}

/**
 * Met en évidence un message dans la conversation
 * @param {number} messageId ID du message à mettre en évidence
 */
function highlightMessage(messageId) {
    const messageElement = document.querySelector(`.message[data-id="${messageId}"]`);
    if (!messageElement) return;
    
    // Faire défiler jusqu'au message
    messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Ajouter une classe pour mettre en évidence le message
    messageElement.classList.add('highlight-message');
    
    // Retirer la mise en évidence après un délai
    setTimeout(() => {
        messageElement.classList.remove('highlight-message');
    }, 3000);
}

/**
 * Formatte une date pour l'affichage
 * @param {string|Date} date Date à formater
 * @returns {string} Date formatée
 */
function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString('fr-FR');
}

/**
 * Formatte une heure pour l'affichage
 * @param {string|Date} date Date dont on veut l'heure
 * @returns {string} Heure formatée
 */
function formatTime(date) {
    const d = new Date(date);
    return d.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
}

/**
 * Formatte une taille de fichier
 * @param {number} bytes Taille en octets
 * @returns {string} Taille formatée
 */
function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }
    
    return `${bytes.toFixed(1)} ${units[i]}`;
}

/**
 * Rafraîchit les données actuelles (conversations, messages)
 */
function refreshData() {
    // Vérifier les nouvelles conversations ou mises à jour
    refreshConversations();
    
    // Si une conversation est ouverte, vérifier les nouveaux messages
    if (currentConversationId && lastMessageId) {
        checkNewMessages();
    }
}

/**
 * Rafraîchit la liste des conversations
 * @param {number} highlightId ID de la conversation à mettre en évidence (optionnel)
 */
function refreshConversations(highlightId = null) {
    // Ne pas regénérer toute la liste, juste mettre à jour les conversations existantes
    fetch('api/get_conversations.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Erreur lors du chargement des conversations');
            }
            
            // Map pour accéder rapidement aux conversations existantes
            const existingConversations = {};
            document.querySelectorAll('.conversation-item').forEach(item => {
                existingConversations[item.dataset.id] = item;
            });
            
            // Map pour les nouvelles données
            const conversationsMap = {};
            data.conversations.forEach(conv => {
                conversationsMap[conv.id] = conv;
            });
            
            // Mettre à jour les conversations existantes
            for (const id in existingConversations) {
                const item = existingConversations[id];
                const conv = conversationsMap[id];
                
                if (conv) {
                    // Mettre à jour la conversation existante
                    updateConversationItem(item, conv);
                } else {
                    // Conversation supprimée
                    item.remove();
                }
                
                // Retirer de la map pour ne pas la recréer
                delete conversationsMap[id];
            }
            
            // Ajouter les nouvelles conversations
            const conversationsList = document.getElementById('conversationsList');
            for (const id in conversationsMap) {
                const conv = conversationsMap[id];
                renderConversationItem(conv);
            }
            
            // Mettre en évidence une conversation si demandé
            if (highlightId) {
                const highlightItem = document.querySelector(`.conversation-item[data-id="${highlightId}"]`);
                if (highlightItem) {
                    highlightItem.classList.add('pulse');
                    setTimeout(() => {
                        highlightItem.classList.remove('pulse');
                    }, 2000);
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors du rafraîchissement des conversations:', error);
        });
}

/**
 * Met à jour un élément de conversation existant
 * @param {HTMLElement} item Élément DOM de la conversation
 * @param {Object} data Nouvelles données de la conversation
 */
function updateConversationItem(item, data) {
    const now = new Date();
    const messageDate = data.date_dernier_message ? new Date(data.date_dernier_message) : now;
    
    // Formater la date
    let dateDisplay = '';
    if (messageDate.toDateString() === now.toDateString()) {
        dateDisplay = messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    } else if (dateDiffInDays(messageDate, now) === 1) {
        dateDisplay = 'Hier';
    } else if (messageDate.getFullYear() === now.getFullYear()) {
        dateDisplay = messageDate.toLocaleDateString([], {day: 'numeric', month: 'short'});
    } else {
        dateDisplay = messageDate.toLocaleDateString([], {day: 'numeric', month: 'short', year: 'numeric'});
    }
    
    // Mettre à jour la date
    const dateElement = item.querySelector('.conversation-date');
    if (dateElement) {
        dateElement.textContent = dateDisplay;
    }
    
    // Mettre à jour l'aperçu du message
    let preview = 'Nouvelle conversation';
    if (data.dernier_message) {
        preview = data.dernier_expediteur ? `${data.dernier_expediteur}: ${data.dernier_message}` : data.dernier_message;
        
        // Tronquer le message s'il est trop long
        if (preview.length > 60) {
            preview = preview.substring(0, 57) + '...';
        }
    }
    
    const previewElement = item.querySelector('.conversation-preview');
    if (previewElement) {
        previewElement.textContent = preview;
    }
    
    // Mettre à jour le badge de messages non lus
    const existingBadge = item.querySelector('.unread-badge');
    
    if (data.unread_count > 0) {
        if (existingBadge) {
            existingBadge.textContent = data.unread_count;
        } else {
            const previewContainer = item.querySelector('.d-flex');
            const badge = document.createElement('span');
            badge.className = 'unread-badge';
            badge.textContent = data.unread_count;
            previewContainer.appendChild(badge);
        }
    } else if (existingBadge) {
        existingBadge.remove();
    }
}

/**
 * Vérifie s'il y a de nouveaux messages
 */
function checkNewMessages() {
    if (!currentConversationId || !lastMessageId || isLoadingMessages) {
        return;
    }
    
    fetch(`api/get_new_messages.php?conversation_id=${currentConversationId}&last_id=${lastMessageId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                // Ajouter les nouveaux messages
                appendNewMessages(data.messages);
                
                // Mettre à jour le dernier ID
                lastMessageId = Math.max(...data.messages.map(m => parseInt(m.id)));
                
                // Jouer un son de notification pour les messages reçus
                playNotificationSound();
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification des nouveaux messages:', error);
        });
}

/**
 * Joue un son de notification
 */
function playNotificationSound() {
    // Vérifier si les sons sont activés dans les préférences
    const soundsEnabled = localStorage.getItem('messagerie_sounds') !== 'false';
    if (!soundsEnabled) return;
    
    try {
        const audio = new Audio('assets/sounds/notification.mp3');
        audio.volume = 0.5;
        audio.play();
    } catch (error) {
        console.error('Erreur lors de la lecture du son:', error);
    }
}

/**
 * Ajoute de nouveaux messages au conteneur existant
 * @param {Array} messages Liste des nouveaux messages
 */
function appendNewMessages(messages) {
    if (!messages || messages.length === 0) return;
    
    const messagesContainer = document.getElementById('messagesContainer');
    const dates = {};
    
    // Récupérer les dates existantes
    document.querySelectorAll('.message-date-separator span').forEach(span => {
        dates[span.textContent] = true;
    });
    
    // Regrouper les messages par date
    const messagesByDate = groupMessagesByDate(messages);
    
    // Ajouter les messages par date
    Object.keys(messagesByDate).forEach(date => {
        // Si la date n'existe pas encore, ajouter le séparateur
        if (!dates[date]) {
            messagesContainer.appendChild(createDateSeparator(date));
            dates[date] = true;
        }
        
        // Ajouter les messages de cette date
        messagesByDate[date].forEach(message => {
            const messageElement = createMessageElement(message);
            messageElement.classList.add('new-message');
            messagesContainer.appendChild(messageElement);
            
            // Animation d'entrée
            setTimeout(() => {
                messageElement.classList.remove('new-message');
            }, 500);
        });
    });
    
    // Faire défiler jusqu'au dernier message
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
}

/**
 * Temporise la recherche lors de la saisie
 */
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const searchInput = document.getElementById('searchInput');
        loadConversations(searchInput.value.trim());
    }, 300);
}

/**
 * Affiche une notification à l'utilisateur
 * @param {string} message Message à afficher
 * @param {string} type Type de notification (success, danger, warning, info)
 */
function showNotification(message, type = 'info') {
    // Utiliser une fonction globale si elle existe
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }
    
    // Nettoyer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notification => {
        // Déplacer les notifications existantes vers le haut
        const currentTop = parseInt(notification.style.top);
        notification.style.top = (currentTop - 60) + 'px';
    });
    
    // Créer une notification personnalisée
    const notification = document.createElement('div');
    notification.className = `notification-toast fade-in ${type}`;
    
    // Déterminer l'icône en fonction du type
    let icon = 'info-circle';
    switch (type) {
        case 'success': icon = 'check-circle'; break;
        case 'danger': icon = 'exclamation-circle'; break;
        case 'warning': icon = 'exclamation-triangle'; break;
    }
    
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${icon}"></i>
        </div>
        <div class="notification-content">
            ${message}
        </div>
        <button type="button" class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Style pour la notification
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.display = 'flex';
    notification.style.alignItems = 'center';
    notification.style.minWidth = '280px';
    notification.style.maxWidth = '350px';
    notification.style.padding = '12px 16px';
    notification.style.background = 'white';
    notification.style.boxShadow = 'var(--box-shadow)';
    notification.style.borderRadius = '8px';
    notification.style.borderLeft = `4px solid var(--${type}-color)`;
    
    // Styles pour les éléments internes
    const styles = document.createElement('style');
    styles.textContent = `
        .notification-toast {
            animation: slideInRight 0.3s ease forwards;
        }
        .notification-toast.fade-out {
            animation: slideOutRight 0.3s ease forwards;
        }
        .notification-icon {
            margin-right: 12px;
            font-size: 1.2rem;
            color: var(--${type}-color);
        }
        .notification-content {
            flex-grow: 1;
            font-size: 0.9rem;
        }
        .notification-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.8rem;
            padding: 0;
            margin-left: 8px;
        }
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
    `;
    document.head.appendChild(styles);
    
    // Ajouter au document
    document.body.appendChild(notification);
    
    // Gérer le bouton de fermeture
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        closeNotification(notification);
    });
    
    // Fermer automatiquement après 5 secondes
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
    
    // Fonction pour fermer proprement la notification
    function closeNotification(notif) {
        notif.classList.add('fade-out');
        setTimeout(() => {
            if (notif.parentElement) {
                notif.remove();
            }
        }, 300);
    }
} 