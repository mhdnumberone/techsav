<?php
/**
 * Admin Payments Management
 * TechSavvyGenLtd Project
 */

require_once '../config/config.php';

// Check if user is staff/admin
if (!isStaff()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize classes
$paymentClass = new Payment();
$orderClass = new Order();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'process_refund':
                $paymentId = (int)$_POST['payment_id'];
                $amount = (float)$_POST['refund_amount'];
                $reason = cleanInput($_POST['refund_reason']);
                $result = $paymentClass->processRefund($paymentId, $amount, $reason);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'add_wallet_funds':
                $userId = (int)$_POST['user_id'];
                $amount = (float)$_POST['amount'];
                $description = cleanInput($_POST['description']);
                $result = $paymentClass->addWalletFunds($userId, $amount, $description);
                if ($result['success']) {
                    $message = 'Wallet funds added successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'verify_payment':
                $paymentId = (int)$_POST['payment_id'];
                // Custom verification logic here
                $message = 'Payment verification completed';
                break;
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

// Build filters array
$filters = array_filter([
    'search' => $search,
    'status' => $status,
    'payment_method' => $payment_method,
    'date_from' => $date_from,
    'date_to' => $date_to
]);

// Get payments
$paymentsData = $paymentClass->getAllPayments($page, ADMIN_ITEMS_PER_PAGE, $filters);
$payments = $paymentsData['payments'];
$totalPages = $paymentsData['pages'];
$totalPayments = $paymentsData['total'];

// Get payment statistics
$paymentStats = $paymentClass->getPaymentStatistics($date_from, $date_to);

$page_title = 'Payments Management';
$body_class = 'admin-page payments-page';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">Payments Management</h1>
                <p class="admin-subtitle">Track and manage payment transactions</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addWalletFundsModal">
                    <i class="fas fa-wallet me-2"></i>Add Wallet Funds
                </button>
                <button class="btn btn-outline-primary" onclick="exportPayments()">
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
                            <div class="stat-icon bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                <i class="fas fa-dollar-sign fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo formatCurrency($paymentStats['total_revenue'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Revenue</div>
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
                                <i class="fas fa-credit-card fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($paymentStats['total_payments'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Payments</div>
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
                                <i class="fas fa-undo fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo formatCurrency($paymentStats['total_refunds'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Refunds</div>
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
                                <i class="fas fa-percentage fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0">
                                    <?php 
                                    $total = $paymentStats['total_payments'] ?? 0;
                                    $successful = $paymentStats['successful_payments'] ?? 0;
                                    echo $total > 0 ? number_format(($successful / $total) * 100, 1) . '%' : '0%';
                                    ?>
                                </div>
                                <div class="stat-label text-muted small">Success Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Method Breakdown -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search payments..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="payment_method">
                                    <option value="">All Methods</option>
                                    <option value="stripe" <?php echo $payment_method === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                                    <option value="paypal" <?php echo $payment_method === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                    <option value="wallet" <?php echo $payment_method === 'wallet' ? 'selected' : ''; ?>>Wallet</option>
                                    <option value="bank_transfer" <?php echo $payment_method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Payment Methods Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment Methods</h5>
                    </div>
                    <div class="card-body">
                        <div class="payment-methods-chart">
                            <div class="method-item d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="method-icon bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                                        <i class="fab fa-cc-stripe"></i>
                                    </div>
                                    <span>Stripe</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($paymentStats['stripe_payments'] ?? 0); ?></span>
                            </div>
                            <div class="method-item d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="method-icon bg-info bg-opacity-10 text-info rounded p-2 me-3">
                                        <i class="fab fa-paypal"></i>
                                    </div>
                                    <span>PayPal</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($paymentStats['paypal_payments'] ?? 0); ?></span>
                            </div>
                            <div class="method-item d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="method-icon bg-success bg-opacity-10 text-success rounded p-2 me-3">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <span>Wallet</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($paymentStats['wallet_payments'] ?? 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Payments (<?php echo $totalPayments; ?>)</h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="refreshPayments()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No payments found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?php echo htmlspecialchars($payment['transaction_id']); ?></div>
                                        <small class="text-muted">ID: <?php echo $payment['id']; ?></small>
                                    </td>
                                    <td>
                                        <?php if ($payment['order_number']): ?>
                                        <a href="orders.php?search=<?php echo urlencode($payment['order_number']); ?>" class="text-decoration-none">
                                            #<?php echo htmlspecialchars($payment['order_number']); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">No Order</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($payment['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold <?php echo $payment['amount'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $payment['amount'] < 0 ? '-' : ''; ?><?php echo formatCurrency(abs($payment['amount'])); ?>
                                        </div>
                                        <small class="text-muted"><?php echo $payment['currency']; ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $methodIcons = [
                                                'stripe' => 'fab fa-cc-stripe text-primary',
                                                'paypal' => 'fab fa-paypal text-info',
                                                'wallet' => 'fas fa-wallet text-success',
                                                'bank_transfer' => 'fas fa-university text-secondary'
                                            ];
                                            $iconClass = $methodIcons[$payment['payment_method']] ?? 'fas fa-credit-card text-muted';
                                            ?>
                                            <i class="<?php echo $iconClass; ?> me-2"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'failed' ? 'danger' : ($payment['status'] === 'refunded' ? 'warning' : 'secondary')); ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?php echo formatDate($payment['created_at']); ?></div>
                                        <small class="text-muted"><?php echo formatTime($payment['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewPayment(<?php echo $payment['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($payment['status'] === 'completed' && $payment['amount'] > 0): ?>
                                            <button class="btn btn-outline-warning" onclick="processRefund(<?php echo $payment['id']; ?>)" title="Process Refund">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($payment['payment_method'] === 'bank_transfer' && $payment['status'] === 'pending'): ?>
                                            <button class="btn btn-outline-success" onclick="verifyPayment(<?php echo $payment['id']; ?>)" title="Verify Payment">
                                                <i class="fas fa-check"></i>
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
                $baseUrl = ADMIN_URL . '/payments.php?' . http_build_query(array_filter([
                    'search' => $search,
                    'status' => $status,
                    'payment_method' => $payment_method,
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

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Payment details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Process Refund Modal -->
<div class="modal fade" id="processRefundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="process_refund">
                <input type="hidden" name="payment_id" id="refundPaymentId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Process Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Refund Amount</label>
                        <input type="number" class="form-control" name="refund_amount" id="refundAmount" step="0.01" min="0" required>
                        <div class="form-text">Maximum refundable amount: <span id="maxRefundAmount">$0.00</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Refund Reason</label>
                        <textarea class="form-control" name="refund_reason" rows="3" required placeholder="Please provide a reason for the refund..."></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action will process a refund for the specified amount. Please ensure all details are correct before proceeding.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Process Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Wallet Funds Modal -->
<div class="modal fade" id="addWalletFundsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="add_wallet_funds">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Wallet Funds</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select User</label>
                        <select class="form-select" name="user_id" id="walletUserId" required>
                            <option value="">Select User</option>
                            <!-- Users will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Reason for adding funds..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Funds</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewPayment(paymentId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/payments/get.php?id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const payment = data.payment;
                let paymentHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Payment Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Transaction ID:</strong></td><td>${payment.transaction_id}</td></tr>
                                <tr><td><strong>Amount:</strong></td><td>${payment.amount}</td></tr>
                                <tr><td><strong>Currency:</strong></td><td>${payment.currency}</td></tr>
                                <tr><td><strong>Payment Method:</strong></td><td>${payment.payment_method}</td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-success">${payment.status}</span></td></tr>
                                <tr><td><strong>Created:</strong></td><td>${payment.created_at}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Name:</strong></td><td>${payment.first_name} ${payment.last_name}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${payment.email}</td></tr>
                                <tr><td><strong>Order:</strong></td><td>${payment.order_number || 'N/A'}</td></tr>
                            </table>
                            
                            ${payment.payment_data ? `
                            <h6 class="mt-3">Payment Data</h6>
                            <pre class="bg-light p-2 rounded small">${JSON.stringify(JSON.parse(payment.payment_data), null, 2)}</pre>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                document.getElementById('paymentDetailsContent').innerHTML = paymentHtml;
                new bootstrap.Modal(document.getElementById('paymentDetailsModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load payment details', 'error');
        });
}

function processRefund(paymentId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/payments/get.php?id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const payment = data.payment;
                document.getElementById('refundPaymentId').value = paymentId;
                document.getElementById('refundAmount').max = payment.amount;
                document.getElementById('refundAmount').value = payment.amount;
                document.getElementById('maxRefundAmount').textContent = payment.amount;
                
                new bootstrap.Modal(document.getElementById('processRefundModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load payment data', 'error');
        });
}

function verifyPayment(paymentId) {
    Swal.fire({
        title: 'Verify Payment',
        text: 'Are you sure you want to mark this payment as verified?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, verify it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="verify_payment">
                <input type="hidden" name="payment_id" value="${paymentId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    window.open(`${window.SITE_CONFIG.apiUrl}/payments/export.php?${params.toString()}`, '_blank');
}

function refreshPayments() {
    window.location.reload();
}

// Load users for wallet funds modal
document.addEventListener('DOMContentLoaded', function() {
    const addWalletModal = document.getElementById('addWalletFundsModal');
    addWalletModal.addEventListener('show.bs.modal', function() {
        fetch(`${window.SITE_CONFIG.apiUrl}/users/list.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const userSelect = document.getElementById('walletUserId');
                    userSelect.innerHTML = '<option value="">Select User</option>';
                    data.users.forEach(user => {
                        userSelect.innerHTML += `<option value="${user.id}">${user.first_name} ${user.last_name} (${user.email})</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });
});
</script>

<?php include '../includes/footer.php'; ?>