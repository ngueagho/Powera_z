<?php
/**
 * API de gestion des propriétés
 * Permet d'ajouter, modifier, supprimer et récupérer des propriétés
 */

// Inclure les fichiers nécessaires
require_once('../config/config.php');
require_once('../models/Database.php');
require_once('../models/User.php');
require_once('../models/Property.php');

// Démarrer la session
session_start();

// Définir l'en-tête JSON pour les réponses API
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION[SESSION_PREFIX . 'logged_in']) || !$_SESSION[SESSION_PREFIX . 'logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Initialiser les modèles
$propertyModel = new Property();
$userModel = new User();

// Traiter en fonction de la méthode HTTP
switch ($method) {
    case 'GET':
        handleGetRequest($propertyModel);
        break;
        
    case 'POST':
        handlePostRequest($propertyModel);
        break;
        
    case 'PUT':
        handlePutRequest($propertyModel);
        break;
        
    case 'DELETE':
        handleDeleteRequest($propertyModel);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Méthode non supportée']);
        exit;
}

/**
 * Gérer les requêtes GET
 * @param Property $propertyModel Instance du modèle Property
 */
function handleGetRequest($propertyModel) {
    // Paramètres possibles
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $owner_id = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Filtres de recherche
    $filters = [];
    
    // Récupérer tous les paramètres GET comme filtres
    foreach ($_GET as $key => $value) {
        if (!in_array($key, ['id', 'page', 'limit'])) {
            $filters[$key] = $value;
        }
    }
    
    // Si un ID est spécifié, récupérer une seule propriété
    if ($id) {
        $property = $propertyModel->getDetailedById($id);
        
        if ($property) {
            // Enregistrer la vue
            $propertyModel->recordView($id, $_SESSION[SESSION_PREFIX . 'user_id'] ?? null, $_SERVER['REMOTE_ADDR']);
            
            echo json_encode(['success' => true, 'data' => $property]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Propriété non trouvée']);
        }
        exit;
    }
    
    // Si un owner_id est spécifié, filtrer par propriétaire
    if ($owner_id) {
        $filters['owner_id'] = $owner_id;
        
        // Vérifier si l'utilisateur est le propriétaire ou un admin
        $isOwner = $_SESSION[SESSION_PREFIX . 'user_id'] == $owner_id;
        $isAdmin = $_SESSION[SESSION_PREFIX . 'user_role'] == 'admin';
        
        if (!$isOwner && !$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }
    }
    
    // Pagination
    $offset = ($page - 1) * $limit;
    $filters['limit'] = $limit;
    $filters['offset'] = $offset;
    
    // Récupérer les propriétés
    $properties = $propertyModel->getAll($filters);
    $totalProperties = $propertyModel->countAll($filters);
    
    // Préparer les métadonnées de pagination
    $totalPages = ceil($totalProperties / $limit);
    
    echo json_encode([
        'success' => true, 
        'data' => $properties,
        'meta' => [
            'total' => $totalProperties,
            'page' => $page,
            'limit' => $limit,
            'pages' => $totalPages
        ]
    ]);
}

/**
 * Gérer les requêtes POST (création)
 * @param Property $propertyModel Instance du modèle Property
 */
function handlePostRequest($propertyModel) {
    // Vérifier si l'utilisateur est un propriétaire ou un admin
    $userRole = $_SESSION[SESSION_PREFIX . 'user_role'];
    if ($userRole !== 'owner' && $userRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seuls les propriétaires peuvent ajouter des propriétés']);
        exit;
    }
    
    // Pour les requêtes AJAX/Fetch avec content-type application/json
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    // Si le corps de la requête est au format JSON, l'utiliser; sinon, utiliser $_POST
    $data = $input ? $input : $_POST;
    
    // Vérifier les champs requis
    $requiredFields = ['title', 'description', 'property_type', 'rooms', 'bathrooms', 'surface', 'price', 'address', 'city'];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
            exit;
        }
    }
    
    // Préparation des données de la propriété
    $propertyData = [
        'owner_id' => $_SESSION[SESSION_PREFIX . 'user_id'],
        'title' => htmlspecialchars($data['title']),
        'description' => htmlspecialchars($data['description']),
        'property_type' => $data['property_type'],
        'rooms' => (int)$data['rooms'],
        'bathrooms' => (int)$data['bathrooms'],
        'surface' => (float)$data['surface'],
        'price' => (float)$data['price'],
        'address' => htmlspecialchars($data['address']),
        'city' => htmlspecialchars($data['city']),
        'postal_code' => isset($data['postal_code']) ? htmlspecialchars($data['postal_code']) : null,
        'latitude' => isset($data['latitude']) ? (float)$data['latitude'] : null,
        'longitude' => isset($data['longitude']) ? (float)$data['longitude'] : null,
        'featured' => isset($data['featured']) ? (bool)$data['featured'] : false,
        'status' => $userRole === 'admin' ? 'available' : 'pending_approval'
    ];
    
    // Créer la propriété
    $propertyId = $propertyModel->create($propertyData);
    
    if ($propertyId) {
        // Si des commodités sont spécifiées, les associer à la propriété
        if (isset($data['amenities']) && is_array($data['amenities'])) {
            $propertyModel->updateAmenities($propertyId, $data['amenities']);
        }
        
        // Si des disponibilités sont spécifiées, les ajouter
        if (isset($data['availability']) && is_array($data['availability'])) {
            $propertyModel->updateAvailability($propertyId, $data['availability']);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Propriété ajoutée avec succès',
            'data' => ['id' => $propertyId]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la propriété']);
    }
}

/**
 * Gérer les requêtes PUT (mise à jour)
 * @param Property $propertyModel Instance du modèle Property
 */
function handlePutRequest($propertyModel) {
    // Pour les requêtes AJAX/Fetch avec content-type application/json
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    // Si le corps de la requête est au format JSON, l'utiliser; sinon, utiliser $_POST
    $data = $input ? $input : $_POST;
    
    // Vérifier si l'ID est spécifié
    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété non spécifié']);
        exit;
    }
    
    $propertyId = (int)$data['id'];
    
    // Récupérer la propriété existante
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété non trouvée']);
        exit;
    }
    
    // Vérifier si l'utilisateur est le propriétaire ou un admin
    $isOwner = $_SESSION[SESSION_PREFIX . 'user_id'] == $property['owner_id'];
    $isAdmin = $_SESSION[SESSION_PREFIX . 'user_role'] == 'admin';
    
    if (!$isOwner && !$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }
    
    // Préparation des données à mettre à jour
    $propertyData = [];
    
    // Champs pouvant être mis à jour
    $updatableFields = [
        'title', 'description', 'property_type', 'rooms', 'bathrooms', 'surface', 
        'price', 'address', 'city', 'postal_code', 'latitude', 'longitude', 
        'featured', 'status'
    ];
    
    foreach ($updatableFields as $field) {
        if (isset($data[$field])) {
            if (in_array($field, ['title', 'description', 'address', 'city', 'postal_code'])) {
                $propertyData[$field] = htmlspecialchars($data[$field]);
            } else {
                $propertyData[$field] = $data[$field];
            }
        }
    }
    
    // S'assurer que seul un admin peut changer le statut en 'available'
    if (isset($propertyData['status']) && $propertyData['status'] === 'available' && !$isAdmin) {
        $propertyData['status'] = 'pending_approval';
    }
    
    // Mettre à jour la propriété
    $result = $propertyModel->update($propertyId, $propertyData);
    
    if ($result) {
        // Si des commodités sont spécifiées, les mettre à jour
        if (isset($data['amenities']) && is_array($data['amenities'])) {
            $propertyModel->updateAmenities($propertyId, $data['amenities']);
        }
        
        // Si des disponibilités sont spécifiées, les mettre à jour
        if (isset($data['availability']) && is_array($data['availability'])) {
            $propertyModel->updateAvailability($propertyId, $data['availability']);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Propriété mise à jour avec succès'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la propriété']);
    }
}

/**
 * Gérer les requêtes DELETE
 * @param Property $propertyModel Instance du modèle Property
 */
function handleDeleteRequest($propertyModel) {
    // Pour les requêtes AJAX/Fetch avec content-type application/json
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    // Si le corps de la requête est au format JSON, l'utiliser; sinon, utiliser $_REQUEST
    $data = $input ? $input : $_REQUEST;
    
    // Vérifier si l'ID est spécifié
    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de propriété non spécifié']);
        exit;
    }
    
    $propertyId = (int)$data['id'];
    
    // Récupérer la propriété existante
    $property = $propertyModel->getById($propertyId);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Propriété non trouvée']);
        exit;
    }
    
    // Vérifier si l'utilisateur est le propriétaire ou un admin
    $isOwner = $_SESSION[SESSION_PREFIX . 'user_id'] == $property['owner_id'];
    $isAdmin = $_SESSION[SESSION_PREFIX . 'user_role'] == 'admin';
    
    if (!$isOwner && !$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }
    
    // Supprimer la propriété
    $result = $propertyModel->delete($propertyId);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Propriété supprimée avec succès'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la propriété']);
    }
}