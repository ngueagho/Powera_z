<?php
/**
 * Classe Message
 * Gère les opérations liées aux messages
 */
class Message {
    private $db;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Envoyer un message
     * @param int $senderId ID de l'expéditeur
     * @param int $receiverId ID du destinataire
     * @param string $content Contenu du message
     * @param int|null $propertyId ID de la propriété (optionnel)
     * @return int|false ID du message ou false si échec
     */
    public function send($senderId, $receiverId, $content, $propertyId = null) {
        return $this->db->insert('messages', [
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'property_id' => $propertyId,
            'content' => $content,
            'is_read' => false
        ]);
    }
    
    /**
     * Récupérer la conversation entre deux utilisateurs
     * @param int $user1Id ID du premier utilisateur
     * @param int $user2Id ID du deuxième utilisateur
     * @param int $lastMessageId ID du dernier message récupéré (pour pagination)
     * @return array Liste des messages
     */
    public function getConversation($user1Id, $user2Id, $lastMessageId = 0) {
        $query = "
            SELECT * FROM messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            AND id > ?
            ORDER BY created_at ASC
        ";
        
        return $this->db->fetchAll($query, [$user1Id, $user2Id, $user2Id, $user1Id, $lastMessageId]);
    }
    
    /**
     * Marquer les messages comme lus
     * @param int $senderId ID de l'expéditeur
     * @param int $receiverId ID du destinataire
     * @return int Nombre de messages mis à jour
     */
    public function markAsRead($senderId, $receiverId) {
        $query = "
            UPDATE messages
            SET is_read = true
            WHERE sender_id = ? AND receiver_id = ? AND is_read = false
        ";
        
        $stmt = $this->db->query($query, [$senderId, $receiverId]);
        return $stmt->rowCount();
    }
    
    /**
     * Récupérer les conversations d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array Liste des conversations
     */
    public function getUserConversations($userId) {
        $query = "
            SELECT 
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.profile_pic,
                u.role,
                (
                    SELECT content 
                    FROM messages 
                    WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?)
                    ORDER BY created_at DESC 
                    LIMIT 1
                ) as last_message,
                (
                    SELECT created_at 
                    FROM messages 
                    WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?)
                    ORDER BY created_at DESC 
                    LIMIT 1
                ) as last_message_time,
                (
                    SELECT COUNT(*) 
                    FROM messages 
                    WHERE sender_id = u.id AND receiver_id = ? AND is_read = false
                ) as unread_count
            FROM users u
            WHERE u.id IN (
                SELECT DISTINCT 
                    CASE 
                        WHEN sender_id = ? THEN receiver_id
                        ELSE sender_id
                    END as contact_id
                FROM messages
                WHERE sender_id = ? OR receiver_id = ?
            )
            ORDER BY last_message_time DESC
        ";
        
        return $this->db->fetchAll($query, [
            $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId
        ]);
    }
    
    /**
     * Compter les messages non lus
     * @param int $userId ID de l'utilisateur
     * @return int Nombre de messages non lus
     */
    public function countUnreadMessages($userId) {
        $query = "
            SELECT COUNT(*) as count
            FROM messages
            WHERE receiver_id = ? AND is_read = false
        ";
        
        $result = $this->db->fetchOne($query, [$userId]);
        return $result['count'];
    }
    
    /**
     * Vérifier s'il y a de nouveaux messages depuis une certaine date
     * @param int $userId ID de l'utilisateur
     * @param string $timestamp Timestamp de référence
     * @return bool Vrai s'il y a de nouveaux messages
     */
    public function hasNewMessages($userId, $timestamp) {
        $query = "
            SELECT COUNT(*) as count
            FROM messages
            WHERE receiver_id = ? AND created_at > ?
        ";
        
        $result = $this->db->fetchOne($query, [$userId, $timestamp]);
        return $result['count'] > 0;
    }
    
    /**
     * Récupérer les messages non lus
     * @param int $userId ID de l'utilisateur
     * @return array Liste des messages non lus
     */
    public function getUnreadMessages($userId) {
        $query = "
            SELECT m.*, u.first_name, u.last_name, u.profile_pic
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ? AND m.is_read = false
            ORDER BY m.created_at DESC
        ";
        
        return $this->db->fetchAll($query, [$userId]);
    }
    
    /**
     * Supprimer un message
     * @param int $messageId ID du message
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès ou échec
     */
    public function delete($messageId, $userId) {
        $query = "
            DELETE FROM messages
            WHERE id = ? AND (sender_id = ? OR receiver_id = ?)
        ";
        
        $stmt = $this->db->query($query, [$messageId, $userId, $userId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Supprimer une conversation
     * @param int $userId ID de l'utilisateur
     * @param int $contactId ID du contact
     * @return bool Succès ou échec
     */
    public function deleteConversation($userId, $contactId) {
        $query = "
            DELETE FROM messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ";
        
        $stmt = $this->db->query($query, [$userId, $contactId, $contactId, $userId]);
        return $stmt->rowCount() > 0;
    }
}