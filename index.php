<?php
/**
 * Page d'accueil
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('config/config.php');
require_once('models/Database.php');
require_once('models/Property.php');

// Démarrer la session
session_start();

// Initialiser les modèles
$propertyModel = new Property();

// Récupérer les propriétés mises en avant
$featuredProperties = $propertyModel->getAll([
    'featured' => true,
    'status' => 'available',
    'limit' => 6
]);

// Récupérer les propriétés récentes
$recentProperties = $propertyModel->getAll([
    'status' => 'available',
    'sort' => 'newest',
    'limit' => 8
]);

// Récupérer tous les types de propriétés
$propertyTypes = [
    'apartment' => 'Appartement',
    'house' => 'Maison',
    'villa' => 'Villa',
    'studio' => 'Studio',
    'room' => 'Chambre'
];

// Inclure l'en-tête
include('includes/header.php');
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Trouvez votre prochain logement</h1>
        <p>Des milliers de logements vous attendent</p>
        
        <!-- Formulaire de recherche -->
        <div class="search-form">
            <form action="views/properties/list.php" method="GET">
                <div class="form-group">
                    <input type="text" name="city" placeholder="Ville" class="form-control">
                </div>
                <div class="form-group">
                    <select name="property_type" class="form-control">
                        <option value="">Type de logement</option>
                        <?php foreach ($propertyTypes as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="min_price" class="form-control">
                        <option value="">Prix minimum</option>
                        <option value="25000">25 000 FCFA</option>
                        <option value="50000">50 000 FCFA</option>
                        <option value="100000">100 000 FCFA</option>
                        <option value="250000">250 000 FCFA</option>
                    </select>
                </div>
                <div class="form-group">
                    <select name="max_price" class="form-control">
                        <option value="">Prix maximum</option>
                        <option value="100000">100 000 FCFA</option>
                        <option value="250000">250 000 FCFA</option>
                        <option value="500000">500 000 FCFA</option>
                        <option value="1000000">1 000 000 FCFA</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
        </div>
    </div>
</section>

<!-- Featured Properties Section -->
<section class="featured-properties">
    <div class="container">
        <h2>Logements en vedette</h2>
        <div class="property-grid">
            <?php if (empty($featuredProperties)): ?>
                <p>Aucun logement en vedette pour le moment.</p>
            <?php else: ?>
                <?php foreach ($featuredProperties as $property): ?>
                    <div class="property-card">
                        <div class="property-image">
                            <?php
                            // Récupérer l'image principale
                            $images = $propertyModel->getPropertyImages($property['id']);
                            $mainImage = !empty($images) ? PROPERTY_UPLOADS . $images[0]['image_path'] : 'assets/images/no-image.jpg';
                            ?>
                            <img src="<?= $mainImage ?>" alt="<?= $property['title'] ?>">
                            <span class="property-price"><?= number_format($property['price'], 0, ',', ' ') ?> FCFA</span>
                        </div>
                        <div class="property-details">
                            <h3><?= $property['title'] ?></h3>
                            <p class="property-location"><i class="fa fa-map-marker"></i> <?= $property['city'] ?></p>
                            <div class="property-info">
                                <span><i class="fa fa-home"></i> <?= $propertyTypes[$property['property_type']] ?></span>
                                <span><i class="fa fa-bed"></i> <?= $property['rooms'] ?> pièces</span>
                                <span><i class="fa fa-bath"></i> <?= $property['bathrooms'] ?> SDB</span>
                                <span><i class="fa fa-expand"></i> <?= $property['surface'] ?> m²</span>
                            </div>
                            <a href="views/properties/detail.php?id=<?= $property['id'] ?>" class="btn btn-secondary">Voir détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="views/properties/list.php" class="btn btn-primary">Voir tous les logements</a>
        </div>
    </div>
</section>

<!-- Recent Properties Section -->
<section class="recent-properties">
    <div class="container">
        <h2>Logements récents</h2>
        <div class="property-grid">
            <?php if (empty($recentProperties)): ?>
                <p>Aucun logement récent pour le moment.</p>
            <?php else: ?>
                <?php foreach ($recentProperties as $property): ?>
                    <div class="property-card">
                        <div class="property-image">
                            <?php
                            // Récupérer l'image principale
                            $images = $propertyModel->getPropertyImages($property['id']);
                            $mainImage = !empty($images) ? PROPERTY_UPLOADS . $images[0]['image_path'] : 'assets/images/no-image.jpg';
                            ?>
                            <img src="<?= $mainImage ?>" alt="<?= $property['title'] ?>">
                            <span class="property-price"><?= number_format($property['price'], 0, ',', ' ') ?> €</span>
                        </div>
                        <div class="property-details">
                            <h3><?= $property['title'] ?></h3>
                            <p class="property-location"><i class="fa fa-map-marker"></i> <?= $property['city'] ?></p>
                            <div class="property-info">
                                <span><i class="fa fa-home"></i> <?= $propertyTypes[$property['property_type']] ?></span>
                                <span><i class="fa fa-bed"></i> <?= $property['rooms'] ?> pièces</span>
                                <span><i class="fa fa-bath"></i> <?= $property['bathrooms'] ?> SDB</span>
                                <span><i class="fa fa-expand"></i> <?= $property['surface'] ?> m²</span>
                            </div>
                            <a href="views/properties/detail.php?id=<?= $property['id'] ?>" class="btn btn-secondary">Voir détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works">
    <div class="container">
        <h2>Comment ça marche</h2>
        <div class="steps">
            <div class="step">
                <div class="step-icon">
                    <i class="fa fa-search"></i>
                </div>
                <h3>Recherchez</h3>
                <p>Trouvez le logement idéal parmi notre sélection de propriétés.</p>
            </div>
            <div class="step">
                <div class="step-icon">
                    <i class="fa fa-calendar"></i>
                </div>
                <h3>Réservez</h3>
                <p>Choisissez vos dates et effectuez votre réservation en ligne.</p>
            </div>
            <div class="step">
                <div class="step-icon">
                    <i class="fa fa-comments"></i>
                </div>
                <h3>Contactez</h3>
                <p>Discutez avec le propriétaire et organisez votre séjour.</p>
            </div>
            <div class="step">
                <div class="step-icon">
                    <i class="fa fa-home"></i>
                </div>
                <h3>Emménagez</h3>
                <p>Profitez de votre nouveau logement en toute sérénité.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section
<section class="testimonials">
    <div class="container">
        <h2>Ce que disent nos utilisateurs</h2>
        <div class="testimonial-slider">
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"J'ai trouvé mon appartement idéal en quelques jours grâce à HouseConnect. Le processus était simple et rapide !"</p>
                </div>
                <div class="testimonial-author">
                    <img src="assets/images/testimonial-1.jpg" alt="Sophie M.">
                    <div>
                        <h4> Magloire tallah.</h4>
                        <p>Locataire</p>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"En tant que propriétaire, je peux facilement gérer mes biens et communiquer avec mes locataires. Une plateforme vraiment complète !"</p>
                </div>
                <div class="testimonial-author">
                    <img src="assets/images/testimonial-1.jpg" alt="Thomas L.">
                    <div>
                        <h4> Lontsi Lambou.</h4>
                        <p>Propriétaire</p>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"Le système de messagerie et d'appels vidéo m'a permis de visiter plusieurs logements à distance. Vraiment pratique !"</p>
                </div>
                <div class="testimonial-author">
                    <img src="assets/images/testimonial-1.jpg" alt="Julie K.">
                    <div>
                        <h4>Julie Kamga.</h4>
                        <p>Locataire</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section> -->
<!-- testimonials.php - Improved design for testimonials section -->

<section class="testimonials-section">
    <div class="container">
        <div class="section-header">
            <h2>Ce que disent nos utilisateurs</h2>
            <p class="section-subtitle">Des témoignages authentiques de notre communauté</p>
        </div>
        
        <div class="testimonial-controls">
            <button id="prev-testimonial" class="testimonial-control-btn">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button id="next-testimonial" class="testimonial-control-btn">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <div class="testimonials-container">
            <div class="testimonials-wrapper">
                <div class="testimonial-item active">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-text">
                            J'ai trouvé mon appartement en seulement quelques jours grâce à HouseConnect. Le processus était simple et rapide !
                        </p>
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-image">
                            <img src="../assets/images/testimonials/testimonial-1.jpg" alt="Magloire Talla.">
                        </div>
                        <div class="author-info">
                            <h4>Sophie M.</h4>
                            <p>Locataire</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-item">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-text">
                            En tant que propriétaire, j'apprécie la facilité avec laquelle je peux gérer mes annonces et communiquer avec mes locataires. Une plateforme vraiment complète !
                        </p>
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-image">
                            <img src="../assets/images/testimonials/testimonial-1.jpg" alt="kamga L.">
                        </div>
                        <div class="author-info">
                            <h4>Thomas L.</h4>
                            <p>Propriétaire</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-item">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-text">
                            Grâce aux options de filtrage avancées, j'ai pu trouver exactement le type de logement que je cherchais. La possibilité de visiter virtuellement les logements à distance est vraiment pratique !
                        </p>
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-image">
                            <img src="../assets/images/testimonials/testimonial-1.jpg" alt="Julie Kegne marte.">
                        </div>
                        <div class="author-info">
                            <h4>Julie K.</h4>
                            <p>Locataire</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-item">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-text">
                            HouseConnect m'a permis de louer mon studio en moins d'une semaine ! Le système de vérification des profils me donne une vraie tranquillité d'esprit.
                        </p>
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-image">
                            <img src="../assets/images/testimonials/testimonial-1.jpg" alt="Pierre Nouanla.">
                        </div>
                        <div class="author-info">
                            <h4>Pierre D.</h4>
                            <p>Propriétaire</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="testimonial-indicators">
            <span class="testimonial-dot active" data-index="0"></span>
            <span class="testimonial-dot" data-index="1"></span>
            <span class="testimonial-dot" data-index="2"></span>
            <span class="testimonial-dot" data-index="3"></span>
        </div>
        
        <div class="testimonial-cta">
            <p>Vous avez utilisé HouseConnect ? Partagez votre expérience</p>
            <a href="contact.php?subject=testimonial" class="btn btn-primary">Laisser un avis</a>
        </div>
    </div>
</section>
<style>
/* Improved Testimonials Styles */
.testimonials-section {
    padding: 80px 0;
    background-color: #f8f9fa;
    position: relative;
    overflow: hidden;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    color: #2a4b8d;
    font-size: 2.2rem;
    margin-bottom: 15px;
    position: relative;
    display: inline-block;
}

