<?php
/**
 * Tableau de bord propriétaire
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../../../config/config.php');
require_once('../../../models/Database.php');
require_once('../../../models/User.php');
require_once('../../../models/Property.php');
require_once('../../../models/Booking.php');
require_once('../../../models/Message.php');

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || 
    !$_SESSION[SESSION_PREFIX . 'logged_in'] || 
    $_SESSION[SESSION_PREFIX . 'user_role'] !== 'owner') {
    
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté en tant que propriétaire pour accéder à cette page'
    ];
    
    header('Location: ../../../views/auth/login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur
$userId = $_SESSION[SESSION_PREFIX . 'user_id'];

// Initialiser les modèles
$userModel = new User();
$propertyModel = new Property();
$bookingModel = new Booking();
$messageModel = new Message();

// Récupérer les informations de l'utilisateur
$user = $userModel->getById($userId);

// Récupérer les propriétés de l'utilisateur
$properties = $propertyModel->getAll(['owner_id' => $userId]);

// Récupérer les dernières réservations
$bookings = $bookingModel->getByOwnerId($userId);

// Récupérer les statistiques de réservation
$bookingStats = $bookingModel->getOwnerStats($userId);

// Récupérer les derniers messages non lus
$unreadMessages = $messageModel->getUnreadMessages($userId);

// Inclure l'en-tête
include('../../../includes/header.php');

// Inclure la barre latérale du tableau de bord
include('../../../includes/sidebar.php');
?>

<div class="dashboard">
    <div class="dashboard-content">
        <div class="dashboard-header">
            <h1>Tableau de bord Propriétaire</h1>
            <p>Bienvenue, <?= $user['first_name'] ?> ! Gérez vos propriétés et réservations.</p>
        </div>

        <!-- Cartes statistiques -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Propriétés</h3>
                    <div class="dashboard-card-icon">
                        <i class="fas fa-home"></i>
                    </div>
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-card-value"><?= count($properties) ?></div>
                    <div class="dashboard-card-label">Logements publiés</div>
                </div>
                <div class="dashboard-card-footer">
                    <a href="../../../views/properties/add.php" class="btn btn-sm btn-outline">Ajouter un logement</a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Réservations</h3>
                    <div class="dashboard-card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-card-value"><?= $bookingStats['total_bookings'] ?></div>
                    <div class="dashboard-card-label">Réservations totales</div>
                </div>
                <div class="dashboard-card-footer">
                    <a href="bookings.php" class="btn btn-sm btn-outline">Voir toutes</a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Revenu</h3>
                    <div class="dashboard-card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-card-value"><?= number_format($bookingStats['total_confirmed_amount'], 0, ',', ' ') ?> FCFA</div>
                    <div class="dashboard-card-label">Réservations confirmées</div>
                </div>
                <div class="dashboard-card-footer">
                    <a href="earnings.php" class="btn btn-sm btn-outline">Voir les détails</a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Messages</h3>
                    <div class="dashboard-card-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-card-value"><?= count($unreadMessages) ?></div>
                    <div class="dashboard-card-label">Messages non lus</div>
                </div>
                <div class="dashboard-card-footer">
                    <a href="../../../views/messages/inbox.php" class="btn btn-sm btn-outline">Voir la messagerie</a>
                </div>
            </div>
        </div>

        <!-- Graphique des réservations -->
        <div class="dashboard-row">
            <div class="dashboard-card dashboard-chart">
                <div class="dashboard-card-header">
                    <h3>Réservations mensuelles</h3>
                </div>
                <div class="dashboard-card-body">
                    <canvas id="bookings-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Dernières réservations -->
        <div class="dashboard-row">
            <div class="dashboard-table">
                <div class="dashboard-card-header">
                    <h3>Dernières réservations</h3>
                    <a href="bookings.php" class="btn btn-sm btn-outline">Voir toutes</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Locataire</th>
                            <th>Logement</th>
                            <th>Dates</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Aucune réservation pour le moment</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
                                <tr>
                                    <td>#<?= $booking['id'] ?></td>
                                    <td><?= $booking['first_name'] ?> <?= $booking['last_name'] ?></td>
                                    <td><?= $booking['title'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($booking['check_in'])) ?> - <?= date('d/m/Y', strtotime($booking['check_out'])) ?></td>
                                    <td><?= number_format($booking['total_price'], 0, ',', ' ') ?> FCFA</td>
                                    <td><span class="status <?= strtolower($booking['status']) ?>"><?= $booking['status'] ?></span></td>
                                    <td>
                                        <a href="booking-detail.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline">Détails</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mes logements -->
        <div class="dashboard-row">
            <div class="dashboard-table">
                <div class="dashboard-card-header">
                    <h3>Mes logements</h3>
                    <a href="../../../views/properties/add.php" class="btn btn-sm btn-primary">Ajouter un logement</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Vues</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($properties)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Vous n'avez pas encore ajouté de logement</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $propertyTypes = [
                                'apartment' => 'Appartement',
                                'house' => 'Maison',
                                'villa' => 'Villa',
                                'studio' => 'Studio',
                                'room' => 'Chambre'
                            ];
                            
                            foreach ($properties as $property): 
                                // Récupérer l'image principale
                                $images = $propertyModel->getPropertyImages($property['id']);
                                $mainImage = !empty($images) ? '../../../uploads/properties/' . $images[0]['image_path'] : '../../../assets/images/no-image.jpg';
                                
                                // Récupérer le nombre de vues
                                $viewCount = $propertyModel->countViews($property['id']);
                            ?>
                                <tr>
                                    <td>
                                        <div class="property-thumbnail">
                                            <img src="<?= $mainImage ?>" alt="<?= $property['title'] ?>">
                                        </div>
                                    </td>
                                    <td><?= $property['title'] ?></td>
                                    <td><?= $propertyTypes[$property['property_type']] ?? $property['property_type'] ?></td>
                                    <td><?= number_format($property['price'], 0, ',', ' ') ?> FCFA</td>
                                    <td><span class="status <?= strtolower($property['status']) ?>"><?= $property['status'] ?></span></td>
                                    <td><?= $viewCount ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="../../../views/properties/detail.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline" title="Voir"><i class="fas fa-eye"></i></a>
                                            <a href="../../../views/properties/edit.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline" title="Modifier"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger" onclick="deleteProperty(<?= $property['id'] ?>)" title="Supprimer"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard {
    display: flex;
    min-height: calc(100vh - 70px);
    background-color: #f5f7fb;
}

.dashboard-content {
    flex: 1;
    padding: 30px;
}

.dashboard-header {
    margin-bottom: 30px;
}

.dashboard-header h1 {
    font-size: 1.75rem;
    margin-bottom: 10px;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dashboard-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.dashboard-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.dashboard-card-header h3 {
    margin-bottom: 0;
    font-size: 1.125rem;
}

.dashboard-card-icon {
    width: 40px;
    height: 40px;
    background-color: #e8f0fe;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4a6ee0;
}

.dashboard-card-body {
    padding: 20px;
}

.dashboard-card-value {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.dashboard-card-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.dashboard-card-footer {
    padding: 10px 20px 20px;
}

.dashboard-row {
    margin-bottom: 30px;
}

.dashboard-chart {
    height: 350px;
}

.dashboard-table {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.dashboard-table table {
    width: 100%;
    border-collapse: collapse;
}

.dashboard-table th, 
.dashboard-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.dashboard-table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.dashboard-table tbody tr:hover {
    background-color: #f8f9fa;
}

.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status.pending {
    background-color: #fff8e6;
    color: #ffc107;
}

.status.confirmed {
    background-color: #e6f7ef;
    color: #28a745;
}

.status.canceled {
    background-color: #feecf0;
    color: #dc3545;
}

.status.completed {
    background-color: #e8f0fe;
    color: #4a6ee0;
}

.status.available {
    background-color: #e6f7ef;
    color: #28a745;
}

.status.rented {
    background-color: #e8f0fe;
    color: #4a6ee0;
}

.status.maintenance {
    background-color: #fff8e6;
    color: #ffc107;
}

.status.pending_approval {
    background-color: #feecf0;
    color: #dc3545;
}

.status.inactive {
    background-color: #f0f0f0;
    color: #6c757d;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.property-thumbnail {
    width: 60px;
    height: 60px;
    border-radius: 4px;
    overflow: hidden;
}

.property-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Responsive pour les petits écrans */
@media (max-width: 768px) {
    .dashboard-cards {
        grid-template-columns: 1fr;
    }
    
    .dashboard-table {
        overflow-x: auto;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données pour le graphique des réservations mensuelles
    const bookingStats = <?= json_encode($bookingStats) ?>;
    
    // Créer un tableau des 12 derniers mois
    const months = [];
    const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    const counts = [];
    
    const now = new Date();
    for (let i = 11; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const monthYear = d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2);
        months.push(monthNames[d.getMonth()] + ' ' + d.getFullYear());
        
        // Récupérer le nombre de réservations pour ce mois
        counts.push(bookingStats.bookings_by_month[monthYear] || 0);
    }
    
    // Initialiser le graphique
    const ctx = document.getElementById('bookings-chart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Réservations',
                data: counts,
                backgroundColor: 'rgba(74, 110, 224, 0.2)',
                borderColor: '#4a6ee0',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
    
    // Fonction pour supprimer une propriété
    window.deleteProperty = function(propertyId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette propriété ? Cette action est irréversible.')) {
            fetch('../../../api/properties.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: propertyId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès
                    showNotification(data.message, 'success');
                    
                    // Recharger la page après un délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
            });
        }
    };
    
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
include('../../../includes/footer.php');
?>