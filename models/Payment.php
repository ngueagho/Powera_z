<?php
/**
 * Classe Payment
 * Gère les opérations liées aux paiements
 */
class Payment {
    private $db;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Créer un nouveau paiement
     * @param array $paymentData Données du paiement
     * @return int|false ID du paiement ou false si échec
     */
    public function create($paymentData) {
        // Calculer la commission
        if (!isset($paymentData['commission'])) {
            $paymentData['commission'] = $this->calculateCommission($paymentData['amount']);
        }
        
        return $this->db->insert('payments', $paymentData);
    }
    
    /**
     * Mettre à jour un paiement
     * @param int $paymentId ID du paiement
     * @param array $paymentData Données à mettre à jour
     * @return int Nombre de lignes affectées
     */
    public function update($paymentId, $paymentData) {
        return $this->db->update('payments', $paymentData, 'id = ?', [$paymentId]);
    }
    
    /**
     * Obtenir un paiement par son ID
     * @param int $paymentId ID du paiement
     * @return array|false Données du paiement ou false si non trouvé
     */
    public function getById($paymentId) {
        return $this->db->fetchOne("SELECT * FROM payments WHERE id = ?", [$paymentId]);
    }
    
    /**
     * Obtenir un paiement par ID de réservation
     * @param int $bookingId ID de la réservation
     * @return array|false Données du paiement ou false si non trouvé
     */
    public function getByBookingId($bookingId) {
        return $this->db->fetchOne("SELECT * FROM payments WHERE booking_id = ?", [$bookingId]);
    }
    
    /**
     * Récupérer tous les paiements d'un utilisateur (en tant que locataire)
     * @param int $userId ID de l'utilisateur
     * @return array Liste des paiements
     */
    public function getByTenantId($userId) {
        $query = "
            SELECT p.*, b.check_in, b.check_out, pr.title as property_title, pr.id as property_id
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN properties pr ON b.property_id = pr.id
            WHERE b.tenant_id = ?
            ORDER BY p.created_at DESC
        ";
        
        return $this->db->fetchAll($query, [$userId]);
    }
    
    /**
     * Récupérer tous les paiements d'un utilisateur (en tant que propriétaire)
     * @param int $userId ID de l'utilisateur
     * @return array Liste des paiements
     */
    public function getByOwnerId($userId) {
        $query = "
            SELECT p.*, b.check_in, b.check_out, pr.title as property_title, pr.id as property_id,
                   u.first_name, u.last_name, u.email
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN properties pr ON b.property_id = pr.id
            JOIN users u ON b.tenant_id = u.id
            WHERE pr.owner_id = ?
            ORDER BY p.created_at DESC
        ";
        
        return $this->db->fetchAll($query, [$userId]);
    }
    
    /**
     * Marquer un paiement comme complété
     * @param int $paymentId ID du paiement
     * @param string $transactionId ID de la transaction
     * @return bool Succès ou échec
     */
    public function markAsCompleted($paymentId, $transactionId) {
        return $this->db->update('payments', [
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'payment_date' => date('Y-m-d H:i:s')
        ], 'id = ?', [$paymentId]) > 0;
    }
    
    /**
     * Marquer un paiement comme échoué
     * @param int $paymentId ID du paiement
     * @return bool Succès ou échec
     */
    public function markAsFailed($paymentId) {
        return $this->db->update('payments', [
            'status' => 'failed'
        ], 'id = ?', [$paymentId]) > 0;
    }
    
    /**
     * Effectuer un remboursement
     * @param int $paymentId ID du paiement
     * @return bool Succès ou échec
     */
    public function refund($paymentId) {
        // Implémentez la logique de remboursement avec le fournisseur de paiement ici
        
        return $this->db->update('payments', [
            'status' => 'refunded'
        ], 'id = ?', [$paymentId]) > 0;
    }
    
    /**
     * Calculer la commission sur un montant
     * @param float $amount Montant du paiement
     * @return float Montant de la commission
     */
    public function calculateCommission($amount) {
        // La commission est définie dans la configuration (COMMISSION_RATE)
        return $amount * (COMMISSION_RATE / 100);
    }
    
