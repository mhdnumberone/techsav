<?php
/**
 * Get Cart Contents API Endpoint
 * TechSavvyGenLtd Project
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include configuration
require_once '../../config/config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    jsonResponse(['success' => false, 'message' => __('login_required', 'Please login to view cart')], 401);
}

$userId = $_SESSION['user_id'];

// Get query parameters
$includeDetails = isset($_GET['details']) && $_GET['details'] === 'true';
$calculateTotals = isset($_GET['totals']) && $_GET['totals'] === 'true';
$format = $_GET['format'] ?? 'full'; // 'full', 'summary', 'count'

try {
    $db = Database::getInstance();
    
    // Handle different response formats
    switch ($format) {
        case 'count':
            // Return only cart item count
            $cartCount = $db->fetchColumn(
                "SELECT SUM(quantity) FROM " . TBL_CART_ITEMS . " WHERE user_id = ?",
                [$userId]
            );
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'cart_count' => (int)$cartCount ?: 0
                ]
            ]);
            break;
            
        case 'summary':
            // Return basic cart summary
            $summary = $db->fetch(
                "SELECT 
                    COUNT(*) as item_types,
                    SUM(quantity) as total_items
                 FROM " . TBL_CART_ITEMS . " WHERE user_id = ?",
                [$userId]
            );
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'item_types' => (int)$summary['item_types'] ?: 0,
                    'total_items' => (int)$summary['total_items'] ?: 0,
                    'is_empty' => $summary['item_types'] == 0
                ]
            ]);
            break;
            
        default:
            // Return full cart details
            $cartQuery = "
                SELECT ci.*,
                       p.name_" . CURRENT_LANGUAGE . " as product_name,
                       p.price as product_price,
                       p.sale_price as product_sale_price,
                       p.featured_image as product_image,
                       p.stock as product_stock,
                       p.is_digital as product_is_digital,
                       p.slug as product_slug,
                       s.name_" . CURRENT_LANGUAGE . " as service_name,
                       s.price as service_price,
                       s.featured_image as service_image,
                       s.slug as service_slug,
                       cs.name_" . CURRENT_LANGUAGE . " as custom_service_name,
                       cs.price as custom_service_price,
                       cs.unique_link as custom_service_link,
                       cat.name_" . CURRENT_LANGUAGE . " as category_name
                FROM " . TBL_CART_ITEMS . " ci
                LEFT JOIN " . TBL_PRODUCTS . " p ON ci.product_id = p.id
                LEFT JOIN " . TBL_SERVICES . " s ON ci.service_id = s.id
                LEFT JOIN " . TBL_CUSTOM_SERVICES . " cs ON ci.custom_service_id = cs.id
                LEFT JOIN " . TBL_CATEGORIES . " cat ON (p.category_id = cat.id OR s.category_id = cat.id)
                WHERE ci.user_id = ?
                ORDER BY ci.created_at DESC
            ";
            
            $cartItems = $db->fetchAll($cartQuery, [$userId]);
            
            // Process cart items
            $processedItems = [];
            $subtotal = 0;
            $totalQuantity = 0;
            
            foreach ($cartItems as $item) {
                $processedItem = [
                    'cart_item_id' => $item['id'],
                    'item_type' => $item['item_type'],
                    'quantity' => (int)$item['quantity'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at']
                ];
                
                // Set item-specific data based on type
                switch ($item['item_type']) {
                    case 'product':
                        $unitPrice = $item['product_sale_price'] ?? $item['product_price'];
                        $processedItem['id'] = $item['product_id'];
                        $processedItem['name'] = $item['product_name'];
                        $processedItem['price'] = (float)$unitPrice;
                        $processedItem['image'] = $item['product_image'];
                        $processedItem['slug'] = $item['product_slug'];
                        $processedItem['stock'] = $item['product_is_digital'] ? null : (int)$item['product_stock'];
                        $processedItem['is_digital'] = (bool)$item['product_is_digital'];
                        $processedItem['category'] = $item['category_name'];
                        
                        if ($includeDetails) {
                            $processedItem['details'] = [
                                'original_price' => (float)$item['product_price'],
                                'sale_price' => $item['product_sale_price'] ? (float)$item['product_sale_price'] : null,
                                'has_discount' => !empty($item['product_sale_price']),
                                'url' => SITE_URL . '/product-details.php?slug=' . $item['product_slug']
                            ];
                        }
                        break;
                        
                    case 'service':
                        $unitPrice = $item['service_price'];
                        $processedItem['id'] = $item['service_id'];
                        $processedItem['name'] = $item['service_name'];
                        $processedItem['price'] = (float)$unitPrice;
                        $processedItem['image'] = $item['service_image'];
                        $processedItem['slug'] = $item['service_slug'];
                        $processedItem['stock'] = null;
                        $processedItem['is_digital'] = true;
                        $processedItem['category'] = $item['category_name'];
                        
                        if ($includeDetails) {
                            $processedItem['details'] = [
                                'url' => SITE_URL . '/service-details.php?slug=' . $item['service_slug']
                            ];
                        }
                        break;
                        
                    case 'custom_service':
                        $unitPrice = $item['custom_service_price'];
                        $processedItem['id'] = $item['custom_service_id'];
                        $processedItem['name'] = $item['custom_service_name'];
                        $processedItem['price'] = (float)$unitPrice;
                        $processedItem['image'] = null;
                        $processedItem['slug'] = null;
                        $processedItem['stock'] = null;
                        $processedItem['is_digital'] = true;
                        $processedItem['category'] = null;
                        
                        if ($includeDetails) {
                            $processedItem['details'] = [
                                'unique_link' => $item['custom_service_link'],
                                'url' => SITE_URL . '/custom-service.php?link=' . $item['custom_service_link']
                            ];
                        }
                        break;
                        
                    default:
                        continue 2; // Skip invalid items
                }
                
                // Calculate totals
                $itemTotal = $unitPrice * $item['quantity'];
                $processedItem['total'] = $itemTotal;
                
                // Add image URL if image exists
                if ($processedItem['image']) {
                    $imagePath = $item['item_type'] === 'product' ? UPLOAD_PATH_PRODUCTS : UPLOAD_PATH_SERVICES;
                    $processedItem['image_url'] = UPLOADS_URL . '/' . $imagePath . '/' . $processedItem['image'];
                } else {
                    $processedItem['image_url'] = null;
                }
                
                $subtotal += $itemTotal;
                $totalQuantity += $item['quantity'];
                $processedItems[] = $processedItem;
            }
            
            // Calculate totals if requested
            $totals = null;
            if ($calculateTotals) {
                $taxRate = (float)getSetting('tax_rate', '0.00') / 100;
                $taxAmount = $subtotal * $taxRate;
                $grandTotal = $subtotal + $taxAmount;
                
                $totals = [
                    'subtotal' => $subtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $grandTotal,
                    'currency' => DEFAULT_CURRENCY,
                    'formatted' => [
                        'subtotal' => formatCurrency($subtotal),
                        'tax_amount' => formatCurrency($taxAmount),
                        'total' => formatCurrency($grandTotal)
                    ]
                ];
            }
            
            // Prepare response
            $response = [
                'success' => true,
                'data' => [
                    'items' => $processedItems,
                    'summary' => [
                        'item_count' => count($processedItems),
                        'total_quantity' => $totalQuantity,
                        'subtotal' => $subtotal,
                        'is_empty' => empty($processedItems)
                    ]
                ]
            ];
            
            if ($totals) {
                $response['data']['totals'] = $totals;
            }
            
            if ($includeDetails) {
                $response['data']['metadata'] = [
                    'user_id' => $userId,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'timezone' => date_default_timezone_get()
                ];
            }
            
            jsonResponse($response);
            break;
    }
    
} catch (Exception $e) {
    error_log("Get cart API error: " . $e->getMessage());
    
    http_response_code(500);
    jsonResponse([
        'success' => false, 
        'message' => __('server_error', 'An error occurred. Please try again.')
    ], 500);
}
?>