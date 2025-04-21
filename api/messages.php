<?php
/**
 * API de gestion des messages
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');
require_once('../models/Message.php');

// Démarrer la session
session_start();

// Définir l'en-tête JSON pour les réponses API
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || !$_SESSION[SESSION_PREFIX . 'logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer l'ID de l'utilisateur courant
$currentUserId = $_SESSION[SESSION_PREFIX . 'user_id'];

// Initialiser les modèles
$userModel = new User();
$messageModel = new Message();

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Récupérer l'action demandée
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : null);

// Traiter en fonction de l'action
switch ($action) {
    case 'get_conversations':
        // Récupérer les conversations de l'utilisateur
        $conversations = $messageModel->getUserConversations($currentUserId);
        $unreadCount = $messageModel->countUnreadMessages($currentUserId);
        
        // Vérifier s'il y a de nouveaux messages depuis la dernière vérification
        $lastCheck = isset($_GET['last_check']) ? $_GET['last_check'] : null;
        $newMessages = false;
        
        if ($lastCheck) {
            $newMessages = $messageModel->hasNewMessages($currentUserId, $lastCheck);
        }
        
        echo json_encode([
            'success' => true,
            'conversations' => $conversations,
            'unread_count' => $unreadCount,
            'new_messages' => $newMessages
        ]);
        break;
        
    case 'get_messages':
        // Vérifier si l'ID de l'utilisateur cible est fourni
        if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID d\'utilisateur non spécifié']);
            exit;
        }
        
        $targetUserId = (int)$_GET['user_id'];
        
        // Récupérer le dernier ID de message pour pagination si fourni
        $lastMessageId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
        
        // Récupérer les messages
        $messages = $messageModel->getConversation($currentUserId, $targetUserId, $lastMessageId);
        
        // Marquer les messages comme lus
        $messageModel->markAsRead($targetUserId, $currentUserId);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        break;
        
    case 'send':
        // Vérifier les champs requis
        if (!isset($_POST['receiver_id']) || empty($_POST['receiver_id']) || !isset($_POST['message']) || empty($_POST['message'])) {
            echo json_encode(['success' => false, 'message' => 'Destinataire et message requis']);
            exit;
        }
        
        $receiverId = (int)$_POST['receiver_id'];
        $message = trim($_POST['message']);
        $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;
        
        // Vérifier si le destinataire existe
        $receiver = $userModel->getById($receiverId);
        if (!$receiver) {
            echo json_encode(['success' => false, 'message' => 'Destinataire introuvable']);
            exit;
        }
        
        // Envoyer le message
        $messageId = $messageModel->send($currentUserId, $receiverId, $message, $propertyId);
        
        if ($messageId) {
            echo json_encode([
                'success' => true,
                'message' => 'Message envoyé',
                'message_id' => $messageId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message']);
        }
        break;
        
    case 'mark_as_read':
        // Vérifier si l'ID de l'utilisateur cible est fourni
        if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID d\'utilisateur non spécifié']);
            exit;
        }
        
        $targetUserId = (int)$_POST['user_id'];
        
        // Marquer les messages comme lus
        $result = $messageModel->markAsRead($targetUserId, $currentUserId);
        
        echo json_encode([
            'success' => true,
            'count' => $result
        ]);
        break;
        
    case 'count_unread':
        // Compter les messages non lus
        $count = $messageModel->countUnreadMessages($currentUserId);
        
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}