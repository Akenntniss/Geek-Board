<?php
require_once 'config.php';

class Message {
    private $db;
    
    public function __construct() {
        $this->db = get_db_connection();
    }
    
    /**
     * Récupère les conversations d'un utilisateur
     */
    public function getConversations($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM messages m 
                        WHERE m.conversation_id = c.id 
                        AND m.created_at > COALESCE(cp.last_read_time, '1970-01-01')) as unread_count,
                       (SELECT m.content FROM messages m 
                        WHERE m.conversation_id = c.id 
                        ORDER BY m.created_at DESC LIMIT 1) as last_message,
                       (SELECT u.nom FROM messages m 
                        JOIN users u ON m.sender_id = u.id 
                        WHERE m.conversation_id = c.id 
                        ORDER BY m.created_at DESC LIMIT 1) as last_sender_name,
                       (SELECT m.created_at FROM messages m 
                        WHERE m.conversation_id = c.id 
                        ORDER BY m.created_at DESC LIMIT 1) as last_message_time
                FROM conversations c
                JOIN conversation_participants cp ON c.id = cp.conversation_id
                WHERE cp.user_id = :user_id
                ORDER BY last_message_time DESC
            ");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des conversations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les messages d'une conversation
     */
    public function getMessages($conversationId, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, u.nom as sender_name, u.prenom as sender_firstname, 
                       (SELECT GROUP_CONCAT(file_path) FROM attachments WHERE message_id = m.id) as attachments
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = :conversation_id
                ORDER BY m.created_at ASC
                LIMIT :offset, :limit
            ");
            $stmt->bindParam(':conversation_id', $conversationId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll();
            
            // Récupérer les confirmations de lecture pour chaque message
            foreach ($messages as &$message) {
                if ($message['requires_confirmation']) {
                    $confirmations = $this->getMessageConfirmations($message['id']);
                    $message['confirmations'] = $confirmations;
                }
                
                // Convertir les pièces jointes en tableau
                if (!empty($message['attachments'])) {
                    $message['attachments'] = explode(',', $message['attachments']);
                } else {
                    $message['attachments'] = [];
                }
            }
            
            return $messages;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des messages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Envoie un nouveau message
     */
    public function sendMessage($conversationId, $senderId, $content, $isImportant = false, $requiresConfirmation = false, $attachments = []) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO messages (conversation_id, sender_id, content, is_important, requires_confirmation)
                VALUES (:conversation_id, :sender_id, :content, :is_important, :requires_confirmation)
            ");
            $stmt->execute([
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'content' => $content,
                'is_important' => $isImportant ? 1 : 0,
                'requires_confirmation' => $requiresConfirmation ? 1 : 0
            ]);
            
            $messageId = $this->db->lastInsertId();
            
            // Traiter les pièces jointes
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $this->addAttachment($messageId, $attachment);
                }
            }
            
            // Traiter les mentions (@utilisateur)
            $this->processMentions($messageId, $content);
            
