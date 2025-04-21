/**
 * JavaScript principal
 * HouseConnect - Application de location immobilière
 */

document.addEventListener('DOMContentLoaded', function() {
    // Animation des propriétés au scroll
    animatePropertiesOnScroll();
    
    // Initialiser les dropdowns
    initDropdowns();
    
    // Initialiser le menu mobile
    initMobileMenu();
    
    // Initialiser les flash messages
    initFlashMessages();
    
    // Initialiser les onglets
    initTabs();
    
    // Initialiser les modals
    initModals();
    
    // Initialiser les formulaires AJAX
    initAjaxForms();
    
    // Initialiser les sliders
    initSliders();
    
    // Initialiser les tooltips
    initTooltips();
});

/**
 * Animer les cartes de propriétés au scroll
 */
function animatePropertiesOnScroll() {
    const propertyCards = document.querySelectorAll('.property-card');
    
    if (propertyCards.length > 0) {
        // Observer pour l'effet d'apparition au scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });
        
        propertyCards.forEach(card => {
            observer.observe(card);
        });
    }
}

/**
 * Initialiser les menus déroulants
 */
function initDropdowns() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdownMenu = this.nextElementSibling;
            
            // Fermer tous les autres dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                if (menu !== dropdownMenu) {
                    menu.classList.remove('show');
                }
            });
            
            dropdownMenu.classList.toggle('show');
        });
    });
    
    // Fermer les dropdowns en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
}

/**
 * Initialiser le menu mobile
 */
function initMobileMenu() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            document.querySelector('header').classList.toggle('mobile-menu-open');
        });
    }
}

/**
 * Initialiser les messages flash
 */
function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    
    flashMessages.forEach(message => {
        const closeButton = message.querySelector('.close-flash');
        
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                message.style.opacity = '0';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 300);
            });
        }
        
        // Auto-disparition après 5 secondes
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.display = 'none';
            }, 300);
        }, 5000);
    });
}

/**
 * Initialiser les onglets
 */
function initTabs() {
    const tabLinks = document.querySelectorAll('.tab-link');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const tabId = this.getAttribute('data-tab');
            const tabContainer = this.closest('.tabs-container');
            
            // Désactiver tous les onglets
            tabContainer.querySelectorAll('.tab-link').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Cacher tous les contenus
            tabContainer.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Activer l'onglet cliqué
            this.classList.add('active');
            
            // Afficher le contenu correspondant
            document.getElementById(tabId).classList.add('active');
        });
    });
}

/**
 * Initialiser les fenêtres modales
 */
function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                // Ouvrir la modal
                modal.classList.add('show');
                document.body.classList.add('modal-open');
                
                // Gestion de la fermeture
                const closeButtons = modal.querySelectorAll('.modal-close');
                
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        modal.classList.remove('show');
                        document.body.classList.remove('modal-open');
                    });
                });
                
                // Fermer en cliquant sur l'arrière-plan
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.remove('show');
                        document.body.classList.remove('modal-open');
                    }
                });
            }
        });
    });
}

/**
 * Initialiser les formulaires AJAX
 */
function initAjaxForms() {
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('[type="submit"]');
            const action = this.getAttribute('action');
            const method = this.getAttribute('method') || 'POST';
            
            // Désactiver le bouton pendant la soumission
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="loading"></span> Traitement...';
            }
            
            // Effacer les messages d'erreur précédents
            this.querySelectorAll('.form-error').forEach(error => {
                error.remove();
            });
            
            // Soumission AJAX
            fetch(action, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.getAttribute('data-original-text') || 'Envoyer';
                }
                
                if (data.success) {
                    // Succès
                    if (data.message) {
                        showNotification(data.message, 'success');
                    }
                    
                    // Redirection si nécessaire
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                    
                    // Reset du formulaire
                    if (!data.redirect && form.getAttribute('data-reset') !== 'false') {
                        form.reset();
                    }
                } else {
                    // Erreur
                    if (data.message) {
                        showNotification(data.message, 'error');
                    }
                    
                    // Afficher les erreurs de validation
                    if (data.errors) {
                        for (const field in data.errors) {
                            const input = form.querySelector(`[name="${field}"]`);
                            if (input) {
                                const errorElement = document.createElement('div');
                                errorElement.className = 'form-error';
                                errorElement.textContent = data.errors[field];
                                input.parentNode.appendChild(errorElement);
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.getAttribute('data-original-text') || 'Envoyer';
                }
                
                showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
            });
        });
        
        // Sauvegarder le texte original des boutons
        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.setAttribute('data-original-text', submitButton.innerHTML);
        }
    });
}

