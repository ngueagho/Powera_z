/**
 * Gestion des cartes avec Leaflet
 * HouseConnect - Application de location immobilière
 */

/**
 * Initialiser une carte Leaflet simple
 * @param {string} elementId ID de l'élément conteneur de la carte
 * @param {number} lat Latitude du centre de la carte
 * @param {number} lng Longitude du centre de la carte
 * @param {number} zoom Niveau de zoom initial
 * @returns {object} Instance de la carte Leaflet
 */
function initMap(elementId, lat = 48.856614, lng = 2.3522219, zoom = 13) {
    const mapElement = document.getElementById(elementId);
    
    if (!mapElement) {
        console.error(`Élément #${elementId} non trouvé`);
        return null;
    }
    
    // Initialiser la carte
    const map = L.map(elementId).setView([lat, lng], zoom);
    
    // Ajouter la couche de tuiles (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    
    return map;
}

/**
 * Ajouter un marqueur à une carte
 * @param {object} map Instance de la carte Leaflet
 * @param {number} lat Latitude du marqueur
 * @param {number} lng Longitude du marqueur
 * @param {string} title Titre du marqueur (pour le popup)
 * @param {string} content Contenu HTML du popup (optionnel)
 * @returns {object} Instance du marqueur
 */
function addMarker(map, lat, lng, title, content = null) {
    if (!map) {
        console.error('Instance de carte non fournie');
        return null;
    }
    
    // Créer le marqueur
    const marker = L.marker([lat, lng]).addTo(map);
    
    // Ajouter un popup si un titre est fourni
    if (title) {
        const popupContent = content || `<b>${title}</b>`;
        marker.bindPopup(popupContent);
    }
    
    return marker;
}

/**
 * Initialiser une carte avec plusieurs propriétés
 * @param {string} elementId ID de l'élément conteneur de la carte
 * @param {array} properties Tableau d'objets propriétés
 * @param {number} zoom Niveau de zoom initial
 * @returns {object} Instance de la carte Leaflet
 */
function initPropertiesMap(elementId, properties, zoom = 12) {
    if (!properties || properties.length === 0) {
        console.error('Aucune propriété fournie');
        return null;
    }
    
    // Calculer le centre de la carte (moyenne des coordonnées)
    let sumLat = 0;
    let sumLng = 0;
    let validProperties = 0;
    
    properties.forEach(property => {
        if (property.latitude && property.longitude) {
            sumLat += parseFloat(property.latitude);
            sumLng += parseFloat(property.longitude);
            validProperties++;
        }
    });
    
    if (validProperties === 0) {
        // Aucune propriété avec coordonnées valides, utiliser Paris par défaut
        return initMap(elementId);
    }
    
    const centerLat = sumLat / validProperties;
    const centerLng = sumLng / validProperties;
    
    // Initialiser la carte
    const map = initMap(elementId, centerLat, centerLng, zoom);
    
    // Ajouter les marqueurs pour chaque propriété
    const markers = [];
    properties.forEach(property => {
        if (property.latitude && property.longitude) {
            // Créer le contenu du popup
            const popupContent = `
                <div class="map-popup">
                    <h3>${property.title}</h3>
                    <p>${property.address}, ${property.city}</p>
                    <p class="price"><strong>${formatPrice(property.price)} €</strong></p>
                    <a href="views/properties/detail.php?id=${property.id}" class="btn btn-sm btn-primary">Voir détails</a>
                </div>
            `;
            
            // Ajouter le marqueur
            const marker = addMarker(map, property.latitude, property.longitude, null, popupContent);
            markers.push(marker);
        }
    });
    
    // Ajuster la vue pour inclure tous les marqueurs
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
    
    return map;
}

/**
 * Initialiser une carte pour une propriété unique avec son adresse
 * @param {string} elementId ID de l'élément conteneur de la carte
 * @param {object} property Objet propriété
 * @returns {object} Instance de la carte Leaflet
 */
function initPropertyMap(elementId, property) {
    if (!property || !property.latitude || !property.longitude) {
        console.error('Propriété invalide ou sans coordonnées');
        return null;
    }
    
    // Initialiser la carte
    const map = initMap(elementId, property.latitude, property.longitude, 15);
    
    // Créer le contenu du popup
    const popupContent = `
        <div class="map-popup">
            <h3>${property.title}</h3>
            <p>${property.address}, ${property.city}</p>
        </div>
    `;
    
    // Ajouter le marqueur
    addMarker(map, property.latitude, property.longitude, null, popupContent).openPopup();
    
    return map;
}

