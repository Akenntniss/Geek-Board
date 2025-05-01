<?php
/**
 * Classe de gestion des notifications push pour PWA
 */
class PushNotifications {
    /**
     * Clés VAPID pour WebPush (à remplacer par vos clés générées)
     */
    private $vapidPublicKey = 'BNbxGYHtMYt33D8xJYLM834JG4fBXHs7o59ag9GhhXF27TGvAJCKsRQBYBjbmTJPRTzFdm0KXtNHI9Qw0sD0VwE';
    private $vapidPrivateKey = 'YOUR_PRIVATE_KEY'; // Remplacez par votre clé privée

    /**
     * Instance PDO pour les interactions avec la base de données
     */
    private $pdo;

    /**
     * Constructeur
     * 
     * @param PDO $pdo Instance PDO pour les interactions avec la base de données
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère la clé publique VAPID
     * 
     * @return string
     */
    public function getPublicKey() {
        return $this->vapidPublicKey;
    }

    /**
     * Envoie une notification push à un utilisateur spécifique
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $title Titre de la notification
     * @param string $body Corps de la notification
     * @param array $options Options supplémentaires (url, icon, etc.)
     * @return array Résultat de l'envoi
     */
    public function sendToUser($userId, $title, $body, $options = []) {
        try {
            // Récupérer les abonnements de l'utilisateur
            $stmt = $this->pdo->prepare("
                SELECT endpoint, auth_key, p256dh_key 
                FROM push_subscriptions 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subscriptions)) {
                return [
                    'success' => false,
                    'message' => 'Aucun abonnement trouvé pour cet utilisateur'
                ];
            }

            $results = [];
            foreach ($subscriptions as $subscription) {
                $result = $this->sendNotification(
                    $subscription,
                    $this->preparePayload($title, $body, $options)
                );
                $results[] = $result;
            }

            return [
                'success' => true,
                'message' => 'Notifications envoyées',
                'details' => $results
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envoie une notification push à tous les utilisateurs ou à un groupe spécifique
     * 
     * @param string $title Titre de la notification
     * @param string $body Corps de la notification
     * @param array $options Options supplémentaires (url, icon, role pour le ciblage, etc.)
     * @return array Résultat de l'envoi
     */
    public function sendToAll($title, $body, $options = []) {
        try {
            $sql = "
                SELECT ps.endpoint, ps.auth_key, ps.p256dh_key 
                FROM push_subscriptions ps
                JOIN users u ON ps.user_id = u.id
            ";
            $params = [];

            // Filtrage par rôle si spécifié
            if (isset($options['role']) && !empty($options['role'])) {
                $sql .= " WHERE u.role = ?";
                $params[] = $options['role'];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subscriptions)) {
                return [
                    'success' => false,
                    'message' => 'Aucun abonnement trouvé'
                ];
            }

            $results = [];
            $payload = $this->preparePayload($title, $body, $options);

            foreach ($subscriptions as $subscription) {
                try {
                    $result = $this->sendNotification($subscription, $payload);
                    $results[] = $result;
                } catch (Exception $e) {
                    // Ignorer les erreurs individuelles et continuer
                    $results[] = [
                        'success' => false,
                        'message' => $e->getMessage(),
                        'endpoint' => $subscription['endpoint']
                    ];
                }
            }

            // Enregistrer la notification dans la base de données
            $this->saveNotificationToDatabase($title, $body, $options);

            return [
                'success' => true,
                'message' => 'Notifications envoyées',
                'sent_count' => count($results),
                'details' => $results
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des notifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envoie une notification push programée (rappel)
     * 
     * @param int $notificationId ID de la notification programmée
     * @return array Résultat de l'envoi
     */
    public function sendScheduledNotification($notificationId) {
        try {
            // Récupérer les informations de la notification programmée
            $stmt = $this->pdo->prepare("
                SELECT n.id, n.title, n.message, n.action_url, n.target_user_id, n.is_broadcast,
                       n.notification_type, n.options
                FROM scheduled_notifications n
                WHERE n.id = ? AND n.status = 'pending' AND n.scheduled_datetime <= NOW()
            ");
            $stmt->execute([$notificationId]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$notification) {
                return [
                    'success' => false,
                    'message' => 'Notification programmée non trouvée ou déjà envoyée'
                ];
            }

            // Préparer les options
            $options = json_decode($notification['options'], true) ?: [];
            $options['url'] = $notification['action_url'];
            $options['type'] = $notification['notification_type'];

            // Envoyer la notification
            $result = null;
            if ($notification['is_broadcast']) {
                $result = $this->sendToAll($notification['title'], $notification['message'], $options);
            } else {
                $result = $this->sendToUser($notification['target_user_id'], $notification['title'], $notification['message'], $options);
            }

            // Mettre à jour le statut de la notification programmée
            $stmt = $this->pdo->prepare("
                UPDATE scheduled_notifications
                SET status = ?, sent_datetime = NOW()
                WHERE id = ?
            ");
            $status = $result['success'] ? 'sent' : 'failed';
            $stmt->execute([$status, $notificationId]);

            return array_merge($result, ['notification_id' => $notificationId]);
        } catch (Exception $e) {
            // Mettre à jour le statut en cas d'erreur
            $stmt = $this->pdo->prepare("
                UPDATE scheduled_notifications
                SET status = 'failed', sent_datetime = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$notificationId]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification programmée: ' . $e->getMessage(),
                'notification_id' => $notificationId
            ];
        }
    }

    /**
     * Envoie toutes les notifications programmées qui sont dues
     * 
     * @return array Résultat de l'envoi
     */
    public function sendAllScheduledNotifications() {
        try {
            // Récupérer toutes les notifications programmées qui sont dues
            $stmt = $this->pdo->prepare("
                SELECT id FROM scheduled_notifications
                WHERE status = 'pending' AND scheduled_datetime <= NOW()
            ");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($notifications)) {
                return [
                    'success' => true,
                    'message' => 'Aucune notification programmée à envoyer'
                ];
            }

            $results = [];
            foreach ($notifications as $notification) {
                $results[] = $this->sendScheduledNotification($notification['id']);
            }

            return [
                'success' => true,
                'message' => 'Notifications programmées envoyées',
                'sent_count' => count($results),
                'details' => $results
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des notifications programmées: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crée une notification programmée
     * 
     * @param string $title Titre de la notification
     * @param string $message Corps de la notification
     * @param string $scheduledDateTime Date et heure prévues (format MySQL datetime)
     * @param array $options Options supplémentaires
     * @return array Résultat de la création
     */
    public function scheduleNotification($title, $message, $scheduledDateTime, $options = []) {
        try {
            // Préparer les données
            $userId = isset($options['user_id']) ? $options['user_id'] : null;
            $isBroadcast = isset($options['is_broadcast']) ? $options['is_broadcast'] : true;
            $notificationType = isset($options['type']) ? $options['type'] : 'general';
            $actionUrl = isset($options['url']) ? $options['url'] : '/';
            $createdBy = isset($options['created_by']) ? $options['created_by'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
            
            // Options supplémentaires pour le JSON
            $jsonOptions = [];
            if (isset($options['icon'])) $jsonOptions['icon'] = $options['icon'];
            if (isset($options['tag'])) $jsonOptions['tag'] = $options['tag'];
            if (isset($options['renotify'])) $jsonOptions['renotify'] = $options['renotify'];
            if (isset($options['vibrate'])) $jsonOptions['vibrate'] = $options['vibrate'];
            if (isset($options['role'])) $jsonOptions['role'] = $options['role'];

            // Insérer dans la base de données
            $stmt = $this->pdo->prepare("
                INSERT INTO scheduled_notifications
                (title, message, scheduled_datetime, target_user_id, is_broadcast, 
                notification_type, action_url, created_by, status, options, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([
                $title,
                $message,
                $scheduledDateTime,
                $userId,
                $isBroadcast ? 1 : 0,
                $notificationType,
                $actionUrl,
                $createdBy,
                !empty($jsonOptions) ? json_encode($jsonOptions) : null
            ]);

            return [
                'success' => true,
                'message' => 'Notification programmée avec succès',
                'notification_id' => $this->pdo->lastInsertId()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la programmation de la notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Annule une notification programmée
     * 
     * @param int $notificationId ID de la notification programmée
     * @return array Résultat de l'annulation
     */
    public function cancelScheduledNotification($notificationId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE scheduled_notifications
                SET status = 'cancelled', updated_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$notificationId]);

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Notification programmée annulée avec succès'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Notification programmée non trouvée ou déjà envoyée'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère les notifications programmées
     * 
     * @param array $filters Filtres (status, user_id, date_from, date_to)
     * @return array Liste des notifications programmées
     */
    public function getScheduledNotifications($filters = []) {
        try {
            $conditions = [];
            $params = [];

            if (isset($filters['status']) && !empty($filters['status'])) {
                $conditions[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (isset($filters['user_id']) && !empty($filters['user_id'])) {
                $conditions[] = "(target_user_id = ? OR is_broadcast = 1)";
                $params[] = $filters['user_id'];
            }

            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $conditions[] = "scheduled_datetime >= ?";
                $params[] = $filters['date_from'];
            }

            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $conditions[] = "scheduled_datetime <= ?";
                $params[] = $filters['date_to'];
            }

            $sql = "
                SELECT n.*, 
                       u1.full_name AS target_user_name,
                       u2.full_name AS created_by_name
                FROM scheduled_notifications n
                LEFT JOIN users u1 ON n.target_user_id = u1.id
                LEFT JOIN users u2 ON n.created_by = u2.id
            ";

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " ORDER BY scheduled_datetime DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prépare le payload de la notification
     * 
     * @param string $title Titre
     * @param string $body Corps
     * @param array $options Options supplémentaires
     * @return string Payload JSON
     */
    private function preparePayload($title, $body, $options = []) {
        $payload = [
            'title' => $title,
            'body' => $body,
            'url' => isset($options['url']) ? $options['url'] : '/',
            'icon' => isset($options['icon']) ? $options['icon'] : '/assets/images/pwa-icons/icon-192x192.png',
            'badge' => '/assets/images/pwa-icons/icon-72x72.png',
            'tag' => isset($options['tag']) ? $options['tag'] : 'default',
            'renotify' => isset($options['renotify']) ? $options['renotify'] : false,
            'timestamp' => time() * 1000
        ];

        if (isset($options['actions'])) {
            $payload['actions'] = $options['actions'];
        }

        if (isset($options['vibrate'])) {
            $payload['vibrate'] = $options['vibrate'];
        } else {
            $payload['vibrate'] = [100, 50, 100];
        }

        return json_encode($payload);
    }

    /**
     * Envoie une notification push à un abonnement spécifique
     * Nécessite une bibliothèque WebPush comme minishlink/web-push
     * 
     * @param array $subscription Données de l'abonnement
     * @param string $payload Payload JSON
     * @return array Résultat de l'envoi
     */
    private function sendNotification($subscription, $payload) {
        // Simuler l'envoi pour le moment
        // Dans une implémentation réelle, utilisez une bibliothèque WebPush
        // comme minishlink/web-push
        
        // Exemple avec la bibliothèque minishlink/web-push (à installer via Composer)
        /*
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'mailto:example@example.com',
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ],
        ]);

        $endpoint = $subscription['endpoint'];
        $authToken = $subscription['auth_key'];
        $p256dh = $subscription['p256dh_key'];

        $result = $webPush->sendNotification(
            $endpoint,
            $payload,
            $p256dh,
            $authToken
        );
        */

        // Pour l'instant, nous simulons un envoi réussi
        return [
            'success' => true,
            'message' => 'Notification envoyée avec succès (simulation)',
            'endpoint' => $subscription['endpoint']
        ];
    }

    /**
     * Enregistre une notification dans la base de données
     * 
     * @param string $title Titre
     * @param string $body Message
     * @param array $options Options
     * @return bool Succès ou échec
     */
    private function saveNotificationToDatabase($title, $body, $options = []) {
        try {
            $notificationType = isset($options['type']) ? $options['type'] : 'general';
            $isImportant = isset($options['important']) ? $options['important'] : false;
            $isBroadcast = isset($options['is_broadcast']) ? $options['is_broadcast'] : true;
            $userId = isset($options['user_id']) ? $options['user_id'] : null;
            $relatedId = isset($options['related_id']) ? $options['related_id'] : null;
            $relatedType = isset($options['related_type']) ? $options['related_type'] : null;
            $actionUrl = isset($options['url']) ? $options['url'] : null;
            $createdBy = isset($options['created_by']) ? $options['created_by'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

            // Si c'est un broadcast, on insère pour tous les utilisateurs ou pour le rôle spécifié
            if ($isBroadcast) {
                $targetUserSql = "SELECT id FROM users";
                $targetUserParams = [];

                if (isset($options['role']) && !empty($options['role'])) {
                    $targetUserSql .= " WHERE role = ?";
                    $targetUserParams[] = $options['role'];
                }

                $stmt = $this->pdo->prepare($targetUserSql);
                $stmt->execute($targetUserParams);
                $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Préparer l'insertion en masse
                $values = [];
                $params = [];

                foreach ($targetUsers as $targetUserId) {
                    $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $params[] = $targetUserId;
                    $params[] = $notificationType;
                    $params[] = $body;
                    $params[] = $relatedId;
                    $params[] = $relatedType;
                    $params[] = $actionUrl;
                    $params[] = $isImportant ? 1 : 0;
                    $params[] = 1; // is_broadcast
                    $params[] = $createdBy;
                }

                if (!empty($values)) {
                    $sql = "
                        INSERT INTO notifications 
                        (user_id, notification_type, message, related_id, related_type, action_url, is_important, is_broadcast, created_by)
                        VALUES " . implode(', ', $values);
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);
                }
            } else {
                // Insertion pour un utilisateur spécifique
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications 
                    (user_id, notification_type, message, related_id, related_type, action_url, is_important, is_broadcast, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $notificationType,
                    $body,
                    $relatedId,
                    $relatedType,
                    $actionUrl,
                    $isImportant ? 1 : 0,
                    0, // is_broadcast
                    $createdBy
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de l\'enregistrement de la notification: ' . $e->getMessage());
            return false;
        }
    }
} 