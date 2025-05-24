<?php
/**
 * Payment Webhook Handler
 * TechSavvyGenLtd Project
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../../config/config.php';

// Disable session for webhooks
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Webhook processing failed'
];

// Log all webhook attempts
$webhook_log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        logWebhookEvent('error', 'Invalid method: ' . $_SERVER['REQUEST_METHOD'], $webhook_log);
        echo json_encode($response);
        exit;
    }

    // Get webhook provider from URL parameter or header
    $provider = $_GET['provider'] ?? $_SERVER['HTTP_X_WEBHOOK_PROVIDER'] ?? '';
    
    if (empty($provider)) {
        // Try to detect provider from headers
        if (isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            $provider = 'stripe';
        } elseif (isset($_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'])) {
            $provider = 'paypal';
        }
    }

    $webhook_log['provider'] = $provider;

    switch ($provider) {
        case 'stripe':
            handleStripeWebhook($webhook_log);
            break;

        case 'paypal':
            handlePayPalWebhook($webhook_log);
            break;

        case 'test':
            // Test webhook endpoint
            if (DEBUG_MODE) {
                handleTestWebhook($webhook_log);
            } else {
                http_response_code(404);
                $response['message'] = 'Endpoint not found';
            }
            break;

        default:
            http_response_code(400);
            $response['message'] = 'Invalid or missing webhook provider';
            logWebhookEvent('error', 'Invalid provider: ' . $provider, $webhook_log);
            break;
    }

} catch (Exception $e) {
    error_log("Webhook processing error: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Internal server error';
    logWebhookEvent('error', 'Exception: ' . $e->getMessage(), $webhook_log);
}

echo json_encode($response);

/**
 * Handle Stripe webhooks
 */
function handleStripeWebhook($webhook_log) {
    global $response;

    $payload = $webhook_log['body'];
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    if (empty($sig_header)) {
        http_response_code(400);
        $response['message'] = 'Missing Stripe signature';
        logWebhookEvent('error', 'Missing Stripe signature', $webhook_log);
        return;
    }

    // Get Stripe webhook secret from settings
    $webhook_secret = getSetting('stripe_webhook_secret', '');
    
    if (empty($webhook_secret)) {
        http_response_code(500);
        $response['message'] = 'Stripe webhook secret not configured';
        logWebhookEvent('error', 'Webhook secret not configured', $webhook_log);
        return;
    }

    // Verify webhook signature
    if (!verifyStripeSignature($payload, $sig_header, $webhook_secret)) {
        http_response_code(401);
        $response['message'] = 'Invalid Stripe signature';
        logWebhookEvent('error', 'Invalid Stripe signature', $webhook_log);
        return;
    }

    $event = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        $response['message'] = 'Invalid JSON payload';
        logWebhookEvent('error', 'Invalid JSON in Stripe webhook', $webhook_log);
        return;
    }

    $webhook_log['event_type'] = $event['type'] ?? 'unknown';
    $webhook_log['event_id'] = $event['id'] ?? 'unknown';

    logWebhookEvent('info', 'Stripe webhook received: ' . $webhook_log['event_type'], $webhook_log);

    // Handle different Stripe events
    switch ($event['type']) {
        case 'payment_intent.succeeded':
            handleStripePaymentSuccess($event, $webhook_log);
            break;

        case 'payment_intent.payment_failed':
            handleStripePaymentFailed($event, $webhook_log);
            break;

        case 'charge.dispute.created':
            handleStripeChargeback($event, $webhook_log);
            break;

        case 'invoice.payment_succeeded':
            handleStripeInvoicePayment($event, $webhook_log);
            break;

        case 'customer.subscription.created':
        case 'customer.subscription.updated':
        case 'customer.subscription.deleted':
            handleStripeSubscription($event, $webhook_log);
            break;

        default:
            // Log unhandled event but return success
            logWebhookEvent('info', 'Unhandled Stripe event: ' . $event['type'], $webhook_log);
            $response['success'] = true;
            $response['message'] = 'Event received but not handled';
            break;
    }
}

/**
 * Handle PayPal webhooks
 */
