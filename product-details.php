<?php
/**
 * Product Details Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Initialize classes
$productClass = new Product();
$reviewClass = new Review();

// Get product slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('HTTP/1.1 404 Not Found');
    include '404.php';
    exit;
}

// Get product details
$product = $productClass->getBySlug($slug);

if (!$product) {
    header('HTTP/1.1 404 Not Found');
    include '404.php';
    exit;
}

// Get product images
$productImages = $productClass->getProductImages($product['id']);

// Get related products
$relatedProducts = $productClass->getRelatedProducts($product['id'], $product['category_id'], 4);

// Get reviews
$reviewsData = $reviewClass->getProductReviews($product['id'], 1, 10);
$reviews = $reviewsData['reviews'];
$totalReviews = $reviewsData['total'];

// Check if user has reviewed this product
$userHasReviewed = false;
if (isLoggedIn()) {
    $userHasReviewed = $reviewClass->hasUserReviewed($_SESSION['user_id'], $product['id']);
}

// Handle review submission
$review_success = '';
$review_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $review_error = __('login_required', 'Please login to submit a review');
    } elseif (!verifyCsrfToken()) {
        $review_error = __('invalid_token', 'Invalid security token');
    } elseif ($userHasReviewed) {
        $review_error = __('already_reviewed', 'You have already reviewed this product');
    } else {
        $reviewData = [
            'user_id' => $_SESSION['user_id'],
            'product_id' => $product['id'],
            'rating' => (int)($_POST['rating'] ?? 0),
            'title' => cleanInput($_POST['title'] ?? ''),
            'comment' => cleanInput($_POST['comment'] ?? '')
        ];

        $result = $reviewClass->create($reviewData);
        if ($result['success']) {
            $review_success = $result['message'];
            $userHasReviewed = true;
        } else {
            $review_error = $result['message'];
        }
    }
}

// Page data
$page_title = $product['name_' . CURRENT_LANGUAGE] . ' - ' . __('products', 'Products');
$body_class = 'product-details-page';
?>

<?php include 'includes/header.php'; ?>

<!-- Product Details Section -->
<section class="product-details py-5">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>"><?php echo __('home', 'Home'); ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/products.php"><?php echo __('products', 'Products'); ?></a>
                </li>
                <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $product['category_id']; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?>
                </li>
            </ol>
        </nav>

        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4">
                <div class="product-images">
                    <!-- Main Image -->
                    <div class="main-image mb-3">
                        <?php if ($product['featured_image']): ?>
                        <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_PRODUCTS . '/' . $product['featured_image']; ?>" 
                             alt="<?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?>" 
                             class="img-fluid rounded shadow" 
                             id="mainProductImage">
                        <?php else: ?>
                        <div class="placeholder-image d-flex align-items-center justify-content-center rounded shadow">
                            <i class="fas fa-box fa-5x text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Image Thumbnails -->
                    <?php if (!empty($productImages) || $product['featured_image']): ?>
                    <div class="image-thumbnails">
                        <div class="row g-2">
                            <?php if ($product['featured_image']): ?>
                            <div class="col-3">
                                <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_PRODUCTS . '/' . $product['featured_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?>" 
                                     class="img-fluid rounded thumbnail-image active"
                                     onclick="changeMainImage(this.src)">
                            </div>
                            <?php endif; ?>
                            
                            <?php foreach ($productImages as $image): ?>
                            <div class="col-3">
                                <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_PRODUCTS . '/' . $image['image_path']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?>" 
                                     class="img-fluid rounded thumbnail-image"
                                     onclick="changeMainImage(this.src)">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Information -->
            <div class="col-lg-6">
                <div class="product-info">
                    <!-- Product Category -->
                    <?php if ($product['category_name']): ?>
                    <div class="product-category mb-2">
                        <span class="badge bg-light text-dark">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <!-- Product Name -->
                    <h1 class="product-title mb-3">
                        <?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?>
                    </h1>

                    <!-- Rating -->
                    <?php if ($product['average_rating'] > 0): ?>
                    <div class="product-rating mb-3">
                        <?php echo generateStarRating($product['average_rating']); ?>
                        <span class="rating-text ms-2">
                            <strong><?php echo number_format($product['average_rating'], 1); ?></strong>
                            (<?php echo $product['review_count']; ?> <?php echo __('reviews', 'reviews'); ?>)
                        </span>
                        <a href="#reviews" class="ms-2 text-decoration-none">
                            <?php echo __('read_reviews', 'Read Reviews'); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Price -->
                    <div class="product-price mb-4">
                        <?php if ($product['sale_price']): ?>
                        <div class="price-section">
                            <span class="current-price display-6 fw-bold text-primary">
                                <?php echo formatCurrency($product['final_price']); ?>
                            </span>
                            <span class="original-price h4 text-muted text-decoration-line-through ms-3">
                                <?php echo formatCurrency($product['price']); ?>
                            </span>
                            <span class="discount-badge badge bg-danger ms-2">
                                <?php 
                                $discount = (($product['price'] - $product['final_price']) / $product['price']) * 100;
                                echo '-' . round($discount) . '%';
                                ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <span class="current-price display-6 fw-bold text-primary">
                            <?php echo formatCurrency($product['price']); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Product Badges -->
                    <div class="product-badges mb-3">
                        <?php if ($product['is_featured']): ?>
                        <span class="badge bg-warning text-dark me-2">
                            <i class="fas fa-star me-1"></i><?php echo __('featured', 'Featured'); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($product['is_digital']): ?>
                        <span class="badge bg-info me-2">
                            <i class="fas fa-download me-1"></i><?php echo __('digital_download', 'Digital Download'); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Stock Status -->
                    <?php if (!$product['is_digital']): ?>
                    <div class="stock-status mb-4">
                        <?php if ($product['stock'] > 0): ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong><?php echo sprintf(__('in_stock_count', '%d in stock'), $product['stock']); ?></strong>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong><?php echo __('out_of_stock', 'Out of Stock'); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Short Description -->
                    <?php if ($product['short_description_' . CURRENT_LANGUAGE]): ?>
                    <div class="product-summary mb-4">
                        <p class="lead"><?php echo nl2br(htmlspecialchars($product['short_description_' . CURRENT_LANGUAGE])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Add to Cart Section -->
                    <div class="add-to-cart-section mb-4">
                        <?php if ($product['is_digital'] || $product['stock'] > 0): ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label"><?php echo __('quantity', 'Quantity'); ?></label>
                                <input type="number" 
                                       class="form-control" 
                                       id="quantity" 
                                       value="1" 
                                       min="1" 
                                       <?php if (!$product['is_digital']): ?>
                                       max="<?php echo $product['stock']; ?>"
                                       <?php endif; ?>>
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                <button class="btn btn-primary btn-lg w-100" 
                                        onclick="addToCart('product', <?php echo $product['id']; ?>)">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <button class="btn btn-secondary btn-lg w-100" disabled>
                            <i class="fas fa-times me-2"></i>
                            <?php echo __('out_of_stock', 'Out of Stock'); ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Additional Actions -->
                    <div class="product-actions mb-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-secondary w-100" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-heart me-1"></i>
                                    <?php echo __('add_to_wishlist', 'Add to Wishlist'); ?>
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-secondary w-100" onclick="shareProduct()">
                                    <i class="fas fa-share-alt me-1"></i>
                                    <?php echo __('share', 'Share'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Contact for Questions -->
                    <div class="contact-section">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h6 class="card-title"><?php echo __('have_questions', 'Have Questions?'); ?></h6>
                                <p class="card-text small text-muted">
                                    <?php echo __('contact_for_questions', 'Contact us for any questions about this product'); ?>
                                </p>
                                <a href="<?php echo SITE_URL; ?>/contact.php?product=<?php echo $product['slug']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?php echo __('contact_us', 'Contact Us'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Description & Details -->
<section class="product-description py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" 
                                id="description-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#description" 
                                type="button" 
                                role="tab">
                            <?php echo __('description', 'Description'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" 
                                id="reviews-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#reviews" 
                                type="button" 
                                role="tab">
                            <?php echo __('reviews', 'Reviews'); ?> (<?php echo $totalReviews; ?>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="productTabsContent">
                    <!-- Description Tab -->
                    <div class="tab-pane fade show active" 
                         id="description" 
                         role="tabpanel" 
                         aria-labelledby="description-tab">
                        <div class="description-content">
                            <?php if ($product['description_' . CURRENT_LANGUAGE]): ?>
                            <div class="product-description">
                                <?php echo nl2br(htmlspecialchars($product['description_' . CURRENT_LANGUAGE])); ?>
                            </div>
                            <?php else: ?>
                            <p class="text-muted"><?php echo __('no_description', 'No description available for this product.'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Reviews Tab -->
                    <div class="tab-pane fade" 
                         id="reviews" 
                         role="tabpanel" 
                         aria-labelledby="reviews-tab">
                        
                        <!-- Review Summary -->
                        <?php if ($product['average_rating'] > 0): ?>
                        <div class="review-summary mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="average-rating text-center">
                                        <div class="rating-number display-4 fw-bold text-primary">
                                            <?php echo number_format($product['average_rating'], 1); ?>
                                        </div>
                                        <div class="rating-stars mb-2">
                                            <?php echo generateStarRating($product['average_rating']); ?>
                                        </div>
                                        <p class="text-muted">
                                            <?php echo sprintf(__('based_on_reviews', 'Based on %d reviews'), $totalReviews); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Add Review Form -->
                        <?php if (isLoggedIn() && !$userHasReviewed): ?>
                        <div class="add-review-section mb-5">
                            <h5><?php echo __('write_review', 'Write a Review'); ?></h5>
                            
                            <?php if ($review_success): ?>
                            <div class="alert alert-success"><?php echo $review_success; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($review_error): ?>
                            <div class="alert alert-danger"><?php echo $review_error; ?></div>
                            <?php endif; ?>

                            <form method="POST" action="" class="review-form">
                                <?php echo csrfToken(); ?>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('rating', 'Rating'); ?> <span class="text-danger">*</span></label>
                                    <div class="rating-input">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="review_title" class="form-label"><?php echo __('review_title', 'Review Title'); ?></label>
                                    <input type="text" class="form-control" id="review_title" name="title" 
                                           placeholder="<?php echo __('review_title_placeholder', 'Summarize your review'); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="review_comment" class="form-label"><?php echo __('your_review', 'Your Review'); ?> <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="review_comment" name="comment" rows="4" 
                                              placeholder="<?php echo __('review_placeholder', 'Share your thoughts about this product'); ?>" 
                                              required></textarea>
                                </div>

                                <button type="submit" name="submit_review" class="btn btn-primary">
                                    <i class="fas fa-star me-1"></i>
                                    <?php echo __('submit_review', 'Submit Review'); ?>
                                </button>
                            </form>
                        </div>
                        <?php elseif (!isLoggedIn()): ?>
                        <div class="login-prompt mb-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('login_to_review', 'Please'); ?>
                                <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                    <?php echo __('login', 'login'); ?>
                                </a>
                                <?php echo __('to_write_review', 'to write a review'); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Reviews List -->
                        <div class="reviews-list">
                            <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                            <div class="review-item border-bottom py-4">
                                <div class="row">
                                    <div class="col-md-2 text-center mb-3">
                                        <div class="reviewer-info">
                                            <?php if ($review['profile_image']): ?>
                                            <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_USERS . '/' . $review['profile_image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($review['first_name']); ?>" 
                                                 class="rounded-circle mb-2" width="60" height="60">
                                            <?php else: ?>
                                            <div class="avatar-placeholder rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px; background-color: #f8f9fa;">
                                                <i class="fas fa-user text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div class="reviewer-name small fw-bold">
                                                <?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'); ?>
                                            </div>
                                            <div class="review-date small text-muted">
                                                <?php echo formatDate($review['created_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-10">
                                        <div class="review-content">
                                            <div class="review-rating mb-2">
                                                <?php echo generateStarRating($review['rating']); ?>
                                            </div>
                                            
                                            <?php if ($review['title']): ?>
                                            <h6 class="review-title mb-2">
                                                <?php echo htmlspecialchars($review['title']); ?>
                                            </h6>
                                            <?php endif; ?>
                                            
                                            <p class="review-text mb-0">
                                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="no-reviews text-center py-5">
                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                <h5><?php echo __('no_reviews_yet', 'No Reviews Yet'); ?></h5>
                                <p class="text-muted"><?php echo __('be_first_to_review', 'Be the first to review this product'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<?php if (!empty($relatedProducts)): ?>
<section class="related-products py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h3 class="section-title mb-4"><?php echo __('related_products', 'Related Products'); ?></h3>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
            <div class="col-lg-3 col-md-6">
                <div class="product-card h-100">
                    <div class="product-image">
                        <?php if ($relatedProduct['featured_image']): ?>
                        <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_PRODUCTS . '/' . $relatedProduct['featured_image']; ?>" 
                             alt="<?php echo htmlspecialchars($relatedProduct['name_' . CURRENT_LANGUAGE]); ?>" 
                             class="img-fluid">
                        <?php else: ?>
                        <div class="placeholder-image d-flex align-items-center justify-content-center">
                            <i class="fas fa-box fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-content p-3">
                        <h6 class="product-name mb-2">
                            <a href="<?php echo SITE_URL; ?>/product-details.php?slug=<?php echo $relatedProduct['slug']; ?>" 
                               class="text-decoration-none">
                                <?php echo htmlspecialchars($relatedProduct['name_' . CURRENT_LANGUAGE]); ?>
                            </a>
                        </h6>
                        
                        <div class="product-price mb-3">
                            <?php if ($relatedProduct['sale_price']): ?>
                            <span class="current-price fw-bold text-primary">
                                <?php echo formatCurrency($relatedProduct['final_price']); ?>
                            </span>
                            <span class="original-price text-muted text-decoration-line-through ms-2">
                                <?php echo formatCurrency($relatedProduct['price']); ?>
                            </span>
                            <?php else: ?>
                            <span class="current-price fw-bold text-primary">
                                <?php echo formatCurrency($relatedProduct['price']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <button class="btn btn-primary btn-sm w-100" 
                                onclick="addToCart('product', <?php echo $relatedProduct['id']; ?>)">
                            <i class="fas fa-shopping-cart me-1"></i>
                            <?php echo __('add_to_cart', 'Add to Cart'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image gallery functionality
    window.changeMainImage = function(src) {
        const mainImage = document.getElementById('mainProductImage');
        if (mainImage) {
            mainImage.src = src;
        }
        
        // Update active thumbnail
        document.querySelectorAll('.thumbnail-image').forEach(img => {
            img.classList.remove('active');
        });
        event.target.classList.add('active');
    };
    
    // Add to cart functionality
    window.addToCart = function(type, id) {
        if (!window.SITE_CONFIG.isLoggedIn) {
            window.location.href = window.SITE_CONFIG.siteUrl + '/login.php?redirect=' + encodeURIComponent(window.location.href);
            return;
        }
        
        const quantity = document.getElementById('quantity') ? document.getElementById('quantity').value : 1;
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>' + window.SITE_CONFIG.texts.loading;
        
        fetch(window.SITE_CONFIG.apiUrl + '/cart/add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                type: type,
                id: id,
                quantity: parseInt(quantity),
                csrf_token: window.SITE_CONFIG.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: window.SITE_CONFIG.texts.success,
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                });
                
                // Update cart count
                const cartCount = document.querySelector('.navbar .badge');
                if (cartCount && data.cart_count) {
                    cartCount.textContent = data.cart_count;
                }
            } else {
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
            button.disabled = false;
            button.innerHTML = originalText;
        });
    };
    
    // Share product functionality
    window.shareProduct = function() {
        if (navigator.share) {
            navigator.share({
                title: document.title,
                url: window.location.href
            });
        } else {
            // Fallback: Copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Product link copied to clipboard',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }
    };
    
    // Add to wishlist functionality
    window.addToWishlist = function(productId) {
        if (!window.SITE_CONFIG.isLoggedIn) {
            window.location.href = window.SITE_CONFIG.siteUrl + '/login.php?redirect=' + encodeURIComponent(window.location.href);
            return;
        }
        
        // Implement wishlist functionality
        Swal.fire({
            icon: 'info',
            title: 'Coming Soon',
            text: 'Wishlist functionality will be available soon!'
        });
    };
    
    // Rating input functionality
    const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            const rating = this.value;
            const labels = document.querySelectorAll('.rating-input label');
            labels.forEach((label, index) => {
                if (index >= (5 - rating)) {
                    label.classList.add('active');
                } else {
                    label.classList.remove('active');
                }
            });
        });
    });
});
</script>

<style>
.thumbnail-image {
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.thumbnail-image:hover,
.thumbnail-image.active {
    opacity: 1;
}

.placeholder-image {
    height: 400px;
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
}

.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
    transition: color 0.3s ease;
}

.rating-input label:hover,
.rating-input label.active,
.rating-input input[type="radio"]:checked ~ label {
    color: #ffc107;
}

.product-actions .btn {
    transition: transform 0.2s ease;
}

.product-actions .btn:hover {
    transform: translateY(-2px);
}

.review-item:last-child {
    border-bottom: none !important;
}

.avatar-placeholder {
    background-color: #e9ecef;
}
</style>

<?php include 'includes/footer.php'; ?>