.section-header h2:after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background-color: #3498db;
}

.section-subtitle {
    color: #666;
    font-size: 1.1rem;
    max-width: 700px;
    margin: 0 auto;
}

.testimonial-controls {
    position: absolute;
    top: 50%;
    width: 100%;
    left: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 30px;
    z-index: 2;
    pointer-events: none;
}

.testimonial-control-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #fff;
    border: 1px solid #eaeaea;
    color: #3498db;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    pointer-events: auto;
}

.testimonial-control-btn:hover {
    background-color: #3498db;
    color: white;
}

.testimonials-container {
    position: relative;
    max-width: 900px;
    margin: 0 auto;
    overflow: hidden;
}

.testimonials-wrapper {
    display: flex;
    transition: transform 0.5s ease;
}

.testimonial-item {
    flex: 0 0 100%;
    padding: 0 15px;
    opacity: 0;
    transition: opacity 0.5s ease;
    display: none;
}

.testimonial-item.active {
    opacity: 1;
    display: block;
}

.testimonial-content {
    background-color: #fff;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    position: relative;
}

.quote-icon {
    position: absolute;
    top: 20px;
    left: 20px;
    color: #f0f0f0;
    font-size: 2.5rem;
    z-index: 0;
}

.testimonial-text {
    position: relative;
    z-index: 1;
    font-size: 1.1rem;
    line-height: 1.6;
    color: #555;
    margin-bottom: 20px;
}

