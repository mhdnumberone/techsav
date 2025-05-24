<?php
/**
 * Cart API Index - TechSavvyGenLtd Project
 * Provides information about available cart API endpoints
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include configuration
require_once '../../config/config.php';

// Only allow GET requests for API documentation
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check if user is logged in (optional for documentation)
$isAuthenticated = isLoggedIn();

// API Documentation
$apiDocumentation = [
    'api_name' => 'TechSavvyGenLtd Cart Management API',
    'version' => '1.0.0',
    'description' => 'RESTful API for managing shopping cart operations',
    'base_url' => SITE_URL . '/api/cart/',
    'authentication_required' => true,
    'csrf_protection' => true,
    'current_user_authenticated' => $isAuthenticated,
    
    'endpoints' => [
        'add' => [
            'url' => 'add.php',
            'method' => 'POST',
            'description' => 'Add item to shopping cart',
            'authentication' => 'required',
            'csrf_token' => 'required',
            'parameters' => [
                'type' => [
                    'type' => 'string',
                    'required' => true,
                    'values' => ['product', 'service', 'custom_service'],
                    'description' => 'Type of item to add'
                ],
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID of the item to add'
                ],
                'quantity' => [
                    'type' => 'integer',
                    'required' => true,
                    'minimum' => 1,
                    'description' => 'Quantity to add'
                ],
                'csrf_token' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'CSRF protection token'
                ]
            ],
            'response' => [
                'success' => 'boolean',
                'message' => 'string',
                'data' => 'object',
                'cart_count' => 'integer'
            ]
        ],
        
        'remove' => [
            'url' => 'remove.php',
            'method' => ['POST', 'DELETE'],
            'description' => 'Remove item from shopping cart or clear entire cart',
            'authentication' => 'required',
            'csrf_token' => 'required',
            'parameters' => [
                'cart_item_id' => [
                    'type' => 'integer',
                    'required' => 'conditional',
                    'description' => 'Cart item ID to remove (required for single item removal)'
                ],
                'item_type' => [
                    'type' => 'string',
                    'required' => 'conditional',
                    'values' => ['product', 'service', 'custom_service'],
                    'description' => 'Alternative identification method'
                ],
                'item_id' => [
                    'type' => 'integer',
                    'required' => 'conditional',
                    'description' => 'Item ID for alternative identification'
                ],
                'clear_cart' => [
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'Set to true to clear entire cart'
                ],
                'csrf_token' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'CSRF protection token'
                ]
            ],
            'response' => [
                'success' => 'boolean',
                'message' => 'string',
                'data' => 'object',
                'cart_count' => 'integer'
            ]
        ],
        
        'update' => [
            'url' => 'update.php',
            'method' => ['POST', 'PUT', 'PATCH'],
            'description' => 'Update cart item quantity (single or bulk update)',
            'authentication' => 'required',
            'csrf_token' => 'required',
            'parameters' => [
                'cart_item_id' => [
                    'type' => 'integer',
                    'required' => 'conditional',
                    'description' => 'Cart item ID to update (for single update)'
                ],
                'quantity' => [
                    'type' => 'integer',
                    'required' => 'conditional',
                    'minimum' => 1,
                    'description' => 'New quantity (for single update)'
                ],
                'items' => [
                    'type' => 'array',
                    'required' => 'conditional',
                    'description' => 'Array of items for bulk update',
                    'items' => [
                        'cart_item_id' => 'integer',
                        'quantity' => 'integer'
                    ]
                ],
                'csrf_token' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'CSRF protection token'
                ]
            ],
            'response' => [
                'success' => 'boolean',
                'message' => 'string',
                'data' => 'object',
                'cart_count' => 'integer'
            ]
        ],
        
        'get' => [
            'url' => 'get.php',
            'method' => 'GET',
            'description' => 'Retrieve cart contents with various format options',
            'authentication' => 'required',
            'csrf_token' => 'not_required',
            'parameters' => [
                'format' => [
                    'type' => 'string',
                    'required' => false,
                    'values' => ['full', 'summary', 'count'],
                    'default' => 'full',
                    'description' => 'Response format'
                ],
                'details' => [
                    'type' => 'boolean',
                    'required' => false,
                    'default' => false,
                    'description' => 'Include additional item details'
                ],
                'totals' => [
                    'type' => 'boolean',
                    'required' => false,
                    'default' => false,
                    'description' => 'Calculate and include cart totals'
                ]
            ],
            'response' => [
                'success' => 'boolean',
                'data' => 'object (varies by format)'
            ]
        ]
    ],
    
    'error_codes' => [
        400 => 'Bad Request - Invalid parameters or data',
        401 => 'Unauthorized - Authentication required',
        403 => 'Forbidden - Invalid CSRF token or access denied',
        404 => 'Not Found - Item or cart item not found',
        405 => 'Method Not Allowed - HTTP method not supported',
        500 => 'Internal Server Error - Server error occurred'
    ],
    
    'data_types' => [
        'product' => 'Physical or digital products from the catalog',
        'service' => 'Professional services offered by the company',
        'custom_service' => 'Customized services created for specific users'
    ],
    
    'rate_limiting' => [
        'enabled' => false,
        'note' => 'Rate limiting may be implemented in future versions'
    ],
    
    'security_notes' => [
        'All cart operations require user authentication',
        'CSRF tokens are required for all state-changing operations',
        'Stock availability is validated for physical products',
        'Service and custom service quantities are limited to 1',
        'All user inputs are sanitized and validated'
    ]
];

// Add current CSRF token if user is authenticated
if ($isAuthenticated) {
    $apiDocumentation['csrf_token'] = generateCsrfToken();
    $apiDocumentation['user_info'] = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}

// Add example usage if requested
if (isset($_GET['examples']) && $_GET['examples'] === 'true') {
    $apiDocumentation['examples'] = [
        'add_product' => [
            'url' => 'POST /api/cart/add.php',
            'headers' => [
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest'
            ],
            'body' => [
                'type' => 'product',
                'id' => 123,
                'quantity' => 2,
                'csrf_token' => 'your_csrf_token_here'
            ]
        ],
        'get_cart_summary' => [
            'url' => 'GET /api/cart/get.php?format=summary',
            'headers' => [
                'Content-Type: application/json'
            ]
        ],
        'update_quantity' => [
            'url' => 'POST /api/cart/update.php',
            'headers' => [
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest'
            ],
            'body' => [
                'cart_item_id' => 456,
                'quantity' => 3,
                'csrf_token' => 'your_csrf_token_here'
            ]
        ]
    ];
}

// Response
jsonResponse([
    'success' => true,
    'documentation' => $apiDocumentation
]);
?>