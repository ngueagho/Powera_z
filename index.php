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

<!-- Testimonials Section -->
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
                        <h4>Sophie M.</h4>
                        <p>Locataire</p>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"En tant que propriétaire, je peux facilement gérer mes biens et communiquer avec mes locataires. Une plateforme vraiment complète !"</p>
                </div>
                <div class="testimonial-author">
                    <img src="assets/images/testimonial-2.jpg" alt="Thomas L.">
                    <div>
                        <h4>Thomas L.</h4>
                        <p>Propriétaire</p>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"Le système de messagerie et d'appels vidéo m'a permis de visiter plusieurs logements à distance. Vraiment pratique !"</p>
                </div>
                <div class="testimonial-author">
                    <img src="assets/images/testimonial-3.jpg" alt="Julie K.">
                    <div>
                        <h4>Julie K.</h4>
                        <p>Locataire</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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