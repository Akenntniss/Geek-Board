# API SMS Gateway

## Configuration

Cette API utilise le service [SMS Gateway pour Android](https://docs.sms-gate.app/getting-started/) pour envoyer des SMS depuis votre application web.

## Informations de connexion

- **URL API**: https://api.sms-gate.app/3rdparty/v1/message
- **Utilisateur**: -GCB75
- **Mot de passe**: Mamanmaman06400

> **IMPORTANT**: L'URL doit être exactement comme indiqué ci-dessus. Utiliser `http://` au lieu de `https://` ou `mobile/v1` au lieu de `3rdparty/v1/message` entraînera des erreurs de redirection (code 308).

## Mode de fonctionnement

Notre implémentation utilise le mode **Cloud Server**, qui permet d'envoyer des SMS via Internet en utilisant l'API publique de SMS Gateway. Ce mode nécessite:

1. Une application [SMS Gateway](https://docs.sms-gate.app/) installée sur un appareil Android
2. Une connexion Internet active sur l'appareil
3. Le mode "Cloud Server" activé dans l'application

## Utilisation

### Exemple avec la fonction `send_sms()`

```php
require_once '../includes/functions.php';

$numero = '+33612345678'; // Toujours au format international
$message = 'Votre message ici';

$result = send_sms($numero, $message);

if ($result['success']) {
    echo "SMS envoyé avec succès!";
} else {
    echo "Erreur: " . $result['message'];
}
```

### Format des numéros de téléphone

L'API accepte uniquement les numéros au format international. Notre implémentation essaie de formater automatiquement les numéros:

- **Format correct**: `+33612345678`
- Si vous fournissez `0612345678`, il sera converti en `+33612345678`
- Si vous fournissez `33612345678`, il sera converti en `+33612345678`

### Format des données

```json
{
  "message": "Contenu du SMS",
  "phoneNumbers": ["+33612345678"]
}
```

### Tester l'API

Vous pouvez tester l'API directement en accédant à:
```
/api/sms_gateway.php?test=1&number=+33612345678&message=Test
```

Ou avec le script de test:
```
/api/test_sms.php?numero=0612345678&message=Test
```

## Codes d'erreur courants

- **200/202**: Succès - Le SMS a été accepté pour envoi
- **308**: Redirection permanente - L'URL de l'API est incorrecte, utiliser https://api.sms-gate.app/3rdparty/v1/message
- **401**: Erreur d'authentification - Vérifiez vos identifiants
- **403**: Accès interdit - Votre compte n'a pas accès à cette ressource
- **404**: Non trouvé - L'URL de l'API est incorrecte
- **500**: Erreur serveur - Problème côté serveur

## Dépannage

Si vous rencontrez des problèmes:

1. Vérifiez que l'application SMS Gateway est en cours d'exécution sur l'appareil Android
2. Assurez-vous que l'appareil est connecté à Internet
3. Vérifiez les identifiants d'API
4. Vérifiez que vous utilisez bien l'URL HTTPS correcte: https://api.sms-gate.app/3rdparty/v1/message
5. Consultez les logs dans `/logs/sms_DATE.log`
6. Assurez-vous d'utiliser des numéros de téléphone au format international

## Documentation

Pour plus d'informations, référez-vous à:
- Le fichier `api/sms_gateway.php` qui contient des exemples complets
- La [documentation officielle](https://docs.sms-gate.app/) 