
<?php
// footer.php - Improved design
?>
<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-logo-section">
                    <div class="footer-logo">
                        <img src="../assets/images/logo.svg" alt=" Logo" class="logo-img">
                        <span>HouseConnect</span>
                    </div>
                    <p class="footer-description">
                        Plateforme de location immobilière simple et sécurisée. Trouvez votre prochain logement ou louez vos biens immobiliers en toute simplicité.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <div class="footer-links">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="../index.php">Accueil</a></li>
                        <li><a href="properties.php">Logements</a></li>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="terms.php">Conditions d'utilisation</a></li>
                        <li><a href="privacy.php">Politique de confidentialité</a></li>
                    </ul>
                </div>

                <div class="footer-contact">
                    <h3>Contactez-nous</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Cite verte, 75000 yaounde</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <p>+237 6 94 61 99 50</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <p>contact@houseconnect.com</p>
                        </div>
                    </div>
                </div>

                <div class="footer-newsletter">
                    <h3>Newsletter</h3>
                    <p>Abonnez-vous pour recevoir nos dernières offres de logements</p>
                    <form class="newsletter-form" action="../controllers/newsletter_controller.php" method="POST">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Votre email" required>
                            <button type="submit" class="subscribe-btn">S'abonner</button>
                        </div>
                        <div class="consent-checkbox">
                            <input type="checkbox" id="newsletter-consent" name="consent" required>
                            <label for="newsletter-consent">J'accepte de recevoir la newsletter de HouseConnect</label>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> HouseConnect. Tous droits réservés.</p>
            </div>
            <div class="payment-methods">
                <span>Méthodes de paiement acceptées:</span>
                <div class="payment-icons">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-cc-apple-pay"></i>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* Improved Footer Styles */
.footer {
    background-color: #f8f9fa;
    color: #555;
    font-family: 'Open Sans', sans-serif;
}

.footer-top {
    padding: 60px 0 40px;
    border-bottom: 1px solid #eaeaea;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
}

/* Logo Section */
.footer-logo-section {
    grid-column: span 1;
}

.footer-logo {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.logo-img {
    height: 40px;
    margin-right: 10px;
}

.footer-logo span {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2a4b8d;
}

.footer-description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.footer-social {
    display: flex;
    gap: 12px;
}

.social-icon {
    width: 36px;
    height: 36px;
    background-color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3498db;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1rem;
    border: 1px solid #eaeaea;
}

.social-icon:hover {
    background-color: #3498db;
    color: white;
    transform: translateY(-3px);
}

/* Links Section */
.footer-links {
    grid-column: span 1;
}

.footer-links h3, 
.footer-contact h3, 
.footer-newsletter h3 {
    color: #2a4b8d;
    font-size: 1.2rem;
    margin-bottom: 20px;
    font-weight: 600;
    position: relative;
    padding-bottom: 10px;
}

.footer-links h3:after, 
.footer-contact h3:after, 
.footer-newsletter h3:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 50px;
    height: 2px;
    background-color: #3498db;
}

.footer-links ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: #555;
    text-decoration: none;
    transition: color 0.2s ease;
    position: relative;
    padding-left: 15px;
    font-size: 0.95rem;
}

.footer-links a:before {
    content: '›';
    position: absolute;
    left: 0;
    color: #3498db;
    font-size: 1.2rem;
    line-height: 1;
}

.footer-links a:hover {
    color: #3498db;
}

/* Contact Section */
.footer-contact {
    grid-column: span 1;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
}

.contact-item i {
    color: #3498db;
    margin-right: 10px;
    min-width: 16px;
}

.contact-item p {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.5;
}

/* Newsletter Section */
.footer-newsletter {
    grid-column: span 1;
}

.footer-newsletter p {
    margin-bottom: 15px;
    font-size: 0.95rem;
    line-height: 1.5;
}

.newsletter-form .form-group {
    position: relative;
    margin-bottom: 10px;
}

.newsletter-form input[type="email"] {
    width: 100%;
    padding: 12px 120px 12px 15px;
    border: 1px solid #ddd;
    border-radius: 30px;
    font-size: 0.9rem;
    background-color: #fff;
    transition: border-color 0.3s ease;
}

.newsletter-form input[type="email"]:focus {
    border-color: #3498db;
    outline: none;
}

.subscribe-btn {
    position: absolute;
    right: 3px;
    top: 3px;
    background-color: #3498db;
    color: white;
    border: none;
    padding: 9px 20px;
    border-radius: 30px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.subscribe-btn:hover {
    background-color: #2980b9;
}

.consent-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-top: 10px;
}

.consent-checkbox input {
    margin-top: 3px;
}

.consent-checkbox label {
    font-size: 0.8rem;
    color: #777;
    line-height: 1.4;
}

/* Footer Bottom */
.footer-bottom {
    padding: 20px 0;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
}

.footer-bottom .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.copyright {
    font-size: 0.9rem;
    color: #666;
}

.payment-methods {
    display: flex;
    align-items: center;
    gap: 15px;
}

.payment-methods span {
    font-size: 0.9rem;
    color: #666;
}

.payment-icons {
    display: flex;
    gap: 10px;
}

.payment-icons i {
    color: #555;
    font-size: 1.5rem;
    transition: color 0.2s ease;
}

.payment-icons i:hover {
    color: #3498db;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .footer-logo-section,
    .footer-links,
    .footer-contact,
    .footer-newsletter {
        grid-column: span 1;
    }
}

@media (max-width: 768px) {
    .footer-top {
        padding: 40px 0 20px;
    }
    
    .footer-bottom .container {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
}

@media (max-width: 576px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
    
    .footer-logo-section,
    .footer-links,
    .footer-contact,
    .footer-newsletter {
        grid-column: span 1;
    }
    
    .payment-methods {
        flex-direction: column;
        gap: 10px;
    }
}
</style>