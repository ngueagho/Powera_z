<!-- Avis -->
<div class="property-section" id="reviews">
                    <h2>Avis (<?= $reviewCount ?>)</h2>
                    
                    <?php if ($reviewCount > 0): ?>
                        <div class="property-reviews">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review">
                                    <div class="review-header">
                                        <div class="review-author">
                                            <img src="../../uploads/users/<?= $review['profile_pic'] ?>" alt="<?= $review['first_name'] ?>">
                                            <div>
                                                <h4><?= $review['first_name'] ?> <?= substr($review['last_name'], 0, 1) ?>.</h4>
                                                <div class="review-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="review-date">
                                            <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <?= nl2br($review['comment']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Aucun avis pour le moment.</p>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION[SESSION_PREFIX . 'logged_in']) && $_SESSION[SESSION_PREFIX . 'logged_in'] && $_SESSION[SESSION_PREFIX . 'user_role'] === 'tenant'): ?>
                        <div class="add-review-section">
                            <h3>Ajouter un avis</h3>
                            <form id="review-form" action="../../api/reviews.php" method="POST" data-ajax="true">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="property_id" value="<?= $propertyId ?>">
                                
                                <div class="form-group">
                                    <label for="rating" class="form-label">Note</label>
                                    <div class="rating-select">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="far fa-star rate-star" data-rating="<?= $i ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="rating" id="rating" value="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comment" class="form-label">Commentaire</label>
                                    <textarea id="comment" name="comment" class="form-control" rows="4" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Publier mon avis</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Propriétés similaires -->
                <div class="property-section">
                    <h2>Logements similaires</h2>
                    <div class="property-grid" id="similar-properties">
                        <!-- Chargé via JavaScript -->
                        <div class="loading-container">
                            <div class="loading loading-lg"></div>
                            <p>Chargement des logements similaires...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de réservation -->
<?php if (isset($_SESSION[SESSION_PREFIX . 'logged_in']) && $_SESSION[SESSION_PREFIX . 'logged_in'] && $_SESSION[SESSION_PREFIX . 'user_role'] === 'tenant'): ?>
    <div id="booking-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Réserver ce logement</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="booking-form" action="../../api/bookings.php" method="POST" data-ajax="true">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="property_id" value="<?= $propertyId ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="check_in" class="form-label">Date d'arrivée</label>
                            <input type="date" id="check_in" name="check_in" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="check_out" class="form-label">Date de départ</label>
                            <input type="date" id="check_out" name="check_out" class="form-control" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="guests" class="form-label">Nombre de personnes</label>
                        <select id="guests" name="guests" class="form-control" required>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="booking-summary">
                        <h4>Récapitulatif</h4>
                        <div class="booking-summary-row">
                            <span>Prix mensuel</span>
                            <span><?= number_format($property['price'], 0, ',', ' ') ?> FCFA</span>
                        </div>
                        <div class="booking-summary-row">
                            <span>Durée</span>
                            <span id="booking-duration">-</span>
                        </div>
                        <div class="booking-summary-row total">
                            <span>Total</span>
                            <span id="booking-total">-</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Réserver maintenant</button>
                    </div>
                </form>
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
<?php endif; ?>

<style>
/* Styles spécifiques à la page de détail */
.property-gallery {
    position: relative;
    height: 500px;
    background-color: #f8f9fa;
    overflow: hidden;
}

.property-main-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.property-thumbnails {
    position: absolute;
    bottom: 20px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 10px;
    padding: 0 20px;
}

.property-thumbnail {
    width: 80px;
    height: 60px;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid white;
    object-fit: cover;
    transition: all 0.3s ease;
}

.property-thumbnail.active {
    border-color: #4a6ee0;
}

.property-thumbnail:hover {
    transform: scale(1.1);
}

