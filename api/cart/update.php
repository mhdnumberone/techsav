<?php
/**
 * Update Cart Item API Endpoint
 * TechSavvyGenLtd Project
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, PATCH');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include configuration
require_once '../../config/config.php';

// Allow POST, PUT, and PATCH methods
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
    http_response_code(405);
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    jsonResponse(['success' => false, 'message' => __('login_required', 'Please login to update cart items')], 401);
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    http_response_code(403);
    jsonResponse(['success' => false, 'message' => __('invalid_token', 'Invalid security token')], 403);
}

$userId = $_SESSION['user_id'];

// Check for bulk update (multiple items)
if (isset($input['items']) && is_array($input['items'])) {
    // Bulk update multiple cart items
    $results = [];
    $errors = [];
    
    try {
        $db = Database::getInstance();
        $db->beginTransaction();
        
        foreach ($input['items'] as $itemUpdate) {
            if (!isset($itemUpdate['cart_item_id']) || !isset($itemUpdate['quantity'])) {
                $errors[] = 'Missing cart_item_id or quantity for bulk update item';
                continue;
            }
            
            $cartItemId = (int)$itemUpdate['cart_item_id'];
            $quantity = (int)$itemUpdate['quantity'];
            
            if ($quantity < 1) {
                $errors[] = "Invalid quantity for cart item {$cartItemId}";
                continue;
            }
            
            // Get cart item details
            $cartItem = $db->fetch(
                "SELECT ci.*, 
                        p.name_" . CURRENT_LANGUAGE . " as product_name, p.stock, p.is_digital,
                        s.name_" . CURRENT_LANGUAGE . " as service_name,
                        cs.name_" . CURRENT_LANGUAGE . " as custom_service_name
                 FROM " . TBL_CART_ITEMS . " ci
                 LEFT JOIN " . TBL_PRODUCTS . " p ON ci.product_id = p.id
                 LEFT JOIN " . TBL_SERVICES . " s ON ci.service_id = s.id
                 LEFT JOIN " . TBL_CUSTOM_SERVICES . " cs ON ci.custom_service_id = cs.id
                 WHERE ci.id = ? AND ci.user_id = ?",
                [$cartItemId, $userId]
            );
            
            if (!$cartItem) {
                $errors[] = "Cart item {$cartItemId} not found";
                continue;
            }
            
            // Check stock limits for products
            if ($cartItem['item_type'] === 'product' && !$cartItem['is_digital']) {
                if ($quantity > $cartItem['stock']) {
                    $errors[] = "Quantity {$quantity} exceeds available stock {$cartItem['stock']} for {$cartItem['product_name']}";
                    continue;
                }
            }
            
            // Check quantity limits for services and custom services
            if (in_array($cartItem['item_type'], ['service', 'custom_service']) && $quantity > 1) {
                $quantity = 1; // Force to 1 for services
            }
            
            // Update the item
            $updated = $db->update(
                TBL_CART_ITEMS,
                ['quantity' => $quantity, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ? AND user_id = ?',
                [$cartItemId, $userId]
            );
            
            if ($updated) {
                $itemName = $cartItem['product_name'] ?? $cartItem['service_name'] ?? $cartItem['custom_service_name'] ?? 'Unknown Item';
                $results[] = [
                    'cart_item_id' => $cartItemId,
                    'item_name' => $itemName,
                    'old_quantity' => $cartItem['quantity'],
                    'new_quantity' => $quantity,
                    'updated' => true
                ];
            } else {
                $errors[] = "Failed to update cart item {$cartItemId}";
            }
        }
        
        if (empty($errors)) {
            $db->commit();
            
            // Get updated cart count
            $cartCount = $db->fetchColumn(
                "SELECT SUM(quantity) FROM " . TBL_CART_ITEMS . " WHERE user_id = ?",
                [$userId]
            );
            
            logActivity('cart_bulk_updated', "Bulk updated " . count($results) . " cart items", $userId);
            
            jsonResponse([
                'success' => true,
                'message' => __('cart_updated', 'Cart updated successfully'),
                'data' => [
                    'updated_items' => $results,
                    'updated_count' => count($results)
                ],
                'cart_count' => (int)$cartCount
            ]);
        } else {
            $db->rollback();
            http_response_code(400);
            jsonResponse([
                'success' => false,
                'message' => __('bulk_update_failed', 'Some items could not be updated'),
                'errors' => $errors,
                'partial_results' => $results
            ], 400);
        }
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollback();
        }
        
        error_log("Bulk update cart API error: " . $e->getMessage());
        
        http_response_code(500);
        jsonResponse([
            'success' => false, 
            'message' => __('server_error', 'An error occurred. Please try again.')
        ], 500);
    }
}

// Single item update
// Validate required fields
$required_fields = ['cart_item_id', 'quantity'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        http_response_code(400);
        jsonResponse(['success' => false, 'message' => sprintf(__('field_required', 'Field %s is required'), $field)], 400);
    }
}

$cartItemId = (int)$input['cart_item_id'];
$quantity = (int)$input['quantity'];

// Validate quantity
if ($quantity < 1) {
    http_response_code(400);
    jsonResponse(['success' => false, 'message' => __('invalid_quantity', 'Quantity must be at least 1')], 400);
}

try {
    $db = Database::getInstance();
    
    // Get cart item details
    $cartItem = $db->fetch(
        "SELECT ci.*, 
                p.name_" . CURRENT_LANGUAGE . " as product_name, p.price, p.sale_price, p.stock, p.is_digital,
                s.name_" . CURRENT_LANGUAGE . " as service_name, s.price as service_price,
                cs.name_" . CURRENT_LANGUAGE . " as custom_service_name, cs.price as custom_service_price
         FROM " . TBL_CART_ITEMS . " ci
         LEFT JOIN " . TBL_PRODUCTS . " p ON ci.product_id = p.id
         LEFT JOIN " . TBL_SERVICES . " s ON ci.service_id = s.id
         LEFT JOIN " . TBL_CUSTOM_SERVICES . " cs ON ci.custom_service_id = cs.id
         WHERE ci.id = ? AND ci.user_id = ?",
        [$cartItemId, $userId]
    );
    
    if (!$cartItem) {
        http_response_code(404);
        jsonResponse(['success' => false, 'message' => __('cart_item_not_found', 'Cart item not found')], 404);
    }
    
    // Get item details based on type
    $itemName = '';
    $itemPrice = 0;
    $maxStock = null;
    
    switch ($cartItem['item_type']) {
        case 'product':
            $itemName = $cartItem['product_name'];
            $itemPrice = $cartItem['sale_price'] ?? $cartItem['price'];
            $maxStock = $cartItem['is_digital'] ? null : $cartItem['stock'];
            break;
        case 'service':
            $itemName = $cartItem['service_name'];
            $itemPrice = $cartItem['service_price'];
            $maxStock = 1; // Services typically limited to quantity 1
            break;
        case 'custom_service':
            $itemName = $cartItem['custom_service_name'];
            $itemPrice = $cartItem['custom_service_price'];
            $maxStock = 1; // Custom services limited to quantity 1
            break;
    }
    
    // Check stock/quantity limits
    if ($maxStock !== null && $quantity > $maxStock) {
        if ($maxStock == 0) {
            http_response_code(400);
            jsonResponse(['success' => false, 'message' => __('out_of_stock', 'Item is out of stock')], 400);
        } else {
            http_response_code(400);
            jsonResponse(['success' => false, 'message' => sprintf(__('insufficient_stock', 'Only %d items available'), $maxStock)], 400);
        }
    }
    
    $db->beginTransaction();
    
    // Update the cart item
    $updated = $db->update(
        TBL_CART_ITEMS,
        ['quantity' => $quantity, 'updated_at' => date('Y-m-d H:i:s')],
        'id = ? AND user_id = ?',
        [$cartItemId, $userId]
    );
    
    if (!$updated) {
        $db->rollback();
        http_response_code(500);
        jsonResponse(['success' => false, 'message' => __('update_failed', 'Failed to update cart item')], 500);
    }
    
    $db->commit();
    
    // Get updated cart count and totals
    $cartCount = $db->fetchColumn(
        "SELECT SUM(quantity) FROM " . TBL_CART_ITEMS . " WHERE user_id = ?",
        [$userId]
    );
    
    // Calculate item total
    $itemTotal = $itemPrice * $quantity;
    
    // Log activity
    logActivity(
        'cart_item_updated',
        "Cart item '{$itemName}' quantity updated from {$cartItem['quantity']} to {$quantity}",
        $userId
    );
    
    // Success response
    $response = [
        'success' => true,
        'message' => __('cart_updated', 'Cart updated successfully'),
        'data' => [
            'cart_item_id' => $cartItemId,
            'item_type' => $cartItem['item_type'],
            'item_name' => $itemName,
            'old_quantity' => $cartItem['quantity'],
            'new_quantity' => $quantity,
            'unit_price' => $itemPrice,
            'item_total' => $itemTotal
        ],
        'cart_count' => (int)$cartCount
    ];
    
    jsonResponse($response);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    
    error_log("Update cart API error: " . $e->getMessage());
    
    http_response_code(500);
    jsonResponse([
        'success' => false, 
        'message' => __('server_error', 'An error occurred. Please try again.')
    ], 500);
}
?>