/**
 * Initialiser les sliders
 */
function initSliders() {
    const sliders = document.querySelectorAll('.slider');
    
    sliders.forEach(slider => {
        const slidesContainer = slider.querySelector('.slides');
        const prevButton = slider.querySelector('.slider-prev');
        const nextButton = slider.querySelector('.slider-next');
        const pagination = slider.querySelector('.slider-pagination');
        
        if (slidesContainer) {
            const slides = slidesContainer.children;
            let currentIndex = 0;
            
            // Créer la pagination
            if (pagination) {
                for (let i = 0; i < slides.length; i++) {
                    const dot = document.createElement('span');
                    dot.className = 'slider-dot';
                    if (i === 0) {
                        dot.classList.add('active');
                    }
                    dot.addEventListener('click', () => {
                        goToSlide(i);
                    });
                    pagination.appendChild(dot);
                }
            }
            
            // Fonction pour aller à un slide spécifique
            function goToSlide(index) {
                if (index < 0) {
                    index = slides.length - 1;
                } else if (index >= slides.length) {
                    index = 0;
                }
                
                slidesContainer.style.transform = `translateX(-${index * 100}%)`;
                currentIndex = index;
                
                // Mettre à jour la pagination
                if (pagination) {
                    const dots = pagination.querySelectorAll('.slider-dot');
                    dots.forEach((dot, i) => {
                        if (i === currentIndex) {
                            dot.classList.add('active');
                        } else {
                            dot.classList.remove('active');
                        }
                    });
                }
            }
            
            // Gestion des boutons
            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    goToSlide(currentIndex - 1);
                });
            }
            
            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    goToSlide(currentIndex + 1);
                });
            }
            
            // Swipe sur mobile
            let touchStartX = 0;
            let touchEndX = 0;
            
            slidesContainer.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            slidesContainer.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                
                if (touchStartX - touchEndX > 50) {
                    // Swipe gauche
                    goToSlide(currentIndex + 1);
                } else if (touchEndX - touchStartX > 50) {
                    // Swipe droite
                    goToSlide(currentIndex - 1);
                }
            });
        }
    });
}

/**
 * Initialiser les tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'tooltip';
            tooltipElement.textContent = text;
            
            document.body.appendChild(tooltipElement);
            
            const rect = this.getBoundingClientRect();
            const tooltipRect = tooltipElement.getBoundingClientRect();
            
            tooltipElement.style.top = `${rect.top - tooltipRect.height - 10 + window.scrollY}px`;
            tooltipElement.style.left = `${rect.left + (rect.width / 2) - (tooltipRect.width / 2) + window.scrollX}px`;
            tooltipElement.style.opacity = '1';
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipElement = document.querySelector('.tooltip');
            if (tooltipElement) {
                tooltipElement.style.opacity = '0';
                setTimeout(() => {
                    tooltipElement.remove();
                }, 300);
            }
        });
    });
}

/**
 * Afficher une notification
 * @param {string} message Message à afficher
 * @param {string} type Type de notification (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Supprimer les anciennes notifications
    const oldNotifications = document.querySelectorAll('.notification-container');
    oldNotifications.forEach(notification => {
        notification.remove();
    });
    
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `notification-container ${type}`;
    notification.innerHTML = `
        <div class="notification notification-${type}">
            <div class="notification-content">
                ${message}
            </div>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Afficher la notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Gestion de la fermeture
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
    
    // Auto-disparition après 5 secondes
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

/**
 * Charger dynamiquement des propriétés via AJAX
 * @param {string} url URL de l'API
 * @param {object} filters Filtres à appliquer
 * @param {string} containerId ID du conteneur où afficher les propriétés
 * @param {function} callback Fonction de rappel après le chargement
 */
