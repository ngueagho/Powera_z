<?php
/**
 * Barre latérale du tableau de bord
 * HouseConnect - Application de location immobilière
 */

// Si la session n'est pas démarrée, la démarrer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || !$_SESSION[SESSION_PREFIX . 'logged_in']) {
    header('Location: ' . APP_URL . '/views/auth/login.php');
    exit;
}

// Récupérer le rôle et l'ID de l'utilisateur
$userRole = $_SESSION[SESSION_PREFIX . 'user_role'];
$userId = $_SESSION[SESSION_PREFIX . 'user_id'];

// Déterminer le chemin relatif vers la racine
$rootPath = '';
$currentDir = dirname($_SERVER['PHP_SELF']);
$dirCount = substr_count($currentDir, '/');
if ($dirCount > 1) {
    $rootPath = str_repeat('../', $dirCount - 1);
}

// Obtenir le chemin de la page actuelle pour déterminer l'élément actif
$currentPage = basename($_SERVER['PHP_SELF']);

// Initialiser les modèles nécessaires
require_once($rootPath . 'models/Database.php');
require_once($rootPath . 'models/User.php');
require_once($rootPath . 'models/Message.php');

$userModel = new User();
$messageModel = new Message();

// Récupérer les informations de l'utilisateur
$user = $userModel->getById($userId);

// Récupérer le nombre de messages non lus
$unreadMessagesCount = $messageModel->countUnreadMessages($userId);
?>

<div class="dashboard-sidebar">
    <div class="dashboard-user">
        <img src="<?= $rootPath ?>uploads/users/<?= $user['profile_pic'] ?>" alt="<?= $user['first_name'] ?>" onerror="this.src='<?= $rootPath ?>assets/images/default-avatar.jpg'">
        <div class="dashboard-user-info">
            <h3><?= $user['first_name'] ?> <?= $user['last_name'] ?></h3>
            <p><?= ucfirst($userRole) ?></p>
        </div>
    </div>
    
    <div class="dashboard-nav">
        <ul>
            <?php if ($userRole === 'admin'): ?>
                <!-- Menu Admin -->
                <li>
                    <a href="<?= $rootPath ?>views/admin/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/admin/users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> Utilisateurs
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/admin/properties.php" class="<?= $currentPage === 'properties.php' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Logements
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/admin/bookings.php" class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-check"></i> Réservations
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/admin/reviews.php" class="<?= $currentPage === 'reviews.php' ? 'active' : '' ?>">
                        <i class="fas fa-star"></i> Avis
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/admin/reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                        <i class="fas fa-flag"></i> Signalements
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/admin/settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i> Paramètres
                    </a>
                </li>
            <?php elseif ($userRole === 'owner'): ?>
                <!-- Menu Propriétaire -->
                <li>
                    <a href="<?= $rootPath ?>views/dashboard/owner/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/dashboard/owner/properties.php" class="<?= $currentPage === 'properties.php' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Mes logements
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/dashboard/owner/bookings.php" class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-check"></i> Réservations
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/dashboard/owner/earnings.php" class="<?= $currentPage === 'earnings.php' ? 'active' : '' ?>">
                        <i class="fas fa-money-bill-wave"></i> Revenus
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/messages/inbox.php" class="<?= $currentPage === 'inbox.php' ? 'active' : '' ?>">
                        <i class="fas fa-envelope"></i> Messages
                        <?php if ($unreadMessagesCount > 0): ?>
                            <span class="badge"><?= $unreadMessagesCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/properties/add.php">
                        <i class="fas fa-plus-circle"></i> Ajouter un logement
                    </a>
                </li>
            <?php else: ?>
                <!-- Menu Locataire -->
                <li>
                    <a href="<?= $rootPath ?>views/dashboard/tenant/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/dashboard/tenant/bookings.php" class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-check"></i> Mes réservations
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/dashboard/tenant/favorites.php" class="<?= $currentPage === 'favorites.php' ? 'active' : '' ?>">
                        <i class="fas fa-heart"></i> Favoris
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/messages/inbox.php" class="<?= $currentPage === 'inbox.php' ? 'active' : '' ?>">
                        <i class="fas fa-envelope"></i> Messages
                        <?php if ($unreadMessagesCount > 0): ?>
                            <span class="badge"><?= $unreadMessagesCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>views/dashboard/tenant/alerts.php" class="<?= $currentPage === 'alerts.php' ? 'active' : '' ?>">
                        <i class="fas fa-bell"></i> Alertes
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Commun à tous les utilisateurs -->
            <li class="divider"></li>
            <li>
                <a href="<?= $rootPath ?>views/profile/edit.php" class="<?= $currentPage === 'edit.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-edit"></i> Mon profil
                </a>
            </li>
            <li>
                <a href="<?= $rootPath ?>views/profile/security.php" class="<?= $currentPage === 'security.php' ? 'active' : '' ?>">
                    <i class="fas fa-shield-alt"></i> Sécurité
                </a>
            </li>
            <li>
                <a href="#" id="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
.dashboard-sidebar {
    width: 250px;
    background-color: #212529;
    color: #dee2e6;
    display: flex;
    flex-direction: column;
    height: 100%;
    position: sticky;
    top: 0;
    z-index: 900;
}

.dashboard-user {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.dashboard-user img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.dashboard-user-info h3 {
    color: white;
    margin-bottom: 5px;
    font-size: 1rem;
}

.dashboard-user-info p {
    margin-bottom: 0;
    font-size: 0.875rem;
    color: #adb5bd;
}

.dashboard-nav {
    flex: 1;
    overflow-y: auto;
    padding: 20px 0;
}

.dashboard-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dashboard-nav li.divider {
    height: 1px;
    background-color: rgba(255, 255, 255, 0.1);
    margin: 15px 20px;
}

.dashboard-nav a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #dee2e6;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.dashboard-nav a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
}

.dashboard-nav a.active {
    background-color: #4a6ee0;
    color: white;
    font-weight: 500;
}

.dashboard-nav i {
    margin-right: 15px;
    width: 20px;
    text-align: center;
}

.dashboard-nav .badge {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    background-color: #dc3545;
    color: white;
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 992px) {
    .dashboard-sidebar {
        width: 100%;
        position: relative;
        height: auto;
    }
    
    .dashboard {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Déconnexion
    document.getElementById('logout-link').addEventListener('click', function(e) {
        e.preventDefault();
        
        fetch('<?= $rootPath ?>controllers/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=logout'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect || '<?= $rootPath ?>';
            } else {
                alert('Erreur lors de la déconnexion. Veuillez réessayer.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
        });
    });
});
</script>