<?php
/**
 * Classe de gestion des véhicules
 */
class Vehicle {
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
     * Récupère tous les véhicules
     * 
     * @return array Liste des véhicules
     */
    public function getAllVehicles() {
        $sql = "SELECT v.*, CONCAT(c.firstname, ' ', c.lastname) as client_name 
                FROM vehicles v
                JOIN clients c ON v.client_id = c.id
                ORDER BY v.registration";
        return $this->db->getRows($sql);
    }
    
    /**
     * Récupère un véhicule par son ID
     * 
     * @param int $id ID du véhicule
     * @return array|false Données du véhicule ou false
     */
    public function getVehicleById($id) {
        $sql = "SELECT v.*, CONCAT(c.firstname, ' ', c.lastname) as client_name 
                FROM vehicles v
                JOIN clients c ON v.client_id = c.id
                WHERE v.id = :id";
        return $this->db->getRow($sql, ['id' => $id]);
    }
    
    /**
     * Recherche des véhicules
     * 
     * @param string $search Terme de recherche
     * @return array Liste des véhicules correspondants
     */
    public function searchVehicles($search) {
        $searchTerm = "%$search%";
        $sql = "SELECT v.*, CONCAT(c.firstname, ' ', c.lastname) as client_name 
                FROM vehicles v
                JOIN clients c ON v.client_id = c.id
                WHERE v.make LIKE :search 
                OR v.model LIKE :search 
                OR v.registration LIKE :search 
                OR v.vin LIKE :search
                OR CONCAT(c.firstname, ' ', c.lastname) LIKE :search
                ORDER BY v.registration";
        
        return $this->db->getRows($sql, ['search' => $searchTerm]);
    }
    
    /**
     * Ajoute un nouveau véhicule
     * 
     * @param array $data Données du véhicule
     * @return int|false ID du véhicule ajouté ou false
     */
    public function addVehicle($data) {
        // Vérifier que le client existe
        if (!$this->db->clientExists($data['client_id'])) {
            return false;
        }
        
        return $this->db->insert('vehicles', $data);
    }
    
    /**
     * Met à jour un véhicule
     * 
     * @param int $id ID du véhicule
     * @param array $data Données du véhicule
     * @return bool Succès ou échec
     */
    public function updateVehicle($id, $data) {
        // Vérifier que le client existe si client_id est fourni
        if (isset($data['client_id']) && !$this->db->clientExists($data['client_id'])) {
            return false;
        }
        
        return $this->db->update('vehicles', $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Supprime un véhicule
     * 
     * @param int $id ID du véhicule
     * @return bool Succès ou échec
     */
    public function deleteVehicle($id) {
        // Vérifier si des réparations sont associées à ce véhicule
        $sql = "SELECT COUNT(*) as count FROM repairs WHERE vehicle_id = :id";
        $result = $this->db->getRow($sql, ['id' => $id]);
        
        if ($result && $result['count'] > 0) {
            // Ne pas supprimer un véhicule avec des réparations
            return false;
        }
        
        return $this->db->delete('vehicles', 'id = :id', ['id' => $id]);
    }
    
    /**
     * Récupère l'historique des réparations d'un véhicule
     * 
     * @param int $id ID du véhicule
     * @return array Liste des réparations
     */
    public function getVehicleRepairs($id) {
        $sql = "SELECT * FROM repairs WHERE vehicle_id = :vehicle_id ORDER BY created_at DESC";
        return $this->db->getRows($sql, ['vehicle_id' => $id]);
    }
    
    /**
     * Récupère les véhicules d'un client spécifique
     * 
     * @param int $clientId ID du client
     * @return array Liste des véhicules
     */
    public function getVehiclesByClientId($clientId) {
        $sql = "SELECT * FROM vehicles WHERE client_id = :client_id ORDER BY registration";
        return $this->db->getRows($sql, ['client_id' => $clientId]);
    }
    
    /**
     * Vérifie si un numéro d'immatriculation existe déjà (hors véhicule spécifié)
     * 
     * @param string $registration Numéro d'immatriculation
     * @param int|null $excludeId ID du véhicule à exclure (pour les mises à jour)
     * @return bool True si existe, False sinon
     */
    public function registrationExists($registration, $excludeId = null) {
        $params = ['registration' => $registration];
        $sql = "SELECT COUNT(*) as count FROM vehicles WHERE registration = :registration";
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $result = $this->db->getRow($sql, $params);
        return ($result && $result['count'] > 0);
    }
    
    /**
     * Vérifie si un VIN existe déjà (hors véhicule spécifié)
     * 
     * @param string $vin Numéro VIN
     * @param int|null $excludeId ID du véhicule à exclure (pour les mises à jour)
     * @return bool True si existe, False sinon
     */
    public function vinExists($vin, $excludeId = null) {
        if (empty($vin)) {
            return false;
        }
        
        $params = ['vin' => $vin];
        $sql = "SELECT COUNT(*) as count FROM vehicles WHERE vin = :vin";
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $result = $this->db->getRow($sql, $params);
        return ($result && $result['count'] > 0);
    }
}
?> 