function loadProperties(url, filters = {}, containerId = 'property-grid', callback = null) {
    const container = document.getElementById(containerId);
    
    if (!container) {
        console.error(`Conteneur #${containerId} non trouvé`);
        return;
    }
    
    // Afficher un indicateur de chargement
    container.innerHTML = '<div class="loading-container"><div class="loading loading-lg"></div><p>Chargement des propriétés...</p></div>';
    
    // Construire l'URL avec les filtres
    const queryParams = new URLSearchParams();
    
    for (const key in filters) {
        if (filters[key] !== null && filters[key] !== '') {
            queryParams.append(key, filters[key]);
        }
    }
    
    const finalUrl = `${url}?${queryParams.toString()}`;
    
    // Requête AJAX
    fetch(finalUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Vider le conteneur
                container.innerHTML = '';
                
                if (data.data.length === 0) {
                    container.innerHTML = '<div class="no-results">Aucune propriété ne correspond à vos critères.</div>';
                    return;
                }
                
                // Afficher les propriétés
                data.data.forEach(property => {
                    const propertyCard = createPropertyCard(property);
                    container.appendChild(propertyCard);
                });
                
                // Animation des cartes
                animatePropertiesOnScroll();
                
                // Ajouter la pagination si nécessaire
                if (data.meta && data.meta.pages > 1) {
                    const pagination = createPagination(data.meta.page, data.meta.pages, filters);
                    container.parentNode.appendChild(pagination);
                }
                
                // Callback si fourni
                if (callback && typeof callback === 'function') {
                    callback(data);
                }
            } else {
                container.innerHTML = `<div class="error-message">${data.message || 'Erreur lors du chargement des propriétés'}</div>`;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            container.innerHTML = '<div class="error-message">Une erreur est survenue lors du chargement des propriétés.</div>';
        });
}

/**
 * Créer une carte de propriété
 * @param {object} property Données de la propriété
 * @returns {HTMLElement} Élément HTML de la carte
 */
function createPropertyCard(property) {
    const propertyTypes = {
        'apartment': 'Appartement',
        'house': 'Maison',
        'villa': 'Villa',
        'studio': 'Studio',
        'room': 'Chambre'
    };
    
    const card = document.createElement('div');
    card.className = 'property-card';
    
    // Image principale ou image par défaut
    let mainImage = 'assets/images/no-image.jpg';
    if (property.images && property.images.length > 0) {
        mainImage = property.images[0].is_main 
            ? `uploads/properties/${property.images[0].image_path}` 
            : `uploads/properties/${property.images[0].image_path}`;
    }
    
    card.innerHTML = `
        <div class="property-image">
            <img src="${mainImage}" alt="${property.title}">
            <span class="property-price">${formatPrice(property.price)} FCFA</span>
        </div>
        <div class="property-details">
            <h3>${property.title}</h3>
            <p class="property-location"><i class="fa fa-map-marker"></i> ${property.city}</p>
            <div class="property-info">
                <span><i class="fa fa-home"></i> ${propertyTypes[property.property_type] || property.property_type}</span>
                <span><i class="fa fa-bed"></i> ${property.rooms} pièces</span>
                <span><i class="fa fa-bath"></i> ${property.bathrooms} SDB</span>
                <span><i class="fa fa-expand"></i> ${property.surface} m²</span>
            </div>
            <a href="views/properties/detail.php?id=${property.id}" class="btn btn-secondary">Voir détails</a>
        </div>
    `;
    
    return card;
}

/**
 * Créer une pagination
 * @param {number} currentPage Page actuelle
 * @param {number} totalPages Nombre total de pages
 * @param {object} filters Filtres actuels
 * @returns {HTMLElement} Élément HTML de la pagination
 */
