# Prompt pour la page de réparation

## Design de l'interface

### Interface principale
```
┌─────────────────────────────────────────────────────────────────────────┐
│                        GESTION DES RÉPARATIONS                          │
├─────────────────────────────────────────────────────────────────────────┤
│ ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐  │
│ │Récentes│ │Nouvelles│ │En cours│ │En attente│ │Terminées│ │Archivées│  │
│ │   42   │ │   15   │ │   8    │ │   12    │ │   35    │ │   78    │  │
│ └───────┘ └───────┘ └───────┘ └───────┘ └───────┘ └───────┘ └───────┘  │
├─────────────────────────────────────────────────────────────────────────┤
│  Recherche: [                                        ]  🔍               │
├─────────────────────────────────────────────────────────────────────────┤
│  Filtres: ▼ Type d'appareil  ▼ Date  ▼ Status                           │
│           📱 Smartphone      📅 Cette semaine  🔵 En cours               │
├─────────────────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────┐ ┌─────────────────────────┐                 │
│ │ 📱 iPhone 13 Pro Max    │ │ 💻 MacBook Pro 2021     │                 │
│ │ 📋 #458 - Martin Paul   │ │ 📋 #459 - Dubois Sophie │                 │
│ │ 🔧 Réparation d'écran   │ │ 🔧 Remplacement batterie │                 │
│ │ 📅 Reçu: 05/05/2025     │ │ 📅 Reçu: 05/05/2025     │                 │
│ │ 💰 Prix: 155,00 €       │ │ 💰 Prix: 95,00 €        │                 │
│ │ 🟢 Prêt à être récupéré │ │ 🔵 En cours             │                 │
│ │ ✉️ SMS  📞 Appeler      │ │ ✉️ SMS  📞 Appeler      │                 │
│ └─────────────────────────┘ └─────────────────────────┘                 │
│ ┌─────────────────────────┐ ┌─────────────────────────┐                 │
│ │ 📱 Samsung Galaxy S22   │ │ 💻 HP Pavilion          │                 │
│ │ 📋 #460 - Dupont Marie  │ │ 📋 #461 - Lefebvre Jean │                 │
│ │ 🔧 Problème logiciel    │ │ 🔧 Problème démarrage   │                 │
│ │ 📅 Reçu: 06/05/2025     │ │ 📅 Reçu: 06/05/2025     │                 │
│ │ 💰 Prix: 50,00 €        │ │ 💰 Prix: 120,00 €       │                 │
│ │ 🟡 Diagnostic en cours  │ │ 🟠 En attente pièce     │                 │
│ │ ✉️ SMS  📞 Appeler      │ │ ✉️ SMS  📞 Appeler      │                 │
│ └─────────────────────────┘ └─────────────────────────┘                 │
│                                                                         │
│ + Ajouter une réparation                                       🔄       │
└─────────────────────────────────────────────────────────────────────────┘
```

### Modal d'envoi de SMS
```
┌─────────────────────────────────────────────────────────────────┐
│                       ENVOYER UN SMS                            │
├─────────────────────────────────────────────────────────────────┤
│ Destinataire: Dupont Marie                                      │
│ Téléphone: +33 6 12 34 56 78                                    │
│                                                                 │
│ Modèle de SMS: ▼ [Diagnostic terminé]                           │
│                                                                 │
│ Message:                                                        │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Bonjour, Marie,                                             │ │
│ │ le devis de votre Samsung Galaxy S22 est disponible.        │ │
│ │ Montant : 50,00 €                                           │ │
│ │ Consultez-le ici :                                          │ │
│ │ 📄 http://Mdgeek.top/suivi.php?id=460                       │ │
│ │ Une question ? Appelez-nous au 04 93 46 71 63               │ │
│ │ MAISON DU GEEK                                              │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ Caractères: 152/160 | SMS: 1                                    │
│                                                                 │
│ [ Annuler ]                               [ Envoyer le SMS ✓ ]  │
└─────────────────────────────────────────────────────────────────┘
```

## Variables disponibles dans les SMS

Les variables suivantes peuvent être utilisées dans les templates SMS :

- `[CLIENT_NOM]` - Nom du client
- `[CLIENT_PRENOM]` - Prénom du client
- `[CLIENT_TELEPHONE]` - Numéro de téléphone du client
- `[REPARATION_ID]` - Numéro ou ID de la réparation
- `[APPAREIL_TYPE]` - Type d'appareil (Smartphone, Ordinateur, etc.)
- `[APPAREIL_MARQUE]` - Marque de l'appareil
- `[APPAREIL_MODELE]` - Modèle de l'appareil
- `[DATE_RECEPTION]` - Date de réception de l'appareil
- `[DATE_FIN_PREVUE]` - Date de fin prévue de la réparation
- `[PRIX]` - Prix de la réparation

## Templates SMS recommandés

### 1. Diagnostic terminé / Devis
```
Bonjour, [CLIENT_PRENOM], 
le devis de votre [APPAREIL_MODELE] est disponible. 
Montant : [PRIX]
Consultez-le ici :
📄 http://Mdgeek.top/suivi.php?id=[REPARATION_ID]
Une question ? Appelez-nous au 04 93 46 71 63
MAISON DU GEEK
```

### 2. Réparation terminée
```
Bonjour [CLIENT_PRENOM],
Votre [APPAREIL_TYPE] [APPAREIL_MODELE] est prêt à être récupéré.
Montant : [PRIX]
Nous sommes ouverts du lundi au vendredi de 9h à 19h.
MAISON DU GEEK - 04 93 46 71 63
```

### 3. En attente de pièce
```
Bonjour [CLIENT_PRENOM],
Concernant votre [APPAREIL_TYPE] [APPAREIL_MODELE] (dossier #[REPARATION_ID]) :
Nous sommes en attente de pièces pour finaliser la réparation.
Délai estimé : 3-5 jours ouvrés.
Nous vous tiendrons informé.
MAISON DU GEEK - 04 93 46 71 63
```

### 4. Rappel de réparation non récupérée
```
Bonjour [CLIENT_PRENOM],
Votre [APPAREIL_TYPE] [APPAREIL_MODELE] est prêt depuis le [DATE_FIN_PREVUE].
Nous vous rappelons qu'au-delà de 30 jours, des frais de gardiennage de 2€/jour seront appliqués.
MAISON DU GEEK - 04 93 46 71 63
```

## Palette de couleurs

- Primaire (bleu): #3b82f6
- Secondaire (gris): #64748b
- Succès (vert): #16a34a
- Danger (rouge): #dc2626
- Warning (jaune): #ca8a04
- Info (bleu clair): #4f46e5
- Fond: #f1f5f9
- Texte: #1e293b
- Blanc: #ffffff

## Statuts et leurs couleurs

- Nouveau / Diagnostic (🟡 jaune): #ca8a04
- En cours de réparation (🔵 bleu): #3b82f6
- En attente (pièce/client) (🟠 orange): #ea580c
- Terminé / Prêt (🟢 vert): #16a34a
- Annulé / Problème (🔴 rouge): #dc2626
- Archivé (⚪ gris): #64748b 