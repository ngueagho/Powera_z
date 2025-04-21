<?php
/**
 * Contrôleur de chat
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');
require_once('../models/Message.php');

// Démarrer la session
session_start();

// Définir l'en-tête JSON pour les réponses AJAX
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || !$_SESSION[SESSION_PREFIX . 'logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer l'ID de l'utilisateur courant
$currentUserId = $_SESSION[SESSION_PREFIX . 'user_id'];

// Vérifier si l'action est spécifiée
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    exit;
}

$action = $_POST['action'];

// Initialiser les modèles
$messageModel = new Message();
$userModel = new User();

// Traiter en fonction de l'action
switch ($action) {
    case 'send_message':
        // Envoyer un message
        handleSendMessage($messageModel, $currentUserId);
        break;
        
    case 'get_conversation':
        // Récupérer une conversation
        handleGetConversation($messageModel, $currentUserId);
        break;
        
    case 'mark_as_read':
        // Marquer les messages comme lus
        handleMarkAsRead($messageModel, $currentUserId);
        break;
        
    case 'delete_message':
        // Supprimer un message
        handleDeleteMessage($messageModel, $currentUserId);
        break;
        
    case 'delete_conversation':
        // Supprimer une conversation
        handleDeleteConversation($messageModel, $currentUserId);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

/**
 * Gérer l'envoi d'un message
 * @param Message $messageModel Instance du modèle Message
 * @param int $senderId ID de l'expéditeur
 */
function handleSendMessage($messageModel, $senderId) {
    // Vérifier les paramètres requis
    if (!isset($_POST['receiver_id']) || !isset($_POST['content']) || empty($_POST['content'])) {
        echo json_encode(['success' => false, 'message' => 'Destinataire et contenu requis']);
        exit;
    }
    
    $receiverId = (int)$_POST['receiver_id'];
    $content = trim($_POST['content']);
    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;
    
    // Vérifier que le destinataire existe
    $receiver = (new User())->getById($receiverId);
    
    if (!$receiver) {
        echo json_encode(['success' => false, 'message' => 'Destinataire introuvable']);
        exit;
    }
    
    // Envoyer le message
    $messageId = $messageModel->send($senderId, $receiverId, $content, $propertyId);
    
    if ($messageId) {
        echo json_encode([
            'success' => true, 
            'message' => 'Message envoyé',
            'message_id' => $messageId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message']);
    }
}

/**
 * Gérer la récupération d'une conversation
 * @param Message $messageModel Instance du modèle Message
 * @param int $userId ID de l'utilisateur
 */
function handleGetConversation($messageModel, $userId) {
    // Vérifier les paramètres requis
    if (!isset($_POST['contact_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de contact manquant']);
        exit;
    }
    
    $contactId = (int)$_POST['contact_id'];
    
    // Récupérer la conversation
    $messages = $messageModel->getConversation($userId, $contactId);
    
    // Marquer les messages comme lus
    $messageModel->markAsRead($contactId, $userId);
    
    echo json_encode([
        'success' => true,
        'conversation' => [
            'messages' => $messages,
            'contact' => (new User())->getById($contactId)
        ]
    ]);
}

/**
 * Gérer le marquage des messages comme lus
 * @param Message $messageModel Instance du modèle Message
 * @param int $userId ID de l'utilisateur
 */
function handleMarkAsRead($messageModel, $userId) {
    // Vérifier les paramètres requis
    if (!isset($_POST['sender_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID d\'expéditeur manquant']);
        exit;
    }
    
    $senderId = (int)$_POST['sender_id'];
    
    // Marquer les messages comme lus
    $count = $messageModel->markAsRead($senderId, $userId);
    
    echo json_encode([
        'success' => true,
        'message' => $count . ' message(s) marqué(s) comme lu(s)',
        'count' => $count
    ]);
}

/**
 * Gérer la suppression d'un message
 * @param Message $messageModel Instance du modèle Message
 * @param int $userId ID de l'utilisateur
 */
function handleDeleteMessage($messageModel, $userId) {
    // Vérifier les paramètres requis
    if (!isset($_POST['message_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de message manquant']);
        exit;
    }
    
    $messageId = (int)$_POST['message_id'];
    
    // Supprimer le message
    $result = $messageModel->delete($messageId, $userId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Message supprimé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du message']);
    }
}

/**
 * Gérer la suppression d'une conversation
 * @param Message $messageModel Instance du modèle Message
 * @param int $userId ID de l'utilisateur
 */
function handleDeleteConversation($messageModel, $userId) {
    // Vérifier les paramètres requis
    if (!isset($_POST['contact_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de contact manquant']);
        exit;
    }
    
    $contactId = (int)$_POST['contact_id'];
    
    // Supprimer la conversation
    $result = $messageModel->deleteConversation($userId, $contactId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Conversation supprimée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la conversation']);
    }
}