<?php
/**
 * Classe Booking
 * Gère les opérations liées aux réservations
 */
class Booking {
    private $db;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Créer une nouvelle réservation
     * @param array $bookingData Données de la réservation
     * @return int|false ID de la réservation ou false si échec
     */
    public function create($bookingData) {
        // Vérifier la disponibilité du logement
        $propertyModel = new Property();
        $isAvailable = $propertyModel->isAvailable(
            $bookingData['property_id'],
            $bookingData['check_in'],
            $bookingData['check_out']
        );
        
        if (!$isAvailable) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            $bookingId = $this->db->insert('bookings', $bookingData);
            
            // Ajouter une notification pour le propriétaire
            $property = $propertyModel->getById($bookingData['property_id']);
            $this->addNotification(
                $property['owner_id'],
                'Nouvelle réservation',
                'Une nouvelle réservation a été effectuée pour votre propriété.',
                $bookingId
            );
            
            $this->db->commit();
            return $bookingId;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur lors de la création de la réservation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour une réservation
     * @param int $bookingId ID de la réservation
     * @param array $bookingData Données à mettre à jour
     * @return int Nombre de lignes affectées
     */
    public function update($bookingId, $bookingData) {
        return $this->db->update('bookings', $bookingData, 'id = ?', [$bookingId]);
    }
    
    /**
     * Supprimer une réservation
     * @param int $bookingId ID de la réservation
     * @return int Nombre de lignes affectées
     */
    public function delete($bookingId) {
        return $this->db->delete('bookings', 'id = ?', [$bookingId]);
    }
    
    /**
     * Obtenir une réservation par son ID
     * @param int $bookingId ID de la réservation
     * @return array|false Données de la réservation ou false si non trouvée
     */
    public function getById($bookingId) {
        return $this->db->fetchOne("SELECT * FROM bookings WHERE id = ?", [$bookingId]);
    }
    
    /**
     * Obtenir une réservation par son ID avec détails complets
     * @param int $bookingId ID de la réservation
     * @return array|false Données de la réservation ou false si non trouvée
     */
    public function getDetailedById($bookingId) {
        $booking = $this->getById($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        // Obtenir la propriété
        $propertyModel = new Property();
        $booking['property'] = $propertyModel->getById($booking['property_id']);
        
        // Obtenir le locataire
        $userModel = new User();
        $booking['tenant'] = $userModel->getById($booking['tenant_id']);
        $booking['owner'] = $userModel->getById($booking['property']['owner_id']);
        
        // Obtenir le contrat
        $booking['contract'] = $this->getContract($bookingId);
        
        // Obtenir le paiement
        $paymentModel = new Payment();
        $booking['payment'] = $paymentModel->getByBookingId($bookingId);
        
        return $booking;
    }
    
    /**
     * Obtenir les réservations d'un utilisateur (en tant que locataire)
     * @param int $userId ID de l'utilisateur
     * @param string $status Filtrer par statut (optionnel)
     * @return array Liste des réservations
     */
    public function getByTenantId($userId, $status = null) {
        $query = "SELECT b.*, p.title, p.address, p.city 
                FROM bookings b 
                JOIN properties p ON b.property_id = p.id 
                WHERE b.tenant_id = ?";
        $params = [$userId];
        
        if ($status) {
            $query .= " AND b.status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY b.check_in DESC";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Obtenir les réservations d'un utilisateur (en tant que propriétaire)
     * @param int $userId ID de l'utilisateur
     * @param string $status Filtrer par statut (optionnel)
     * @return array Liste des réservations
     */
    public function getByOwnerId($userId, $status = null) {
        $query = "SELECT b.*, p.title, p.address, p.city, u.first_name, u.last_name, u.email 
                FROM bookings b 
                JOIN properties p ON b.property_id = p.id 
                JOIN users u ON b.tenant_id = u.id 
                WHERE p.owner_id = ?";
        $params = [$userId];
        
        if ($status) {
            $query .= " AND b.status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY b.check_in DESC";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Obtenir les réservations d'une propriété
     * @param int $propertyId ID de la propriété
     * @param string $status Filtrer par statut (optionnel)
     * @return array Liste des réservations
     */
    public function getByPropertyId($propertyId, $status = null) {
        $query = "SELECT b.*, u.first_name, u.last_name 
                FROM bookings b 
                JOIN users u ON b.tenant_id = u.id 
                WHERE b.property_id = ?";
        $params = [$propertyId];
        
        if ($status) {
            $query .= " AND b.status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY b.check_in DESC";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Changer le statut d'une réservation
     * @param int $bookingId ID de la réservation
     * @param string $status Nouveau statut
     * @return bool Succès ou échec
     */
    public function changeStatus($bookingId, $status) {
        $booking = $this->getById($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $result = $this->db->update('bookings', ['status' => $status], 'id = ?', [$bookingId]);
        
        if ($result > 0) {
            // Ajouter une notification pour le locataire
            $this->addNotification(
                $booking['tenant_id'],
                'Mise à jour de réservation',
                'Le statut de votre réservation a été mis à jour en "' . $status . '".',
                $bookingId
            );
            
            // Si la réservation est confirmée, créer le contrat
            if ($status === 'confirmed' && !$this->getContract($bookingId)) {
                $this->generateContract($bookingId);
            }
        }
        
        return $result > 0;
    }
    
    /**
     * Générer un contrat pour une réservation
     * @param int $bookingId ID de la réservation
     * @return string|false Chemin du contrat ou false si échec
     */
    public function generateContract($bookingId) {
        $booking = $this->getDetailedById($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        // Créer le dossier de contrats si nécessaire
        $contractsDir = ROOT_PATH . 'uploads/contracts/';
        if (!file_exists($contractsDir)) {
            mkdir($contractsDir, 0755, true);
        }
        
        // Générer le nom du fichier
        $fileName = 'contract_' . $bookingId . '_' . date('YmdHis') . '.pdf';
        $filePath = $contractsDir . $fileName;
        
        // Créer le PDF
        require_once(ROOT_PATH . 'libraries/tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(APP_NAME);
        $pdf->SetAuthor(APP_NAME);
        $pdf->SetTitle('Contrat de location');
        $pdf->SetSubject('Contrat de location');
        
        // Ajouter une page
        $pdf->AddPage();
        
        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'CONTRAT DE LOCATION', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Informations sur les parties
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'ENTRE LES SOUSSIGNÉS :', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, 'Le propriétaire : ' . $booking['owner']['first_name'] . ' ' . $booking['owner']['last_name'] . "\n" . 'Email : ' . $booking['owner']['email'], 0, 'L');
        $pdf->Ln(5);
        $pdf->MultiCell(0, 10, 'Le locataire : ' . $booking['tenant']['first_name'] . ' ' . $booking['tenant']['last_name'] . "\n" . 'Email : ' . $booking['tenant']['email'], 0, 'L');
        $pdf->Ln(10);
        
        // Informations sur le logement
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'OBJET DE LA LOCATION :', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, 'Logement : ' . $booking['property']['title'] . "\n" . 'Adresse : ' . $booking['property']['address'] . ', ' . $booking['property']['city'], 0, 'L');
        $pdf->Ln(10);
        
        // Durée et montant
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'DURÉE ET MONTANT :', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, 'Date d\'arrivée : ' . $booking['check_in'] . "\n" . 'Date de départ : ' . $booking['check_out'] . "\n" . 'Montant total : ' . $booking['total_price'] . ' €', 0, 'L');
        $pdf->Ln(10);
        
        // Signatures
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'SIGNATURES :', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 15, 'Propriétaire : ________________________', 0, 1, 'L');
        $pdf->Cell(0, 15, 'Locataire : ________________________', 0, 1, 'L');
        
        // Sauvegarder le PDF
        $pdf->Output($filePath, 'F');
        
        // Insérer dans la base de données
        $contractId = $this->db->insert('contracts', [
            'booking_id' => $bookingId,
            'contract_path' => $fileName
        ]);
        
        return $contractId ? $fileName : false;
    }
    
    /**
     * Obtenir le contrat d'une réservation
     * @param int $bookingId ID de la réservation
     * @return array|false Données du contrat ou false si non trouvé
     */
    public function getContract($bookingId) {
        return $this->db->fetchOne("SELECT * FROM contracts WHERE booking_id = ?", [$bookingId]);
    }
    
    /**
     * Signer un contrat
     * @param int $contractId ID du contrat
     * @param string $role Rôle (owner/tenant)
     * @return bool Succès ou échec
     */
    public function signContract($contractId, $role) {
        $data = [];
        
        if ($role === 'owner') {
            $data['owner_signed'] = true;
            $data['owner_signature_date'] = date('Y-m-d H:i:s');
        } else if ($role === 'tenant') {
            $data['tenant_signed'] = true;
            $data['tenant_signature_date'] = date('Y-m-d H:i:s');
        } else {
            return false;
        }
        
        return $this->db->update('contracts', $data, 'id = ?', [$contractId]) > 0;
    }
    
    /**
     * Ajouter une notification
     * @param int $userId ID de l'utilisateur
     * @param string $title Titre de la notification
     * @param string $message Message de la notification
     * @param int $relatedId ID lié (optionnel)
     * @return int|false ID de la notification ou false si échec
     */
    private function addNotification($userId, $title, $message, $relatedId = null) {
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => 'booking',
            'title' => $title,
            'message' => $message,
            'related_id' => $relatedId
        ]);
    }
    
    /**
     * Calculer le prix total d'une réservation
     * @param int $propertyId ID de la propriété
     * @param string $checkIn Date d'arrivée (Y-m-d)
     * @param string $checkOut Date de départ (Y-m-d)
     * @return float Prix total
     */
    public function calculatePrice($propertyId, $checkIn, $checkOut) {
        $propertyModel = new Property();
        $property = $propertyModel->getById($propertyId);
        
        if (!$property) {
            return 0;
        }
        
        $checkInDate = new DateTime($checkIn);
        $checkOutDate = new DateTime($checkOut);
        $interval = $checkInDate->diff($checkOutDate);
        $nights = $interval->days;
        
        return $property['price'] * $nights;
    }
    
    /**
     * Obtenir les statistiques de réservation d'un propriétaire
     * @param int $ownerId ID du propriétaire
     * @return array Statistiques
     */
    public function getOwnerStats($ownerId) {
        $stats = [];
        
        // Nombre total de réservations
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM bookings b 
            JOIN properties p ON b.property_id = p.id 
            WHERE p.owner_id = ?", 
            [$ownerId]
        );
        $stats['total_bookings'] = $result['count'];
        
        // Réservations par statut
        $statuses = $this->db->fetchAll(
            "SELECT b.status, COUNT(*) as count FROM bookings b 
            JOIN properties p ON b.property_id = p.id 
            WHERE p.owner_id = ? 
            GROUP BY b.status", 
            [$ownerId]
        );
        
        $stats['status_counts'] = [];
        foreach ($statuses as $status) {
            $stats['status_counts'][$status['status']] = $status['count'];
        }
        
        // Montant total des réservations confirmées
        $result = $this->db->fetchOne(
            "SELECT SUM(b.total_price) as total FROM bookings b 
            JOIN properties p ON b.property_id = p.id 
            WHERE p.owner_id = ? AND b.status = 'confirmed'", 
            [$ownerId]
        );
        $stats['total_confirmed_amount'] = $result['total'] ?? 0;
        
        // Réservations par mois (12 derniers mois)
        $bookingsByMonth = $this->db->fetchAll(
            "SELECT DATE_FORMAT(b.created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM bookings b 
            JOIN properties p ON b.property_id = p.id 
            WHERE p.owner_id = ? AND b.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
            GROUP BY month 
            ORDER BY month ASC", 
            [$ownerId]
        );
        
        $stats['bookings_by_month'] = [];
        foreach ($bookingsByMonth as $item) {
            $stats['bookings_by_month'][$item['month']] = $item['count'];
        }
        
        return $stats;
    }
}