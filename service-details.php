<?php
/**
 * Service Details Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Initialize classes
$serviceClass = new Service();
$reviewClass = new Review();

// Get service slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(SITE_URL . '/services.php');
}

// Get service details
$service = $serviceClass->getBySlug($slug);

if (!$service) {
    redirect(SITE_URL . '/404.php');
}

// Get related services
$relatedServices = $serviceClass->getRelatedServices($service['id'], $service['category_id'], 4);

// Get service reviews
$reviewsData = $reviewClass->getServiceReviews($service['id'], 1, 10);
$reviews = $reviewsData['reviews'];
$totalReviews = $reviewsData['total'];

// Check if user has already reviewed this service
$userReview = null;
if (isLoggedIn()) {
    $userReview = $reviewClass->getUserReview($_SESSION['user_id'], null, $service['id']);
}

// Handle review submission
$review_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $review_message = ['type' => 'error', 'text' => __('login_required', 'Please login to submit a review')];
    } elseif (!verifyCsrfToken()) {
        $review_message = ['type' => 'error', 'text' => __('invalid_token', 'Invalid security token')];
    } else {
        $reviewData = [
            'user_id' => $_SESSION['user_id'],
            'service_id' => $service['id'],
            'rating' => (int)($_POST['rating'] ?? 0),
            'title' => cleanInput($_POST['title'] ?? ''),
            'comment' => cleanInput($_POST['comment'] ?? '')
        ];
        
        $result = $reviewClass->create($reviewData);
        $review_message = [
            'type' => $result['success'] ? 'success' : 'error',
            'text' => $result['message']
        ];
        
        if ($result['success']) {
            // Refresh page to show new review status
            redirect($_SERVER['REQUEST_URI']);
        }
    }
}

// Handle custom service request
$custom_service_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_custom_service'])) {
    if (!isLoggedIn()) {
        $custom_service_message = ['type' => 'error', 'text' => __('login_required', 'Please login to request custom service')];
    } elseif (!verifyCsrfToken()) {
        $custom_service_message = ['type' => 'error', 'text' => __('invalid_token', 'Invalid security token')];
    } else {
        // Redirect to contact page with service parameter
        redirect(SITE_URL . '/contact.php?service=' . $service['slug'] . '&subject=' . urlencode('Custom Service Request: ' . $service['name_en']));
    }
}

// Page data
$page_title = $service['name_' . CURRENT_LANGUAGE] . ' - ' . __('services', 'Services');
$body_class = 'service-details-page';

// SEO and social media meta tags
$meta_description = truncateText($service['short_description_' . CURRENT_LANGUAGE], 160);
$service_image = $service['featured_image'] ? UPLOADS_URL . '/' . UPLOAD_PATH_SERVICES . '/' . $service['featured_image'] : ASSETS_URL . '/images/service-placeholder.jpg';
?>

<?php include 'includes/header.php'; ?>

<!-- Additional meta tags for this page -->
<meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($service['name_en'] . ', ' . ($service['category_name'] ?? '')); ?>">

<!-- Open Graph Meta Tags -->
<meta property="og:title" content="<?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
<meta property="og:image" content="<?php echo $service_image; ?>">
<meta property="og:url" content="<?php echo getCurrentUrl(); ?>">
<meta property="og:type" content="product">

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
<meta name="twitter:image" content="<?php echo $service_image; ?>">

<!-- Schema.org structured data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Service",
  "name": "<?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>",
  "description": "<?php echo htmlspecialchars($service['description_' . CURRENT_LANGUAGE]); ?>",
  "provider": {
    "@type": "Organization",
    "name": "<?php echo SITE_NAME; ?>",
    "url": "<?php echo SITE_URL; ?>"
  },
  "url": "<?php echo getCurrentUrl(); ?>",
  "image": "<?php echo $service_image; ?>",
  <?php if ($service['has_fixed_price'] && $service['price']): ?>
  "offers": {
    "@type": "Offer",
    "price": "<?php echo $service['price']; ?>",
    "priceCurrency": "<?php echo DEFAULT_CURRENCY; ?>",
    "availability": "https://schema.org/InStock"
  },
  <?php endif; ?>
  <?php if ($service['average_rating'] > 0): ?>
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?php echo $service['average_rating']; ?>",
    "reviewCount": "<?php echo $service['review_count']; ?>",
    "bestRating": "5",
    "worstRating": "1"
  },
  <?php endif; ?>
  "category": "<?php echo htmlspecialchars($service['category_name'] ?? ''); ?>"
}
</script>

<!-- Breadcrumbs -->
<section class="breadcrumbs bg-light py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>"><?php echo __('home', 'Home'); ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/services.php"><?php echo __('services', 'Services'); ?></a>
                </li>
                <?php if ($service['category_name']): ?>
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/services.php?category=<?php echo $service['category_id']; ?>">
                        <?php echo htmlspecialchars($service['category_name']); ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>
                </li>
            </ol>
        </nav>
    </div>
</section>

<!-- Service Details -->
<section class="service-details py-5">
    <div class="container">
        <div class="row">
            <!-- Service Image -->
            <div class="col-lg-6 mb-4">
                <div class="service-image-section">
                    <div class="main-image mb-3">
                        <?php if ($service['featured_image']): ?>
                        <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_SERVICES . '/' . $service['featured_image']; ?>" 
                             alt="<?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?>" 
                             class="img-fluid rounded shadow"
                             id="mainServiceImage">
                        <?php else: ?>
                        <div class="placeholder-image d-flex align-items-center justify-content-center rounded shadow bg-light" style="height: 400px;">
                            <i class="fas fa-cogs fa-5x text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Service Information -->
            <div class="col-lg-6">
                <div class="service-info">
                    <!-- Service Category -->
                    <?php if ($service['category_name']): ?>
                    <div class="service-category mb-2">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($service['category_name']); ?></span>
                        <?php if ($service['is_featured']): ?>
                        <span class="badge bg-warning ms-2">
                            <i class="fas fa-star me-1"></i><?php echo __('featured', 'Featured'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Service Name -->
                    <h1 class="service-name h2 mb-3"><?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?></h1>
                    
                    <!-- Service Rating -->
                    <?php if ($service['average_rating'] > 0): ?>
                    <div class="service-rating mb-3">
                        <div class="d-flex align-items-center">
                            <?php echo generateStarRating($service['average_rating']); ?>
                            <span class="rating-value ms-2 fw-bold"><?php echo number_format($service['average_rating'], 1); ?></span>
                            <span class="rating-count text-muted ms-2">(<?php echo $service['review_count']; ?> <?php echo __('reviews', 'reviews'); ?>)</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Service Short Description -->
                    <div class="service-short-description mb-4">
                        <p class="lead text-muted"><?php echo nl2br(htmlspecialchars($service['short_description_' . CURRENT_LANGUAGE])); ?></p>
                    </div>
                    
                    <!-- Service Price -->
                    <div class="service-pricing mb-4">
                        <div class="price-section p-3 bg-light rounded">
                            <?php if ($service['has_fixed_price'] && $service['price']): ?>
                            <div class="fixed-price">
                                <span class="price-label text-muted d-block"><?php echo __('service_price', 'Service Price'); ?></span>
                                <span class="price h3 text-primary fw-bold"><?php echo formatCurrency($service['price']); ?></span>
                            </div>
                            <?php else: ?>
                            <div class="custom-price">
                                <span class="price-label text-muted d-block"><?php echo __('pricing', 'Pricing'); ?></span>
                                <span class="price h5 text-info"><?php echo __('custom_pricing', 'Custom Pricing'); ?></span>
                                <small class="d-block text-muted mt-1"><?php echo __('contact_for_quote', 'Contact us for a personalized quote'); ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="service-actions mb-4">
                        <?php if ($service['has_fixed_price'] && $service['price']): ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <?php if (isLoggedIn()): ?>
                                <button class="btn btn-primary btn-lg w-100" onclick="addToCart('service', <?php echo $service['id']; ?>)">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                </button>
                                <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    <?php echo __('login_to_order', 'Login to Order'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <a href="<?php echo SITE_URL; ?>/contact.php?service=<?php echo $service['slug']; ?>" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-comments me-2"></i>
                                    <?php echo __('discuss_project', 'Discuss Project'); ?>
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <a href="<?php echo SITE_URL; ?>/contact.php?service=<?php echo $service['slug']; ?>" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-envelope me-2"></i>
                                    <?php echo __('get_quote', 'Get Quote'); ?>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <?php if (isLoggedIn()): ?>
                                <form method="POST" class="d-inline w-100">
                                    <?php echo csrfToken(); ?>
                                    <button type="submit" name="request_custom_service" class="btn btn-outline-primary btn-lg w-100">
                                        <i class="fas fa-cog me-2"></i>
                                        <?php echo __('request_custom', 'Request Custom'); ?>
                                    </button>
                                </form>
                                <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    <?php echo __('login_to_request', 'Login to Request'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Service Features -->
                    <div class="service-features">
                        <h6 class="fw-bold mb-3"><?php echo __('service_includes', 'This Service Includes'); ?></h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('professional_consultation', 'Professional consultation'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('custom_solution', 'Custom solution design'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('quality_assurance', 'Quality assurance testing'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('documentation', 'Complete documentation'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('support_included', '30 days post-delivery support'); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Service Details Tabs -->
<section class="service-details-tabs py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-4" id="serviceDetailsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
                            <i class="fas fa-info-circle me-2"></i><?php echo __('description', 'Description'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="process-tab" data-bs-toggle="tab" data-bs-target="#process" type="button" role="tab">
                            <i class="fas fa-tasks me-2"></i><?php echo __('process', 'Process'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                            <i class="fas fa-star me-2"></i><?php echo __('reviews', 'Reviews'); ?> (<?php echo $totalReviews; ?>)
                        </button>
                    </li>
                </ul>
                
                <!-- Tabs Content -->
                <div class="tab-content" id="serviceDetailsTabsContent">
                    <!-- Description Tab -->
                    <div class="tab-pane fade show active" id="description" role="tabpanel">
                        <div class="description-content">
                            <?php if ($service['description_' . CURRENT_LANGUAGE]): ?>
                            <div class="service-description">
                                <?php echo nl2br(htmlspecialchars($service['description_' . CURRENT_LANGUAGE])); ?>
                            </div>
                            <?php else: ?>
                            <p class="text-muted"><?php echo __('no_description', 'No detailed description available for this service.'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Process Tab -->
                    <div class="tab-pane fade" id="process" role="tabpanel">
                        <div class="process-content">
                            <h5 class="mb-4"><?php echo __('our_process', 'Our Development Process'); ?></h5>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="process-step">
                                        <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                                            <span class="fw-bold">1</span>
                                        </div>
                                        <h6 class="step-title"><?php echo __('consultation', 'Consultation'); ?></h6>
                                        <p class="step-description text-muted"><?php echo __('consultation_desc', 'We discuss your requirements and project scope in detail.'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="process-step">
                                        <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                                            <span class="fw-bold">2</span>
                                        </div>
                                        <h6 class="step-title"><?php echo __('planning', 'Planning'); ?></h6>
                                        <p class="step-description text-muted"><?php echo __('planning_desc', 'We create a detailed project plan and timeline.'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="process-step">
                                        <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                                            <span class="fw-bold">3</span>
                                        </div>
                                        <h6 class="step-title"><?php echo __('development', 'Development'); ?></h6>
                                        <p class="step-description text-muted"><?php echo __('development_desc', 'Our expert team develops your solution using best practices.'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="process-step">
                                        <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                                            <span class="fw-bold">4</span>
                                        </div>
                                        <h6 class="step-title"><?php echo __('delivery', 'Delivery'); ?></h6>
                                        <p class="step-description text-muted"><?php echo __('delivery_desc', 'We deliver the completed solution with full documentation and support.'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reviews Tab -->
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <div class="reviews-content">
                            <!-- Review Summary -->
                            <?php if ($service['average_rating'] > 0): ?>
                            <div class="review-summary mb-4 p-4 bg-white rounded shadow-sm">
                                <div class="row align-items-center">
                                    <div class="col-md-4 text-center">
                                        <div class="average-rating">
                                            <span class="rating-number display-4 fw-bold text-primary"><?php echo number_format($service['average_rating'], 1); ?></span>
                                            <div class="rating-stars mt-2">
                                                <?php echo generateStarRating($service['average_rating']); ?>
                                            </div>
                                            <p class="text-muted mt-2"><?php echo $service['review_count']; ?> <?php echo __('reviews', 'reviews'); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="rating-breakdown">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <?php
                                            $ratingCount = 0; // You would calculate this from database
                                            $percentage = $service['review_count'] > 0 ? ($ratingCount / $service['review_count']) * 100 : 0;
                                            ?>
                                            <div class="rating-bar d-flex align-items-center mb-2">
                                                <span class="rating-label me-2"><?php echo $i; ?> <i class="fas fa-star text-warning"></i></span>
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                                <span class="rating-count text-muted"><?php echo $ratingCount; ?></span>
                                            </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Add Review Form -->
                            <?php if (isLoggedIn() && !$userReview): ?>
                            <div class="add-review mb-4">
                                <h5 class="mb-3"><?php echo __('write_review', 'Write a Review'); ?></h5>
                                
                                <?php if ($review_message): ?>
                                <div class="alert alert-<?php echo $review_message['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                                    <?php echo $review_message['text']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" class="review-form">
                                    <?php echo csrfToken(); ?>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('rating', 'Rating'); ?> <span class="text-danger">*</span></label>
                                        <div class="star-rating-input">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                            <label for="star<?php echo $i; ?>" class="star-label">
                                                <i class="fas fa-star"></i>
                                            </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="review_title" class="form-label"><?php echo __('review_title', 'Review Title'); ?></label>
                                        <input type="text" class="form-control" id="review_title" name="title" maxlength="255">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="review_comment" class="form-label"><?php echo __('comment', 'Comment'); ?> <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="review_comment" name="comment" rows="4" required></textarea>
                                    </div>
                                    
                                    <button type="submit" name="submit_review" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        <?php echo __('submit_review', 'Submit Review'); ?>
                                    </button>
                                </form>
                            </div>
                            <?php elseif ($userReview): ?>
                            <div class="user-review-notice mb-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?php echo __('already_reviewed', 'You have already reviewed this service.'); ?>
                                </div>
                            </div>
                            <?php elseif (!isLoggedIn()): ?>
                            <div class="login-prompt mb-4">
                                <div class="alert alert-warning">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    <?php echo __('login_to_review', 'Please login to write a review.'); ?>
                                    <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-sm btn-outline-warning ms-2">
                                        <?php echo __('login', 'Login'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Reviews List -->
                            <div class="reviews-list">
                                <?php if (!empty($reviews)): ?>
                                <h5 class="mb-4"><?php echo __('customer_reviews', 'Customer Reviews'); ?></h5>
                                <?php foreach ($reviews as $review): ?>
                                <div class="review-item mb-4 p-4 bg-white rounded shadow-sm">
                                    <div class="review-header d-flex align-items-center mb-3">
                                        <div class="reviewer-avatar me-3">
                                            <?php if ($review['profile_image']): ?>
                                            <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_USERS . '/' . $review['profile_image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($review['first_name']); ?>" 
                                                 class="rounded-circle" width="50" height="50">
                                            <?php else: ?>
                                            <div class="avatar-placeholder rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="reviewer-info flex-grow-1">
                                            <h6 class="reviewer-name mb-0"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                            <div class="review-rating">
                                                <?php echo generateStarRating($review['rating']); ?>
                                            </div>
                                        </div>
                                        <div class="review-date text-muted">
                                            <small><?php echo formatDate($review['created_at']); ?></small>
                                        </div>
                                    </div>
                                    
                                    <?php if ($review['title']): ?>
                                    <h6 class="review-title mb-2"><?php echo htmlspecialchars($review['title']); ?></h6>
                                    <?php endif; ?>
                                    
                                    <div class="review-comment">
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <div class="no-reviews text-center py-5">
                                    <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                    <h5><?php echo __('no_reviews_yet', 'No Reviews Yet'); ?></h5>
                                    <p class="text-muted"><?php echo __('be_first_reviewer', 'Be the first to review this service!'); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Services -->
<?php if (!empty($relatedServices)): ?>
<section class="related-services py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h3 class="section-title"><?php echo __('related_services', 'Related Services'); ?></h3>
            <p class="section-subtitle text-muted"><?php echo __('related_services_desc', 'Other services you might be interested in'); ?></p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($relatedServices as $relatedService): ?>
            <div class="col-lg-3 col-md-6">
                <div class="service-card h-100">
                    <?php if ($relatedService['featured_image']): ?>
                    <div class="service-image">
                        <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_SERVICES . '/' . $relatedService['featured_image']; ?>" 
                             alt="<?php echo htmlspecialchars($relatedService['name_' . CURRENT_LANGUAGE]); ?>" 
                             class="img-fluid">
                    </div>
                    <?php endif; ?>
                    
                    <div class="service-content p-3">
                        <h6 class="service-name mb-2">
                            <a href="<?php echo SITE_URL; ?>/service-details.php?slug=<?php echo $relatedService['slug']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($relatedService['name_' . CURRENT_LANGUAGE]); ?>
                            </a>
                        </h6>
                        
                        <p class="service-description text-muted small mb-3">
                            <?php echo truncateText($relatedService['short_description_' . CURRENT_LANGUAGE], 80); ?>
                        </p>
                        
                        <div class="service-meta d-flex justify-content-between align-items-center">
                            <?php if ($relatedService['has_fixed_price'] && $relatedService['price']): ?>
                            <span class="service-price fw-bold text-primary">
                                <?php echo formatCurrency($relatedService['price']); ?>
                            </span>
                            <?php else: ?>
                            <span class="service-price text-muted">
                                <?php echo __('custom_pricing', 'Custom Pricing'); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($relatedService['average_rating'] > 0): ?>
                            <div class="service-rating">
                                <?php echo generateStarRating($relatedService['average_rating']); ?>
                                <small class="text-muted">(<?php echo $relatedService['review_count']; ?>)</small>
                            </div>
                            <?php endif; ?>
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
<section class="cta-section py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="cta-title mb-3"><?php echo __('ready_to_get_started', 'Ready to Get Started?'); ?></h3>
                <p class="cta-subtitle mb-0">
                    <?php echo __('contact_us_today', 'Contact us today to discuss your project requirements and get a personalized quote.'); ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="cta-buttons">
                    <a href="<?php echo SITE_URL; ?>/contact.php?service=<?php echo $service['slug']; ?>" class="btn btn-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>
                        <?php echo __('contact_us', 'Contact Us'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Star Rating Input Styles */
