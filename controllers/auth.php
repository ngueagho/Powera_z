<?php
/**
 * Contrôleur d'authentification
 * Gestion des connexions, inscriptions, et récupération de mot de passe
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');

// Démarrer la session
session_start();

// Définir l'en-tête JSON pour les réponses AJAX
header('Content-Type: application/json');

// Vérifier si l'action est spécifiée
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    exit;
}
/**
 * Envoyer un email de vérification
 * @param array $user Données de l'utilisateur
 * @return bool Succès ou échec
 */
function sendVerificationEmail($user) {
    $to = $user['email'];
    $subject = APP_NAME . " - Vérification de votre compte";
    
    $verificationLink = APP_URL . "/views/auth/verify.php?token=" . $user['verification_token'];
    
    $message = "
    <html>
    <head>
        <title>Vérification de votre compte</title>
    </head>
    <body>
        <h2>Bienvenue sur " . APP_NAME . ", " . $user['first_name'] . "!</h2>
        <p>Veuillez cliquer sur le lien ci-dessous pour vérifier votre adresse email :</p>
        <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
        <p>Si vous n'avez pas créé de compte, veuillez ignorer cet email.</p>
        <p>Merci,<br>L'équipe " . APP_NAME . "</p>
    </body>
    </html>
    ";
    
    // En-têtes pour l'email HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . APP_EMAIL . "\r\n";
    
    // Envoyer l'email
    return mail($to, $subject, $message, $headers);
}

/**
 * Envoyer un email de réinitialisation de mot de passe
 * @param array $user Données de l'utilisateur
 * @param string $token Token de réinitialisation
 * @return bool Succès ou échec
 */
function sendResetPasswordEmail($user, $token) {
    $to = $user['email'];
    $subject = APP_NAME . " - Réinitialisation de mot de passe";
    
    $resetLink = APP_URL . "/views/auth/reset-password.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Réinitialisation de mot de passe</title>
    </head>
    <body>
        <h2>Réinitialisation de mot de passe</h2>
        <p>Bonjour " . $user['first_name'] . ",</p>
        <p>Vous avez demandé la réinitialisation de votre mot de passe. Veuillez cliquer sur le lien ci-dessous :</p>
        <p><a href='{$resetLink}'>{$resetLink}</a></p>
        <p>Ce lien expirera dans 1 heure.</p>
        <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
        <p>Merci,<br>L'équipe " . APP_NAME . "</p>
    </body>
    </html>
    ";
    
    // En-têtes pour l'email HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . APP_EMAIL . "\r\n";
    
    // Envoyer l'email
    return mail($to, $subject, $message, $headers);
}

/**
 * Obtenir la page d'accueil en fonction du rôle
 * @param string $role Rôle de l'utilisateur
 * @return string URL de la page d'accueil
 */
function getUserHomePage($role) {
    switch ($role) {
        case 'admin':
            return '../views/admin/dashboard.php';
        case 'owner':
            return '../views/dashboard/owner/index.php';
        case 'tenant':
            return '../views/dashboard/tenant/index.php';
        default:
            return '../index.php';
    }
}

$action = $_POST['action'];
$userModel = new User();

// Traiter l'action demandée
switch ($action) {
    case 'register':
        handleRegistration($userModel);
        break;
        
    case 'login':
        handleLogin($userModel);
        break;
        
    case 'logout':
        handleLogout();
        break;
        
    case 'reset-password-request':
        handleResetPasswordRequest($userModel);
        break;
        
    case 'reset-password':
        handleResetPassword($userModel);
        break;
        
    case 'verify-email':
        handleVerifyEmail($userModel);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

/**
 * Gérer l'inscription d'un nouvel utilisateur
 * @param User $userModel Instance du modèle User
 */
function handleRegistration($userModel) {
    // Vérifier les champs requis
    $requiredFields = ['first_name', 'last_name', 'email', 'password', 'password_confirm', 'role'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
            exit;
        }
    }
    
    // Valider l'email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit;
    }
    
    // Vérifier que l'email n'est pas déjà utilisé
    if ($userModel->getByEmail($_POST['email'])) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
        exit;
    }
    
    // Vérifier que les mots de passe correspondent
    if ($_POST['password'] !== $_POST['password_confirm']) {
        echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
        exit;
    }
    
    // Vérifier la complexité du mot de passe
    if (strlen($_POST['password']) < 8) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']);
        exit;
    }
    
    // Vérifier le rôle
    $allowedRoles = ['owner', 'tenant'];
    if (!in_array($_POST['role'], $allowedRoles)) {
        echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
        exit;
    }
    
    // Vérifier le captcha (simple, à améliorer)
    if (!isset($_POST['captcha']) || $_POST['captcha'] !== $_SESSION['captcha']) {
        echo json_encode(['success' => false, 'message' => 'Captcha incorrect']);
        exit;
    }
    
    // Préparer les données utilisateur
    $userData = [
        'first_name' => htmlspecialchars($_POST['first_name']),
        'last_name' => htmlspecialchars($_POST['last_name']),
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'role' => $_POST['role'],
        'phone' => isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : null,
        'verified' => false,
        'status' => 'active'
    ];
    
    // Créer l'utilisateur
    $userId = $userModel->create($userData);
    
    if ($userId) {
        // Récupérer l'utilisateur créé pour obtenir le token de vérification
        $user = $userModel->getById($userId);
        
        // Envoyer l'email de vérification
        sendVerificationEmail($user);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Inscription réussie ! Un email de vérification a été envoyé.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'inscription']);
    }
}

