<?php
/**
 * Contrôleur de propriété
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');
require_once('../models/Property.php');

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

// Initialiser les modèles
$propertyModel = new Property();
$userModel = new User();

// Traiter en fonction de l'action
switch ($action) {
    case 'add_property':
        // Ajouter une propriété (propriétaire uniquement)
        handleAddProperty($propertyModel, $userId, $userRole);
        break;
        
    case 'update_property':
        // Mettre à jour une propriété
        handleUpdateProperty($propertyModel, $userId, $userRole);
        break;
        
    case 'delete_property':
        // Supprimer une propriété
        handleDeleteProperty($propertyModel, $userId, $userRole);
        break;
        
    case 'upload_image':
        // Télécharger une image
        handleUploadImage($propertyModel, $userId, $userRole);
        break;
        
    case 'delete_image':
        // Supprimer une image
        handleDeleteImage($propertyModel, $userId, $userRole);
        break;
        
    case 'upload_video':
        // Télécharger une vidéo
        handleUploadVideo($propertyModel, $userId, $userRole);
        break;
        
    case 'delete_video':
        // Supprimer une vidéo
        handleDeleteVideo($propertyModel, $userId, $userRole);
        break;
        
    case 'update_amenities':
        // Mettre à jour les commodités
        handleUpdateAmenities($propertyModel, $userId, $userRole);
        break;
        
    case 'update_availability':
        // Mettre à jour les disponibilités
        handleUpdateAvailability($propertyModel, $userId, $userRole);
        break;
        
    case 'toggle_featured':
        // Mettre en avant une propriété
        handleToggleFeatured($propertyModel, $userId, $userRole);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

/**
 * Gérer l'ajout d'une propriété
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleAddProperty($propertyModel, $userId, $userRole) {
    // Vérifier que l'utilisateur est un propriétaire ou un administrateur
    if ($userRole != 'owner' && $userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les propriétaires peuvent ajouter des propriétés']);
        exit;
    }
    
    // Vérifier les champs requis
    $requiredFields = [
        'title', 'description', 'property_type', 'rooms', 'bathrooms', 'surface', 
        'price', 'address', 'city'
    ];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires']);
            exit;
        }
    }
    
    // Préparer les données de la propriété
    $propertyData = [
        'owner_id' => $userRole == 'admin' && isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : $userId,
        'title' => htmlspecialchars($_POST['title']),
        'description' => htmlspecialchars($_POST['description']),
        'property_type' => $_POST['property_type'],
        'rooms' => (int)$_POST['rooms'],
        'bathrooms' => (int)$_POST['bathrooms'],
        'surface' => (float)$_POST['surface'],
        'price' => (float)$_POST['price'],
        'address' => htmlspecialchars($_POST['address']),
        'city' => htmlspecialchars($_POST['city']),
        'postal_code' => isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : null,
        'latitude' => isset($_POST['latitude']) ? (float)$_POST['latitude'] : null,
        'longitude' => isset($_POST['longitude']) ? (float)$_POST['longitude'] : null,
        'featured' => isset($_POST['featured']) ? (bool)$_POST['featured'] : false,
        'status' => $userRole == 'admin' ? 'available' : 'pending_approval'
    ];
    
    // Créer la propriété
    $propertyId = $propertyModel->create($propertyData);
    
    if ($propertyId) {
        // Gérer les commodités
        if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
            $propertyModel->updateAmenities($propertyId, $_POST['amenities']);
        }
        
        // Gérer les disponibilités
        if (isset($_POST['availability_start']) && isset($_POST['availability_end']) && 
            is_array($_POST['availability_start']) && is_array($_POST['availability_end'])) {
            
            $availability = [];
            for ($i = 0; $i < count($_POST['availability_start']); $i++) {
                if (!empty($_POST['availability_start'][$i]) && !empty($_POST['availability_end'][$i])) {
                    $availability[] = [
                        'start_date' => $_POST['availability_start'][$i],
                        'end_date' => $_POST['availability_end'][$i],
                        'status' => 'available'
                    ];
                }
            }
            
            if (!empty($availability)) {
                $propertyModel->updateAvailability($propertyId, $availability);
            }
        }
        
        // Gérer les images
        $uploadedImages = [];
        
        if (isset($_FILES['property_images']) && !empty($_FILES['property_images']['name'][0])) {
            $imageCount = count($_FILES['property_images']['name']);
            
            for ($i = 0; $i < $imageCount; $i++) {
                $file = [
                    'name' => $_FILES['property_images']['name'][$i],
                    'type' => $_FILES['property_images']['type'][$i],
                    'tmp_name' => $_FILES['property_images']['tmp_name'][$i],
                    'error' => $_FILES['property_images']['error'][$i],
                    'size' => $_FILES['property_images']['size'][$i]
                ];
                
                // Vérifier s'il s'agit de l'image principale
                $isMain = ($i == 0);
                
                $imagePath = $propertyModel->addImage($propertyId, $file, $isMain);
                
                if ($imagePath) {
                    $uploadedImages[] = $imagePath;
                }
            }
        }
        
        // Gérer la vidéo
        $uploadedVideo = null;
        
        if (isset($_FILES['property_video']) && !empty($_FILES['property_video']['name'])) {
            $videoPath = $propertyModel->addVideo($propertyId, $_FILES['property_video']);
            
            if ($videoPath) {
                $uploadedVideo = $videoPath;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Propriété ajoutée avec succès' . ($userRole == 'owner' ? ' et en attente d\'approbation' : ''),
            'property_id' => $propertyId,
            'uploaded_images' => $uploadedImages,
            'uploaded_video' => $uploadedVideo
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la propriété']);
    }
}

/**
 * Gérer la mise à jour d'une propriété
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleUpdateProperty($propertyModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété manquant']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    
    // Récupérer la propriété
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canEdit = false;
    
    if ($userRole == 'admin') {
        $canEdit = true;
    } else if ($userRole == 'owner' && $property['owner_id'] == $userId) {
        $canEdit = true;
    }
    
    if (!$canEdit) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette propriété']);
        exit;
    }
    
    // Préparer les données à mettre à jour
    $propertyData = [];
    
    // Champs pouvant être mis à jour
    $updatableFields = [
        'title', 'description', 'property_type', 'rooms', 'bathrooms', 'surface', 
        'price', 'address', 'city', 'postal_code', 'latitude', 'longitude', 'featured', 'status'
    ];
    
    foreach ($updatableFields as $field) {
        if (isset($_POST[$field])) {
            if (in_array($field, ['title', 'description', 'address', 'city', 'postal_code'])) {
                $propertyData[$field] = htmlspecialchars($_POST[$field]);
            } else {
                $propertyData[$field] = $_POST[$field];
            }
        }
    }
    
    // S'assurer que seul un admin peut changer le statut en 'available'
    if (isset($propertyData['status']) && $propertyData['status'] == 'available' && $userRole != 'admin') {
        $propertyData['status'] = 'pending_approval';
    }
    
    // Mettre à jour la propriété
    $result = $propertyModel->update($propertyId, $propertyData);
    
    if ($result) {
        // Gérer les commodités
        if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
            $propertyModel->updateAmenities($propertyId, $_POST['amenities']);
        }
        
        // Gérer les disponibilités
        if (isset($_POST['availability_start']) && isset($_POST['availability_end']) && 
            is_array($_POST['availability_start']) && is_array($_POST['availability_end'])) {
            
            $availability = [];
            for ($i = 0; $i < count($_POST['availability_start']); $i++) {
                if (!empty($_POST['availability_start'][$i]) && !empty($_POST['availability_end'][$i])) {
                    $availability[] = [
                        'start_date' => $_POST['availability_start'][$i],
                        'end_date' => $_POST['availability_end'][$i],
                        'status' => 'available'
                    ];
                }
            }
            
            $propertyModel->updateAvailability($propertyId, $availability);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Propriété mise à jour avec succès' . 
                        (isset($propertyData['status']) && $propertyData['status'] == 'pending_approval' && $userRole == 'owner' 
                        ? ' et en attente d\'approbation' : '')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la propriété']);
    }
}

/**
 * Gérer la suppression d'une propriété
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleDeleteProperty($propertyModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété manquant']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    
    // Récupérer la propriété
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canDelete = false;
    
    if ($userRole == 'admin') {
        $canDelete = true;
    } else if ($userRole == 'owner' && $property['owner_id'] == $userId) {
        $canDelete = true;
    }
    
    if (!$canDelete) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cette propriété']);
        exit;
    }
    
    // Supprimer la propriété
    $result = $propertyModel->delete($propertyId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Propriété supprimée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la propriété']);
    }
}

/**
 * Gérer le téléchargement d'une image
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleUploadImage($propertyModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id']) || !isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété et image requis']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    $isMain = isset($_POST['is_main']) ? (bool)$_POST['is_main'] : false;
    
    // Récupérer la propriété
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canUpload = false;
    
    if ($userRole == 'admin') {
        $canUpload = true;
    } else if ($userRole == 'owner' && $property['owner_id'] == $userId) {
        $canUpload = true;
    }
    
    if (!$canUpload) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette propriété']);
        exit;
    }
    
    // Télécharger l'image
    $imagePath = $propertyModel->addImage($propertyId, $_FILES['image'], $isMain);
    
    if ($imagePath) {
        echo json_encode([
            'success' => true,
            'message' => 'Image téléchargée avec succès',
            'image_path' => $imagePath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de l\'image']);
    }
}

/**
 * Gérer la suppression d'une image
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleDeleteImage($propertyModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['image_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de l\'image manquant']);
        exit;
    }
    
    $imageId = (int)$_POST['image_id'];
    
    // Récupérer l'image
    $db = Database::getInstance();
    $image = $db->fetchOne("SELECT * FROM property_images WHERE id = ?", [$imageId]);
    
    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Image introuvable']);
        exit;
    }
    
    // Récupérer la propriété
    $property = $propertyModel->getById($image['property_id']);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canDelete = false;
    
    if ($userRole == 'admin') {
        $canDelete = true;
    } else if ($userRole == 'owner' && $property['owner_id'] == $userId) {
        $canDelete = true;
    }
    
    if (!$canDelete) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette propriété']);
        exit;
    }
    
    // Supprimer l'image
    $result = $propertyModel->deleteImage($imageId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Image supprimée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'image']);
    }
}

/**
 * Gérer le téléchargement d'une vidéo
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleUploadVideo($propertyModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id']) || !isset($_FILES['video'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété et vidéo requis']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    
    // Récupérer la propriété
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canUpload = false;
    
    if ($userRole == 'admin') {
        $canUpload = true;
    } else if ($userRole == 'owner' && $property['owner_id'] == $userId) {
        $canUpload = true;
    }
    
    if (!$canUpload) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette propriété']);
        exit;
    }
    
    // Télécharger la vidéo
    $videoPath = $propertyModel->addVideo($propertyId, $_FILES['video']);
    
    if ($videoPath) {
        echo json_encode([
            'success' => true,
            'message' => 'Vidéo téléchargée avec succès',
            'video_path' => $videoPath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de la vidéo']);
    }
}

/**
 * Gérer la suppression d'une vidéo
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleDeleteVideo($propertyModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['video_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de la vidéo manquant']);
        exit;
    }
    
    $videoId = (int)$_POST['video_id'];
    
    // Récupérer la vidéo
    $db = Database::getInstance();
    $video = $db->fetchOne("SELECT * FROM property_videos WHERE id = ?", [$videoId]);
    
    if (!$video) {
        echo json_encode(['success' => false, 'message' => 'Vidéo introuvable']);
        exit;
    }
    
    // Récupérer la propriété
    $property = $propertyModel->getById($video['property_id']);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canDelete = false;
    
    if ($userRole == 'admin') {
        $canDelete = true;
    } else if ($userRole == 'owner' && $property['owner_id'] == $userId) {
        $canDelete = true;
    }
    
    if (!$canDelete) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette propriété']);
        exit;
    }
    
    // Supprimer la vidéo
    $result = $propertyModel->deleteVideo($videoId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Vidéo supprimée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la vidéo']);
    }
}

/**
 * Gérer la mise à jour des commodités
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleUpdateAmenities($propertyModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id']) || !isset($_POST['amenities']) || !is_array($_POST['amenities'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété et commodités requis']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    $amenities = array_map('intval', $_POST['amenities']);
    
    // Récupérer la propriété
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canUpdate = false;
    
    if ($userRole == 'admin') {
        $canUpdate = true;
    } else if ($userRole == 'owner' && $property['owner_id'] == $userId) {
        $canUpdate = true;
    }
    
    if (!$canUpdate) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette propriété']);
        exit;
    }
    
    // Mettre à jour les commodités
    $result = $propertyModel->updateAmenities($propertyId, $amenities);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Commodités mises à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour des commodités']);
    }
}

/**
 * Gérer la mise à jour des disponibilités
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleUpdateAvailability($propertyModel, $userId, $userRole) {
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id']) || !isset($_POST['availability']) || !is_array($_POST['availability'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété et disponibilités requis']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    $availability = $_POST['availability'];
    
    // Récupérer la propriété
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Vérifier les droits d'accès
    $canUpdate = false;
    
    if ($userRole == 'admin') {
        $canUpdate = true;
    } else if ($userRole == 'owner' && $property['owner_id'] == $userId) {
        $canUpdate = true;
    }
    
    if (!$canUpdate) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette propriété']);
        exit;
    }
    
    // Mettre à jour les disponibilités
    $result = $propertyModel->updateAvailability($propertyId, $availability);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Disponibilités mises à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour des disponibilités']);
    }
}

/**
 * Gérer la mise en avant d'une propriété
 * @param Property $propertyModel Instance du modèle Property
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 */
function handleToggleFeatured($propertyModel, $userId, $userRole) {
    // Vérifier que l'utilisateur est un administrateur
    if ($userRole != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les administrateurs peuvent mettre en avant les propriétés']);
        exit;
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['property_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété manquant']);
        exit;
    }
    
    $propertyId = (int)$_POST['property_id'];
    $featured = isset($_POST['featured']) ? (bool)$_POST['featured'] : false;
    
    // Récupérer la propriété
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété introuvable']);
        exit;
    }
    
    // Mettre à jour la propriété
    $result = $propertyModel->update($propertyId, ['featured' => $featured]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Propriété ' . ($featured ? 'mise en avant' : 'retirée de la mise en avant') . ' avec succès'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la propriété']);
    }
}