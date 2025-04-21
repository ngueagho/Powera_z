<?php
/**
 * Page de liste des propriétés
 * HouseConnect - Application de location immobilière
 */

// Inclure les fichiers nécessaires
require_once('../../config/config.php');
require_once('../../models/Database.php');
require_once('../../models/Property.php');

// Démarrer la session
session_start();

// Initialiser les modèles
$propertyModel = new Property();

// Récupérer les filtres depuis l'URL
$filters = [];
if (isset($_GET['property_type']) && !empty($_GET['property_type'])) {
    $filters['property_type'] = $_GET['property_type'];
}
if (isset($_GET['city']) && !empty($_GET['city'])) {
    $filters['city'] = $_GET['city'];
}
if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
    $filters['min_price'] = (float)$_GET['min_price'];
}
if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $filters['max_price'] = (float)$_GET['max_price'];
}
if (isset($_GET['min_rooms']) && !empty($_GET['min_rooms'])) {
    $filters['min_rooms'] = (int)$_GET['min_rooms'];
}
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    $filters['sort'] = $_GET['sort'];
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$filters['limit'] = $limit;
$filters['offset'] = $offset;

// Récupérer les propriétés
$properties = $propertyModel->getAll($filters);
$totalProperties = $propertyModel->countAll($filters);

// Calculer les pages
$totalPages = ceil($totalProperties / $limit);

// Types de propriétés
$propertyTypes = [
    'apartment' => 'Appartement',
    'house' => 'Maison',
    'villa' => 'Villa',
    'studio' => 'Studio',
    'room' => 'Chambre'
];

// Options de tri
$sortOptions = [
    'newest' => 'Plus récent',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix décroissant'
];

// Inclure l'en-tête
include('../../includes/header.php');
?>

