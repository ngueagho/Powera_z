<?php
/**
 * Contrôleur d'utilisateur
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');

// Démarrer la session
session_start();

// Définir l'en-tête JSON pour les réponses AJAX
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || !$_SESSION[SESSION_PREFIX . 'logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier si l'action est spécifiée
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    exit;
}

$action = $_POST['action'];
$userId = $_SESSION[SESSION_PREFIX . 'user_id'];
$userRole = $_SESSION[SESSION_PREFIX . 'user_role'];

// Initialiser le modèle
$userModel = new User();

// Traiter en fonction de l'action
switch ($action) {
    case 'update_profile':
        // Mettre à jour le profil
        handleUpdateProfile($userModel, $userId);
        break;
        
    case 'change_password':
        // Changer le mot de passe
        handleChangePassword($userModel, $userId);
        break;
        
    case 'upload_profile_pic':
        // Télécharger une photo de profil
        handleUploadProfilePic($userModel, $userId);
        break;
        
    case 'create_user':
        // Créer un utilisateur (administrateur uniquement)
        handleCreateUser($userModel, $userRole);
        break;
        
    case 'update_user':
        // Mettre à jour un utilisateur (administrateur uniquement)
        handleUpdateUser($userModel, $userRole);
        break;
        
    case 'delete_user':
        // Supprimer un utilisateur (administrateur uniquement)
        handleDeleteUser($userModel, $userRole);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

/**
 * Gérer la mise à jour du profil
 * @param User $userModel Instance du modèle User
 * @param int $userId ID de l'utilisateur
 */
function handleUpdateProfile($userModel, $userId) {
    // Vérifier les champs requis
    $requiredFields = ['first_name', 'last_name', 'email'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
            exit;
        }
    }
    
    // Valider l'email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit;
    }
    
    // Vérifier si l'email existe déjà (pour un autre utilisateur)
    $existingUser = $userModel->getByEmail($_POST['email']);
    if ($existingUser && $existingUser['id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par un autre compte']);
        exit;
    }
    
    // Préparer les données utilisateur
    $userData = [
        'first_name' => htmlspecialchars($_POST['first_name']),
        'last_name' => htmlspecialchars($_POST['last_name']),
        'email' => $_POST['email'],
        'phone' => isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : null,
        'bio' => isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : null
    ];
    
    // Mettre à jour l'utilisateur
    $result = $userModel->update($userId, $userData);
    
    if ($result) {
        // Mettre à jour le nom dans la session
        $_SESSION[SESSION_PREFIX . 'user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
        
        echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du profil']);
    }
}

/**
 * Gérer le changement de mot de passe
 * @param User $userModel Instance du modèle User
 * @param int $userId ID de l'utilisateur
 */
function handleChangePassword($userModel, $userId) {
    // Vérifier les champs requis
    $requiredFields = ['current_password', 'new_password', 'confirm_password'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
            exit;
        }
    }
    
    // Vérifier que les nouveaux mots de passe correspondent
    if ($_POST['new_password'] !== $_POST['confirm_password']) {
        echo json_encode(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas']);
        exit;
    }
    
    // Vérifier la complexité du nouveau mot de passe
    if (strlen($_POST['new_password']) < 8) {
        echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères']);
        exit;
    }
    
    // Récupérer l'utilisateur
    $user = $userModel->getById($userId);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
        exit;
    }
    
    // Vérifier le mot de passe actuel
    if (!password_verify($_POST['current_password'], $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Mot de passe actuel incorrect']);
        exit;
    }
    
    // Mettre à jour le mot de passe
    $result = $userModel->update($userId, ['password' => $_POST['new_password']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Mot de passe changé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du changement de mot de passe']);
    }
}

/**
 * Gérer le téléchargement d'une photo de profil
 * @param User $userModel Instance du modèle User
 * @param int $userId ID de l'utilisateur
 */
function handleUploadProfilePic($userModel, $userId) {
    // Vérifier si une image est fournie
    if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] != 0) {
        echo json_encode(['success' => false, 'message' => 'Aucune image fournie ou erreur lors du téléchargement']);
        exit;
    }
    
    // Télécharger la photo de profil
    $imagePath = $userModel->uploadProfilePic($userId, $_FILES['profile_pic']);
    
    if ($imagePath) {
        echo json_encode(['success' => true, 'message' => 'Photo de profil mise à jour', 'image_path' => $imagePath]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la photo de profil']);
    }
}