function createPagination(currentPage, totalPages, filters = {}) {
    const pagination = document.createElement('div');
    pagination.className = 'pagination';
    
    // Premier et précédent
    if (currentPage > 1) {
        const first = document.createElement('a');
        first.href = '#';
        first.textContent = '«';
        first.addEventListener('click', e => {
            e.preventDefault();
            filters.page = 1;
            loadProperties(window.location.pathname, filters);
        });
        pagination.appendChild(first);
        
        const prev = document.createElement('a');
        prev.href = '#';
        prev.textContent = '‹';
        prev.addEventListener('click', e => {
            e.preventDefault();
            filters.page = currentPage - 1;
            loadProperties(window.location.pathname, filters);
        });
        pagination.appendChild(prev);
    }
    
    // Pages
    const maxPages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
    let endPage = Math.min(totalPages, startPage + maxPages - 1);
    
    if (endPage - startPage + 1 < maxPages) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            const current = document.createElement('span');
            current.className = 'current';
            current.textContent = i;
            pagination.appendChild(current);
        } else {
            const page = document.createElement('a');
            page.href = '#';
            page.textContent = i;
            page.addEventListener('click', e => {
                e.preventDefault();
                filters.page = i;
                loadProperties(window.location.pathname, filters);
            });
            pagination.appendChild(page);
        }
    }
    
    // Suivant et dernier
    if (currentPage < totalPages) {
        const next = document.createElement('a');
        next.href = '#';
        next.textContent = '›';
        next.addEventListener('click', e => {
            e.preventDefault();
            filters.page = currentPage + 1;
            loadProperties(window.location.pathname, filters);
        });
        pagination.appendChild(next);
        
        const last = document.createElement('a');
        last.href = '#';
        last.textContent = '»';
        last.addEventListener('click', e => {
            e.preventDefault();
            filters.page = totalPages;
            loadProperties(window.location.pathname, filters);
        });
        pagination.appendChild(last);
    }
    
    return pagination;
}

/**
 * Formater un prix
 * @param {number} price Prix à formater
 * @returns {string} Prix formaté
 */
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR').format(price);
}

/**
 * Intialiser les filtres de recherche
 * @param {string} formId ID du formulaire de filtres
 * @param {string} apiUrl URL de l'API de propriétés
 * @param {string} containerId ID du conteneur des propriétés
 */
function initSearchFilters(formId, apiUrl, containerId = 'property-grid') {
    const form = document.getElementById(formId);
    
    if (!form) {
        console.error(`Formulaire #${formId} non trouvé`);
        return;
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const filters = {};
        
        // Convertir FormData en objet
        for (const [key, value] of formData.entries()) {
            filters[key] = value;
        }
        
        // Reset de la page
        filters.page = 1;
        
        // Charger les propriétés
        loadProperties(apiUrl, filters, containerId);
        
        // Mettre à jour l'URL avec les filtres
        const queryParams = new URLSearchParams();
        for (const key in filters) {
            if (filters[key] !== null && filters[key] !== '') {
                queryParams.append(key, filters[key]);
            }
        }
        
        const newUrl = `${window.location.pathname}?${queryParams.toString()}`;
        window.history.pushState({ filters }, '', newUrl);
    });
    
    // Gestion du retour arrière du navigateur
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.filters) {
            loadProperties(apiUrl, e.state.filters, containerId);
        } else {
            // Si pas d'état, charger avec les paramètres de l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const filters = {};
            
            urlParams.forEach((value, key) => {
                filters[key] = value;
            });
            
            loadProperties(apiUrl, filters, containerId);
        }
    });
    
    // Charger les propriétés au chargement de la page
    const urlParams = new URLSearchParams(window.location.search);
    const initialFilters = {};
    
    urlParams.forEach((value, key) => {
        initialFilters[key] = value;
        
        // Pré-remplir le formulaire
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = value;
        }
    });
    
    loadProperties(apiUrl, initialFilters, containerId);
}