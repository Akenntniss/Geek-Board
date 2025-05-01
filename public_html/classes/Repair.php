<?php
/**
 * Classe de gestion des réparations
 */
class Repair {
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
     * Récupère toutes les réparations
     * 
     * @param string|null $status Filtre par statut (optionnel)
     * @return array Liste des réparations
     */
    public function getAllRepairs($status = null) {
        $params = [];
        $sql = "SELECT r.*, 
                v.registration, v.make, v.model,
                CONCAT(c.firstname, ' ', c.lastname) as client_name
                FROM repairs r
                JOIN vehicles v ON r.vehicle_id = v.id
                JOIN clients c ON v.client_id = c.id";
        
        if ($status !== null) {
            $sql .= " WHERE r.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        return $this->db->getRows($sql, $params);
    }
    
    /**
     * Récupère une réparation par son ID
     * 
     * @param int $id ID de la réparation
     * @return array|false Données de la réparation ou false
     */
    public function getRepairById($id) {
        $sql = "SELECT r.*, 
                v.registration, v.make, v.model, v.year, v.vin,
                c.id as client_id, c.firstname, c.lastname, c.email, c.phone
                FROM repairs r
                JOIN vehicles v ON r.vehicle_id = v.id
                JOIN clients c ON v.client_id = c.id
                WHERE r.id = :id";
        return $this->db->getRow($sql, ['id' => $id]);
    }
    
    /**
     * Recherche des réparations
     * 
     * @param string $search Terme de recherche
     * @return array Liste des réparations correspondantes
     */
    public function searchRepairs($search) {
        $searchTerm = "%$search%";
        $sql = "SELECT r.*, 
                v.registration, v.make, v.model,
                CONCAT(c.firstname, ' ', c.lastname) as client_name
                FROM repairs r
                JOIN vehicles v ON r.vehicle_id = v.id
                JOIN clients c ON v.client_id = c.id
                WHERE r.description LIKE :search 
                OR v.registration LIKE :search 
                OR CONCAT(c.firstname, ' ', c.lastname) LIKE :search
                OR r.invoice_number LIKE :search
                ORDER BY r.created_at DESC";
        
        return $this->db->getRows($sql, ['search' => $searchTerm]);
    }
    
    /**
     * Ajoute une nouvelle réparation
     * 
     * @param array $data Données de la réparation
     * @return int|false ID de la réparation ajoutée ou false
     */
    public function addRepair($data) {
        // Vérifier que le véhicule existe
        $sql = "SELECT COUNT(*) as count FROM vehicles WHERE id = :vehicle_id";
        $result = $this->db->getRow($sql, ['vehicle_id' => $data['vehicle_id']]);
        
        if (!$result || $result['count'] == 0) {
            return false;
        }
        
        // Définir la date de création
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Définir le statut par défaut
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }
        
        return $this->db->insert('repairs', $data);
    }
    