            // Mettre à jour la date de la conversation
            $stmt = $this->db->prepare("
                UPDATE conversations 
                SET updated_at = CURRENT_TIMESTAMP
                WHERE id = :conversation_id
            ");
            $stmt->execute(['conversation_id' => $conversationId]);
            
            $this->db->commit();
            return $messageId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de l'envoi du message: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute une pièce jointe à un message
     */
    private function addAttachment($messageId, $file) {
        $fileName = $file['name'];
        $fileType = $file['type'];
        $fileSize = $file['size'];
        $fileTmpName = $file['tmp_name'];
        
        // Générer un nom de fichier unique
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $uniqueName = uniqid() . '_' . time() . '.' . $fileExt;
        $uploadPath = MSG_UPLOAD_PATH . $uniqueName;
        
        // Déplacer le fichier
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO attachments (message_id, file_name, file_path, file_type, file_size)
                    VALUES (:message_id, :file_name, :file_path, :file_type, :file_size)
                ");
                $stmt->execute([
                    'message_id' => $messageId,
                    'file_name' => $fileName,
                    'file_path' => $uploadPath,
                    'file_type' => $fileType,
                    'file_size' => $fileSize
                ]);
                return true;
            } catch (PDOException $e) {
                error_log("Erreur lors de l'ajout de la pièce jointe: " . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Extrait et traite les mentions (@utilisateur) dans le message
     */
    private function processMentions($messageId, $content) {
        // Extraire les mentions (format @utilisateur)
        preg_match_all('/@(\w+)/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                // Trouver l'utilisateur par son nom d'utilisateur
                $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Ajouter la mention
                    $stmt = $this->db->prepare("
                        INSERT INTO mentions (message_id, user_id)
                        VALUES (:message_id, :user_id)
                    ");
                    $stmt->execute([
                        'message_id' => $messageId,
                        'user_id' => $user['id']
                    ]);
                }
            }
        }
    }
    
    /**
     * Crée une nouvelle conversation
     */
    public function createConversation($title, $type, $creatorId, $participants) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO conversations (titre, type)
                VALUES (:titre, :type)
            ");
            $stmt->execute([
                'titre' => $title,
                'type' => $type
            ]);
            
            $conversationId = $this->db->lastInsertId();
            
            // Ajouter le créateur comme administrateur
            $stmt = $this->db->prepare("
                INSERT INTO conversation_participants (conversation_id, user_id, is_admin)
                VALUES (:conversation_id, :user_id, 1)
            ");
            $stmt->execute([
                'conversation_id' => $conversationId,
                'user_id' => $creatorId
            ]);
            
            // Ajouter les autres participants
            if (!empty($participants)) {
                $stmt = $this->db->prepare("
                    INSERT INTO conversation_participants (conversation_id, user_id)
                    VALUES (:conversation_id, :user_id)
                ");
                
                foreach ($participants as $participantId) {
                    if ($participantId != $creatorId) { // Éviter les doublons
                        $stmt->execute([
                            'conversation_id' => $conversationId,
                            'user_id' => $participantId
                        ]);
                    }
                }
            }
            
            $this->db->commit();
            return $conversationId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la création de la conversation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marque les messages comme lus pour un utilisateur
     */
    public function markAsRead($conversationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE conversation_participants
                SET last_read_time = CURRENT_TIMESTAMP
                WHERE conversation_id = :conversation_id AND user_id = :user_id
            ");
            $stmt->execute([
                'conversation_id' => $conversationId,
                'user_id' => $userId
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors du marquage des messages comme lus: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Confirme la lecture d'un message important
     */
    public function confirmMessageRead($messageId, $userId) {
        try {
            // Vérifier si le message existe et nécessite une confirmation
            $stmt = $this->db->prepare("
                SELECT requires_confirmation FROM messages
                WHERE id = :message_id
            ");
            $stmt->execute(['message_id' => $messageId]);
            $message = $stmt->fetch();
            
            if (!$message || !$message['requires_confirmation']) {
                return false;
            }
            
            // Ajouter la confirmation de lecture
            $stmt = $this->db->prepare("
                INSERT INTO message_confirmations (message_id, user_id)
                VALUES (:message_id, :user_id)
                ON DUPLICATE KEY UPDATE confirmation_time = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                'message_id' => $messageId,
                'user_id' => $userId
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la confirmation de lecture: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les confirmations de lecture pour un message
     */
    public function getMessageConfirmations($messageId) {
        try {
            $stmt = $this->db->prepare("
                SELECT mc.*, u.nom, u.prenom
                FROM message_confirmations mc
                JOIN users u ON mc.user_id = u.id
                WHERE mc.message_id = :message_id
            ");
            $stmt->execute(['message_id' => $messageId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des confirmations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Vérifie si un utilisateur est participant à une conversation
     */
    public function isParticipant($conversationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM conversation_participants
                WHERE conversation_id = :conversation_id AND user_id = :user_id
            ");
            $stmt->execute([
                'conversation_id' => $conversationId,
                'user_id' => $userId
            ]);
            $result = $stmt->fetch();
            
            return $result && $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du participant: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les détails d'une conversation
     */
    public function getConversationDetails($conversationId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = c.id) as participants_count
                FROM conversations c
                WHERE c.id = :conversation_id
            ");
            $stmt->execute(['conversation_id' => $conversationId]);
            $conversation = $stmt->fetch();
            
            if (!$conversation) {
                return null;
            }
            
            // Récupérer les participants
            $stmt = $this->db->prepare("
                SELECT cp.*, u.nom, u.prenom, u.email
                FROM conversation_participants cp
                JOIN users u ON cp.user_id = u.id
                WHERE cp.conversation_id = :conversation_id
            ");
            $stmt->execute(['conversation_id' => $conversationId]);
            $conversation['participants'] = $stmt->fetchAll();
            
            return $conversation;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des détails de la conversation: " . $e->getMessage());
            return null;
        }
    }
}
?> 