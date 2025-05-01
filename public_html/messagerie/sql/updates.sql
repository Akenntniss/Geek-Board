-- Mise à jour du schema de la messagerie pour la version 2.1
-- Ajout des fonctionnalités d'indicateur de frappe, d'édition de messages, etc.

-- Désactiver les contraintes de clés étrangères temporairement
SET FOREIGN_KEY_CHECKS=0;

-- Table pour suivre les statuts de frappe des utilisateurs
CREATE TABLE IF NOT EXISTS typing_status (
    user_id INT NOT NULL,
    conversation_id INT NOT NULL,
    timestamp DATETIME NOT NULL,
    PRIMARY KEY (user_id, conversation_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout d'une colonne à message_reads pour stocker des métadonnées supplémentaires
ALTER TABLE message_reads
ADD COLUMN IF NOT EXISTS metadata JSON DEFAULT NULL;

-- Ajout d'un index pour accélérer les requêtes sur les réactions
CREATE INDEX IF NOT EXISTS idx_message_reactions_message_id ON message_reactions(message_id);
CREATE INDEX IF NOT EXISTS idx_message_reactions_user_id ON message_reactions(user_id);

-- Table pour les réponses aux messages (références)
CREATE TABLE IF NOT EXISTS message_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    reply_to_id INT NOT NULL,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reply (message_id, reply_to_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Réactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS=1; 