    /**
     * Met à jour une réparation
     * 
     * @param int $id ID de la réparation
     * @param array $data Données de la réparation
     * @return bool Succès ou échec
     */
    public function updateRepair($id, $data) {
        // Vérifier que le véhicule existe si vehicle_id est fourni
        if (isset($data['vehicle_id'])) {
            $sql = "SELECT COUNT(*) as count FROM vehicles WHERE id = :vehicle_id";
            $result = $this->db->getRow($sql, ['vehicle_id' => $data['vehicle_id']]);
            
            if (!$result || $result['count'] == 0) {
                return false;
            }
        }
        
        // Définir la date de mise à jour
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('repairs', $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Supprime une réparation
     * 
     * @param int $id ID de la réparation
     * @return bool Succès ou échec
     */
    public function deleteRepair($id) {
        // Supprimer les messages associés à cette réparation
        $this->db->delete('messages', 'repair_id = :repair_id', ['repair_id' => $id]);
        
        // Supprimer la réparation
        return $this->db->delete('repairs', 'id = :id', ['id' => $id]);
    }
    
    /**
     * Met à jour le statut d'une réparation
     * 
     * @param int $id ID de la réparation
     * @param string $status Nouveau statut
     * @return bool Succès ou échec
     */
    public function updateStatus($id, $status) {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Si le statut est "completed", définir la date de fin
        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('repairs', $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Ajoute un coût à une réparation
     * 
     * @param int $repairId ID de la réparation
     * @param string $description Description du coût
     * @param float $amount Montant
     * @return int|false ID du coût ajouté ou false
     */
    public function addCost($repairId, $description, $amount) {
        $data = [
            'repair_id' => $repairId,
            'description' => $description,
            'amount' => $amount,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('repair_costs', $data);
    }
    
    /**
     * Récupère les coûts d'une réparation
     * 
     * @param int $repairId ID de la réparation
     * @return array Liste des coûts
     */
    public function getRepairCosts($repairId) {
        $sql = "SELECT * FROM repair_costs WHERE repair_id = :repair_id ORDER BY created_at";
        return $this->db->getRows($sql, ['repair_id' => $repairId]);
    }
    
    /**
     * Calcule le coût total d'une réparation
     * 
     * @param int $repairId ID de la réparation
     * @return float Coût total
     */
    public function calculateTotalCost($repairId) {
        $sql = "SELECT SUM(amount) as total FROM repair_costs WHERE repair_id = :repair_id";
        $result = $this->db->getRow($sql, ['repair_id' => $repairId]);
        
        return ($result ? floatval($result['total']) : 0);
    }
    
    /**
     * Génère un numéro de facture unique
     * 
     * @return string Numéro de facture
     */
    public function generateInvoiceNumber() {
        $year = date('Y');
        $month = date('m');
        
        // Trouver le dernier numéro de facture pour ce mois
        $sql = "SELECT invoice_number FROM repairs 
                WHERE invoice_number LIKE :prefix 
                ORDER BY invoice_number DESC LIMIT 1";
        
        $prefix = "INV-$year$month-";
        $result = $this->db->getRow($sql, ['prefix' => "$prefix%"]);
        
        if ($result && $result['invoice_number']) {
            // Extraire le dernier numéro et incrémenter
            $parts = explode('-', $result['invoice_number']);
            $number = intval(end($parts)) + 1;
        } else {
            // Premier numéro de facture pour ce mois
            $number = 1;
        }
        
        // Formater avec des zéros en tête (ex: INV-202305-0001)
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Finalise une réparation en générant une facture
     * 
     * @param int $repairId ID de la réparation
     * @return bool Succès ou échec
     */
    public function finalizeRepair($repairId) {
        // Calculer le coût total
        $totalCost = $this->calculateTotalCost($repairId);
        
        // Générer un numéro de facture
        $invoiceNumber = $this->generateInvoiceNumber();
        
        // Mettre à jour la réparation
        $data = [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'total_cost' => $totalCost,
            'invoice_number' => $invoiceNumber,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('repairs', $data, 'id = :id', ['id' => $repairId]);
    }
    
    /**
     * Récupère les statistiques des réparations
     * 
     * @param string $period Période ('day', 'week', 'month', 'year')
     * @return array Statistiques
     */
    public function getRepairStats($period = 'month') {
        // Définir la date de début en fonction de la période
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d 00:00:00');
                $format = '%H:00';
                break;
            case 'week':
                $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
                $format = '%Y-%m-%d';
                break;
            case 'year':
                $startDate = date('Y-01-01 00:00:00');
                $format = '%Y-%m';
                break;
            case 'month':
            default:
                $startDate = date('Y-m-01 00:00:00');
                $format = '%Y-%m-%d';
                break;
        }
        
        // Récupérer le nombre de réparations par jour/semaine/mois
        $sql = "SELECT DATE_FORMAT(created_at, '$format') as period, 
                COUNT(*) as count, 
                SUM(total_cost) as revenue
                FROM repairs 
                WHERE created_at >= :start_date
                GROUP BY period 
                ORDER BY period";
        
        $repairsByPeriod = $this->db->getRows($sql, ['start_date' => $startDate]);
        
        // Récupérer le nombre de réparations par statut
        $sql = "SELECT status, COUNT(*) as count 
                FROM repairs 
                WHERE created_at >= :start_date
                GROUP BY status";
        
        $repairsByStatus = $this->db->getRows($sql, ['start_date' => $startDate]);
        
        // Calculer le revenu total
        $sql = "SELECT SUM(total_cost) as total_revenue 
                FROM repairs 
                WHERE created_at >= :start_date 
                AND status = 'completed'";
        
        $revenueResult = $this->db->getRow($sql, ['start_date' => $startDate]);
        $totalRevenue = ($revenueResult ? floatval($revenueResult['total_revenue']) : 0);
        
        return [
            'repairs_by_period' => $repairsByPeriod,
            'repairs_by_status' => $repairsByStatus,
            'total_revenue' => $totalRevenue
        ];
    }
}
?> 