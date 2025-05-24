<?php
/**
 * Add Item to Cart API Endpoint
 * TechSavvyGenLtd Project
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include configuration
require_once '../../config/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    jsonResponse(['success' => false, 'message' => __('login_required', 'Please login to add items to cart')], 401);
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    http_response_code(403);
    jsonResponse(['success' => false, 'message' => __('invalid_token', 'Invalid security token')], 403);
}

// Validate required fields
$required_fields = ['type', 'id', 'quantity'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        jsonResponse(['success' => false, 'message' => sprintf(__('field_required', 'Field %s is required'), $field)], 400);
    }
}

$itemType = $input['type'];
$itemId = (int)$input['id'];
$quantity = (int)$input['quantity'];
$userId = $_SESSION['user_id'];

// Validate item type
$validTypes = ['product', 'service', 'custom_service'];
if (!in_array($itemType, $validTypes)) {
    http_response_code(400);
    jsonResponse(['success' => false, 'message' => __('invalid_item_type', 'Invalid item type')], 400);
}

// Validate quantity
if ($quantity < 1) {
    http_response_code(400);
    jsonResponse(['success' => false, 'message' => __('invalid_quantity', 'Quantity must be at least 1')], 400);
}

try {
    $db = Database::getInstance();
    
    // Validate item exists and get details
    $itemData = null;
    $tableName = '';
    $itemName = '';
    $itemPrice = 0;
    $maxStock = null;
    
    switch ($itemType) {
        case 'product':
            $tableName = TBL_PRODUCTS;
            $itemData = $db->fetch(
                "SELECT id, name_" . CURRENT_LANGUAGE . " as name, price, sale_price, stock, is_digital, status 
                 FROM {$tableName} WHERE id = ? AND status = 'active'",
                [$itemId]
            );
            if ($itemData) {
                $itemName = $itemData['name'];
                $itemPrice = $itemData['sale_price'] ?? $itemData['price'];
                $maxStock = $itemData['is_digital'] ? null : $itemData['stock'];
            }
            break;
            
        case 'service':
            $tableName = TBL_SERVICES;
            $itemData = $db->fetch(
                "SELECT id, name_" . CURRENT_LANGUAGE . " as name, price, status 
                 FROM {$tableName} WHERE id = ? AND status = 'active'",
                [$itemId]
            );
            if ($itemData) {
                $itemName = $itemData['name'];
                $itemPrice = $itemData['price'];
                $maxStock = 1; // Services typically limited to quantity 1
            }
            break;
            
        case 'custom_service':
            $tableName = TBL_CUSTOM_SERVICES;
            $itemData = $db->fetch(
                "SELECT id, name_" . CURRENT_LANGUAGE . " as name, price, status, user_id 
                 FROM {$tableName} WHERE id = ? AND status = 'pending'",
                [$itemId]
            );
            if ($itemData) {
                // Check if custom service belongs to current user
                if ($itemData['user_id'] != $userId) {
                    http_response_code(403);
                    jsonResponse(['success' => false, 'message' => __('access_denied', 'Access denied')], 403);
                }
                $itemName = $itemData['name'];
                $itemPrice = $itemData['price'];
                $maxStock = 1; // Custom services limited to quantity 1
            }
            break;
    }
    
    // Check if item exists and is available
    if (!$itemData) {
        http_response_code(404);
        jsonResponse(['success' => false, 'message' => __('item_not_found', 'Item not found or not available')], 404);
    }
    
    // Check stock availability for physical products
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
    
    // Check if item already exists in cart
    $existingItem = $db->fetch(
        "SELECT id, quantity FROM " . TBL_CART_ITEMS . " 
         WHERE user_id = ? AND item_type = ? AND " . $itemType . "_id = ?",
        [$userId, $itemType, $itemId]
    );
    
    if ($existingItem) {
        // Update existing item quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        
        // Check stock limit again for updated quantity
        if ($maxStock !== null && $newQuantity > $maxStock) {
            $db->rollback();
            http_response_code(400);
            jsonResponse(['success' => false, 'message' => sprintf(__('cart_stock_limit', 'Cannot add more. Maximum %d items allowed'), $maxStock)], 400);
        }
        
        $updated = $db->update(
            TBL_CART_ITEMS,
            ['quantity' => $newQuantity, 'updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$existingItem['id']]
        );
        
        if (!$updated) {
            $db->rollback();
            http_response_code(500);
            jsonResponse(['success' => false, 'message' => __('update_failed', 'Failed to update cart item')], 500);
        }
        
        $cartItemId = $existingItem['id'];
        $action = 'updated';
        
    } else {
        // Add new item to cart
        $cartData = [
            'user_id' => $userId,
            'item_type' => $itemType,
            'quantity' => $quantity,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Set the appropriate foreign key
        $cartData[$itemType . '_id'] = $itemId;
        
        $cartItemId = $db->insert(TBL_CART_ITEMS, $cartData);
        
        if (!$cartItemId) {
            $db->rollback();
            http_response_code(500);
            jsonResponse(['success' => false, 'message' => __('add_failed', 'Failed to add item to cart')], 500);
        }
        
        $action = 'added';
    }
    
    $db->commit();
    
    // Get updated cart count
    $cartCount = $db->fetchColumn(
        "SELECT SUM(quantity) FROM " . TBL_CART_ITEMS . " WHERE user_id = ?",
        [$userId]
    );
    
    // Log activity
    $actionText = $action === 'added' ? 'added to' : 'updated in';
    logActivity(
        'cart_item_' . $action,
        "Item '{$itemName}' {$actionText} cart (Quantity: {$quantity})",
        $userId
    );
    
    // Success response
    $response = [
        'success' => true,
        'message' => $action === 'added' 
            ? __('item_added_to_cart', 'Item added to cart successfully')
            : __('cart_updated', 'Cart updated successfully'),
        'data' => [
            'cart_item_id' => $cartItemId,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'item_name' => $itemName,
            'quantity' => $action === 'added' ? $quantity : $existingItem['quantity'] + $quantity,
            'price' => $itemPrice,
            'action' => $action
        ],
        'cart_count' => (int)$cartCount
    ];
    
    jsonResponse($response);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    
    error_log("Add to cart API error: " . $e->getMessage());
    
    http_response_code(500);
    jsonResponse([
        'success' => false, 
        'message' => __('server_error', 'An error occurred. Please try again.')
    ], 500);
}
?>