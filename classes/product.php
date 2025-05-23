<?php
/**
 * Product Management Class
 * TechSavvyGenLtd Project
 */

class Product {
    private $db;
    private $table = TBL_PRODUCTS;
    private $imagesTable = TBL_PRODUCT_IMAGES;
    private $categoriesTable = TBL_CATEGORIES;
    private $reviewsTable = TBL_REVIEWS;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new product
     */
    public function create($data) {
        // Validate required fields
        $required = ['name_ar', 'name_en', 'price', 'category_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field {$field} is required"];
            }
        }
        
        // Validate price
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            return ['success' => false, 'message' => 'Invalid price'];
        }
        
        try {
            // Generate slug
            $slug = $this->generateUniqueSlug($data['name_en']);
            
            // Prepare product data
            $productData = [
                'category_id' => (int)$data['category_id'],
                'name_ar' => cleanInput($data['name_ar']),
                'name_en' => cleanInput($data['name_en']),
                'slug' => $slug,
                'description_ar' => cleanInput($data['description_ar'] ?? ''),
                'description_en' => cleanInput($data['description_en'] ?? ''),
                'short_description_ar' => cleanInput($data['short_description_ar'] ?? ''),
                'short_description_en' => cleanInput($data['short_description_en'] ?? ''),
                'price' => (float)$data['price'],
                'sale_price' => !empty($data['sale_price']) ? (float)$data['sale_price'] : null,
                'stock' => (int)($data['stock'] ?? 0),
                'is_digital' => !empty($data['is_digital']) ? 1 : 0,
                'digital_file' => cleanInput($data['digital_file'] ?? ''),
                'featured_image' => cleanInput($data['featured_image'] ?? ''),
                'status' => $data['status'] ?? STATUS_ACTIVE,
                'is_featured' => !empty($data['is_featured']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $productId = $this->db->insert($this->table, $productData);
            
            if ($productId) {
                logActivity('product_created', "Product '{$productData['name_en']}' created", $_SESSION['user_id'] ?? null);
                return [
                    'success' => true,
                    'message' => 'Product created successfully',
                    'product_id' => $productId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create product'];
            
        } catch (Exception $e) {
            error_log("Product creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Product creation failed. Please try again.'];
        }
    }
    
    /**
     * Update product
     */
    public function update($id, $data) {
        try {
            // Check if product exists
            if (!$this->exists($id)) {
                return ['success' => false, 'message' => 'Product not found'];
            }
            
            $allowedFields = [
                'category_id', 'name_ar', 'name_en', 'description_ar', 'description_en',
                'short_description_ar', 'short_description_en', 'price', 'sale_price',
                'stock', 'is_digital', 'digital_file', 'featured_image', 'status', 'is_featured'
            ];
            
            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if (in_array($field, ['name_ar', 'name_en', 'description_ar', 'description_en', 'short_description_ar', 'short_description_en', 'digital_file', 'featured_image'])) {
                        $updateData[$field] = cleanInput($data[$field]);
                    } elseif (in_array($field, ['category_id', 'stock'])) {
                        $updateData[$field] = (int)$data[$field];
                    } elseif (in_array($field, ['price', 'sale_price'])) {
                        $updateData[$field] = !empty($data[$field]) ? (float)$data[$field] : null;
                    } elseif (in_array($field, ['is_digital', 'is_featured'])) {
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
                logActivity('product_updated', "Product ID {$id} updated", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Product updated successfully'];
            }
            
            return ['success' => false, 'message' => 'No changes made'];
            
        } catch (Exception $e) {
            error_log("Product update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Product update failed. Please try again.'];
        }
    }
    
    /**
     * Delete product
     */
    public function delete($id) {
        try {
            $product = $this->getById($id);
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }
            
            $this->db->beginTransaction();
            
            // Delete product images
            $images = $this->getProductImages($id);
            foreach ($images as $image) {
                deleteFile(UPLOAD_PATH_PRODUCTS . '/' . $image['image_path']);
            }
            $this->db->delete($this->imagesTable, 'product_id = ?', [$id]);
            
            // Delete featured image
            if ($product['featured_image']) {
                deleteFile(UPLOAD_PATH_PRODUCTS . '/' . $product['featured_image']);
            }
            
            // Delete digital file
            if ($product['digital_file']) {
                deleteFile(UPLOAD_PATH_PRODUCTS . '/' . $product['digital_file']);
            }
            
            // Delete product
            $deleted = $this->db->delete($this->table, 'id = ?', [$id]);
            
            if ($deleted) {
                $this->db->commit();
                logActivity('product_deleted', "Product '{$product['name_en']}' deleted", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Product deleted successfully'];
            }
            
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to delete product'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Product deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Product deletion failed. Please try again.'];
        }
    }
    
    /**
     * Get product by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT p.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} p
                    LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON p.id = r.product_id AND r.status = 'approved'
                    WHERE p.id = ?
                    GROUP BY p.id";
            
            $product = $this->db->fetch($sql, [$id]);
            
            if ($product) {
                $product['images'] = $this->getProductImages($id);
                $product['final_price'] = $this->calculateFinalPrice($product);
            }
            
            return $product;
            
        } catch (Exception $e) {
            error_log("Get product by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get product by slug
     */
    public function getBySlug($slug) {
        try {
            $sql = "SELECT p.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} p
                    LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON p.id = r.product_id AND r.status = 'approved'
                    WHERE p.slug = ? AND p.status = 'active'
                    GROUP BY p.id";
            
            $product = $this->db->fetch($sql, [$slug]);
            
            if ($product) {
                $product['images'] = $this->getProductImages($product['id']);
                $product['final_price'] = $this->calculateFinalPrice($product);
            }
            
            return $product;
            
        } catch (Exception $e) {
            error_log("Get product by slug failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all products with filtering and pagination
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = ['p.status = ?'];
            $params = [STATUS_ACTIVE];
            
            // Category filter
            if (!empty($filters['category_id'])) {
                $conditions[] = "p.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $searchTerm = "%{$filters['search']}%";
                $conditions[] = "(p.name_ar LIKE ? OR p.name_en LIKE ? OR p.description_ar LIKE ? OR p.description_en LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Price range filter
            if (!empty($filters['min_price'])) {
                $conditions[] = "p.price >= ?";
                $params[] = (float)$filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $conditions[] = "p.price <= ?";
                $params[] = (float)$filters['max_price'];
            }
            
            // Featured filter
            if (!empty($filters['featured'])) {
                $conditions[] = "p.is_featured = 1";
            }
            
            // Digital filter
            if (isset($filters['is_digital'])) {
                $conditions[] = "p.is_digital = ?";
                $params[] = $filters['is_digital'] ? 1 : 0;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            
            // Order by
            $orderBy = 'ORDER BY ';
            switch ($filters['sort'] ?? 'newest') {
                case 'price_low':
                    $orderBy .= 'p.price ASC';
                    break;
                case 'price_high':
                    $orderBy .= 'p.price DESC';
                    break;
                case 'name':
                    $orderBy .= 'p.name_' . CURRENT_LANGUAGE . ' ASC';
                    break;
                case 'rating':
                    $orderBy .= 'average_rating DESC, p.created_at DESC';
                    break;
                default:
                    $orderBy .= 'p.created_at DESC';
            }
            
            // Get total count
            $totalQuery = "SELECT COUNT(DISTINCT p.id) FROM {$this->table} p {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get products
            $sql = "SELECT p.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} p
                    LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON p.id = r.product_id AND r.status = 'approved'
                    {$whereClause}
                    GROUP BY p.id
                    {$orderBy}
                    LIMIT {$limit} OFFSET {$offset}";
            
            $products = $this->db->fetchAll($sql, $params);
            
            // Calculate final prices
            foreach ($products as &$product) {
                $product['final_price'] = $this->calculateFinalPrice($product);
            }
            
            return [
                'products' => $products,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all products failed: " . $e->getMessage());
            return ['products' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get featured products
     */
    public function getFeaturedProducts($limit = 8) {
        try {
            $sql = "SELECT p.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} p
                    LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON p.id = r.product_id AND r.status = 'approved'
                    WHERE p.status = 'active' AND p.is_featured = 1
                    GROUP BY p.id
                    ORDER BY p.created_at DESC
                    LIMIT {$limit}";
            
            $products = $this->db->fetchAll($sql);
            
            // Calculate final prices
            foreach ($products as &$product) {
                $product['final_price'] = $this->calculateFinalPrice($product);
            }
            
            return $products;
            
        } catch (Exception $e) {
            error_log("Get featured products failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get related products
     */
    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        try {
            $sql = "SELECT p.*, c.name_" . CURRENT_LANGUAGE . " as category_name,
                           AVG(r.rating) as average_rating,
                           COUNT(r.id) as review_count
                    FROM {$this->table} p
                    LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                    LEFT JOIN {$this->reviewsTable} r ON p.id = r.product_id AND r.status = 'approved'
                    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
                    GROUP BY p.id
                    ORDER BY RAND()
                    LIMIT {$limit}";
            
            $products = $this->db->fetchAll($sql, [$categoryId, $productId]);
            
            // Calculate final prices
            foreach ($products as &$product) {
                $product['final_price'] = $this->calculateFinalPrice($product);
            }
            
            return $products;
            
        } catch (Exception $e) {
            error_log("Get related products failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Upload product image
     */
    public function uploadImage($productId, $file, $isFeatured = false) {
        $upload = uploadFile($file, UPLOAD_PATH_PRODUCTS);
        
        if ($upload['success']) {
            try {
                if ($isFeatured) {
                    // Update featured image
                    $this->db->update(
                        $this->table,
                        ['featured_image' => $upload['filename']],
                        'id = ?',
                        [$productId]
                    );
                } else {
                    // Add to product images
                    $this->db->insert($this->imagesTable, [
                        'product_id' => $productId,
                        'image_path' => $upload['filename'],
                        'sort_order' => $this->getNextImageSortOrder($productId)
                    ]);
                }
                
                return $upload;
                
            } catch (Exception $e) {
                deleteFile($upload['path']);
                error_log("Product image upload failed: " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to save image information'];
            }
        }
        
        return $upload;
    }
    
    /**
     * Delete product image
     */
    public function deleteImage($imageId, $productId) {
        try {
            $image = $this->db->fetch(
                "SELECT image_path FROM {$this->imagesTable} WHERE id = ? AND product_id = ?",
                [$imageId, $productId]
            );
            
            if ($image) {
                $deleted = $this->db->delete($this->imagesTable, 'id = ?', [$imageId]);
                
                if ($deleted) {
                    deleteFile(UPLOAD_PATH_PRODUCTS . '/' . $image['image_path']);
                    return ['success' => true, 'message' => 'Image deleted successfully'];
                }
            }
            
            return ['success' => false, 'message' => 'Image not found'];
            
        } catch (Exception $e) {
            error_log("Product image deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Image deletion failed'];
        }
    }
    
    /**
     * Update stock quantity
     */
    public function updateStock($productId, $quantity, $operation = 'set') {
        try {
            if ($operation === 'decrease') {
                $sql = "UPDATE {$this->table} SET stock = stock - ? WHERE id = ? AND stock >= ?";
                $params = [$quantity, $productId, $quantity];
            } elseif ($operation === 'increase') {
                $sql = "UPDATE {$this->table} SET stock = stock + ? WHERE id = ?";
                $params = [$quantity, $productId];
            } else {
                $sql = "UPDATE {$this->table} SET stock = ? WHERE id = ?";
                $params = [$quantity, $productId];
            }
            
            $stmt = $this->db->query($sql, $params);
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                logActivity('product_stock_updated', "Product ID {$productId} stock updated", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Stock updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update stock or insufficient quantity'];
            
        } catch (Exception $e) {
            error_log("Product stock update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Stock update failed'];
        }
    }
    
    /**
     * Check if product exists
     */
    public function exists($id) {
        return $this->db->exists($this->table, 'id = ?', [$id]);
    }
    
    /**
     * Get product images
     */
    public function getProductImages($productId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->imagesTable} WHERE product_id = ? ORDER BY sort_order ASC",
                [$productId]
            );
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Calculate final price with discounts
     */
    private function calculateFinalPrice($product) {
        $price = (float)$product['price'];
        $salePrice = $product['sale_price'] ? (float)$product['sale_price'] : null;
        
        // Use sale price if available and lower than regular price
        if ($salePrice && $salePrice < $price) {
            return $salePrice;
        }
        
        return $price;
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
     * Get next image sort order
     */
    private function getNextImageSortOrder($productId) {
        $maxOrder = $this->db->fetchColumn(
            "SELECT MAX(sort_order) FROM {$this->imagesTable} WHERE product_id = ?",
            [$productId]
        );
        
        return ($maxOrder ? $maxOrder : 0) + 1;
    }
}
?>