/**
 * Rechercher des coordonnées à partir d'une adresse
 * @param {string} address Adresse à géocoder
 * @param {function} callback Fonction de rappel(lat, lng)
 */
function geocodeAddress(address, callback) {
    // Utiliser le service de géocodage Nominatim (OpenStreetMap)
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                callback(lat, lng);
            } else {
                callback(null, null);
            }
        })
        .catch(error => {
            console.error('Erreur de géocodage:', error);
            callback(null, null);
        });
}

/**
 * Initialiser un sélecteur de localisation sur la carte
 * @param {string} elementId ID de l'élément conteneur de la carte
 * @param {function} onLocationSelect Fonction de rappel(lat, lng, address)
 * @returns {object} Instance de la carte Leaflet
 */
function initLocationPicker(elementId, onLocationSelect) {
    // Initialiser la carte
    const map = initMap(elementId);
    
    // Ajouter un marqueur temporaire
    let tempMarker = null;
    
    // Événement de clic sur la carte
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Supprimer l'ancien marqueur
        if (tempMarker) {
            map.removeLayer(tempMarker);
        }
        
        // Ajouter un nouveau marqueur
        tempMarker = L.marker([lat, lng]).addTo(map);
        
        // Faire une géolocalisation inverse pour obtenir l'adresse
        reverseGeocode(lat, lng, function(address) {
            if (address) {
                tempMarker.bindPopup(`<b>${address}</b>`).openPopup();
            }
            
            // Appeler la fonction de rappel
            if (onLocationSelect) {
                onLocationSelect(lat, lng, address);
            }
        });
    });
    
    return map;
}

/**
 * Géocodage inverse (coordonnées vers adresse)
 * @param {number} lat Latitude
 * @param {number} lng Longitude
 * @param {function} callback Fonction de rappel(address)
 */
function reverseGeocode(lat, lng, callback) {
    // Utiliser le service de géocodage inverse Nominatim (OpenStreetMap)
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.display_name) {
                callback(data.display_name);
            } else {
                callback(null);
            }
        })
        .catch(error => {
            console.error('Erreur de géocodage inverse:', error);
            callback(null);
        });
}

/**
 * Calculer un itinéraire entre deux points
 * @param {object} map Instance de la carte Leaflet
 * @param {number} fromLat Latitude du point de départ
 * @param {number} fromLng Longitude du point de départ
 * @param {number} toLat Latitude du point d'arrivée
 * @param {number} toLng Longitude du point d'arrivée
 * @param {function} callback Fonction de rappel(routeInfo)
 */
function calculateRoute(map, fromLat, fromLng, toLat, toLng, callback) {
    // Vérifier si le plugin Leaflet Routing Machine est disponible
    if (!L.Routing) {
        console.error('Leaflet Routing Machine non disponible');
        return;
    }
    
    // Créer le contrôle d'itinéraire
    const control = L.Routing.control({
        waypoints: [
            L.latLng(fromLat, fromLng),
            L.latLng(toLat, toLng)
        ],
        routeWhileDragging: true,
        showAlternatives: true,
        fitSelectedRoutes: true,
        lineOptions: {
            styles: [{ color: '#4a6ee0', weight: 4 }]
        }
    }).addTo(map);
    
    // Événement de calcul de route
    control.on('routesfound', function(e) {
        const routes = e.routes;
        const routeInfo = {
            distance: routes[0].summary.totalDistance, // en mètres
            time: routes[0].summary.totalTime, // en secondes
            instructions: routes[0].instructions
        };
        
        if (callback) {
            callback(routeInfo);
        }
    });
    
    return control;
}

/**
 * Obtenir la position actuelle de l'utilisateur
 * @param {function} callback Fonction de rappel(lat, lng)
 */
function getCurrentPosition(callback) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                callback(lat, lng);
            },
            function(error) {
                console.error('Erreur de géolocalisation:', error);
                callback(null, null);
            }
        );
    } else {
        console.error('Géolocalisation non supportée');
        callback(null, null);
    }
}

/**
 * Formater la durée en texte
 * @param {number} seconds Durée en secondes
 * @returns {string} Durée formatée
 */
function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (hours > 0) {
        return `${hours} h ${minutes} min`;
    } else {
        return `${minutes} min`;
    }
}

/**
 * Formater la distance en texte
 * @param {number} meters Distance en mètres
 * @returns {string} Distance formatée
 */
function formatDistance(meters) {
    if (meters >= 1000) {
        return `${(meters / 1000).toFixed(1)} km`;
    } else {
        return `${Math.round(meters)} m`;
    }
}