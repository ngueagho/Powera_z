<?php
/**
 * En-tête du site
 * HouseConnect - Application de location immobilière
 */

// Si la session n'est pas démarrée, la démarrer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déterminer le chemin relatif vers la racine
$rootPath = '';
$currentDir = dirname($_SERVER['PHP_SELF']);
$dirCount = substr_count($currentDir, '/');
if ($dirCount > 1) {
    $rootPath = str_repeat('../', $dirCount - 1);
}

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION[SESSION_PREFIX . 'logged_in']) && $_SESSION[SESSION_PREFIX . 'logged_in'];
$userRole = $isLoggedIn ? $_SESSION[SESSION_PREFIX . 'user_role'] : '';
$userName = $isLoggedIn ? $_SESSION[SESSION_PREFIX . 'user_name'] : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HouseConnect - Trouvez votre logement idéal</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= $rootPath ?>assets/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $rootPath ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= $rootPath ?>assets/css/responsive.css">
    <link rel="stylesheet" href="<?= $rootPath ?>assets/css/animations.css">
    <!-- <link rel="stylesheet" href="<?= $rootPath ?>assets/css/footer.css"> -->
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Leaflet CSS pour les cartes -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="<?= $rootPath ?>index.php">
                        <img src="<?= $rootPath ?>assets/images/logo.svg" alt="HouseConnect Logo">
                        <span>HouseConnect</span>
                    </a>
                </div>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="<?= $rootPath ?>index.php">Accueil</a></li>
                        <li><a href="<?= $rootPath ?>views/properties/list.php">Logements</a></li>
                        <li><a href="<?= $rootPath ?>views/about.php">À propos</a></li>
                        <li><a href="<?= $rootPath ?>views/contact.php">Contact</a></li>
                    </ul>
                </nav>
                
                <div class="user-actions">
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown">
                            <button class="dropdown-toggle">
                                <i class="fas fa-user-circle"></i> <?= $userName ?> <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <?php if ($userRole === 'admin'): ?>
                                    <a href="<?= $rootPath ?>views/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard Admin</a>
                                <?php elseif ($userRole === 'owner'): ?>
                                    <a href="<?= $rootPath ?>views/dashboard/owner/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                    <a href="<?= $rootPath ?>views/properties/add.php"><i class="fas fa-plus-circle"></i> Ajouter un bien</a>
                                <?php elseif ($userRole === 'tenant'): ?>
                                    <a href="<?= $rootPath ?>views/dashboard/tenant/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                    <a href="<?= $rootPath ?>views/dashboard/tenant/favorites.php"><i class="fas fa-heart"></i> Favoris</a>
                                <?php endif; ?>
                                
                                <a href="<?= $rootPath ?>views/messages/inbox.php"><i class="fas fa-envelope"></i> Messages <span class="badge" id="unread-messages">0</span></a>
                                <a href="<?= $rootPath ?>views/profile/edit.php"><i class="fas fa-user-edit"></i> Profil</a>
                                <a href="#" id="logout-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= $rootPath ?>views/auth/login.php" class="btn btn-outline">Connexion</a>
                        <a href="<?= $rootPath ?>views/auth/register.php" class="btn btn-primary">Inscription</a>
                    <?php endif; ?>
                </div>
                
                <!-- Bouton menu mobile -->
                <div class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </header>
    
    <main>
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?= $_SESSION['flash_message']['type'] ?>">
                <div class="container">
                    <?= $_SESSION['flash_message']['message'] ?>
                    <button class="close-flash"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>