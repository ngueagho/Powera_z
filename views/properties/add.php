<?php
/**
 * Page d'ajout d'une propriété
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../../config/config.php');
require_once('../../models/Database.php');
require_once('../../models/Property.php');
require_once('../../models/User.php');

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || 
    !$_SESSION[SESSION_PREFIX . 'logged_in'] || 
    $_SESSION[SESSION_PREFIX . 'user_role'] !== 'owner') {
    
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Vous devez être connecté en tant que propriétaire pour ajouter un logement'
    ];
    
    header('Location: ../../views/auth/login.php');
    exit;
}

// Initialiser les modèles
$propertyModel = new Property();
$userModel = new User();

// Récupérer toutes les commodités
$amenities = $propertyModel->getAllAmenities();

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

<section class="add-property-section">
    <div class="container">
        <div class="add-property-header">
            <h1>Ajouter un logement</h1>
            <p>Publiez votre bien immobilier sur HouseConnect</p>
        </div>
        
        <div class="add-property-content">
            <form id="add-property-form" action="../../api/properties.php" method="POST" enctype="multipart/form-data" data-ajax="true">
                <input type="hidden" name="action" value="create">
                
                <!-- Étape 1: Informations générales -->
                <div class="form-step active" id="step-1">
                    <h2>Informations générales</h2>
                    
                    <div class="form-group">
                        <label for="title" class="form-label">Titre de l'annonce*</label>
                        <input type="text" id="title" name="title" class="form-control" required maxlength="100">
                        <small class="form-help">Un titre accrocheur (100 caractères max)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description*</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required></textarea>
                        <small class="form-help">Décrivez votre bien en détail</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="property_type" class="form-label">Type de logement*</label>
                            <select id="property_type" name="property_type" class="form-control" required>
                                <option value="">Sélectionnez...</option>
                                <?php foreach ($propertyTypes as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price" class="form-label">Prix (FCFA/mois)*</label>
                            <input type="number" id="price" name="price" class="form-control" required min="1000">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rooms" class="form-label">Nombre de pièces*</label>
                            <input type="number" id="rooms" name="rooms" class="form-control" required min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="bathrooms" class="form-label">Salles de bain*</label>
                            <input type="number" id="bathrooms" name="bathrooms" class="form-control" required min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="surface" class="form-label">Surface (m²)*</label>
                            <input type="number" id="surface" name="surface" class="form-control" required min="1" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn btn-primary next-step">Suivant</button>
                    </div>
                </div>
                
                <!-- Étape 2: Localisation -->
                <div class="form-step" id="step-2">
                    <h2>Localisation</h2>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Adresse complète*</label>
                        <input type="text" id="address" name="address" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city" class="form-label">Ville*</label>
                            <input type="text" id="city" name="city" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="postal_code" class="form-label">Code postal</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Emplacement sur la carte</label>
                        <small class="form-help">Cliquez sur la carte pour marquer l'emplacement précis</small>
                        <div id="location-map" class="location-map"></div>
                    </div>
                    
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    
                    <div class="form-group">
                        <div class="location-info">
                            <i class="fas fa-map-marker-alt"></i> <span id="location-text">Aucun emplacement sélectionné</span>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn btn-outline prev-step">Précédent</button>
                        <button type="button" class="btn btn-primary next-step">Suivant</button>
                    </div>
                </div>
                
                <!-- Étape 3: Photos et vidéos -->
                <div class="form-step" id="step-3">
                    <h2>Photos et vidéos</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Photos (max 10)*</label>
                        <div class="file-upload-container">
                            <input type="file" id="property_images" name="property_images[]" class="file-upload" accept="image/*" multiple required>
                            <label for="property_images" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Déposer vos images ici ou cliquer pour parcourir</span>
                            </label>
                        </div>
                        <small class="form-help">Formats acceptés : JPG, PNG, GIF (max 5 MB par image)</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="image-preview-container" id="image-preview-container"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Vidéo (optionnel)</label>
                        <div class="file-upload-container">
                            <input type="file" id="property_video" name="property_video" class="file-upload" accept="video/mp4,video/quicktime">
                            <label for="property_video" class="file-upload-label">
                                <i class="fas fa-video"></i>
                                <span>Ajouter une vidéo</span>
                            </label>
                        </div>
                        <small class="form-help">Format accepté : MP4 (max 50 MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="video-preview-container" id="video-preview-container"></div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn btn-outline prev-step">Précédent</button>
                        <button type="button" class="btn btn-primary next-step">Suivant</button>
                    </div>
                </div>
                
                <!-- Étape 4: Commodités et disponibilité -->
                <div class="form-step" id="step-4">
                    <h2>Commodités et disponibilité</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Commodités disponibles</label>
                        <div class="amenities-grid">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="amenity-checkbox">
                                    <input type="checkbox" id="amenity_<?= $amenity['id'] ?>" name="amenities[]" value="<?= $amenity['id'] ?>">
                                    <label for="amenity_<?= $amenity['id'] ?>">
                                        <i class="fas fa-<?= $amenity['icon'] ?>"></i> <?= $amenity['name'] ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Périodes de disponibilité</label>
                        <small class="form-help">Ajoutez les périodes pendant lesquelles votre logement est disponible</small>
                        
                        <div id="availability-container">
                            <div class="availability-row">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Date de début</label>
                                        <input type="date" name="availability_start[]" class="form-control" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Date de fin</label>
                                        <input type="date" name="availability_end[]" class="form-control" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline btn-sm" id="add-availability">
                            <i class="fas fa-plus"></i> Ajouter une période
                        </button>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn btn-outline prev-step">Précédent</button>
                        <button type="submit" class="btn btn-primary">Publier l'annonce</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.add-property-content {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin-top: 30px;
    margin-bottom: 30px;
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
}

.form-step h2 {
    margin-bottom: 30px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

.form-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.location-map {
    height: 300px;
    border-radius: 8px;
    overflow: hidden;
    margin-top: 10px;
}

.location-info {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 4px;
    margin-top: 10px;
}

.file-upload-container {
    position: relative;
    margin-top: 10px;
}

.file-upload {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 1;
}

.file-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-upload-label:hover {
    border-color: #4a6ee0;
    background-color: #e8f0fe;
}

.file-upload-label i {
    font-size: 2rem;
    margin-bottom: 10px;
    color: #6c757d;
}

.image-preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.image-preview {
    width: 100px;
    height: 100px;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.75rem;
}

.video-preview-container {
    margin-top: 15px;
}

.video-preview {
    max-width: 100%;
    border-radius: 4px;
    overflow: hidden;
}

.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.amenity-checkbox {
    display: flex;
    align-items: center;
}

.amenity-checkbox input {
    margin-right: 10px;
}

.availability-row {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    position: relative;
}

.availability-row .remove-availability {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
}
</style>

<script src="../../assets/js/map.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navigation entre les étapes
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    const formSteps = document.querySelectorAll('.form-step');
    
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Trouver l'étape active
            const activeStep = document.querySelector('.form-step.active');
            const activeIndex = Array.from(formSteps).indexOf(activeStep);
            
            // Valider l'étape active
            if (!validateStep(activeStep)) {
                return;
            }
            
            // Masquer l'étape active
            activeStep.classList.remove('active');
            
            // Afficher la prochaine étape
            formSteps[activeIndex + 1].classList.add('active');
            
            // Scroller en haut
            window.scrollTo(0, 0);
        });
    });
    
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Trouver l'étape active
            const activeStep = document.querySelector('.form-step.active');
            const activeIndex = Array.from(formSteps).indexOf(activeStep);
            
            // Masquer l'étape active
            activeStep.classList.remove('active');
            
            // Afficher l'étape précédente
            formSteps[activeIndex - 1].classList.add('active');
            
            // Scroller en haut
            window.scrollTo(0, 0);
        });
    });
    
    // Validation des étapes
    function validateStep(step) {
        const inputs = step.querySelectorAll('input[required], select[required], textarea[required]');
        let valid = true;
        
        inputs.forEach(input => {
            if (!input.value) {
                input.classList.add('is-invalid');
                valid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!valid) {
            showNotification('Veuillez remplir tous les champs obligatoires', 'error');
        }
        
        return valid;
    }
    
    // Initialiser la carte
    let map = initMap('location-map');
    let marker = null;
    
    // Géocoder l'adresse lorsqu'elle est modifiée
    const addressInput = document.getElementById('address');
    const cityInput = document.getElementById('city');
    
    addressInput.addEventListener('blur', function() {
        if (this.value && cityInput.value) {
            geocodeAddress(this.value + ', ' + cityInput.value, function(lat, lng) {
                if (lat && lng) {
                    updateMarker(lat, lng);
                }
            });
        }
    });
    
    cityInput.addEventListener('blur', function() {
        if (this.value && addressInput.value) {
            geocodeAddress(addressInput.value + ', ' + this.value, function(lat, lng) {
                if (lat && lng) {
                    updateMarker(lat, lng);
                }
            });
        }
    });
    
    // Cliquer sur la carte pour placer un marqueur
    map.on('click', function(e) {
        updateMarker(e.latlng.lat, e.latlng.lng);
        
        // Faire un géocodage inverse pour obtenir l'adresse
        reverseGeocode(e.latlng.lat, e.latlng.lng, function(address) {
            if (address) {
                document.getElementById('location-text').textContent = address;
            }
        });
    });
    
    // Mettre à jour le marqueur
    function updateMarker(lat, lng) {
        // Supprimer l'ancien marqueur
        if (marker) {
            map.removeLayer(marker);
        }
        
        // Ajouter un nouveau marqueur
        marker = L.marker([lat, lng]).addTo(map);
        
        // Centrer la carte
        map.setView([lat, lng], 15);
        
        // Mettre à jour les champs cachés
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
    }
    
    // Prévisualisation des images
    const imagesInput = document.getElementById('property_images');
    const previewContainer = document.getElementById('image-preview-container');
    
    imagesInput.addEventListener('change', function() {
        previewContainer.innerHTML = '';
        
        if (this.files.length > 10) {
            showNotification('Vous ne pouvez pas télécharger plus de 10 images', 'error');
            this.value = '';
            return;
        }
        
        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            
            // Vérifier le type et la taille du fichier
            if (!file.type.match('image.*')) {
                showNotification('Seules les images sont acceptées', 'error');
                continue;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                showNotification('La taille maximale par image est de 5 MB', 'error');
                continue;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const preview = document.createElement('div');
                preview.className = 'image-preview';
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <div class="image-preview-remove" data-index="${i}">×</div>
                    ${i === 0 ? '<div class="image-preview-main">Principale</div>' : ''}
                `;
                previewContainer.appendChild(preview);
                
                // Ajouter l'événement de suppression
                preview.querySelector('.image-preview-remove').addEventListener('click', function() {
                    // Supprimer la prévisualisation
                    this.parentNode.remove();
                    
                    // Supprimer le fichier (ne fonctionne pas directement, nécessite une solution différente)
                    // Une solution est de créer un nouveau FileList, mais c'est complexe
                });
            };
            
            reader.readAsDataURL(file);
        }
    });
    
    // Prévisualisation de la vidéo
    const videoInput = document.getElementById('property_video');
    const videoPreviewContainer = document.getElementById('video-preview-container');
    
    videoInput.addEventListener('change', function() {
        videoPreviewContainer.innerHTML = '';
        
        if (this.files.length > 0) {
            const file = this.files[0];
            
            // Vérifier le type et la taille du fichier
            if (!file.type.match('video.*')) {
                showNotification('Seules les vidéos sont acceptées', 'error');
                this.value = '';
                return;
            }
            
            if (file.size > 50 * 1024 * 1024) {
                showNotification('La taille maximale de la vidéo est de 50 MB', 'error');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const preview = document.createElement('div');
                preview.className = 'video-preview';
                preview.innerHTML = `
                    <video controls>
                        <source src="${e.target.result}" type="${file.type}">
                        Votre navigateur ne supporte pas les vidéos HTML5.
                    </video>
                    <button type="button" class="btn btn-danger btn-sm mt-2" id="remove-video">
                        <i class="fas fa-trash"></i> Supprimer la vidéo
                    </button>
                `;
                videoPreviewContainer.appendChild(preview);
                
                // Ajouter l'événement de suppression
                document.getElementById('remove-video').addEventListener('click', function() {
                    videoInput.value = '';
                    videoPreviewContainer.innerHTML = '';
                });
            };
            
            reader.readAsDataURL(file);
        }
    });
    
    // Gestion des périodes de disponibilité
    const availabilityContainer = document.getElementById('availability-container');
    const addAvailabilityButton = document.getElementById('add-availability');
    
    addAvailabilityButton.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'availability-row';
        row.innerHTML = `
            <button type="button" class="remove-availability">&times;</button>
            <div class="form-row">
                <div class="form-group">
                    <label>Date de début</label>
                    <input type="date" name="availability_start[]" class="form-control" required min="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="form-group">
                    <label>Date de fin</label>
                    <input type="date" name="availability_end[]" class="form-control" required min="${new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0]}">
                </div>
            </div>
        `;
        availabilityContainer.appendChild(row);
        
        // Ajouter l'événement de suppression
        row.querySelector('.remove-availability').addEventListener('click', function() {
            this.parentNode.remove();
        });
    });
    
    // Soumission du formulaire
    const form = document.getElementById('add-property-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Valider l'étape active
        const activeStep = document.querySelector('.form-step.active');
        if (!validateStep(activeStep)) {
            return;
        }
        
        // Créer un FormData pour l'envoi des fichiers
        const formData = new FormData(this);
        
        // Désactiver le bouton de soumission
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="loading"></span> Publication en cours...';
        
        // Envoyer les données au serveur
        fetch(this.getAttribute('action'), {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Réactiver le bouton
            submitButton.disabled = false;
            submitButton.innerHTML = 'Publier l\'annonce';
            
            if (data.success) {
                // Afficher un message de succès
                showNotification(data.message, 'success');
                
                // Rediriger vers la page de détail après un délai
                setTimeout(() => {
                    window.location.href = 'detail.php?id=' + data.data.id;
                }, 2000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            
            // Réactiver le bouton
            submitButton.disabled = false;
            submitButton.innerHTML = 'Publier l\'annonce';
            
            showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
        });
    });
});

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
?>