.property-features {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.property-feature {
    flex: 1;
    min-width: 150px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.property-feature i {
    font-size: 24px;
    color: #4a6ee0;
    margin-bottom: 10px;
}

.property-feature span {
    display: block;
    color: #6c757d;
    margin-bottom: 5px;
}

.property-feature strong {
    font-size: 1.125rem;
}

.property-videos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.property-video video {
    width: 100%;
    border-radius: 8px;
}

.rating-select {
    display: flex;
    gap: 5px;
    font-size: 24px;
    color: #ffc107;
    margin-bottom: 10px;
}

.rating-select .fa-star {
    cursor: pointer;
}

.booking-summary {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
}

.booking-summary h4 {
    margin-bottom: 10px;
}

.booking-summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.booking-summary-row.total {
    border-top: 1px solid #dee2e6;
    padding-top: 10px;
    font-weight: bold;
    font-size: 1.125rem;
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
</style>

<script src="../../assets/js/map.js"></script>
<script src="../../assets/js/webrtc.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la carte
    <?php if ($property['latitude'] && $property['longitude']): ?>
        const propertyMap = initPropertyMap('property-map', {
            latitude: <?= $property['latitude'] ?>,
            longitude: <?= $property['longitude'] ?>,
            title: "<?= addslashes($property['title']) ?>",
            address: "<?= addslashes($property['address']) ?>",
            city: "<?= addslashes($property['city']) ?>"
        });
    <?php endif; ?>

    // Changement d'image principale
    window.changeMainImage = function(thumbnail, src) {
        document.querySelectorAll('.property-thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        thumbnail.classList.add('active');
        document.getElementById('main-image').src = src;
    };

    // Sélection de note
    const rateStars = document.querySelectorAll('.rate-star');
    if (rateStars.length > 0) {
        rateStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                document.getElementById('rating').value = rating;
                
                // Mettre à jour l'affichage des étoiles
                rateStars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                
                // Mettre à jour l'affichage des étoiles au survol
                rateStars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
            
            star.addEventListener('mouseout', function() {
                const currentRating = parseInt(document.getElementById('rating').value);
                
                // Restaurer l'affichage des étoiles
                rateStars.forEach((s, index) => {
                    if (index < currentRating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });
    }

    // Formulaire de réservation
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    
    if (checkInInput && checkOutInput) {
        function updateBookingSummary() {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            
            if (isNaN(checkIn.getTime()) || isNaN(checkOut.getTime())) {
                return;
            }
            
            // Calculer la durée en mois
            const monthsDiff = (checkOut.getFullYear() - checkIn.getFullYear()) * 12 + 
                               (checkOut.getMonth() - checkIn.getMonth());
            
            // Si la date de départ est après le même jour du mois suivant, ajouter un mois
            const dayDiff = checkOut.getDate() - checkIn.getDate();
            const durationMonths = monthsDiff + (dayDiff > 0 ? 1 : 0);
            
            // Afficher la durée
            document.getElementById('booking-duration').textContent = 
                durationMonths + (durationMonths > 1 ? ' mois' : ' mois');
            
            // Calculer le total
            const total = <?= $property['price'] ?> * durationMonths;
            document.getElementById('booking-total').textContent = formatPrice(total) + ' €';
        }
        
        checkInInput.addEventListener('change', function() {
            // Mettre à jour la date minimale de départ
            const checkInDate = new Date(this.value);
            const minCheckOutDate = new Date(checkInDate);
            minCheckOutDate.setDate(minCheckOutDate.getDate() + 1);
            
            checkOutInput.min = minCheckOutDate.toISOString().split('T')[0];
            
            // Si la date de départ est avant la nouvelle date d'arrivée + 1, la réinitialiser
            if (checkOutInput.value && new Date(checkOutInput.value) <= checkInDate) {
                checkOutInput.value = minCheckOutDate.toISOString().split('T')[0];
            }
            
            updateBookingSummary();
        });
        
        checkOutInput.addEventListener('change', updateBookingSummary);
        
        // Soumettre le formulaire de réservation
        const bookingForm = document.getElementById('booking-form');
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Désactiver le bouton pendant la soumission
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="loading"></span> Traitement...';
            
            // Soumission AJAX
            fetch(this.getAttribute('action'), {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = 'Réserver maintenant';
                
                if (data.success) {
                    // Fermer la modal
                    document.getElementById('booking-modal').classList.remove('show');
                    document.body.classList.remove('modal-open');
                    
                    // Afficher un message de succès
                    showNotification(data.message, 'success');
                    
                    // Rediriger si nécessaire
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = 'Réserver maintenant';
                
                showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
            });
        });
    }

    // Charger les propriétés similaires
    loadSimilarProperties();

    // Soumettre le formulaire d'avis
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérifier si une note est sélectionnée
            const rating = document.getElementById('rating').value;
            if (rating === '0') {
                showNotification('Veuillez sélectionner une note', 'error');
                return;
            }
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Désactiver le bouton pendant la soumission
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="loading"></span> Publication...';
            
            // Soumission AJAX
            fetch(this.getAttribute('action'), {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = 'Publier mon avis';
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Recharger la page pour afficher le nouvel avis
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = 'Publier mon avis';
                
                showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
            });
        });
    }
    
    // Fonctions pour les boutons d'action
    window.contactOwner = function(ownerId) {
        window.location.href = '../messages/chat.php?user_id=' + ownerId;
    };
    
    window.startCall = function(ownerId, type) {
        // Configurer la modal d'appel
        const callModal = document.getElementById('call-modal');
        const callModalTitle = document.getElementById('call-modal-title');
        
        callModalTitle.textContent = type === 'video' ? 'Appel vidéo' : 'Appel audio';
        callModal.classList.add('show');
        document.body.classList.add('modal-open');
        
        // Initialiser les variables globales pour WebRTC
        window.currentUserId = <?= $userId ?? 0 ?>;
        window.callReceiverId = ownerId;
        window.callerName = "<?= isset($_SESSION[SESSION_PREFIX . 'user_name']) ? $_SESSION[SESSION_PREFIX . 'user_name'] : 'Utilisateur' ?>";
        window.callerAvatar = "../../uploads/users/<?= isset($_SESSION[SESSION_PREFIX . 'profile_pic']) ? $_SESSION[SESSION_PREFIX . 'profile_pic'] : 'default.jpg' ?>";
        
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
                document.body.classList.remove('modal-open');
                
                // Arrêter le chronomètre
                stopCallTimer();
            }
        );
        
        // Démarrer l'appel
        startCall(type, document.getElementById('local-video'), document.getElementById('remote-video'));
        
        // Gérer la fermeture de la modal
        const closeButton = callModal.querySelector('.modal-close');
        closeButton.addEventListener('click', function() {
            endCall();
            callModal.classList.remove('show');
            document.body.classList.remove('modal-open');
            stopCallTimer();
        });
    };
    
    window.shareProperty = function() {
        if (navigator.share) {
            navigator.share({
                title: "<?= addslashes($property['title']) ?>",
                text: "Découvrez ce logement sur HouseConnect",
                url: window.location.href
            })
            .catch(error => console.error('Erreur de partage:', error));
        } else {
            // Copier l'URL dans le presse-papier
            const dummy = document.createElement('input');
            document.body.appendChild(dummy);
            dummy.value = window.location.href;
            dummy.select();
            document.execCommand('copy');
            document.body.removeChild(dummy);
            
            showNotification('Lien copié dans le presse-papier !', 'success');
        }
    };
    
    // Ajouter aux favoris
    const favButton = document.querySelector('.add-to-favorites');
    if (favButton) {
        favButton.addEventListener('click', function() {
            const propertyId = this.getAttribute('data-property-id');
            
            fetch('../../api/favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'toggle',
                    property_id: propertyId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'icône
                    const icon = this.querySelector('i');
                    if (data.added) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.innerHTML = '<i class="fas fa-heart"></i> Retirer des favoris';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.innerHTML = '<i class="far fa-heart"></i> Ajouter aux favoris';
                    }
                    
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
            });
        });
    }
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
    if (timeElement) {
        timeElement.textContent = `${padZero(minutes)}:${padZero(seconds)}`;
    }
}