/**
 * Gérer la connexion d'un utilisateur
 * @param User $userModel Instance du modèle User
 */
function handleLogin($userModel) {
    // Vérifier les champs requis
    if (!isset($_POST['email']) || !isset($_POST['password']) || empty($_POST['email']) || empty($_POST['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
        exit;
    }
    
    // Authentifier l'utilisateur
    $user = $userModel->authenticate($_POST['email'], $_POST['password']);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
        exit;
    }
    
    // Vérifier si l'utilisateur est vérifié
    if (!$user['verified']) {
        echo json_encode(['success' => false, 'message' => 'Veuillez vérifier votre email avant de vous connecter']);
        exit;
    }
    
    // Vérifier si l'utilisateur n'est pas banni
    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Votre compte est désactivé ou suspendu']);
        exit;
    }
    
    // Créer la session
    $_SESSION[SESSION_PREFIX . 'user_id'] = $user['id'];
    $_SESSION[SESSION_PREFIX . 'user_role'] = $user['role'];
    $_SESSION[SESSION_PREFIX . 'user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION[SESSION_PREFIX . 'logged_in'] = true;
    
    // Régénérer l'ID de session pour prévenir la fixation de session
    session_regenerate_id(true);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Connexion réussie',
        'redirect' => getUserHomePage($user['role'])
    ]);
}

/**
 * Gérer la déconnexion d'un utilisateur
 */
function handleLogout() {
    // Détruire toutes les variables de session
    $_SESSION = [];
    
    // Détruire le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Déconnexion réussie',
        'redirect' => '../index.php'
    ]);
}

/**
 * Gérer la demande de réinitialisation de mot de passe
 * @param User $userModel Instance du modèle User
 */
function handleResetPasswordRequest($userModel) {
    // Vérifier si l'email est spécifié
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        echo json_encode(['success' => false, 'message' => 'Email requis']);
        exit;
    }
    
    // Vérifier si l'utilisateur existe
    $user = $userModel->getByEmail($_POST['email']);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Aucun compte associé à cet email']);
        exit;
    }
    
    // Générer un token de réinitialisation
    $token = $userModel->generateResetToken($_POST['email']);
    
    if ($token) {
        // Envoyer l'email de réinitialisation
        sendResetPasswordEmail($user, $token);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Un email de réinitialisation a été envoyé'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération du token']);
    }
}

/**
 * Gérer la réinitialisation de mot de passe
 * @param User $userModel Instance du modèle User
 */
function handleResetPassword($userModel) {
    // Vérifier les champs requis
    if (!isset($_POST['token']) || !isset($_POST['password']) || !isset($_POST['password_confirm']) || 
        empty($_POST['token']) || empty($_POST['password']) || empty($_POST['password_confirm'])) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
        exit;
    }
    
    // Vérifier que les mots de passe correspondent
    if ($_POST['password'] !== $_POST['password_confirm']) {
        echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
        exit;
    }
    
    // Vérifier la complexité du mot de passe
    if (strlen($_POST['password']) < 8) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']);
        exit;
    }
    
    // Réinitialiser le mot de passe
    $result = $userModel->resetPassword($_POST['token'], $_POST['password']);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Mot de passe réinitialisé avec succès',
            'redirect' => '../views/auth/login.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Token invalide ou expiré']);
    }
}

/**
 * Gérer la vérification de l'email
 * @param User $userModel Instance du modèle User
 */
function handleVerifyEmail($userModel) {
    // Vérifier si le token est spécifié
    if (!isset($_POST['token']) || empty($_POST['token'])) {
        echo json_encode(['success' => false, 'message' => 'Token requis']);
        exit;
    }
    
    // Vérifier l'email
    $result = $userModel->verifyEmail($_POST['token']);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Email vérifié avec succès',
            'redirect' => '../views/auth/login.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Token invalide']);
    }
}