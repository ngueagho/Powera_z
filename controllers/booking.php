<?php
/**
 * Contrôleur de réservation
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');
require_once('../models/Property.php');
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
$bookingModel = new Booking();
$propertyModel = new Property();
$userModel = new User();
$paymentModel = new Payment();

// Traiter en fonction de l'action
switch ($action) {
    case 'create':
        // Créer une nouvelle réservation
        handleCreateBooking($bookingModel, $propertyModel, $userId);
        break;
        
    case 'cancel':
        // Annuler une réservation
        handleCancelBooking($bookingModel, $userId, $userRole);
        break;
        
    case 'confirm':
        // Confirmer une réservation (propriétaire uniquement)
        handleConfirmBooking($bookingModel, $userId, $userRole);
        break;
        
    case 'complete':
        // Marquer une réservation comme terminée (propriétaire uniquement)
        handleCompleteBooking($bookingModel, $userId, $userRole);
        break;
        
    case 'generate_contract':
        // Générer un contrat de location
        handleGenerateContract($bookingModel, $userId, $userRole);
        break;
        
    case 'sign_contract':
        // Signer un contrat de location
        handleSignContract($bookingModel, $userId, $userRole);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

/**
 * Gérer la création d'une réservation
 * @param Booking $bookingModel Instance du modèle Booking
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 */
function handleCreateBooking($bookingModel, $propertyModel, $userId) {
    // Vérifier les champs requis
    $requiredFields = ['property_id', 'check_in', 'check_out', 'guests'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
            exit;
        }
    }
    
    $propertyId = (int)$_POST['property_id'];
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    $guests = (int)$_POST['guests'];
    
    // Valider les dates
    $checkInDate = new DateTime($checkIn);
    $checkOutDate = new DateTime($checkOut);
    $today = new DateTime();
    
    if ($checkInDate < $today) {
        echo json_encode(['success' => false, 'message' => 'La date d\'arrivée doit être future']);
        exit;
    }
    
    if ($checkOutDate <= $checkInDate) {
        echo json_encode(['success' => false, 'message' => 'La date de départ doit être postérieure à la date d\'arrivée']);
        exit;
    }
    
    // Récupérer la propriété
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier que l'utilisateur n'est pas le propriétaire
    if ($property['owner_id'] == $userId) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas réserver votre propre logement']);
        exit;
    }
    
    // Vérifier la disponibilité
    $isAvailable = $propertyModel->isAvailable($propertyId, $checkIn, $checkOut);
    
    if (!$isAvailable) {
        echo json_encode(['success' => false, 'message' => 'Ce logement n\'est pas disponible pour ces dates']);
        exit;
    }
    
    // Calculer le prix total
    $totalPrice = $bookingModel->calculatePrice($propertyId, $checkIn, $checkOut);
    
    // Créer la réservation
    $bookingData = [
        'property_id' => $propertyId,
        'tenant_id' => $userId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guests' => $guests,
        'total_price' => $totalPrice,
        'status' => 'pending',
        'payment_status' => 'pending'
    ];
    
    $bookingId = $bookingModel->create($bookingData);
    
    if ($bookingId) {
        // Créer un paiement en attente
        $paymentData = [
            'booking_id' => $bookingId,
            'amount' => $totalPrice,
            'commission' => $totalPrice * (COMMISSION_RATE / 100),
            'payment_method' => isset($_POST['payment_method']) ? $_POST['payment_method'] : 'bank_transfer',
            'status' => 'pending'
        ];
        
        $paymentModel = new Payment();
        $paymentId = $paymentModel->create($paymentData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Réservation créée avec succès. En attente de confirmation par le propriétaire.',
            'booking_id' => $bookingId,
            'payment_id' => $paymentId,
            'redirect' => '../views/dashboard/tenant/bookings.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la réservation']);
    }
}

