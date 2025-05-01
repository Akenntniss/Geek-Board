# Module de gestion des SMS

Ce module fournit une interface unifiée pour l'envoi de SMS depuis différentes parties de l'application, avec des fonctionnalités avancées comme les notifications toast et la prévisualisation des messages.

## Fonctionnalités

- Envoi de SMS personnalisés
- Envoi de SMS à partir de modèles prédéfinis
- Prévisualisation du message avant envoi
- Notifications toast pour les retours d'envoi
- Interface de chargement pendant l'envoi
- Gestion des erreurs

## Installation

1. Incluez le fichier `sms_manager.js` dans votre page HTML :

```html
<script src="includes/modules/sms_manager.js"></script>
```

2. Assurez-vous que Bootstrap 5+ et Font Awesome 5+ sont également inclus dans votre page.

## Utilisation

Le module expose un objet global `SmsManager` avec les méthodes suivantes :

### Envoyer un SMS personnalisé

```javascript
SmsManager.sendCustomSms(message, data, onSuccess, onError);
```

Paramètres :
- `message` (string) : Contenu du SMS
- `data` (object) : Données du destinataire
  - `client_id` : ID du client
  - `telephone` : Numéro de téléphone
  - `reparation_id` : ID de la réparation
- `onSuccess` (function, optional) : Callback en cas de succès
- `onError` (function, optional) : Callback en cas d'erreur

### Envoyer un SMS à partir d'un modèle prédéfini

```javascript
SmsManager.sendPredefinedSms(templateId, data, onSuccess, onError);
```

Paramètres :
- `templateId` (number) : ID du modèle de SMS
- `data` (object) : Données du destinataire
  - `client_id` : ID du client
  - `telephone` : Numéro de téléphone
  - `reparation_id` : ID de la réparation
- `onSuccess` (function, optional) : Callback en cas de succès
- `onError` (function, optional) : Callback en cas d'erreur

### Prévisualiser un SMS avant envoi

```javascript
SmsManager.previewSms(message, source, data, onConfirm);
```

Paramètres :
- `message` (string) : Contenu du SMS
- `source` (string) : Source du message ('custom' ou 'predefined')
- `data` (object) : Données du destinataire
  - `telephone` : Numéro de téléphone (utilisé pour l'affichage)
- `onConfirm` (function) : Fonction appelée lors de la confirmation

### Afficher une notification toast

```javascript
SmsManager.showToast(message, type);
```

Paramètres :
- `message` (string) : Contenu de la notification
- `type` (string, optional) : Type de notification ('success', 'error', 'warning', 'info')

## Exemples

Voir le fichier `includes/examples/sms_example.js` pour des exemples d'utilisation.

### Exemple de base

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Envoyer un SMS personnalisé
    const btnEnvoyerSMS = document.getElementById('btnEnvoyerSMS');
    if (btnEnvoyerSMS) {
        btnEnvoyerSMS.addEventListener('click', function() {
            const texteSMS = document.getElementById('texteSMS').value;
            const clientData = {
                client_id: document.querySelector('input[name="client_id"]').value,
                telephone: document.querySelector('input[name="client_telephone"]').value,
                reparation_id: document.querySelector('input[name="reparation_id"]').value
            };
            
            // Envoyer le SMS directement
            SmsManager.sendCustomSms(texteSMS, clientData);
        });
    }
});
```

## Structure du backend

Ce module s'attend à ce que le serveur backend implémente un endpoint `ajax/send_sms.php` qui accepte les paramètres suivants :

- `client_id` : ID du client
- `client_telephone` : Numéro de téléphone
- `reparation_id` : ID de la réparation
- `message` : Contenu du SMS (pour les SMS personnalisés)
- `template_id` : ID du modèle (pour les SMS prédéfinis)
- `type` : Type de SMS ('custom' ou 'predefined')

La réponse attendue du serveur est un objet JSON contenant :

```json
{
    "success": true|false,
    "message": "Message de succès ou d'erreur"
}
``` 