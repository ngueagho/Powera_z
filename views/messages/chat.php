<?php
/**
 * Page de conversation (chat)
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../../config/config.php');
require_once('../../models/Database.php');
require_once('../../models/User.php');
require_once('../../models/Message.php');
require_once('../../models/Property.php');

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

// Vérifier si l'ID de l'utilisateur cible est fourni
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header('Location: inbox.php');
    exit;
}

// Récupérer les IDs des utilisateurs
$currentUserId = $_SESSION[SESSION_PREFIX . 'user_id'];
$targetUserId = (int)$_GET['user_id'];

// Récupérer l'ID de la propriété (optionnel)
$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : null;

// Initialiser les modèles
$userModel = new User();
$messageModel = new Message();
$propertyModel = new Property();

// Récupérer les informations des utilisateurs
$currentUser = $userModel->getById($currentUserId);
$targetUser = $userModel->getById($targetUserId);

// Rediriger si l'utilisateur cible n'existe pas
if (!$targetUser) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Cet utilisateur n\'existe pas'
    ];
    
    header('Location: inbox.php');
    exit;
}

// Récupérer la propriété si spécifiée
$property = null;
if ($propertyId) {
    $property = $propertyModel->getById($propertyId);
}

// Récupérer l'historique des messages
$messages = $messageModel->getConversation($currentUserId, $targetUserId);

// Marquer tous les messages de cette conversation comme lus
$messageModel->markAsRead($targetUserId, $currentUserId);

// Inclure l'en-tête
include('../../includes/header.php');

// Inclure la barre latérale si l'utilisateur est connecté
include('../../includes/sidebar.php');
?>

<div class="dashboard">
    <div class="dashboard-content">
        <div class="dashboard-header">
            <h1>Conversation avec <?= $targetUser['first_name'] ?> <?= $targetUser['last_name'] ?></h1>
            <a href="inbox.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Retour à la boîte de réception</a>
        </div>
        
        <div class="chat-container">
            <!-- En-tête du chat -->
            <div class="chat-header">
                <img src="../../uploads/users/<?= $targetUser['profile_pic'] ?>" alt="<?= $targetUser['first_name'] ?>" class="chat-header-avatar" onerror="this.src='../../assets/images/default-avatar.jpg'">
                <div class="chat-header-info">
                    <h3><?= $targetUser['first_name'] ?> <?= $targetUser['last_name'] ?></h3>
                    <p><?= ucfirst($targetUser['role']) ?></p>
                </div>
                <div class="chat-header-actions">
                    <button type="button" class="btn btn-outline btn-sm start-call" data-call-type="audio" title="Appel audio"><i class="fas fa-phone"></i></button>
                    <button type="button" class="btn btn-outline btn-sm start-call" data-call-type="video" title="Appel vidéo"><i class="fas fa-video"></i></button>
                </div>
            </div>
            
            <!-- Corps du chat (messages) -->
            <div class="chat-messages" id="chat-messages">
                <?php if (!empty($property)): ?>
                    <div class="chat-property-info">
                        <div class="chat-property-card">
                            <div class="chat-property-image">
                                <?php
                                // Récupérer l'image principale
                                $images = $propertyModel->getPropertyImages($property['id']);
                                $mainImage = !empty($images) ? '../../uploads/properties/' . $images[0]['image_path'] : '../../assets/images/no-image.jpg';
                                ?>
                                <img src="<?= $mainImage ?>" alt="<?= $property['title'] ?>">
                            </div>
                            <div class="chat-property-details">
                                <h4><?= $property['title'] ?></h4>
                                <p><?= $property['address'] ?>, <?= $property['city'] ?></p>
                                <p class="chat-property-price"><?= number_format($property['price'], 0, ',', ' ') ?> FCFA</p>
                                <a href="../../views/properties/detail.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline">Voir le bien</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($messages)): ?>
                    <div class="chat-no-messages">
                        <p>Aucun message pour le moment. Commencez la conversation !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="chat-message <?= $message['sender_id'] == $currentUserId ? 'outgoing' : 'incoming' ?>">
                            <img src="../../uploads/users/<?= $message['sender_id'] == $currentUserId ? $currentUser['profile_pic'] : $targetUser['profile_pic'] ?>" alt="Avatar" class="chat-message-avatar" onerror="this.src='../../assets/images/default-avatar.jpg'">
                            <div class="chat-message-content">
                                <div class="chat-message-bubble"><?= nl2br(htmlspecialchars($message['content'])) ?></div>
                                <div class="chat-message-time"><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Formulaire d'envoi de message -->
            <div class="chat-input">
                <form id="chat-form">
                    <input type="hidden" name="receiver_id" value="<?= $targetUserId ?>">
                    <?php if ($propertyId): ?>
                        <input type="hidden" name="property_id" value="<?= $propertyId ?>">
                    <?php endif; ?>
                    <div class="chat-input-container">
                        <textarea name="message" id="message" placeholder="Écrivez votre message..." required></textarea>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'appel -->
<div id="call-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="call-modal-title">Appel</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="call-container">
                <div class="video-container">
                    <video id="remote-video" autoplay playsinline></video>
                    <video id="local-video" autoplay playsinline muted></video>
                </div>
                <div class="call-controls">
                    <button id="toggle-audio" class="btn-circle"><i class="fas fa-microphone"></i></button>
                    <button id="toggle-video" class="btn-circle"><i class="fas fa-video"></i></button>
                    <button id="hangup-call" class="btn-circle btn-danger"><i class="fas fa-phone-slash"></i></button>
                </div>
                <div class="call-status">
                    <span id="call-time">00:00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-container {
    display: flex;
    flex-direction: column;
    height: 600px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.chat-header {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.chat-header-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 15px;
    object-fit: cover;
}

.chat-header-info h3 {
    margin-bottom: 0;
    font-size: 1.125rem;
}

.chat-header-info p {
    margin-bottom: 0;
    font-size: 0.875rem;
    color: #6c757d;
}

.chat-header-actions {
    margin-left: auto;
    display: flex;
    gap: 10px;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background-color: #f5f7fb;
}

.chat-message {
    display: flex;
    margin-bottom: 20px;
}

.chat-message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 15px;
    object-fit: cover;
}

.chat-message-content {
    max-width: 70%;
}

.chat-message-bubble {
    background-color: white;
    padding: 12px 15px;
    border-radius: 18px;
    border-top-left-radius: 4px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
    margin-bottom: 5px;
}

.chat-message.outgoing {
    flex-direction: row-reverse;
}

.chat-message.outgoing .chat-message-avatar {
    margin-right: 0;
    margin-left: 15px;
}

.chat-message.outgoing .chat-message-content {
    align-items: flex-end;
}

.chat-message.outgoing .chat-message-bubble {
    background-color: #4a6ee0;
    color: white;
    border-radius: 18px;
    border-top-right-radius: 4px;
}

.chat-message-time {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
}

.chat-input {
    padding: 15px 20px;
    border-top: 1px solid #dee2e6;
    background-color: white;
}

.chat-input-container {
    display: flex;
    align-items: center;
}

.chat-input textarea {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #dee2e6;
    border-radius: 25px;
    resize: none;
    height: 50px;
    max-height: 100px;
    background-color: #f8f9fa;
}

.chat-input textarea:focus {
    outline: none;
    border-color: #4a6ee0;
    background-color: white;
}

.chat-input button {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #4a6ee0;
    cursor: pointer;
    margin-left: 15px;
    transition: transform 0.3s ease;
}

.chat-input button:hover {
    transform: scale(1.1);
}

.chat-no-messages {
    text-align: center;
    padding: 50px 0;
    color: #6c757d;
}

.chat-property-info {
    margin-bottom: 30px;
}

.chat-property-card {
    display: flex;
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.chat-property-image {
    width: 120px;
    height: 120px;
    flex-shrink: 0;
}

.chat-property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-property-details {
    flex: 1;
    padding: 15px;
}

.chat-property-details h4 {
    margin-bottom: 5px;
    font-size: 1rem;
}

.chat-property-details p {
    margin-bottom: 5px;
    font-size: 0.875rem;
    color: #6c757d;
}

.chat-property-price {
    font-weight: 600;
    color: #4a6ee0;
    margin-bottom: 10px;
}

/* Modal d'appel */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background-color: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
}