/**
 * Gérer l'annulation d'une réservation
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleCancelBooking($bookingModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$_POST['booking_id'];
    
    // Récupérer la réservation
    $booking = $bookingModel->getById($bookingId);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canCancel = false;
    
    if ($userRole == 'admin') {
        $canCancel = true;
    } else if ($userRole == 'tenant' && $booking['tenant_id'] == $userId) {
        $canCancel = true;
    } else if ($userRole == 'owner') {
        $property = (new Property())->getById($booking['property_id']);
        $canCancel = ($property && $property['owner_id'] == $userId);
    }
    
    if (!$canCancel) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à annuler cette réservation']);
        exit;
    }
    
    // Vérifier que la réservation peut être annulée
    if ($booking['status'] != 'pending' && $booking['status'] != 'confirmed') {
        echo json_encode(['success' => false, 'message' => 'Cette réservation ne peut plus être annulée']);
        exit;
    }
    
    // Annuler la réservation
    $result = $bookingModel->changeStatus($bookingId, 'canceled');
    
    if ($result) {
        // Mettre à jour le statut du paiement si nécessaire
        if ($booking['payment_status'] == 'paid') {
            // Rembourser le paiement
            $paymentModel = new Payment();
            $payment = $paymentModel->getByBookingId($bookingId);
            
            if ($payment) {
                $paymentModel->refund($payment['id']);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Réservation annulée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'annulation de la réservation']);
    }
}

/**
 * Gérer la confirmation d'une réservation (propriétaire uniquement)
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleConfirmBooking($bookingModel, $userId, $userRole) {
    // Vérifier que l'utilisateur est propriétaire ou admin
    if ($userRole != 'owner' && $userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les propriétaires peuvent confirmer les réservations']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$_POST['booking_id'];
    
    // Récupérer la réservation
    $booking = $bookingModel->getById($bookingId);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier que l'utilisateur est bien le propriétaire
    if ($userRole == 'owner') {
        $property = (new Property())->getById($booking['property_id']);
        
        if (!$property || $property['owner_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas le propriétaire de ce logement']);
            exit;
        }
    }
    
    // Vérifier que la réservation est en attente
    if ($booking['status'] != 'pending') {
        echo json_encode(['success' => false, 'message' => 'Cette réservation n\'est pas en attente de confirmation']);
        exit;
    }
    
    // Confirmer la réservation
    $result = $bookingModel->changeStatus($bookingId, 'confirmed');
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Réservation confirmée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la confirmation de la réservation']);
    }
}

/**
 * Gérer la finalisation d'une réservation (propriétaire uniquement)
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleCompleteBooking($bookingModel, $userId, $userRole) {
    // Vérifier que l'utilisateur est propriétaire ou admin
    if ($userRole != 'owner' && $userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les propriétaires peuvent finaliser les réservations']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$_POST['booking_id'];
    
    // Récupérer la réservation
    $booking = $bookingModel->getById($bookingId);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier que l'utilisateur est bien le propriétaire
    if ($userRole == 'owner') {
        $property = (new Property())->getById($booking['property_id']);
        
        if (!$property || $property['owner_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas le propriétaire de ce logement']);
            exit;
        }
    }
    
    // Vérifier que la réservation est confirmée
    if ($booking['status'] != 'confirmed') {
        echo json_encode(['success' => false, 'message' => 'Cette réservation n\'est pas confirmée']);
        exit;
    }
    
    // Vérifier que la date de fin est passée
    $checkOutDate = new DateTime($booking['check_out']);
    $today = new DateTime();
    
    if ($checkOutDate > $today) {
        echo json_encode(['success' => false, 'message' => 'Cette réservation n\'est pas encore terminée']);
        exit;
    }
    
    // Finaliser la réservation
    $result = $bookingModel->changeStatus($bookingId, 'completed');
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Réservation finalisée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la finalisation de la réservation']);
    }
}

/**
 * Gérer la génération d'un contrat de location
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleGenerateContract($bookingModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$_POST['booking_id'];
    
    // Récupérer la réservation
    $booking = $bookingModel->getDetailedById($bookingId);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canGenerate = false;
    
    if ($userRole == 'admin') {
        $canGenerate = true;
    } else if ($userRole == 'owner' && $booking['property']['owner_id'] == $userId) {
        $canGenerate = true;
    } else if ($userRole == 'tenant' && $booking['tenant_id'] == $userId) {
        $canGenerate = true;
    }
    
    if (!$canGenerate) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à générer ce contrat']);
        exit;
    }
    
    // Vérifier que la réservation est confirmée
    if ($booking['status'] != 'confirmed') {
        echo json_encode(['success' => false, 'message' => 'Un contrat ne peut être généré que pour une réservation confirmée']);
        exit;
    }
    
    // Vérifier si un contrat existe déjà
    if (isset($booking['contract']) && $booking['contract']) {
        echo json_encode(['success' => true, 'message' => 'Un contrat existe déjà', 'contract' => $booking['contract']]);
        exit;
    }
    
    // Générer le contrat
    $contractPath = $bookingModel->generateContract($bookingId);
    
    if ($contractPath) {
        echo json_encode([
            'success' => true, 
            'message' => 'Contrat généré avec succès',
            'contract' => [
                'id' => $booking['contract']['id'] ?? null,
                'booking_id' => $bookingId,
                'contract_path' => $contractPath,
                'owner_signed' => false,
                'tenant_signed' => false
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération du contrat']);
    }
}

/**
 * Gérer la signature d'un contrat de location
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleSignContract($bookingModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['contract_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de contrat manquant']);
        exit;
    }
    
    $contractId = (int)$_POST['contract_id'];
    
    // Récupérer le contrat
    $db = Database::getInstance();
    $contract = $db->fetchOne("SELECT * FROM contracts WHERE id = ?", [$contractId]);
    
    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'Contrat introuvable']);
        exit;
    }
    
    // Récupérer la réservation
    $booking = $bookingModel->getDetailedById($contract['booking_id']);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Déterminer le rôle de signature
    $role = null;
    
    if ($userRole == 'owner' && $booking['property']['owner_id'] == $userId) {
        $role = 'owner';
        
        // Vérifier que le propriétaire n'a pas déjà signé
        if ($contract['owner_signed']) {
            echo json_encode(['success' => false, 'message' => 'Vous avez déjà signé ce contrat']);
            exit;
        }
    } else if ($userRole == 'tenant' && $booking['tenant_id'] == $userId) {
        $role = 'tenant';
        
        // Vérifier que le locataire n'a pas déjà signé
        if ($contract['tenant_signed']) {
            echo json_encode(['success' => false, 'message' => 'Vous avez déjà signé ce contrat']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à signer ce contrat']);
        exit;
    }
    
    // Signer le contrat
    $result = $bookingModel->signContract($contractId, $role);
    
    if ($result) {
        // Vérifier si le contrat est maintenant complètement signé
        $updatedContract = $db->fetchOne("SELECT * FROM contracts WHERE id = ?", [$contractId]);
        $fullySignedContract = $updatedContract['owner_signed'] && $updatedContract['tenant_signed'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Contrat signé avec succès',
            'fully_signed' => $fullySignedContract
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la signature du contrat']);
    }
}