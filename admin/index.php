<?php
/**
 * Admin Dashboard - TechSavvyGenLtd
 */

require_once '../config/config.php';

// Check if user is logged in and is staff
if (!isLoggedIn() || !isStaff()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Initialize classes
$userClass = new User();
$productClass = new Product();
$serviceClass = new Service();
$orderClass = new Order();
$paymentClass = new Payment();
$reviewClass = new Review();

// Get dashboard statistics
$userStats = $userClass->getAllUsers(1, 1);
$productStats = $productClass->getAll(1, 1);
$serviceStats = $serviceClass->getAll(1, 1);
$orderStats = $orderClass->getOrderStatistics();
$paymentStats = $paymentClass->getPaymentStatistics();
$reviewStats = $reviewClass->getReviewStatistics();

// Get recent activities
$recentOrders = $orderClass->getAllOrders(1, 5)['orders'];
$recentUsers = $userClass->getAllUsers(1, 5)['users'];
$pendingReviews = $reviewClass->getPendingReviews(5);

// Page data
$page_title = __('admin_dashboard', 'Admin Dashboard');
$body_class = 'admin-dashboard';
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 col-md-3">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10 col-md-9">
            <!-- Welcome Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><?php echo __('dashboard', 'Dashboard'); ?></h1>
                    <p class="text-muted">
                        <?php echo __('welcome_back', 'Welcome back'), ' ', $_SESSION['first_name']; ?>! 
                        <?php echo __('dashboard_subtitle', 'Here\'s what\'s happening with your business today.'); ?>
                    </p>
                </div>
                <div class="text-muted small">
                    <?php echo __('last_updated', 'Last updated'); ?>: <?php echo date('M j, Y g:i A'); ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <!-- Total Users -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                                    <i class="fas fa-users fa-lg"></i>
                                </div>
                                <div>
                                    <div class="stat-value h4 mb-0"><?php echo number_format($userStats['total']); ?></div>
                                    <div class="stat-label text-muted small"><?php echo __('total_users', 'Total Users'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                    <i class="fas fa-box fa-lg"></i>
                                </div>
                                <div>
                                    <div class="stat-value h4 mb-0"><?php echo number_format($productStats['total']); ?></div>
                                    <div class="stat-label text-muted small"><?php echo __('total_products', 'Total Products'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info bg-opacity-10 text-info rounded-circle p-3 me-3">
                                    <i class="fas fa-shopping-cart fa-lg"></i>
                                </div>
                                <div>
                                    <div class="stat-value h4 mb-0"><?php echo number_format($orderStats['total_orders'] ?? 0); ?></div>
                                    <div class="stat-label text-muted small"><?php echo __('total_orders', 'Total Orders'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-circle p-3 me-3">
                                    <i class="fas fa-dollar-sign fa-lg"></i>
                                </div>
                                <div>
                                    <div class="stat-value h4 mb-0"><?php echo formatCurrency($paymentStats['total_revenue'] ?? 0); ?></div>
                                    <div class="stat-label text-muted small"><?php echo __('total_revenue', 'Total Revenue'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Orders -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo __('recent_orders', 'Recent Orders'); ?></h5>
                                <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                    <?php echo __('view_all', 'View All'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recentOrders)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?php echo __('order_number', 'Order #'); ?></th>
                                            <th><?php echo __('customer', 'Customer'); ?></th>
                                            <th><?php echo __('amount', 'Amount'); ?></th>
                                            <th><?php echo __('status', 'Status'); ?></th>
                                            <th><?php echo __('date', 'Date'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                            <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($order['created_at']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <p><?php echo __('no_orders_yet', 'No orders yet'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats & Actions -->
                <div class="col-lg-4">
                    <!-- Order Status Overview -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0"><?php echo __('order_status_overview', 'Order Status Overview'); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted"><?php echo __('pending', 'Pending'); ?></span>
                                <span class="fw-medium"><?php echo $orderStats['pending_orders'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted"><?php echo __('processing', 'Processing'); ?></span>
                                <span class="fw-medium"><?php echo $orderStats['processing_orders'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted"><?php echo __('completed', 'Completed'); ?></span>
                                <span class="fw-medium"><?php echo $orderStats['completed_orders'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted"><?php echo __('cancelled', 'Cancelled'); ?></span>
                                <span class="fw-medium"><?php echo $orderStats['cancelled_orders'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0"><?php echo __('quick_actions', 'Quick Actions'); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="products.php?action=add" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i><?php echo __('add_product', 'Add Product'); ?>
                                </a>
                                <a href="services.php?action=add" class="btn btn-outline-success">
                                    <i class="fas fa-plus me-2"></i><?php echo __('add_service', 'Add Service'); ?>
                                </a>
                                <a href="categories.php?action=add" class="btn btn-outline-info">
                                    <i class="fas fa-plus me-2"></i><?php echo __('add_category', 'Add Category'); ?>
                                </a>
                                <a href="reports.php" class="btn btn-outline-warning">
                                    <i class="fas fa-chart-bar me-2"></i><?php echo __('view_reports', 'View Reports'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Statistics Row -->
            <div class="row g-4 mt-1">
                <!-- Recent Users -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo __('recent_users', 'Recent Users'); ?></h5>
                                <a href="users.php" class="btn btn-outline-primary btn-sm">
                                    <?php echo __('view_all', 'View All'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recentUsers)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentUsers as $user): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo ASSETS_URL; ?>/images/users/<?php echo $user['profile_image'] ?: 'default.png'; ?>" 
                                             class="rounded-circle me-3" width="40" height="40" alt="User">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'warning' : 'primary'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <p><?php echo __('no_users_yet', 'No users yet'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Pending Reviews -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo __('pending_reviews', 'Pending Reviews'); ?></h5>
                                <a href="reviews.php?status=pending" class="btn btn-outline-primary btn-sm">
                                    <?php echo __('view_all', 'View All'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($pendingReviews)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pendingReviews as $review): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                            <p class="mb-1 small"><?php echo truncateText($review['comment'], 80); ?></p>
                                            <small class="text-muted"><?php echo $review['product_name'] ?? $review['service_name']; ?></small>
                                        </div>
                                        <div class="text-end">
                                            <?php echo generateStarRating($review['rating']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-star fa-2x mb-2"></i>
                                <p><?php echo __('no_pending_reviews', 'No pending reviews'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dashboard every 5 minutes
    setTimeout(() => {
        window.location.reload();
    }, 300000);
    
    // Add hover effects to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>