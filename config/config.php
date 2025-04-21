<?php
/**
 * Configuration de la base de données
 * HouseConnect - Application de location immobilière
 */

// Informations de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'houseconnect');
define('DB_USER', 'root');
define('DB_PASS', '');

// Paramètres de l'application
define('APP_NAME', 'HouseConnect');
define('APP_URL', 'http://localhost/houseconnect');
define('APP_EMAIL', 'contact@houseconnect.com');
define('APP_CURRENCY', 'FCFA'); // Devise utilisée

// Chemins
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('PROPERTY_UPLOADS', UPLOADS_PATH . 'properties/');
define('USER_UPLOADS', UPLOADS_PATH . 'users/');

// Paramètres de sécurité
define('SESSION_PREFIX', 'hc_');
define('TOKEN_LIFETIME', 3600); // 1 heure
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60); // 15 minutes

// Commission sur les paiements (en pourcentage)
define('COMMISSION_RATE', 5); // 5%

// Tailles maximales (en octets)
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('MAX_IMAGES_PER_PROPERTY', 10);

// Activation des fonctionnalités
define('ENABLE_OTP', false);
define('ENABLE_PAYMENTS', true);
define('ENABLE_WEBRTC', true);