.star-rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.star-rating-input input[type="radio"] {
    display: none;
}

.star-rating-input .star-label {
    color: #ddd;
    font-size: 1.5rem;
    cursor: pointer;
    transition: color 0.2s;
    margin-right: 5px;
}

.star-rating-input .star-label:hover,
.star-rating-input .star-label:hover ~ .star-label {
    color: #ffc107;
}

.star-rating-input input[type="radio"]:checked ~ .star-label {
    color: #ffc107;
}

/* Service Card Hover Effects */
.service-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    overflow: hidden;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Product Actions Overlay */
.product-actions {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.service-card:hover .product-actions {
    opacity: 1;
}

.service-image {
    position: relative;
    overflow: hidden;
}

.service-image img {
    transition: transform 0.3s ease;
}

.service-card:hover .service-image img {
    transform: scale(1.05);
}

/* Review Item Styling */
.review-item {
    border-left: 4px solid #007bff;
}

/* Process Steps */
.process-step .step-number {
    font-size: 1.25rem;
}

/* Tab Content Styling */
.tab-content {
    min-height: 300px;
}

/* Rating Breakdown */
.rating-bar .progress {
    height: 8px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .service-actions .row {
        --bs-gutter-x: 0.5rem;
    }
    
    .service-actions .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
    
    .star-rating-input .star-label {
        font-size: 1.25rem;
        margin-right: 3px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    window.addToCart = function(type, id) {
        if (!window.SITE_CONFIG.isLoggedIn) {
            window.location.href = window.SITE_CONFIG.siteUrl + '/login.php?redirect=' + encodeURIComponent(window.location.href);
            return;
        }
        
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + window.SITE_CONFIG.texts.loading;
        
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
    
    // Star rating interaction
    const starInputs = document.querySelectorAll('.star-rating-input input[type="radio"]');
    starInputs.forEach(input => {
        input.addEventListener('change', function() {
            const rating = this.value;
            console.log('Rating selected:', rating);
        });
    });
    
    // Service image zoom functionality
    const mainImage = document.getElementById('mainServiceImage');
    if (mainImage) {
        mainImage.addEventListener('click', function() {
            // Create modal for image zoom
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${this.src}" class="img-fluid" alt="${this.alt}">
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
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
    
    // Auto-activate reviews tab if there's a review message
    <?php if ($review_message): ?>
    const reviewTab = document.getElementById('reviews-tab');
    if (reviewTab) {
        reviewTab.click();
    }
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>