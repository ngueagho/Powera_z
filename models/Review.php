<?php
/**
 * Classe Review
 * Gère les opérations liées aux avis
 */
class Review {
    private $db;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Créer un nouvel avis
     * @param array $reviewData Données de l'avis
     * @return int|false ID de l'avis ou false si échec
     */
    public function create($reviewData) {
        // Par défaut, les avis ne sont pas approuvés automatiquement
        if (!isset($reviewData['is_approved'])) {
            $reviewData['is_approved'] = false;
        }
        
        return $this->db->insert('reviews', $reviewData);
    }
    
    /**
     * Mettre à jour un avis
     * @param int $reviewId ID de l'avis
     * @param array $reviewData Données à mettre à jour
     * @return int Nombre de lignes affectées
     */
    public function update($reviewId, $reviewData) {
        return $this->db->update('reviews', $reviewData, 'id = ?', [$reviewId]);
    }
    
    /**
     * Supprimer un avis
     * @param int $reviewId ID de l'avis
     * @return int Nombre de lignes affectées
     */
    public function delete($reviewId) {
        return $this->db->delete('reviews', 'id = ?', [$reviewId]);
    }
    
    /**
     * Obtenir un avis par son ID
     * @param int $reviewId ID de l'avis
     * @return array|false Données de l'avis ou false si non trouvé
     */
    public function getById($reviewId) {
        return $this->db->fetchOne("SELECT * FROM reviews WHERE id = ?", [$reviewId]);
    }
    
    /**
     * Récupérer les avis d'une propriété
     * @param int $propertyId ID de la propriété
     * @param bool $onlyApproved Ne récupérer que les avis approuvés
     * @return array Liste des avis
     */
    public function getPropertyReviews($propertyId, $onlyApproved = true) {
        $query = "
            SELECT r.*, u.first_name, u.last_name, u.profile_pic
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.property_id = ?
        ";
        
        if ($onlyApproved) {
            $query .= " AND r.is_approved = 1";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        return $this->db->fetchAll($query, [$propertyId]);
    }
    
    /**
     * Récupérer les avis laissés par un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array Liste des avis
     */
    public function getUserReviews($userId) {
        $query = "
            SELECT r.*, p.title as property_title, p.id as property_id, p.address, p.city
            FROM reviews r
            JOIN properties p ON r.property_id = p.id
            WHERE r.reviewer_id = ?
            ORDER BY r.created_at DESC
        ";
        
        return $this->db->fetchAll($query, [$userId]);
    }
    
    /**
     * Récupérer les avis concernant les propriétés d'un utilisateur
     * @param int $userId ID de l'utilisateur (propriétaire)
     * @param bool $onlyApproved Ne récupérer que les avis approuvés
     * @return array Liste des avis
     */
    public function getOwnerPropertyReviews($userId, $onlyApproved = false) {
        $query = "
            SELECT r.*, p.title as property_title, p.id as property_id, p.address, p.city,
                   u.first_name, u.last_name, u.profile_pic
            FROM reviews r
            JOIN properties p ON r.property_id = p.id
            JOIN users u ON r.reviewer_id = u.id
            WHERE p.owner_id = ?
        ";
        
        if ($onlyApproved) {
            $query .= " AND r.is_approved = 1";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        return $this->db->fetchAll($query, [$userId]);
    }
    
    /**
     * Approuver un avis
     * @param int $reviewId ID de l'avis
     * @return bool Succès ou échec
     */
    public function approve($reviewId) {
        return $this->db->update('reviews', ['is_approved' => true], 'id = ?', [$reviewId]) > 0;
    }
    
    /**
     * Rejeter un avis
     * @param int $reviewId ID de l'avis
     * @return bool Succès ou échec
     */
    public function reject($reviewId) {
        return $this->delete($reviewId);
    }
    
    /**
     * Vérifier si un utilisateur a déjà laissé un avis pour une propriété
     * @param int $userId ID de l'utilisateur
     * @param int $propertyId ID de la propriété
     * @return bool Vrai si l'utilisateur a déjà laissé un avis
     */
    public function hasUserReviewed($userId, $propertyId) {
        $query = "
            SELECT COUNT(*) as count
            FROM reviews
            WHERE reviewer_id = ? AND property_id = ?
        ";
        
        $result = $this->db->fetchOne($query, [$userId, $propertyId]);
        return $result['count'] > 0;
    }
    
    /**
     * Récupérer tous les avis en attente d'approbation
     * @return array Liste des avis en attente
     */
    public function getPendingReviews() {
        $query = "
            SELECT r.*, p.title as property_title, p.id as property_id, p.address, p.city,
                   u.first_name, u.last_name, u.profile_pic
            FROM reviews r
            JOIN properties p ON r.property_id = p.id
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.is_approved = 0
            ORDER BY r.created_at ASC
        ";
        
        return $this->db->fetchAll($query);
    }
    
    /**
     * Calculer la note moyenne d'une propriété
     * @param int $propertyId ID de la propriété
     * @return float Note moyenne
     */
    public function getAverageRating($propertyId) {
        $query = "
            SELECT AVG(rating) as average
            FROM reviews
            WHERE property_id = ? AND is_approved = 1
        ";
        
        $result = $this->db->fetchOne($query, [$propertyId]);
        return round($result['average'] ?? 0, 1);
    }
    
    /**
     * Compter le nombre d'avis d'une propriété
     * @param int $propertyId ID de la propriété
     * @param bool $onlyApproved Ne compter que les avis approuvés
     * @return int Nombre d'avis
     */
    public function countPropertyReviews($propertyId, $onlyApproved = true) {
        $query = "
            SELECT COUNT(*) as count
            FROM reviews
            WHERE property_id = ?
        ";
        
        if ($onlyApproved) {
            $query .= " AND is_approved = 1";
        }
        
        $result = $this->db->fetchOne($query, [$propertyId]);
        return $result['count'];
    }
    
    /**
     * Récupérer la répartition des notes d'une propriété
     * @param int $propertyId ID de la propriété
     * @return array Répartition des notes (clé = note, valeur = nombre)
     */
    public function getRatingDistribution($propertyId) {
        $query = "
            SELECT rating, COUNT(*) as count
            FROM reviews
            WHERE property_id = ? AND is_approved = 1
            GROUP BY rating
            ORDER BY rating DESC
        ";
        
        $results = $this->db->fetchAll($query, [$propertyId]);
        
        // Initialiser la répartition pour toutes les notes (1 à 5)
        $distribution = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ];
        
        // Remplir avec les résultats réels
        foreach ($results as $result) {
            $distribution[$result['rating']] = $result['count'];
        }
        
        return $distribution;
    }
}