function handlePayPalWebhook($webhook_log) {
    global $response;

    $payload = $webhook_log['body'];
    $headers = $webhook_log['headers'];
    
    // Verify PayPal webhook
    if (!verifyPayPalWebhook($payload, $headers)) {
        http_response_code(401);
        $response['message'] = 'Invalid PayPal webhook';
        logWebhookEvent('error', 'Invalid PayPal webhook signature', $webhook_log);
        return;
    }

    $event = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        $response['message'] = 'Invalid JSON payload';
        logWebhookEvent('error', 'Invalid JSON in PayPal webhook', $webhook_log);
        return;
    }

    $webhook_log['event_type'] = $event['event_type'] ?? 'unknown';
    $webhook_log['event_id'] = $event['id'] ?? 'unknown';

    logWebhookEvent('info', 'PayPal webhook received: ' . $webhook_log['event_type'], $webhook_log);

    // Handle different PayPal events
    switch ($event['event_type']) {
        case 'PAYMENT.CAPTURE.COMPLETED':
            handlePayPalPaymentSuccess($event, $webhook_log);
            break;

        case 'PAYMENT.CAPTURE.DENIED':
        case 'PAYMENT.CAPTURE.FAILED':
            handlePayPalPaymentFailed($event, $webhook_log);
            break;

        case 'PAYMENT.CAPTURE.REFUNDED':
            handlePayPalRefund($event, $webhook_log);
            break;

        default:
            // Log unhandled event but return success
            logWebhookEvent('info', 'Unhandled PayPal event: ' . $event['event_type'], $webhook_log);
            $response['success'] = true;
            $response['message'] = 'Event received but not handled';
            break;
    }
}

/**
 * Handle test webhooks (debug mode only)
 */
function handleTestWebhook($webhook_log) {
    global $response;
    
    $payload = json_decode($webhook_log['body'], true);
    
    logWebhookEvent('info', 'Test webhook received', array_merge($webhook_log, ['payload' => $payload]));
    
    $response['success'] = true;
    $response['message'] = 'Test webhook received successfully';
    $response['received_data'] = $payload;
}

/**
 * Handle Stripe payment success
 */
function handleStripePaymentSuccess($event, $webhook_log) {
    global $response;

    try {
        $payment_intent = $event['data']['object'];
        $transaction_id = $payment_intent['id'];
        $amount = $payment_intent['amount'] / 100; // Convert from cents
        $currency = strtoupper($payment_intent['currency']);

        // Find payment record
        $paymentClass = new Payment();
        $payment = $paymentClass->getByTransactionId($transaction_id);

        if (!$payment) {
            logWebhookEvent('warning', 'Payment not found for transaction: ' . $transaction_id, $webhook_log);
            $response['success'] = true; // Return success to prevent retries
            $response['message'] = 'Payment not found';
            return;
        }

        if ($payment['status'] === PAYMENT_STATUS_COMPLETED) {
            logWebhookEvent('info', 'Payment already completed: ' . $transaction_id, $webhook_log);
            $response['success'] = true;
            $response['message'] = 'Payment already completed';
            return;
        }

        // Update payment status
        $db = Database::getInstance();
        $db->beginTransaction();

        $db->update(
            TBL_PAYMENTS,
            [
                'status' => PAYMENT_STATUS_COMPLETED,
                'payment_data' => json_encode([
                    'stripe_payment_intent' => $payment_intent,
                    'webhook_processed' => date('Y-m-d H:i:s')
                ]),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$payment['id']]
        );

        // Update order status
        $orderClass = new Order();
        $orderClass->updatePaymentStatus($payment['order_id'], PAYMENT_STATUS_PAID);

        $db->commit();

        // Send notification
        createNotification(
            $payment['user_id'],
            'تم تأكيد الدفع',
            'Payment Confirmed',
            'تم تأكيد دفع طلبك بنجاح',
            'Your payment has been confirmed successfully',
            NOTIFICATION_PAYMENT
        );

        logWebhookEvent('success', 'Payment completed: ' . $transaction_id, $webhook_log);

        $response['success'] = true;
        $response['message'] = 'Payment processed successfully';

    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollback();
        }
        throw $e;
    }
}

/**
 * Handle Stripe payment failure
 */
