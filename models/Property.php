<?php
/**
 * Classe Property
 * Gère les opérations liées aux propriétés immobilières
 */
class Property {
    private $db;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Créer une nouvelle propriété
     * @param array $propertyData Données de la propriété
     * @return int|false ID de la propriété ou false si échec
     */
    public function create($propertyData) {
        $this->db->beginTransaction();
        
        try {
            $propertyId = $this->db->insert('properties', $propertyData);
            
            // Si la transaction échoue, une exception sera levée
            $this->db->commit();
            
            return $propertyId;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur lors de la création de la propriété: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour une propriété
     * @param int $propertyId ID de la propriété
     * @param array $propertyData Données à mettre à jour
     * @return int Nombre de lignes affectées
     */
    public function update($propertyId, $propertyData) {
        return $this->db->update('properties', $propertyData, 'id = ?', [$propertyId]);
    }
    
    /**
     * Supprimer une propriété
     * @param int $propertyId ID de la propriété
     * @return int Nombre de lignes affectées
     */
    public function delete($propertyId) {
        $this->db->beginTransaction();
        
        try {
            // Supprimer les images
            $this->db->delete('property_images', 'property_id = ?', [$propertyId]);
            
            // Supprimer les vidéos
            $this->db->delete('property_videos', 'property_id = ?', [$propertyId]);
            
            // Supprimer les liens avec les commodités
            $this->db->delete('property_amenities', 'property_id = ?', [$propertyId]);
            
            // Supprimer les disponibilités
            $this->db->delete('availability', 'property_id = ?', [$propertyId]);
            
            // Supprimer la propriété
            $result = $this->db->delete('properties', 'id = ?', [$propertyId]);
            
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur lors de la suppression de la propriété: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtenir une propriété par son ID
     * @param int $propertyId ID de la propriété
     * @return array|false Données de la propriété ou false si non trouvée
     */
    public function getById($propertyId) {
        return $this->db->fetchOne("SELECT * FROM properties WHERE id = ?", [$propertyId]);
    }
    
    /**
     * Obtenir une propriété par son ID avec détails complets
     * @param int $propertyId ID de la propriété
     * @return array|false Données de la propriété ou false si non trouvée
     */
    public function getDetailedById($propertyId) {
        $property = $this->getById($propertyId);
        
        if (!$property) {
            return false;
        }
        
        // Obtenir le propriétaire
        $userModel = new User();
        $property['owner'] = $userModel->getById($property['owner_id']);
        
        // Obtenir les images
        $property['images'] = $this->getPropertyImages($propertyId);
        
        // Obtenir les vidéos
        $property['videos'] = $this->getPropertyVideos($propertyId);
        
        // Obtenir les commodités
        $property['amenities'] = $this->getPropertyAmenities($propertyId);
        
        // Obtenir les disponibilités
        $property['availability'] = $this->getPropertyAvailability($propertyId);
        
        // Obtenir les avis
        $reviewModel = new Review();
        $property['reviews'] = $reviewModel->getPropertyReviews($propertyId);
        
        return $property;
    }
    
    /**
     * Obtenir toutes les propriétés
     * @param array $filters Filtres de recherche
     * @return array Liste des propriétés
     */
    public function getAll($filters = []) {
        $query = "SELECT p.* FROM properties p WHERE 1=1";
        $params = [];
        
        // Filtrer par statut
        if (isset($filters['status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['status'];
        } else {
            // Par défaut, afficher uniquement les propriétés disponibles
            $query .= " AND p.status = 'available'";
        }
        
        // Filtrer par propriétaire
        if (isset($filters['owner_id'])) {
            $query .= " AND p.owner_id = ?";
            $params[] = $filters['owner_id'];
        }
        
        // Filtrer par type de propriété
        if (isset($filters['property_type'])) {
            $query .= " AND p.property_type = ?";
            $params[] = $filters['property_type'];
        }
        
        // Filtrer par prix minimum
        if (isset($filters['min_price'])) {
            $query .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        // Filtrer par prix maximum
        if (isset($filters['max_price'])) {
            $query .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Filtrer par nombre minimum de pièces
        if (isset($filters['min_rooms'])) {
            $query .= " AND p.rooms >= ?";
            $params[] = $filters['min_rooms'];
        }
        
        // Filtrer par ville
        if (isset($filters['city'])) {
            $query .= " AND p.city LIKE ?";
            $params[] = '%' . $filters['city'] . '%';
        }
        
        // Filtrer par commodités
        if (isset($filters['amenities']) && is_array($filters['amenities'])) {
            $placeholders = implode(',', array_fill(0, count($filters['amenities']), '?'));
            $query .= " AND p.id IN (
                SELECT property_id FROM property_amenities 
                WHERE amenity_id IN ({$placeholders}) 
                GROUP BY property_id 
                HAVING COUNT(DISTINCT amenity_id) = ?
            )";
            
            foreach ($filters['amenities'] as $amenityId) {
                $params[] = $amenityId;
            }
            $params[] = count($filters['amenities']);
        }
        
        // Trier les résultats
        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $query .= " ORDER BY p.price ASC";
                    break;
                case 'price_desc':
                    $query .= " ORDER BY p.price DESC";
                    break;
                case 'newest':
                    $query .= " ORDER BY p.created_at DESC";
                    break;
                default:
                    $query .= " ORDER BY p.featured DESC, p.created_at DESC";
            }
        } else {
            $query .= " ORDER BY p.featured DESC, p.created_at DESC";
        }
        
        // Pagination
        if (isset($filters['limit'])) {
            $limit = (int) $filters['limit'];
            $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;
            $query .= " LIMIT {$offset}, {$limit}";
        }
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Compter le nombre de propriétés correspondant aux filtres
     * @param array $filters Filtres de recherche
     * @return int Nombre de propriétés
     */
    public function countAll($filters = []) {
        $query = "SELECT COUNT(*) as count FROM properties p WHERE 1=1";
        $params = [];
        
        // Filtrer par statut
        if (isset($filters['status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['status'];
        } else {
            // Par défaut, compter uniquement les propriétés disponibles
            $query .= " AND p.status = 'available'";
        }
        
        // Filtrer par propriétaire
        if (isset($filters['owner_id'])) {
            $query .= " AND p.owner_id = ?";
            $params[] = $filters['owner_id'];
        }
        
        // Filtrer par type de propriété
        if (isset($filters['property_type'])) {
            $query .= " AND p.property_type = ?";
            $params[] = $filters['property_type'];
        }
        
        // Filtrer par prix minimum
        if (isset($filters['min_price'])) {
            $query .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        // Filtrer par prix maximum
        if (isset($filters['max_price'])) {
            $query .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Filtrer par nombre minimum de pièces
        if (isset($filters['min_rooms'])) {
            $query .= " AND p.rooms >= ?";
            $params[] = $filters['min_rooms'];
        }
        
        // Filtrer par ville
        if (isset($filters['city'])) {
            $query .= " AND p.city LIKE ?";
            $params[] = '%' . $filters['city'] . '%';
        }
        
        // Filtrer par commodités
        if (isset($filters['amenities']) && is_array($filters['amenities'])) {
            $placeholders = implode(',', array_fill(0, count($filters['amenities']), '?'));
            $query .= " AND p.id IN (
                SELECT property_id FROM property_amenities 
                WHERE amenity_id IN ({$placeholders}) 
                GROUP BY property_id 
                HAVING COUNT(DISTINCT amenity_id) = ?
            )";
            
            foreach ($filters['amenities'] as $amenityId) {
                $params[] = $amenityId;
            }
            $params[] = count($filters['amenities']);
        }
        
        $result = $this->db->fetchOne($query, $params);
        return $result['count'];
    }
    
    /**
     * Ajouter une image à une propriété
     * @param int $propertyId ID de la propriété
     * @param array $file Données du fichier ($_FILES)
     * @param bool $isMain Est-ce l'image principale
     * @return string|false Chemin de l'image ou false si échec
     */
    public function addImage($propertyId, $file, $isMain = false) {
        // Vérifier si le fichier est une image
        $fileType = exif_imagetype($file['tmp_name']);
        if (!$fileType || !in_array($fileType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            return false;
        }
        
        // Créer le dossier si nécessaire
        $uploadDir = PROPERTY_UPLOADS . $propertyId . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'property_' . $propertyId . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Si c'est l'image principale, définir toutes les autres comme non principales
            if ($isMain) {
                $this->db->update('property_images', 
                    ['is_main' => false], 
                    'property_id = ?', 
                    [$propertyId]
                );
            }
            
            // Insérer dans la base de données
            $relativePath = $propertyId . '/' . $fileName;
            $imageId = $this->db->insert('property_images', [
                'property_id' => $propertyId,
                'image_path' => $relativePath,
                'is_main' => $isMain
            ]);
            
            return $imageId ? $relativePath : false;
        }
        
        return false;
    }
    
    /**
     * Ajouter une vidéo à une propriété
     * @param int $propertyId ID de la propriété
     * @param array $file Données du fichier ($_FILES)
     * @return string|false Chemin de la vidéo ou false si échec
     */
    public function addVideo($propertyId, $file) {
        // Vérifier le type MIME de la vidéo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $finfo->file($file['tmp_name']);
        
        if (!in_array($fileType, ['video/mp4', 'video/mpeg', 'video/quicktime'])) {
            return false;
        }
        
        // Créer le dossier si nécessaire
        $uploadDir = PROPERTY_UPLOADS . $propertyId . '/videos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'property_' . $propertyId . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Insérer dans la base de données
            $relativePath = $propertyId . '/videos/' . $fileName;
            $videoId = $this->db->insert('property_videos', [
                'property_id' => $propertyId,
                'video_path' => $relativePath
            ]);
            
            return $videoId ? $relativePath : false;
        }
        
        return false;
    }
    
    /**
     * Supprimer une image
     * @param int $imageId ID de l'image
     * @return bool Succès ou échec
     */
    public function deleteImage($imageId) {
        // Obtenir l'image
        $image = $this->db->fetchOne("SELECT * FROM property_images WHERE id = ?", [$imageId]);
        
        if (!$image) {
            return false;
        }
        
        // Supprimer le fichier
        $filePath = PROPERTY_UPLOADS . $image['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Supprimer de la base de données
        return $this->db->delete('property_images', 'id = ?', [$imageId]) > 0;
    }
    
    /**
     * Supprimer une vidéo
     * @param int $videoId ID de la vidéo
     * @return bool Succès ou échec
     */
    public function deleteVideo($videoId) {
        // Obtenir la vidéo
        $video = $this->db->fetchOne("SELECT * FROM property_videos WHERE id = ?", [$videoId]);
        
        if (!$video) {
            return false;
        }
        
        // Supprimer le fichier
        $filePath = PROPERTY_UPLOADS . $video['video_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Supprimer de la base de données
        return $this->db->delete('property_videos', 'id = ?', [$videoId]) > 0;
    }
    
    /**
     * Obtenir les images d'une propriété
     * @param int $propertyId ID de la propriété
     * @return array Liste des images
     */
    public function getPropertyImages($propertyId) {
        return $this->db->fetchAll(
            "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_main DESC, id ASC", 
            [$propertyId]
        );
    }
    
    /**
     * Obtenir les vidéos d'une propriété
     * @param int $propertyId ID de la propriété
     * @return array Liste des vidéos
     */
    public function getPropertyVideos($propertyId) {
        return $this->db->fetchAll(
            "SELECT * FROM property_videos WHERE property_id = ? ORDER BY id ASC", 
            [$propertyId]
        );
    }
    
    /**
     * Obtenir les commodités d'une propriété
     * @param int $propertyId ID de la propriété
     * @return array Liste des commodités
     */
    public function getPropertyAmenities($propertyId) {
        return $this->db->fetchAll(
            "SELECT a.* FROM amenities a 
            JOIN property_amenities pa ON a.id = pa.amenity_id 
            WHERE pa.property_id = ? 
            ORDER BY a.name ASC", 
            [$propertyId]
        );
    }
    
    /**
     * Obtenir les disponibilités d'une propriété
     * @param int $propertyId ID de la propriété
     * @return array Liste des disponibilités
     */
    public function getPropertyAvailability($propertyId) {
        return $this->db->fetchAll(
            "SELECT * FROM availability WHERE property_id = ? ORDER BY start_date ASC", 
            [$propertyId]
        );
    }
    
    /**
     * Mettre à jour les commodités d'une propriété
     * @param int $propertyId ID de la propriété
     * @param array $amenityIds IDs des commodités
     * @return bool Succès ou échec
     */
    public function updateAmenities($propertyId, $amenityIds) {
        $this->db->beginTransaction();
        
        try {
            // Supprimer les anciennes associations
            $this->db->delete('property_amenities', 'property_id = ?', [$propertyId]);
            
            // Ajouter les nouvelles associations
            foreach ($amenityIds as $amenityId) {
                $this->db->insert('property_amenities', [
                    'property_id' => $propertyId,
                    'amenity_id' => $amenityId
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur lors de la mise à jour des commodités: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour les disponibilités d'une propriété
     * @param int $propertyId ID de la propriété
     * @param array $availability Périodes de disponibilité
     * @return bool Succès ou échec
     */
    public function updateAvailability($propertyId, $availability) {
        $this->db->beginTransaction();
        
        try {
            // Supprimer les anciennes disponibilités
            $this->db->delete('availability', 'property_id = ?', [$propertyId]);
            
            // Ajouter les nouvelles disponibilités
            foreach ($availability as $period) {
                $this->db->insert('availability', [
                    'property_id' => $propertyId,
                    'start_date' => $period['start_date'],
                    'end_date' => $period['end_date'],
                    'status' => $period['status'] ?? 'available'
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur lors de la mise à jour des disponibilités: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enregistrer une vue de propriété
     * @param int $propertyId ID de la propriété
     * @param int $userId ID de l'utilisateur (optionnel)
     * @param string $ipAddress Adresse IP
     * @return bool Succès ou échec
     */
    public function recordView($propertyId, $userId = null, $ipAddress) {
        return $this->db->insert('property_views', [
            'property_id' => $propertyId,
            'user_id' => $userId,
            'ip_address' => $ipAddress
        ]) > 0;
    }
    
    /**
     * Compter les vues d'une propriété
     * @param int $propertyId ID de la propriété
     * @return int Nombre de vues
     */
    public function countViews($propertyId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM property_views WHERE property_id = ?", 
            [$propertyId]
        );
        return $result['count'];
    }
    
    /**
     * Obtenir toutes les commodités disponibles
     * @return array Liste des commodités
     */
    public function getAllAmenities() {
        return $this->db->fetchAll("SELECT * FROM amenities ORDER BY name ASC");
    }
    
    /**
     * Vérifier si une propriété est disponible pour une période donnée
     * @param int $propertyId ID de la propriété
     * @param string $checkIn Date d'arrivée (Y-m-d)
     * @param string $checkOut Date de départ (Y-m-d)
     * @return bool Disponibilité
     */
    public function isAvailable($propertyId, $checkIn, $checkOut) {
        // Vérifier les périodes de disponibilité
        $availability = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM availability 
            WHERE property_id = ? 
            AND start_date <= ? 
            AND end_date >= ? 
            AND status = 'available'", 
            [$propertyId, $checkIn, $checkOut]
        );
        
        if ($availability['count'] == 0) {
            return false;
        }
        
        // Vérifier s'il n'y a pas de réservation qui chevauche
        $bookings = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM bookings 
            WHERE property_id = ? 
            AND status IN ('pending', 'confirmed') 
            AND ((check_in <= ? AND check_out >= ?) 
            OR (check_in >= ? AND check_in < ?) 
            OR (check_out > ? AND check_out <= ?))", 
            [$propertyId, $checkOut, $checkIn, $checkIn, $checkOut, $checkIn, $checkOut]
        );
        
        return $bookings['count'] == 0;
    }
    
    /**
     * Chercher des propriétés correspondant à une alerte
     * @param array $alert Critères de l'alerte
     * @return array Liste des propriétés
     */
    public function findForAlert($alert) {
        $filters = [];
        
        if (isset($alert['property_type'])) {
            $filters['property_type'] = $alert['property_type'];
        }
        
        if (isset($alert['min_price'])) {
            $filters['min_price'] = $alert['min_price'];
        }
        
        if (isset($alert['max_price'])) {
            $filters['max_price'] = $alert['max_price'];
        }
        
        if (isset($alert['min_rooms'])) {
            $filters['min_rooms'] = $alert['min_rooms'];
        }
        
        if (isset($alert['city'])) {
            $filters['city'] = $alert['city'];
        }
        
        // Ajouter uniquement les propriétés créées après la dernière notification
        if (isset($alert['last_notified'])) {
            $query = "SELECT p.* FROM properties p 
                    WHERE p.status = 'available' 
                    AND p.created_at > ?";
            
            $params = [$alert['last_notified']];
            
            if (isset($filters['property_type'])) {
                $query .= " AND p.property_type = ?";
                $params[] = $filters['property_type'];
            }
            
            if (isset($filters['min_price'])) {
                $query .= " AND p.price >= ?";
                $params[] = $filters['min_price'];
            }
            
            if (isset($filters['max_price'])) {
                $query .= " AND p.price <= ?";
                $params[] = $filters['max_price'];
            }
            
            if (isset($filters['min_rooms'])) {
                $query .= " AND p.rooms >= ?";
                $params[] = $filters['min_rooms'];
            }
            
            if (isset($filters['city'])) {
                $query .= " AND p.city LIKE ?";
                $params[] = '%' . $filters['city'] . '%';
            }
            
            return $this->db->fetchAll($query, $params);
        }
        
        // Par défaut, utiliser la fonction getAll standard
        return $this->getAll($filters);
    }
}