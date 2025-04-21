<?php
/**
 * Page de connexion
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../../config/config.php');

// Démarrer la session
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION[SESSION_PREFIX . 'logged_in']) && $_SESSION[SESSION_PREFIX . 'logged_in']) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Inclure l'en-tête
include('../../includes/header.php');
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Connexion</h1>
                <p>Accédez à votre compte HouseConnect</p>
            </div>
            <div class="auth-body">
                <form id="login-form" action="../../controllers/auth.php" method="POST" data-ajax="true">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="remember" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">Se souvenir de moi</label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="reset-password-request.php">Mot de passe oublié ?</a>
                    </div>
                </form>
            </div>
            <div class="auth-footer">
                <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous</a></p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        
        // Désactiver le bouton pendant la soumission
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="loading"></span> Connexion...';
        
        // Effacer les messages d'erreur précédents
        document.querySelectorAll('.form-error').forEach(error => {
            error.remove();
        });
        
        // Soumission AJAX
        fetch(this.getAttribute('action'), {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Réactiver le bouton
            submitButton.disabled = false;
            submitButton.innerHTML = 'Se connecter';
            
            if (data.success) {
                // Redirection
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = '../../index.php';
                }
            } else {
                // Afficher l'erreur
                const errorElement = document.createElement('div');
                errorElement.className = 'form-error shake';
                errorElement.textContent = data.message || 'Une erreur est survenue';
                loginForm.insertBefore(errorElement, loginForm.firstChild);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            
            // Réactiver le bouton
            submitButton.disabled = false;
            submitButton.innerHTML = 'Se connecter';
            
            // Afficher une erreur générique
            const errorElement = document.createElement('div');
            errorElement.className = 'form-error shake';
            errorElement.textContent = 'Une erreur est survenue lors de la connexion';
            loginForm.insertBefore(errorElement, loginForm.firstChild);
        });
    });
});
</script>

<?php
// Inclure le pied de page
include('../../includes/footer.php');
?>