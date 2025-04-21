<?php
/**
 * Contrôleur d'administration
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');
require_once('../models/Property.php');
require_once('../models/Booking.php');
require_once('../models/Review.php');

// Démarrer la session
session_start();

// Définir l'en-tête JSON pour les réponses AJAX
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || 
    !$_SESSION[SESSION_PREFIX . 'logged_in'] || 
    $_SESSION[SESSION_PREFIX . 'user_role'] !== 'admin') {
    
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier si l'action est spécifiée
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    exit;
}

$action = $_POST['action'];

// Initialiser les modèles
$userModel = new User();
$propertyModel = new Property();
$bookingModel = new Booking();
$reviewModel = new Review();

// Traiter en fonction de l'action
switch ($action) {
    case 'user_status':
        // Changer le statut d'un utilisateur
        handleUserStatus($userModel);
        break;
        
    case 'delete_user':
        // Supprimer un utilisateur
        handleDeleteUser($userModel);
        break;
        
    case 'property_status':
        // Changer le statut d'une propriété
        handlePropertyStatus($propertyModel);
        break;
        
    case 'delete_property':
        // Supprimer une propriété
        handleDeleteProperty($propertyModel);
        break;
        
    case 'approve_review':
        // Approuver un avis
        handleApproveReview($reviewModel);
        break;
        
    case 'reject_review':
        // Rejeter un avis
        handleRejectReview($reviewModel);
        break;
        
    case 'get_statistics':
        // Récupérer les statistiques générales
        handleGetStatistics($userModel, $propertyModel, $bookingModel);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

/**
 * Gérer le changement de statut d'un utilisateur
 * @param User $userModel Instance du modèle User
 */
function handleUserStatus($userModel) {
    // Vérifier les paramètres requis
    if (!isset($_POST['user_id']) || !isset($_POST['status'])) {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }
    
    $userId = (int)$_POST['user_id'];
    $status = $_POST['status'];
    
    // Valider le statut
    $validStatuses = ['active', 'inactive', 'banned'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        exit;
    }
    
    // Changer le statut
    $result = $userModel->changeStatus($userId, $status);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut']);
    }
}

/**
 * Gérer la suppression d'un utilisateur
 * @param User $userModel Instance du modèle User
 */
function handleDeleteUser($userModel) {
    // Vérifier les paramètres requis
    if (!isset($_POST['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
        exit;
    }
    
    $userId = (int)$_POST['user_id'];
    
    // Supprimer l'utilisateur
    $result = $userModel->delete($userId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'utilisateur']);
    }
}

/**
 * Gérer le changement de statut d'une propriété
 * @param Property $propertyModel Instance du modèle Property
 */
function handlePropertyStatus($propertyModel) {
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id']) || !isset($_POST['status'])) {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    $status = $_POST['status'];
    
    // Valider le statut
    $validStatuses = ['available', 'rented', 'maintenance', 'pending_approval', 'inactive'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        exit;
    }
    
    // Mettre à jour le statut
    $result = $propertyModel->update($propertyId, ['status' => $status]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut']);
    }
}

/**
 * Gérer la suppression d'une propriété
 * @param Property $propertyModel Instance du modèle Property
 */
function handleDeleteProperty($propertyModel) {
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID propriété manquant']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    
    // Supprimer la propriété
    $result = $propertyModel->delete($propertyId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Propriété supprimée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la propriété']);
    }
}

/**
 * Gérer l'approbation d'un avis
 * @param Review $reviewModel Instance du modèle Review
 */
function handleApproveReview($reviewModel) {
    // Vérifier les paramètres requis
    if (!isset($_POST['review_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID avis manquant']);
        exit;
    }
    
    $reviewId = (int)$_POST['review_id'];
    
    // Approuver l'avis
    $result = $reviewModel->approve($reviewId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Avis approuvé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'approbation de l\'avis']);
    }
}

/**
 * Gérer le rejet d'un avis
 * @param Review $reviewModel Instance du modèle Review
 */
function handleRejectReview($reviewModel) {
    // Vérifier les paramètres requis
    if (!isset($_POST['review_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID avis manquant']);
        exit;
    }
    
    $reviewId = (int)$_POST['review_id'];
    
    // Rejeter l'avis
    $result = $reviewModel->reject($reviewId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Avis rejeté avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du rejet de l\'avis']);
    }
}

/**
 * Récupérer les statistiques générales
 * @param User $userModel Instance du modèle User
 * @param Property $propertyModel Instance du modèle Property
 * @param Booking $bookingModel Instance du modèle Booking
 */
function handleGetStatistics($userModel, $propertyModel, $bookingModel) {
    $stats = [];
    
    // Statistiques des utilisateurs
    $allUsers = $userModel->getAll();
    $stats['total_users'] = count($allUsers);
    
    $ownerCount = 0;
    $tenantCount = 0;
    $adminCount = 0;
    
    foreach ($allUsers as $user) {
        switch ($user['role']) {
            case 'owner':
                $ownerCount++;
                break;
            case 'tenant':
                $tenantCount++;
                break;
            case 'admin':
                $adminCount++;
                break;
        }
    }
    
    $stats['owners_count'] = $ownerCount;
    $stats['tenants_count'] = $tenantCount;
    $stats['admins_count'] = $adminCount;
    
    // Statistiques des propriétés
    $allProperties = $propertyModel->getAll([]);
    $stats['total_properties'] = count($allProperties);
    
    $availableCount = 0;
    $rentedCount = 0;
    $pendingCount = 0;
    $maintenanceCount = 0;
    $inactiveCount = 0;
    
    foreach ($allProperties as $property) {
        switch ($property['status']) {
            case 'available':
                $availableCount++;
                break;
            case 'rented':
                $rentedCount++;
                break;
            case 'pending_approval':
                $pendingCount++;
                break;
            case 'maintenance':
                $maintenanceCount++;
                break;
            case 'inactive':
                $inactiveCount++;
                break;
        }
    }
    
    $stats['available_properties'] = $availableCount;
    $stats['rented_properties'] = $rentedCount;
    $stats['pending_properties'] = $pendingCount;
    $stats['maintenance_properties'] = $maintenanceCount;
    $stats['inactive_properties'] = $inactiveCount;
    
    // Statistiques des réservations
    $db = Database::getInstance();
    
    // Total des réservations
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM bookings");
    $stats['total_bookings'] = $result['count'];
    
    // Réservations par statut
    $statuses = $db->fetchAll("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
    $stats['bookings_by_status'] = [];
    foreach ($statuses as $status) {
        $stats['bookings_by_status'][$status['status']] = $status['count'];
    }
    
    // Revenus totaux et commissions
    $result = $db->fetchOne("SELECT SUM(amount) as total_amount, SUM(commission) as total_commission FROM payments WHERE status = 'completed'");
    $stats['total_revenue'] = $result['total_amount'] ?? 0;
    $stats['total_commission'] = $result['total_commission'] ?? 0;
    
    // Envoyer les statistiques
    echo json_encode(['success' => true, 'stats' => $stats]);
}