function handleStripePaymentFailed($event, $webhook_log) {
    global $response;

    try {
        $payment_intent = $event['data']['object'];
        $transaction_id = $payment_intent['id'];
        $failure_reason = $payment_intent['last_payment_error']['message'] ?? 'Payment failed';

        // Find payment record
        $paymentClass = new Payment();
        $payment = $paymentClass->getByTransactionId($transaction_id);

        if (!$payment) {
            logWebhookEvent('warning', 'Payment not found for failed transaction: ' . $transaction_id, $webhook_log);
            $response['success'] = true;
            $response['message'] = 'Payment not found';
            return;
        }

        // Update payment status
        $db = Database::getInstance();
        $db->update(
            TBL_PAYMENTS,
            [
                'status' => PAYMENT_STATUS_FAILED,
                'payment_data' => json_encode([
                    'stripe_payment_intent' => $payment_intent,
                    'failure_reason' => $failure_reason,
                    'webhook_processed' => date('Y-m-d H:i:s')
                ]),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$payment['id']]
        );

        // Update order status
        $orderClass = new Order();
        $orderClass->updatePaymentStatus($payment['order_id'], PAYMENT_STATUS_FAILED);

        // Send notification
        createNotification(
            $payment['user_id'],
            'فشل في الدفع',
            'Payment Failed',
            'فشل في دفع طلبك: ' . $failure_reason,
            'Payment failed: ' . $failure_reason,
            NOTIFICATION_ERROR
        );

        logWebhookEvent('info', 'Payment failed: ' . $transaction_id . ' - ' . $failure_reason, $webhook_log);

        $response['success'] = true;
        $response['message'] = 'Payment failure processed';

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Handle PayPal payment success
 */
function handlePayPalPaymentSuccess($event, $webhook_log) {
    global $response;

    try {
        $capture = $event['resource'];
        $transaction_id = $capture['id'];
        $amount = (float)$capture['amount']['value'];
        $currency = $capture['amount']['currency_code'];

        // Find payment record
        $paymentClass = new Payment();
        $payment = $paymentClass->getByTransactionId($transaction_id);

        if (!$payment) {
            logWebhookEvent('warning', 'Payment not found for PayPal transaction: ' . $transaction_id, $webhook_log);
            $response['success'] = true;
            $response['message'] = 'Payment not found';
            return;
        }

        // Update payment status
        $db = Database::getInstance();
        $db->update(
            TBL_PAYMENTS,
            [
                'status' => PAYMENT_STATUS_COMPLETED,
                'payment_data' => json_encode([
                    'paypal_capture' => $capture,
                    'webhook_processed' => date('Y-m-d H:i:s')
                ]),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$payment['id']]
        );

        // Update order status
        $orderClass = new Order();
        $orderClass->updatePaymentStatus($payment['order_id'], PAYMENT_STATUS_PAID);

        // Send notification
        createNotification(
            $payment['user_id'],
            'تم تأكيد الدفع',
            'Payment Confirmed',
            'تم تأكيد دفع طلبك بنجاح عبر PayPal',
            'Your PayPal payment has been confirmed successfully',
            NOTIFICATION_PAYMENT
        );

        logWebhookEvent('success', 'PayPal payment completed: ' . $transaction_id, $webhook_log);

        $response['success'] = true;
        $response['message'] = 'PayPal payment processed successfully';

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Verify Stripe webhook signature
 */
function verifyStripeSignature($payload, $sig_header, $webhook_secret) {
    $elements = explode(',', $sig_header);
    $signature = '';
    $timestamp = '';

    foreach ($elements as $element) {
        $item = explode('=', $element, 2);
        if ($item[0] === 'v1') {
            $signature = $item[1];
        } elseif ($item[0] === 't') {
            $timestamp = $item[1];
        }
    }

    if (empty($signature) || empty($timestamp)) {
        return false;
    }

    // Check timestamp (prevent replay attacks)
    if (abs(time() - $timestamp) > 300) { // 5 minutes tolerance
        return false;
    }

    // Verify signature
    $expected_signature = hash_hmac('sha256', $timestamp . '.' . $payload, $webhook_secret);
    
    return hash_equals($expected_signature, $signature);
}

/**
 * Verify PayPal webhook
 */
function verifyPayPalWebhook($payload, $headers) {
    // PayPal webhook verification is more complex and requires API calls
    // For now, we'll do basic verification
    
    $required_headers = [
        'PAYPAL-TRANSMISSION-ID',
        'PAYPAL-CERT-ID', 
        'PAYPAL-AUTH-ALGO',
        'PAYPAL-TRANSMISSION-TIME',
        'PAYPAL-AUTH-VERSION'
    ];

    foreach ($required_headers as $header) {
        if (!isset($headers[$header])) {
            return false;
        }
    }

    // In production, implement full PayPal webhook verification
    // https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
    
    return true;
}

/**
 * Log webhook events
 */
function logWebhookEvent($level, $message, $webhook_log) {
    try {
        $db = Database::getInstance();
        
        $logData = [
            'user_id' => null,
            'action' => 'webhook_' . ($webhook_log['provider'] ?? 'unknown'),
            'description' => $message,
            'ip_address' => $webhook_log['ip'],
            'user_agent' => $webhook_log['user_agent'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db->insert(TBL_SYSTEM_LOGS, $logData);

        // Also log to file for debugging
        if (DEBUG_MODE) {
            $log_entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => $level,
                'message' => $message,
                'webhook_data' => $webhook_log
            ];
            
            error_log("WEBHOOK: " . json_encode($log_entry));
        }

    } catch (Exception $e) {
        error_log("Failed to log webhook event: " . $e->getMessage());
    }
}

/**
 * Create notification helper
 */
function createNotification($userId, $titleAr, $titleEn, $messageAr, $messageEn, $type, $link = '') {
    try {
        $notificationClass = new Notification();
        $notificationClass->sendToUser($userId, $titleAr, $titleEn, $messageAr, $messageEn, $type, $link);
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
    }
}

/**
 * Get setting helper
 */
function getSetting($key, $default = '') {
    try {
        $db = Database::getInstance();
        $value = $db->fetchColumn(
            "SELECT setting_value FROM " . TBL_SETTINGS . " WHERE setting_key = ?",
            [$key]
        );
        return $value !== false ? $value : $default;
    } catch (Exception $e) {
        return $default;
    }
}
?>