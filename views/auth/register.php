<?php
/**
 * Page d'inscription
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

// Par défaut, rôle "tenant" (locataire)
$selectedRole = isset($_GET['role']) && $_GET['role'] === 'owner' ? 'owner' : 'tenant';

// Générer un captcha simple
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$_SESSION['captcha'] = $num1 + $num2;

// Inclure l'en-tête
include('../../includes/header.php');
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Inscription</h1>
                <p>Créez votre compte HouseConnect</p>
            </div>
            <div class="auth-body">
                <div class="role-selector">
                    <a href="?role=tenant" class="role-btn <?= $selectedRole === 'tenant' ? 'active' : '' ?>">
                        <i class="fas fa-user"></i>
                        <span>Je suis locataire</span>
                    </a>
                    <a href="?role=owner" class="role-btn <?= $selectedRole === 'owner' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i>
                        <span>Je suis propriétaire</span>
                    </a>
                </div>
                
                <form id="register-form" action="../../controllers/auth.php" method="POST" data-ajax="true">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="role" value="<?= $selectedRole ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Téléphone (optionnel)</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" id="password" name="password" class="form-control" required minlength="8">
                            <small class="form-help">8 caractères minimum</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="8">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="captcha" class="form-label">Sécurité : Combien font <?= $num1 ?> + <?= $num2 ?> ?</label>
                        <input type="number" id="captcha" name="captcha" class="form-control" required>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                        <label for="terms" class="form-check-label">J'accepte les <a href="../terms.php" target="_blank">conditions d'utilisation</a> et la <a href="../privacy.php" target="_blank">politique de confidentialité</a>.</label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
                    </div>
                </form>
            </div>
            <div class="auth-footer">
                <p>Déjà inscrit ? <a href="login.php">Connectez-vous</a></p>
            </div>
        </div>
    </div>
</section>

<style>
.role-selector {
    display: flex;
    margin-bottom: 20px;
    gap: 15px;
}

.role-btn {
    flex: 1;
    text-align: center;
    padding: 15px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
}

.role-btn:hover {
    border-color: #4a6ee0;
    color: #4a6ee0;
}

.role-btn.active {
    border-color: #4a6ee0;
    background-color: #e8f0fe;
    color: #4a6ee0;
}

.role-btn i {
    font-size: 24px;
    margin-bottom: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('register-form');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    
    // Vérifier la correspondance des mots de passe
    function checkPasswordMatch() {
        if (passwordInput.value !== confirmInput.value) {
            confirmInput.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            confirmInput.setCustomValidity('');
        }
    }
    
    passwordInput.addEventListener('change', checkPasswordMatch);
    confirmInput.addEventListener('keyup', checkPasswordMatch);
    
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        
        // Désactiver le bouton pendant la soumission
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="loading"></span> Inscription...';
        
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
            submitButton.innerHTML = 'S\'inscrire';
            
            if (data.success) {
                // Afficher un message de succès et rediriger
                registerForm.innerHTML = `
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h3>Inscription réussie !</h3>
                        <p>${data.message}</p>
                        <a href="login.php" class="btn btn-primary">Se connecter</a>
                    </div>
                `;
            } else {
                // Afficher l'erreur
                const errorElement = document.createElement('div');
                errorElement.className = 'form-error shake';
                errorElement.textContent = data.message || 'Une erreur est survenue';
                registerForm.insertBefore(errorElement, registerForm.firstChild);
                
                // Régénérer le captcha si nécessaire
                if (data.message && data.message.includes('Captcha')) {
                    window.location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            
            // Réactiver le bouton
            submitButton.disabled = false;
            submitButton.innerHTML = 'S\'inscrire';
            
            // Afficher une erreur générique
            const errorElement = document.createElement('div');
            errorElement.className = 'form-error shake';
            errorElement.textContent = 'Une erreur est survenue lors de l\'inscription';
            registerForm.insertBefore(errorElement, registerForm.firstChild);
        });
    });
});
</script>

<?php
// Inclure le pied de page
include('../../includes/footer.php');
?>