.testimonial-rating {
    display: flex;
    gap: 5px;
}

.testimonial-rating i {
    color: #FFD700;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 15px;
}

.author-image {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #fff;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

.author-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-info h4 {
    margin: 0 0 5px;
    color: #2a4b8d;
    font-size: 1.1rem;
}

.author-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.testimonial-indicators {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.testimonial-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
}

.testimonial-dot.active {
    background-color: #3498db;
    transform: scale(1.2);
}

.testimonial-cta {
    text-align: center;
    margin-top: 50px;
    padding: 30px;
    background-color: #e6f0ff;
    border-radius: 10px;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.testimonial-cta p {
    color: #555;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.btn-primary {
    background-color: #3498db;
    color: white;
    padding: 12px 25px;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    border: none;
}

.btn-primary:hover {
    background-color: #2980b9;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .testimonials-section {
        padding: 60px 0;
    }
    
    .section-header h2 {
        font-size: 2rem;
    }
    
    .testimonial-controls {
        padding: 0 20px;
    }
}

@media (max-width: 768px) {
    .testimonial-content {
        padding: 25px;
    }
    
    .testimonial-text {
        font-size: 1rem;
    }
    
    .author-image {
        width: 60px;
        height: 60px;
    }
}

@media (max-width: 576px) {
    .section-header h2 {
        font-size: 1.8rem;
    }
    
    .section-subtitle {
        font-size: 1rem;
    }
    
    .testimonial-control-btn {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .testimonial-content {
        padding: 20px;
    }
    
    .quote-icon {
        font-size: 2rem;
    }
    
    .testimonial-cta {
        padding: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Testimonials slider functionality
    const testimonials = document.querySelectorAll('.testimonial-item');
    const dots = document.querySelectorAll('.testimonial-dot');
    const prevBtn = document.getElementById('prev-testimonial');
    const nextBtn = document.getElementById('next-testimonial');
    
    let currentIndex = 0;
    const totalTestimonials = testimonials.length;
    
    // Function to show testimonial by index
    function showTestimonial(index) {
        // Hide all testimonials
        testimonials.forEach(testimonial => {
            testimonial.classList.remove('active');
        });
        
        // Remove active class from all dots
        dots.forEach(dot => {
            dot.classList.remove('active');
        });
        
        // Show the current testimonial and activate corresponding dot
        testimonials[index].classList.add('active');
        dots[index].classList.add('active');
    }
    
    // Next testimonial
    function nextTestimonial() {
        currentIndex = (currentIndex + 1) % totalTestimonials;
        showTestimonial(currentIndex);
    }
    
    // Previous testimonial
    function prevTestimonial() {
        currentIndex = (currentIndex - 1 + totalTestimonials) % totalTestimonials;
        showTestimonial(currentIndex);
    }
    
    // Add click event to dots
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentIndex = index;
            showTestimonial(currentIndex);
        });
    });
    
    // Add click events to navigation buttons
    if(prevBtn && nextBtn) {
        prevBtn.addEventListener('click', prevTestimonial);
        nextBtn.addEventListener('click', nextTestimonial);
    }
    
    // Auto-rotate testimonials every 5 seconds
    let testimonialInterval = setInterval(nextTestimonial, 5000);
    
    // Pause auto-rotation when hovering over testimonials
    const testimonialsContainer = document.querySelector('.testimonials-container');
    
    if(testimonialsContainer) {
        testimonialsContainer.addEventListener('mouseenter', () => {
            clearInterval(testimonialInterval);
        });
        
        testimonialsContainer.addEventListener('mouseleave', () => {
            testimonialInterval = setInterval(nextTestimonial, 5000);
        });
    }
});
</script>




<!-- Call to Action Section -->
<section class="cta">
    <div class="container">
        <div class="cta-content">
            <h2>Vous êtes propriétaire ?</h2>
            <p>Inscrivez-vous gratuitement et mettez vos biens en location en quelques minutes.</p>
            <a href="views/auth/register.php?role=owner" class="btn btn-primary">Devenir propriétaire</a>
        </div>
    </div>
</section>

<?php
// Inclure le pied de page
include('includes/footer.php');
?>