function padZero(num) {
    return num.toString().padStart(2, '0');
}

// Charger les propriétés similaires
function loadSimilarProperties() {
    fetch('../../api/properties.php?property_type=<?= $property['property_type'] ?>&city=<?= urlencode($property['city']) ?>&limit=4&exclude=<?= $propertyId ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const container = document.getElementById('similar-properties');
                container.innerHTML = '';
                
                data.data.forEach(property => {
                    const propertyCard = createPropertyCard(property);
                    container.appendChild(propertyCard);
                });
            } else {
                document.getElementById('similar-properties').innerHTML = '<p>Aucun logement similaire trouvé.</p>';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('similar-properties').innerHTML = '<p>Erreur lors du chargement des logements similaires.</p>';
        });
}

// Créer une carte de propriété
function createPropertyCard(property) {
    const propertyTypes = {
        'apartment': 'Appartement',
        'house': 'Maison',
        'villa': 'Villa',
        'studio': 'Studio',
        'room': 'Chambre'
    };
    
    const card = document.createElement('div');
    card.className = 'property-card';
    
    // Image principale ou image par défaut
    let mainImage = '../../assets/images/no-image.jpg';
    if (property.images && property.images.length > 0) {
        mainImage = `../../uploads/properties/${property.images[0].image_path}`;
    }
    
    card.innerHTML = `
        <div class="property-image">
            <img src="${mainImage}" alt="${property.title}">
            <span class="property-price">${formatPrice(property.price)} FCFA</span>
        </div>
        <div class="property-details">
            <h3>${property.title}</h3>
            <p class="property-location"><i class="fa fa-map-marker-alt"></i> ${property.city}</p>
            <div class="property-info">
                <span><i class="fa fa-home"></i> ${propertyTypes[property.property_type] || property.property_type}</span>
                <span><i class="fa fa-bed"></i> ${property.rooms} pièces</span>
                <span><i class="fa fa-bath"></i> ${property.bathrooms} SDB</span>
                <span><i class="fa fa-expand"></i> ${property.surface} m²</span>
            </div>
            <a href="detail.php?id=${property.id}" class="btn btn-secondary">Voir détails</a>
        </div>
    `;
    
    return card;
}