.modal-header h3 {
    margin-bottom: 0;
    font-size: 1.25rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

.video-container {
    position: relative;
    height: 400px;
    background-color: #000;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}

.video-container video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#local-video {
    position: absolute;
    bottom: 20px;
    right: 20px;
    width: 150px;
    height: 100px;
    border-radius: 8px;
    border: 2px solid white;
    z-index: 1;
}

.call-controls {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 15px;
}

.btn-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #f8f9fa;
    border: none;
    font-size: 1.125rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-circle:hover {
    background-color: #e2e6ea;
}

.btn-circle.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-circle.btn-danger:hover {
    background-color: #c82333;
}

.call-status {
    text-align: center;
    font-size: 1.125rem;
    color: #6c757d;
}

/* Responsif pour les petits écrans */
@media (max-width: 768px) {
    .chat-container {
        height: 500px;
    }
    
    .chat-property-card {
        flex-direction: column;
    }
    
    .chat-property-image {
        width: 100%;
        height: 150px;
    }
    
    .chat-message-content {
        max-width: 85%;
    }
}
</style>

<script src="../../assets/js/webrtc.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du chat
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message');
    const chatMessages = document.getElementById('chat-messages');
    
    // Faire défiler vers le bas pour voir les derniers messages
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Soumettre le formulaire pour envoyer un message
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Récupérer le message
        const message = messageInput.value.trim();
        
        if (message === '') {
            return;
        }
        
        // Créer un FormData
        const formData = new FormData(this);
        formData.append('action', 'send');
        
        // Désactiver le bouton d'envoi
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        // Afficher le message localement avant confirmation du serveur
        addLocalMessage(message);
        
        // Vider le champ de message
        messageInput.value = '';
        
        // Envoyer le message au serveur
        fetch('../../api/messages.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Réactiver le bouton
            submitButton.disabled = false;
            
            if (!data.success) {
                // Afficher une erreur
                showNotification(data.message, 'error');
                
                // Restaurer le message dans le champ
                messageInput.value = message;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            
            // Réactiver le bouton
            submitButton.disabled = false;
            
            // Afficher une erreur
            showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
            
            // Restaurer le message dans le champ
            messageInput.value = message;
        });
    });
    
    // Ajouter un message local avant confirmation du serveur
    function addLocalMessage(content) {
        const messageElement = document.createElement('div');
        messageElement.className = 'chat-message outgoing temp';
        
        // Récupérer l'avatar de l'utilisateur actuel
        const currentUserAvatar = '<?= $currentUser['profile_pic'] ?>';
        const avatarSrc = currentUserAvatar ? '../../uploads/users/' + currentUserAvatar : '../../assets/images/default-avatar.jpg';
        
        messageElement.innerHTML = `
            <img src="${avatarSrc}" alt="Avatar" class="chat-message-avatar" onerror="this.src='../../assets/images/default-avatar.jpg'">
            <div class="chat-message-content">
                <div class="chat-message-bubble">${content.replace(/\n/g, '<br>')}</div>
                <div class="chat-message-time">À l'instant</div>
            </div>
        `;
        
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Ajuster automatiquement la hauteur du textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight > 100 ? 100 : this.scrollHeight) + 'px';
    });
    
    // Activer la touche Entrée pour envoyer (Shift+Entrée pour un saut de ligne)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Vérifier les nouveaux messages périodiquement
    function checkNewMessages() {
        fetch(`../../api/messages.php?action=get_messages&user_id=<?= $targetUserId ?>&last_id=${getLastMessageId()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    // Ajouter les nouveaux messages
                    data.messages.forEach(message => {
                        addServerMessage(message);
                    });
                    
                    // Faire défiler vers le bas
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(error => console.error('Erreur lors de la récupération des messages:', error));
    }
    
    // Récupérer l'ID du dernier message
    function getLastMessageId() {
        const messages = document.querySelectorAll('.chat-message:not(.temp)');
        if (messages.length > 0) {
            const lastMessage = messages[messages.length - 1];
            return lastMessage.getAttribute('data-id') || 0;
        }
        return 0;
    }
    
    // Ajouter un message reçu du serveur
    function addServerMessage(message) {
        // Vérifier si le message existe déjà
        if (document.querySelector(`.chat-message[data-id="${message.id}"]`)) {
            return;
        }
        
        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${message.sender_id == <?= $currentUserId ?> ? 'outgoing' : 'incoming'}`;
        messageElement.setAttribute('data-id', message.id);
        
        // Récupérer l'avatar
        const avatarSrc = message.sender_id == <?= $currentUserId ?> 
            ? (<?= json_encode($currentUser['profile_pic']) ?> || '../../assets/images/default-avatar.jpg')
            : (<?= json_encode($targetUser['profile_pic']) ?> || '../../assets/images/default-avatar.jpg');
        
        messageElement.innerHTML = `
            <img src="../../uploads/users/${avatarSrc}" alt="Avatar" class="chat-message-avatar" onerror="this.src='../../assets/images/default-avatar.jpg'">
            <div class="chat-message-content">
                <div class="chat-message-bubble">${message.content.replace(/\n/g, '<br>')}</div>
                <div class="chat-message-time">${formatDateTime(message.created_at)}</div>
            </div>
        `;
        
        // Supprimer les messages temporaires si c'est un message sortant
        if (message.sender_id == <?= $currentUserId ?>) {
            const tempMessages = document.querySelectorAll('.chat-message.temp');
            tempMessages.forEach(temp => temp.remove());
        }
        
        chatMessages.appendChild(messageElement);
    }
    
    // Vérifier les nouveaux messages toutes les 5 secondes
    setInterval(checkNewMessages, 5000);
    
    // Formater une date et heure
    function formatDateTime(datetime) {
        const date = new Date(datetime);
        return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    }
    
    // Boutons d'appel
    const callButtons = document.querySelectorAll('.start-call');
    const callModal = document.getElementById('call-modal');
    const callModalTitle = document.getElementById('call-modal-title');
    const closeButton = document.querySelector('.modal-close');
    const hangupButton = document.getElementById('hangup-call');
    
    // Vérifier si WebRTC est supporté
    if (checkWebRTCSupport()) {
        callButtons.forEach(button => {
            button.addEventListener('click', function() {
                const callType = this.getAttribute('data-call-type');
                startCall(callType);
            });
        });
    } else {
        // Désactiver les boutons si WebRTC n'est pas supporté
        callButtons.forEach(button => {
            button.disabled = true;
            button.title = 'Appels non supportés par votre navigateur';
        });
    }
    
    // Démarrer un appel
    function startCall(type) {
        callModalTitle.textContent = type === 'video' ? 'Appel vidéo' : 'Appel audio';
        callModal.classList.add('show');
        
        // Initialiser les variables globales pour WebRTC
        window.currentUserId = <?= $currentUserId ?>;
        window.callReceiverId = <?= $targetUserId ?>;
        window.callerName = "<?= $currentUser['first_name'] ?> <?= $currentUser['last_name'] ?>";
        window.callerAvatar = "../../uploads/users/<?= $currentUser['profile_pic'] ?>";
        
        // Initialiser l'interface d'appel
        initCallInterface(
            'local-video', 
            'remote-video', 
            null, // pas de bouton d'appel, l'appel commence immédiatement
            'hangup-call',
            'toggle-audio',
            'toggle-video',
            function(callType) {
                // Démarrer le chronomètre
                startCallTimer();
            },
            function() {
                // Fermer la modal
                callModal.classList.remove('show');
                
                // Arrêter le chronomètre
                stopCallTimer();
            }
        );
        
        // Démarrer l'appel
        startCall(type, document.getElementById('local-video'), document.getElementById('remote-video'));
    }
    
    // Fermer la modal d'appel
    closeButton.addEventListener('click', function() {
        callModal.classList.remove('show');
        endCall();
        stopCallTimer();
    });
    
    // Raccrocher l'appel
    hangupButton.addEventListener('click', function() {
        endCall();
        callModal.classList.remove('show');
        stopCallTimer();
    });
    
    // Chronomètre d'appel
    let callInterval = null;
    let callSeconds = 0;
    
    function startCallTimer() {
        callSeconds = 0;
        updateCallTime();
        
        callInterval = setInterval(function() {
            callSeconds++;
            updateCallTime();
        }, 1000);
    }
    
    function stopCallTimer() {
        if (callInterval) {
            clearInterval(callInterval);
            callInterval = null;
        }
    }
    
    function updateCallTime() {
        const minutes = Math.floor(callSeconds / 60);
        const seconds = callSeconds % 60;
        
        const timeElement = document.getElementById('call-time');
        timeElement.textContent = `${padZero(minutes)}:${padZero(seconds)}`;
    }
    
    function padZero(num) {
        return num.toString().padStart(2, '0');
    }
    
    // Afficher une notification
    function showNotification(message, type = 'info') {
        // Supprimer les anciennes notifications
        const oldNotifications = document.querySelectorAll('.notification-container');
        oldNotifications.forEach(notification => {
            notification.remove();
        });
        
        // Créer la notification
        const notification = document.createElement('div');
        notification.className = `notification-container ${type}`;
        notification.innerHTML = `
            <div class="notification notification-${type}">
                <div class="notification-content">
                    ${message}
                </div>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Afficher la notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Gestion de la fermeture
        const closeButton = notification.querySelector('.notification-close');
        closeButton.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
        
        // Auto-disparition après 5 secondes
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
});
</script>

<?php
// Inclure le pied de page
include('../../includes/footer.php');
?> {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
}

.modal-header