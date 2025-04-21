<?php
/**
 * Contrôleur de paiement
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');
require_once('../models/Booking.php');
require_once('../models/Payment.php');

// Démarrer la session
session_start();

// Définir l'en-tête JSON pour les réponses AJAX
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || !$_SESSION[SESSION_PREFIX . 'logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier si l'action est spécifiée
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    exit;
}

$action = $_POST['action'];
$userId = $_SESSION[SESSION_PREFIX . 'user_id'];
$userRole = $_SESSION[SESSION_PREFIX . 'user_role'];

// Initialiser les modèles
$paymentModel = new Payment();
$bookingModel = new Booking();
$userModel = new User();

// Traiter en fonction de l'action
switch ($action) {
    case 'process_payment':
        // Traiter un paiement
        handleProcessPayment($paymentModel, $bookingModel, $userId, $userRole);
        break;
        
    case 'verify_payment':
        // Vérifier un paiement
        handleVerifyPayment($paymentModel, $bookingModel, $userId, $userRole);
        break;
        
    case 'mark_as_paid':
        // Marquer un paiement comme payé (administrateur uniquement)
        handleMarkAsPaid($paymentModel, $bookingModel, $userRole);
        break;
        
    case 'generate_invoice':
        // Générer une facture
        handleGenerateInvoice($paymentModel, $userId, $userRole);
        break;
        
    case 'refund_payment':
        // Rembourser un paiement (administrateur uniquement)
        handleRefundPayment($paymentModel, $userRole);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

/**
 * Gérer le traitement d'un paiement
 * @param Payment $paymentModel Instance du modèle Payment
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleProcessPayment($paymentModel, $bookingModel, $userId, $userRole) {
    // Vérifier que l'utilisateur est un locataire
    if ($userRole != 'tenant' && $userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les locataires peuvent effectuer des paiements']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['payment_id']) || !isset($_POST['payment_method'])) {
        echo json_encode(['success' => false, 'message' => 'ID de paiement et méthode de paiement requis']);
        exit;
    }
    
    $paymentId = (int)$_POST['payment_id'];
    $paymentMethod = $_POST['payment_method'];
    
    // Récupérer le paiement
    $payment = $paymentModel->getById($paymentId);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Paiement introuvable']);
        exit;
    }
    
    // Récupérer la réservation
    $booking = $bookingModel->getById($payment['booking_id']);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier que l'utilisateur est bien le locataire
    if ($userRole == 'tenant' && $booking['tenant_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas payer pour cette réservation']);
        exit;
    }
    
    // Vérifier que le paiement est en attente
    if ($payment['status'] != 'pending') {
        echo json_encode(['success' => false, 'message' => 'Ce paiement n\'est pas en attente']);
        exit;
    }
    
    // Valider la méthode de paiement
    $validMethods = ['credit_card', 'paypal', 'bank_transfer', 'mobile_money'];
    if (!in_array($paymentMethod, $validMethods)) {
        echo json_encode(['success' => false, 'message' => 'Méthode de paiement invalide']);
        exit;
    }
    
    // Mettre à jour la méthode de paiement
    $paymentModel->update($paymentId, ['payment_method' => $paymentMethod]);
    
    // Simulation du traitement du paiement
    // Dans une application réelle, vous intégreriez ici un service de paiement (Stripe, PayPal, etc.)
    $success = true;
    $transactionId = 'TRX' . time() . rand(1000, 9999);
    
    if ($success) {
        // Marquer le paiement comme complété
        $paymentModel->markAsCompleted($paymentId, $transactionId);
        
        // Mettre à jour le statut de la réservation
        $bookingModel->update($booking['id'], ['payment_status' => 'paid']);
        
        // Générer la facture
        $invoicePath = $paymentModel->generateInvoice($paymentId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Paiement traité avec succès',
            'transaction_id' => $transactionId,
            'invoice_path' => $invoicePath
        ]);
    } else {
        // Marquer le paiement comme échoué
        $paymentModel->markAsFailed($paymentId);
        
        echo json_encode(['success' => false, 'message' => 'Erreur lors du traitement du paiement']);
    }
}

/**
 * Gérer la vérification d'un paiement
 * @param Payment $paymentModel Instance du modèle Payment
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleVerifyPayment($paymentModel, $bookingModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['payment_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de paiement manquant']);
        exit;
    }
    
    $paymentId = (int)$_POST['payment_id'];
    
    // Récupérer le paiement
    $payment = $paymentModel->getById($paymentId);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Paiement introuvable']);
        exit;
    }
    
    // Récupérer la réservation
    $booking = $bookingModel->getDetailedById($payment['booking_id']);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canAccess = false;
    
    if ($userRole == 'admin') {
        $canAccess = true;
    } else if ($userRole == 'owner' && $booking['property']['owner_id'] == $userId) {
        $canAccess = true;
    } else if ($userRole == 'tenant' && $booking['tenant_id'] == $userId) {
        $canAccess = true;
    }
    
    if (!$canAccess) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à vérifier ce paiement']);
        exit;
    }
    
    // Retourner les détails du paiement
    echo json_encode([
        'success' => true,
        'payment' => $payment,
        'booking' => $booking
    ]);
}

/**
 * Gérer le marquage d'un paiement comme payé (administrateur uniquement)
 * @param Payment $paymentModel Instance du modèle Payment
 * @param Booking $bookingModel Instance du modèle Booking
 * @param string $userRole Rôle de l'utilisateur
 */
