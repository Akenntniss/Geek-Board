# Module de Messagerie v2.0

Ce module offre un système de messagerie complet et moderne pour la plateforme MD Geek.

## Fonctionnalités

- Messagerie instantanée avec interface utilisateur moderne
- Support des conversations directes et de groupe
- Envoi de messages texte, d'images et de fichiers
- Notifications en temps réel
- Marquage de lectures
- Filtres de conversations (tous, non lus, directs, groupes, favoris, archivés)
- Recherche dans les conversations

## Structure des fichiers

```
messagerie/
├── api/                   # API endpoints pour les requêtes AJAX
│   ├── create_conversation.php
│   ├── get_conversations.php
│   ├── get_messages.php
│   ├── get_new_messages.php
│   ├── get_users.php
│   ├── send_message.php
│   └── ...
├── css/                   # Feuilles de style
│   └── messagerie.css
├── includes/              # Fonctions et utilitaires
│   └── functions.php
├── js/                    # Scripts JavaScript
│   └── messagerie.js
├── logs/                  # Journaux d'erreurs
│   └── messagerie_errors.log
├── uploads/               # Fichiers uploadés
│   ├── files/
│   ├── images/
│   └── thumbnails/
├── index.php              # Page principale
└── README.md              # Documentation
```

## Base de données

Le module utilise les tables suivantes :

- `conversations` - Stocke les informations des conversations
- `conversation_participants` - Relie les utilisateurs aux conversations
- `messages` - Stocke les messages envoyés
- `message_attachments` - Stocke les pièces jointes des messages
- `message_reads` - Enregistre les lectures de messages
- `message_reactions` - Stocke les réactions aux messages

## Installation

1. Importer le schéma SQL depuis `sql/messagerie_schema.sql`
2. Assurez-vous que les dossiers d'upload sont accessibles en écriture
3. Vérifiez que la configuration de base de données est correcte

## Utilisation

Accédez à la messagerie via `/messagerie/` ou créez un lien dans votre application.

```php
<a href="/messagerie/">
    <i class="fas fa-envelope"></i> Messagerie
    <?php if ($unread_count > 0): ?>
    <span class="badge bg-danger"><?php echo $unread_count; ?></span>
    <?php endif; ?>
</a>
```

Pour obtenir le nombre de messages non lus :

```php
require_once 'messagerie/includes/functions.php';
$unread_count = count_unread_messages($_SESSION['user_id']);
```

## Personnalisation

Les couleurs et éléments visuels peuvent être modifiés dans `messagerie.css`.

## Technologies utilisées

- PHP 7.4+
- MariaDB/MySQL
- JavaScript (ES6+)
- HTML5/CSS3
- Bootstrap 5
- Font Awesome
- Select2 (pour les sélecteurs d'utilisateurs)

## Maintenance

Les journaux d'erreurs sont stockés dans `logs/messagerie_errors.log`. 