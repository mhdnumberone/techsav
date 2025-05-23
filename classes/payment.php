<?php
/**
 * Payment Management Class
 * TechSavvyGenLtd Project
 */

class Payment {
    private $db;
    private $table = TBL_PAYMENTS;
    private $ordersTable = TBL_ORDERS;
    private $usersTable = TBL_USERS;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Process payment for order
     */
    public function processPayment($orderId, $paymentData) {
        try {
            // Validate order
            $order = $this->db->fetch(
                "SELECT * FROM {$this->ordersTable} WHERE id = ? AND payment_status = ?",
                [$orderId, PAYMENT_STATUS_PENDING]
            );
            
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found or already paid'];
            }
            
            // Validate payment method
            $validMethods = [PAYMENT_METHOD_STRIPE, PAYMENT_METHOD_PAYPAL, PAYMENT_METHOD_WALLET, PAYMENT_METHOD_BANK];
            if (!in_array($paymentData['payment_method'], $validMethods)) {
                return ['success' => false, 'message' => 'Invalid payment method'];
            }
            
            $this->db->beginTransaction();
            
            // Process payment based on method
            $result = $this->processPaymentByMethod($order, $paymentData);
            
            if (!$result['success']) {
                $this->db->rollback();
                return $result;
            }
            
            // Create payment record
            $payment = [
                'order_id' => $orderId,
                'user_id' => $order['user_id'],
                'transaction_id' => $result['transaction_id'],
                'payment_method' => $paymentData['payment_method'],
                'amount' => $order['total_amount'],
                'currency' => DEFAULT_CURRENCY,
                'status' => PAYMENT_STATUS_COMPLETED,
                'payment_data' => json_encode($result['payment_data'] ?? []),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $paymentId = $this->db->insert($this->table, $payment);
            
            if (!$paymentId) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Failed to record payment'];
            }
            
            // Update order payment status
            $this->db->update(
                $this->ordersTable,
                [
                    'payment_status' => PAYMENT_STATUS_PAID,
                    'payment_method' => $paymentData['payment_method'],
                    'status' => ORDER_STATUS_PROCESSING,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$orderId]
            );
            
            $this->db->commit();
            
            // Send payment confirmation
            $this->sendPaymentConfirmation($paymentId);
            
            // Create notification
            createNotification(
                $order['user_id'],
                'تم تأكيد الدفع',
                'Payment Confirmed',
                "تم تأكيد دفع الطلب رقم: {$order['order_number']}",
                "Payment confirmed for order: {$order['order_number']}",
                NOTIFICATION_PAYMENT
            );
            
            logActivity('payment_processed', "Payment processed for order {$order['order_number']}", $order['user_id']);
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $paymentId,
                'transaction_id' => $result['transaction_id']
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Payment processing failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Payment processing failed. Please try again.'];
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund($paymentId, $amount = null, $reason = '') {
        try {
            $payment = $this->getById($paymentId);
            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }
            
            if ($payment['status'] !== PAYMENT_STATUS_COMPLETED) {
                return ['success' => false, 'message' => 'Payment cannot be refunded'];
            }
            
            $refundAmount = $amount ?? $payment['amount'];
            if ($refundAmount > $payment['amount']) {
                return ['success' => false, 'message' => 'Refund amount exceeds payment amount'];
            }
            
            $this->db->beginTransaction();
            
            // Process refund based on payment method
            $result = $this->processRefundByMethod($payment, $refundAmount, $reason);
            
            if (!$result['success']) {
                $this->db->rollback();
                return $result;
            }
            
            // Create refund payment record
            $refundPayment = [
                'order_id' => $payment['order_id'],
                'user_id' => $payment['user_id'],
                'transaction_id' => $result['refund_transaction_id'],
                'payment_method' => $payment['payment_method'],
                'amount' => -$refundAmount,
                'currency' => $payment['currency'],
                'status' => PAYMENT_STATUS_REFUNDED,
                'payment_data' => json_encode([
                    'original_payment_id' => $paymentId,
                    'refund_reason' => $reason,
                    'refund_data' => $result['refund_data'] ?? []
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $refundPaymentId = $this->db->insert($this->table, $refundPayment);
            
            if (!$refundPaymentId) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Failed to record refund'];
            }
            
            // Update original payment status if full refund
            if ($refundAmount == $payment['amount']) {
                $this->db->update(
                    $this->table,
                    ['status' => PAYMENT_STATUS_REFUNDED, 'updated_at' => date('Y-m-d H:i:s')],
                    'id = ?',
                    [$paymentId]
                );
                
                // Update order status
                $this->db->update(
                    $this->ordersTable,
                    [
                        'payment_status' => PAYMENT_STATUS_REFUNDED,
                        'status' => ORDER_STATUS_REFUNDED,
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    'id = ?',
                    [$payment['order_id']]
                );
            }
            
            $this->db->commit();
            
            // Send refund notification
            createNotification(
                $payment['user_id'],
                'تم استرداد المبلغ',
                'Refund Processed',
                "تم استرداد مبلغ " . formatCurrency($refundAmount),
                "Refund of " . formatCurrency($refundAmount) . " has been processed",
                NOTIFICATION_PAYMENT
            );
            
            logActivity('refund_processed', "Refund of {$refundAmount} processed for payment ID {$paymentId}", $_SESSION['user_id'] ?? null);
            
            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund_payment_id' => $refundPaymentId,
                'refund_transaction_id' => $result['refund_transaction_id']
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Refund processing failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Refund processing failed. Please try again.'];
        }
    }
    
    /**
     * Get payment by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT p.*, o.order_number, u.first_name, u.last_name, u.email
                    FROM {$this->table} p
                    LEFT JOIN {$this->ordersTable} o ON p.order_id = o.id
                    LEFT JOIN {$this->usersTable} u ON p.user_id = u.id
                    WHERE p.id = ?";
            
            return $this->db->fetch($sql, [$id]);
            
        } catch (Exception $e) {
            error_log("Get payment by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get payment by transaction ID
     */
    public function getByTransactionId($transactionId) {
        try {
            $sql = "SELECT p.*, o.order_number, u.first_name, u.last_name, u.email
                    FROM {$this->table} p
                    LEFT JOIN {$this->ordersTable} o ON p.order_id = o.id
                    LEFT JOIN {$this->usersTable} u ON p.user_id = u.id
                    WHERE p.transaction_id = ?";
            
            return $this->db->fetch($sql, [$transactionId]);
            
        } catch (Exception $e) {
            error_log("Get payment by transaction ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get order payments
     */
    public function getOrderPayments($orderId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE order_id = ? 
                    ORDER BY created_at DESC";
            
            return $this->db->fetchAll($sql, [$orderId]);
            
        } catch (Exception $e) {
            error_log("Get order payments failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user payments
     */
    public function getUserPayments($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $total = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?",
                [$userId]
            );
            
            // Get payments
            $sql = "SELECT p.*, o.order_number
                    FROM {$this->table} p
                    LEFT JOIN {$this->ordersTable} o ON p.order_id = o.id
                    WHERE p.user_id = ?
                    ORDER BY p.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $payments = $this->db->fetchAll($sql, [$userId]);
            
            return [
                'payments' => $payments,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get user payments failed: " . $e->getMessage());
            return ['payments' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get all payments (admin function)
     */
    public function getAllPayments($page = 1, $limit = ADMIN_ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            // Status filter
            if (!empty($filters['status'])) {
                $conditions[] = "p.status = ?";
                $params[] = $filters['status'];
            }
            
            // Payment method filter
            if (!empty($filters['payment_method'])) {
                $conditions[] = "p.payment_method = ?";
                $params[] = $filters['payment_method'];
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(p.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(p.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $searchTerm = "%{$filters['search']}%";
                $conditions[] = "(p.transaction_id LIKE ? OR o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $totalQuery = "SELECT COUNT(*) FROM {$this->table} p 
                          LEFT JOIN {$this->ordersTable} o ON p.order_id = o.id
                          LEFT JOIN {$this->usersTable} u ON p.user_id = u.id
                          {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get payments
            $sql = "SELECT p.*, o.order_number, u.first_name, u.last_name, u.email
                    FROM {$this->table} p
                    LEFT JOIN {$this->ordersTable} o ON p.order_id = o.id
                    LEFT JOIN {$this->usersTable} u ON p.user_id = u.id
                    {$whereClause}
                    ORDER BY p.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $payments = $this->db->fetchAll($sql, $params);
            
            return [
                'payments' => $payments,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all payments failed: " . $e->getMessage());
            return ['payments' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get payment statistics
     */
    public function getPaymentStatistics($dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($dateFrom) {
                $conditions[] = "DATE(created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "DATE(created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $sql = "SELECT 
                        COUNT(*) as total_payments,
                        COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END), 0) as total_revenue,
                        COALESCE(SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END), 0) as total_refunds,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_payments,
                        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_payments,
                        COUNT(CASE WHEN status = 'refunded' THEN 1 END) as refunded_payments,
                        COUNT(CASE WHEN payment_method = 'stripe' THEN 1 END) as stripe_payments,
                        COUNT(CASE WHEN payment_method = 'paypal' THEN 1 END) as paypal_payments,
                        COUNT(CASE WHEN payment_method = 'wallet' THEN 1 END) as wallet_payments
                    FROM {$this->table} {$whereClause}";
            
            return $this->db->fetch($sql, $params);
            
        } catch (Exception $e) {
            error_log("Get payment statistics failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process wallet payment
     */
    public function processWalletPayment($userId, $amount, $description = '') {
        try {
            $this->db->beginTransaction();
            
            // Get user wallet balance
            $user = $this->db->fetch(
                "SELECT wallet_balance FROM {$this->usersTable} WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'User not found'];
            }
            
            if ($user['wallet_balance'] < $amount) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Insufficient wallet balance'];
            }
            
            // Deduct amount from wallet
            $this->db->update(
                $this->usersTable,
                ['wallet_balance' => $user['wallet_balance'] - $amount],
                'id = ?',
                [$userId]
            );
            
            $this->db->commit();
            
            logActivity('wallet_payment', "Wallet payment of {$amount}. {$description}", $userId);
            
            return [
                'success' => true,
                'transaction_id' => 'WALLET_' . generateRandomString(16),
                'payment_data' => [
                    'previous_balance' => $user['wallet_balance'],
                    'new_balance' => $user['wallet_balance'] - $amount,
                    'description' => $description
                ]
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Wallet payment failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Wallet payment failed'];
        }
    }
    
    /**
     * Add funds to wallet
     */
    public function addWalletFunds($userId, $amount, $description = '') {
        try {
            $this->db->beginTransaction();
            
            // Get current balance
            $currentBalance = $this->db->fetchColumn(
                "SELECT wallet_balance FROM {$this->usersTable} WHERE id = ?",
                [$userId]
            );
            
            $newBalance = $currentBalance + $amount;
            
            // Update balance
            $this->db->update(
                $this->usersTable,
                ['wallet_balance' => $newBalance],
                'id = ?',
                [$userId]
            );
            
            $this->db->commit();
            
            logActivity('wallet_funds_added', "Wallet funds added: {$amount}. {$description}", $userId);
            
            return [
                'success' => true,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Add wallet funds failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add wallet funds'];
        }
    }
    
    /**
     * Payment method specific processing
     */
    private function processPaymentByMethod($order, $paymentData) {
        switch ($paymentData['payment_method']) {
            case PAYMENT_METHOD_STRIPE:
                return $this->processStripePayment($order, $paymentData);
                
            case PAYMENT_METHOD_PAYPAL:
                return $this->processPayPalPayment($order, $paymentData);
                
            case PAYMENT_METHOD_WALLET:
                return $this->processWalletPayment($order['user_id'], $order['total_amount'], "Payment for order {$order['order_number']}");
                
            case PAYMENT_METHOD_BANK:
                return $this->processBankTransfer($order, $paymentData);
                
            default:
                return ['success' => false, 'message' => 'Unsupported payment method'];
        }
    }
    
    /**
     * Refund method specific processing
     */
    private function processRefundByMethod($payment, $amount, $reason) {
        switch ($payment['payment_method']) {
            case PAYMENT_METHOD_STRIPE:
                return $this->processStripeRefund($payment, $amount, $reason);
                
            case PAYMENT_METHOD_PAYPAL:
                return $this->processPayPalRefund($payment, $amount, $reason);
                
            case PAYMENT_METHOD_WALLET:
                // Add refund back to wallet
                $result = $this->addWalletFunds($payment['user_id'], $amount, "Refund: {$reason}");
                if ($result['success']) {
                    return [
                        'success' => true,
                        'refund_transaction_id' => 'WALLET_REFUND_' . generateRandomString(16),
                        'refund_data' => $result
                    ];
                }
                return $result;
                
            default:
                return ['success' => false, 'message' => 'Refund not supported for this payment method'];
        }
    }
    
    /**
     * Process Stripe payment (placeholder - implement with actual Stripe SDK)
     */
    private function processStripePayment($order, $paymentData) {
        // This is a placeholder implementation
        // In production, integrate with Stripe API
        
        try {
            // Simulate Stripe payment processing
            $transactionId = 'stripe_' . generateRandomString(24);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_data' => [
                    'stripe_payment_intent' => $transactionId,
                    'stripe_charge_id' => 'ch_' . generateRandomString(24)
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Stripe payment failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process PayPal payment (placeholder - implement with actual PayPal SDK)
     */
    private function processPayPalPayment($order, $paymentData) {
        // This is a placeholder implementation
        // In production, integrate with PayPal API
        
        try {
            // Simulate PayPal payment processing
            $transactionId = 'paypal_' . generateRandomString(20);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_data' => [
                    'paypal_payment_id' => $transactionId,
                    'paypal_payer_id' => 'PAYER' . generateRandomString(10)
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'PayPal payment failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process bank transfer (manual verification)
     */
    private function processBankTransfer($order, $paymentData) {
        $transactionId = 'bank_' . generateRandomString(16);
        
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'payment_data' => [
                'bank_reference' => $paymentData['bank_reference'] ?? '',
                'transfer_date' => $paymentData['transfer_date'] ?? date('Y-m-d'),
                'requires_verification' => true
            ]
        ];
    }
    
    /**
     * Process Stripe refund (placeholder)
     */
    private function processStripeRefund($payment, $amount, $reason) {
        // Placeholder for Stripe refund implementation
        return [
            'success' => true,
            'refund_transaction_id' => 'stripe_refund_' . generateRandomString(24),
            'refund_data' => ['stripe_refund_id' => 're_' . generateRandomString(24)]
        ];
    }
    
    /**
     * Process PayPal refund (placeholder)
     */
    private function processPayPalRefund($payment, $amount, $reason) {
        // Placeholder for PayPal refund implementation
        return [
            'success' => true,
            'refund_transaction_id' => 'paypal_refund_' . generateRandomString(20),
            'refund_data' => ['paypal_refund_id' => 'REF' . generateRandomString(15)]
        ];
    }
    
    /**
     * Send payment confirmation email
     */
    private function sendPaymentConfirmation($paymentId) {
        try {
            $payment = $this->getById($paymentId);
            if ($payment && $payment['email']) {
                $subject = "Payment Confirmation - " . formatCurrency($payment['amount']);
                $body = "Your payment has been processed successfully.\n\nTransaction ID: {$payment['transaction_id']}\nAmount: " . formatCurrency($payment['amount']) . "\nOrder: {$payment['order_number']}\n\nThank you for your business!";
                
                sendEmail($payment['email'], $subject, $body);
            }
        } catch (Exception $e) {
            error_log("Payment confirmation email failed: " . $e->getMessage());
        }
    }
}
?>