<?php
/**
 * Gestionnaire de synchronisation de données
 * 
 * Ce script traite les données envoyées depuis le client lorsqu'il se reconnecte
 * après avoir été hors ligne.
 */

// Configuration et bases de données
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
$shop_pdo = getShopDBConnection();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour synchroniser des données'
    ]);
    exit;
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Récupérer les données JSON
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Vérifier que les données sont valides
if (!$data || !isset($data['table']) || !isset($data['action']) || !isset($data['data'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Données invalides'
    ]);
    exit;
}

// Tables autorisées
$allowedTables = ['repairs', 'clients', 'tasks'];

// Vérifier que la table est autorisée
if (!in_array($data['table'], $allowedTables)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Table non autorisée'
    ]);
    exit;
}

// Synchroniser les données
try {
    $result = syncData($data['table'], $data['action'], $data['data']);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Synchronisation réussie',
        'data' => $result
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Fonction de synchronisation des données
 */
function syncData($table, $action, $itemData) {
    global $conn;
    
    switch ($table) {
        case 'repairs':
            return syncRepairs($action, $itemData);
        case 'clients':
            return syncClients($action, $itemData);
        case 'tasks':
            return syncTasks($action, $itemData);
        default:
            throw new Exception('Table non prise en charge');
    }
}

/**
 * Synchronisation des réparations
 */
function syncRepairs($action, $data) {
    global $conn;
    
    switch ($action) {
        case 'add':
            // Supprimer les champs non nécessaires
            unset($data['id']);
            unset($data['sync_status']);
            unset($data['offline_id']);
            
            // Enregistrer l'ID hors ligne pour référence
            $offlineId = $data['offline_id'] ?? null;
            
            // Ajouter l'utilisateur et la date
            $data['user_id'] = $_SESSION['user_id'];
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Construire la requête SQL
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $stmt = $shop_pdo->prepare("INSERT INTO repairs ($columns) VALUES ($placeholders)");
            
            // Lier les paramètres
            $i = 1;
            foreach ($data as $value) {
                $stmt->bindValue($i++, $value);
            }
            
            // Exécuter la requête
            $stmt->execute();
            
            // Récupérer l'ID inséré
            $id = $shop_pdo->lastInsertId();
            
            return [
                'id' => $id,
                'offline_id' => $offlineId
            ];
            
        case 'update':
            $id = $data['id'];
            
            // Supprimer les champs non nécessaires
            unset($data['id']);
            unset($data['sync_status']);
            unset($data['offline_id']);
            
            // Mettre à jour la date de modification
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Construire la requête SQL
            $updates = [];
            foreach (array_keys($data) as $column) {
                $updates[] = "$column = ?";
            }
            $updateStr = implode(', ', $updates);
            
            $stmt = $shop_pdo->prepare("UPDATE repairs SET $updateStr WHERE id = ?");
            
            // Lier les paramètres
            $i = 1;
            foreach ($data as $value) {
                $stmt->bindValue($i++, $value);
            }
            $stmt->bindValue($i, $id);
            
            // Exécuter la requête
            $stmt->execute();
            
            return [
                'id' => $id
            ];
            
        case 'delete':
            $id = $data['id'];
            
            // Vérifier si la réparation existe
            $stmt = $shop_pdo->prepare("SELECT id FROM repairs WHERE id = ?");
            $stmt->bindValue(1, $id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                throw new Exception('Réparation introuvable');
            }
            
            // Supprimer la réparation
            $stmt = $shop_pdo->prepare("DELETE FROM repairs WHERE id = ?");
            $stmt->bindValue(1, $id);
            $stmt->execute();
            
            return [
                'id' => $id
            ];
            
        default:
            throw new Exception('Action non prise en charge');
    }
}

/**
 * Synchronisation des clients
 */
function syncClients($action, $data) {
    global $conn;
    
    switch ($action) {
        case 'add':
            // Supprimer les champs non nécessaires
            unset($data['id']);
            unset($data['sync_status']);
            unset($data['offline_id']);
            
            // Enregistrer l'ID hors ligne pour référence
            $offlineId = $data['offline_id'] ?? null;
            
            // Ajouter l'utilisateur et la date
            $data['user_id'] = $_SESSION['user_id'];
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Construire la requête SQL
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $stmt = $shop_pdo->prepare("INSERT INTO clients ($columns) VALUES ($placeholders)");
            
            // Lier les paramètres
            $i = 1;
            foreach ($data as $value) {
                $stmt->bindValue($i++, $value);
            }
            
            // Exécuter la requête
            $stmt->execute();
            
            // Récupérer l'ID inséré
            $id = $shop_pdo->lastInsertId();
            
            return [
                'id' => $id,
                'offline_id' => $offlineId
            ];
            
        case 'update':
            $id = $data['id'];
            
            // Supprimer les champs non nécessaires
            unset($data['id']);
            unset($data['sync_status']);
            unset($data['offline_id']);
            
            // Mettre à jour la date de modification
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Construire la requête SQL
            $updates = [];
            foreach (array_keys($data) as $column) {
                $updates[] = "$column = ?";
            }
            $updateStr = implode(', ', $updates);
            
            $stmt = $shop_pdo->prepare("UPDATE clients SET $updateStr WHERE id = ?");
            
            // Lier les paramètres
            $i = 1;
            foreach ($data as $value) {
                $stmt->bindValue($i++, $value);
            }
            $stmt->bindValue($i, $id);
            
            // Exécuter la requête
            $stmt->execute();
            
            return [
                'id' => $id
            ];
            
        case 'delete':
            $id = $data['id'];
            
            // Vérifier si le client existe
            $stmt = $shop_pdo->prepare("SELECT id FROM clients WHERE id = ?");
            $stmt->bindValue(1, $id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                throw new Exception('Client introuvable');
            }
            
            // Supprimer le client
            $stmt = $shop_pdo->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->bindValue(1, $id);
            $stmt->execute();
            
            return [
                'id' => $id
            ];
            
        default:
            throw new Exception('Action non prise en charge');
    }
}

/**
 * Synchronisation des tâches
 */
function syncTasks($action, $data) {
    global $conn;
    
    switch ($action) {
        case 'add':
            // Supprimer les champs non nécessaires
            unset($data['id']);
            unset($data['sync_status']);
            unset($data['offline_id']);
            
            // Enregistrer l'ID hors ligne pour référence
            $offlineId = $data['offline_id'] ?? null;
            
            // Ajouter l'utilisateur et la date
            $data['user_id'] = $_SESSION['user_id'];
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Construire la requête SQL
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $stmt = $shop_pdo->prepare("INSERT INTO tasks ($columns) VALUES ($placeholders)");
            
            // Lier les paramètres
            $i = 1;
            foreach ($data as $value) {
                $stmt->bindValue($i++, $value);
            }
            
            // Exécuter la requête
            $stmt->execute();
            
            // Récupérer l'ID inséré
            $id = $shop_pdo->lastInsertId();
            
            return [
                'id' => $id,
                'offline_id' => $offlineId
            ];
            
        case 'update':
            $id = $data['id'];
            
            // Supprimer les champs non nécessaires
            unset($data['id']);
            unset($data['sync_status']);
            unset($data['offline_id']);
            
            // Mettre à jour la date de modification
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Construire la requête SQL
            $updates = [];
            foreach (array_keys($data) as $column) {
                $updates[] = "$column = ?";
            }
            $updateStr = implode(', ', $updates);
            
            $stmt = $shop_pdo->prepare("UPDATE tasks SET $updateStr WHERE id = ?");
            
            // Lier les paramètres
            $i = 1;
            foreach ($data as $value) {
                $stmt->bindValue($i++, $value);
            }
            $stmt->bindValue($i, $id);
            
            // Exécuter la requête
            $stmt->execute();
            
            return [
                'id' => $id
            ];
            
        case 'delete':
            $id = $data['id'];
            
            // Vérifier si la tâche existe
            $stmt = $shop_pdo->prepare("SELECT id FROM tasks WHERE id = ?");
            $stmt->bindValue(1, $id);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                throw new Exception('Tâche introuvable');
            }
            
            // Supprimer la tâche
            $stmt = $shop_pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->bindValue(1, $id);
            $stmt->execute();
            
            return [
                'id' => $id
            ];
            
        default:
            throw new Exception('Action non prise en charge');
    }
} 