/**
 * Gérer la création d'un utilisateur (administrateur uniquement)
 * @param User $userModel Instance du modèle User
 * @param string $userRole Rôle de l'utilisateur
 */
function handleCreateUser($userModel, $userRole) {
    // Vérifier que l'utilisateur est un administrateur
    if ($userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les administrateurs peuvent créer des utilisateurs']);
        exit;
    }
    
    // Vérifier les champs requis
    $requiredFields = ['first_name', 'last_name', 'email', 'password', 'role'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
            exit;
        }
    }
    
    // Valider l'email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit;
    }
    
    // Vérifier si l'email existe déjà
    $existingUser = $userModel->getByEmail($_POST['email']);
    if ($existingUser) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
        exit;
    }
    
    // Valider le rôle
    $validRoles = ['admin', 'owner', 'tenant'];
    if (!in_array($_POST['role'], $validRoles)) {
        echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
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
        'bio' => isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : null,
        'verified' => true,
        'status' => 'active'
    ];
    
    // Créer l'utilisateur
    $userId = $userModel->create($userData);
    
    if ($userId) {
        echo json_encode([
            'success' => true, 
            'message' => 'Utilisateur créé avec succès',
            'user_id' => $userId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de l\'utilisateur']);
    }
}

/**
 * Gérer la mise à jour d'un utilisateur (administrateur uniquement)
 * @param User $userModel Instance du modèle User
 * @param string $userRole Rôle de l'utilisateur
 */
function handleUpdateUser($userModel, $userRole) {
    // Vérifier que l'utilisateur est un administrateur
    if ($userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les administrateurs peuvent mettre à jour les utilisateurs']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
        exit;
    }
    
    $targetUserId = (int)$_POST['user_id'];
    
    // Récupérer l'utilisateur
    $user = $userModel->getById($targetUserId);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
        exit;
    }
    
    // Préparer les données à mettre à jour
    $userData = [];
    
    // Champs pouvant être mis à jour
    $updatableFields = [
        'first_name', 'last_name', 'email', 'phone', 'bio', 'role', 'status'
    ];
    
    foreach ($updatableFields as $field) {
        if (isset($_POST[$field])) {
            $userData[$field] = $field == 'email' ? $_POST[$field] : htmlspecialchars($_POST[$field]);
        }
    }
    
    // Si l'email est modifié, vérifier qu'il n'est pas déjà utilisé
    if (isset($userData['email']) && $userData['email'] != $user['email']) {
        $existingUser = $userModel->getByEmail($userData['email']);
        if ($existingUser) {
            echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par un autre compte']);
            exit;
        }
    }
    
    // Si un nouveau mot de passe est fourni, l'inclure
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $userData['password'] = $_POST['password'];
    }
    
    // Mettre à jour l'utilisateur
    $result = $userModel->update($targetUserId, $userData);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'utilisateur']);
    }
}

/**
 * Gérer la suppression d'un utilisateur (administrateur uniquement)
 * @param User $userModel Instance du modèle User
 * @param string $userRole Rôle de l'utilisateur
 */
function handleDeleteUser($userModel, $userRole) {
    // Vérifier que l'utilisateur est un administrateur
    if ($userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les administrateurs peuvent supprimer des utilisateurs']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
        exit;
    }
    
    $targetUserId = (int)$_POST['user_id'];
    
    // Récupérer l'utilisateur
    $user = $userModel->getById($targetUserId);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
        exit;
    }
    
    // Empêcher la suppression de soi-même
    if ($targetUserId == $_SESSION[SESSION_PREFIX . 'user_id']) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
        exit;
    }
    
    // Supprimer l'utilisateur
    $result = $userModel->delete($targetUserId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'utilisateur']);
    }
}