<section class="properties-section">
    <div class="container">
        <div class="properties-header">
            <h1>Logements à louer</h1>
            <p>Découvrez notre sélection de logements disponibles</p>
        </div>

        <div class="properties-content">
            <!-- Filtres -->
            <div class="properties-filters">
                <div class="filter-header">
                    <h3>Filtres</h3>
                    <a href="list.php" class="reset-filters">Réinitialiser</a>
                </div>
                
                <form id="filter-form" action="list.php" method="GET">
                    <div class="form-group">
                        <label for="property_type" class="form-label">Type de logement</label>
                        <select id="property_type" name="property_type" class="form-control">
                            <option value="">Tous les types</option>
                            <?php foreach ($propertyTypes as $value => $label): ?>
                                <option value="<?= $value ?>" <?= isset($filters['property_type']) && $filters['property_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="city" class="form-label">Ville</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?= isset($filters['city']) ? htmlspecialchars($filters['city']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="min_price" class="form-label">Prix minimum</label>
                        <select id="min_price" name="min_price" class="form-control">
                            <option value="">Aucun minimum</option>
                            <option value="25000" <?= isset($filters['min_price']) && $filters['min_price'] == 25000 ? 'selected' : '' ?>>25 000 FCFA</option>
                            <option value="50000" <?= isset($filters['min_price']) && $filters['min_price'] == 50000 ? 'selected' : '' ?>>50 000 FCFA</option>
                            <option value="100000" <?= isset($filters['min_price']) && $filters['min_price'] == 100000 ? 'selected' : '' ?>>100 000 FCFA</option>
                            <option value="250000" <?= isset($filters['min_price']) && $filters['min_price'] == 250000 ? 'selected' : '' ?>>250 000 FCFA</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_price" class="form-label">Prix maximum</label>
                        <select id="max_price" name="max_price" class="form-control">
                            <option value="">Aucun maximum</option>
                            <option value="100000" <?= isset($filters['max_price']) && $filters['max_price'] == 100000 ? 'selected' : '' ?>>100 000 FCFA</option>
                            <option value="250000" <?= isset($filters['max_price']) && $filters['max_price'] == 250000 ? 'selected' : '' ?>>250 000 FCFA</option>
                            <option value="500000" <?= isset($filters['max_price']) && $filters['max_price'] == 500000 ? 'selected' : '' ?>>500 000 FCFA</option>
                            <option value="1000000" <?= isset($filters['max_price']) && $filters['max_price'] == 1000000 ? 'selected' : '' ?>>1 000 000 FCFA</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_rooms" class="form-label">Nombre de pièces (min)</label>
                        <select id="min_rooms" name="min_rooms" class="form-control">
                            <option value="">Toutes tailles</option>
                            <option value="1" <?= isset($filters['min_rooms']) && $filters['min_rooms'] == 1 ? 'selected' : '' ?>>1+</option>
                            <option value="2" <?= isset($filters['min_rooms']) && $filters['min_rooms'] == 2 ? 'selected' : '' ?>>2+</option>
                            <option value="3" <?= isset($filters['min_rooms']) && $filters['min_rooms'] == 3 ? 'selected' : '' ?>>3+</option>
                            <option value="4" <?= isset($filters['min_rooms']) && $filters['min_rooms'] == 4 ? 'selected' : '' ?>>4+</option>
                            <option value="5" <?= isset($filters['min_rooms']) && $filters['min_rooms'] == 5 ? 'selected' : '' ?>>5+</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Filtrer</button>
                    </div>
                </form>
            </div>
            
            <!-- Liste des propriétés -->
            <div class="properties-list">
                <div class="properties-list-header">
                    <div class="results-count">
                        <?= $totalProperties ?> logement<?= $totalProperties > 1 ? 's' : '' ?> trouvé<?= $totalProperties > 1 ? 's' : '' ?>
                    </div>
                    
                    <div class="properties-sort">
                        <label for="sort">Trier par :</label>
                        <select id="sort" name="sort" class="form-control">
                            <?php foreach ($sortOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= isset($filters['sort']) && $filters['sort'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                        <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                        <button class="view-btn" data-view="map"><i class="fas fa-map-marker-alt"></i></button>
                    </div>
                </div>
                
                <!-- Vue grille par défaut -->
                <div class="view-mode grid-view active">
                    <?php if (empty($properties)): ?>
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <h3>Aucun logement trouvé</h3>
                            <p>Essayez de modifier vos critères de recherche</p>
                        </div>
                    <?php else: ?>
                        <div class="property-grid">
                            <?php foreach ($properties as $property): ?>
                                <div class="property-card">
                                    <div class="property-image">
                                        <?php
                                        // Récupérer l'image principale
                                        $images = $propertyModel->getPropertyImages($property['id']);
                                        $mainImage = !empty($images) ? '../../uploads/properties/' . $images[0]['image_path'] : '../../assets/images/no-image.jpg';
                                        ?>
                                        <img src="<?= $mainImage ?>" alt="<?= $property['title'] ?>">
                                        <span class="property-price"><?= number_format($property['price'], 0, ',', ' ') ?> FCFA</span>
                                    </div>
                                    <div class="property-details">
                                        <h3><?= $property['title'] ?></h3>
                                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?= $property['city'] ?></p>
                                        <div class="property-info">
                                            <span><i class="fas fa-home"></i> <?= $propertyTypes[$property['property_type']] ?? $property['property_type'] ?></span>
                                            <span><i class="fas fa-bed"></i> <?= $property['rooms'] ?> pièces</span>
                                            <span><i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> SDB</span>
                                            <span><i class="fas fa-expand"></i> <?= $property['surface'] ?> m²</span>
                                        </div>
                                        <a href="detail.php?id=<?= $property['id'] ?>" class="btn btn-secondary">Voir détails</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Vue liste (masquée par défaut) -->
                <div class="view-mode list-view">
                    <?php if (empty($properties)): ?>
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <h3>Aucun logement trouvé</h3>
                            <p>Essayez de modifier vos critères de recherche</p>
                        </div>
                    <?php else: ?>
                        <div class="property-list">
                            <?php foreach ($properties as $property): ?>
                                <div class="property-card-list">
                                    <div class="property-image">
                                        <?php
                                        // Récupérer l'image principale
                                        $images = $propertyModel->getPropertyImages($property['id']);
                                        $mainImage = !empty($images) ? '../../uploads/properties/' . $images[0]['image_path'] : '../../assets/images/no-image.jpg';
                                        ?>
                                        <img src="<?= $mainImage ?>" alt="<?= $property['title'] ?>">
                                    </div>
                                    <div class="property-details">
                                        <h3><?= $property['title'] ?></h3>
                                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?= $property['address'] ?>, <?= $property['city'] ?></p>
                                        <div class="property-info">
                                            <span><i class="fas fa-home"></i> <?= $propertyTypes[$property['property_type']] ?? $property['property_type'] ?></span>
                                            <span><i class="fas fa-bed"></i> <?= $property['rooms'] ?> pièces</span>
                                            <span><i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> SDB</span>
                                            <span><i class="fas fa-expand"></i> <?= $property['surface'] ?> m²</span>
                                        </div>
                                        <p class="property-description"><?= substr($property['description'], 0, 150) ?>...</p>
                                    </div>
                                    <div class="property-actions">
                                        <div class="property-price"><?= number_format($property['price'], 0, ',', ' ') ?> FCFA</div>
                                        <a href="detail.php?id=<?= $property['id'] ?>" class="btn btn-secondary">Voir détails</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Vue carte (masquée par défaut) -->
                <div class="view-mode map-view">
                    <div id="properties-map" class="properties-map"></div>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">«</a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹</a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        if ($endPage - $startPage < 4) {
                            $startPage = max(1, $endPage - 4);
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">›</a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">»</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.properties-content {
    display: flex;
    gap: 30px;
    margin-top: 30px;
}

.properties-filters {
    width: 300px;
    flex-shrink: 0;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    padding: 20px;
    align-self: flex-start;
    position: sticky;
    top: 100px;
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filter-header h3 {
    margin-bottom: 0;
}

.reset-filters {
    font-size: 0.875rem;
}

.properties-list {
    flex: 1;
}

.properties-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #dee2e6;
}

.view-toggle {
    display: flex;
    gap: 5px;
}

.view-btn {
    background: none;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.view-btn.active {
    background-color: #4a6ee0;
    color: white;
    border-color: #4a6ee0;
}

.view-mode {
    display: none;
}

.view-mode.active {
    display: block;
}

.property-card-list {
    display: flex;
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.property-card-list:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.property-card-list .property-image {
    width: 250px;
    height: 200px;
    flex-shrink: 0;
}

.property-card-list .property-details {
    flex: 1;
    padding: 20px;
}

.property-card-list .property-actions {
    width: 150px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background-color: #f5f7fb;
}

.property-card-list .property-price {
    font-size: 1.25rem;
    font-weight: 600;
    color: #4a6ee0;
    margin-bottom: 15px;
}

.properties-map {
    height: 700px;
    border-radius: 8px;
    overflow: hidden;
}

.no-results {
    text-align: center;
    padding: 50px 0;
}

.no-results i {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 20px;
}
</style>

<script src="../../assets/js/map.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des vues
    const viewButtons = document.querySelectorAll('.view-btn');
    const viewModes = document.querySelectorAll('.view-mode');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const viewMode = this.getAttribute('data-view');
            
            // Désactiver tous les boutons et modes
            viewButtons.forEach(btn => btn.classList.remove('active'));
            viewModes.forEach(mode => mode.classList.remove('active'));
            
            // Activer le bouton et le mode correspondant
            this.classList.add('active');
            document.querySelector('.' + viewMode + '-view').classList.add('active');
            
            // Initialiser la carte si c'est la vue carte
            if (viewMode === 'map' && !window.propertiesMap) {
                initPropertiesMap();
            }
        });
    });
    
    // Tri des propriétés
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('sort', this.value);
            window.location.href = url.toString();
        });
    }
    
    // Initialisation de la carte
    function initPropertiesMap() {
        // Récupérer les propriétés depuis PHP
        const properties = <?= json_encode($properties) ?>;
        
        // Ajouter les coordonnées aux propriétés
        const propertiesWithCoords = properties.filter(property => property.latitude && property.longitude);
        
        if (propertiesWithCoords.length > 0) {
            window.propertiesMap = initPropertiesMap('properties-map', propertiesWithCoords);
        } else {
            document.querySelector('.map-view').innerHTML = '<div class="no-results"><i class="fas fa-map-marker-alt"></i><h3>Aucun logement avec coordonnées</h3><p>Les propriétés affichées n\'ont pas de coordonnées géographiques.</p></div>';
        }
    }
    
    // Filtres avec soumission automatique lors du changement de valeur
    const filterForm = document.getElementById('filter-form');
    const filterSelects = filterForm.querySelectorAll('select');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
});
</script>

<?php
// Inclure le pied de page
include('../../includes/footer.php');
?>