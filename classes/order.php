<?php
/**
 * Order Management Class
 * TechSavvyGenLtd Project
 */

class Order {
    private $db;
    private $table = TBL_ORDERS;
    private $itemsTable = TBL_ORDER_ITEMS;
    private $productsTable = TBL_PRODUCTS;
    private $servicesTable = TBL_SERVICES;
    private $customServicesTable = TBL_CUSTOM_SERVICES;
    private $usersTable = TBL_USERS;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new order from cart
     */
    public function createFromCart($userId, $orderData) {
        try {
            $this->db->beginTransaction();
            
            // Get cart items
            $cartItems = $this->getCartItems($userId);
            if (empty($cartItems)) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Cart is empty'];
            }
            
            // Calculate totals
            $totalAmount = $this->calculateCartTotal($cartItems);
            
            // Generate order number
            $orderNumber = generateOrderNumber();
            
            // Prepare order data
            $order = [
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => ORDER_STATUS_PENDING,
                'payment_status' => PAYMENT_STATUS_PENDING,
                'payment_method' => cleanInput($orderData['payment_method'] ?? ''),
                'shipping_address' => cleanInput($orderData['shipping_address'] ?? ''),
                'billing_address' => cleanInput($orderData['billing_address'] ?? ''),
                'notes' => cleanInput($orderData['notes'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Create order
            $orderId = $this->db->insert($this->table, $order);
            
            if (!$orderId) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Failed to create order'];
            }
            
            // Add order items
            foreach ($cartItems as $item) {
                $orderItem = [
                    'order_id' => $orderId,
                    'product_id' => $item['item_type'] === ITEM_TYPE_PRODUCT ? $item['product_id'] : null,
                    'service_id' => $item['item_type'] === ITEM_TYPE_SERVICE ? $item['service_id'] : null,
                    'custom_service_id' => $item['item_type'] === ITEM_TYPE_CUSTOM_SERVICE ? $item['custom_service_id'] : null,
                    'item_type' => $item['item_type'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->insert($this->itemsTable, $orderItem);
                
                // Update stock for products
                if ($item['item_type'] === ITEM_TYPE_PRODUCT) {
                    $this->updateProductStock($item['product_id'], $item['quantity']);
                }
            }
            
            // Clear cart
            $this->clearCart($userId);
            
            $this->db->commit();
            
            // Send order confirmation email
            $this->sendOrderConfirmationEmail($orderId);
            
            // Create notification
            createNotification(
                $userId,
                'تم إنشاء طلب جديد',
                'New Order Created',
                "رقم الطلب: {$orderNumber}",
                "Order Number: {$orderNumber}",
                NOTIFICATION_ORDER
            );
            
            logActivity('order_created', "Order {$orderNumber} created", $userId);
            
            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId,
                'order_number' => $orderNumber
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Order creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Order creation failed. Please try again.'];
        }
    }
    
    /**
     * Create order for custom service
     */
    public function createForCustomService($customServiceId, $orderData = []) {
        try {
            $this->db->beginTransaction();
            
            // Get custom service data
            $customService = $this->db->fetch(
                "SELECT * FROM {$this->customServicesTable} WHERE id = ? AND status = ?",
                [$customServiceId, CUSTOM_SERVICE_PENDING]
            );
            
            if (!$customService) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Custom service not found or already paid'];
            }
            
            // Check if not expired
            if (strtotime($customService['expiry_date']) < time()) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Custom service has expired'];
            }
            
            // Generate order number
            $orderNumber = generateOrderNumber();
            
            // Prepare order data
            $order = [
                'user_id' => $customService['user_id'],
                'order_number' => $orderNumber,
                'total_amount' => $customService['price'],
                'status' => ORDER_STATUS_PENDING,
                'payment_status' => PAYMENT_STATUS_PENDING,
                'payment_method' => cleanInput($orderData['payment_method'] ?? ''),
                'billing_address' => cleanInput($orderData['billing_address'] ?? ''),
                'notes' => 'Custom Service: ' . $customService['name_en'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Create order
            $orderId = $this->db->insert($this->table, $order);
            
            if (!$orderId) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Failed to create order'];
            }
            
            // Add order item
            $orderItem = [
                'order_id' => $orderId,
                'custom_service_id' => $customServiceId,
                'item_type' => ITEM_TYPE_CUSTOM_SERVICE,
                'quantity' => 1,
                'price' => $customService['price'],
                'total' => $customService['price'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert($this->itemsTable, $orderItem);
            
            $this->db->commit();
            
            logActivity('custom_service_order_created', "Order {$orderNumber} created for custom service", $customService['user_id']);
            
            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId,
                'order_number' => $orderNumber
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Custom service order creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Order creation failed. Please try again.'];
        }
    }
    
    /**
     * Update order status
     */
    public function updateStatus($orderId, $status) {
        try {
            $validStatuses = [
                ORDER_STATUS_PENDING,
                ORDER_STATUS_PROCESSING,
                ORDER_STATUS_COMPLETED,
                ORDER_STATUS_CANCELLED,
                ORDER_STATUS_REFUNDED
            ];
            
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid order status'];
            }
            
            $order = $this->getById($orderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            $updated = $this->db->update(
                $this->table,
                ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$orderId]
            );
            
            if ($updated) {
                // Handle status-specific actions
                $this->handleStatusChange($order, $status);
                
                logActivity('order_status_updated', "Order {$order['order_number']} status changed to {$status}", $_SESSION['user_id'] ?? null);
                
                return ['success' => true, 'message' => 'Order status updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update order status'];
            
        } catch (Exception $e) {
            error_log("Order status update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Status update failed. Please try again.'];
        }
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $paymentStatus) {
        try {
            $validStatuses = [
                PAYMENT_STATUS_PENDING,
                PAYMENT_STATUS_PAID,
                PAYMENT_STATUS_FAILED,
                PAYMENT_STATUS_REFUNDED
            ];
            
            if (!in_array($paymentStatus, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid payment status'];
            }
            
            $order = $this->getById($orderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            $updateData = [
                'payment_status' => $paymentStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update order status based on payment status
            if ($paymentStatus === PAYMENT_STATUS_PAID && $order['status'] === ORDER_STATUS_PENDING) {
                $updateData['status'] = ORDER_STATUS_PROCESSING;
            }
            
            $updated = $this->db->update(
                $this->table,
                $updateData,
                'id = ?',
                [$orderId]
            );
            
            if ($updated) {
                // Handle payment status specific actions
                $this->handlePaymentStatusChange($order, $paymentStatus);
                
                logActivity('order_payment_status_updated', "Order {$order['order_number']} payment status changed to {$paymentStatus}", $_SESSION['user_id'] ?? null);
                
                return ['success' => true, 'message' => 'Payment status updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update payment status'];
            
        } catch (Exception $e) {
            error_log("Payment status update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Payment status update failed. Please try again.'];
        }
    }
    
    /**
     * Get order by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT o.*, u.first_name, u.last_name, u.email
                    FROM {$this->table} o
                    LEFT JOIN {$this->usersTable} u ON o.user_id = u.id
                    WHERE o.id = ?";
            
            $order = $this->db->fetch($sql, [$id]);
            
            if ($order) {
                $order['items'] = $this->getOrderItems($id);
            }
            
            return $order;
            
        } catch (Exception $e) {
            error_log("Get order by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get order by order number
     */
    public function getByOrderNumber($orderNumber) {
        try {
            $sql = "SELECT o.*, u.first_name, u.last_name, u.email
                    FROM {$this->table} o
                    LEFT JOIN {$this->usersTable} u ON o.user_id = u.id
                    WHERE o.order_number = ?";
            
            $order = $this->db->fetch($sql, [$orderNumber]);
            
            if ($order) {
                $order['items'] = $this->getOrderItems($order['id']);
            }
            
            return $order;
            
        } catch (Exception $e) {
            error_log("Get order by number failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user orders
     */
    public function getUserOrders($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $total = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?",
                [$userId]
            );
            
            // Get orders
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $orders = $this->db->fetchAll($sql, [$userId]);
            
            // Get items for each order
            foreach ($orders as &$order) {
                $order['items'] = $this->getOrderItems($order['id']);
            }
            
            return [
                'orders' => $orders,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get user orders failed: " . $e->getMessage());
            return ['orders' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get all orders (admin function)
     */
    public function getAllOrders($page = 1, $limit = ADMIN_ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            // Status filter
            if (!empty($filters['status'])) {
                $conditions[] = "o.status = ?";
                $params[] = $filters['status'];
            }
            
            // Payment status filter
            if (!empty($filters['payment_status'])) {
                $conditions[] = "o.payment_status = ?";
                $params[] = $filters['payment_status'];
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(o.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(o.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $searchTerm = "%{$filters['search']}%";
                $conditions[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $totalQuery = "SELECT COUNT(*) FROM {$this->table} o 
                          LEFT JOIN {$this->usersTable} u ON o.user_id = u.id 
                          {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get orders
            $sql = "SELECT o.*, u.first_name, u.last_name, u.email
                    FROM {$this->table} o
                    LEFT JOIN {$this->usersTable} u ON o.user_id = u.id
                    {$whereClause}
                    ORDER BY o.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $orders = $this->db->fetchAll($sql, $params);
            
            return [
                'orders' => $orders,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all orders failed: " . $e->getMessage());
            return ['orders' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get order items
     */
    public function getOrderItems($orderId) {
        try {
            $sql = "SELECT oi.*,
                           p.name_" . CURRENT_LANGUAGE . " as product_name,
                           p.featured_image as product_image,
                           s.name_" . CURRENT_LANGUAGE . " as service_name,
                           s.featured_image as service_image,
                           cs.name_" . CURRENT_LANGUAGE . " as custom_service_name
                    FROM {$this->itemsTable} oi
                    LEFT JOIN {$this->productsTable} p ON oi.product_id = p.id
                    LEFT JOIN {$this->servicesTable} s ON oi.service_id = s.id
                    LEFT JOIN {$this->customServicesTable} cs ON oi.custom_service_id = cs.id
                    WHERE oi.order_id = ?
                    ORDER BY oi.created_at ASC";
            
            return $this->db->fetchAll($sql, [$orderId]);
            
        } catch (Exception $e) {
            error_log("Get order items failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate order statistics
     */
    public function getOrderStatistics($dateFrom = null, $dateTo = null) {
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
            
            // Total orders and revenue
            $sql = "SELECT 
                        COUNT(*) as total_orders,
                        COALESCE(SUM(total_amount), 0) as total_revenue,
                        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_revenue,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
                    FROM {$this->table} {$whereClause}";
            
            return $this->db->fetch($sql, $params);
            
        } catch (Exception $e) {
            error_log("Get order statistics failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder($orderId, $reason = '') {
        try {
            $order = $this->getById($orderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            if ($order['status'] === ORDER_STATUS_COMPLETED) {
                return ['success' => false, 'message' => 'Cannot cancel completed order'];
            }
            
            $this->db->beginTransaction();
            
            // Update order status
            $this->db->update(
                $this->table,
                [
                    'status' => ORDER_STATUS_CANCELLED,
                    'notes' => $order['notes'] . "\nCancellation reason: " . $reason,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$orderId]
            );
            
            // Restore stock for cancelled items
            foreach ($order['items'] as $item) {
                if ($item['item_type'] === ITEM_TYPE_PRODUCT) {
                    $this->db->query(
                        "UPDATE {$this->productsTable} SET stock = stock + ? WHERE id = ?",
                        [$item['quantity'], $item['product_id']]
                    );
                }
            }
            
            $this->db->commit();
            
            // Send cancellation notification
            createNotification(
                $order['user_id'],
                'تم إلغاء الطلب',
                'Order Cancelled',
                "تم إلغاء الطلب رقم: {$order['order_number']}",
                "Order cancelled: {$order['order_number']}",
                NOTIFICATION_ORDER
            );
            
            logActivity('order_cancelled', "Order {$order['order_number']} cancelled", $_SESSION['user_id'] ?? null);
            
            return ['success' => true, 'message' => 'Order cancelled successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Order cancellation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Order cancellation failed. Please try again.'];
        }
    }
    
    /**
     * Helper methods
     */
    
    private function getCartItems($userId) {
        try {
            $sql = "SELECT ci.*,
                           p.name_" . CURRENT_LANGUAGE . " as product_name,
                           p.price as product_price,
                           p.sale_price as product_sale_price,
                           p.stock as product_stock,
                           s.name_" . CURRENT_LANGUAGE . " as service_name,
                           s.price as service_price,
                           cs.name_" . CURRENT_LANGUAGE . " as custom_service_name,
                           cs.price as custom_service_price
                    FROM " . TBL_CART_ITEMS . " ci
                    LEFT JOIN {$this->productsTable} p ON ci.product_id = p.id
                    LEFT JOIN {$this->servicesTable} s ON ci.service_id = s.id
                    LEFT JOIN {$this->customServicesTable} cs ON ci.custom_service_id = cs.id
                    WHERE ci.user_id = ?
                    ORDER BY ci.created_at ASC";
            
            $items = $this->db->fetchAll($sql, [$userId]);
            
            // Calculate prices
            foreach ($items as &$item) {
                if ($item['item_type'] === ITEM_TYPE_PRODUCT) {
                    $item['price'] = $item['product_sale_price'] ?? $item['product_price'];
                } elseif ($item['item_type'] === ITEM_TYPE_SERVICE) {
                    $item['price'] = $item['service_price'];
                } elseif ($item['item_type'] === ITEM_TYPE_CUSTOM_SERVICE) {
                    $item['price'] = $item['custom_service_price'];
                }
            }
            
            return $items;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function calculateCartTotal($cartItems) {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
    
    private function clearCart($userId) {
        $this->db->delete(TBL_CART_ITEMS, 'user_id = ?', [$userId]);
    }
    
    private function updateProductStock($productId, $quantity) {
        $this->db->query(
            "UPDATE {$this->productsTable} SET stock = stock - ? WHERE id = ? AND stock >= ?",
            [$quantity, $productId, $quantity]
        );
    }
    
    private function handleStatusChange($order, $newStatus) {
        switch ($newStatus) {
            case ORDER_STATUS_COMPLETED:
                // Send completion notification
                createNotification(
                    $order['user_id'],
                    'تم إكمال الطلب',
                    'Order Completed',
                    "تم إكمال الطلب رقم: {$order['order_number']}",
                    "Order completed: {$order['order_number']}",
                    NOTIFICATION_ORDER
                );
                break;
            
            case ORDER_STATUS_PROCESSING:
                // Send processing notification
                createNotification(
                    $order['user_id'],
                    'قيد المعالجة',
                    'Order Processing',
                    "الطلب رقم {$order['order_number']} قيد المعالجة",
                    "Order {$order['order_number']} is being processed",
                    NOTIFICATION_ORDER
                );
                break;
        }
    }
    
    private function handlePaymentStatusChange($order, $newPaymentStatus) {
        switch ($newPaymentStatus) {
            case PAYMENT_STATUS_PAID:
                // Send payment confirmation
                createNotification(
                    $order['user_id'],
                    'تم تأكيد الدفع',
                    'Payment Confirmed',
                    "تم تأكيد دفع الطلب رقم: {$order['order_number']}",
                    "Payment confirmed for order: {$order['order_number']}",
                    NOTIFICATION_PAYMENT
                );
                break;
            
            case PAYMENT_STATUS_FAILED:
                // Send payment failure notification
                createNotification(
                    $order['user_id'],
                    'فشل في الدفع',
                    'Payment Failed',
                    "فشل دفع الطلب رقم: {$order['order_number']}",
                    "Payment failed for order: {$order['order_number']}",
                    NOTIFICATION_ERROR
                );
                break;
        }
    }
    
    private function sendOrderConfirmationEmail($orderId) {
        try {
            $order = $this->getById($orderId);
            if ($order && $order['email']) {
                $subject = "Order Confirmation - {$order['order_number']}";
                $body = "Thank you for your order!\n\nOrder Number: {$order['order_number']}\nTotal: " . formatCurrency($order['total_amount']) . "\n\nWe will process your order shortly.";
                
                sendEmail($order['email'], $subject, $body);
            }
        } catch (Exception $e) {
            error_log("Order confirmation email failed: " . $e->getMessage());
        }
    }
}
?>