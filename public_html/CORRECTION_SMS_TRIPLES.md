# 🚨 Correction des SMS Envoyés en Triple - GeekBoard

## 🔍 **Problème Identifié**

Les SMS étaient envoyés **3 fois consécutives** lors des changements de statut, causant de la confusion chez les clients et des surcoûts d'envoi.

### 📊 Diagnostic des Causes

#### **1. Système de Retry Agressif**
- **Fichier :** `public_html/classes/NewSmsService.php`
- **Problème :** `private $maxRetries = 3;`
- **Impact :** Si l'API retourne une erreur temporaire mais envoie quand même le SMS, le système réessaye 3 fois

#### **2. Absence de Protection Anti-Doublons**
- Aucun mécanisme pour détecter les envois identiques
- Pas de fenêtre de temps pour bloquer les doublons
- Aucune vérification basée sur le contenu du message

#### **3. Limitation de Caractères Incorrect**
- **Problème précédent :** Messages tronqués à 160 caractères
- **Correction :** Augmentation à 16000 caractères
- **Bug résiduel :** Troncature incorrecte à 1597 caractères même avec limite 16000

---

## ✅ **Solutions Implémentées**

### **1. Réduction du Nombre de Tentatives**
```php
// AVANT
private $maxRetries = 3;

// APRÈS
private $maxRetries = 1;
```
- **Fichier modifié :** `public_html/classes/NewSmsService.php`
- **Impact :** Élimine les tentatives multiples

### **2. Protection Anti-Doublons Intelligente**
- **Nouveau fichier :** `public_html/classes/SmsDeduplication.php`
- **Fonctionnalités :**
  - 🔒 **Hash sécurisé** des numéros et messages (SHA-256)
  - ⏱️ **Fenêtre de protection** de 60 secondes par défaut
  - 🗄️ **Table dédiée** `sms_deduplication` avec auto-nettoyage
  - 📊 **Statistiques** en temps réel

### **3. Intégration dans la Fonction SMS Principale**
```php
// Protection contre les doublons
require_once __DIR__ . '/../classes/SmsDeduplication.php';
$deduplication = new SmsDeduplication();

if (!$deduplication->canSendSms($phoneNumber, $message, $statusId, $repairId)) {
    return [
        'success' => false,
        'message' => 'SMS identique envoyé récemment - Doublon bloqué',
        'duplicate_blocked' => true
    ];
}
```

### **4. Correction de la Limitation de Caractères**
```php
// AVANT
if (strlen($message) > 16000) {
    $message = substr($message, 0, 1597) . '...'; // BUG !
}

// APRÈS
if (strlen($message) > 16000) {
    $message = substr($message, 0, 15997) . '...'; // CORRECT
}
```

---

## 🛠️ **Nouveaux Outils de Diagnostic**

### **1. Script de Diagnostic Complet**
- **Fichier :** `public_html/debug_sms_triples.php`
- **Fonctionnalités :**
  - 📋 Analyse des logs SMS des dernières 24h
  - 🔍 Détection automatique des doublons
  - ⚙️ Vérification de la configuration retry
  - 🛡️ Statut de la protection anti-doublons
  - 📝 Logs d'erreur NewSmsService

### **2. Table de Déduplication Automatique**
```sql
CREATE TABLE sms_deduplication (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_hash VARCHAR(64) NOT NULL,
    message_hash VARCHAR(64) NOT NULL,
    status_id INT,
    repair_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_message (phone_hash, message_hash),
    INDEX idx_created_at (created_at)
);
```

---

## 📈 **Résultats Attendus**

### **Avant la Correction**
- ❌ SMS envoyé 3 fois pour chaque changement de statut
- ❌ Messages tronqués à 160 caractères (ou incorrectement)
- ❌ Confusion client et surcoûts

### **Après la Correction**
- ✅ **1 seul SMS** par changement de statut
- ✅ **Messages complets** jusqu'à 16000 caractères
- ✅ **Protection anti-doublons** automatique
- ✅ **Diagnostics en temps réel** disponibles

---

## 🔧 **Fichiers Modifiés**

| Fichier | Action | Description |
|---------|--------|-------------|
| `classes/NewSmsService.php` | ✏️ Modifié | Réduction maxRetries: 3→1 |
| `includes/sms_functions.php` | ✏️ Modifié | Ajout protection anti-doublons + correction troncature |
| `classes/SmsDeduplication.php` | ➕ Créé | Nouvelle classe protection doublons |
| `debug_sms_triples.php` | ➕ Créé | Script diagnostic complet |
| `CORRECTION_SMS_TRIPLES.md` | ➕ Créé | Cette documentation |

---

## 🧪 **Test et Validation**

### **Pour Tester la Correction**
1. **Accéder au diagnostic :** `http://votre-domaine/debug_sms_triples.php`
2. **Effectuer un changement de statut** depuis la recherche universelle
3. **Vérifier les logs** pour confirmer l'envoi unique
4. **Tenter un changement identique** dans les 60 secondes pour tester la protection

### **Commandes de Vérification SQL**
```sql
-- Vérifier les doublons récents
SELECT phone_hash, message_hash, COUNT(*) as count, 
       MIN(created_at) as first_sent, MAX(created_at) as last_sent
FROM sms_deduplication 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY phone_hash, message_hash 
HAVING COUNT(*) > 1;

-- Statistiques des SMS des dernières 24h
SELECT COUNT(*) as total_sms, 
       COUNT(DISTINCT phone_hash) as unique_phones,
       COUNT(DISTINCT message_hash) as unique_messages
FROM sms_deduplication 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

---

## ⚠️ **Points d'Attention**

1. **Logs d'Erreur :** Surveiller `logs/new_sms_*.log` pour détecter d'éventuels problèmes
2. **Performance :** La table `sms_deduplication` se nettoie automatiquement (>24h supprimés)
3. **Fenêtre de Protection :** Modifiable via `$deduplication->setDeduplicationWindow(seconds)`
4. **Fallback :** En cas d'erreur de déduplication, l'envoi est autorisé (pas de blocage du service)

---

## 🎯 **Workflow Final Optimisé**

1. **Changement de statut** → `ajax/send_status_sms.php`
2. **Vérification anti-doublon** → `SmsDeduplication::canSendSms()`
3. **Si autorisé** → `NewSmsService::sendSms()` (1 seule tentative)
4. **Enregistrement** → Hash dans `sms_deduplication` + log dans `sms_logs`
5. **Message complet** jusqu'à 16000 caractères ✅

**Résultat :** **Plus de SMS triples ! 🎉** 