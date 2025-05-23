<?php
/**
 * Invoice Management Class
 * TechSavvyGenLtd Project
 */

class Invoice {
    private $db;
    private $table = TBL_INVOICES;
    private $ordersTable = TBL_ORDERS;
    private $orderItemsTable = TBL_ORDER_ITEMS;
    private $usersTable = TBL_USERS;
    private $settingsTable = TBL_SETTINGS;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create invoice from order
     */
    public function createFromOrder($orderId) {
        try {
            // Get order data
            $order = $this->db->fetch(
                "SELECT o.*, u.first_name, u.last_name, u.email, u.address, u.city, u.country, u.postal_code
                 FROM {$this->ordersTable} o
                 LEFT JOIN {$this->usersTable} u ON o.user_id = u.id
                 WHERE o.id = ?",
                [$orderId]
            );
            
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            // Check if invoice already exists
            $existingInvoice = $this->db->fetch(
                "SELECT id FROM {$this->table} WHERE order_id = ?",
                [$orderId]
            );
            
            if ($existingInvoice) {
                return ['success' => false, 'message' => 'Invoice already exists for this order'];
            }
            
            // Calculate tax and totals
            $taxRate = (float)getSetting('tax_rate', '0.00') / 100;
            $subtotal = $order['total_amount'];
            $taxAmount = $subtotal * $taxRate;
            $totalAmount = $subtotal + $taxAmount;
            
            // Generate invoice number
            $invoiceNumber = generateInvoiceNumber();
            
            // Prepare invoice data
            $invoiceData = [
                'order_id' => $orderId,
                'user_id' => $order['user_id'],
                'invoice_number' => $invoiceNumber,
                'amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => $order['payment_status'] === PAYMENT_STATUS_PAID ? INVOICE_STATUS_PAID : INVOICE_STATUS_UNPAID,
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $invoiceId = $this->db->insert($this->table, $invoiceData);
            
            if ($invoiceId) {
                // Generate PDF
                $pdfResult = $this->generatePDF($invoiceId);
                
                if ($pdfResult['success']) {
                    // Update invoice with PDF path
                    $this->db->update(
                        $this->table,
                        ['pdf_path' => $pdfResult['pdf_path']],
                        'id = ?',
                        [$invoiceId]
                    );
                }
                
                logActivity('invoice_created', "Invoice {$invoiceNumber} created for order {$order['order_number']}", $_SESSION['user_id'] ?? null);
                
                return [
                    'success' => true,
                    'message' => 'Invoice created successfully',
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoiceNumber
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create invoice'];
            
        } catch (Exception $e) {
            error_log("Invoice creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Invoice creation failed. Please try again.'];
        }
    }
    
    /**
     * Update invoice status
     */
    public function updateStatus($invoiceId, $status) {
        try {
            $validStatuses = [INVOICE_STATUS_PAID, INVOICE_STATUS_UNPAID, INVOICE_STATUS_CANCELLED];
            
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid invoice status'];
            }
            
            $invoice = $this->getById($invoiceId);
            if (!$invoice) {
                return ['success' => false, 'message' => 'Invoice not found'];
            }
            
            $updated = $this->db->update(
                $this->table,
                ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$invoiceId]
            );
            
            if ($updated) {
                logActivity('invoice_status_updated', "Invoice {$invoice['invoice_number']} status changed to {$status}", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Invoice status updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update invoice status'];
            
        } catch (Exception $e) {
            error_log("Invoice status update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Status update failed. Please try again.'];
        }
    }
    
    /**
     * Get invoice by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT i.*, o.order_number, o.created_at as order_date,
                           u.first_name, u.last_name, u.email, u.address, u.city, u.country, u.postal_code
                    FROM {$this->table} i
                    LEFT JOIN {$this->ordersTable} o ON i.order_id = o.id
                    LEFT JOIN {$this->usersTable} u ON i.user_id = u.id
                    WHERE i.id = ?";
            
            $invoice = $this->db->fetch($sql, [$id]);
            
            if ($invoice) {
                $invoice['items'] = $this->getInvoiceItems($invoice['order_id']);
            }
            
            return $invoice;
            
        } catch (Exception $e) {
            error_log("Get invoice by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get invoice by invoice number
     */
    public function getByInvoiceNumber($invoiceNumber) {
        try {
            $sql = "SELECT i.*, o.order_number, o.created_at as order_date,
                           u.first_name, u.last_name, u.email, u.address, u.city, u.country, u.postal_code
                    FROM {$this->table} i
                    LEFT JOIN {$this->ordersTable} o ON i.order_id = o.id
                    LEFT JOIN {$this->usersTable} u ON i.user_id = u.id
                    WHERE i.invoice_number = ?";
            
            $invoice = $this->db->fetch($sql, [$invoiceNumber]);
            
            if ($invoice) {
                $invoice['items'] = $this->getInvoiceItems($invoice['order_id']);
            }
            
            return $invoice;
            
        } catch (Exception $e) {
            error_log("Get invoice by number failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user invoices
     */
    public function getUserInvoices($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $total = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?",
                [$userId]
            );
            
            // Get invoices
            $sql = "SELECT i.*, o.order_number
                    FROM {$this->table} i
                    LEFT JOIN {$this->ordersTable} o ON i.order_id = o.id
                    WHERE i.user_id = ?
                    ORDER BY i.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $invoices = $this->db->fetchAll($sql, [$userId]);
            
            return [
                'invoices' => $invoices,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get user invoices failed: " . $e->getMessage());
            return ['invoices' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get all invoices (admin function)
     */
    public function getAllInvoices($page = 1, $limit = ADMIN_ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            // Status filter
            if (!empty($filters['status'])) {
                $conditions[] = "i.status = ?";
                $params[] = $filters['status'];
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(i.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(i.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $searchTerm = "%{$filters['search']}%";
                $conditions[] = "(i.invoice_number LIKE ? OR o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $totalQuery = "SELECT COUNT(*) FROM {$this->table} i 
                          LEFT JOIN {$this->ordersTable} o ON i.order_id = o.id
                          LEFT JOIN {$this->usersTable} u ON i.user_id = u.id
                          {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get invoices
            $sql = "SELECT i.*, o.order_number, u.first_name, u.last_name, u.email
                    FROM {$this->table} i
                    LEFT JOIN {$this->ordersTable} o ON i.order_id = o.id
                    LEFT JOIN {$this->usersTable} u ON i.user_id = u.id
                    {$whereClause}
                    ORDER BY i.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $invoices = $this->db->fetchAll($sql, $params);
            
            return [
                'invoices' => $invoices,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all invoices failed: " . $e->getMessage());
            return ['invoices' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Generate PDF invoice
     */
    public function generatePDF($invoiceId) {
        try {
            $invoice = $this->getById($invoiceId);
            if (!$invoice) {
                return ['success' => false, 'message' => 'Invoice not found'];
            }
            
            // Create upload directory if it doesn't exist
            $uploadPath = UPLOADS_PATH . '/' . UPLOAD_PATH_INVOICES;
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $filename = 'invoice_' . $invoice['invoice_number'] . '.pdf';
            $filePath = $uploadPath . '/' . $filename;
            
            // Generate PDF content
            $htmlContent = $this->generateInvoiceHTML($invoice);
            
            // Use TCPDF or similar library to generate PDF
            // This is a simplified implementation
            if ($this->createPDFFromHTML($htmlContent, $filePath)) {
                return [
                    'success' => true,
                    'pdf_path' => UPLOAD_PATH_INVOICES . '/' . $filename,
                    'filename' => $filename
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to generate PDF'];
            
        } catch (Exception $e) {
            error_log("PDF generation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'PDF generation failed'];
        }
    }
    
    /**
     * Send invoice by email
     */
    public function sendByEmail($invoiceId, $email = null, $message = '') {
        try {
            $invoice = $this->getById($invoiceId);
            if (!$invoice) {
                return ['success' => false, 'message' => 'Invoice not found'];
            }
            
            $recipientEmail = $email ?? $invoice['email'];
            if (!$recipientEmail) {
                return ['success' => false, 'message' => 'No email address provided'];
            }
            
            // Generate PDF if not exists
            if (!$invoice['pdf_path'] || !file_exists(UPLOADS_PATH . '/' . $invoice['pdf_path'])) {
                $pdfResult = $this->generatePDF($invoiceId);
                if (!$pdfResult['success']) {
                    return $pdfResult;
                }
                $invoice['pdf_path'] = $pdfResult['pdf_path'];
            }
            
            $subject = "Invoice {$invoice['invoice_number']} - " . SITE_NAME;
            $body = $this->generateEmailBody($invoice, $message);
            $attachment = UPLOADS_PATH . '/' . $invoice['pdf_path'];
            
            // Send email with attachment
            $sent = $this->sendEmailWithAttachment($recipientEmail, $subject, $body, $attachment);
            
            if ($sent) {
                logActivity('invoice_sent', "Invoice {$invoice['invoice_number']} sent to {$recipientEmail}", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Invoice sent successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to send invoice email'];
            
        } catch (Exception $e) {
            error_log("Invoice email sending failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed'];
        }
    }
    
    /**
     * Get invoice statistics
     */
    public function getInvoiceStatistics($dateFrom = null, $dateTo = null) {
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
                        COUNT(*) as total_invoices,
                        COALESCE(SUM(total_amount), 0) as total_amount,
                        COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount,
                        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
                        COUNT(CASE WHEN status = 'unpaid' THEN 1 END) as unpaid_invoices,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_invoices,
                        COALESCE(AVG(total_amount), 0) as average_amount
                    FROM {$this->table} {$whereClause}";
            
            return $this->db->fetch($sql, $params);
            
        } catch (Exception $e) {
            error_log("Get invoice statistics failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices() {
        try {
            $sql = "SELECT i.*, o.order_number, u.first_name, u.last_name, u.email
                    FROM {$this->table} i
                    LEFT JOIN {$this->ordersTable} o ON i.order_id = o.id
                    LEFT JOIN {$this->usersTable} u ON i.user_id = u.id
                    WHERE i.status = ? AND i.due_date < CURDATE()
                    ORDER BY i.due_date ASC";
            
            return $this->db->fetchAll($sql, [INVOICE_STATUS_UNPAID]);
            
        } catch (Exception $e) {
            error_log("Get overdue invoices failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Helper methods
     */
    
    private function getInvoiceItems($orderId) {
        try {
            $sql = "SELECT oi.*,
                           p.name_" . CURRENT_LANGUAGE . " as product_name,
                           s.name_" . CURRENT_LANGUAGE . " as service_name,
                           cs.name_" . CURRENT_LANGUAGE . " as custom_service_name
                    FROM {$this->orderItemsTable} oi
                    LEFT JOIN " . TBL_PRODUCTS . " p ON oi.product_id = p.id
                    LEFT JOIN " . TBL_SERVICES . " s ON oi.service_id = s.id
                    LEFT JOIN " . TBL_CUSTOM_SERVICES . " cs ON oi.custom_service_id = cs.id
                    WHERE oi.order_id = ?
                    ORDER BY oi.created_at ASC";
            
            return $this->db->fetchAll($sql, [$orderId]);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function generateInvoiceHTML($invoice) {
        $companyName = getSetting('site_name', SITE_NAME);
        $companyEmail = getSetting('site_email', SITE_EMAIL);
        $companyPhone = getSetting('site_phone', SITE_PHONE);
        $companyAddress = getSetting('site_address', '');
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Invoice ' . $invoice['invoice_number'] . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .company-info { margin-bottom: 20px; }
                .invoice-info { margin-bottom: 20px; }
                .customer-info { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; }
                .total-row { font-weight: bold; }
                .text-right { text-align: right; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>INVOICE</h1>
                <h2>' . $companyName . '</h2>
            </div>
            
            <div class="company-info">
                <strong>From:</strong><br>
                ' . $companyName . '<br>
                ' . $companyEmail . '<br>
                ' . $companyPhone . '<br>
                ' . $companyAddress . '
            </div>
            
            <div class="customer-info">
                <strong>To:</strong><br>
                ' . $invoice['first_name'] . ' ' . $invoice['last_name'] . '<br>
                ' . $invoice['email'] . '<br>
                ' . $invoice['address'] . '<br>
                ' . $invoice['city'] . ', ' . $invoice['country'] . ' ' . $invoice['postal_code'] . '
            </div>
            
            <div class="invoice-info">
                <table>
                    <tr>
                        <td><strong>Invoice Number:</strong></td>
                        <td>' . $invoice['invoice_number'] . '</td>
                        <td><strong>Order Number:</strong></td>
                        <td>' . $invoice['order_number'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>Invoice Date:</strong></td>
                        <td>' . formatDate($invoice['created_at']) . '</td>
                        <td><strong>Due Date:</strong></td>
                        <td>' . formatDate($invoice['due_date']) . '</td>
                    </tr>
                </table>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($invoice['items'] as $item) {
            $itemName = $item['product_name'] ?? $item['service_name'] ?? $item['custom_service_name'] ?? 'Unknown Item';
            $html .= '
                    <tr>
                        <td>' . $itemName . '</td>
                        <td>' . $item['quantity'] . '</td>
                        <td>' . formatCurrency($item['price']) . '</td>
                        <td>' . formatCurrency($item['total']) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                        <td><strong>' . formatCurrency($invoice['amount']) . '</strong></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Tax:</strong></td>
                        <td><strong>' . formatCurrency($invoice['tax_amount']) . '</strong></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>Total:</strong></td>
                        <td><strong>' . formatCurrency($invoice['total_amount']) . '</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 40px;">
                <p><strong>Status:</strong> ' . ucfirst($invoice['status']) . '</p>
                <p>Thank you for your business!</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function createPDFFromHTML($html, $filePath) {
        // This is a placeholder implementation
        // In production, use TCPDF, FPDF, or wkhtmltopdf
        
        try {
            // Simple HTML to PDF conversion (basic implementation)
            // For production, integrate with proper PDF library
            file_put_contents($filePath, $html);
            return true;
        } catch (Exception $e) {
            error_log("PDF creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function generateEmailBody($invoice, $customMessage = '') {
        $body = "Dear {$invoice['first_name']} {$invoice['last_name']},\n\n";
        
        if ($customMessage) {
            $body .= $customMessage . "\n\n";
        }
        
        $body .= "Please find attached your invoice.\n\n";
        $body .= "Invoice Details:\n";
        $body .= "Invoice Number: {$invoice['invoice_number']}\n";
        $body .= "Order Number: {$invoice['order_number']}\n";
        $body .= "Amount: " . formatCurrency($invoice['total_amount']) . "\n";
        $body .= "Due Date: " . formatDate($invoice['due_date']) . "\n";
        $body .= "Status: " . ucfirst($invoice['status']) . "\n\n";
        $body .= "Thank you for your business!\n\n";
        $body .= "Best regards,\n";
        $body .= SITE_NAME;
        
        return $body;
    }
    
    private function sendEmailWithAttachment($to, $subject, $body, $attachment) {
        // Basic email with attachment implementation
        // In production, use PHPMailer or similar library
        
        try {
            $headers = [
                'From: ' . SITE_EMAIL,
                'Reply-To: ' . SITE_EMAIL,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // For now, send without attachment (basic implementation)
            // In production, implement proper attachment handling
            return mail($to, $subject, $body, implode("\r\n", $headers));
            
        } catch (Exception $e) {
            error_log("Email with attachment failed: " . $e->getMessage());
            return false;
        }
    }
}
?>