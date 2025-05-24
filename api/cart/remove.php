<?php
/**
 * Remove Item from Cart API Endpoint
 * TechSavvyGenLtd Project
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include configuration
require_once '../../config/config.php';

// Allow POST and DELETE methods
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    jsonResponse(['success' => false, 'message' => __('login_required', 'Please login to remove items from cart')], 401);
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    http_response_code(403);
    jsonResponse(['success' => false, 'message' => __('invalid_token', 'Invalid security token')], 403);
}

$userId = $_SESSION['user_id'];

// Check if this is a clear all cart request
if (isset($input['clear_cart']) && $input['clear_cart'] === true) {
    try {
        $db = Database::getInstance();
        
        // Get cart items count before clearing
        $cartCount = $db->fetchColumn(
            "SELECT COUNT(*) FROM " . TBL_CART_ITEMS . " WHERE user_id = ?",
            [$userId]
        );
        
        if ($cartCount == 0) {
            jsonResponse([
                'success' => true,
                'message' => __('cart_already_empty', 'Cart is already empty'),
                'cart_count' => 0
            ]);
        }
        
        // Clear all cart items for user
        $deleted = $db->delete(TBL_CART_ITEMS, 'user_id = ?', [$userId]);
        
        if ($deleted) {
            logActivity('cart_cleared', "All cart items cleared ({$cartCount} items)", $userId);
            
            jsonResponse([
                'success' => true,
                'message' => __('cart_cleared', 'Cart cleared successfully'),
                'cart_count' => 0,
                'items_removed' => $deleted
            ]);
        } else {
            http_response_code(500);
            jsonResponse(['success' => false, 'message' => __('clear_failed', 'Failed to clear cart')], 500);
        }
        
    } catch (Exception $e) {
        error_log("Clear cart API error: " . $e->getMessage());
        
        http_response_code(500);
        jsonResponse([
            'success' => false, 
            'message' => __('server_error', 'An error occurred. Please try again.')
        ], 500);
    }
}

// Individual item removal
// Validate required fields
if (!isset($input['cart_item_id']) || empty($input['cart_item_id'])) {
    // Check for alternative identification methods
    if (isset($input['item_type']) && isset($input['item_id'])) {
        $itemType = $input['item_type'];
        $itemId = (int)$input['item_id'];
        
        // Validate item type
        $validTypes = ['product', 'service', 'custom_service'];
        if (!in_array($itemType, $validTypes)) {
            http_response_code(400);
            jsonResponse(['success' => false, 'message' => __('invalid_item_type', 'Invalid item type')], 400);
        }
        
        $findByType = true;
    } else {
        http_response_code(400);
        jsonResponse(['success' => false, 'message' => __('cart_item_id_required', 'Cart item ID is required')], 400);
    }
} else {
    $cartItemId = (int)$input['cart_item_id'];
    $findByType = false;
}

try {
    $db = Database::getInstance();
    
    // Get cart item details before deletion
    if ($findByType) {
        $cartItem = $db->fetch(
            "SELECT ci.*, 
                    p.name_" . CURRENT_LANGUAGE . " as product_name,
                    s.name_" . CURRENT_LANGUAGE . " as service_name,
                    cs.name_" . CURRENT_LANGUAGE . " as custom_service_name
             FROM " . TBL_CART_ITEMS . " ci
             LEFT JOIN " . TBL_PRODUCTS . " p ON ci.product_id = p.id
             LEFT JOIN " . TBL_SERVICES . " s ON ci.service_id = s.id
             LEFT JOIN " . TBL_CUSTOM_SERVICES . " cs ON ci.custom_service_id = cs.id
             WHERE ci.user_id = ? AND ci.item_type = ? AND ci.{$itemType}_id = ?",
            [$userId, $itemType, $itemId]
        );
    } else {
        $cartItem = $db->fetch(
            "SELECT ci.*, 
                    p.name_" . CURRENT_LANGUAGE . " as product_name,
                    s.name_" . CURRENT_LANGUAGE . " as service_name,
                    cs.name_" . CURRENT_LANGUAGE . " as custom_service_name
             FROM " . TBL_CART_ITEMS . " ci
             LEFT JOIN " . TBL_PRODUCTS . " p ON ci.product_id = p.id
             LEFT JOIN " . TBL_SERVICES . " s ON ci.service_id = s.id
             LEFT JOIN " . TBL_CUSTOM_SERVICES . " cs ON ci.custom_service_id = cs.id
             WHERE ci.id = ? AND ci.user_id = ?",
            [$cartItemId, $userId]
        );
    }
    
    if (!$cartItem) {
        http_response_code(404);
        jsonResponse(['success' => false, 'message' => __('cart_item_not_found', 'Cart item not found')], 404);
    }
    
    // Get item name for logging
    $itemName = '';
    switch ($cartItem['item_type']) {
        case 'product':
            $itemName = $cartItem['product_name'];
            break;
        case 'service':
            $itemName = $cartItem['service_name'];
            break;
        case 'custom_service':
            $itemName = $cartItem['custom_service_name'];
            break;
    }
    
    $db->beginTransaction();
    
    // Delete the cart item
    $deleted = $db->delete(TBL_CART_ITEMS, 'id = ? AND user_id = ?', [$cartItem['id'], $userId]);
    
    if (!$deleted) {
        $db->rollback();
        http_response_code(500);
        jsonResponse(['success' => false, 'message' => __('remove_failed', 'Failed to remove item from cart')], 500);
    }
    
    $db->commit();
    
    // Get updated cart count
    $cartCount = $db->fetchColumn(
        "SELECT SUM(quantity) FROM " . TBL_CART_ITEMS . " WHERE user_id = ?",
        [$userId]
    );
    
    // Log activity
    logActivity(
        'cart_item_removed',
        "Item '{$itemName}' removed from cart (Quantity: {$cartItem['quantity']})",
        $userId
    );
    
    // Success response
    $response = [
        'success' => true,
        'message' => __('item_removed', 'Item removed from cart successfully'),
        'data' => [
            'removed_item' => [
                'cart_item_id' => $cartItem['id'],
                'item_type' => $cartItem['item_type'],
                'item_name' => $itemName,
                'quantity' => $cartItem['quantity']
            ]
        ],
        'cart_count' => (int)$cartCount ?: 0
    ];
    
    jsonResponse($response);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    
    error_log("Remove from cart API error: " . $e->getMessage());
    
    http_response_code(500);
    jsonResponse([
        'success' => false, 
        'message' => __('server_error', 'An error occurred. Please try again.')
    ], 500);
}
?>