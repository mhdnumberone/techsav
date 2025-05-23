<?php
/**
 * Shopping Cart Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Initialize classes
$productClass = new Product();
$serviceClass = new Service();

// Handle AJAX cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjaxRequest()) {
    if (!verifyCsrfToken()) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token'], 400);
    }
    
    $action = $_POST['action'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    try {
        switch ($action) {
            case 'update':
                if ($quantity > 0) {
                    $stmt = $GLOBALS['pdo']->prepare("
                        UPDATE " . TBL_CART_ITEMS . " 
                        SET quantity = ?, updated_at = NOW() 
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$quantity, $itemId, $_SESSION['user_id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        logActivity('cart_item_updated', "Cart item {$itemId} quantity updated to {$quantity}", $_SESSION['user_id']);
                        jsonResponse(['success' => true, 'message' => __('cart_updated', 'Cart updated successfully')]);
                    } else {
                        jsonResponse(['success' => false, 'message' => __('item_not_found', 'Item not found')], 404);
                    }
                } else {
                    jsonResponse(['success' => false, 'message' => __('invalid_quantity', 'Invalid quantity')], 400);
                }
                break;
                
            case 'remove':
                $stmt = $GLOBALS['pdo']->prepare("
                    DELETE FROM " . TBL_CART_ITEMS . " 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$itemId, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() > 0) {
                    logActivity('cart_item_removed', "Cart item {$itemId} removed", $_SESSION['user_id']);
                    jsonResponse(['success' => true, 'message' => __('item_removed', 'Item removed from cart')]);
                } else {
                    jsonResponse(['success' => false, 'message' => __('item_not_found', 'Item not found')], 404);
                }
                break;
                
            case 'clear':
                $stmt = $GLOBALS['pdo']->prepare("DELETE FROM " . TBL_CART_ITEMS . " WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                logActivity('cart_cleared', 'Shopping cart cleared', $_SESSION['user_id']);
                jsonResponse(['success' => true, 'message' => __('cart_cleared', 'Cart cleared successfully')]);
                break;
                
            default:
                jsonResponse(['success' => false, 'message' => __('invalid_action', 'Invalid action')], 400);
        }
    } catch (Exception $e) {
        error_log("Cart action failed: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => __('operation_failed', 'Operation failed. Please try again.')], 500);
    }
}

// Get cart items
try {
    $cartQuery = "
        SELECT ci.*,
               p.name_" . CURRENT_LANGUAGE . " as product_name,
               p.price as product_price,
               p.sale_price as product_sale_price,
               p.featured_image as product_image,
               p.stock as product_stock,
               p.is_digital as is_digital,
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
        ORDER BY ci.created_at DESC
    ";
    
    $cartItems = $GLOBALS['pdo']->prepare($cartQuery);
    $cartItems->execute([$_SESSION['user_id']]);
    $cartItems = $cartItems->fetchAll();
    
    // Calculate totals
    $subtotal = 0;
    $validItems = [];
    
    foreach ($cartItems as $item) {
        $itemPrice = 0;
        $itemName = '';
        $itemImage = '';
        $maxQuantity = null;
        
        if ($item['item_type'] === ITEM_TYPE_PRODUCT) {
            $itemPrice = $item['product_sale_price'] ?? $item['product_price'];
            $itemName = $item['product_name'];
            $itemImage = $item['product_image'];
            $maxQuantity = $item['is_digital'] ? null : $item['product_stock'];
        } elseif ($item['item_type'] === ITEM_TYPE_SERVICE) {
            $itemPrice = $item['service_price'];
            $itemName = $item['service_name'];
            $itemImage = $item['service_image'];
            $maxQuantity = 1; // Services typically have quantity of 1
        } elseif ($item['item_type'] === ITEM_TYPE_CUSTOM_SERVICE) {
            $itemPrice = $item['custom_service_price'];
            $itemName = $item['custom_service_name'];
            $maxQuantity = 1;
        }
        
        if ($itemPrice > 0 && $itemName) {
            $item['calculated_price'] = $itemPrice;
            $item['item_name'] = $itemName;
            $item['item_image'] = $itemImage;
            $item['max_quantity'] = $maxQuantity;
            $item['total_price'] = $itemPrice * $item['quantity'];
            
            $subtotal += $item['total_price'];
            $validItems[] = $item;
        }
    }
    
    $taxRate = (float)getSetting('tax_rate', '0.00') / 100;
    $taxAmount = $subtotal * $taxRate;
    $totalAmount = $subtotal + $taxAmount;
    
} catch (Exception $e) {
    error_log("Cart retrieval failed: " . $e->getMessage());
    $validItems = [];
    $subtotal = $taxAmount = $totalAmount = 0;
}

// Page data
$page_title = __('shopping_cart', 'Shopping Cart');
$body_class = 'cart-page';
?>

<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<section class="page-header bg-gradient-primary text-white py-4">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="page-header-content">
                    <h1 class="page-title h3 mb-2">
                        <i class="fas fa-shopping-cart me-2"></i>
                        <?php echo __('shopping_cart', 'Shopping Cart'); ?>
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="<?php echo SITE_URL; ?>" class="text-white-50">
                                    <?php echo __('home', 'Home'); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                <?php echo __('cart', 'Cart'); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cart Content -->
<section class="cart-content py-5">
    <div class="container">
        <?php if (!empty($validItems)): ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="cart-items">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0"><?php echo __('cart_items', 'Cart Items'); ?></h4>
                        <button class="btn btn-outline-danger btn-sm" id="clearCartBtn">
                            <i class="fas fa-trash me-1"></i>
                            <?php echo __('clear_cart', 'Clear Cart'); ?>
                        </button>
                    </div>
                    
                    <div class="cart-items-list" id="cartItemsList">
                        <?php foreach ($validItems as $item): ?>
                        <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                            <div class="row g-3 align-items-center">
                                <!-- Item Image -->
                                <div class="col-md-2">
                                    <div class="item-image">
                                        <?php if ($item['item_image']): ?>
                                        <img src="<?php echo UPLOADS_URL . '/' . ($item['item_type'] === ITEM_TYPE_PRODUCT ? UPLOAD_PATH_PRODUCTS : UPLOAD_PATH_SERVICES) . '/' . $item['item_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                             class="img-fluid rounded">
                                        <?php else: ?>
                                        <div class="placeholder-image rounded d-flex align-items-center justify-content-center">
                                            <i class="fas fa-<?php echo $item['item_type'] === ITEM_TYPE_PRODUCT ? 'box' : 'cog'; ?> fa-2x text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Item Details -->
                                <div class="col-md-4">
                                    <div class="item-details">
                                        <h6 class="item-name mb-1">
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                        </h6>
                                        <small class="item-type text-muted">
                                            <?php 
                                            switch ($item['item_type']) {
                                                case ITEM_TYPE_PRODUCT:
                                                    echo __('product', 'Product');
                                                    break;
                                                case ITEM_TYPE_SERVICE:
                                                    echo __('service', 'Service');
                                                    break;
                                                case ITEM_TYPE_CUSTOM_SERVICE:
                                                    echo __('custom_service', 'Custom Service');
                                                    break;
                                            }
                                            ?>
                                        </small>
                                        <?php if ($item['item_type'] === ITEM_TYPE_PRODUCT && $item['is_digital']): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-info text-white">
                                                <i class="fas fa-download me-1"></i>
                                                <?php echo __('digital', 'Digital'); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Quantity -->
                                <div class="col-md-2">
                                    <div class="quantity-control">
                                        <label class="form-label small"><?php echo __('quantity', 'Quantity'); ?></label>
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   class="form-control text-center quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>"
                                                   min="1"
                                                   <?php echo $item['max_quantity'] ? 'max="' . $item['max_quantity'] . '"' : ''; ?>
                                                   data-item-id="<?php echo $item['id']; ?>"
                                                   onchange="updateQuantityDirect(this)">
                                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <?php if ($item['max_quantity']): ?>
                                        <small class="text-muted"><?php echo sprintf(__('max_available', 'Max: %d'), $item['max_quantity']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Price -->
                                <div class="col-md-2">
                                    <div class="item-price text-center">
                                        <div class="unit-price small text-muted">
                                            <?php echo formatCurrency($item['calculated_price']); ?> <?php echo __('each', 'each'); ?>
                                        </div>
                                        <div class="total-price fw-bold">
                                            <?php echo formatCurrency($item['total_price']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="col-md-2">
                                    <div class="item-actions text-center">
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="removeItem(<?php echo $item['id']; ?>)"
                                                title="<?php echo __('remove_item', 'Remove Item'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="cart-summary sticky-top">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                <?php echo __('order_summary', 'Order Summary'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="summary-line d-flex justify-content-between">
                                <span><?php echo __('subtotal', 'Subtotal'); ?></span>
                                <span id="subtotalAmount"><?php echo formatCurrency($subtotal); ?></span>
                            </div>
                            
                            <?php if ($taxRate > 0): ?>
                            <div class="summary-line d-flex justify-content-between">
                                <span><?php echo __('tax', 'Tax'); ?> (<?php echo number_format($taxRate * 100, 1); ?>%)</span>
                                <span id="taxAmount"><?php echo formatCurrency($taxAmount); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="summary-line d-flex justify-content-between fw-bold fs-5">
                                <span><?php echo __('total', 'Total'); ?></span>
                                <span id="totalAmount"><?php echo formatCurrency($totalAmount); ?></span>
                            </div>
                            
                            <!-- Coupon Code -->
                            <div class="coupon-section mt-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="<?php echo __('coupon_code', 'Coupon Code'); ?>" id="couponCode">
                                    <button class="btn btn-outline-secondary" type="button" id="applyCouponBtn">
                                        <?php echo __('apply', 'Apply'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <div class="d-grid mt-4">
                                <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>
                                    <?php echo __('proceed_to_checkout', 'Proceed to Checkout'); ?>
                                </a>
                            </div>
                            
                            <!-- Continue Shopping -->
                            <div class="text-center mt-3">
                                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    <?php echo __('continue_shopping', 'Continue Shopping'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Badges -->
                    <div class="security-badges text-center mt-4">
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="security-badge">
                                    <i class="fas fa-shield-alt text-success fa-2x"></i>
                                    <small class="d-block text-muted"><?php echo __('secure', 'Secure'); ?></small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="security-badge">
                                    <i class="fas fa-lock text-primary fa-2x"></i>
                                    <small class="d-block text-muted"><?php echo __('encrypted', 'Encrypted'); ?></small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="security-badge">
                                    <i class="fas fa-check-circle text-success fa-2x"></i>
                                    <small class="d-block text-muted"><?php echo __('verified', 'Verified'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Empty Cart -->
        <div class="empty-cart text-center py-5">
            <div class="empty-cart-icon mb-4">
                <i class="fas fa-shopping-cart fa-4x text-muted"></i>
            </div>
            <h3 class="empty-cart-title mb-3"><?php echo __('cart_empty', 'Your Cart is Empty'); ?></h3>
            <p class="empty-cart-text text-muted mb-4">
                <?php echo __('cart_empty_desc', 'Looks like you haven\'t added any items to your cart yet. Start exploring our products and services!'); ?>
            </p>
            <div class="empty-cart-actions">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-box me-2"></i>
                    <?php echo __('browse_products', 'Browse Products'); ?>
                </a>
                <a href="<?php echo SITE_URL; ?>/services.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-cog me-2"></i>
                    <?php echo __('browse_services', 'Browse Services'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Custom JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update quantity function
    window.updateQuantity = function(itemId, change) {
        const input = document.querySelector(`input[data-item-id="${itemId}"]`);
        const currentQty = parseInt(input.value);
        const newQty = Math.max(1, currentQty + change);
        const maxQty = input.getAttribute('max');
        
        if (maxQty && newQty > parseInt(maxQty)) {
            Swal.fire({
                icon: 'warning',
                title: window.SITE_CONFIG.texts.warning,
                text: '<?php echo __('quantity_exceeds_stock', 'Quantity exceeds available stock'); ?>'
            });
            return;
        }
        
        input.value = newQty;
        updateCartItem(itemId, newQty);
    };
    
    // Direct quantity update
    window.updateQuantityDirect = function(input) {
        const itemId = input.getAttribute('data-item-id');
        const quantity = parseInt(input.value);
        
        if (quantity < 1) {
            input.value = 1;
            return;
        }
        
        updateCartItem(itemId, quantity);
    };
    
    // Update cart item via AJAX
    function updateCartItem(itemId, quantity) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'update',
                item_id: itemId,
                quantity: quantity,
                '<?php echo CSRF_TOKEN_NAME; ?>': window.SITE_CONFIG.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to update totals
                location.reload();
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
        });
    }
    
    // Remove item function
    window.removeItem = function(itemId) {
        Swal.fire({
            title: window.SITE_CONFIG.texts.confirm,
            text: '<?php echo __('remove_item_confirm', 'Are you sure you want to remove this item from your cart?'); ?>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: window.SITE_CONFIG.texts.yes,
            cancelButtonText: window.SITE_CONFIG.texts.cancel
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        action: 'remove',
                        item_id: itemId,
                        '<?php echo CSRF_TOKEN_NAME; ?>': window.SITE_CONFIG.csrfToken
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
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
                });
            }
        });
    };
    
    // Clear cart function
    document.getElementById('clearCartBtn')?.addEventListener('click', function() {
        Swal.fire({
            title: window.SITE_CONFIG.texts.confirm,
            text: '<?php echo __('clear_cart_confirm', 'Are you sure you want to clear your entire cart?'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: window.SITE_CONFIG.texts.yes,
            cancelButtonText: window.SITE_CONFIG.texts.cancel
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        action: 'clear',
                        '<?php echo CSRF_TOKEN_NAME; ?>': window.SITE_CONFIG.csrfToken
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
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
                });
            }
        });
    });
    
    // Apply coupon (placeholder functionality)
    document.getElementById('applyCouponBtn')?.addEventListener('click', function() {
        const couponCode = document.getElementById('couponCode').value.trim();
        
        if (!couponCode) {
            Swal.fire({
                icon: 'warning',
                title: window.SITE_CONFIG.texts.warning,
                text: '<?php echo __('enter_coupon_code', 'Please enter a coupon code'); ?>'
            });
            return;
        }
        
        // Placeholder for coupon functionality
        Swal.fire({
            icon: 'info',
            title: 'Feature Coming Soon',
            text: 'Coupon functionality will be available soon!'
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>