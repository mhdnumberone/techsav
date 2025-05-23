<?php
/**
 * Homepage - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Initialize classes
$productClass = new Product();
$serviceClass = new Service();
$reviewClass = new Review();

// Get featured content
$featuredProducts = $productClass->getFeaturedProducts(8);
$featuredServices = $serviceClass->getFeaturedServices(6);
$latestReviews = $reviewClass->getLatestReviews(6);

// Page data
$page_title = __('home', 'Home');
$body_class = 'homepage';
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section bg-gradient-primary text-white">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="hero-title display-4 fw-bold mb-4">
                        <?php echo __('hero_title', 'Professional Web Development & Digital Solutions'); ?>
                    </h1>
                    <p class="hero-subtitle lead mb-4">
                        <?php echo __('hero_subtitle', 'We create stunning websites, mobile applications, and digital products that help your business grow and succeed in the digital world.'); ?>
                    </p>
                    <div class="hero-buttons">
                        <a href="<?php echo SITE_URL; ?>/services.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-rocket me-2"></i>
                            <?php echo __('our_services', 'Our Services'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-comments me-2"></i>
                            <?php echo __('get_consultation', 'Get Consultation'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image text-center">
                    <img src="<?php echo ASSETS_URL; ?>/images/hero-illustration.svg" alt="<?php echo __('web_development', 'Web Development'); ?>" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Animated Background Elements -->
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
</section>

<!-- Services Overview Section -->
<section class="services-overview py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title"><?php echo __('what_we_do', 'What We Do'); ?></h2>
                    <p class="section-subtitle text-muted">
                        <?php echo __('services_intro', 'We offer comprehensive digital solutions to help your business thrive'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Web Development -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card h-100 text-center">
                    <div class="service-icon mb-4">
                        <i class="fas fa-code text-primary fa-3x"></i>
                    </div>
                    <h4 class="service-title mb-3"><?php echo __('web_development', 'Web Development'); ?></h4>
                    <p class="service-description text-muted">
                        <?php echo __('web_dev_desc', 'Custom websites and web applications built with modern technologies and best practices.'); ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>/services.php?category=web-development" class="btn btn-outline-primary">
                        <?php echo __('learn_more', 'Learn More'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Mobile Apps -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card h-100 text-center">
                    <div class="service-icon mb-4">
                        <i class="fas fa-mobile-alt text-success fa-3x"></i>
                    </div>
                    <h4 class="service-title mb-3"><?php echo __('mobile_apps', 'Mobile Applications'); ?></h4>
                    <p class="service-description text-muted">
                        <?php echo __('mobile_desc', 'Native and cross-platform mobile applications for iOS and Android devices.'); ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>/services.php?category=mobile-apps" class="btn btn-outline-success">
                        <?php echo __('learn_more', 'Learn More'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Digital Products -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card h-100 text-center">
                    <div class="service-icon mb-4">
                        <i class="fas fa-shopping-bag text-info fa-3x"></i>
                    </div>
                    <h4 class="service-title mb-3"><?php echo __('digital_products', 'Digital Products'); ?></h4>
                    <p class="service-description text-muted">
                        <?php echo __('digital_desc', 'Ready-to-use digital solutions, templates, and tools for your business needs.'); ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-info">
                        <?php echo __('view_products', 'View Products'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Services Section -->
<?php if (!empty($featuredServices)): ?>
<section class="featured-services py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title"><?php echo __('featured_services', 'Featured Services'); ?></h2>
                    <p class="section-subtitle text-muted">
                        <?php echo __('featured_services_desc', 'Discover our most popular professional services'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featuredServices as $service): ?>
            <div class="col-lg-4 col-md-6">
                <div class="service-item h-100">
                    <?php if ($service['featured_image']): ?>
                    <div class="service-image">
                        <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_SERVICES . '/' . $service['featured_image']; ?>" 
                             alt="<?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>" 
                             class="img-fluid">
                    </div>
                    <?php endif; ?>
                    
                    <div class="service-content p-4">
                        <h5 class="service-name mb-3">
                            <a href="<?php echo SITE_URL; ?>/service-details.php?slug=<?php echo $service['slug']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>
                            </a>
                        </h5>
                        
                        <p class="service-description text-muted mb-3">
                            <?php echo truncateText($service['short_description_' . CURRENT_LANGUAGE], 120); ?>
                        </p>
                        
                        <div class="service-meta d-flex justify-content-between align-items-center">
                            <?php if ($service['has_fixed_price'] && $service['price']): ?>
                            <span class="service-price fw-bold text-primary">
                                <?php echo formatCurrency($service['price']); ?>
                            </span>
                            <?php else: ?>
                            <span class="service-price text-muted">
                                <?php echo __('custom_pricing', 'Custom Pricing'); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($service['average_rating'] > 0): ?>
                            <div class="service-rating">
                                <?php echo generateStarRating($service['average_rating']); ?>
                                <small class="text-muted">(<?php echo $service['review_count']; ?>)</small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="service-actions mt-3">
                            <a href="<?php echo SITE_URL; ?>/service-details.php?slug=<?php echo $service['slug']; ?>" class="btn btn-primary btn-sm">
                                <?php echo __('view_details', 'View Details'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>/services.php" class="btn btn-outline-primary btn-lg">
                <?php echo __('view_all_services', 'View All Services'); ?>
                <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Products Section -->
<?php if (!empty($featuredProducts)): ?>
<section class="featured-products py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title"><?php echo __('featured_products', 'Featured Products'); ?></h2>
                    <p class="section-subtitle text-muted">
                        <?php echo __('featured_products_desc', 'Explore our best-selling digital products and tools'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="col-lg-3 col-md-6">
                <div class="product-item h-100">
                    <div class="product-image">
                        <?php if ($product['featured_image']): ?>
                        <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_PRODUCTS . '/' . $product['featured_image']; ?>" 
                             alt="<?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?>" 
                             class="img-fluid">
                        <?php else: ?>
                        <div class="placeholder-image d-flex align-items-center justify-content-center">
                            <i class="fas fa-box fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($product['sale_price']): ?>
                        <div class="product-badge sale-badge">
                            <?php echo __('sale', 'Sale'); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($product['is_digital']): ?>
                        <div class="product-badge digital-badge">
                            <?php echo __('digital', 'Digital'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-content p-3">
                        <h6 class="product-name mb-2">
                            <a href="<?php echo SITE_URL; ?>/product-details.php?slug=<?php echo $product['slug']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?>
                            </a>
                        </h6>
                        
                        <p class="product-description text-muted small mb-3">
                            <?php echo truncateText($product['short_description_' . CURRENT_LANGUAGE], 80); ?>
                        </p>
                        
                        <div class="product-price mb-3">
                            <?php if ($product['sale_price']): ?>
                            <span class="current-price fw-bold text-primary">
                                <?php echo formatCurrency($product['final_price']); ?>
                            </span>
                            <span class="original-price text-muted text-decoration-line-through ms-2">
                                <?php echo formatCurrency($product['price']); ?>
                            </span>
                            <?php else: ?>
                            <span class="current-price fw-bold text-primary">
                                <?php echo formatCurrency($product['price']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($product['average_rating'] > 0): ?>
                        <div class="product-rating mb-3">
                            <?php echo generateStarRating($product['average_rating']); ?>
                            <small class="text-muted">(<?php echo $product['review_count']; ?>)</small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="product-actions">
                            <a href="<?php echo SITE_URL; ?>/product-details.php?slug=<?php echo $product['slug']; ?>" class="btn btn-primary btn-sm w-100">
                                <?php echo __('view_product', 'View Product'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-primary btn-lg">
                <?php echo __('view_all_products', 'View All Products'); ?>
                <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Stats Section -->
<section class="stats-section py-5 bg-primary text-white">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h3 class="stat-number" data-count="500">0</h3>
                    <p class="stat-label"><?php echo __('happy_clients', 'Happy Clients'); ?></p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-project-diagram fa-2x"></i>
                    </div>
                    <h3 class="stat-number" data-count="250">0</h3>
                    <p class="stat-label"><?php echo __('completed_projects', 'Completed Projects'); ?></p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h3 class="stat-number" data-count="5">0</h3>
                    <p class="stat-label"><?php echo __('years_experience', 'Years Experience'); ?></p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                    <h3 class="stat-number" data-count="98">0</h3>
                    <p class="stat-label"><?php echo __('satisfaction_rate', 'Satisfaction Rate'); ?>%</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<?php if (!empty($latestReviews)): ?>
<section class="testimonials py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title"><?php echo __('what_clients_say', 'What Our Clients Say'); ?></h2>
                    <p class="section-subtitle text-muted">
                        <?php echo __('testimonials_desc', 'Read what our satisfied clients have to say about our services'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach (array_slice($latestReviews, 0, 3) as $review): ?>
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-item h-100">
                    <div class="testimonial-content">
                        <div class="testimonial-rating mb-3">
                            <?php echo generateStarRating($review['rating']); ?>
                        </div>
                        
                        <?php if ($review['title']): ?>
                        <h6 class="testimonial-title mb-3"><?php echo htmlspecialchars($review['title']); ?></h6>
                        <?php endif; ?>
                        
                        <p class="testimonial-text text-muted mb-4">
                            "<?php echo htmlspecialchars(truncateText($review['comment'], 150)); ?>"
                        </p>
                        
                        <div class="testimonial-author d-flex align-items-center">
                            <div class="author-avatar me-3">
                                <?php if ($review['profile_image']): ?>
                                <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_USERS . '/' . $review['profile_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($review['first_name']); ?>" 
                                     class="rounded-circle" width="50" height="50">
                                <?php else: ?>
                                <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="author-info">
                                <h6 class="author-name mb-0">
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo $review['product_name'] ?? $review['service_name'] ?? $review['custom_service_name']; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="cta-section py-5 bg-gradient-secondary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="cta-title mb-3"><?php echo __('ready_to_start', 'Ready to Start Your Project?'); ?></h3>
                <p class="cta-subtitle mb-0">
                    <?php echo __('cta_desc', 'Let\'s discuss your ideas and bring them to life with our expert team.'); ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="cta-buttons">
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-light btn-lg">
                        <i class="fas fa-rocket me-2"></i>
                        <?php echo __('start_project', 'Start Your Project'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom JavaScript for Homepage -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate counter numbers
    const counters = document.querySelectorAll('.stat-number');
    const animateCounters = () => {
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const count = parseInt(counter.innerText);
            const increment = target / 100;
            
            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(animateCounters, 30);
            } else {
                counter.innerText = target;
            }
        });
    };
    
    // Start animation when stats section is visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.unobserve(entry.target);
            }
        });
    });
    
    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        observer.observe(statsSection);
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>