<?php
/**
 * Products Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Initialize classes
$productClass = new Product();

// Get filters from URL
$filters = [
    'category_id' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest',
    'featured' => $_GET['featured'] ?? '',
    'is_digital' => isset($_GET['type']) ? ($_GET['type'] === 'digital' ? 1 : 0) : null,
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? ''
];

// Get current page
$page = max(1, (int)($_GET['page'] ?? 1));

// Get products with filters
$productsData = $productClass->getAll($page, ITEMS_PER_PAGE, $filters);
$products = $productsData['products'];
$totalPages = $productsData['pages'];
$totalProducts = $productsData['total'];

// Get categories for filter
$categoriesQuery = "SELECT * FROM " . TBL_CATEGORIES . " WHERE status = 'active' ORDER BY name_" . CURRENT_LANGUAGE;
$categories = $GLOBALS['pdo']->query($categoriesQuery)->fetchAll();

// Page data
$page_title = __('our_products', 'Our Products');
$body_class = 'products-page';
?>

<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<section class="page-header bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="page-header-content text-center">
                    <h1 class="page-title display-4 fw-bold mb-3">
                        <?php echo __('our_products', 'Our Products'); ?>
                    </h1>
                    <p class="page-subtitle lead mb-4">
                        <?php echo __('products_subtitle', 'Discover our collection of digital products, tools, and ready-to-use solutions'); ?>
                    </p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item">
                                <a href="<?php echo SITE_URL; ?>" class="text-white-50">
                                    <?php echo __('home', 'Home'); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                <?php echo __('products', 'Products'); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Products Content -->
<section class="products-content py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="filters-sidebar">
                    <!-- Search -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('search_products', 'Search Products'); ?></h5>
                        <form method="GET" action="" class="search-form">
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="<?php echo __('search_placeholder', 'Search for products...'); ?>"
                                       value="<?php echo htmlspecialchars($filters['search']); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <!-- Preserve other filters -->
                            <?php foreach (['category', 'sort', 'featured', 'type', 'min_price', 'max_price'] as $param): ?>
                                <?php if (!empty($_GET[$param])): ?>
                                <input type="hidden" name="<?php echo $param; ?>" value="<?php echo htmlspecialchars($_GET[$param]); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </form>
                    </div>
                    
                    <!-- Categories -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('categories', 'Categories'); ?></h5>
                        <div class="category-list">
                            <a href="<?php echo SITE_URL; ?>/products.php" 
                               class="category-item <?php echo empty($filters['category_id']) ? 'active' : ''; ?>">
                                <span><?php echo __('all_categories', 'All Categories'); ?></span>
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>" 
                               class="category-item <?php echo $filters['category_id'] == $category['id'] ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($category['name_' . CURRENT_LANGUAGE]); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Product Type -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('product_type', 'Product Type'); ?></h5>
                        <div class="type-filters">
                            <a href="<?php echo SITE_URL; ?>/products.php?<?php echo http_build_query(array_merge($_GET, ['type' => ''])); ?>" 
                               class="filter-item <?php echo !isset($_GET['type']) || empty($_GET['type']) ? 'active' : ''; ?>">
                                <?php echo __('all_types', 'All Types'); ?>
                            </a>
                            <a href="<?php echo SITE_URL; ?>/products.php?<?php echo http_build_query(array_merge($_GET, ['type' => 'digital'])); ?>" 
                               class="filter-item <?php echo ($_GET['type'] ?? '') === 'digital' ? 'active' : ''; ?>">
                                <i class="fas fa-download me-2"></i>
                                <?php echo __('digital_products', 'Digital Products'); ?>
                            </a>
                            <a href="<?php echo SITE_URL; ?>/products.php?<?php echo http_build_query(array_merge($_GET, ['type' => 'physical'])); ?>" 
                               class="filter-item <?php echo ($_GET['type'] ?? '') === 'physical' ? 'active' : ''; ?>">
                                <i class="fas fa-box me-2"></i>
                                <?php echo __('physical_products', 'Physical Products'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('price_range', 'Price Range'); ?></h5>
                        <form method="GET" action="" class="price-filter-form">
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" 
                                           class="form-control form-control-sm" 
                                           name="min_price" 
                                           placeholder="<?php echo __('min_price', 'Min'); ?>"
                                           value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                                           min="0">
                                </div>
                                <div class="col-6">
                                    <input type="number" 
                                           class="form-control form-control-sm" 
                                           name="max_price" 
                                           placeholder="<?php echo __('max_price', 'Max'); ?>"
                                           value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                           min="0">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100 mt-2">
                                <?php echo __('apply_filter', 'Apply Filter'); ?>
                            </button>
                            <!-- Preserve other filters -->
                            <?php foreach (['category', 'search', 'sort', 'featured', 'type'] as $param): ?>
                                <?php if (!empty($_GET[$param])): ?>
                                <input type="hidden" name="<?php echo $param; ?>" value="<?php echo htmlspecialchars($_GET[$param]); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </form>
                    </div>
                    
                    <!-- Quick Filters -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title mb-3"><?php echo __('quick_filters', 'Quick Filters'); ?></h5>
                        <div class="quick-filters">
                            <a href="<?php echo SITE_URL; ?>/products.php?featured=1" 
                               class="filter-item <?php echo !empty($filters['featured']) ? 'active' : ''; ?>">
                                <i class="fas fa-star text-warning me-2"></i>
                                <?php echo __('featured_products', 'Featured Products'); ?>
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
                            <?php echo sprintf(__('showing_results', 'Showing %d of %d products'), count($products), $totalProducts); ?>
                        </h6>
                        <?php if (!empty($filters['search'])): ?>
                        <small class="text-muted">
                            <?php echo sprintf(__('search_results_for', 'Search results for: "%s"'), htmlspecialchars($filters['search'])); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="view-sort-options d-flex align-items-center">
                        <!-- View Toggle -->
                        <div class="view-toggle me-3">
                            <button class="btn btn-outline-secondary btn-sm active" data-view="grid" title="<?php echo __('grid_view', 'Grid View'); ?>">
                                <i class="fas fa-th"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" data-view="list" title="<?php echo __('list_view', 'List View'); ?>">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                        
                        <!-- Sort Options -->
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
                </div>
                
                <!-- Products Grid -->
                <?php if (!empty($products)): ?>
                <div class="products-container" id="productsContainer">
                    <div class="row g-4" id="productsGrid">
                        <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6 product-item">
                            <div class="product-card h-100">
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
                                    
                                    <!-- Product Badges -->
                                    <div class="product-badges">
                                        <?php if ($product['sale_price']): ?>
                                        <span class="badge bg-danger">
                                            <?php echo __('sale', 'Sale'); ?>
                                        </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['is_featured']): ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-star"></i>
                                        </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['is_digital']): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-download"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Quick Actions -->
                                    <div class="product-actions">
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="addToCart('product', <?php echo $product['id']; ?>)"
                                                title="<?php echo __('add_to_cart', 'Add to Cart'); ?>">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                        <a href="<?php echo SITE_URL; ?>/product-details.php?slug=<?php echo $product['slug']; ?>" 
                                           class="btn btn-outline-primary btn-sm"
                                           title="<?php echo __('view_details', 'View Details'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="product-content p-3">
                                    <!-- Category -->
                                    <?php if ($product['category_name']): ?>
                                    <div class="product-category mb-2">
                                        <small class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Product Name -->
                                    <h6 class="product-name mb-2">
                                        <a href="<?php echo SITE_URL; ?>/product-details.php?slug=<?php echo $product['slug']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?>
                                        </a>
                                    </h6>
                                    
                                    <!-- Product Description -->
                                    <p class="product-description text-muted small mb-3">
                                        <?php echo truncateText($product['short_description_' . CURRENT_LANGUAGE], 80); ?>
                                    </p>
                                    
                                    <!-- Rating -->
                                    <?php if ($product['average_rating'] > 0): ?>
                                    <div class="product-rating mb-2">
                                        <?php echo generateStarRating($product['average_rating']); ?>
                                        <small class="text-muted ms-1">(<?php echo $product['review_count']; ?>)</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Price -->
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
                                    
                                    <!-- Stock Status -->
                                    <?php if (!$product['is_digital']): ?>
                                    <div class="stock-status mb-3">
                                        <?php if ($product['stock'] > 0): ?>
                                        <small class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?php echo sprintf(__('in_stock_count', '%d in stock'), $product['stock']); ?>
                                        </small>
                                        <?php else: ?>
                                        <small class="text-danger">
                                            <i class="fas fa-times-circle me-1"></i>
                                            <?php echo __('out_of_stock', 'Out of Stock'); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Add to Cart Button -->
                                    <div class="product-cart-action">
                                        <?php if ($product['is_digital'] || $product['stock'] > 0): ?>
                                        <button class="btn btn-primary btn-sm w-100" 
                                                onclick="addToCart('product', <?php echo $product['id']; ?>)">
                                            <i class="fas fa-shopping-cart me-1"></i>
                                            <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-secondary btn-sm w-100" disabled>
                                            <?php echo __('out_of_stock', 'Out of Stock'); ?>
                                        </button>
                                        <?php endif; ?>
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
                    $baseUrl = SITE_URL . '/products.php?' . http_build_query(array_filter([
                        'category' => $filters['category_id'],
                        'search' => $filters['search'],
                        'sort' => $filters['sort'],
                        'featured' => $filters['featured'],
                        'type' => $_GET['type'] ?? '',
                        'min_price' => $filters['min_price'],
                        'max_price' => $filters['max_price']
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
                    <h4 class="no-results-title mb-3"><?php echo __('no_products_found', 'No Products Found'); ?></h4>
                    <p class="no-results-text text-muted mb-4">
                        <?php echo __('no_products_desc', 'We couldn\'t find any products matching your criteria. Try adjusting your filters or search terms.'); ?>
                    </p>
                    <div class="no-results-actions">
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                            <?php echo __('view_all_products', 'View All Products'); ?>
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
                <h3 class="cta-title mb-3"><?php echo __('looking_for_custom', 'Looking for Something Custom?'); ?></h3>
                <p class="cta-subtitle mb-0">
                    <?php echo __('custom_product_desc', 'We can create custom digital products and solutions tailored specifically to your business needs.'); ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="cta-buttons">
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-lightbulb me-2"></i>
                        <?php echo __('discuss_idea', 'Discuss Your Idea'); ?>
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
    
    // Handle view toggle
    const viewButtons = document.querySelectorAll('.view-toggle button');
    const productsGrid = document.getElementById('productsGrid');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            
            // Update active button
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update grid layout
            if (view === 'list') {
                productsGrid.className = 'products-list';
                // Change product items to full width for list view
                const productItems = document.querySelectorAll('.product-item');
                productItems.forEach(item => {
                    item.className = 'col-12 product-item';
                });
            } else {
                productsGrid.className = 'row g-4';
                // Restore grid layout
                const productItems = document.querySelectorAll('.product-item');
                productItems.forEach(item => {
                    item.className = 'col-lg-4 col-md-6 product-item';
                });
            }
        });
    });
    
    // Add to cart functionality
    window.addToCart = function(type, id) {
        if (!window.SITE_CONFIG.isLoggedIn) {
            // Redirect to login if not logged in
            window.location.href = window.SITE_CONFIG.siteUrl + '/login.php?redirect=' + encodeURIComponent(window.location.href);
            return;
        }
        
        // Show loading state
        const button = event.target.closest('button');
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
    
    // Product card hover effects
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Price filter form enhancement
    const priceForm = document.querySelector('.price-filter-form');
    if (priceForm) {
        const minPrice = priceForm.querySelector('input[name="min_price"]');
        const maxPrice = priceForm.querySelector('input[name="max_price"]');
        
        // Validate price range
        priceForm.addEventListener('submit', function(e) {
            const min = parseFloat(minPrice.value) || 0;
            const max = parseFloat(maxPrice.value) || Infinity;
            
            if (min > max) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'Minimum price cannot be greater than maximum price.'
                });
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>