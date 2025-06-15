<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Classe de gestion de base de données
 */
class Database {
    private $conn;
    
    /**
     * Constructeur qui établit la connexion à la base de données
     */
    public function __construct() {
        try {
            $this->conn = getShopDBConnection();
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    
    /**
     * Exécute une requête SQL avec des paramètres
     * 
     * @param string $sql La requête SQL à exécuter
     * @param array $params Les paramètres à lier à la requête
     * @return PDOStatement|false L'objet PDOStatement ou false en cas d'erreur
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère une ligne de résultat de la requête
     * 
     * @param string $sql La requête SQL
     * @param array $params Les paramètres de la requête
     * @return array|false Un tableau associatif contenant la ligne ou false
     */
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Récupère toutes les lignes de résultat de la requête
     * 
     * @param string $sql La requête SQL
     * @param array $params Les paramètres de la requête
     * @return array Un tableau de résultats
     */
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Insère des données dans une table
     * 
     * @param string $table Le nom de la table
     * @param array $data Les données à insérer (clé => valeur)
     * @return int|false L'ID de la dernière insertion ou false en cas d'erreur
     */
    public function insert($table, $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erreur d'insertion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour des données dans une table
     * 
     * @param string $table Le nom de la table
     * @param array $data Les données à mettre à jour (clé => valeur)
     * @param string $where La condition WHERE
     * @param array $whereParams Les paramètres pour la condition WHERE
     * @return bool Succès ou échec de la mise à jour
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $setClauses = [];
            foreach (array_keys($data) as $key) {
                $setClauses[] = "$key = :$key";
            }
            $setClause = implode(', ', $setClauses);
            
            $sql = "UPDATE $table SET $setClause WHERE $where";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            foreach ($whereParams as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur de mise à jour: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime des données d'une table
     * 
     * @param string $table Le nom de la table
     * @param string $where La condition WHERE
     * @param array $params Les paramètres pour la condition WHERE
     * @return bool Succès ou échec de la suppression
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM $table WHERE $where";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erreur de suppression: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sauvegarde un SMS envoyé dans la base de données
     * 
     * @param int $clientId ID du client
     * @param int $repairId ID de la réparation (optionnel)
     * @param string $phoneNumber Numéro de téléphone
     * @param string $message Contenu du SMS
     * @return int|false ID du SMS enregistré ou false
     */
    public function logSms($clientId, $repairId, $phoneNumber, $message) {
        $data = [
            'client_id' => $clientId,
            'repair_id' => $repairId ?: null,
            'phone_number' => $phoneNumber,
            'message' => $message,
            'sent_date' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert('sms_logs', $data);
    }
    
    /**
     * Vérifie si un client existe
     * 
     * @param int $clientId ID du client
     * @return bool Vrai si le client existe
     */
    public function clientExists($clientId) {
        $sql = "SELECT COUNT(*) as count FROM clients WHERE id = :id";
        $result = $this->getRow($sql, ['id' => $clientId]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Vérifie si une réparation existe
     * 
     * @param int $repairId ID de la réparation
     * @return bool Vrai si la réparation existe
     */
    public function repairExists($repairId) {
        $sql = "SELECT COUNT(*) as count FROM repairs WHERE id = :id";
        $result = $this->getRow($sql, ['id' => $repairId]);
        return $result && $result['count'] > 0;
    }
}
?> 