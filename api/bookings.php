<?php
/**
 * API de réservation
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

// Définir l'en-tête JSON pour les réponses API
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || !$_SESSION[SESSION_PREFIX . 'logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION[SESSION_PREFIX . 'user_id'];
$userRole = $_SESSION[SESSION_PREFIX . 'user_role'];

// Initialiser les modèles
$bookingModel = new Booking();
$propertyModel = new Property();
$userModel = new User();
$paymentModel = new Payment();

// Traiter en fonction de la méthode HTTP
switch ($method) {
    case 'GET':
        handleGetRequest($bookingModel, $propertyModel, $userId, $userRole);
        break;
        
    case 'POST':
        handlePostRequest($bookingModel, $propertyModel, $paymentModel, $userId, $userRole);
        break;
        
    case 'PUT':
        handlePutRequest($bookingModel, $userId, $userRole);
        break;
        
    case 'DELETE':
        handleDeleteRequest($bookingModel, $userId, $userRole);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Méthode non supportée']);
        exit;
}

/**
 * Gérer les requêtes GET
 * @param Booking $bookingModel Instance du modèle Booking
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleGetRequest($bookingModel, $propertyModel, $userId, $userRole) {
    // Récupérer l'ID de la réservation depuis les paramètres GET
    $bookingId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if ($bookingId) {
        // Récupérer une réservation spécifique
        $booking = $bookingModel->getDetailedById($bookingId);
        
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
            echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à accéder à cette réservation']);
            exit;
        }
        
        echo json_encode(['success' => true, 'booking' => $booking]);
    } else {
        // Récupérer toutes les réservations de l'utilisateur
        $bookings = [];
        
        if ($userRole == 'admin') {
            // Les administrateurs peuvent voir toutes les réservations
            $bookings = $bookingModel->getAll();
        } else if ($userRole == 'owner') {
            // Les propriétaires voient les réservations de leurs propriétés
            $bookings = $bookingModel->getByOwnerId($userId);
        } else if ($userRole == 'tenant') {
            // Les locataires voient leurs propres réservations
            $bookings = $bookingModel->getByTenantId($userId);
        }
        
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}

/**
 * Gérer les requêtes POST
 * @param Booking $bookingModel Instance du modèle Booking
 * @param Property $propertyModel Instance du modèle Property
 * @param Payment $paymentModel Instance du modèle Payment
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handlePostRequest($bookingModel, $propertyModel, $paymentModel, $userId, $userRole) {
    // Pour les requêtes AJAX/Fetch avec content-type application/json
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    // Si le corps de la requête est au format JSON, l'utiliser; sinon, utiliser $_POST
    $data = $input ? $input : $_POST;
    
    // Récupérer l'action
    $action = isset($data['action']) ? $data['action'] : 'create';
    
    switch ($action) {
        case 'create':
            // Créer une nouvelle réservation
            createBooking($bookingModel, $propertyModel, $paymentModel, $userId, $userRole, $data);
            break;
            
        case 'cancel':
            // Annuler une réservation
            cancelBooking($bookingModel, $userId, $userRole, $data);
            break;
            
        case 'confirm':
            // Confirmer une réservation (propriétaire uniquement)
            confirmBooking($bookingModel, $userId, $userRole, $data);
            break;
            
        case 'complete':
            // Marquer une réservation comme terminée (propriétaire uniquement)
            completeBooking($bookingModel, $userId, $userRole, $data);
            break;
            
        case 'generate_contract':
            // Générer un contrat de location
            generateContract($bookingModel, $userId, $userRole, $data);
            break;
            
        case 'sign_contract':
            // Signer un contrat de location
            signContract($bookingModel, $userId, $userRole, $data);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            exit;
    }
}

/**
 * Gérer les requêtes PUT
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handlePutRequest($bookingModel, $userId, $userRole) {
    // Pour les requêtes AJAX/Fetch avec content-type application/json
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    // Si le corps de la requête est au format JSON, l'utiliser; sinon, utiliser $_POST
    $data = $input ? $input : $_POST;
    
    // Vérifier si l'ID de la réservation est spécifié
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$data['id'];
    
    // Récupérer la réservation
    $booking = $bookingModel->getById($bookingId);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canUpdate = false;
    
    if ($userRole == 'admin') {
        $canUpdate = true;
    } else {
        // Pour les autres rôles, vérifier en fonction des champs à mettre à jour
        $property = (new Property())->getById($booking['property_id']);
        
        if ($userRole == 'owner' && $property && $property['owner_id'] == $userId) {
            // Les propriétaires peuvent mettre à jour certains champs
            $allowedFields = ['status', 'notes'];
            $hasAllowedFields = false;
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $hasAllowedFields = true;
                    break;
                }
            }
            
            $canUpdate = $hasAllowedFields;
        } else if ($userRole == 'tenant' && $booking['tenant_id'] == $userId) {
            // Les locataires peuvent mettre à jour certains champs
            $allowedFields = ['guests', 'notes'];
            $hasAllowedFields = false;
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $hasAllowedFields = true;
                    break;
                }
            }
            
            $canUpdate = $hasAllowedFields;
        }
    }
    
    if (!$canUpdate) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à mettre à jour cette réservation']);
        exit;
    }
    
    // Préparer les données à mettre à jour
    $bookingData = [];
    
    // Filtrer les champs selon le rôle
    if ($userRole == 'admin') {
        // Les administrateurs peuvent mettre à jour tous les champs
        $updatableFields = ['tenant_id', 'check_in', 'check_out', 'guests', 'total_price', 'status', 'payment_status', 'notes'];
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $bookingData[$field] = $data[$field];
            }
        }
    } else if ($userRole == 'owner') {
        // Les propriétaires peuvent mettre à jour certains champs
        $updatableFields = ['status', 'notes'];
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $bookingData[$field] = $data[$field];
            }
        }
    } else if ($userRole == 'tenant') {
        // Les locataires peuvent mettre à jour certains champs
        $updatableFields = ['guests', 'notes'];
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $bookingData[$field] = $data[$field];
            }
        }
    }
    
    // Mettre à jour la réservation
    $result = $bookingModel->update($bookingId, $bookingData);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Réservation mise à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la réservation']);
    }
}

/**
 * Gérer les requêtes DELETE
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleDeleteRequest($bookingModel, $userId, $userRole) {
    // Pour les requêtes AJAX/Fetch avec content-type application/json
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    // Si le corps de la requête est au format JSON, l'utiliser; sinon, utiliser $_REQUEST
    $data = $input ? $input : $_REQUEST;
    
    // Vérifier si l'ID de la réservation est spécifié
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$data['id'];
    
    // Récupérer la réservation
    $booking = $bookingModel->getById($bookingId);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canDelete = false;
    
    if ($userRole == 'admin') {
        $canDelete = true;
    } else if ($userRole == 'tenant' && $booking['tenant_id'] == $userId) {
        // Les locataires peuvent supprimer uniquement les réservations en attente
        $canDelete = ($booking['status'] == 'pending');
    }
    
    if (!$canDelete) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cette réservation']);
        exit;
    }
    
    // Supprimer la réservation
    $result = $bookingModel->delete($bookingId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Réservation supprimée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la réservation']);
    }
}

/**
 * Créer une nouvelle réservation
 * @param Booking $bookingModel Instance du modèle Booking
 * @param Property $propertyModel Instance du modèle Property
 * @param Payment $paymentModel Instance du modèle Payment
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 * @param array $data Données de la requête
 */