// Formater un prix
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR').format(price);
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
</script>

<?php
// Inclure le pied de page
include('../../includes/footer.php');
?><?php
/**
 * Page de détail d'une propriété
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../../config/config.php');
require_once('../../models/Database.php');
require_once('../../models/Property.php');
require_once('../../models/User.php');
require_once('../../models/Review.php');
require_once('../../models/Booking.php');

// Démarrer la session
session_start();

// Vérifier si l'ID de la propriété est spécifié
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$propertyId = (int)$_GET['id'];

// Initialiser les modèles
$propertyModel = new Property();
$userModel = new User();
$reviewModel = new Review();
$bookingModel = new Booking();

// Récupérer les détails de la propriété
$property = $propertyModel->getDetailedById($propertyId);

// Rediriger si la propriété n'existe pas ou n'est pas disponible
if (!$property || $property['status'] !== 'available') {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Ce logement n\'est pas disponible actuellement.'
    ];
    header('Location: list.php');
    exit;
}

// Enregistrer la vue
$userId = isset($_SESSION[SESSION_PREFIX . 'user_id']) ? $_SESSION[SESSION_PREFIX . 'user_id'] : null;
$propertyModel->recordView($propertyId, $userId, $_SERVER['REMOTE_ADDR']);

// Récupérer les avis
$reviews = $reviewModel->getPropertyReviews($propertyId);

// Calculer la note moyenne
$averageRating = 0;
$reviewCount = count($reviews);
if ($reviewCount > 0) {
    $totalRating = 0;
    foreach ($reviews as $review) {
        $totalRating += $review['rating'];
    }
    $averageRating = round($totalRating / $reviewCount, 1);
}

// Types de propriétés
$propertyTypes = [
    'apartment' => 'Appartement',
    'house' => 'Maison',
    'villa' => 'Villa',
    'studio' => 'Studio',
    'room' => 'Chambre'
];

// Inclure l'en-tête
include('../../includes/header.php');
?>

<section class="property-detail-section">
    <div class="container">
        <div class="property-detail">
            <!-- Galerie d'images -->
            <div class="property-gallery">
                <?php if (!empty($property['images'])): ?>
                    <img src="../../uploads/properties/<?= $property['images'][0]['image_path'] ?>" alt="<?= $property['title'] ?>" class="property-main-image" id="main-image">
                    
                    <?php if (count($property['images']) > 1): ?>
                        <div class="property-thumbnails">
                            <?php foreach ($property['images'] as $index => $image): ?>
                                <img src="../../uploads/properties/<?= $image['image_path'] ?>" alt="Thumbnail <?= $index + 1 ?>" class="property-thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="changeMainImage(this, '../../uploads/properties/<?= $image['image_path'] ?>')">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <img src="../../assets/images/no-image.jpg" alt="<?= $property['title'] ?>" class="property-main-image">
                <?php endif; ?>
            </div>
            
            <!-- En-tête de la propriété -->
            <div class="property-header">
                <div class="property-title">
                    <h1><?= $property['title'] ?></h1>
                    <p class="property-address"><i class="fas fa-map-marker-alt"></i> <?= $property['address'] ?>, <?= $property['city'] ?></p>
                    
                    <?php if ($reviewCount > 0): ?>
                        <div class="property-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $averageRating): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i - 0.5 <= $averageRating): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span>(<?= $reviewCount ?> avis)</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="property-price-info">
                    <span class="property-price"><?= number_format($property['price'], 0, ',', ' ') ?> FCFA</span> / mois
                </div>
                
                <div class="property-actions">
                    <?php if (isset($_SESSION[SESSION_PREFIX . 'logged_in']) && $_SESSION[SESSION_PREFIX . 'logged_in']): ?>
                        <?php if ($_SESSION[SESSION_PREFIX . 'user_role'] === 'tenant'): ?>
                            <button class="btn btn-primary" data-modal="booking-modal">Réserver</button>
                            <button class="btn btn-outline" onclick="contactOwner(<?= $property['owner_id'] ?>)"><i class="fas fa-comments"></i> Contacter le propriétaire</button>
                            <button class="btn btn-outline add-to-favorites" data-property-id="<?= $propertyId ?>"><i class="far fa-heart"></i> Ajouter aux favoris</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="../../views/auth/login.php" class="btn btn-primary">Connectez-vous pour réserver</a>
                    <?php endif; ?>
                    <button class="btn btn-outline" onclick="shareProperty()"><i class="fas fa-share-alt"></i> Partager</button>
                </div>
            </div>
            
            <!-- Corps de la propriété -->
            <div class="property-body">
                <!-- Caractéristiques principales -->
                <div class="property-section">
                    <h2>Caractéristiques</h2>
                    <div class="property-features">
                        <div class="property-feature">
                            <i class="fas fa-home"></i>
                            <span>Type</span>
                            <strong><?= $propertyTypes[$property['property_type']] ?? $property['property_type'] ?></strong>
                        </div>
                        <div class="property-feature">
                            <i class="fas fa-expand"></i>
                            <span>Surface</span>
                            <strong><?= $property['surface'] ?> m²</strong>
                        </div>
                        <div class="property-feature">
                            <i class="fas fa-bed"></i>
                            <span>Pièces</span>
                            <strong><?= $property['rooms'] ?></strong>
                        </div>
                        <div class="property-feature">
                            <i class="fas fa-bath"></i>
                            <span>Salles de bain</span>
                            <strong><?= $property['bathrooms'] ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="property-section">
                    <h2>Description</h2>
                    <div class="property-description">
                        <?= nl2br($property['description']) ?>
                    </div>
                </div>
                
                <!-- Commodités -->
                <?php if (!empty($property['amenities'])): ?>
                    <div class="property-section">
                        <h2>Commodités</h2>
                        <div class="property-amenities">
                            <?php foreach ($property['amenities'] as $amenity): ?>
                                <div class="property-amenity">
                                    <i class="fas fa-<?= $amenity['icon'] ?>"></i> <?= $amenity['name'] ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Carte -->
                <?php if ($property['latitude'] && $property['longitude']): ?>
                    <div class="property-section">
                        <h2>Emplacement</h2>
                        <div id="property-map" class="property-map"></div>
                    </div>
                <?php endif; ?>
                
                <!-- Disponibilité -->
                <div class="property-section">
                    <h2>Disponibilité</h2>
                    <div id="property-calendar" class="property-calendar"></div>
                </div>
                
                <!-- Vidéos -->
                <?php if (!empty($property['videos'])): ?>
                    <div class="property-section">
                        <h2>Vidéos</h2>
                        <div class="property-videos">
                            <?php foreach ($property['videos'] as $video): ?>
                                <div class="property-video">
                                    <video controls>
                                        <source src="../../uploads/properties/<?= $video['video_path'] ?>" type="video/mp4">
                                        Votre navigateur ne supporte pas les vidéos HTML5.
                                    </video>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Propriétaire -->
                <div class="property-section">
                    <h2>À propos du propriétaire</h2>
                    <div class="property-owner">
                        <img src="../../uploads/users/<?= $property['owner']['profile_pic'] ?>" alt="<?= $property['owner']['first_name'] ?>" class="property-owner-image">
                        <div class="property-owner-info">
                            <h3><?= $property['owner']['first_name'] ?> <?= substr($property['owner']['last_name'], 0, 1) ?>.</h3>
                            <p>Membre depuis <?= date('F Y', strtotime($property['owner']['created_at'])) ?></p>
                            
                            <?php if (isset($_SESSION[SESSION_PREFIX . 'logged_in']) && $_SESSION[SESSION_PREFIX . 'logged_in'] && $_SESSION[SESSION_PREFIX . 'user_role'] === 'tenant'): ?>
                                <button class="btn btn-primary" onclick="contactOwner(<?= $property['owner_id'] ?>)"><i class="fas fa-comments"></i> Contacter</button>
                                <button class="btn btn-outline" onclick="startCall(<?= $property['owner_id'] ?>, 'audio')"><i class="fas fa-phone"></i> Appeler</button>
                                <button class="btn btn-outline" onclick="startCall(<?= $property['owner_id'] ?>, 'video')"><i class="fas fa-video"></i> Vidéo</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Avis -->
                <div class="