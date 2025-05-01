<?php
/**
 * Fonctions pour le système de messagerie - Version optimisée
 */

/**
 * Récupère les conversations d'un utilisateur
 *
 * @param int $user_id ID de l'utilisateur
 * @param string $search Terme de recherche (optionnel)
 * @return array Liste des conversations
 */
function get_user_conversations($user_id, $search = '') {
    global $pdo;
    
    try {
        $query = "
            SELECT c.*, 
                   cp.role,
                   cp.date_derniere_lecture,
                   u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM messages m 
                    WHERE m.conversation_id = c.id 
                    AND m.date_envoi > COALESCE(cp.date_derniere_lecture, '2000-01-01')) as unread_count,
                   (SELECT m.contenu FROM messages m 
                    WHERE m.conversation_id = c.id 
                    ORDER BY m.date_envoi DESC LIMIT 1) as dernier_message,
                   (SELECT m.date_envoi FROM messages m 
                    WHERE m.conversation_id = c.id 
                    ORDER BY m.date_envoi DESC LIMIT 1) as date_dernier_message,
                   (SELECT u.full_name FROM messages m 
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.conversation_id = c.id 
                    ORDER BY m.date_envoi DESC LIMIT 1) as dernier_expediteur
            FROM conversations c
            JOIN conversation_participants cp ON c.id = cp.conversation_id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE cp.user_id = :user_id
        ";
        
        $params = [':user_id' => $user_id];
        
        if (!empty($search)) {
            $query .= " AND (c.titre LIKE :search 
                            OR EXISTS (SELECT 1 FROM messages m2 
                                      WHERE m2.conversation_id = c.id 
                                      AND m2.contenu LIKE :search_content))";
            $params[':search'] = "%$search%";
            $params[':search_content'] = "%$search%";
        }
        
        $query .= " ORDER BY date_dernier_message DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des conversations : " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les messages d'une conversation
 *
 * @param int $conversation_id ID de la conversation
 * @param int $user_id ID de l'utilisateur actuel
 * @param int $limit Nombre de messages à récupérer (pagination)
 * @param int $offset Offset pour la pagination
 * @return array Messages de la conversation
 */
function get_conversation_messages($conversation_id, $user_id, $limit = 50, $offset = 0) {
    global $pdo;
    
    try {
        // Vérifier d'abord que l'utilisateur est bien participant de cette conversation
        $check_query = "SELECT 1 FROM conversation_participants 
                       WHERE conversation_id = :conversation_id 
                       AND user_id = :user_id";
                       
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $user_id
        ]);
        
        if ($check_stmt->rowCount() == 0) {
            return ['error' => 'Vous n\'avez pas accès à cette conversation'];
        }
        
        // Mettre à jour la date de dernière lecture
        update_last_read($conversation_id, $user_id);
        
        // Récupérer les messages - Requête simplifiée
        $query = "
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = :conversation_id
            ORDER BY m.date_envoi DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajouter l'information "is_mine" pour chaque message
        foreach ($messages as &$message) {
            $message['is_mine'] = ($message['sender_id'] == $user_id);
        }
        
        // Inverser l'ordre pour afficher du plus ancien au plus récent
        return array_reverse($messages);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des messages : " . $e->getMessage());
        return ['error' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()];
    }
}

/**
 * Récupère les nouveaux messages depuis un certain ID
 *
 * @param int $conversation_id ID de la conversation
 * @param int $user_id ID de l'utilisateur actuel
 * @param int $last_id Dernier ID de message
 * @return array Nouveaux messages
 */
function get_new_messages($conversation_id, $user_id, $last_id) {
    global $pdo;
    
    try {
        // Vérifier que l'utilisateur est bien participant
        $check_query = "SELECT 1 FROM conversation_participants 
                       WHERE conversation_id = :conversation_id 
                       AND user_id = :user_id";
                       
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $user_id
        ]);
        
        if ($check_stmt->rowCount() == 0) {
            return ['error' => 'Vous n\'avez pas accès à cette conversation'];
        }
        
        // Mettre à jour la date de dernière lecture
        update_last_read($conversation_id, $user_id);
        
        // Récupérer les nouveaux messages
        $query = "
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = :conversation_id
            AND m.id > :last_id
            ORDER BY m.date_envoi ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':last_id' => $last_id
        ]);
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajouter l'information "is_mine" pour chaque message
        foreach ($messages as &$message) {
            $message['is_mine'] = ($message['sender_id'] == $user_id);
        }
        
        return $messages;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des nouveaux messages : " . $e->getMessage());
        return ['error' => 'Erreur lors de la récupération des nouveaux messages: ' . $e->getMessage()];
    }
}

