<?php
/**
 * Admin Reports & Analytics
 * TechSavvyGenLtd Project
 */

require_once '../config/config.php';

// Check if user is staff/admin
if (!isStaff()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize classes
$orderClass = new Order();
$paymentClass = new Payment();
$userClass = new User();
$productClass = new Product();
$serviceClass = new Service();
$reviewClass = new Review();

// Get date range filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today
$period = $_GET['period'] ?? 'month';

// Validate dates
if (!$dateFrom) $dateFrom = date('Y-m-01');
if (!$dateTo) $dateTo = date('Y-m-d');

// Get statistics for the period
$orderStats = $orderClass->getOrderStatistics($dateFrom, $dateTo);
$paymentStats = $paymentClass->getPaymentStatistics($dateFrom, $dateTo);
$reviewStats = $reviewClass->getReviewStatistics();

// Get user statistics
$userStats = [
    'total_users' => $userClass->getAllUsers(1, 1)['total'],
    'new_users_period' => getNewUsersCount($dateFrom, $dateTo),
    'active_users' => getActiveUsersCount()
];

// Get top performing items
$topProducts = getTopProducts($dateFrom, $dateTo, 10);
$topServices = getTopServices($dateFrom, $dateTo, 10);

// Get monthly/daily data for charts
$chartData = getChartData($period, $dateFrom, $dateTo);

$page_title = 'Reports & Analytics';
$body_class = 'admin-page reports-page';

// Helper functions
function getNewUsersCount($dateFrom, $dateTo) {
    global $userClass;
    $db = Database::getInstance();
    try {
        return $db->fetchColumn(
            "SELECT COUNT(*) FROM " . TBL_USERS . " WHERE DATE(registration_date) BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        );
    } catch (Exception $e) {
        return 0;
    }
}

function getActiveUsersCount() {
    global $userClass;
    $db = Database::getInstance();
    try {
        return $db->fetchColumn(
            "SELECT COUNT(*) FROM " . TBL_USERS . " WHERE status = 'active'",
            []
        );
    } catch (Exception $e) {
        return 0;
    }
}

function getTopProducts($dateFrom, $dateTo, $limit = 10) {
    $db = Database::getInstance();
    try {
        $sql = "SELECT p.id, p.name_" . CURRENT_LANGUAGE . " as name, p.price, p.featured_image,
                       COUNT(oi.id) as order_count,
                       SUM(oi.quantity) as total_quantity,
                       SUM(oi.total) as total_revenue
                FROM " . TBL_PRODUCTS . " p
                LEFT JOIN " . TBL_ORDER_ITEMS . " oi ON p.id = oi.product_id
                LEFT JOIN " . TBL_ORDERS . " o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY p.id
                ORDER BY total_revenue DESC
                LIMIT {$limit}";
        
        return $db->fetchAll($sql, [$dateFrom, $dateTo]);
    } catch (Exception $e) {
        return [];
    }
}

function getTopServices($dateFrom, $dateTo, $limit = 10) {
    $db = Database::getInstance();
    try {
        $sql = "SELECT s.id, s.name_" . CURRENT_LANGUAGE . " as name, s.price, s.featured_image,
                       COUNT(oi.id) as order_count,
                       SUM(oi.quantity) as total_quantity,
                       SUM(oi.total) as total_revenue
                FROM " . TBL_SERVICES . " s
                LEFT JOIN " . TBL_ORDER_ITEMS . " oi ON s.id = oi.service_id
                LEFT JOIN " . TBL_ORDERS . " o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY s.id
                ORDER BY total_revenue DESC
                LIMIT {$limit}";
        
        return $db->fetchAll($sql, [$dateFrom, $dateTo]);
    } catch (Exception $e) {
        return [];
    }
}

function getChartData($period, $dateFrom, $dateTo) {
    $db = Database::getInstance();
    
    if ($period === 'day') {
        $format = '%Y-%m-%d';
        $interval = 'DAY';
    } else {
        $format = '%Y-%m';
        $interval = 'MONTH';
    }
    
    try {
        // Orders chart data
        $ordersChart = $db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '{$format}') as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as revenue
             FROM " . TBL_ORDERS . "
             WHERE created_at BETWEEN ? AND ?
             GROUP BY period
             ORDER BY period",
            [$dateFrom, $dateTo]
        );
        
        // Users chart data
        $usersChart = $db->fetchAll(
            "SELECT DATE_FORMAT(registration_date, '{$format}') as period,
                    COUNT(*) as user_count
             FROM " . TBL_USERS . "
             WHERE registration_date BETWEEN ? AND ?
             GROUP BY period
             ORDER BY period",
            [$dateFrom, $dateTo]
        );
        
        return [
            'orders' => $ordersChart,
            'users' => $usersChart
        ];
    } catch (Exception $e) {
        return [
            'orders' => [],
            'users' => []
        ];
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">Reports & Analytics</h1>
                <p class="admin-subtitle">Business insights and performance metrics</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download me-2"></i>Export Reports
                </button>
                <button class="btn btn-outline-info" onclick="printReport()">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Period</label>
                        <select class="form-select" name="period">
                            <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Daily</option>
                            <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                <i class="fas fa-dollar-sign fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo formatCurrency($paymentStats['total_revenue'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Revenue</div>
                                <div class="stat-change text-success small">
                                    <i class="fas fa-arrow-up"></i> +12.5%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                                <i class="fas fa-shopping-cart fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($orderStats['total_orders'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Orders</div>
                                <div class="stat-change text-success small">
                                    <i class="fas fa-arrow-up"></i> +8.3%
                                </div>
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
                                <i class="fas fa-users fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($userStats['new_users_period']); ?></div>
                                <div class="stat-label text-muted small">New Users</div>
                                <div class="stat-change text-warning small">
                                    <i class="fas fa-arrow-right"></i> +2.1%
                                </div>
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
                                <i class="fas fa-star fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($reviewStats['average_rating'] ?? 0, 1); ?></div>
                                <div class="stat-label text-muted small">Avg. Rating</div>
                                <div class="stat-change text-success small">
                                    <i class="fas fa-arrow-up"></i> +0.2
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Revenue Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Revenue & Orders Trend</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" onclick="toggleChart('revenue')">Revenue</button>
                            <button class="btn btn-outline-primary" onclick="toggleChart('orders')">Orders</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Order Status Breakdown -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="orderStatusChart" height="300"></canvas>
                        <div class="order-status-legend mt-3">
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="legend-item">
                                        <div class="legend-color bg-warning rounded-circle d-inline-block" style="width: 12px; height: 12px;"></div>
                                        <small class="ms-1">Pending: <?php echo $orderStats['pending_orders'] ?? 0; ?></small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="legend-item">
                                        <div class="legend-color bg-info rounded-circle d-inline-block" style="width: 12px; height: 12px;"></div>
                                        <small class="ms-1">Processing: <?php echo $orderStats['processing_orders'] ?? 0; ?></small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="legend-item">
                                        <div class="legend-color bg-success rounded-circle d-inline-block" style="width: 12px; height: 12px;"></div>
                                        <small class="ms-1">Completed: <?php echo $orderStats['completed_orders'] ?? 0; ?></small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="legend-item">
                                        <div class="legend-color bg-danger rounded-circle d-inline-block" style="width: 12px; height: 12px;"></div>
                                        <small class="ms-1">Cancelled: <?php echo $orderStats['cancelled_orders'] ?? 0; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="row g-4 mb-4">
            <!-- Top Products -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Products</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($topProducts)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProducts as $index => $product): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rank-badge me-2">
                                                    <span class="badge bg-primary"><?php echo $index + 1; ?></span>
                                                </div>
                                                <img src="<?php echo $product['featured_image'] ? UPLOADS_URL . '/' . UPLOAD_PATH_PRODUCTS . '/' . $product['featured_image'] : ASSETS_URL . '/images/placeholder.jpg'; ?>" 
                                                     class="rounded me-2" width="40" height="40" alt="Product">
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <small class="text-muted"><?php echo formatCurrency($product['price']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-medium"><?php echo $product['order_count']; ?></span>
                                            <br><small class="text-muted"><?php echo $product['total_quantity']; ?> items</small>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-success"><?php echo formatCurrency($product['total_revenue']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-box fa-2x mb-2"></i>
                            <p>No product data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Services -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Services</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($topServices)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topServices as $index => $service): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rank-badge me-2">
                                                    <span class="badge bg-success"><?php echo $index + 1; ?></span>
                                                </div>
                                                <img src="<?php echo $service['featured_image'] ? UPLOADS_URL . '/' . UPLOAD_PATH_SERVICES . '/' . $service['featured_image'] : ASSETS_URL . '/images/placeholder.jpg'; ?>" 
                                                     class="rounded me-2" width="40" height="40" alt="Service">
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($service['name']); ?></div>
                                                    <small class="text-muted"><?php echo $service['price'] ? formatCurrency($service['price']) : 'Custom Price'; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-medium"><?php echo $service['order_count']; ?></span>
                                            <br><small class="text-muted"><?php echo $service['total_quantity']; ?> orders</small>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-success"><?php echo formatCurrency($service['total_revenue']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-cogs fa-2x mb-2"></i>
                            <p>No service data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Analytics -->
        <div class="row g-4">
            <!-- Payment Methods -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment Methods</h5>
                    </div>
                    <div class="card-body">
                        <div class="payment-methods-stats">
                            <div class="method-stat d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fab fa-cc-stripe text-primary me-2"></i>
                                    <span>Stripe</span>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold"><?php echo $paymentStats['stripe_payments'] ?? 0; ?></div>
                                    <small class="text-muted">payments</small>
                                </div>
                            </div>
                            <div class="method-stat d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fab fa-paypal text-info me-2"></i>
                                    <span>PayPal</span>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold"><?php echo $paymentStats['paypal_payments'] ?? 0; ?></div>
                                    <small class="text-muted">payments</small>
                                </div>
                            </div>
                            <div class="method-stat d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-wallet text-success me-2"></i>
                                    <span>Wallet</span>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold"><?php echo $paymentStats['wallet_payments'] ?? 0; ?></div>
                                    <small class="text-muted">payments</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Activity -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">User Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="user-stats">
                            <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="stat-label text-muted small">Total Users</div>
                                    <div class="stat-value fw-bold"><?php echo number_format($userStats['total_users']); ?></div>
                                </div>
                                <i class="fas fa-users text-primary"></i>
                            </div>
                            <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="stat-label text-muted small">Active Users</div>
                                    <div class="stat-value fw-bold"><?php echo number_format($userStats['active_users']); ?></div>
                                </div>
                                <i class="fas fa-user-check text-success"></i>
                            </div>
                            <div class="stat-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label text-muted small">New This Period</div>
                                    <div class="stat-value fw-bold"><?php echo number_format($userStats['new_users_period']); ?></div>
                                </div>
                                <i class="fas fa-user-plus text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="generateReport('sales')">
                                <i class="fas fa-chart-line me-2"></i>Sales Report
                            </button>
                            <button class="btn btn-outline-success" onclick="generateReport('financial')">
                                <i class="fas fa-dollar-sign me-2"></i>Financial Report
                            </button>
                            <button class="btn btn-outline-info" onclick="generateReport('users')">
                                <i class="fas fa-users me-2"></i>User Report
                            </button>
                            <button class="btn btn-outline-warning" onclick="generateReport('inventory')">
                                <i class="fas fa-boxes me-2"></i>Inventory Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Reports</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label class="form-label">Report Type</label>
                        <select class="form-select" name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="sales">Sales Report</option>
                            <option value="financial">Financial Report</option>
                            <option value="users">User Report</option>
                            <option value="products">Product Report</option>
                            <option value="services">Service Report</option>
                            <option value="reviews">Review Report</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" name="format" required>
                            <option value="xlsx">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" name="export_date_from" value="<?php echo $dateFrom; ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" name="export_date_to" value="<?php echo $dateTo; ?>">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="exportReport()">Export Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// Chart data from PHP
const chartData = <?php echo json_encode($chartData); ?>;
const orderStats = <?php echo json_encode($orderStats); ?>;

// Initialize charts
let revenueChart, orderStatusChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    
    const labels = chartData.orders.map(item => item.period);
    const revenueData = chartData.orders.map(item => parseFloat(item.revenue || 0));
    const orderData = chartData.orders.map(item => parseInt(item.order_count || 0));
    
    revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: revenueData,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Orders',
                    data: orderData,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
    
    // Order Status Chart
    const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
    
    orderStatusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Processing', 'Completed', 'Cancelled'],
            datasets: [{
                data: [
                    orderStats.pending_orders || 0,
                    orderStats.processing_orders || 0,
                    orderStats.completed_orders || 0,
                    orderStats.cancelled_orders || 0
                ],
                backgroundColor: [
                    '#ffc107', // warning
                    '#17a2b8', // info
                    '#28a745', // success
                    '#dc3545'  // danger
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function toggleChart(type) {
    // Update button states
    const buttons = document.querySelectorAll('.btn-group button');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // This would toggle between different chart views
    // Implementation depends on specific requirements
}

function generateReport(type) {
    const params = new URLSearchParams({
        type: type,
        date_from: '<?php echo $dateFrom; ?>',
        date_to: '<?php echo $dateTo; ?>',
        format: 'pdf'
    });
    
    window.open(`${window.SITE_CONFIG.apiUrl}/reports/generate.php?${params.toString()}`, '_blank');
}

function exportReport() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        params.append(key, value);
    }
    
    window.open(`${window.SITE_CONFIG.apiUrl}/reports/export.php?${params.toString()}`, '_blank');
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
    modal.hide();
}

function printReport() {
    // Hide non-printable elements
    const elements = document.querySelectorAll('.btn, .form-control, .nav, .pagination');
    elements.forEach(el => el.style.display = 'none');
    
    // Print
    window.print();
    
    // Restore elements
    elements.forEach(el => el.style.display = '');
}

// Auto-refresh data every 5 minutes
setInterval(() => {
    if (document.visibilityState === 'visible') {
        // Only refresh if page is visible
        location.reload();
    }
}, 300000);
</script>

<style>
@media print {
    .admin-sidebar,
    .admin-header .btn-group,
    .card-header .btn-group,
    .no-print {
        display: none !important;
    }
    
    .admin-content {
        padding: 0 !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>