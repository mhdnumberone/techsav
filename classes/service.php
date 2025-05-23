<?php
/**
 * Service Management Class
 * TechSavvyGenLtd Project
 */

class Service {
    private $db;
    private $table = TBL_SERVICES;
    private $customServicesTable = TBL_CUSTOM_SERVICES;
    private $categoriesTable = TBL_CATEGORIES;
    private $reviewsTable = TBL_REVIEWS;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new service
     */
    public function create($data) {
        // Validate required fields
        $required = ['name_ar', 'name_en', 'category_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field {$field} is required"];
            }
        }
        
        // Validate price if has_fixed_price is true
        if (!empty($data['has_fixed_price']) && empty($data['price'])) {
            return ['success' => false, 'message' => 'Price is required for fixed price services'];
        }
        
        try {
            // Generate slug
            $slug = $this->generateUniqueSlug($data['name_en']);
            
            // Prepare service data
            $serviceData = [
                'category_id' => (int)$data['category_id'],
                'name_ar' => cleanInput($data['name_ar']),
                'name_en' => cleanInput($data['name_en']),
                'slug' => $slug,
                'description_ar' => cleanInput($data['description_ar'] ?? ''),
                'description_en' => cleanInput($data['description_en'] ?? ''),
                'short_description_ar' => cleanInput($data['short_description_ar'] ?? ''),
                'short_description_en' => cleanInput($data['short_description_en'] ?? ''),
                'price' => !empty($data['price']) ? (float)$data['price'] : null,
                'has_fixed_price' => !empty($data['has_fixed_price']) ? 1 : 0,
                'featured_image' => cleanInput($data['featured_image'] ?? ''),
                'status' => $data['status'] ?? STATUS_ACTIVE,
                'is_featured' => !empty($data['is_featured']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $serviceId = $this->db->insert($this->table, $serviceData);
            
            if ($serviceId) {
                logActivity('service_created', "Service '{$serviceData['name_en']}' created", $_SESSION['user_id'] ?? null);
                return [
                    'success' => true,
                    'message' => 'Service created successfully',
                    'service_id' => $serviceId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create service'];
            
        } catch (Exception $e) {
            error_log("Service creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Service creation failed. Please try again.'];
        }
    }
    
    /**
     * Update service
     */
    public function update($id, $data) {
        try {
            // Check if service exists
            if (!$this->exists($id)) {
                return ['success' => false, 'message' => 'Service not found'];
            }
            
            $allowedFields = [
                'category_id', 'name_ar', 'name_en', 'description_ar', 'description_en',
                'short_description_ar', 'short_description_en', 'price', 'has_fixed_price',
                'featured_image', 'status', 'is_featured'
            ];
            
            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if (in_array($field, ['name_ar', 'name_en', 'description_ar', 'description_en', 'short_description_ar', 'short_description_en', 'featured_image'])) {
                        $updateData[$field] = cleanInput($data[$field]);
                    } elseif ($field === 'category_id') {
                        $updateData[$field] = (int)$data[$field];
                    } elseif ($field === 'price') {
                        $updateData[$field] = !empty($data[$field]) ? (float)$data[$field] : null;
                    } elseif (in_array($field, ['has_fixed_price', 'is_featured'])) {
                        $updateData[$field] = !empty($data[$field]) ? 1 : 0;
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }
            
            // Update slug if name changed
            if (isset($data['name_en'])) {
                $updateData['slug'] = $this->generateUniqueSlug($data['name_en'], $id);
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $updated = $this->db->update(
                $this->table,
                $updateData,
                'id = ?',
                [$id]
            );
            
            if ($updated) {
                logActivity('service_updated', "Service ID {$id} updated", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Service updated successfully'];
            }
            
            return ['success' => false, 'message' => 'No changes made'];
            
        } catch (Exception $e) {
            error_log("Service update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Service update failed. Please try again.'];
        }
    }
    
    /**
     * Delete service
     */
    public function delete($id) {
        try {
            $service = $this->getById($id);
            if (!$service) {
                return ['success' => false, 'message' => 'Service not found'];
            }
            
            $this->db->beginTransaction();
            
            // Delete featured image
            if ($service['featured_image']) {
                deleteFile(UPLOAD_PATH_SERVICES . '/' . $service['featured_image']);
            }
            
            // Delete service
            $deleted = $this->db->delete($this->table, 'id = ?', [$id]);
            
            if ($deleted) {
                $this->db->commit();
                logActivity('service_deleted', "Service '{$service['name_en']}' deleted", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Service deleted successfully'];
            }
            
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to delete service'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Service deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Service deletion failed. Please try again.'];
        }
    }
    
    /**
     * Get service by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT s.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} s
                    LEFT JOIN {$this->categoriesTable} c ON s.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON s.id = r.service_id AND r.status = 'approved'
                    WHERE s.id = ?
                    GROUP BY s.id";
            
            return $this->db->fetch($sql, [$id]);
            
        } catch (Exception $e) {
            error_log("Get service by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get service by slug
     */
    public function getBySlug($slug) {
        try {
            $sql = "SELECT s.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} s
                    LEFT JOIN {$this->categoriesTable} c ON s.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON s.id = r.service_id AND r.status = 'approved'
                    WHERE s.slug = ? AND s.status = 'active'
                    GROUP BY s.id";
            
            return $this->db->fetch($sql, [$slug]);
            
        } catch (Exception $e) {
            error_log("Get service by slug failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all services with filtering and pagination
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = ['s.status = ?'];
            $params = [STATUS_ACTIVE];
            
            // Category filter
            if (!empty($filters['category_id'])) {
                $conditions[] = "s.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $searchTerm = "%{$filters['search']}%";
                $conditions[] = "(s.name_ar LIKE ? OR s.name_en LIKE ? OR s.description_ar LIKE ? OR s.description_en LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Price type filter
            if (isset($filters['has_fixed_price'])) {
                $conditions[] = "s.has_fixed_price = ?";
                $params[] = $filters['has_fixed_price'] ? 1 : 0;
            }
            
            // Featured filter
            if (!empty($filters['featured'])) {
                $conditions[] = "s.is_featured = 1";
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            
            // Order by
            $orderBy = 'ORDER BY ';
            switch ($filters['sort'] ?? 'newest') {
                case 'price_low':
                    $orderBy .= 'COALESCE(s.price, 999999) ASC';
                    break;
                case 'price_high':
                    $orderBy .= 'COALESCE(s.price, 0) DESC';
                    break;
                case 'name':
                    $orderBy .= 's.name_' . CURRENT_LANGUAGE . ' ASC';
                    break;
                case 'rating':
                    $orderBy .= 'average_rating DESC, s.created_at DESC';
                    break;
                default:
                    $orderBy .= 's.created_at DESC';
            }
            
            // Get total count
            $totalQuery = "SELECT COUNT(DISTINCT s.id) FROM {$this->table} s {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get services
            $sql = "SELECT s.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} s
                    LEFT JOIN {$this->categoriesTable} c ON s.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON s.id = r.service_id AND r.status = 'approved'
                    {$whereClause}
                    GROUP BY s.id
                    {$orderBy}
                    LIMIT {$limit} OFFSET {$offset}";
            
            $services = $this->db->fetchAll($sql, $params);
            
            return [
                'services' => $services,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all services failed: " . $e->getMessage());
            return ['services' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get featured services
     */
    public function getFeaturedServices($limit = 6) {
        try {
            $sql = "SELECT s.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} s
                    LEFT JOIN {$this->categoriesTable} c ON s.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON s.id = r.service_id AND r.status = 'approved'
                    WHERE s.status = 'active' AND s.is_featured = 1
                    GROUP BY s.id
                    ORDER BY s.created_at DESC
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql);
            
        } catch (Exception $e) {
            error_log("Get featured services failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get related services
     */
    public function getRelatedServices($serviceId, $categoryId, $limit = 4) {
        try {
            $sql = "SELECT s.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} s
                    LEFT JOIN {$this->categoriesTable} c ON s.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON s.id = r.service_id AND r.status = 'approved'
                    WHERE s.category_id = ? AND s.id != ? AND s.status = 'active'
                    GROUP BY s.id
                    ORDER BY RAND()
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [$categoryId, $serviceId]);
            
        } catch (Exception $e) {
            error_log("Get related services failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create custom service
     */
    public function createCustomService($data) {
        // Validate required fields
        $required = ['service_id', 'user_id', 'name_ar', 'name_en', 'price'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field {$field} is required"];
            }
        }
        
        // Validate price
        if (!is_numeric($data['price']) || $data['price'] <= 0) {
            return ['success' => false, 'message' => 'Invalid price'];
        }
        
        try {
            // Generate unique link
            $uniqueLink = generateCustomServiceLink();
            
            // Set expiry date (default 30 days)
            $expiryDays = $data['expiry_days'] ?? 30;
            $expiryDate = date('Y-m-d H:i:s', time() + ($expiryDays * 24 * 60 * 60));
            
            // Prepare custom service data
            $customServiceData = [
                'service_id' => (int)$data['service_id'],
                'user_id' => (int)$data['user_id'],
                'name_ar' => cleanInput($data['name_ar']),
                'name_en' => cleanInput($data['name_en']),
                'description_ar' => cleanInput($data['description_ar'] ?? ''),
                'description_en' => cleanInput($data['description_en'] ?? ''),
                'price' => (float)$data['price'],
                'unique_link' => $uniqueLink,
                'expiry_date' => $expiryDate,
                'status' => CUSTOM_SERVICE_PENDING,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $customServiceId = $this->db->insert($this->customServicesTable, $customServiceData);
            
            if ($customServiceId) {
                // Send email to customer with payment link
                $this->sendCustomServiceEmail($data['user_id'], $uniqueLink, $customServiceData);
                
                logActivity('custom_service_created', "Custom service created for user ID {$data['user_id']}", $_SESSION['user_id'] ?? null);
                
                return [
                    'success' => true,
                    'message' => 'Custom service created successfully',
                    'custom_service_id' => $customServiceId,
                    'unique_link' => $uniqueLink
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create custom service'];
            
        } catch (Exception $e) {
            error_log("Custom service creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Custom service creation failed. Please try again.'];
        }
    }
    
    /**
     * Get custom service by unique link
     */
    public function getCustomServiceByLink($uniqueLink) {
        try {
            $sql = "SELECT cs.*, s.name_" . CURRENT_LANGUAGE . " as base_service_name,
                           u.first_name, u.last_name, u.email
                    FROM {$this->customServicesTable} cs
                    LEFT JOIN {$this->table} s ON cs.service_id = s.id
                    LEFT JOIN " . TBL_USERS . " u ON cs.user_id = u.id
                    WHERE cs.unique_link = ?";
            
            return $this->db->fetch($sql, [$uniqueLink]);
            
        } catch (Exception $e) {
            error_log("Get custom service by link failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update custom service status
     */
    public function updateCustomServiceStatus($id, $status) {
        try {
            $validStatuses = [CUSTOM_SERVICE_PENDING, CUSTOM_SERVICE_PAID, CUSTOM_SERVICE_EXPIRED, CUSTOM_SERVICE_CANCELLED];
            
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $updated = $this->db->update(
                $this->customServicesTable,
                ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$id]
            );
            
            if ($updated) {
                logActivity('custom_service_status_updated', "Custom service ID {$id} status changed to {$status}", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Custom service status updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update custom service status'];
            
        } catch (Exception $e) {
            error_log("Custom service status update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Status update failed. Please try again.'];
        }
    }
    
    /**
     * Upload service image
     */
    public function uploadImage($serviceId, $file) {
        $upload = uploadFile($file, UPLOAD_PATH_SERVICES);
        
        if ($upload['success']) {
            try {
                // Update featured image
                $updated = $this->db->update(
                    $this->table,
                    ['featured_image' => $upload['filename']],
                    'id = ?',
                    [$serviceId]
                );
                
                if ($updated) {
                    return $upload;
                } else {
                    deleteFile($upload['path']);
                    return ['success' => false, 'message' => 'Failed to update service image'];
                }
                
            } catch (Exception $e) {
                deleteFile($upload['path']);
                error_log("Service image upload failed: " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to save image information'];
            }
        }
        
        return $upload;
    }
    
    /**
     * Check if service exists
     */
    public function exists($id) {
        return $this->db->exists($this->table, 'id = ?', [$id]);
    }
    
    /**
     * Check if custom service exists
     */
    public function customServiceExists($id) {
        return $this->db->exists($this->customServicesTable, 'id = ?', [$id]);
    }
    
    /**
     * Expire old custom services
     */
    public function expireOldCustomServices() {
        try {
            $expired = $this->db->update(
                $this->customServicesTable,
                ['status' => CUSTOM_SERVICE_EXPIRED],
                'expiry_date < NOW() AND status = ?',
                [CUSTOM_SERVICE_PENDING]
            );
            
            if ($expired > 0) {
                logActivity('custom_services_expired', "{$expired} custom services expired automatically");
            }
            
            return $expired;
            
        } catch (Exception $e) {
            error_log("Custom services expiration failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Generate unique slug
     */
    private function generateUniqueSlug($name, $excludeId = null) {
        $slug = createSlug($name);
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $condition = "slug = ?";
            $params = [$slug];
            
            if ($excludeId) {
                $condition .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            if (!$this->db->exists($this->table, $condition, $params)) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Send custom service email
     */
    private function sendCustomServiceEmail($userId, $uniqueLink, $serviceData) {
        try {
            $user = $this->db->fetch("SELECT email, first_name FROM " . TBL_USERS . " WHERE id = ?", [$userId]);
            
            if ($user) {
                $paymentUrl = SITE_URL . "/custom-service.php?link={$uniqueLink}";
                $subject = "Custom Service Ready for Payment";
                $body = "Dear {$user['first_name']},\n\nYour custom service '{$serviceData['name_en']}' is ready for payment.\n\nPrice: " . formatCurrency($serviceData['price']) . "\n\nPayment Link: {$paymentUrl}\n\nThank you!";
                
                sendEmail($user['email'], $subject, $body);
            }
        } catch (Exception $e) {
            error_log("Custom service email failed: " . $e->getMessage());
        }
    }
}
?>