    /**
     * Obtenir les statistiques de paiement d'un propriétaire
     * @param int $ownerId ID du propriétaire
     * @return array Statistiques des paiements
     */
    public function getOwnerStats($ownerId) {
        $stats = [];
        
        // Montant total des paiements complétés
        $result = $this->db->fetchOne("
            SELECT SUM(p.amount - p.commission) as total
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN properties pr ON b.property_id = pr.id
            WHERE pr.owner_id = ? AND p.status = 'completed'
        ", [$ownerId]);
        
        $stats['total_payments'] = $result['total'] ?? 0;
        
        // Répartition par méthode de paiement
        $methods = $this->db->fetchAll("
            SELECT p.payment_method, COUNT(*) as count
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN properties pr ON b.property_id = pr.id
            WHERE pr.owner_id = ? AND p.status = 'completed'
            GROUP BY p.payment_method
        ", [$ownerId]);
        
        $stats['payment_methods'] = [];
        foreach ($methods as $method) {
            $stats['payment_methods'][$method['payment_method']] = $method['count'];
        }
        
        // Paiements par mois (12 derniers mois)
        $paymentsByMonth = $this->db->fetchAll("
            SELECT DATE_FORMAT(p.payment_date, '%Y-%m') as month, SUM(p.amount - p.commission) as total
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN properties pr ON b.property_id = pr.id
            WHERE pr.owner_id = ? AND p.status = 'completed' AND p.payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ", [$ownerId]);
        
        $stats['payments_by_month'] = [];
        foreach ($paymentsByMonth as $item) {
            $stats['payments_by_month'][$item['month']] = $item['total'];
        }
        
        return $stats;
    }
    
    /**
     * Générer une facture PDF
     * @param int $paymentId ID du paiement
     * @return string|false Chemin du fichier PDF ou false si échec
     */
    public function generateInvoice($paymentId) {
        // Récupérer les informations du paiement
        $payment = $this->getById($paymentId);
        if (!$payment) {
            return false;
        }
        
        // Récupérer les détails de la réservation
        $booking = (new Booking())->getDetailedById($payment['booking_id']);
        if (!$booking) {
            return false;
        }
        
        // Créer le dossier des factures si nécessaire
        $invoicesDir = ROOT_PATH . 'uploads/invoices/';
        if (!file_exists($invoicesDir)) {
            mkdir($invoicesDir, 0755, true);
        }
        
        // Générer le nom du fichier
        $fileName = 'invoice_' . $paymentId . '_' . date('YmdHis') . '.pdf';
        $filePath = $invoicesDir . $fileName;
        
        // Créer le PDF (utiliser TCPDF ou FPDF)
        require_once(ROOT_PATH . 'libraries/tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(APP_NAME);
        $pdf->SetAuthor(APP_NAME);
        $pdf->SetTitle('Facture #' . $paymentId);
        $pdf->SetSubject('Facture de réservation');
        
        // Ajouter une page
        $pdf->AddPage();
        
        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'FACTURE #' . $paymentId, 0, 1, 'C');
        $pdf->Ln(5);
        
        // Informations sur les parties
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'INFORMATIONS', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        
        // Informations sur le propriétaire et le locataire
        $pdf->MultiCell(0, 10, 'Propriétaire : ' . $booking['owner']['first_name'] . ' ' . $booking['owner']['last_name'] . "\n" . 
                           'Locataire : ' . $booking['tenant']['first_name'] . ' ' . $booking['tenant']['last_name'], 0, 'L');
        $pdf->Ln(5);
        
        // Informations sur la réservation
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'DÉTAILS DE LA RÉSERVATION', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, 'Logement : ' . $booking['property']['title'] . "\n" . 
                           'Adresse : ' . $booking['property']['address'] . ', ' . $booking['property']['city'] . "\n" . 
                           'Période : du ' . date('d/m/Y', strtotime($booking['check_in'])) . ' au ' . date('d/m/Y', strtotime($booking['check_out'])), 0, 'L');
        $pdf->Ln(5);
        
        // Détails du paiement
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'DÉTAILS DU PAIEMENT', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(90, 10, 'Montant total', 0, 0, 'L');
        $pdf->Cell(90, 10, number_format($payment['amount'], 0, ',', ' ') . ' FCFA', 0, 1, 'R');
        $pdf->Cell(90, 10, 'Commission plateforme (' . COMMISSION_RATE . '%)', 0, 0, 'L');
        $pdf->Cell(90, 10, '- ' . number_format($payment['commission'], 0, ',', ' ') . ' FCFA', 0, 1, 'R');
        $pdf->Cell(90, 10, 'Montant net propriétaire', 0, 0, 'L');
        $pdf->Cell(90, 10, number_format($payment['amount'] - $payment['commission'], 0, ',', ' ') . ' FCFA', 0, 1, 'R');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'PAIEMENT', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(90, 10, 'Méthode de paiement', 0, 0, 'L');
        $pdf->Cell(90, 10, ucfirst(str_replace('_', ' ', $payment['payment_method'])), 0, 1, 'R');
        $pdf->Cell(90, 10, 'Date de paiement', 0, 0, 'L');
        $pdf->Cell(90, 10, date('d/m/Y H:i', strtotime($payment['payment_date'])), 0, 1, 'R');
        $pdf->Cell(90, 10, 'Statut', 0, 0, 'L');
        $pdf->Cell(90, 10, ucfirst($payment['status']), 0, 1, 'R');
        $pdf->Ln(10);
        
        // Notes et conditions
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 10, 'Cette facture a été générée automatiquement par ' . APP_NAME . '. ' . 
                           'Pour toute question concernant cette facture, veuillez contacter notre service client.', 0, 'L');
        
        // Sauvegarder le PDF
        $pdf->Output($filePath, 'F');
        
        // Mettre à jour le paiement avec le chemin de la facture
        $this->db->update('payments', [
            'invoice_path' => $fileName
        ], 'id = ?', [$paymentId]);
        
        return $fileName;
    }
}