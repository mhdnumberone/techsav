<?php
/**
 * Services Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Initialize classes
$serviceClass = new Service();

// Get filters from URL
$filters = [
    'category_id' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest',
    'featured' => $_GET['featured'] ?? '',
    'has_fixed_price' => isset($_GET['pricing']) ? ($_GET['pricing'] === 'fixed' ? 1 : 0) : null
];

// Get current page
$page = max(1, (int)($_GET['page'] ?? 1));

// Get services with filters
$servicesData = $serviceClass->getAll($page, ITEMS_PER_PAGE, $filters);
$services = $servicesData['services'];
$totalPages = $servicesData['pages'];
$totalServices = $servicesData['total'];

// Get categories for filter
$categoriesQuery = "SELECT * FROM " . TBL_CATEGORIES . " WHERE status = 'active' ORDER BY name_" . CURRENT_LANGUAGE;
$categories = $GLOBALS['pdo']->query($categoriesQuery)->fetchAll();

// Page data
$page_title = __('our_services', 'Our Services');
$body_class = 'services-page';
?>

<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<section class="page-header bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="page-header-content text-center">
                    <h1 class="page-title display-4 fw-bold mb-3">
                        <?php echo __('our_services', 'Our Services'); ?>
                    </h1>
                    <p class="page-subtitle lead mb-4">
                        <?php echo __('services_subtitle', 'Professional digital solutions tailored to meet your business needs and drive growth'); ?>
                    </p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item">
                                <a href="<?php echo SITE_URL; ?>" class="text-white-50">
                                    <?php echo __('home', 'Home'); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                <?php echo __('services', 'Services'); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Content -->
<section class="services-content py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="filters-sidebar">
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('search_services', 'Search Services'); ?></h5>
                        <form method="GET" action="" class="search-form">
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="<?php echo __('search_placeholder', 'Search for services...'); ?>"
                                       value="<?php echo htmlspecialchars($filters['search']); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <!-- Preserve other filters -->
                            <?php foreach (['category', 'sort', 'featured', 'pricing'] as $param): ?>
                                <?php if (!empty($_GET[$param])): ?>
                                <input type="hidden" name="<?php echo $param; ?>" value="<?php echo htmlspecialchars($_GET[$param]); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </form>
                    </div>
                    
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('categories', 'Categories'); ?></h5>
                        <div class="category-list">
                            <a href="<?php echo SITE_URL; ?>/services.php" 
                               class="category-item <?php echo empty($filters['category_id']) ? 'active' : ''; ?>">
                                <span><?php echo __('all_categories', 'All Categories'); ?></span>
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="<?php echo SITE_URL; ?>/services.php?category=<?php echo $category['id']; ?>" 
                               class="category-item <?php echo $filters['category_id'] == $category['id'] ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($category['name_' . CURRENT_LANGUAGE]); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('pricing_type', 'Pricing Type'); ?></h5>
                        <div class="pricing-filters">
                            <a href="<?php echo SITE_URL; ?>/services.php?<?php echo http_build_query(array_merge($_GET, ['pricing' => ''])); ?>" 
                               class="filter-item <?php echo !isset($_GET['pricing']) || empty($_GET['pricing']) ? 'active' : ''; ?>">
                                <?php echo __('all_pricing', 'All Pricing'); ?>
                            </a>
                            <a href="<?php echo SITE_URL; ?>/services.php?<?php echo http_build_query(array_merge($_GET, ['pricing' => 'fixed'])); ?>" 
                               class="filter-item <?php echo ($_GET['pricing'] ?? '') === 'fixed' ? 'active' : ''; ?>">
                                <?php echo __('fixed_price', 'Fixed Price'); ?>
                            </a>
                            <a href="<?php echo SITE_URL; ?>/services.php?<?php echo http_build_query(array_merge($_GET, ['pricing' => 'custom'])); ?>" 
                               class="filter-item <?php echo ($_GET['pricing'] ?? '') === 'custom' ? 'active' : ''; ?>">
                                <?php echo __('custom_pricing', 'Custom Pricing'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('quick_filters', 'Quick Filters'); ?></h5>
                        <div class="quick-filters">
                            <a href="<?php echo SITE_URL; ?>/services.php?featured=1" 
                               class="filter-item <?php echo !empty($filters['featured']) ? 'active' : ''; ?>">
                                <i class="fas fa-star text-warning me-2"></i>
                                <?php echo __('featured_services', 'Featured Services'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <!-- Results Header -->
                <div class="results-header d-flex justify-content-between align-items-center mb-4">
                    <div class="results-info">
                        <h6 class="mb-0">
                            <?php echo sprintf(__('showing_results', 'Showing %d of %d services'), count($services), $totalServices); ?>
                        </h6>
                        <?php if (!empty($filters['search'])): ?>
                        <small class="text-muted">
                            <?php echo sprintf(__('search_results_for', 'Search results for: "%s"'), htmlspecialchars($filters['search'])); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sort-options">
                        <select class="form-select form-select-sm" id="sortSelect">
                            <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>
                                <?php echo __('sort_newest', 'Newest First'); ?>
                            </option>
                            <option value="name" <?php echo $filters['sort'] === 'name' ? 'selected' : ''; ?>>
                                <?php echo __('sort_name', 'Name A-Z'); ?>
                            </option>
                            <option value="price_low" <?php echo $filters['sort'] === 'price_low' ? 'selected' : ''; ?>>
                                <?php echo __('sort_price_low', 'Price: Low to High'); ?>
                            </option>
                            <option value="price_high" <?php echo $filters['sort'] === 'price_high' ? 'selected' : ''; ?>>
                                <?php echo __('sort_price_high', 'Price: High to Low'); ?>
                            </option>
                            <option value="rating" <?php echo $filters['sort'] === 'rating' ? 'selected' : ''; ?>>
                                <?php echo __('sort_rating', 'Highest Rated'); ?>
                            </option>
                        </select>
                    </div>
                </div>
                
                <!-- Services Grid -->
                <?php if (!empty($services)): ?>
                <div class="services-grid">
                    <div class="row g-4">
                        <?php foreach ($services as $service): ?>
                        <div class="col-lg-6 col-md-12">
                            <div class="service-card h-100">
                                <div class="row g-0 h-100">
                                    <?php if ($service['featured_image']): ?>
                                    <div class="col-md-4">
                                        <div class="service-image">
                                            <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_SERVICES . '/' . $service['featured_image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>" 
                                                 class="img-fluid h-100 w-100">
                                            <?php if ($service['is_featured']): ?>
                                            <div class="service-badge featured-badge">
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                    <?php else: ?>
                                    <div class="col-12">
                                    <?php endif; ?>
                                        <div class="service-content p-4 h-100 d-flex flex-column">
                                            <div class="service-header mb-3">
                                                <h5 class="service-name mb-2">
                                                    <a href="<?php echo SITE_URL; ?>/service-details.php?slug=<?php echo $service['slug']; ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>
                                                    </a>
                                                </h5>
                                                
                                                <?php if ($service['category_name']): ?>
                                                <span class="service-category badge bg-light text-dark">
                                                    <?php echo htmlspecialchars($service['category_name']); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <p class="service-description text-muted mb-3 flex-grow-1">
                                                <?php echo truncateText($service['short_description_' . CURRENT_LANGUAGE], 120); ?>
                                            </p>
                                            
                                            <div class="service-meta">
                                                <div class="service-price-rating d-flex justify-content-between align-items-center mb-3">
                                                    <div class="service-price">
                                                        <?php if ($service['has_fixed_price'] && $service['price']): ?>
                                                        <span class="price fw-bold text-primary">
                                                            <?php echo formatCurrency($service['price']); ?>
                                                        </span>
                                                        <?php else: ?>
                                                        <span class="price text-muted">
                                                            <?php echo __('custom_pricing', 'Custom Pricing'); ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php if ($service['average_rating'] > 0): ?>
                                                    <div class="service-rating">
                                                        <?php echo generateStarRating($service['average_rating']); ?>
                                                        <small class="text-muted ms-1">(<?php echo $service['review_count']; ?>)</small>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="service-actions">
                                                    <a href="<?php echo SITE_URL; ?>/service-details.php?slug=<?php echo $service['slug']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <?php echo __('view_details', 'View Details'); ?>
                                                    </a>
                                                    
                                                    <?php if ($service['has_fixed_price'] && $service['price']): ?>
                                                    <button class="btn btn-outline-primary btn-sm ms-2" 
                                                            onclick="addToCart('service', <?php echo $service['id']; ?>)">
                                                        <i class="fas fa-shopping-cart me-1"></i>
                                                        <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                                    </button>
                                                    <?php else: ?>
                                                    <a href="<?php echo SITE_URL; ?>/contact.php?service=<?php echo $service['slug']; ?>" 
                                                       class="btn btn-outline-primary btn-sm ms-2">
                                                        <i class="fas fa-envelope me-1"></i>
                                                        <?php echo __('get_quote', 'Get Quote'); ?>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper mt-5">
                    <?php
                    $baseUrl = SITE_URL . '/services.php?' . http_build_query(array_filter([
                        'category' => $filters['category_id'],
                        'search' => $filters['search'],
                        'sort' => $filters['sort'],
                        'featured' => $filters['featured'],
                        'pricing' => $_GET['pricing'] ?? ''
                    ]));
                    echo generatePagination($page, $totalPages, $baseUrl);
                    ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <!-- No Results -->
                <div class="no-results text-center py-5">
                    <div class="no-results-icon mb-4">
                        <i class="fas fa-search fa-4x text-muted"></i>
                    </div>
                    <h4 class="no-results-title mb-3"><?php echo __('no_services_found', 'No Services Found'); ?></h4>
                    <p class="no-results-text text-muted mb-4">
                        <?php echo __('no_services_desc', 'We couldn\'t find any services matching your criteria. Try adjusting your filters or search terms.'); ?>
                    </p>
                    <div class="no-results-actions">
                        <a href="<?php echo SITE_URL; ?>/services.php" class="btn btn-primary">
                            <?php echo __('view_all_services', 'View All Services'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-primary ms-2">
                            <?php echo __('contact_us', 'Contact Us'); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="cta-title mb-3"><?php echo __('custom_service_needed', 'Need a Custom Service?'); ?></h3>
                <p class="cta-subtitle mb-0">
                    <?php echo __('custom_service_desc', 'Can\'t find exactly what you\'re looking for? We offer custom development services tailored to your specific requirements.'); ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="cta-buttons">
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-comments me-2"></i>
                        <?php echo __('discuss_project', 'Discuss Your Project'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sort selection
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('sort', this.value);
            url.searchParams.delete('page'); // Reset to page 1
            window.location.href = url.toString();
        });
    }
    
    // Add to cart functionality
    window.addToCart = function(type, id) {
        if (!window.SITE_CONFIG.isLoggedIn) {
            // Redirect to login if not logged in
            window.location.href = window.SITE_CONFIG.siteUrl + '/login.php?redirect=' + encodeURIComponent(window.location.href);
            return;
        }
        
        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>' + window.SITE_CONFIG.texts.loading;
        
        // Make AJAX request
        fetch(window.SITE_CONFIG.apiUrl + '/cart/add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                type: type,
                id: id,
                quantity: 1,
                csrf_token: window.SITE_CONFIG.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: window.SITE_CONFIG.texts.success,
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                });
                
                // Update cart count if element exists
                const cartCount = document.querySelector('.navbar .badge');
                if (cartCount && data.cart_count) {
                    cartCount.textContent = data.cart_count;
                }
            } else {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: window.SITE_CONFIG.texts.error,
                text: window.SITE_CONFIG.texts.networkError
            });
        })
        .finally(() => {
            // Restore button state
            button.disabled = false;
            button.innerHTML = originalText;
        });
    };
    
    // Service card hover effects
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>