function handleMarkAsPaid($paymentModel, $bookingModel, $userRole) {
    // Vérifier que l'utilisateur est un administrateur
    if ($userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les administrateurs peuvent marquer les paiements comme payés']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['payment_id']) || !isset($_POST['transaction_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de paiement et ID de transaction requis']);
        exit;
    }
    
    $paymentId = (int)$_POST['payment_id'];
    $transactionId = $_POST['transaction_id'];
    
    // Récupérer le paiement
    $payment = $paymentModel->getById($paymentId);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Paiement introuvable']);
        exit;
    }
    
    // Vérifier que le paiement est en attente
    if ($payment['status'] != 'pending') {
        echo json_encode(['success' => false, 'message' => 'Ce paiement n\'est pas en attente']);
        exit;
    }
    
    // Marquer le paiement comme complété
    $result = $paymentModel->markAsCompleted($paymentId, $transactionId);
    
    if ($result) {
        // Mettre à jour le statut de la réservation
        $bookingModel->update($payment['booking_id'], ['payment_status' => 'paid']);
        
        // Générer la facture
        $invoicePath = $paymentModel->generateInvoice($paymentId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Paiement marqué comme payé avec succès',
            'invoice_path' => $invoicePath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du paiement']);
    }
}

/**
 * Gérer la génération d'une facture
 * @param Payment $paymentModel Instance du modèle Payment
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleGenerateInvoice($paymentModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['payment_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de paiement manquant']);
        exit;
    }
    
    $paymentId = (int)$_POST['payment_id'];
    
    // Récupérer le paiement
    $payment = $paymentModel->getById($paymentId);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Paiement introuvable']);
        exit;
    }
    
    // Récupérer la réservation
    $booking = (new Booking())->getDetailedById($payment['booking_id']);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canAccess = false;
    
    if ($userRole == 'admin') {
        $canAccess = true;
    } else if ($userRole == 'owner' && $booking['property']['owner_id'] == $userId) {
        $canAccess = true;
    } else if ($userRole == 'tenant' && $booking['tenant_id'] == $userId) {
        $canAccess = true;
    }
    
    if (!$canAccess) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à générer cette facture']);
        exit;
    }
    
    // Vérifier que le paiement est complété
    if ($payment['status'] != 'completed') {
        echo json_encode(['success' => false, 'message' => 'Une facture ne peut être générée que pour un paiement complété']);
        exit;
    }
    
    // Générer la facture
    $invoicePath = $paymentModel->generateInvoice($paymentId);
    
    if ($invoicePath) {
        echo json_encode([
            'success' => true,
            'message' => 'Facture générée avec succès',
            'invoice_path' => $invoicePath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération de la facture']);
    }
}

/**
 * Gérer le remboursement d'un paiement (administrateur uniquement)
 * @param Payment $paymentModel Instance du modèle Payment
 * @param string $userRole Rôle de l'utilisateur
 */
function handleRefundPayment($paymentModel, $userRole) {
    // Vérifier que l'utilisateur est un administrateur
    if ($userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les administrateurs peuvent rembourser les paiements']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['payment_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de paiement manquant']);
        exit;
    }
    
    $paymentId = (int)$_POST['payment_id'];
    
    // Récupérer le paiement
    $payment = $paymentModel->getById($paymentId);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Paiement introuvable']);
        exit;
    }
    
    // Vérifier que le paiement est complété
    if ($payment['status'] != 'completed') {
        echo json_encode(['success' => false, 'message' => 'Seuls les paiements complétés peuvent être remboursés']);
        exit;
    }
    
    // Effectuer le remboursement
    $result = $paymentModel->refund($paymentId);
    
    if ($result) {
        // Mettre à jour la réservation
        $bookingModel = new Booking();
        $bookingModel->update($payment['booking_id'], ['payment_status' => 'refunded']);
        
        echo json_encode(['success' => true, 'message' => 'Paiement remboursé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du remboursement du paiement']);
    }
}