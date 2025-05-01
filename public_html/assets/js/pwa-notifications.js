/**
 * Système de notifications pour PWA
 * Gère l'enregistrement aux notifications push, la gestion des autorisations
 * et l'affichage des notifications
 */

// Variable globale pour stocker la subscription
let pushSubscription = null;

// Initialisation du système de notifications
document.addEventListener('DOMContentLoaded', function() {
  initPwaNotifications();
});

/**
 * Initialise le système de notifications pour la PWA
 */
function initPwaNotifications() {
  // Vérifier si c'est une PWA et si les notifications sont supportées
  const isPwa = window.matchMedia('(display-mode: standalone)').matches || 
                window.navigator.standalone || 
                document.referrer.includes('android-app://');
  
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    console.log('Les notifications push ne sont pas supportées sur ce navigateur');
    return;
  }
  
  // Vérifier si le service worker est déjà enregistré
  navigator.serviceWorker.ready
    .then(registration => {
      // Vérifier si l'utilisateur est déjà abonné
      return registration.pushManager.getSubscription()
        .then(subscription => {
          pushSubscription = subscription;
          
          // Si l'application est en mode PWA, proposer les notifications
          if (isPwa && Notification.permission === 'default') {
            setTimeout(() => {
              showNotificationPermissionRequest();
            }, 3000); // Attendre 3 secondes avant de proposer les notifications
          }
          
          // Si l'utilisateur est déjà abonné, envoyer la subscription au serveur
          if (subscription) {
            updateSubscriptionOnServer(subscription);
          }
        });
    })
    .catch(error => {
      console.error('Erreur lors de l\'initialisation des notifications:', error);
    });
}

/**
 * Affiche une demande d'autorisation pour les notifications
 */
function showNotificationPermissionRequest() {
  // Créer la boîte de dialogue
  const permissionDialog = document.createElement('div');
  permissionDialog.className = 'notification-permission-dialog';
  permissionDialog.innerHTML = `
    <div class="notification-permission-content">
      <div class="notification-permission-icon">
        <i class="fas fa-bell"></i>
      </div>
      <div class="notification-permission-text">
        <h3>Activez les notifications</h3>
        <p>Recevez des alertes importantes et des mises à jour sur vos activités</p>
      </div>
      <div class="notification-permission-actions">
        <button class="btn-cancel">Plus tard</button>
        <button class="btn-allow">Activer</button>
      </div>
    </div>
  `;
  
  // Ajouter des styles
  const style = document.createElement('style');
  style.textContent = `
    .notification-permission-dialog {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      width: 90%;
      max-width: 400px;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      z-index: 9999;
      animation: slide-up 0.3s ease-out;
    }
    
    .notification-permission-content {
      padding: 20px;
    }
    
    .notification-permission-icon {
      text-align: center;
      margin-bottom: 15px;
    }
    
    .notification-permission-icon i {
      font-size: 2.5rem;
      color: #4361ee;
      padding: 15px;
      background-color: #eef2ff;
      border-radius: 50%;
    }
    
    .notification-permission-text {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .notification-permission-text h3 {
      font-size: 1.2rem;
      margin-bottom: 8px;
      font-weight: 600;
    }
    
    .notification-permission-text p {
      font-size: 0.9rem;
      color: #6b7280;
      margin: 0;
    }
    
    .notification-permission-actions {
      display: flex;
      gap: 10px;
    }
    
    .notification-permission-actions button {
      flex: 1;
      padding: 10px;
      border-radius: 8px;
      border: none;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .btn-cancel {
      background-color: #f3f4f6;
      color: #4b5563;
    }
    
    .btn-allow {
      background-color: #4361ee;
      color: white;
    }
    
    .btn-cancel:hover {
      background-color: #e5e7eb;
    }
    
    .btn-allow:hover {
      background-color: #3a56d4;
    }
    
    @keyframes slide-up {
      from {
        transform: translate(-50%, 100%);
        opacity: 0;
      }
      to {
        transform: translate(-50%, 0);
        opacity: 1;
      }
    }
  `;
  
  // Ajouter les éléments au DOM
  document.head.appendChild(style);
  document.body.appendChild(permissionDialog);
  
  // Gérer les événements
  const btnCancel = permissionDialog.querySelector('.btn-cancel');
  const btnAllow = permissionDialog.querySelector('.btn-allow');
  
  btnCancel.addEventListener('click', () => {
    permissionDialog.remove();
    
    // Stocker dans localStorage pour ne pas redemander tout de suite
    localStorage.setItem('notification_permission_declined', Date.now());
  });
  
  btnAllow.addEventListener('click', () => {
    permissionDialog.remove();
    subscribeToPushNotifications();
  });
}

