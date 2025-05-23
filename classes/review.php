<?php
/**
 * Review Management Class
 * TechSavvyGenLtd Project
 */

class Review {
    private $db;
    private $table = TBL_REVIEWS;
    private $usersTable = TBL_USERS;
    private $productsTable = TBL_PRODUCTS;
    private $servicesTable = TBL_SERVICES;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new review
     */
    public function create($data) {
        // Validate required fields
        $required = ['user_id', 'rating', 'comment'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field {$field} is required"];
            }
        }
        
        // Validate rating
        if (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
        }
        
        // Check if either product_id or service_id is provided
        if (empty($data['product_id']) && empty($data['service_id'])) {
            return ['success' => false, 'message' => 'Either product_id or service_id is required'];
        }
        
        // Check if user has already reviewed this item
        $existingReview = $this->getUserReview($data['user_id'], $data['product_id'] ?? null, $data['service_id'] ?? null);
        if ($existingReview) {
            return ['success' => false, 'message' => 'You have already reviewed this item'];
        }
        
        try {
            // Prepare review data
            $reviewData = [
                'user_id' => (int)$data['user_id'],
                'product_id' => !empty($data['product_id']) ? (int)$data['product_id'] : null,
                'service_id' => !empty($data['service_id']) ? (int)$data['service_id'] : null,
                'rating' => (int)$data['rating'],
                'title' => cleanInput($data['title'] ?? ''),
                'comment' => cleanInput($data['comment']),
                'status' => REVIEW_STATUS_PENDING,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $reviewId = $this->db->insert($this->table, $reviewData);
            
            if ($reviewId) {
                // Create notification for admin
                createNotification(
                    null, // Admin notification
                    'مراجعة جديدة',
                    'New Review',
                    'تم إضافة مراجعة جديدة تحتاج للموافقة',
                    'A new review has been submitted and needs approval',
                    NOTIFICATION_INFO
                );
                
                logActivity('review_created', "Review created for " . ($data['product_id'] ? "product ID {$data['product_id']}" : "service ID {$data['service_id']}"), $data['user_id']);
                
                return [
                    'success' => true,
                    'message' => 'Review submitted successfully and is pending approval',
                    'review_id' => $reviewId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create review'];
            
        } catch (Exception $e) {
            error_log("Review creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Review creation failed. Please try again.'];
        }
    }
    
    /**
     * Update review
     */
    public function update($id, $data, $userId = null) {
        try {
            $review = $this->getById($id);
            if (!$review) {
                return ['success' => false, 'message' => 'Review not found'];
            }
            
            // Check if user owns this review (unless admin)
            if ($userId && $review['user_id'] != $userId && !isStaff()) {
                return ['success' => false, 'message' => 'You can only edit your own reviews'];
            }
            
            $allowedFields = ['rating', 'title', 'comment', 'status'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'rating') {
                        if (!is_numeric($data[$field]) || $data[$field] < 1 || $data[$field] > 5) {
                            return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
                        }
                        $updateData[$field] = (int)$data[$field];
                    } elseif ($field === 'status') {
                        // Only staff can change status
                        if (isStaff()) {
                            $validStatuses = [REVIEW_STATUS_PENDING, REVIEW_STATUS_APPROVED, REVIEW_STATUS_REJECTED];
                            if (in_array($data[$field], $validStatuses)) {
                                $updateData[$field] = $data[$field];
                            }
                        }
                    } else {
                        $updateData[$field] = cleanInput($data[$field]);
                    }
                }
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
                logActivity('review_updated', "Review ID {$id} updated", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Review updated successfully'];
            }
            
            return ['success' => false, 'message' => 'No changes made'];
            
        } catch (Exception $e) {
            error_log("Review update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Review update failed. Please try again.'];
        }
    }
    
    /**
     * Delete review
     */
    public function delete($id, $userId = null) {
        try {
            $review = $this->getById($id);
            if (!$review) {
                return ['success' => false, 'message' => 'Review not found'];
            }
            
            // Check if user owns this review (unless admin)
            if ($userId && $review['user_id'] != $userId && !isStaff()) {
                return ['success' => false, 'message' => 'You can only delete your own reviews'];
            }
            
            $deleted = $this->db->delete($this->table, 'id = ?', [$id]);
            
            if ($deleted) {
                logActivity('review_deleted', "Review ID {$id} deleted", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Review deleted successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete review'];
            
        } catch (Exception $e) {
            error_log("Review deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Review deletion failed. Please try again.'];
        }
    }
    
    /**
     * Get review by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT r.*, u.first_name, u.last_name, u.profile_image,
                           p.name_" . CURRENT_LANGUAGE . " as product_name,
                           s.name_" . CURRENT_LANGUAGE . " as service_name
                    FROM {$this->table} r
                    LEFT JOIN {$this->usersTable} u ON r.user_id = u.id
                    LEFT JOIN {$this->productsTable} p ON r.product_id = p.id
                    LEFT JOIN {$this->servicesTable} s ON r.service_id = s.id
                    WHERE r.id = ?";
            
            return $this->db->fetch($sql, [$id]);
            
        } catch (Exception $e) {
            error_log("Get review by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get product reviews
     */
    public function getProductReviews($productId, $page = 1, $limit = 10, $status = REVIEW_STATUS_APPROVED) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $total = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} WHERE product_id = ? AND status = ?",
                [$productId, $status]
            );
            
            // Get reviews
            $sql = "SELECT r.*, u.first_name, u.last_name, u.profile_image
                    FROM {$this->table} r
                    LEFT JOIN {$this->usersTable} u ON r.user_id = u.id
                    WHERE r.product_id = ? AND r.status = ?
                    ORDER BY r.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $reviews = $this->db->fetchAll($sql, [$productId, $status]);
            
            return [
                'reviews' => $reviews,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get product reviews failed: " . $e->getMessage());
            return ['reviews' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get service reviews
     */
    public function getServiceReviews($serviceId, $page = 1, $limit = 10, $status = REVIEW_STATUS_APPROVED) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $total = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} WHERE service_id = ? AND status = ?",
                [$serviceId, $status]
            );
            
            // Get reviews
            $sql = "SELECT r.*, u.first_name, u.last_name, u.profile_image
                    FROM {$this->table} r
                    LEFT JOIN {$this->usersTable} u ON r.user_id = u.id
                    WHERE r.service_id = ? AND r.status = ?
                    ORDER BY r.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $reviews = $this->db->fetchAll($sql, [$serviceId, $status]);
            
            return [
                'reviews' => $reviews,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get service reviews failed: " . $e->getMessage());
            return ['reviews' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get user reviews
     */
    public function getUserReviews($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $total = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?",
                [$userId]
            );
            
            // Get reviews
            $sql = "SELECT r.*,
                           p.name_" . CURRENT_LANGUAGE . " as product_name,
                           s.name_" . CURRENT_LANGUAGE . " as service_name
                    FROM {$this->table} r
                    LEFT JOIN {$this->productsTable} p ON r.product_id = p.id
                    LEFT JOIN {$this->servicesTable} s ON r.service_id = s.id
                    WHERE r.user_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $reviews = $this->db->fetchAll($sql, [$userId]);
            
            return [
                'reviews' => $reviews,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get user reviews failed: " . $e->getMessage());
            return ['reviews' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get all reviews (admin function)
     */
    public function getAllReviews($page = 1, $limit = ADMIN_ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            // Status filter
            if (!empty($filters['status'])) {
                $conditions[] = "r.status = ?";
                $params[] = $filters['status'];
            }
            
            // Rating filter
            if (!empty($filters['rating'])) {
                $conditions[] = "r.rating = ?";
                $params[] = $filters['rating'];
            }
            
            // Product/Service filter
            if (!empty($filters['product_id'])) {
                $conditions[] = "r.product_id = ?";
                $params[] = $filters['product_id'];
            }
            
            if (!empty($filters['service_id'])) {
                $conditions[] = "r.service_id = ?";
                $params[] = $filters['service_id'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $searchTerm = "%{$filters['search']}%";
                $conditions[] = "(r.title LIKE ? OR r.comment LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $totalQuery = "SELECT COUNT(*) FROM {$this->table} r 
                          LEFT JOIN {$this->usersTable} u ON r.user_id = u.id
                          {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get reviews
            $sql = "SELECT r.*, u.first_name, u.last_name, u.email,
                           p.name_" . CURRENT_LANGUAGE . " as product_name,
                           s.name_" . CURRENT_LANGUAGE . " as service_name
                    FROM {$this->table} r
                    LEFT JOIN {$this->usersTable} u ON r.user_id = u.id
                    LEFT JOIN {$this->productsTable} p ON r.product_id = p.id
                    LEFT JOIN {$this->servicesTable} s ON r.service_id = s.id
                    {$whereClause}
                    ORDER BY r.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $reviews = $this->db->fetchAll($sql, $params);
            
            return [
                'reviews' => $reviews,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all reviews failed: " . $e->getMessage());
            return ['reviews' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get pending reviews
     */
    public function getPendingReviews($limit = 50) {
        try {
            $sql = "SELECT r.*, u.first_name, u.last_name,
                           p.name_" . CURRENT_LANGUAGE . " as product_name,
                           s.name_" . CURRENT_LANGUAGE . " as service_name
                    FROM {$this->table} r
                    LEFT JOIN {$this->usersTable} u ON r.user_id = u.id
                    LEFT JOIN {$this->productsTable} p ON r.product_id = p.id
                    LEFT JOIN {$this->servicesTable} s ON r.service_id = s.id
                    WHERE r.status = ?
                    ORDER BY r.created_at ASC
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [REVIEW_STATUS_PENDING]);
            
        } catch (Exception $e) {
            error_log("Get pending reviews failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Approve review
     */
    public function approve($id) {
        return $this->updateStatus($id, REVIEW_STATUS_APPROVED);
    }
    
    /**
     * Reject review
     */
    public function reject($id) {
        return $this->updateStatus($id, REVIEW_STATUS_REJECTED);
    }
    
    /**
     * Update review status
     */
    public function updateStatus($id, $status) {
        try {
            $validStatuses = [REVIEW_STATUS_PENDING, REVIEW_STATUS_APPROVED, REVIEW_STATUS_REJECTED];
            
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $review = $this->getById($id);
            if (!$review) {
                return ['success' => false, 'message' => 'Review not found'];
            }
            
            $updated = $this->db->update(
                $this->table,
                ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$id]
            );
            
            if ($updated) {
                // Notify user about status change
                if ($status === REVIEW_STATUS_APPROVED) {
                    createNotification(
                        $review['user_id'],
                        'تم قبول المراجعة',
                        'Review Approved',
                        'تم قبول مراجعتك ونشرها',
                        'Your review has been approved and published',
                        NOTIFICATION_SUCCESS
                    );
                } elseif ($status === REVIEW_STATUS_REJECTED) {
                    createNotification(
                        $review['user_id'],
                        'تم رفض المراجعة',
                        'Review Rejected',
                        'تم رفض مراجعتك',
                        'Your review has been rejected',
                        NOTIFICATION_WARNING
                    );
                }
                
                logActivity('review_status_updated', "Review ID {$id} status changed to {$status}", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Review status updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update review status'];
            
        } catch (Exception $e) {
            error_log("Review status update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Status update failed. Please try again.'];
        }
    }
    
    /**
     * Get average rating for product
     */
    public function getProductAverageRating($productId) {
        try {
            $result = $this->db->fetch(
                "SELECT AVG(rating) as average_rating, COUNT(*) as review_count 
                 FROM {$this->table} 
                 WHERE product_id = ? AND status = ?",
                [$productId, REVIEW_STATUS_APPROVED]
            );
            
            return [
                'average_rating' => round($result['average_rating'] ?? 0, 1),
                'review_count' => (int)($result['review_count'] ?? 0)
            ];
            
        } catch (Exception $e) {
            return ['average_rating' => 0, 'review_count' => 0];
        }
    }
    
    /**
     * Get average rating for service
     */
    public function getServiceAverageRating($serviceId) {
        try {
            $result = $this->db->fetch(
                "SELECT AVG(rating) as average_rating, COUNT(*) as review_count 
                 FROM {$this->table} 
                 WHERE service_id = ? AND status = ?",
                [$serviceId, REVIEW_STATUS_APPROVED]
            );
            
            return [
                'average_rating' => round($result['average_rating'] ?? 0, 1),
                'review_count' => (int)($result['review_count'] ?? 0)
            ];
            
        } catch (Exception $e) {
            return ['average_rating' => 0, 'review_count' => 0];
        }
    }
    
    /**
     * Get review statistics
     */
    public function getReviewStatistics() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_reviews,
                        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
                        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reviews,
                        COALESCE(AVG(rating), 0) as average_rating,
                        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star_reviews,
                        COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star_reviews,
                        COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star_reviews,
                        COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star_reviews,
                        COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star_reviews
                    FROM {$this->table}";
            
            return $this->db->fetch($sql);
            
        } catch (Exception $e) {
            error_log("Get review statistics failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user has reviewed item
     */
    public function hasUserReviewed($userId, $productId = null, $serviceId = null) {
        return $this->getUserReview($userId, $productId, $serviceId) !== null;
    }
    
    /**
     * Get user's review for specific item
     */
    public function getUserReview($userId, $productId = null, $serviceId = null) {
        try {
            $conditions = ['user_id = ?'];
            $params = [$userId];
            
            if ($productId) {
                $conditions[] = 'product_id = ?';
                $params[] = $productId;
            } elseif ($serviceId) {
                $conditions[] = 'service_id = ?';
                $params[] = $serviceId;
            } else {
                return null;
            }
            
            $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $conditions);
            return $this->db->fetch($sql, $params);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get latest reviews
     */
    public function getLatestReviews($limit = 5) {
        try {
            $sql = "SELECT r.*, u.first_name, u.last_name, u.profile_image,
                           p.name_" . CURRENT_LANGUAGE . " as product_name,
                           s.name_" . CURRENT_LANGUAGE . " as service_name
                    FROM {$this->table} r
                    LEFT JOIN {$this->usersTable} u ON r.user_id = u.id
                    LEFT JOIN {$this->productsTable} p ON r.product_id = p.id
                    LEFT JOIN {$this->servicesTable} s ON r.service_id = s.id
                    WHERE r.status = ?
                    ORDER BY r.created_at DESC
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [REVIEW_STATUS_APPROVED]);
            
        } catch (Exception $e) {
            error_log("Get latest reviews failed: " . $e->getMessage());
            return [];
        }
    }
}
?>