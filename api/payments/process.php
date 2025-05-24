<?php
/**
 * Payment Processing API
 * TechSavvyGenLtd Project
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => 'Unknown error occurred'
];

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        $response['message'] = 'Authentication required';
        echo json_encode($response);
        exit;
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Fallback to POST data if JSON decode fails
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }

    // Validate CSRF token
    if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
        http_response_code(403);
        $response['message'] = 'Invalid security token';
        echo json_encode($response);
        exit;
    }

    // Initialize classes
    $paymentClass = new Payment();
    $orderClass = new Order();
    $userClass = new User();
    
    $userId = $_SESSION['user_id'];
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'process_order_payment':
            // Process payment for an existing order
            $orderId = (int)($input['order_id'] ?? 0);
            $paymentMethod = cleanInput($input['payment_method'] ?? '');
            
            if ($orderId <= 0) {
                http_response_code(400);
                $response['message'] = 'Invalid order ID';
                break;
            }

            if (empty($paymentMethod)) {
                http_response_code(400);
                $response['message'] = 'Payment method is required';
                break;
            }

            // Validate payment method
            $validMethods = [PAYMENT_METHOD_STRIPE, PAYMENT_METHOD_PAYPAL, PAYMENT_METHOD_WALLET, PAYMENT_METHOD_BANK];
            if (!in_array($paymentMethod, $validMethods)) {
                http_response_code(400);
                $response['message'] = 'Invalid payment method';
                break;
            }

            // Get order details
            $order = $orderClass->getById($orderId);
            if (!$order || $order['user_id'] != $userId) {
                http_response_code(404);
                $response['message'] = 'Order not found';
                break;
            }

            if ($order['payment_status'] !== PAYMENT_STATUS_PENDING) {
                http_response_code(400);
                $response['message'] = 'Order payment already processed';
                break;
            }

            // Prepare payment data
            $paymentData = [
                'payment_method' => $paymentMethod,
                'billing_address' => cleanInput($input['billing_address'] ?? ''),
                'metadata' => [
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'session_id' => session_id()
                ]
            ];

            // Add payment method specific data
            switch ($paymentMethod) {
                case PAYMENT_METHOD_STRIPE:
                    $paymentData['stripe_token'] = cleanInput($input['stripe_token'] ?? '');
                    $paymentData['stripe_payment_method_id'] = cleanInput($input['stripe_payment_method_id'] ?? '');
                    
                    if (empty($paymentData['stripe_token']) && empty($paymentData['stripe_payment_method_id'])) {
                        http_response_code(400);
                        $response['message'] = 'Stripe payment token or payment method is required';
                        break 2;
                    }
                    break;

                case PAYMENT_METHOD_PAYPAL:
                    $paymentData['paypal_payment_id'] = cleanInput($input['paypal_payment_id'] ?? '');
                    $paymentData['paypal_payer_id'] = cleanInput($input['paypal_payer_id'] ?? '');
                    
                    if (empty($paymentData['paypal_payment_id'])) {
                        http_response_code(400);
                        $response['message'] = 'PayPal payment ID is required';
                        break 2;
                    }
                    break;

                case PAYMENT_METHOD_WALLET:
                    // Check wallet balance
                    $user = $userClass->getById($userId);
                    if (!$user || $user['wallet_balance'] < $order['total_amount']) {
                        http_response_code(400);
                        $response['message'] = 'Insufficient wallet balance';
                        break 2;
                    }
                    break;

                case PAYMENT_METHOD_BANK:
                    $paymentData['bank_reference'] = cleanInput($input['bank_reference'] ?? '');
                    $paymentData['transfer_date'] = cleanInput($input['transfer_date'] ?? date('Y-m-d'));
                    $paymentData['bank_name'] = cleanInput($input['bank_name'] ?? '');
                    break;
            }

            // Process payment
            $result = $paymentClass->processPayment($orderId, $paymentData);
            
            if ($result['success']) {
                $response['success'] = true;
                $response['message'] = $result['message'];
                $response['payment_id'] = $result['payment_id'];
                $response['transaction_id'] = $result['transaction_id'];
                $response['order_id'] = $orderId;
                
                // Return redirect URL for bank transfers or pending payments
                if ($paymentMethod === PAYMENT_METHOD_BANK) {
                    $response['redirect_url'] = SITE_URL . '/order-pending.php?order=' . $order['order_number'];
                } else {
                    $response['redirect_url'] = SITE_URL . '/order-success.php?order=' . $order['order_number'];
                }

                // Log successful payment
                logActivity('payment_processed', "Payment processed for order {$order['order_number']}", $userId);

            } else {
                http_response_code(400);
                $response['message'] = $result['message'];
                
                // Log failed payment attempt
                logActivity('payment_failed', "Payment failed for order {$order['order_number']}: {$result['message']}", $userId);
            }
            break;

        case 'process_custom_service_payment':
            // Process payment for custom service
            $customServiceId = (int)($input['custom_service_id'] ?? 0);
            $paymentMethod = cleanInput($input['payment_method'] ?? '');
            
            if ($customServiceId <= 0) {
                http_response_code(400);
                $response['message'] = 'Invalid custom service ID';
                break;
            }

            // Create order for custom service first
            $orderResult = $orderClass->createForCustomService($customServiceId, $input);
            
            if (!$orderResult['success']) {
                http_response_code(400);
                $response['message'] = $orderResult['message'];
                break;
            }

            $orderId = $orderResult['order_id'];
            
            // Now process payment for the created order
            $paymentData = [
                'payment_method' => $paymentMethod,
                'billing_address' => cleanInput($input['billing_address'] ?? '')
            ];

            $result = $paymentClass->processPayment($orderId, $paymentData);
            
            if ($result['success']) {
                $response['success'] = true;
                $response['message'] = 'Custom service payment processed successfully';
                $response['payment_id'] = $result['payment_id'];
                $response['transaction_id'] = $result['transaction_id'];
                $response['order_id'] = $orderId;
                $response['order_number'] = $orderResult['order_number'];
            } else {
                // If payment fails, cancel the order
                $orderClass->cancelOrder($orderId, 'Payment failed: ' . $result['message']);
                http_response_code(400);
                $response['message'] = $result['message'];
            }
            break;

        case 'add_wallet_funds':
            // Add funds to user wallet (requires special validation)
            $amount = (float)($input['amount'] ?? 0);
            $description = cleanInput($input['description'] ?? 'Wallet top-up');
            
            if ($amount <= 0) {
                http_response_code(400);
                $response['message'] = 'Invalid amount';
                break;
            }

            if ($amount > 10000) { // Max wallet top-up limit
                http_response_code(400);
                $response['message'] = 'Amount exceeds maximum limit';
                break;
            }

            // For wallet funds, we need to process payment first, then add to wallet
            // This is a simplified version - in production, integrate with payment processor
            $result = $paymentClass->addWalletFunds($userId, $amount, $description);
            
            if ($result['success']) {
                $response['success'] = true;
                $response['message'] = 'Wallet funds added successfully';
                $response['previous_balance'] = $result['previous_balance'];
                $response['new_balance'] = $result['new_balance'];
                $response['amount_added'] = $amount;
            } else {
                http_response_code(500);
                $response['message'] = $result['message'];
            }
            break;

        case 'validate_payment_data':
            // Validate payment data before processing
            $paymentMethod = cleanInput($input['payment_method'] ?? '');
            $amount = (float)($input['amount'] ?? 0);
            
            $validation = [
                'valid' => true,
                'errors' => []
            ];

            if (empty($paymentMethod)) {
                $validation['valid'] = false;
                $validation['errors'][] = 'Payment method is required';
            }

            if ($amount <= 0) {
                $validation['valid'] = false;
                $validation['errors'][] = 'Invalid amount';
            }

            // Method-specific validation
            switch ($paymentMethod) {
                case PAYMENT_METHOD_WALLET:
                    $user = $userClass->getById($userId);
                    if (!$user || $user['wallet_balance'] < $amount) {
                        $validation['valid'] = false;
                        $validation['errors'][] = 'Insufficient wallet balance';
                        $validation['wallet_balance'] = $user['wallet_balance'] ?? 0;
                    }
                    break;

                case PAYMENT_METHOD_STRIPE:
                    if (empty(getSetting('stripe_public_key')) || empty(getSetting('stripe_secret_key'))) {
                        $validation['valid'] = false;
                        $validation['errors'][] = 'Stripe payment not configured';
                    }
                    break;

                case PAYMENT_METHOD_PAYPAL:
                    if (empty(getSetting('paypal_client_id')) || empty(getSetting('paypal_client_secret'))) {
                        $validation['valid'] = false;
                        $validation['errors'][] = 'PayPal payment not configured';
                    }
                    break;
            }

            $response['success'] = true;
            $response['validation'] = $validation;
            break;

        case 'get_payment_methods':
            // Get available payment methods for user
            $user = $userClass->getById($userId);
            $amount = (float)($_GET['amount'] ?? 0);
            
            $methods = [];

            // Stripe
            if (getSetting('stripe_public_key') && getSetting('stripe_secret_key')) {
                $methods[] = [
                    'id' => PAYMENT_METHOD_STRIPE,
                    'name' => 'Credit/Debit Card',
                    'icon' => 'fab fa-cc-stripe',
                    'description' => 'Pay securely with your credit or debit card',
                    'enabled' => true
                ];
            }

            // PayPal
            if (getSetting('paypal_client_id') && getSetting('paypal_client_secret')) {
                $methods[] = [
                    'id' => PAYMENT_METHOD_PAYPAL,
                    'name' => 'PayPal',
                    'icon' => 'fab fa-paypal',
                    'description' => 'Pay with your PayPal account',
                    'enabled' => true
                ];
            }

            // Wallet
            $walletEnabled = $user && $user['wallet_balance'] >= $amount;
            $methods[] = [
                'id' => PAYMENT_METHOD_WALLET,
                'name' => 'Wallet Payment',
                'icon' => 'fas fa-wallet',
                'description' => 'Pay from your wallet balance',
                'enabled' => $walletEnabled,
                'wallet_balance' => $user['wallet_balance'] ?? 0,
                'insufficient_balance' => !$walletEnabled && $amount > 0
            ];

            // Bank Transfer
            $methods[] = [
                'id' => PAYMENT_METHOD_BANK,
                'name' => 'Bank Transfer',
                'icon' => 'fas fa-university',
                'description' => 'Pay via bank transfer (manual verification required)',
                'enabled' => true
            ];

            $response['success'] = true;
            $response['payment_methods'] = $methods;
            break;

        default:
            http_response_code(400);
            $response['message'] = 'Invalid action specified';
            break;
    }

} catch (Exception $e) {
    error_log("Payment processing API error: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Internal server error';
    $response['error_code'] = 'PAYMENT_PROCESSING_ERROR';
}

echo json_encode($response);
?>