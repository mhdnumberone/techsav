<?php
/**
 * Checkout Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode(SITE_URL . '/checkout.php'));
}

// Initialize classes
$orderClass = new Order();
$paymentClass = new Payment();
$userClass = new User();

// Get current user data
$currentUser = $userClass->getById($_SESSION['user_id']);
if (!$currentUser) {
    redirect(SITE_URL . '/login.php');
}

// Get cart items
$cartItems = getCartItems($_SESSION['user_id']);
if (empty($cartItems)) {
    redirect(SITE_URL . '/cart.php');
}

// Calculate cart totals
$subtotal = calculateCartSubtotal($cartItems);
$taxRate = (float)getSetting('tax_rate', '0.00') / 100;
$taxAmount = $subtotal * $taxRate;
$total = $subtotal + $taxAmount;

// Handle form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_checkout'])) {
    // Verify CSRF token
    if (!verifyCsrfToken()) {
        $error_message = __('invalid_token', 'Invalid security token. Please try again.');
    } else {
        // Validate required fields
        $required_fields = ['payment_method', 'billing_address'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = sprintf(__('field_required', 'Field %s is required'), __($field, $field));
            }
        }
        
        // Validate payment method
        $validPaymentMethods = [PAYMENT_METHOD_STRIPE, PAYMENT_METHOD_PAYPAL, PAYMENT_METHOD_WALLET, PAYMENT_METHOD_BANK];
        if (!in_array($_POST['payment_method'], $validPaymentMethods)) {
            $errors[] = __('invalid_payment_method', 'Invalid payment method selected');
        }
        
        // Validate wallet balance if wallet payment selected
        if ($_POST['payment_method'] === PAYMENT_METHOD_WALLET) {
            if ($currentUser['wallet_balance'] < $total) {
                $errors[] = __('insufficient_wallet_balance', 'Insufficient wallet balance. Please add funds or choose another payment method.');
            }
        }
        
        if (empty($errors)) {
            try {
                // Prepare order data
                $orderData = [
                    'payment_method' => cleanInput($_POST['payment_method']),
                    'billing_address' => cleanInput($_POST['billing_address']),
                    'shipping_address' => cleanInput($_POST['shipping_address'] ?? $_POST['billing_address']),
                    'notes' => cleanInput($_POST['notes'] ?? '')
                ];
                
                // Create order
                $orderResult = $orderClass->createFromCart($_SESSION['user_id'], $orderData);
                
                if ($orderResult['success']) {
                    $orderId = $orderResult['order_id'];
                    $orderNumber = $orderResult['order_number'];
                    
                    // Process payment
                    $paymentData = [
                        'payment_method' => $_POST['payment_method'],
                        'billing_address' => $_POST['billing_address']
                    ];
                    
                    // Add payment method specific data
                    if ($_POST['payment_method'] === PAYMENT_METHOD_STRIPE) {
                        $paymentData['stripe_token'] = $_POST['stripe_token'] ?? '';
                    } elseif ($_POST['payment_method'] === PAYMENT_METHOD_PAYPAL) {
                        $paymentData['paypal_payment_id'] = $_POST['paypal_payment_id'] ?? '';
                        $paymentData['paypal_payer_id'] = $_POST['paypal_payer_id'] ?? '';
                    } elseif ($_POST['payment_method'] === PAYMENT_METHOD_BANK) {
                        $paymentData['bank_reference'] = $_POST['bank_reference'] ?? '';
                        $paymentData['transfer_date'] = $_POST['transfer_date'] ?? '';
                    }
                    
                    $paymentResult = $paymentClass->processPayment($orderId, $paymentData);
                    
                    if ($paymentResult['success']) {
                        // Create invoice
                        $invoiceClass = new Invoice();
                        $invoiceClass->createFromOrder($orderId);
                        
                        // Send notification
                        createNotification(
                            $_SESSION['user_id'],
                            'تم إنشاء الطلب بنجاح',
                            'Order Created Successfully',
                            "رقم الطلب: {$orderNumber}",
                            "Order Number: {$orderNumber}",
                            NOTIFICATION_ORDER,
                            SITE_URL . "/profile.php#orders"
                        );
                        
                        // Redirect to success page
                        redirect(SITE_URL . "/checkout-success.php?order={$orderNumber}");
                    } else {
                        $error_message = $paymentResult['message'];
                    }
                } else {
                    $error_message = $orderResult['message'];
                }
            } catch (Exception $e) {
                error_log("Checkout processing failed: " . $e->getMessage());
                $error_message = __('checkout_error', 'An error occurred during checkout. Please try again.');
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Page data
$page_title = __('checkout', 'Checkout');
$body_class = 'checkout-page';

// Helper function to get cart items
function getCartItems($userId) {
    global $pdo;
    try {
        $sql = "SELECT ci.*,
                       p.name_" . CURRENT_LANGUAGE . " as product_name,
                       p.price as product_price,
                       p.sale_price as product_sale_price,
                       p.featured_image as product_image,
                       p.is_digital as product_is_digital,
                       s.name_" . CURRENT_LANGUAGE . " as service_name,
                       s.price as service_price,
                       s.featured_image as service_image,
                       cs.name_" . CURRENT_LANGUAGE . " as custom_service_name,
                       cs.price as custom_service_price
                FROM " . TBL_CART_ITEMS . " ci
                LEFT JOIN " . TBL_PRODUCTS . " p ON ci.product_id = p.id
                LEFT JOIN " . TBL_SERVICES . " s ON ci.service_id = s.id
                LEFT JOIN " . TBL_CUSTOM_SERVICES . " cs ON ci.custom_service_id = cs.id
                WHERE ci.user_id = ?
                ORDER BY ci.created_at ASC";
        
        $items = $pdo->prepare($sql);
        $items->execute([$userId]);
        $cartItems = $items->fetchAll();
        
        // Calculate prices and totals
        foreach ($cartItems as &$item) {
            if ($item['item_type'] === ITEM_TYPE_PRODUCT) {
                $item['name'] = $item['product_name'];
                $item['price'] = $item['product_sale_price'] ?? $item['product_price'];
                $item['image'] = $item['product_image'];
                $item['is_digital'] = $item['product_is_digital'];
            } elseif ($item['item_type'] === ITEM_TYPE_SERVICE) {
                $item['name'] = $item['service_name'];
                $item['price'] = $item['service_price'];
                $item['image'] = $item['service_image'];
                $item['is_digital'] = true;
            } elseif ($item['item_type'] === ITEM_TYPE_CUSTOM_SERVICE) {
                $item['name'] = $item['custom_service_name'];
                $item['price'] = $item['custom_service_price'];
                $item['image'] = null;
                $item['is_digital'] = true;
            }
            $item['total'] = $item['price'] * $item['quantity'];
        }
        
        return $cartItems;
    } catch (Exception $e) {
        return [];
    }
}

// Helper function to calculate cart subtotal
function calculateCartSubtotal($cartItems) {
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['total'];
    }
    return $subtotal;
}
?>

<?php include 'includes/header.php'; ?>

<!-- Checkout Content -->
<section class="checkout-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="checkout-header text-center mb-5">
                    <h1 class="page-title"><?php echo __('checkout', 'Checkout'); ?></h1>
                    <p class="page-subtitle text-muted">
                        <?php echo __('checkout_subtitle', 'Review your order and complete your purchase'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Order Summary Sidebar -->
            <div class="col-lg-4 order-lg-2 mb-4">
                <div class="order-summary">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo __('order_summary', 'Order Summary'); ?></h5>
                        </div>
                        <div class="card-body">
                            <!-- Cart Items -->
                            <div class="cart-items">
                                <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item d-flex align-items-center mb-3">
                                    <div class="item-image me-3">
                                        <?php if ($item['image']): ?>
                                        <img src="<?php echo UPLOADS_URL . '/' . ($item['item_type'] === ITEM_TYPE_PRODUCT ? UPLOAD_PATH_PRODUCTS : UPLOAD_PATH_SERVICES) . '/' . $item['image']; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="img-fluid rounded" width="60" height="60">
                                        <?php else: ?>
                                        <div class="placeholder-image d-flex align-items-center justify-content-center rounded bg-light" style="width: 60px; height: 60px;">
                                            <i class="fas fa-<?php echo $item['item_type'] === ITEM_TYPE_PRODUCT ? 'box' : 'cog'; ?> text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details flex-grow-1">
                                        <h6 class="item-name mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <div class="item-meta d-flex justify-content-between">
                                            <span class="quantity text-muted"><?php echo __('qty', 'Qty'); ?>: <?php echo $item['quantity']; ?></span>
                                            <span class="price fw-bold"><?php echo formatCurrency($item['total']); ?></span>
                                        </div>
                                        <?php if ($item['is_digital']): ?>
                                        <small class="text-info">
                                            <i class="fas fa-download me-1"></i><?php echo __('digital_item', 'Digital Item'); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Order Totals -->
                            <hr>
                            <div class="order-totals">
                                <div class="total-row d-flex justify-content-between">
                                    <span><?php echo __('subtotal', 'Subtotal'); ?>:</span>
                                    <span><?php echo formatCurrency($subtotal); ?></span>
                                </div>
                                <?php if ($taxAmount > 0): ?>
                                <div class="total-row d-flex justify-content-between">
                                    <span><?php echo __('tax', 'Tax'); ?> (<?php echo ($taxRate * 100); ?>%):</span>
                                    <span><?php echo formatCurrency($taxAmount); ?></span>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="total-row d-flex justify-content-between fw-bold h5">
                                    <span><?php echo __('total', 'Total'); ?>:</span>
                                    <span class="text-primary"><?php echo formatCurrency($total); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Badge -->
                    <div class="security-badge text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-lock me-1"></i>
                            <?php echo __('secure_checkout', 'Secure checkout powered by SSL encryption'); ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Checkout Form -->
            <div class="col-lg-8 order-lg-1">
                <!-- Error/Success Messages -->
                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="checkout-form" id="checkoutForm">
                    <?php echo csrfToken(); ?>
                    
                    <!-- Billing Information -->
                    <div class="checkout-section">
                        <h4 class="section-title mb-3">
                            <i class="fas fa-credit-card me-2"></i>
                            <?php echo __('billing_information', 'Billing Information'); ?>
                        </h4>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label"><?php echo __('first_name', 'First Name'); ?></label>
                                <input type="text" class="form-control" id="first_name" value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label"><?php echo __('last_name', 'Last Name'); ?></label>
                                <input type="text" class="form-control" id="last_name" value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" readonly>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label"><?php echo __('email_address', 'Email Address'); ?></label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                            </div>
                            <div class="col-12">
                                <label for="billing_address" class="form-label"><?php echo __('billing_address', 'Billing Address'); ?> <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="billing_address" name="billing_address" rows="3" required><?php echo htmlspecialchars($_POST['billing_address'] ?? $currentUser['address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Information -->
                    <div class="checkout-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="section-title mb-0">
                                <i class="fas fa-shipping-fast me-2"></i>
                                <?php echo __('shipping_information', 'Shipping Information'); ?>
                            </h4>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="same_as_billing" checked>
                                <label class="form-check-label" for="same_as_billing">
                                    <?php echo __('same_as_billing', 'Same as billing address'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div id="shipping_address_section" style="display: none;">
                            <div class="col-12">
                                <label for="shipping_address" class="form-label"><?php echo __('shipping_address', 'Shipping Address'); ?></label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"><?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <h4 class="section-title mb-3">
                            <i class="fas fa-credit-card me-2"></i>
                            <?php echo __('payment_method', 'Payment Method'); ?>
                        </h4>
                        
                        <div class="payment-methods">
                            <!-- Stripe Payment -->
                            <div class="payment-method">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="stripe" value="stripe" checked>
                                    <label class="form-check-label" for="stripe">
                                        <div class="method-info">
                                            <div class="method-title">
                                                <i class="fab fa-cc-stripe me-2"></i>
                                                <?php echo __('credit_debit_card', 'Credit/Debit Card'); ?>
                                            </div>
                                            <small class="text-muted"><?php echo __('stripe_desc', 'Secure payment with Stripe'); ?></small>
                                        </div>
                                    </label>
                                </div>
                                <div class="payment-details" id="stripe_details">
                                    <div class="card-element p-3 border rounded bg-light">
                                        <div id="card-element">
                                            <!-- Stripe Elements will create form elements here -->
                                        </div>
                                        <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- PayPal Payment -->
                            <div class="payment-method">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                    <label class="form-check-label" for="paypal">
                                        <div class="method-info">
                                            <div class="method-title">
                                                <i class="fab fa-paypal me-2"></i>
                                                <?php echo __('paypal', 'PayPal'); ?>
                                            </div>
                                            <small class="text-muted"><?php echo __('paypal_desc', 'Pay securely with your PayPal account'); ?></small>
                                        </div>
                                    </label>
                                </div>
                                <div class="payment-details" id="paypal_details" style="display: none;">
                                    <div class="p-3 border rounded bg-light">
                                        <div id="paypal-button-container">
                                            <!-- PayPal button will be rendered here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Wallet Payment -->
                            <?php if ($currentUser['wallet_balance'] >= $total): ?>
                            <div class="payment-method">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="wallet" value="wallet">
                                    <label class="form-check-label" for="wallet">
                                        <div class="method-info">
                                            <div class="method-title">
                                                <i class="fas fa-wallet me-2"></i>
                                                <?php echo __('wallet_payment', 'Wallet Payment'); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo __('wallet_balance', 'Current balance'); ?>: <?php echo formatCurrency($currentUser['wallet_balance']); ?>
                                            </small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Bank Transfer -->
                            <div class="payment-method">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="bank" value="bank_transfer">
                                    <label class="form-check-label" for="bank">
                                        <div class="method-info">
                                            <div class="method-title">
                                                <i class="fas fa-university me-2"></i>
                                                <?php echo __('bank_transfer', 'Bank Transfer'); ?>
                                            </div>
                                            <small class="text-muted"><?php echo __('bank_desc', 'Manual verification required'); ?></small>
                                        </div>
                                    </label>
                                </div>
                                <div class="payment-details" id="bank_details" style="display: none;">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="bank_reference" class="form-label"><?php echo __('transfer_reference', 'Transfer Reference'); ?></label>
                                                <input type="text" class="form-control" id="bank_reference" name="bank_reference">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="transfer_date" class="form-label"><?php echo __('transfer_date', 'Transfer Date'); ?></label>
                                                <input type="date" class="form-control" id="transfer_date" name="transfer_date" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="bank-info mt-3">
                                            <h6><?php echo __('bank_details', 'Bank Details'); ?>:</h6>
                                            <small class="text-muted">
                                                <?php echo __('bank_account_info', 'Bank: Sample Bank<br>Account: 1234567890<br>IBAN: XX00 0000 0000 0000 0000'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Notes -->
                    <div class="checkout-section">
                        <h4 class="section-title mb-3">
                            <i class="fas fa-sticky-note me-2"></i>
                            <?php echo __('order_notes', 'Order Notes'); ?>
                            <small class="text-muted">(<?php echo __('optional', 'Optional'); ?>)</small>
                        </h4>
                        
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="<?php echo __('order_notes_placeholder', 'Any special instructions for your order...'); ?>"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div class="checkout-section">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="accept_terms" name="accept_terms" required>
                            <label class="form-check-label" for="accept_terms">
                                <?php echo __('accept_terms_checkout', 'I agree to the'); ?>
                                <a href="<?php echo SITE_URL; ?>/terms-of-service.php" target="_blank" class="text-decoration-none">
                                    <?php echo __('terms_of_service', 'Terms of Service'); ?>
                                </a>
                                <?php echo __('and', 'and'); ?>
                                <a href="<?php echo SITE_URL; ?>/privacy-policy.php" target="_blank" class="text-decoration-none">
                                    <?php echo __('privacy_policy', 'Privacy Policy'); ?>
                                </a>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="checkout-submit">
                        <button type="submit" name="submit_checkout" class="btn btn-primary btn-lg w-100" id="submitButton">
                            <i class="fas fa-lock me-2"></i>
                            <?php echo __('complete_order', 'Complete Order'); ?> - <?php echo formatCurrency($total); ?>
                        </button>
                        
                        <div class="checkout-footer text-center mt-3">
                            <small class="text-muted">
                                <?php echo __('checkout_security_note', 'Your payment information is encrypted and secure'); ?>
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Stripe Elements -->
<?php if (getSetting('stripe_public_key')): ?>
<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?php echo getSetting('stripe_public_key'); ?>');
const elements = stripe.elements();

// Create card element
const cardElement = elements.create('card', {
    style: {
        base: {
            fontSize: '16px',
            color: '#424770',
            '::placeholder': {
                color: '#aab7c4',
            },
        },
    },
});

cardElement.mount('#card-element');

// Handle real-time validation errors from the card Element
cardElement.addEventListener('change', ({error}) => {
    const displayError = document.getElementById('card-errors');
    if (error) {
        displayError.textContent = error.message;
    } else {
        displayError.textContent = '';
    }
});
</script>
<?php endif; ?>

<!-- PayPal SDK -->
<?php if (getSetting('paypal_client_id')): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo getSetting('paypal_client_id'); ?>&currency=<?php echo DEFAULT_CURRENCY; ?>"></script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method switching
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const paymentDetails = document.querySelectorAll('.payment-details');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all payment details
            paymentDetails.forEach(detail => {
                detail.style.display = 'none';
            });
            
            // Show selected payment details
            const selectedDetails = document.getElementById(this.value + '_details');
            if (selectedDetails) {
                selectedDetails.style.display = 'block';
            }
            
            // Initialize PayPal if selected
            if (this.value === 'paypal') {
                initializePayPal();
            }
        });
    });
    
    // Shipping address toggle
    const sameAsBilling = document.getElementById('same_as_billing');
    const shippingSection = document.getElementById('shipping_address_section');
    
    sameAsBilling.addEventListener('change', function() {
        if (this.checked) {
            shippingSection.style.display = 'none';
        } else {
            shippingSection.style.display = 'block';
        }
    });
    
    // Form submission
    const checkoutForm = document.getElementById('checkoutForm');
    checkoutForm.addEventListener('submit', function(e) {
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (selectedPayment === 'stripe') {
            e.preventDefault();
            handleStripePayment();
        } else if (selectedPayment === 'paypal') {
            e.preventDefault();
            // PayPal will handle submission
        }
        // Other payment methods proceed normally
    });
    
    // Stripe payment handling
    function handleStripePayment() {
        const submitButton = document.getElementById('submitButton');
        const originalText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + window.SITE_CONFIG.texts.loading;
        
        stripe.createToken(cardElement).then(function(result) {
            if (result.error) {
                // Show error to customer
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
                
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            } else {
                // Submit the form with the token
                const hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripe_token');
                hiddenInput.setAttribute('value', result.token.id);
                checkoutForm.appendChild(hiddenInput);
                
                checkoutForm.submit();
            }
        });
    }
    
    // PayPal initialization
    function initializePayPal() {
        <?php if (getSetting('paypal_client_id')): ?>
        document.getElementById('paypal-button-container').innerHTML = '';
        
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '<?php echo number_format($total, 2, '.', ''); ?>'
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    // Add PayPal data to form
                    const paymentIdInput = document.createElement('input');
                    paymentIdInput.setAttribute('type', 'hidden');
                    paymentIdInput.setAttribute('name', 'paypal_payment_id');
                    paymentIdInput.setAttribute('value', data.orderID);
                    
                    const payerIdInput = document.createElement('input');
                    payerIdInput.setAttribute('type', 'hidden');
                    payerIdInput.setAttribute('name', 'paypal_payer_id');
                    payerIdInput.setAttribute('value', details.payer.payer_id);
                    
                    checkoutForm.appendChild(paymentIdInput);
                    checkoutForm.appendChild(payerIdInput);
                    checkoutForm.submit();
                });
            },
            onError: function(err) {
                console.error('PayPal error:', err);
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'PayPal payment failed. Please try again.'
                });
            }
        }).render('#paypal-button-container');
        <?php endif; ?>
    }
    
    // Form validation
    checkoutForm.addEventListener('submit', function(e) {
        const termsAccepted = document.getElementById('accept_terms').checked;
        
        if (!termsAccepted) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: window.SITE_CONFIG.texts.error,
                text: 'You must accept the terms and conditions to continue.'
            });
            return;
        }
        
        // Show loading state for non-Stripe payments
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked').value;
        if (selectedPayment !== 'stripe' && selectedPayment !== 'paypal') {
            const submitButton = document.getElementById('submitButton');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + window.SITE_CONFIG.texts.loading;
            
            // Re-enable after 10 seconds (fallback)
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }, 10000);
        }
    });
    
    // Initialize default payment method
    const defaultPayment = document.querySelector('input[name="payment_method"]:checked');
    if (defaultPayment) {
        defaultPayment.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include 'includes/footer.php'; ?>