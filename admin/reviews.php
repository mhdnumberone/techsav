<?php
/**
 * Admin Reviews Management
 * TechSavvyGenLtd Project
 */

require_once '../config/config.php';

// Check if user is staff/admin
if (!isStaff()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize classes
$reviewClass = new Review();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'approve_review':
                $reviewId = (int)$_POST['review_id'];
                $result = $reviewClass->approve($reviewId);
                if ($result['success']) {
                    $message = 'Review approved successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'reject_review':
                $reviewId = (int)$_POST['review_id'];
                $result = $reviewClass->reject($reviewId);
                if ($result['success']) {
                    $message = 'Review rejected successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete_review':
                $reviewId = (int)$_POST['review_id'];
                $result = $reviewClass->delete($reviewId);
                if ($result['success']) {
                    $message = 'Review deleted successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'bulk_action':
                $reviewIds = $_POST['review_ids'] ?? [];
                $bulkAction = $_POST['bulk_action_type'] ?? '';
                
                if (!empty($reviewIds) && !empty($bulkAction)) {
                    $successCount = 0;
                    foreach ($reviewIds as $reviewId) {
                        $reviewId = (int)$reviewId;
                        switch ($bulkAction) {
                            case 'approve':
                                $result = $reviewClass->approve($reviewId);
                                break;
                            case 'reject':
                                $result = $reviewClass->reject($reviewId);
                                break;
                            case 'delete':
                                $result = $reviewClass->delete($reviewId);
                                break;
                            default:
                                $result = ['success' => false];
                        }
                        if ($result['success']) {
                            $successCount++;
                        }
                    }
                    $message = "Bulk action completed. {$successCount} reviews processed.";
                } else {
                    $error = 'Please select reviews and action';
                }
                break;
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$rating = $_GET['rating'] ?? '';
$product_id = $_GET['product_id'] ?? '';
$service_id = $_GET['service_id'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

// Build filters array
$filters = array_filter([
    'search' => $search,
    'status' => $status,
    'rating' => $rating,
    'product_id' => $product_id,
    'service_id' => $service_id
]);

// Get reviews
$reviewsData = $reviewClass->getAllReviews($page, ADMIN_ITEMS_PER_PAGE, $filters);
$reviews = $reviewsData['reviews'];
$totalPages = $reviewsData['pages'];
$totalReviews = $reviewsData['total'];

// Get review statistics
$reviewStats = $reviewClass->getReviewStatistics();

// Get pending reviews for quick access
$pendingReviews = $reviewClass->getPendingReviews(5);

$page_title = 'Reviews Management';
$body_class = 'admin-page reviews-page';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">Reviews Management</h1>
                <p class="admin-subtitle">Moderate and manage customer reviews</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewStatsModal">
                    <i class="fas fa-chart-bar me-2"></i>Statistics
                </button>
                <button class="btn btn-outline-success" onclick="exportReviews()">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                                <i class="fas fa-star fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($reviewStats['total_reviews'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Reviews</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-circle p-3 me-3">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($reviewStats['pending_reviews'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Pending Reviews</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                <i class="fas fa-check fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($reviewStats['approved_reviews'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Approved Reviews</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info bg-opacity-10 text-info rounded-circle p-3 me-3">
                                <i class="fas fa-star-half-alt fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($reviewStats['average_rating'] ?? 0, 1); ?></div>
                                <div class="stat-label text-muted small">Average Rating</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Reviews List -->
            <div class="col-lg-8">
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search reviews..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="rating">
                                    <option value="">All Ratings</option>
                                    <option value="5" <?php echo $rating === '5' ? 'selected' : ''; ?>>5 Stars</option>
                                    <option value="4" <?php echo $rating === '4' ? 'selected' : ''; ?>>4 Stars</option>
                                    <option value="3" <?php echo $rating === '3' ? 'selected' : ''; ?>>3 Stars</option>
                                    <option value="2" <?php echo $rating === '2' ? 'selected' : ''; ?>>2 Stars</option>
                                    <option value="1" <?php echo $rating === '1' ? 'selected' : ''; ?>>1 Star</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="reviews.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" id="bulkActionsForm">
                            <?php echo csrfToken(); ?>
                            <input type="hidden" name="action" value="bulk_action">
                            
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <input type="checkbox" class="form-check-input" id="selectAllReviews">
                                    <label class="form-check-label" for="selectAllReviews">Select All</label>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select" name="bulk_action_type" required>
                                        <option value="">Bulk Actions</option>
                                        <option value="approve">Approve Selected</option>
                                        <option value="reject">Reject Selected</option>
                                        <option value="delete">Delete Selected</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-outline-primary" onclick="return confirmBulkAction()">
                                        Apply
                                    </button>
                                </div>
                                <div class="col-auto ms-auto">
                                    <small class="text-muted">
                                        <span id="selectedCount">0</span> reviews selected
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reviews Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Reviews (<?php echo $totalReviews; ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($reviews)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No reviews found</p>
                        </div>
                        <?php else: ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                            <div class="review-item border-bottom p-3">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <input type="checkbox" class="form-check-input review-checkbox" 
                                               name="review_ids[]" value="<?php echo $review['id']; ?>" form="bulkActionsForm">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                                <div class="rating mb-1">
                                                    <?php echo generateStarRating($review['rating']); ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo $review['product_name'] ?? $review['service_name']; ?> •
                                                    <?php echo formatDate($review['created_at']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?php echo $review['status'] === 'approved' ? 'success' : ($review['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($review['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($review['title']): ?>
                                        <h6 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h6>
                                        <?php endif; ?>
                                        
                                        <p class="review-comment mb-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        
                                        <div class="review-actions">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($review['status'] === 'pending'): ?>
                                                <button class="btn btn-outline-success" onclick="approveReview(<?php echo $review['id']; ?>)">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                                <button class="btn btn-outline-warning" onclick="rejectReview(<?php echo $review['id']; ?>)">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($review['status'] === 'rejected'): ?>
                                                <button class="btn btn-outline-success" onclick="approveReview(<?php echo $review['id']; ?>)">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-outline-primary" onclick="viewReviewDetails(<?php echo $review['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deleteReview(<?php echo $review['id']; ?>)">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <?php
                        $baseUrl = ADMIN_URL . '/reviews.php?' . http_build_query(array_filter([
                            'search' => $search,
                            'status' => $status,
                            'rating' => $rating,
                            'product_id' => $product_id,
                            'service_id' => $service_id
                        ]));
                        echo generatePagination($page, $totalPages, $baseUrl);
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Pending Reviews -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pending Reviews</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pendingReviews)): ?>
                        <div class="pending-reviews">
                            <?php foreach ($pendingReviews as $pending): ?>
                            <div class="pending-review mb-3 p-2 border rounded">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong class="small"><?php echo htmlspecialchars($pending['first_name'] . ' ' . $pending['last_name']); ?></strong>
                                    <?php echo generateStarRating($pending['rating'], 'sm'); ?>
                                </div>
                                <p class="small mb-1"><?php echo truncateText($pending['comment'], 80); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><?php echo $pending['product_name'] ?? $pending['service_name']; ?></small>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-success btn-sm" onclick="approveReview(<?php echo $pending['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="rejectReview(<?php echo $pending['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center">
                            <a href="reviews.php?status=pending" class="btn btn-outline-primary btn-sm">
                                View All Pending
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p>No pending reviews</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Rating Distribution -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Rating Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="rating-distribution">
                            <?php
                            $totalRatings = $reviewStats['total_reviews'] ?? 1;
                            for ($i = 5; $i >= 1; $i--):
                                $count = $reviewStats["{$i}_star_reviews"] ?? 0;
                                $percentage = $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0;
                            ?>
                            <div class="rating-row d-flex align-items-center mb-2">
                                <div class="rating-stars me-2">
                                    <?php echo generateStarRating($i, 'sm'); ?>
                                </div>
                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $count; ?></small>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Details Modal -->
<div class="modal fade" id="reviewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reviewDetailsContent">
                <!-- Review details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Review Statistics Modal -->
<div class="modal fade" id="reviewStatsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-primary"><?php echo number_format($reviewStats['total_reviews'] ?? 0); ?></h3>
                            <p>Total Reviews</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-warning"><?php echo number_format($reviewStats['average_rating'] ?? 0, 1); ?></h3>
                            <p>Average Rating</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-success"><?php echo number_format($reviewStats['approved_reviews'] ?? 0); ?></h3>
                            <p>Approved Reviews</p>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h6>Rating Breakdown</h6>
                <div class="row g-2">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <span><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?></span>
                            <span class="fw-bold"><?php echo number_format($reviewStats["{$i}_star_reviews"] ?? 0); ?></span>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle review actions
function approveReview(reviewId) {
    if (confirm('Are you sure you want to approve this review?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <?php echo csrfToken(); ?>
            <input type="hidden" name="action" value="approve_review">
            <input type="hidden" name="review_id" value="${reviewId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectReview(reviewId) {
    if (confirm('Are you sure you want to reject this review?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <?php echo csrfToken(); ?>
            <input type="hidden" name="action" value="reject_review">
            <input type="hidden" name="review_id" value="${reviewId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteReview(reviewId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the review!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="delete_review">
                <input type="hidden" name="review_id" value="${reviewId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function viewReviewDetails(reviewId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/reviews/get.php?id=${reviewId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const review = data.review;
                let reviewHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Review Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Customer:</strong></td><td>${review.first_name} ${review.last_name}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${review.email}</td></tr>
                                <tr><td><strong>Rating:</strong></td><td>${generateStarRating(review.rating)}</td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-${review.status === 'approved' ? 'success' : (review.status === 'pending' ? 'warning' : 'danger')}">${review.status}</span></td></tr>
                                <tr><td><strong>Date:</strong></td><td>${review.created_at}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Item Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Type:</strong></td><td>${review.product_name ? 'Product' : 'Service'}</td></tr>
                                <tr><td><strong>Name:</strong></td><td>${review.product_name || review.service_name}</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    ${review.title ? `<h6>Review Title</h6><p>${review.title}</p>` : ''}
                    
                    <h6>Review Comment</h6>
                    <p>${review.comment.replace(/\n/g, '<br>')}</p>
                `;
                
                document.getElementById('reviewDetailsContent').innerHTML = reviewHtml;
                new bootstrap.Modal(document.getElementById('reviewDetailsModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load review details', 'error');
        });
}

function exportReviews() {
    const params = new URLSearchParams(window.location.search);
    window.open(`${window.SITE_CONFIG.apiUrl}/reviews/export.php?${params.toString()}`, '_blank');
}

function confirmBulkAction() {
    const selectedReviews = document.querySelectorAll('.review-checkbox:checked');
    const action = document.querySelector('[name="bulk_action_type"]').value;
    
    if (selectedReviews.length === 0) {
        Swal.fire('Warning', 'Please select at least one review', 'warning');
        return false;
    }
    
    if (!action) {
        Swal.fire('Warning', 'Please select an action', 'warning');
        return false;
    }
    
    return confirm(`Are you sure you want to ${action} ${selectedReviews.length} review(s)?`);
}

// Handle checkbox selections
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllReviews');
    const reviewCheckboxes = document.querySelectorAll('.review-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    
    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        reviewCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
    });
    
    // Individual checkbox functionality
    reviewCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    function updateSelectedCount() {
        const checked = document.querySelectorAll('.review-checkbox:checked').length;
        selectedCount.textContent = checked;
        
        // Update select all checkbox state
        selectAllCheckbox.indeterminate = checked > 0 && checked < reviewCheckboxes.length;
        selectAllCheckbox.checked = checked === reviewCheckboxes.length;
    }
    
    // Initialize count
    updateSelectedCount();
});

// Helper function for star rating display (JavaScript equivalent)
function generateStarRating(rating, size = '') {
    let stars = '';
    const sizeClass = size ? `star-rating-${size}` : '';
    
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += `<i class="fas fa-star text-warning ${sizeClass}"></i>`;
        } else {
            stars += `<i class="far fa-star text-muted ${sizeClass}"></i>`;
        }
    }
    
    return stars;
}
</script>

<?php include '../includes/footer.php'; ?>