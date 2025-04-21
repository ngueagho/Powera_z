</main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <div class="logo">
                        <img src="<?= $rootPath ?>assets/images/logo.svg" alt="HouseConnect Logo">
                        <span>HouseConnect</span>
                    </div>
                    <p>
                        Plateforme de location immobilière simple et sécurisée. 
                        Trouvez votre prochain logement ou louez vos biens immobiliers en toute simplicité.
                    </p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="footer-section links">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="<?= $rootPath ?>index.php">Accueil</a></li>
                        <li><a href="<?= $rootPath ?>views/properties/list.php">Logements</a></li>
                        <li><a href="<?= $rootPath ?>views/about.php">À propos</a></li>
                        <li><a href="<?= $rootPath ?>views/contact.php">Contact</a></li>
                        <li><a href="<?= $rootPath ?>views/terms.php">Conditions d'utilisation</a></li>
                        <li><a href="<?= $rootPath ?>views/privacy.php">Politique de confidentialité</a></li>
                    </ul>
                </div>
                
                <div class="footer-section contact">
                    <h3>Contactez-nous</h3>
                    <div class="contact-info">
                        <div><i class="fas fa-map-marker-alt"></i> 123 Rue de la Location, 75000 Paris</div>
                        <div><i class="fas fa-phone"></i> +33 1 23 45 67 89</div>
                        <div><i class="fas fa-envelope"></i> contact@houseconnect.com</div>
                    </div>
                </div>
                
                <div class="footer-section newsletter">
                    <h3>Newsletter</h3>
                    <p>Abonnez-vous pour recevoir nos dernières offres de logements</p>
                    <form id="newsletter-form">
                        <input type="email" name="email" placeholder="Votre email" required>
                        <button type="submit" class="btn btn-primary">S'abonner</button>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> HouseConnect. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="<?= $rootPath ?>assets/js/main.js"></script>
    
    <!-- Leaflet JS pour les cartes -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <!-- Script de déconnexion -->
    <script>
        $(document).ready(function() {
            // Gérer la déconnexion
            $('#logout-link').click(function(e) {
                e.preventDefault();
                
                $.post('<?= $rootPath ?>controllers/auth.php', {
                    action: 'logout'
                }, function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                    }
                }, 'json');
            });
            
            // Fermer les messages flash
            $('.close-flash').click(function() {
                $(this).closest('.flash-message').slideUp();
            });
            
            // Gérer le menu mobile
            $('.mobile-menu-toggle').click(function() {
                $('header').toggleClass('mobile-menu-open');
            });
            
            // Gérer les dropdown menus
            $('.dropdown-toggle').click(function() {
                $(this).next('.dropdown-menu').toggleClass('show');
            });
            
            // Fermer les dropdowns en cliquant à l'extérieur
            $(document).click(function(e) {
                if (!$(e.target).closest('.dropdown').length) {
                    $('.dropdown-menu').removeClass('show');
                }
            });
            
            // Inscription à la newsletter
            $('#newsletter-form').submit(function(e) {
                e.preventDefault();
                
                $.post('<?= $rootPath ?>controllers/newsletter.php', {
                    action: 'subscribe',
                    email: $(this).find('input[name="email"]').val()
                }, function(response) {
                    if (response.success) {
                        $('#newsletter-form').html('<p class="success">Merci pour votre inscription !</p>');
                    } else {
                        $('#newsletter-form').append('<p class="error">' + response.message + '</p>');
                    }
                }, 'json');
            });
            
            <?php if ($isLoggedIn): ?>
            // Vérifier les messages non lus
            function checkUnreadMessages() {
                $.getJSON('<?= $rootPath ?>api/messages.php?unread=true', function(response) {
                    if (response.success && response.data > 0) {
                        $('#unread-messages').text(response.data).show();
                    } else {
                        $('#unread-messages').hide();
                    }
                });
            }
            
            // Vérifier les messages non lus toutes les 30 secondes
            checkUnreadMessages();
            setInterval(checkUnreadMessages, 30000);
            <?php endif; ?>
        });
    </script>
</body>
</html>