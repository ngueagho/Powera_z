<?php
require_once 'includes/header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Trouvez votre prochain logement</h1>
        <p>Des milliers de logements vous attendent</p>
        
        <div class="search-box">
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="ville" placeholder="Ville" class="search-input">
                
                <select name="type" class="search-select">
                    <option value="">Type de logement</option>
                    <option value="chambre">Chambre</option>
                    <option value="studio">Studio</option>
                    <option value="appartement">Appartement</option>
                    <option value="maison">Maison</option>
                </select>
                
                <select name="prix_min" class="search-select">
                    <option value="">Prix minimum</option>
                    <option value="50000">50 000 FCFA</option>
                    <option value="100000">100 000 FCFA</option>
                    <option value="150000">150 000 FCFA</option>
                    <option value="200000">200 000 FCFA</option>
                </select>
                
                <select name="prix_max" class="search-select">
                    <option value="">Prix maximum</option>
                    <option value="100000">100 000 FCFA</option>
                    <option value="200000">200 000 FCFA</option>
                    <option value="300000">300 000 FCFA</option>
                    <option value="400000">400 000 FCFA</option>
                </select>
                
                <button type="submit" class="search-button">Rechercher</button>
            </form>
        </div>
    </div>
</div>

<div class="main-content">
    <section class="featured-section">
        <h2>Logements en vedette</h2>
        <?php
        // Vérifier s'il y a des logements en vedette
        $featured_properties = []; // À remplacer par une vraie requête
        if (empty($featured_properties)) {
            echo '<p class="no-results">Aucun logement en vedette pour le moment.</p>';
        }
        ?>
        <a href="logements.php" class="view-all-btn">Voir tous les logements</a>
    </section>

    <section class="recent-section">
        <h2>Logements récents</h2>
        <!-- Les logements récents seront affichés ici -->
    </section>

    <section class="how-it-works">
        <h2>Comment ça marche</h2>
        <div class="steps-container">
            <div class="step">
                <div class="step-icon search-icon"></div>
                <h3>Recherchez</h3>
                <p>Trouvez le logement idéal parmi notre sélection de propriétés.</p>
            </div>
            
            <div class="step">
                <div class="step-icon reserve-icon"></div>
                <h3>Réservez</h3>
                <p>Choisissez vos dates et effectuez votre réservation en ligne.</p>
            </div>
            
            <div class="step">
                <div class="step-icon contact-icon"></div>
                <h3>Contactez</h3>
                <p>Discutez avec le propriétaire et organisez votre séjour.</p>
            </div>
            
            <div class="step">
                <div class="step-icon move-icon"></div>
                <h3>Emménagez</h3>
                <p>Profitez de votre nouveau logement en toute sérénité.</p>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <h2>Ce que disent nos utilisateurs</h2>
        <div class="testimonials-container">
            <div class="testimonial">
                <p>"J'ai trouvé mon appartement idéal en quelques jours grâce à HouseConnect. Le processus était simple et rapide !"</p>
                <div class="testimonial-author">
                    <span class="author-name">Sophie M.</span>
                    <span class="author-type">Locataire</span>
                </div>
            </div>

            <div class="testimonial">
                <p>"Je peux maintenant gérer mes biens et communiquer avec mes locataires. Une plateforme vraiment complète !"</p>
                <div class="testimonial-author">
                    <span class="author-name">Thomas L.</span>
                    <span class="author-type">Propriétaire</span>
                </div>
            </div>

            <div class="testimonial">
                <p>"Le système de messagerie et d'appels vidéo m'a permis de visiter plusieurs logements à distance. Vraiment pratique !"</p>
                <div class="testimonial-author">
                    <span class="author-name">Julie K.</span>
                    <span class="author-type">Locataire</span>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <h2>Vous êtes propriétaire ?</h2>
        <p>Inscrivez-vous gratuitement et mettez vos biens en location en quelques minutes.</p>
        <a href="register.php?type=proprietaire" class="cta-button">Devenir propriétaire</a>
    </section>
</div>

<?php
require_once 'includes/footer.php';
?>