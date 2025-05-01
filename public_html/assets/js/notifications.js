/**
 * Gestion des notifications
 */

document.addEventListener('DOMContentLoaded', function() {
    initNotifications();
});

/**
 * Initialise le système de notifications
 */
function initNotifications() {
    const notificationsIcon = document.getElementById('notifications-icon');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    const markAllReadBtn = document.getElementById('mark-all-read');
    
    if (!notificationsIcon || !notificationsDropdown) return;
    
    // Afficher/masquer le dropdown des notifications
    notificationsIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationsDropdown.classList.toggle('show');
        
        // Si le dropdown est affiché, charger les notifications
        if (notificationsDropdown.classList.contains('show')) {
            loadNotifications();
        }
    });
    
    // Fermer le dropdown en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (notificationsDropdown.classList.contains('show') && 
            !notificationsDropdown.contains(e.target) && 
            e.target !== notificationsIcon) {
            notificationsDropdown.classList.remove('show');
        }
    });
    
    // Marquer toutes les notifications comme lues
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markAllNotificationsAsRead();
        });
    }
    
    // Charger le nombre de notifications non lues au chargement
    updateNotificationCount();
    
    // Actualiser le nombre de notifications toutes les 60 secondes
    setInterval(updateNotificationCount, 60000);
}

/**
 * Charge les notifications et les affiche dans le dropdown
 */
function loadNotifications() {
    const notificationsList = document.getElementById('notifications-list');
    if (!notificationsList) return;
    
    // Afficher un indicateur de chargement
    notificationsList.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
    
    // Requête AJAX pour récupérer les notifications
    fetch('ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
            } else {
                notificationsList.innerHTML = '<div class="text-center p-3">Erreur lors du chargement des notifications.</div>';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            notificationsList.innerHTML = '<div class="text-center p-3">Erreur lors du chargement des notifications.</div>';
        });
}

/**
 * Affiche les notifications dans le dropdown
 * @param {Array} notifications Liste des notifications
 */
function displayNotifications(notifications) {
    const notificationsList = document.getElementById('notifications-list');
    if (!notificationsList) return;
    
    if (notifications.length === 0) {
        notificationsList.innerHTML = '<div class="text-center p-3">Aucune notification.</div>';
        return;
    }
    
    let html = '';
    
    notifications.forEach(notification => {
        // Déterminer l'icône en fonction du type
        let icon = '';
        switch (notification.type) {
            case 'reparation':
                icon = '<i class="fas fa-wrench"></i>';
                break;
            case 'commande':
                icon = '<i class="fas fa-shopping-cart"></i>';
                break;
            case 'diagnostic':
                icon = '<i class="fas fa-stethoscope"></i>';
                break;
            case 'tache':
                icon = '<i class="fas fa-tasks"></i>';
                break;
            default:
                icon = '<i class="fas fa-bell"></i>';
        }
        
        // Formater la date
        const date = new Date(notification.created_at);
        const formattedDate = formatTimeAgo(date);
        
        // Créer l'élément HTML
        html += `
            <li class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-icon notification-${notification.type}">
                        ${icon}
                    </div>
                    <div class="notification-text">
                        <p class="notification-message">${notification.message}</p>
                        <p class="notification-time">${formattedDate}</p>
                    </div>
                </div>
            </li>
        `;
    });
    
    notificationsList.innerHTML = html;
    
    // Ajouter des écouteurs d'événements pour marquer comme lu
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            markNotificationAsRead(notificationId);
            
            // Rediriger vers la page correspondante si nécessaire
            // Cette partie peut être adaptée selon les besoins
            // window.location.href = 'index.php?page=details&type=' + notification.type + '&id=' + notification.reference_id;
        });
    });
}

/**
 * Met à jour le compteur de notifications
 */
function updateNotificationCount() {
    const badge = document.getElementById('notifications-badge');
    if (!badge) return;
    
    fetch('ajax/count_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.count;
                
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'flex';
                    badge.classList.add('has-new');
                } else {
                    badge.style.display = 'none';
                    badge.classList.remove('has-new');
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
}

/**
 * Marque une notification comme lue
 * @param {number} notificationId ID de la notification
 */
function markNotificationAsRead(notificationId) {
    fetch('ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'interface
            const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('unread');
            }
            
            // Mettre à jour le compteur
            updateNotificationCount();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

/**
 * Marque toutes les notifications comme lues
 */
function markAllNotificationsAsRead() {
    fetch('ajax/mark_all_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'interface
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            
            // Mettre à jour le compteur
            updateNotificationCount();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

/**
 * Formate une date en "il y a X temps"
 * @param {Date} date La date à formater
 * @return {string} La date formatée
 */
function formatTimeAgo(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffSec < 60) {
        return 'À l\'instant';
    } else if (diffMin < 60) {
        return `Il y a ${diffMin} minute${diffMin > 1 ? 's' : ''}`;
    } else if (diffHour < 24) {
        return `Il y a ${diffHour} heure${diffHour > 1 ? 's' : ''}`;
    } else if (diffDay < 7) {
        return `Il y a ${diffDay} jour${diffDay > 1 ? 's' : ''}`;
    } else {
        return date.toLocaleDateString('fr-FR');
    }
}