/**
 * S'abonne aux notifications push
 */
function subscribeToPushNotifications() {
  // Demander la permission de notification
  Notification.requestPermission()
    .then(permission => {
      if (permission === 'granted') {
        // L'utilisateur a accepté, on procède à l'abonnement
        navigator.serviceWorker.ready
          .then(registration => {
            // Options de souscription
            const applicationServerKey = urlBase64ToUint8Array(
              // Clé publique VAPID (à remplacer par votre clé)
              'BNbxGYHtMYt33D8xJYLM834JG4fBXHs7o59ag9GhhXF27TGvAJCKsRQBYBjbmTJPRTzFdm0KXtNHI9Qw0sD0VwE'
            );
            
            const options = {
              userVisibleOnly: true,
              applicationServerKey: applicationServerKey
            };
            
            // S'abonner
            return registration.pushManager.subscribe(options)
              .then(subscription => {
                console.log('Abonnement réussi:', subscription);
                pushSubscription = subscription;
                
                // Envoyer la subscription au serveur
                updateSubscriptionOnServer(subscription);
                
                // Afficher un message de succès
                showLocalNotification('Notifications activées', 'Vous recevrez désormais les notifications importantes.');
              })
              .catch(error => {
                console.error('Erreur lors de l\'abonnement:', error);
                showLocalNotification('Erreur', 'Impossible d\'activer les notifications.', 'error');
              });
          });
      } else {
        console.log('Permission refusée');
      }
    });
}

/**
 * Met à jour la subscription sur le serveur
 * @param {PushSubscription} subscription - Objet de souscription
 */
function updateSubscriptionOnServer(subscription) {
  if (!subscription) return;
  
  const subscriptionJson = subscription.toJSON();
  
  // Envoyer la subscription au serveur
  fetch('ajax/update_push_subscription.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      endpoint: subscriptionJson.endpoint,
      keys: subscriptionJson.keys
    })
  })
  .then(response => response.json())
  .then(data => {
    console.log('Subscription mise à jour sur le serveur:', data);
  })
  .catch(error => {
    console.error('Erreur lors de la mise à jour de la subscription:', error);
  });
}

/**
 * Se désabonne des notifications push
 */
function unsubscribeFromPushNotifications() {
  if (!pushSubscription) return;
  
  pushSubscription.unsubscribe()
    .then(() => {
      console.log('Désabonnement réussi');
      pushSubscription = null;
      
      // Informer le serveur
      fetch('ajax/delete_push_subscription.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          endpoint: pushSubscription.endpoint
        })
      })
      .catch(error => {
        console.error('Erreur lors de la suppression de la subscription:', error);
      });
      
      showLocalNotification('Notifications désactivées', 'Vous ne recevrez plus de notifications.');
    })
    .catch(error => {
      console.error('Erreur lors du désabonnement:', error);
    });
}

/**
 * Affiche une notification locale (sans passer par le serveur)
 * @param {string} title - Titre de la notification
 * @param {string} message - Message de la notification
 * @param {string} type - Type de notification (info, success, warning, error)
 */
function showLocalNotification(title, message, type = 'info') {
  // Si les notifications sont supportées et autorisées
  if ('Notification' in window && Notification.permission === 'granted') {
    navigator.serviceWorker.ready
      .then(registration => {
        // Options de la notification
        const options = {
          body: message,
          icon: '/assets/images/pwa-icons/icon-192x192.png',
          badge: '/assets/images/pwa-icons/icon-72x72.png',
          tag: type,
          data: {
            url: window.location.href,
            type: type
          },
          vibrate: [100, 50, 100]
        };
        
        // Afficher la notification
        registration.showNotification(title, options);
      });
  } else {
    // Fallback si les notifications ne sont pas disponibles
    // Utiliser la fonction showNotification du fichier professional-desktop.js si disponible
    if (typeof window.showNotification === 'function') {
      window.showNotification(message, type);
    } else {
      // Fallback simple
      alert(`${title}: ${message}`);
    }
  }
}

/**
 * Convertit une clé VAPID Base64 en tableau d'octets Uint8Array
 * @param {string} base64String - Chaîne Base64
 * @return {Uint8Array} - Tableau d'octets
 */
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  
  return outputArray;
}

// Exporter les fonctions pour les rendre disponibles globalement
window.PwaNotifications = {
  subscribe: subscribeToPushNotifications,
  unsubscribe: unsubscribeFromPushNotifications,
  showNotification: showLocalNotification
}; 