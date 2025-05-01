<?php
/**
 * Classe de gestion des clients
 */
class Client {
    private $db;
    
    /**
     * Constructeur
     * 
     * @param Database $db Instance de la classe Database
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    /**
     * Récupère tous les clients
     * 
     * @return array Liste des clients
     */
    public function getAllClients() {
        $sql = "SELECT * FROM clients ORDER BY lastname, firstname";
        return $this->db->getRows($sql);
    }
    
    /**
     * Récupère un client par son ID
     * 
     * @param int $id ID du client
     * @return array|false Données du client ou false
     */
    public function getClientById($id) {
        $sql = "SELECT * FROM clients WHERE id = :id";
        return $this->db->getRow($sql, ['id' => $id]);
    }
    
    /**
     * Recherche des clients
     * 
     * @param string $search Terme de recherche
     * @return array Liste des clients correspondants
     */
    public function searchClients($search) {
        $searchTerm = "%$search%";
        $sql = "SELECT * FROM clients 
                WHERE firstname LIKE :search 
                OR lastname LIKE :search 
                OR email LIKE :search 
                OR phone LIKE :search 
                OR CONCAT(firstname, ' ', lastname) LIKE :search
                ORDER BY lastname, firstname";
        
        return $this->db->getRows($sql, ['search' => $searchTerm]);
    }
    
    /**
     * Ajoute un nouveau client
     * 
     * @param array $data Données du client
     * @return int|false ID du client ajouté ou false
     */
    public function addClient($data) {
        // Vérifier si l'email existe déjà
        if (isset($data['email']) && !empty($data['email'])) {
            if ($this->emailExists($data['email'])) {
                return false;
            }
        }
        
        // Définir la date de création
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert('clients', $data);
    }
    
    /**
     * Met à jour un client
     * 
     * @param int $id ID du client
     * @param array $data Données du client
     * @return bool Succès ou échec
     */
    public function updateClient($id, $data) {
        // Vérifier si l'email existe déjà (pour un autre client)
        if (isset($data['email']) && !empty($data['email'])) {
            if ($this->emailExists($data['email'], $id)) {
                return false;
            }
        }
        
        // Définir la date de mise à jour
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('clients', $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Supprime un client
     * 
     * @param int $id ID du client
     * @return bool Succès ou échec
     */
    public function deleteClient($id) {
        // Vérifier si le client a des véhicules
        $sql = "SELECT COUNT(*) as count FROM vehicles WHERE client_id = :client_id";
        $result = $this->db->getRow($sql, ['client_id' => $id]);
        
        if ($result && $result['count'] > 0) {
            return false; // Le client a des véhicules, ne pas supprimer
        }
        
        return $this->db->delete('clients', 'id = :id', ['id' => $id]);
    }
    
    /**
     * Vérifie si un email existe déjà
     * 
     * @param string $email Email à vérifier
     * @param int|null $excludeId ID du client à exclure (pour mise à jour)
     * @return bool True si l'email existe, false sinon
     */
    public function emailExists($email, $excludeId = null) {
        $params = ['email' => $email];
        $sql = "SELECT COUNT(*) as count FROM clients WHERE email = :email";
        
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        $result = $this->db->getRow($sql, $params);
        return ($result && $result['count'] > 0);
    }
    
    /**
     * Récupère les statistiques clients
     * 
     * @return array Statistiques
     */
    public function getClientStats() {
        // Nombre total de clients
        $sql = "SELECT COUNT(*) as total FROM clients";
        $totalResult = $this->db->getRow($sql);
        $total = ($totalResult ? intval($totalResult['total']) : 0);
        
        // Nouveaux clients ce mois-ci
        $sql = "SELECT COUNT(*) as count FROM clients 
                WHERE created_at >= :start_date";
        $startDate = date('Y-m-01 00:00:00'); // Premier jour du mois
        $newResult = $this->db->getRow($sql, ['start_date' => $startDate]);
        $newClients = ($newResult ? intval($newResult['count']) : 0);
        
        // Clients avec le plus de véhicules
        $sql = "SELECT c.id, CONCAT(c.firstname, ' ', c.lastname) as name, 
                COUNT(v.id) as vehicle_count
                FROM clients c
                JOIN vehicles v ON c.id = v.client_id
                GROUP BY c.id
                ORDER BY vehicle_count DESC
                LIMIT 5";
        $topClients = $this->db->getRows($sql);
        
        // Clients avec le plus de réparations
        $sql = "SELECT c.id, CONCAT(c.firstname, ' ', c.lastname) as name, 
                COUNT(r.id) as repair_count
                FROM clients c
                JOIN vehicles v ON c.id = v.client_id
                JOIN repairs r ON v.id = r.vehicle_id
                GROUP BY c.id
                ORDER BY repair_count DESC
                LIMIT 5";
        $topRepairClients = $this->db->getRows($sql);
        
        return [
            'total_clients' => $total,
            'new_clients' => $newClients,
            'top_clients_by_vehicles' => $topClients,
            'top_clients_by_repairs' => $topRepairClients
        ];
    }
    
    /**
     * Récupère l'historique complet d'un client (véhicules et réparations)
     * 
     * @param int $id ID du client
     * @return array Historique client
     */
    public function getClientHistory($id) {
        // Récupérer les données du client
        $client = $this->getClientById($id);
        if (!$client) {
            return false;
        }
        
        // Récupérer les véhicules du client
        $sql = "SELECT * FROM vehicles WHERE client_id = :client_id ORDER BY registration";
        $vehicles = $this->db->getRows($sql, ['client_id' => $id]);
        
        // Récupérer les réparations pour chaque véhicule
        $vehicleHistory = [];
        foreach ($vehicles as $vehicle) {
            $sql = "SELECT r.*, 
                    v.registration, v.make, v.model
                    FROM repairs r
                    JOIN vehicles v ON r.vehicle_id = v.id
                    WHERE v.id = :vehicle_id
                    ORDER BY r.created_at DESC";
            
            $repairs = $this->db->getRows($sql, ['vehicle_id' => $vehicle['id']]);
            
            $vehicleHistory[] = [
                'vehicle' => $vehicle,
                'repairs' => $repairs
            ];
        }
        
        // Calcul des statistiques client
        $sql = "SELECT COUNT(*) as repair_count, SUM(r.total_cost) as total_spent
                FROM repairs r
                JOIN vehicles v ON r.vehicle_id = v.id
                WHERE v.client_id = :client_id
                AND r.status = 'completed'";
        
        $stats = $this->db->getRow($sql, ['client_id' => $id]);
        
        return [
            'client' => $client,
            'vehicle_history' => $vehicleHistory,
            'stats' => [
                'repair_count' => ($stats ? intval($stats['repair_count']) : 0),
                'total_spent' => ($stats ? floatval($stats['total_spent']) : 0)
            ]
        ];
    }
}
?> 