// Configuration
const NOTIFICATION_CHECK_INTERVAL = 5000; // Vérifier les nouvelles notifications toutes les 5 secondes
const MESSAGE_SOUND = new Audio('assets/sounds/notification.mp3');

// État global
let currentConversationId = null;
let lastMessageId = 0;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeMessagerie();
    setupEventListeners();
    startNotificationCheck();
});

// Fonctions d'initialisation
function initializeMessagerie() {
    // Récupérer l'ID de la conversation actuelle depuis l'URL
    const urlParams = new URLSearchParams(window.location.search);
    currentConversationId = urlParams.get('conv');
    
    if (currentConversationId) {
        loadConversation(currentConversationId);
    }
    
    // Initialiser le compteur de messages non lus
    updateUnreadCount();
}

function setupEventListeners() {
    // Gestionnaire pour le bouton de nouvelle conversation
    const newConversationBtn = document.getElementById('newConversationBtn');
    if (newConversationBtn) {
        newConversationBtn.addEventListener('click', showNewConversationModal);
    }
    
    // Gestionnaire pour le formulaire d'envoi de message
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', handleMessageSubmit);
    }
    
    // Gestionnaire pour le scroll des messages
    const messagesContainer = document.querySelector('.conversation-messages');
    if (messagesContainer) {
        messagesContainer.addEventListener('scroll', handleMessagesScroll);
    }
}

// Fonctions de gestion des messages
function loadConversation(id) {
    const content = document.getElementById('conversationContent');
    if (!content) return;
    
    // Afficher le loader
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    `;
    
    // Charger la conversation
    fetch(`ajax/get_conversation.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversation(data.conversation);
                // Mettre à jour le dernier message ID
                if (data.conversation.messages.length > 0) {
                    lastMessageId = data.conversation.messages[data.conversation.messages.length - 1].id;
                }
            } else {
                showError('Erreur lors du chargement de la conversation');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Une erreur est survenue');
        });
}

function displayConversation(conv) {
    const content = document.getElementById('conversationContent');
    if (!content) return;
    
    content.innerHTML = `
        <div class="conversation-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">${conv.titre}</h5>
                <div class="conversation-actions">
                    ${conv.type === 'groupe' ? `
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="showGroupSettings(${conv.id})">
                            <i class="fas fa-cog"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteConversation(${conv.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <small class="text-muted">${conv.type === 'annonce' ? 'Annonce' : 'Conversation'}</small>
        </div>
        <div class="conversation-messages">
            ${conv.messages.map(msg => `
                ${msg.est_annonce ? `
                    <div class="announcement">
                        <div class="announcement-header">
                            <div class="announcement-title">
                                <i class="fas fa-bullhorn me-2"></i>
                                Annonce importante
                            </div>
                            <div class="announcement-meta">
                                ${formatDate(msg.date_envoi)}
                            </div>
                        </div>
                        <div class="announcement-content">
                            ${msg.contenu}
                        </div>
                        <div class="announcement-footer">
                            <div class="read-confirmation">
                                ${msg.lectures ? `
                                    <i class="fas fa-check-circle"></i>
                                    ${msg.lectures} lecture(s)
                                ` : 'Non lu'}
                            </div>
                            ${!msg.est_lu ? `
                                <button class="btn btn-sm btn-success confirm-read" data-id="${msg.id}">
                                    <i class="fas fa-check me-1"></i> Confirmer la lecture
                                </button>
                            ` : ''}
                        </div>
                    </div>
                ` : `
                    <div class="message ${msg.sender_id === currentUserId ? 'sent' : ''}" data-id="${msg.id}">
                        <div class="message-avatar">
                            <img src="assets/images/avatars/${msg.sender_id}.jpg" alt="Avatar" onerror="this.src='assets/images/default-avatar.png'">
                        </div>
                        <div class="message-content">
                            <div class="message-sender">${msg.sender_nom} ${msg.sender_prenom}</div>
                            <div class="message-text">${msg.contenu}</div>
                            <div class="message-meta">
                                ${formatDate(msg.date_envoi)}
                            </div>
                        </div>
                    </div>
                `}
            `).join('')}
        </div>
        <div class="conversation-input">
            <form id="messageForm" class="d-flex gap-2">
                <input type="hidden" name="conversation_id" value="${conv.id}">
                <div class="flex-grow-1">
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" onclick="showAttachmentOptions()">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <input type="text" class="form-control" name="message" placeholder="Écrivez votre message...">
                        <button type="button" class="btn btn-outline-secondary" onclick="showEmojiPicker()">
                            <i class="fas fa-smile"></i>
                        </button>
                    </div>
                    <div id="attachmentOptions" class="mt-2 d-none">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="attachFile('image')">
                                <i class="fas fa-image"></i> Image
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="attachFile('document')">
                                <i class="fas fa-file"></i> Document
                            </button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    `;
    
    // Scroll to bottom
    const messagesContainer = content.querySelector('.conversation-messages');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Réinitialiser les gestionnaires d'événements
    setupMessageEventListeners();
}

// Fonctions de gestion des événements
function handleMessageSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    fetch('ajax/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            form.reset();
            loadConversation(formData.get('conversation_id'));
        } else {
            showError('Erreur lors de l\'envoi du message');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Une erreur est survenue');
    });
}

function handleMessagesScroll(e) {
    const container = e.target;
    if (container.scrollTop === 0) {
        loadMoreMessages();
    }
}

// Fonctions de notification
function startNotificationCheck() {
    setInterval(checkNewMessages, NOTIFICATION_CHECK_INTERVAL);
}

function checkNewMessages() {
    if (!currentConversationId) return;
    
    fetch(`ajax/check_new_messages.php?conversation_id=${currentConversationId}&last_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.hasNewMessages) {
                loadConversation(currentConversationId);
                playNotificationSound();
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function updateUnreadCount() {
    fetch('ajax/get_unread_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateUnreadBadge(data.count);
            }
        })
        .catch(error => console.error('Erreur:', error));
}

// Fonctions utilitaires
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    // Si moins d'une minute
    if (diff < 60000) {
        return 'À l\'instant';
    }
    // Si moins d'une heure
    if (diff < 3600000) {
        const minutes = Math.floor(diff / 60000);
        return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
    }
    // Si moins d'un jour
    if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
    }
    // Si moins d'une semaine
    if (diff < 604800000) {
        const days = Math.floor(diff / 86400000);
        return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
    }
    // Sinon, afficher la date complète
    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function playNotificationSound() {
    MESSAGE_SOUND.play().catch(error => console.error('Erreur de lecture du son:', error));
}

function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.conversation-main').prepend(alert);
}

function updateUnreadBadge(count) {
    const badge = document.querySelector('.messagerie-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }
}

// Fonctions pour les pièces jointes et emojis
function showAttachmentOptions() {
    const options = document.getElementById('attachmentOptions');
    options.classList.toggle('d-none');
}

function attachFile(type) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = type === 'image' ? 'image/*' : '.pdf,.doc,.docx,.xls,.xlsx';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            uploadFile(file, type);
        }
    };
    input.click();
}

function uploadFile(file, type) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    formData.append('conversation_id', currentConversationId);
    
    fetch('ajax/upload_file.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadConversation(currentConversationId);
        } else {
            showError('Erreur lors de l\'upload du fichier');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Une erreur est survenue');
    });
}

function showEmojiPicker() {
    // Implémenter le sélecteur d'emojis
    // Vous pouvez utiliser une bibliothèque comme emoji-mart
} 