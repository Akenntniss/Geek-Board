# Fonctionnalité SMS - GeekBoard

## Vue d'ensemble

La fonctionnalité SMS permet d'envoyer des messages texte directement aux clients depuis la page de gestion des clients. Cette fonctionnalité supporte les templates prédéfinis et les messages personnalisés.

## Fonctionnalités

### 🎯 Bouton SMS
- **Emplacement** : À côté du numéro de téléphone de chaque client
- **Visibilité** : Desktop (grande colonne) et mobile (version compacte)
- **Condition** : Affiché uniquement si le client a un numéro de téléphone

### 📱 Modal d'envoi SMS
- **Interface moderne** : Modal Bootstrap responsive
- **Deux modes** : Templates prédéfinis ou message personnalisé
- **Aperçu en temps réel** : Prévisualisation du message avec variables remplacées
- **Validation** : Vérification de la longueur (160 caractères max)

### 📋 Templates SMS
- **Variables dynamiques** : `{CLIENT_NOM}`, `{CLIENT_PRENOM}`, `{DATE}`
- **Gestion centralisée** : Stockés en base de données
- **Templates par défaut** :
  - Bienvenue
  - Rappel RDV
  - Réparation terminée
  - Devis disponible
  - Remerciements

## Structure technique

### Base de données

#### Table `sms_templates`
```sql
- id (int, auto_increment)
- nom (varchar(100)) - Nom du template
- contenu (text) - Contenu avec variables
- statut_id (int, nullable) - Lien avec statut de réparation
- est_actif (tinyint(1)) - Template actif/inactif
- created_at (timestamp)
- updated_at (timestamp)
```

#### Table `sms_logs`
```sql
- id (int, auto_increment)
- recipient (varchar(20)) - Numéro de téléphone
- message (text) - Message envoyé
- sent_at (timestamp) - Date d'envoi
- status (varchar(20)) - Statut (sent, failed)
- client_id (int) - ID du client
- template_id (int, nullable) - ID du template utilisé
```

#### Table `sms_template_variables`
```sql
- id (int, auto_increment)
- nom (varchar(50)) - Nom de la variable
- description (varchar(255)) - Description
- exemple (varchar(100)) - Exemple de valeur
```

### Fichiers créés/modifiés

#### Nouveaux fichiers
- `ajax/send_client_sms.php` - Traitement de l'envoi SMS
- `ajax/get_sms_templates.php` - Récupération des templates (existant)

#### Fichiers modifiés
- `pages/clients.php` - Ajout boutons SMS et modal

## Variables disponibles

| Variable | Description | Exemple |
|----------|-------------|---------|
| `{CLIENT_NOM}` | Nom de famille du client | Dupont |
| `{CLIENT_PRENOM}` | Prénom du client | Jean |
| `{DATE}` | Date du jour (format français) | 03/01/2025 |

## Utilisation

### 1. Envoyer un SMS avec template
1. Cliquer sur le bouton SMS (📱) à côté du téléphone
2. Sélectionner "Template prédéfini"
3. Choisir un template dans la liste
4. Vérifier l'aperçu avec variables remplacées
5. Cliquer "Envoyer le SMS"

### 2. Envoyer un SMS personnalisé
1. Cliquer sur le bouton SMS (📱)
2. Sélectionner "Message personnalisé"
3. Taper le message (max 160 caractères)
4. Vérifier l'aperçu et le compteur
5. Cliquer "Envoyer le SMS"

## Sécurité

### Validation côté serveur
- ✅ Vérification de l'ID client
- ✅ Validation du numéro de téléphone français
- ✅ Limitation de longueur (160 caractères)
- ✅ Échappement des données
- ✅ Protection contre l'injection SQL

### Validation côté client
- ✅ Vérification des champs requis
- ✅ Compteur de caractères en temps réel
- ✅ Aperçu du message final
- ✅ Désactivation du bouton pendant l'envoi

## API SMS

### Configuration actuelle
- **Mode** : Simulation (95% de succès)
- **Fonction** : `simulateSmsSend()` dans `send_client_sms.php`

### Intégration d'une vraie API
Pour intégrer une vraie API SMS, remplacer la fonction `simulateSmsSend()` :

```php
function realSmsSend($telephone, $message) {
    $api_url = 'https://api.sms-provider.com/send';
    $api_key = 'your-api-key';
    
    $data = [
        'to' => $telephone,
        'message' => $message,
        'from' => 'YourBusiness'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
}
```

## Logs et suivi

### Historique des SMS
- Tous les SMS envoyés sont enregistrés dans `sms_logs`
- Traçabilité complète : client, message, template, date
- Statut de l'envoi (sent/failed)

### Consultation des logs
```sql
-- Voir les SMS d'un client
SELECT * FROM sms_logs WHERE client_id = 123 ORDER BY sent_at DESC;

-- Statistiques par template
SELECT t.nom, COUNT(l.id) as nb_envois 
FROM sms_templates t 
LEFT JOIN sms_logs l ON t.id = l.template_id 
GROUP BY t.id;
```

## Améliorations futures

### 🚀 Fonctionnalités prévues
- [ ] Envoi de SMS groupés
- [ ] Programmation d'envoi différé
- [ ] Templates avec plus de variables (prix, statut réparation)
- [ ] Statistiques d'envoi dans le dashboard
- [ ] Gestion des réponses SMS (si API le supporte)
- [ ] Templates par statut de réparation
- [ ] Intégration avec le système de notifications

### 🔧 Améliorations techniques
- [ ] Rate limiting pour éviter le spam
- [ ] Cache des templates côté client
- [ ] Compression des messages longs
- [ ] Support des emojis
- [ ] Prévisualisation sur différents mobiles

## Support

### Dépannage courant

**Problème** : "Template SMS non trouvé"
**Solution** : Vérifier que des templates actifs existent dans `sms_templates`

**Problème** : "Numéro de téléphone invalide"
**Solution** : Le numéro doit être au format français (0X XX XX XX XX ou +33 X XX XX XX XX)

**Problème** : "Message trop long"
**Solution** : Réduire le message à 160 caractères maximum

### Contact
Pour toute question technique, contacter l'équipe de développement GeekBoard.

---
*Documentation mise à jour le 03/01/2025* 