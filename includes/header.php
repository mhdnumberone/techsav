<?php
/**
 * Site Header Template
 * TechSavvyGenLtd Project
 */

// Ensure config is loaded
if (!defined('SITE_NAME')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$is_admin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
$cart_count = isLoggedIn() ? getCartCount() : 0;
$notifications_count = isLoggedIn() ? getUnreadNotificationsCount() : 0;
?>
<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANGUAGE; ?>" dir="<?php echo isRTL() ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo getSetting('site_description', __('site_description', 'Professional web development services and digital solutions')); ?>">
    <meta name="keywords" content="<?php echo getSetting('site_keywords', 'web development, mobile apps, python scripts, digital products'); ?>">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $page_title ?? SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo getSetting('site_description'); ?>">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/images/og-image.jpg">
    <meta property="og:url" content="<?php echo getCurrentUrl(); ?>">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $page_title ?? SITE_NAME; ?>">
    <meta name="twitter:description" content="<?php echo getSetting('site_description'); ?>">
    <meta name="twitter:image" content="<?php echo ASSETS_URL; ?>/images/twitter-card.jpg">
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <?php if (isRTL()): ?>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    
    <!-- SweetAlert2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo ASSETS_URL; ?>/css/main.css" rel="stylesheet">
    <?php if (isRTL()): ?>
    <link href="<?php echo ASSETS_URL; ?>/css/rtl.css" rel="stylesheet">
    <?php endif; ?>
    
    <?php if ($is_admin): ?>
    <link href="<?php echo ASSETS_URL; ?>/css/admin.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Additional CSS for specific pages -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
        <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo $body_class ?? ''; ?>">

<?php if (!$is_admin): ?>
<!-- Main Site Header -->
<header class="site-header">
    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-2 d-none d-md-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="contact-info">
                        <span class="me-3">
                            <i class="fas fa-envelope me-1"></i>
                            <?php echo getSetting('site_email', SITE_EMAIL); ?>
                        </span>
                        <span>
                            <i class="fas fa-phone me-1"></i>
                            <?php echo getSetting('site_phone', SITE_PHONE); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="top-bar-right d-flex justify-content-end align-items-center">
                        <!-- Language Switcher -->
                        <div class="language-switcher me-3">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-globe me-1"></i>
                                    <?php echo CURRENT_LANGUAGE === 'ar' ? 'العربية' : 'English'; ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="?lang=ar">العربية</a></li>
                                    <li><a class="dropdown-item" href="?lang=en">English</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Social Media Links -->
                        <div class="social-links">
                            <?php if (getSetting('facebook_url')): ?>
                            <a href="<?php echo getSetting('facebook_url'); ?>" target="_blank" class="text-white me-2">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (getSetting('twitter_url')): ?>
                            <a href="<?php echo getSetting('twitter_url'); ?>" target="_blank" class="text-white me-2">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (getSetting('linkedin_url')): ?>
                            <a href="<?php echo getSetting('linkedin_url'); ?>" target="_blank" class="text-white me-2">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (getSetting('instagram_url')): ?>
                            <a href="<?php echo getSetting('instagram_url'); ?>" target="_blank" class="text-white">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="<?php echo SITE_NAME; ?>" height="50">
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                            <?php echo __('home', 'Home'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'about' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/about.php">
                            <?php echo __('about', 'About'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'services' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/services.php">
                            <?php echo __('services', 'Services'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'products' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/products.php">
                            <?php echo __('products', 'Products'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'achievements' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/achievements.php">
                            <?php echo __('achievements', 'Achievements'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'contact' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/contact.php">
                            <?php echo __('contact', 'Contact'); ?>
                        </a>
                    </li>
                </ul>
                
                <!-- Right Side Menu -->
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <!-- Shopping Cart -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cart_count; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($notifications_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $notifications_count; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notifications-dropdown">
                            <h6 class="dropdown-header"><?php echo __('notifications', 'Notifications'); ?></h6>
                            <div class="notifications-list">
                                <!-- Notifications will be loaded via AJAX -->
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="<?php echo SITE_URL; ?>/profile.php#notifications">
                                <?php echo __('view_all_notifications', 'View All Notifications'); ?>
                            </a>
                        </div>
                    </li>
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <img src="<?php echo ASSETS_URL; ?>/images/users/<?php echo $_SESSION['profile_image'] ?? 'default.png'; ?>" 
                                 class="rounded-circle me-1" width="30" height="30" alt="Profile">
                            <?php echo $_SESSION['first_name'] ?? __('account', 'Account'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                <i class="fas fa-user me-2"></i><?php echo __('profile', 'Profile'); ?>
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php#orders">
                                <i class="fas fa-shopping-bag me-2"></i><?php echo __('my_orders', 'My Orders'); ?>
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php#wallet">
                                <i class="fas fa-wallet me-2"></i><?php echo __('wallet', 'Wallet'); ?>
                            </a></li>
                            <?php if (isStaff()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>">
                                <i class="fas fa-cog me-2"></i><?php echo __('admin_panel', 'Admin Panel'); ?>
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i><?php echo __('logout', 'Logout'); ?>
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <!-- Login/Register for guests -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i><?php echo __('login', 'Login'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">
                            <i class="fas fa-user-plus me-1"></i><?php echo __('register', 'Register'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<?php else: ?>
<!-- Admin Header -->
<header class="admin-header bg-dark text-white">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="admin-brand">
                    <a href="<?php echo ADMIN_URL; ?>" class="text-white text-decoration-none">
                        <i class="fas fa-cog me-2"></i>
                        <?php echo SITE_NAME; ?> - <?php echo __('admin_panel', 'Admin Panel'); ?>
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-nav d-flex justify-content-end align-items-center">
                    <a href="<?php echo SITE_URL; ?>" target="_blank" class="text-white me-3">
                        <i class="fas fa-external-link-alt me-1"></i><?php echo __('view_site', 'View Site'); ?>
                    </a>
                    <div class="dropdown">
                        <a class="text-white dropdown-toggle text-decoration-none" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['first_name'] ?? 'Admin'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                <i class="fas fa-user me-2"></i><?php echo __('profile', 'Profile'); ?>
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/settings/">
                                <i class="fas fa-cog me-2"></i><?php echo __('settings', 'Settings'); ?>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i><?php echo __('logout', 'Logout'); ?>
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<?php endif; ?>

<!-- Main Content Wrapper -->
<main class="main-content"><?php echo "\n"; ?>