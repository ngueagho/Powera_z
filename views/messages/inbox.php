<?php
/**
 * Page de messagerie (boîte de réception)
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../../config/config.php');
require_once('../../models/Database.php');
require_once('../../models/User.php');
require_once('../../models/Message.php');

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || !$_SESSION[SESSION_PREFIX . 'logged_in']) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté pour accéder à la messagerie'
    ];
    
    header('Location: ../../views/auth/login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur
$userId = $_SESSION[SESSION_PREFIX . 'user_id'];

// Initialiser les modèles
$userModel = new User();
$messageModel = new Message();

// Récupérer les conversations de l'utilisateur
$conversations = $messageModel->getUserConversations($userId);

// Inclure l'en-tête
include('../../includes/header.php');

// Inclure la barre latérale si l'utilisateur est connecté
include('../../includes/sidebar.php');
?>

<div class="dashboard">
    <div class="dashboard-content">
        <div class="dashboard-header">
            <h1>Messagerie</h1>
            <p>Gérez vos conversations avec les propriétaires et locataires</p>
        </div>
        
        <div class="chat-container">
            <!-- Liste des conversations -->
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <h3>Conversations</h3>
                </div>
                
                <div class="chat-sidebar-search">
                    <input type="text" id="conversation-search" placeholder="Rechercher..." class="form-control">
                </div>
                
                <div class="chat-conversations">
                    <?php if (empty($conversations)): ?>
                        <div class="no-conversations">
                            <p>Aucune conversation pour le moment</p>
                            <a href="../../views/properties/list.php" class="btn btn-primary btn-sm">Parcourir les logements</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                            <div class="chat-conversation <?= $conversation['unread_count'] > 0 ? 'unread' : '' ?>" data-user-id="<?= $conversation['user_id'] ?>">
                                <img src="../../uploads/users/<?= $conversation['profile_pic'] ?>" alt="<?= $conversation['first_name'] ?>" class="chat-conversation-avatar" onerror="this.src='../../assets/images/default-avatar.jpg'">
                                <div class="chat-conversation-info">
                                    <div class="chat-conversation-header">
                                        <div class="chat-conversation-name"><?= $conversation['first_name'] ?> <?= $conversation['last_name'] ?></div>
                                        <div class="chat-conversation-time"><?= formatTimeAgo($conversation['last_message_time']) ?></div>
                                    </div>
                                    <div class="chat-conversation-message">
                                        <?= strlen($conversation['last_message']) > 30 ? substr($conversation['last_message'], 0, 30) . '...' : $conversation['last_message'] ?>
                                    </div>
                                </div>
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <div class="chat-conversation-unread"><?= $conversation['unread_count'] ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Contenu de la conversation -->
            <div class="chat-main">
                <div class="chat-welcome">
                    <div class="chat-welcome-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2>Bienvenue dans votre messagerie</h2>
                    <p>Sélectionnez une conversation pour commencer à discuter</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-container {
    display: flex;
    height: 700px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.chat-sidebar {
    width: 300px;
    border-right: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
}

.chat-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
}

.chat-sidebar-header h3 {
    margin-bottom: 0;
    font-size: 1.125rem;
}

.chat-sidebar-search {
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
}

.chat-conversations {
    flex: 1;
    overflow-y: auto;
}

.chat-conversation {
    display: flex;
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
    cursor: pointer;
    transition: background-color 0.3s ease;
    position: relative;
}

.chat-conversation:hover {
    background-color: #f8f9fa;
}

.chat-conversation.active {
    background-color: #e8f0fe;
}

.chat-conversation.unread {
    background-color: #f0f7ff;
}

.chat-conversation-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 15px;
    object-fit: cover;
}

.chat-conversation-info {
    flex: 1;
    min-width: 0;
}

.chat-conversation-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.chat-conversation-name {
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-conversation-time {
    font-size: 0.75rem;
    color: #6c757d;
}

.chat-conversation-message {
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-conversation-unread {
    min-width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #4a6ee0;
    color: white;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 15px;
    right: 15px;
}

.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-welcome {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    text-align: center;
    color: #6c757d;
}

.chat-welcome-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #dee2e6;
}

.no-conversations {
    padding: 30px;
    text-align: center;
    color: #6c757d;
}

.no-conversations p {
    margin-bottom: 15px;
}

/* Responsif pour les petits écrans */
@media (max-width: 768px) {
    .chat-container {
        flex-direction: column;
        height: auto;
    }
    
    .chat-sidebar {
        width: 100%;
        height: 300px;
    }
    
    .chat-main {
        height: 400px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments de la page
    const conversations = document.querySelectorAll('.chat-conversation');
    const chatMain = document.querySelector('.chat-main');
    const searchInput = document.getElementById('conversation-search');
    
    // Cliquer sur une conversation
    conversations.forEach(conversation => {
        conversation.addEventListener('click', function() {
            // Récupérer l'ID de l'utilisateur destinataire
            const userId = this.getAttribute('data-user-id');
            
            // Retirer la classe active des autres conversations
            conversations.forEach(conv => conv.classList.remove('active'));
            
            // Ajouter la classe active à cette conversation
            this.classList.add('active');
            
            // Supprimer la classe unread
            this.classList.remove('unread');
            const unreadBadge = this.querySelector('.chat-conversation-unread');
            if (unreadBadge) {
                unreadBadge.remove();
            }
            
            // Charger la conversation
            loadConversation(userId);
        });
    });
    
    // Recherche dans les conversations
    searchInput.addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        
        conversations.forEach(conversation => {
            const name = conversation.querySelector('.chat-conversation-name').textContent.toLowerCase();
            const message = conversation.querySelector('.chat-conversation-message').textContent.toLowerCase();
            
            if (name.includes(searchValue) || message.includes(searchValue)) {
                conversation.style.display = 'flex';
            } else {
                conversation.style.display = 'none';
            }
        });
    });
    
    // Fonction pour charger une conversation
    function loadConversation(userId) {
        // Afficher un chargement
        chatMain.innerHTML = '<div class="loading-container"><div class="loading loading-lg"></div><p>Chargement de la conversation...</p></div>';
        
        // Rediriger vers la page de chat
        window.location.href = 'chat.php?user_id=' + userId;
    }
    
    // Fonction pour rafraîchir les conversations
    function refreshConversations() {
        fetch('../../api/messages.php?action=get_conversations')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour le nombre de messages non lus dans le badge du menu
                    const unreadMessagesCount = data.unread_count;
                    const unreadBadge = document.querySelector('.dashboard-nav .badge');
                    
                    if (unreadMessagesCount > 0) {
                        if (unreadBadge) {
                            unreadBadge.textContent = unreadMessagesCount;
                        } else {
                            const messagesLink = document.querySelector('.dashboard-nav a[href*="messages"]');
                            if (messagesLink) {
                                const badge = document.createElement('span');
                                badge.className = 'badge';
                                badge.textContent = unreadMessagesCount;
                                messagesLink.appendChild(badge);
                            }
                        }
                    } else if (unreadBadge) {
                        unreadBadge.remove();
                    }
                    
                    // Vérifier s'il y a de nouveaux messages
                    if (data.new_messages) {
                        // Recharger la page pour voir les nouveaux messages
                        window.location.reload();
                    }
                }
            })
            .catch(error => console.error('Erreur lors du rafraîchissement des conversations:', error));
    }
    
    // Rafraîchir les conversations toutes les 30 secondes
    setInterval(refreshConversations, 30000);
});

/**
 * Formater un timestamp en temps écoulé
 * @param {string} timestamp Timestamp à formater
 * @return {string} Temps écoulé formaté
 */
function formatTimeAgo(timestamp) {
    const now = new Date();
    const date = new Date(timestamp);
    const diff = Math.floor((now - date) / 1000); // Différence en secondes
    
    if (diff < 60) {
        return 'À l\'instant';
    } else if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        return `Il y a ${minutes} min`;
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return `Il y a ${hours}h`;
    } else if (diff < 604800) {
        const days = Math.floor(diff / 86400);
        return `Il y a ${days}j`;
    } else {
        return date.toLocaleDateString('fr-FR');
    }
}
</script>

<?php
// Fonction pour formater le temps écoulé
function formatTimeAgo($timestamp) {
    $now = new DateTime();
    $date = new DateTime($timestamp);
    $diff = $now->getTimestamp() - $date->getTimestamp();
    
    if ($diff < 60) {
        return "À l'instant";
    } else if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Il y a " . $minutes . " min";
    } else if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Il y a " . $hours . "h";
    } else if ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Il y a " . $days . "j";
    } else {
        return $date->format('d/m/Y');
    }
}

// Inclure le pied de page
include('../../includes/footer.php');
?>