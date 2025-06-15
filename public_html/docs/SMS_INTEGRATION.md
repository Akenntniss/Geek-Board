# Intégration SMS Gateway - GeekBoard

## Vue d'ensemble

GeekBoard utilise maintenant l'API SMS Gateway développée spécialement pour l'envoi de SMS via des cartes SIM connectées à un téléphone Android. Cette solution remplace l'ancienne API distante et offre un contrôle total sur l'envoi des SMS.

## Configuration de l'API

- **URL de base :** `http://168.231.85.4:3001/api`
- **Documentation complète :** [http://168.231.85.4/frontend/documentation.html](http://168.231.85.4/frontend/documentation.html)
- **Authentification :** Non requise en développement
- **Format :** JSON

## Fonctionnalités disponibles

### Envoi de SMS
- ✅ Envoi via multiple cartes SIM
- ✅ Gestion automatique des quotas
- ✅ Basculement automatique entre opérateurs
- ✅ Priorités des messages (low, normal, high)
- ✅ Historique complet des envois

### Gestion des SIMs
- ✅ Surveillance des quotas mensuels
- ✅ Statistiques en temps réel
- ✅ Configuration des limites
- ✅ Auto-switch en cas de dépassement

## Utilisation dans GeekBoard

### 1. Envoi simple de SMS

```php
// Utilisation de la fonction principale
$result = send_sms('+33612345678', 'Votre réparation est prête !');

if ($result['success']) {
    echo "SMS envoyé avec succès";
} else {
    echo "Erreur : " . $result['message'];
}
```

### 2. Envoi avec options avancées

```php
// Utilisation directe de la classe
$smsService = new NewSmsService();
$result = $smsService->sendSms(
    '+33612345678', 
    'Message urgent', 
    'high',  // priorité
    32       // ID de SIM spécifique
);
```

### 3. Récupération de l'historique

```php
$smsService = new NewSmsService();
$history = $smsService->getHistory(1, 20, 'sent');

if ($history['success']) {
    foreach ($history['data'] as $sms) {
        echo "SMS à {$sms['recipient']} : {$sms['status']}";
    }
}
```

### 4. Vérification du statut des SIMs

```php
$smsService = new NewSmsService();
$simsStatus = $smsService->getSimsStatus();

if ($simsStatus['success']) {
    foreach ($simsStatus['data'] as $sim) {
        echo "SIM {$sim['carrier_name']} : {$sim['usage_percentage']}% utilisé";
    }
}
```

## Intégration dans les réparations

Le système SMS est automatiquement intégré dans :

1. **Changement de statut** : SMS automatique envoyé selon le template configuré
2. **Envoi de devis** : Notification au client avec lien
3. **Modal de réparation** : Bouton direct d'envoi de SMS
4. **Glisser-déposer** : Notification optionnelle lors du changement de statut

### Activation/Désactivation

Dans le modal de changement de statut, l'utilisateur peut :
- ✅ Activer l'envoi de SMS (bouton vert)
- ❌ Désactiver l'envoi de SMS (bouton rouge)

## Templates SMS

Les templates sont stockés dans la table `sms_templates` et permettent de :
- Personnaliser les messages selon le statut
- Utiliser des variables dynamiques (`{client_nom}`, `{numero_reparation}`, etc.)
- Gérer plusieurs langues si nécessaire

## Monitoring et logs

### Logs applicatifs
- **Fichier :** `logs/new_sms_YYYY-MM-DD.log`
- **Contenu :** Tentatives d'envoi, erreurs, succès

### Base de données
- **Table :** `sms_logs`
- **Contenu :** Historique complet des SMS avec métadonnées

### Interface de monitoring
- **URL :** `ajax/test_sms_api.php`
- **Fonctions :** Test de connectivité, envoi de test, historique

## Gestion d'erreurs

Le système gère automatiquement :

### Codes d'erreur HTTP
- **200** : Succès
- **400** : Paramètres invalides
- **429** : Limite de taux dépassée
- **500** : Erreur serveur

### Retry automatique
- **Tentatives :** 2 maximum
- **Délai :** Backoff exponentiel (1s, 2s)
- **Conditions :** Échec temporaire seulement

### Protection contre les doublons
- **Système :** Vérification des SMS identiques
- **Période :** 15 minutes par défaut
- **Critères :** Numéro + message + contexte

## Format des numéros

Le système accepte et convertit automatiquement :
- `0612345678` → `+33612345678`
- `33612345678` → `+33612345678`
- `612345678` → `+33612345678`
- `+33612345678` → `+33612345678` (inchangé)

## Sécurité

- **Validation :** Tous les paramètres sont validés
- **Sanitisation :** Messages nettoyés automatiquement
- **Limites :** Protection contre le spam
- **Logs :** Traçabilité complète des envois

## Support et dépannage

### Tests de connectivité
1. Accéder à `/ajax/test_sms_api.php`
2. Vérifier la connectivité API
3. Tester l'envoi avec un numéro réel
4. Consulter les logs en cas d'erreur

### Commandes utiles
```bash
# Voir les logs récents
tail -f logs/new_sms_$(date +%Y-%m-%d).log

# Vérifier la table SMS
mysql -e "SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 5;"
```

### Problèmes courants

1. **API non accessible**
   - Vérifier que le téléphone Android est connecté
   - Contrôler l'adresse IP `168.231.85.4`

2. **SMS non envoyé**
   - Vérifier les quotas SIM
   - Contrôler le format du numéro
   - Consulter les logs d'erreur

3. **Doublons bloqués**
   - Normal pour éviter les envois multiples
   - Attendre 15 minutes ou modifier le message

## Mise à jour et maintenance

- **Version actuelle :** 1.0
- **Dernière mise à jour :** Selon la documentation API
- **Prochaines fonctionnalités :** MMS, SMS groupés, webhooks

---

*Pour plus d'informations, consulter la [documentation complète de l'API](http://168.231.85.4/frontend/documentation.html)* 