function createBooking($bookingModel, $propertyModel, $paymentModel, $userId, $userRole, $data) {
    // Vérifier que l'utilisateur est un locataire ou un administrateur
    if ($userRole != 'tenant' && $userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les locataires peuvent effectuer des réservations']);
        exit;
    }
    
    // Vérifier les champs requis
    $requiredFields = ['property_id', 'check_in', 'check_out', 'guests'];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
            exit;
        }
    }
    
    $propertyId = (int)$data['property_id'];
    $checkIn = $data['check_in'];
    $checkOut = $data['check_out'];
    $guests = (int)$data['guests'];
    
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
        'tenant_id' => $userRole == 'admin' && isset($data['tenant_id']) ? (int)$data['tenant_id'] : $userId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guests' => $guests,
        'total_price' => $totalPrice,
        'status' => 'pending',
        'payment_status' => 'pending',
        'notes' => isset($data['notes']) ? htmlspecialchars($data['notes']) : null
    ];
    
    $bookingId = $bookingModel->create($bookingData);
    
    if ($bookingId) {
        // Créer un paiement en attente
        $paymentData = [
            'booking_id' => $bookingId,
            'amount' => $totalPrice,
            'commission' => $totalPrice * (COMMISSION_RATE / 100),
            'payment_method' => isset($data['payment_method']) ? $data['payment_method'] : 'bank_transfer',
            'status' => 'pending'
        ];
        
        $paymentId = $paymentModel->create($paymentData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Réservation créée avec succès. En attente de confirmation par le propriétaire.',
            'booking_id' => $bookingId,
            'payment_id' => $paymentId,
            'redirect' => '../views/dashboard/' . ($userRole == 'admin' ? 'admin' : 'tenant') . '/bookings.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la réservation']);
    }
}

/**
 * Annuler une réservation
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 * @param array $data Données de la requête
 */
function cancelBooking($bookingModel, $userId, $userRole, $data) {
    // Vérifier les paramètres requis
    if (!isset($data['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$data['booking_id'];
    
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
 * Confirmer une réservation
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 * @param array $data Données de la requête
 */
function confirmBooking($bookingModel, $userId, $userRole, $data) {
    // Vérifier que l'utilisateur est propriétaire ou admin
    if ($userRole != 'owner' && $userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les propriétaires peuvent confirmer les réservations']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($data['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$data['booking_id'];
    
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
 * Marquer une réservation comme terminée
 * @param Booking $bookingModel Instance du modèle Booking
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 * @param array $data Données de la requête
 */
function completeBooking($bookingModel, $userId, $userRole, $data) {
    // Vérifier que l'utilisateur est propriétaire ou admin
    if ($userRole != 'owner' && $userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les propriétaires peuvent finaliser les réservations']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($data['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }
    
    $bookingId = (int)$data['booking_id'];
    
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
 * Générer un contrat de location
 * @param Booking $bookingModel Instance