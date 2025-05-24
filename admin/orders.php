<?php
/**
 * Admin Orders Management
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
$invoiceClass = new Invoice();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_order_status':
                $orderId = (int)$_POST['order_id'];
                $status = $_POST['status'];
                $result = $orderClass->updateStatus($orderId, $status);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_payment_status':
                $orderId = (int)$_POST['order_id'];
                $paymentStatus = $_POST['payment_status'];
                $result = $orderClass->updatePaymentStatus($orderId, $paymentStatus);
                if ($result['success']) {
                    $message = 'Payment status updated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'cancel_order':
                $orderId = (int)$_POST['order_id'];
                $reason = cleanInput($_POST['reason'] ?? '');
                $result = $orderClass->cancelOrder($orderId, $reason);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'generate_invoice':
                $orderId = (int)$_POST['order_id'];
                $result = $invoiceClass->createFromOrder($orderId);
                if ($result['success']) {
                    $message = 'Invoice generated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'send_order_email':
                $orderId = (int)$_POST['order_id'];
                $order = $orderClass->getById($orderId);
                if ($order && $order['email']) {
                    $subject = "Order Update - {$order['order_number']}";
                    $message_body = cleanInput($_POST['message_body']);
                    if (sendEmail($order['email'], $subject, $message_body)) {
                        $message = 'Email sent successfully';
                    } else {
                        $error = 'Failed to send email';
                    }
                } else {
                    $error = 'Order not found or no email address';
                }
                break;
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

// Build filters array
$filters = array_filter([
    'search' => $search,
    'status' => $status,
    'payment_status' => $payment_status,
    'date_from' => $date_from,
    'date_to' => $date_to
]);

// Get orders
$ordersData = $orderClass->getAllOrders($page, ADMIN_ITEMS_PER_PAGE, $filters);
$orders = $ordersData['orders'];
$totalPages = $ordersData['pages'];
$totalOrders = $ordersData['total'];

// Get order statistics
$orderStats = $orderClass->getOrderStatistics($date_from, $date_to);

$page_title = 'Orders Management';
$body_class = 'admin-page orders-page';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">Orders Management</h1>
                <p class="admin-subtitle">Track and manage customer orders</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-success" onclick="exportOrders()">
                    <i class="fas fa-download me-2"></i>Export Orders
                </button>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderStatsModal">
                    <i class="fas fa-chart-bar me-2"></i>Statistics
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
                                <i class="fas fa-shopping-cart fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($orderStats['total_orders'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Orders</div>
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
                                <div class="stat-value h4 mb-0"><?php echo number_format($orderStats['pending_orders'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Pending Orders</div>
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
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($orderStats['completed_orders'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Completed Orders</div>
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
                                <i class="fas fa-dollar-sign fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo formatCurrency($orderStats['total_revenue'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Revenue</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="payment_status">
                            <option value="">All Payments</option>
                            <option value="pending" <?php echo $payment_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $payment_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $payment_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $payment_status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Orders (<?php echo $totalOrders; ?>)</h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="bulkActions()">
                        <i class="fas fa-tasks me-1"></i>Bulk Actions
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Order Status</th>
                                <th>Payment Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No orders found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input order-checkbox" value="<?php echo $order['id']; ?>">
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                                            <small class="text-muted">ID: <?php echo $order['id']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo formatCurrency($order['total_amount']); ?></div>
                                        <?php if ($order['payment_method']): ?>
                                        <small class="text-muted"><?php echo ucfirst($order['payment_method']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm order-status-select" 
                                                data-order-id="<?php echo $order['id']; ?>"
                                                <?php echo $order['status'] === 'completed' || $order['status'] === 'cancelled' ? 'disabled' : ''; ?>>
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            <option value="refunded" <?php echo $order['status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm payment-status-select" 
                                                data-order-id="<?php echo $order['id']; ?>"
                                                <?php echo $order['payment_status'] === 'paid' || $order['payment_status'] === 'refunded' ? 'disabled' : ''; ?>>
                                            <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div><?php echo formatDate($order['created_at']); ?></div>
                                        <small class="text-muted"><?php echo formatTime($order['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewOrder(<?php echo $order['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="generateInvoice(<?php echo $order['id']; ?>)" title="Generate Invoice">
                                                <i class="fas fa-file-invoice"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="sendOrderEmail(<?php echo $order['id']; ?>)" title="Send Email">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                            <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                                            <button class="btn btn-outline-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)" title="Cancel Order">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <?php
                $baseUrl = ADMIN_URL . '/orders.php?' . http_build_query(array_filter([
                    'search' => $search,
                    'status' => $status,
                    'payment_status' => $payment_status,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ]));
                echo generatePagination($page, $totalPages, $baseUrl);
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="cancel_order">
                <input type="hidden" name="order_id" id="cancelOrderId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason</label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="Please provide a reason for cancellation..."></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action will cancel the order and restore any affected inventory. The customer will be notified.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Email Modal -->
<div class="modal fade" id="sendEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="send_order_email">
                <input type="hidden" name="order_id" id="emailOrderId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Send Order Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message_body" rows="5" required placeholder="Enter your message to the customer..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Order Statistics Modal -->
<div class="modal fade" id="orderStatsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-primary"><?php echo number_format($orderStats['total_orders'] ?? 0); ?></h3>
                                <p class="mb-0">Total Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-success"><?php echo formatCurrency($orderStats['total_revenue'] ?? 0); ?></h3>
                                <p class="mb-0">Total Revenue</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="text-warning"><?php echo number_format($orderStats['pending_orders'] ?? 0); ?></h4>
                                <p class="mb-0 small">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="text-info"><?php echo number_format($orderStats['processing_orders'] ?? 0); ?></h4>
                                <p class="mb-0 small">Processing</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="text-success"><?php echo number_format($orderStats['completed_orders'] ?? 0); ?></h4>
                                <p class="mb-0 small">Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="text-danger"><?php echo number_format($orderStats['cancelled_orders'] ?? 0); ?></h4>
                                <p class="mb-0 small">Cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle status changes
document.addEventListener('DOMContentLoaded', function() {
    // Order status changes
    document.querySelectorAll('.order-status-select').forEach(select => {
        const originalValue = select.value;
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            
            if (confirm(`Are you sure you want to change the order status to "${newStatus}"?`)) {
                updateOrderStatus(orderId, newStatus);
            } else {
                this.value = originalValue;
            }
        });
    });
    
    // Payment status changes
    document.querySelectorAll('.payment-status-select').forEach(select => {
        const originalValue = select.value;
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            
            if (confirm(`Are you sure you want to change the payment status to "${newStatus}"?`)) {
                updatePaymentStatus(orderId, newStatus);
            } else {
                this.value = originalValue;
            }
        });
    });
    
    // Select all checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
});

function updateOrderStatus(orderId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <?php echo csrfToken(); ?>
        <input type="hidden" name="action" value="update_order_status">
        <input type="hidden" name="order_id" value="${orderId}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function updatePaymentStatus(orderId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <?php echo csrfToken(); ?>
        <input type="hidden" name="action" value="update_payment_status">
        <input type="hidden" name="order_id" value="${orderId}">
        <input type="hidden" name="payment_status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function viewOrder(orderId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/orders/get.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                let orderHtml = `
                    <div class="row">
                        <div class="col-md-8">
                            <h6>Order Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Order Number:</strong></td><td>#${order.order_number}</td></tr>
                                <tr><td><strong>Customer:</strong></td><td>${order.first_name} ${order.last_name}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${order.email}</td></tr>
                                <tr><td><strong>Total Amount:</strong></td><td>${order.total_amount}</td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-primary">${order.status}</span></td></tr>
                                <tr><td><strong>Payment Status:</strong></td><td><span class="badge bg-success">${order.payment_status}</span></td></tr>
                                <tr><td><strong>Payment Method:</strong></td><td>${order.payment_method || 'N/A'}</td></tr>
                                <tr><td><strong>Order Date:</strong></td><td>${order.created_at}</td></tr>
                            </table>
                            
                            <h6 class="mt-4">Order Items</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;
                
                if (order.items && order.items.length > 0) {
                    order.items.forEach(item => {
                        const itemName = item.product_name || item.service_name || item.custom_service_name || 'Unknown Item';
                        orderHtml += `
                            <tr>
                                <td>${itemName}</td>
                                <td>${item.quantity}</td>
                                <td>${item.price}</td>
                                <td>${item.total}</td>
                            </tr>
                        `;
                    });
                }
                
                orderHtml += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6>Addresses</h6>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Billing Address</h6>
                                    <p class="card-text small">${order.billing_address || 'Not provided'}</p>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Shipping Address</h6>
                                    <p class="card-text small">${order.shipping_address || 'Same as billing'}</p>
                                </div>
                            </div>
                            
                            ${order.notes ? `
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h6 class="card-title">Order Notes</h6>
                                    <p class="card-text small">${order.notes}</p>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                document.getElementById('orderDetailsContent').innerHTML = orderHtml;
                new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load order details', 'error');
        });
}

function generateInvoice(orderId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <?php echo csrfToken(); ?>
        <input type="hidden" name="action" value="generate_invoice">
        <input type="hidden" name="order_id" value="${orderId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function cancelOrder(orderId) {
    document.getElementById('cancelOrderId').value = orderId;
    new bootstrap.Modal(document.getElementById('cancelOrderModal')).show();
}

function sendOrderEmail(orderId) {
    document.getElementById('emailOrderId').value = orderId;
    new bootstrap.Modal(document.getElementById('sendEmailModal')).show();
}

function exportOrders() {
    const params = new URLSearchParams(window.location.search);
    window.open(`${window.SITE_CONFIG.apiUrl}/orders/export.php?${params.toString()}`, '_blank');
}

function bulkActions() {
    const selectedOrders = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    
    if (selectedOrders.length === 0) {
        Swal.fire('Warning', 'Please select at least one order', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Bulk Actions',
        html: `
            <select class="form-select" id="bulkAction">
                <option value="">Select Action</option>
                <option value="mark_processing">Mark as Processing</option>
                <option value="mark_completed">Mark as Completed</option>
                <option value="generate_invoices">Generate Invoices</option>
                <option value="export_selected">Export Selected</option>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Execute',
        preConfirm: () => {
            const action = document.getElementById('bulkAction').value;
            if (!action) {
                Swal.showValidationMessage('Please select an action');
                return false;
            }
            return action;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            executeBulkAction(result.value, selectedOrders);
        }
    });
}

function executeBulkAction(action, orderIds) {
    // Implementation for bulk actions
    console.log('Executing bulk action:', action, 'on orders:', orderIds);
    Swal.fire('Info', 'Bulk action functionality coming soon', 'info');
}
</script>

<?php include '../includes/footer.php'; ?>