/**
 * Mettre à jour la date de dernière lecture d'une conversation
 *
 * @param int $conversation_id ID de la conversation
 * @param int $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function update_last_read($conversation_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE conversation_participants 
            SET date_derniere_lecture = NOW() 
            WHERE conversation_id = :conversation_id 
            AND user_id = :user_id
        ");
        
        return $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $user_id
        ]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour de la date de dernière lecture : " . $e->getMessage());
        return false;
    }
}

/**
 * Créer une nouvelle conversation
 *
 * @param string $titre Titre de la conversation
 * @param string $type Type de conversation (direct, groupe)
 * @param int $created_by ID de l'utilisateur qui crée la conversation
 * @param array $participants Liste des IDs des participants
 * @param string $first_message Premier message de la conversation (optionnel)
 * @return array Résultat de l'opération avec l'ID de la conversation créée
 */
function create_conversation($titre, $type, $created_by, $participants, $first_message = '') {
    global $pdo;
    
    // S'assurer que le créateur est dans la liste des participants
    if (!in_array($created_by, $participants)) {
        $participants[] = $created_by;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Créer la conversation
        $stmt = $pdo->prepare("
            INSERT INTO conversations (titre, type, created_by, date_creation) 
            VALUES (:titre, :type, :created_by, NOW())
        ");
        
        $stmt->execute([
            ':titre' => $titre,
            ':type' => $type,
            ':created_by' => $created_by
        ]);
        
        $conversation_id = $pdo->lastInsertId();
        
        // Ajouter les participants
        foreach ($participants as $participant_id) {
            $role = ($participant_id == $created_by) ? 'admin' : 'membre';
            
            $stmt = $pdo->prepare("
                INSERT INTO conversation_participants (conversation_id, user_id, role, date_derniere_lecture) 
                VALUES (:conversation_id, :user_id, :role, NOW())
            ");
            
            $stmt->execute([
                ':conversation_id' => $conversation_id,
                ':user_id' => $participant_id,
                ':role' => $role
            ]);
        }
        
        // Ajouter le premier message si fourni
        if (!empty($first_message)) {
            $stmt = $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, contenu, type, date_envoi) 
                VALUES (:conversation_id, :sender_id, :contenu, 'texte', NOW())
            ");
            
            $stmt->execute([
                ':conversation_id' => $conversation_id,
                ':sender_id' => $created_by,
                ':contenu' => $first_message
            ]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'conversation_id' => $conversation_id
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erreur lors de la création de la conversation : " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erreur lors de la création de la conversation: ' . $e->getMessage()
        ];
    }
}

/**
 * Envoyer un nouveau message
 *
 * @param int $conversation_id ID de la conversation
 * @param int $sender_id ID de l'expéditeur
 * @param string $contenu Contenu du message
 * @param string $type Type de message (texte, fichier)
 * @param array $fichier Informations sur le fichier (optionnel)
 * @return array Résultat de l'opération avec le message créé
 */
function send_message($conversation_id, $sender_id, $contenu, $type = 'texte', $fichier = null) {
    global $pdo;
    
    // Vérifier que l'expéditeur est bien participant de cette conversation
    $check_query = "SELECT 1 FROM conversation_participants 
                   WHERE conversation_id = :conversation_id 
                   AND user_id = :user_id";
                   
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([
        ':conversation_id' => $conversation_id,
        ':user_id' => $sender_id
    ]);
    
    if ($check_stmt->rowCount() == 0) {
        return [
            'success' => false,
            'error' => 'Vous n\'êtes pas autorisé à envoyer des messages dans cette conversation'
        ];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insérer le message
        $query = "
            INSERT INTO messages (conversation_id, sender_id, contenu, type, date_envoi) 
            VALUES (:conversation_id, :sender_id, :contenu, :type, NOW())
        ";
        
        $params = [
            ':conversation_id' => $conversation_id,
            ':sender_id' => $sender_id,
            ':contenu' => $contenu,
            ':type' => $type
        ];
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $message_id = $pdo->lastInsertId();
        
        // Si c'est un fichier, ajouter les informations du fichier
        if ($type == 'fichier' && $fichier) {
            $file_query = "
                UPDATE messages 
                SET fichier_url = :url,
                    fichier_nom = :nom,
                    fichier_type = :type,
                    fichier_taille = :taille
                WHERE id = :message_id
            ";
            
            $stmt = $pdo->prepare($file_query);
            $stmt->execute([
                ':url' => $fichier['url'],
                ':nom' => $fichier['nom'],
                ':type' => $fichier['type'],
                ':taille' => $fichier['taille'],
                ':message_id' => $message_id
            ]);
        }
        
        // Mettre à jour la date de dernière lecture pour l'expéditeur
        update_last_read($conversation_id, $sender_id);
        
        // Récupérer le message complet pour le retourner
        $message_query = "
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id = :message_id
        ";
        
        $stmt = $pdo->prepare($message_query);
        $stmt->execute([':message_id' => $message_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ajouter l'information "is_mine"
        if ($message) {
            $message['is_mine'] = true;
            $message['time'] = date('H:i', strtotime($message['date_envoi']));
            $message['date'] = date('d/m/Y', strtotime($message['date_envoi']));
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message_id' => $message_id,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erreur lors de l'envoi du message : " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtenir les détails d'une conversation
 *
 * @param int $conversation_id ID de la conversation
 * @param int $user_id ID de l'utilisateur actuel
 * @return array Détails de la conversation
 */
function get_conversation_details($conversation_id, $user_id) {
    global $pdo;
    
    try {
        // Vérifier que l'utilisateur est bien participant
        $check_query = "SELECT 1 FROM conversation_participants 
                       WHERE conversation_id = :conversation_id 
                       AND user_id = :user_id";
                       
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $user_id
        ]);
        
        if ($check_stmt->rowCount() == 0) {
            return ['error' => 'Vous n\'avez pas accès à cette conversation'];
        }
        
        // Obtenir les détails de la conversation
        $query = "
            SELECT c.*, 
                   u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id) as message_count
            FROM conversations c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = :conversation_id
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':conversation_id' => $conversation_id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            return ['error' => 'Conversation non trouvée'];
        }
        
        // Obtenir la liste des participants
        $participants_query = "
            SELECT cp.*, u.full_name, u.username
            FROM conversation_participants cp
            JOIN users u ON cp.user_id = u.id
            WHERE cp.conversation_id = :conversation_id
            ORDER BY cp.role = 'admin' DESC, u.full_name
        ";
        
        $stmt = $pdo->prepare($participants_query);
        $stmt->execute([':conversation_id' => $conversation_id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $conversation['participants'] = $participants;
        
        return $conversation;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des détails de la conversation : " . $e->getMessage());
        return ['error' => 'Erreur lors de la récupération des détails de la conversation: ' . $e->getMessage()];
    }
}

/**
 * Récupérer tous les utilisateurs pour la sélection des participants
 *
 * @param int $current_user_id ID de l'utilisateur actuel (pour l'exclure)
 * @return array Liste des utilisateurs
 */
function get_users_for_participants($current_user_id = null) {
    global $pdo;
    
    try {
        $query = "SELECT id, full_name, username, role FROM users";
        
        if ($current_user_id) {
            $query .= " WHERE id != :current_user_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':current_user_id' => $current_user_id]);
        } else {
            $stmt = $pdo->query($query);
        }
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatter pour select2
        $formatted_users = [];
        foreach ($users as $user) {
            $formatted_users[] = [
                'id' => $user['id'],
                'text' => $user['full_name'] . ' (' . $user['username'] . ')'
            ];
        }
        
        return $formatted_users;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des utilisateurs : " . $e->getMessage());
        return [];
    }
}

/**
 * Télécharger un fichier pour la messagerie
 *
 * @param array $file Tableau $_FILES contenant le fichier
 * @return array Résultat de l'opération avec les informations du fichier
 */
function upload_message_file($file) {
    // Vérifier si le fichier existe et qu'il n'y a pas d'erreur
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'error' => 'Erreur lors de l\'upload du fichier'
        ];
    }
    
    // Créer le répertoire s'il n'existe pas
    $upload_dir = '../uploads/messagerie/' . date('Y/m/d');
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Générer un nom de fichier unique
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $upload_dir . '/' . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'fichier' => [
                'url' => str_replace('../', '', $filepath),
                'nom' => basename($file['name']),
                'type' => $file['type'],
                'taille' => $file['size']
            ]
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Erreur lors du déplacement du fichier'
        ];
    }
}

/**
 * Rechercher des messages dans les conversations d'un utilisateur
 *
 * @param int $user_id ID de l'utilisateur
 * @param string $search_term Terme de recherche
 * @return array Résultats de la recherche
 */
function search_messages($user_id, $search_term) {
    global $pdo;
    
    if (empty($search_term)) {
        return [];
    }
    
    try {
        $query = "
            SELECT m.*, 
                   c.titre as conversation_title,
                   u.full_name as sender_name
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            JOIN users u ON m.sender_id = u.id
            JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
            WHERE cp.user_id = :user_id
            AND (m.contenu LIKE :search_term OR c.titre LIKE :search_term)
            ORDER BY m.date_envoi DESC
            LIMIT 50
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':search_term' => "%$search_term%"
        ]);
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajouter l'information "is_mine" pour chaque message
        foreach ($messages as &$message) {
            $message['is_mine'] = ($message['sender_id'] == $user_id);
        }
        
        return $messages;
    } catch (PDOException $e) {
        error_log("Erreur lors de la recherche de messages : " . $e->getMessage());
        return [];
    }
}
?> 