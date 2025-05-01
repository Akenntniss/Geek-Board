# Guide de configuration de SMS Gateway pour Android

Ce guide vous aidera à configurer correctement l'application SMS Gateway sur votre téléphone Android pour fonctionner avec votre application de réparation.

## Problème identifié

La connexion à l'API SMS Cloud (`https://api.sms-gate.app/3rdparty/v1/message`) n'est plus disponible. Nous allons donc configurer l'application en mode **Local Server** à la place.

## Étapes à suivre

### 1. Installation de l'application SMS Gateway

1. Téléchargez et installez l'application [SMS Gateway](https://play.google.com/store/apps/details?id=networkapps.net.smsgateway) depuis le Google Play Store sur votre téléphone Android.
2. Ouvrez l'application et procédez à la configuration initiale.

### 2. Configuration en mode Local Server

1. Sur votre téléphone Android, ouvrez l'application SMS Gateway.
2. Allez dans le menu "Settings" (Paramètres).
3. Sélectionnez "API Configuration" (Configuration de l'API).
4. Désactivez l'option "Cloud Server" si elle est active.
5. Activez l'option "Local Server".
6. Notez l'adresse IP et le port affichés (par exemple `192.168.1.100:8080`).
7. Vous pouvez conserver les identifiants de base:
   - Utilisateur: `-GCB75`
   - Mot de passe: `Mamanmaman06400`

### 3. Modification des fichiers de configuration

Vous devrez mettre à jour deux fichiers sur votre serveur avec l'adresse correcte:

1. Ouvrez le fichier `includes/functions.php` et modifiez la ligne:
   ```php
   $url = $gateway_url ?? 'http://192.168.1.100:8080/api';
   ```
   Remplacez `192.168.1.100:8080` par l'adresse IP et le port de votre téléphone.

2. Ouvrez le fichier `api/sms_gateway.php` et modifiez la ligne:
   ```php
   'api_url' => 'http://192.168.1.100:8080/api',
   ```
   Remplacez `192.168.1.100:8080` par l'adresse IP et le port de votre téléphone.

### 4. Vérification du fonctionnement

1. Assurez-vous que votre téléphone Android est connecté au même réseau WiFi que votre serveur.
2. Vérifiez que l'application SMS Gateway est ouverte et en cours d'exécution sur votre téléphone.
3. Accédez à la page de test depuis votre navigateur:
   ```
   https://votredomaine.com/api/test_sms.php
   ```
4. Entrez un numéro de téléphone et un message de test pour vérifier que tout fonctionne.

### 5. Recommandations importantes

- **Adresse IP fixe**: Si possible, attribuez une adresse IP fixe à votre téléphone Android sur votre réseau WiFi pour éviter d'avoir à modifier les paramètres à chaque changement d'IP.
- **Téléphone dédié**: Utilisez un téléphone Android dédié à cette fonction, connecté en permanence au réseau et au chargeur.
- **Forfait SMS**: Assurez-vous que le téléphone dispose d'un forfait avec suffisamment de SMS pour votre usage.
- **Application en premier plan**: Sur certains téléphones, l'application peut être mise en veille. Vérifiez les paramètres de batterie pour autoriser l'application à fonctionner en arrière-plan.

## Dépannage

Si vous rencontrez des problèmes:

1. **Erreur de connexion**: Vérifiez que le téléphone et le serveur sont sur le même réseau WiFi.
2. **Erreur 404**: Vérifiez que l'URL est correcte, avec `/api` à la fin.
3. **Aucune réponse**: Vérifiez que l'application est bien en cours d'exécution sur le téléphone.
4. **SMS non envoyés**: Vérifiez les autorisations de l'application et l'état du forfait SMS.

## Support

Si vous avez besoin d'aide supplémentaire, consultez la [documentation officielle](https://docs.sms-gate.app/) ou contactez le support technique. 