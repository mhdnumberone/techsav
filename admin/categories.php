<?php
/**
 * Admin Categories Management
 * TechSavvyGenLtd Project
 */

require_once '../config/config.php';

// Check if user is staff/admin
if (!isStaff()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize Category class
$categoryClass = new Category();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_category':
                $result = $categoryClass->create($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_category':
                $categoryId = (int)$_POST['category_id'];
                $result = $categoryClass->update($categoryId, $_POST);
                if ($result['success']) {
                    $message = 'Category updated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete_category':
                $categoryId = (int)$_POST['category_id'];
                $result = $categoryClass->delete($categoryId);
                if ($result['success']) {
                    $message = 'Category deleted successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'upload_image':
                $categoryId = (int)$_POST['category_id'];
                if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
                    $result = $categoryClass->uploadImage($categoryId, $_FILES['category_image']);
                    if ($result['success']) {
                        $message = 'Image uploaded successfully';
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Please select a valid image file';
                }
                break;
                
            case 'reorder_categories':
                $categoryOrders = $_POST['category_orders'] ?? [];
                $result = $categoryClass->reorderCategories($categoryOrders);
                if ($result['success']) {
                    $message = 'Categories reordered successfully';
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$parent_id = $_GET['parent_id'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

// Build filters array
$filters = array_filter([
    'search' => $search,
    'status' => $status,
    'parent_id' => $parent_id
]);

// Get categories
$categoriesData = $categoryClass->getAll($page, ADMIN_ITEMS_PER_PAGE, $filters);
$categories = $categoriesData['categories'];
$totalPages = $categoriesData['pages'];
$totalCategories = $categoriesData['total'];

// Get all categories for parent dropdown (no pagination)
$allCategories = $categoryClass->getAll(1, 1000)['categories'];

// Get category statistics
$categoryStats = getCategoryStatistics();

$page_title = 'Categories Management';
$body_class = 'admin-page categories-page';

// Helper functions
function getCategoryStatistics() {
    global $categoryClass;
    $db = Database::getInstance();
    
    try {
        $stats = $db->fetch(
            "SELECT 
                COUNT(*) as total_categories,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_categories,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_categories,
                COUNT(CASE WHEN parent_id IS NULL THEN 1 END) as parent_categories,
                COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as child_categories
             FROM " . TBL_CATEGORIES
        );
        
        return $stats;
    } catch (Exception $e) {
        return [
            'total_categories' => 0,
            'active_categories' => 0,
            'inactive_categories' => 0,
            'parent_categories' => 0,
            'child_categories' => 0
        ];
    }
}

// Category class placeholder (assuming it doesn't exist in the provided files)
class Category {
    private $db;
    private $table = TBL_CATEGORIES;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $required = ['name_ar', 'name_en'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field {$field} is required"];
            }
        }
        
        try {
            $slug = $this->generateUniqueSlug($data['name_en']);
            
            $categoryData = [
                'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                'name_ar' => cleanInput($data['name_ar']),
                'name_en' => cleanInput($data['name_en']),
                'slug' => $slug,
                'description_ar' => cleanInput($data['description_ar'] ?? ''),
                'description_en' => cleanInput($data['description_en'] ?? ''),
                'image' => cleanInput($data['image'] ?? ''),
                'status' => $data['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $categoryId = $this->db->insert($this->table, $categoryData);
            
            if ($categoryId) {
                logActivity('category_created', "Category '{$categoryData['name_en']}' created", $_SESSION['user_id'] ?? null);
                return [
                    'success' => true,
                    'message' => 'Category created successfully',
                    'category_id' => $categoryId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create category'];
            
        } catch (Exception $e) {
            error_log("Category creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Category creation failed. Please try again.'];
        }
    }
    
    public function update($id, $data) {
        try {
            if (!$this->exists($id)) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            $allowedFields = ['parent_id', 'name_ar', 'name_en', 'description_ar', 'description_en', 'image', 'status'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if (in_array($field, ['name_ar', 'name_en', 'description_ar', 'description_en', 'image'])) {
                        $updateData[$field] = cleanInput($data[$field]);
                    } elseif ($field === 'parent_id') {
                        $updateData[$field] = !empty($data[$field]) ? (int)$data[$field] : null;
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }
            
            if (isset($data['name_en'])) {
                $updateData['slug'] = $this->generateUniqueSlug($data['name_en'], $id);
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $updated = $this->db->update($this->table, $updateData, 'id = ?', [$id]);
            
            if ($updated) {
                logActivity('category_updated', "Category ID {$id} updated", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Category updated successfully'];
            }
            
            return ['success' => false, 'message' => 'No changes made'];
            
        } catch (Exception $e) {
            error_log("Category update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Category update failed. Please try again.'];
        }
    }
    
    public function delete($id) {
        try {
            $category = $this->getById($id);
            if (!$category) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            // Check if category has children
            $hasChildren = $this->db->exists($this->table, 'parent_id = ?', [$id]);
            if ($hasChildren) {
                return ['success' => false, 'message' => 'Cannot delete category with subcategories'];
            }
            
            // Check if category has products/services
            $hasProducts = $this->db->exists(TBL_PRODUCTS, 'category_id = ?', [$id]);
            $hasServices = $this->db->exists(TBL_SERVICES, 'category_id = ?', [$id]);
            
            if ($hasProducts || $hasServices) {
                return ['success' => false, 'message' => 'Cannot delete category with associated products/services'];
            }
            
            $this->db->beginTransaction();
            
            // Delete image
            if ($category['image']) {
                deleteFile(UPLOAD_PATH_CATEGORIES . '/' . $category['image']);
            }
            
            $deleted = $this->db->delete($this->table, 'id = ?', [$id]);
            
            if ($deleted) {
                $this->db->commit();
                logActivity('category_deleted', "Category '{$category['name_en']}' deleted", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Category deleted successfully'];
            }
            
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to delete category'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Category deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Category deletion failed. Please try again.'];
        }
    }
    
    public function getById($id) {
        try {
            $sql = "SELECT c.*, parent.name_" . CURRENT_LANGUAGE . " as parent_name
                    FROM {$this->table} c
                    LEFT JOIN {$this->table} parent ON c.parent_id = parent.id
                    WHERE c.id = ?";
            
            return $this->db->fetch($sql, [$id]);
            
        } catch (Exception $e) {
            error_log("Get category by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            if (!empty($filters['search'])) {
                $searchTerm = "%{$filters['search']}%";
                $conditions[] = "(c.name_ar LIKE ? OR c.name_en LIKE ? OR c.description_ar LIKE ? OR c.description_en LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['status'])) {
                $conditions[] = "c.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['parent_id'])) {
                $conditions[] = "c.parent_id = ?";
                $params[] = $filters['parent_id'];
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $totalQuery = "SELECT COUNT(*) FROM {$this->table} c {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get categories
            $sql = "SELECT c.*, parent.name_" . CURRENT_LANGUAGE . " as parent_name,
                           (SELECT COUNT(*) FROM {$this->table} child WHERE child.parent_id = c.id) as child_count,
                           (SELECT COUNT(*) FROM " . TBL_PRODUCTS . " p WHERE p.category_id = c.id) as product_count,
                           (SELECT COUNT(*) FROM " . TBL_SERVICES . " s WHERE s.category_id = c.id) as service_count
                    FROM {$this->table} c
                    LEFT JOIN {$this->table} parent ON c.parent_id = parent.id
                    {$whereClause}
                    ORDER BY c.parent_id ASC, c.name_" . CURRENT_LANGUAGE . " ASC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $categories = $this->db->fetchAll($sql, $params);
            
            return [
                'categories' => $categories,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all categories failed: " . $e->getMessage());
            return ['categories' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    public function uploadImage($categoryId, $file) {
        $upload = uploadFile($file, UPLOAD_PATH_CATEGORIES);
        
        if ($upload['success']) {
            try {
                $updated = $this->db->update(
                    $this->table,
                    ['image' => $upload['filename']],
                    'id = ?',
                    [$categoryId]
                );
                
                if ($updated) {
                    return $upload;
                } else {
                    deleteFile($upload['path']);
                    return ['success' => false, 'message' => 'Failed to update category image'];
                }
                
            } catch (Exception $e) {
                deleteFile($upload['path']);
                error_log("Category image upload failed: " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to save image information'];
            }
        }
        
        return $upload;
    }
    
    public function reorderCategories($orders) {
        try {
            $this->db->beginTransaction();
            
            foreach ($orders as $id => $order) {
                $this->db->update(
                    $this->table,
                    ['sort_order' => (int)$order],
                    'id = ?',
                    [$id]
                );
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Categories reordered successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Category reordering failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reorder categories'];
        }
    }
    
    public function exists($id) {
        return $this->db->exists($this->table, 'id = ?', [$id]);
    }
    
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
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">Categories Management</h1>
                <p class="admin-subtitle">Organize your products and services into categories</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <i class="fas fa-plus me-2"></i>Add Category
                </button>
                <button class="btn btn-outline-info" onclick="toggleCategoryTree()">
                    <i class="fas fa-sitemap me-2"></i>Tree View
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                                <i class="fas fa-folder fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($categoryStats['total_categories']); ?></div>
                                <div class="stat-label text-muted small">Total Categories</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                <i class="fas fa-check fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($categoryStats['active_categories']); ?></div>
                                <div class="stat-label text-muted small">Active Categories</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info bg-opacity-10 text-info rounded-circle p-3 me-3">
                                <i class="fas fa-layer-group fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($categoryStats['parent_categories']); ?></div>
                                <div class="stat-label text-muted small">Parent Categories</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-circle p-3 me-3">
                                <i class="fas fa-list fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($categoryStats['child_categories']); ?></div>
                                <div class="stat-label text-muted small">Sub Categories</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="parent_id">
                            <option value="">All Categories</option>
                            <option value="0">Parent Categories Only</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <?php if (!$cat['parent_id']): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $parent_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name_' . CURRENT_LANGUAGE]); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Categories (<?php echo $totalCategories; ?>)</h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-success" onclick="exportCategories()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                    <button class="btn btn-outline-warning" id="reorderBtn" onclick="toggleReorder()" style="display: none;">
                        <i class="fas fa-sort me-1"></i>Reorder
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="categoriesTable">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <div class="drag-handle" style="display: none;">
                                        <i class="fas fa-grip-vertical text-muted"></i>
                                    </div>
                                </th>
                                <th>Category</th>
                                <th>Parent</th>
                                <th>Products</th>
                                <th>Services</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sortableCategories">
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No categories found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                <tr data-category-id="<?php echo $category['id']; ?>">
                                    <td>
                                        <div class="drag-handle" style="display: none;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($category['parent_id']): ?>
                                            <div class="category-indent me-2">└─</div>
                                            <?php endif; ?>
                                            
                                            <img src="<?php echo $category['image'] ? UPLOADS_URL . '/' . UPLOAD_PATH_CATEGORIES . '/' . $category['image'] : ASSETS_URL . '/images/placeholder.jpg'; ?>" 
                                                 class="rounded me-2" width="40" height="40" alt="Category" style="object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($category['name_' . CURRENT_LANGUAGE]); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($category['slug']); ?></small>
                                                <?php if ($category['child_count'] > 0): ?>
                                                <br><small class="text-info"><?php echo $category['child_count']; ?> subcategories</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($category['parent_name']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($category['parent_name']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">─</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $category['product_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $category['service_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $category['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($category['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($category['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editCategory(<?php echo $category['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="manageCategoryImage(<?php echo $category['id']; ?>)" title="Image">
                                                <i class="fas fa-image"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="addSubcategory(<?php echo $category['id']; ?>)" title="Add Subcategory">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <?php
                $baseUrl = ADMIN_URL . '/categories.php?' . http_build_query(array_filter([
                    'search' => $search,
                    'status' => $status,
                    'parent_id' => $parent_id
                ]));
                echo generatePagination($page, $totalPages, $baseUrl);
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="create_category">
                
                <div class="modal-header">
                    <h5 class="modal-title">Create New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Parent Category (Optional)</label>
                            <select class="form-select" name="parent_id">
                                <option value="">No Parent (Top Level)</option>
                                <?php foreach ($allCategories as $cat): ?>
                                    <?php if (!$cat['parent_id']): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name_' . CURRENT_LANGUAGE]); ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (Arabic)</label>
                            <input type="text" class="form-control" name="name_ar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (English)</label>
                            <input type="text" class="form-control" name="name_en" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (Arabic)</label>
                            <textarea class="form-control" name="description_ar" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (English)</label>
                            <textarea class="form-control" name="description_en" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editCategoryForm">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" name="category_id" id="editCategoryId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Parent Category</label>
                            <select class="form-select" name="parent_id" id="editParentId">
                                <option value="">No Parent (Top Level)</option>
                                <?php foreach ($allCategories as $cat): ?>
                                    <?php if (!$cat['parent_id']): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name_' . CURRENT_LANGUAGE]); ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (Arabic)</label>
                            <input type="text" class="form-control" name="name_ar" id="editNameAr" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (English)</label>
                            <input type="text" class="form-control" name="name_en" id="editNameEn" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (Arabic)</label>
                            <textarea class="form-control" name="description_ar" id="editDescAr" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (English)</label>
                            <textarea class="form-control" name="description_en" id="editDescEn" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Upload Modal -->
<div class="modal fade" id="imageUploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="upload_image">
                <input type="hidden" name="category_id" id="imageCategoryId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Upload Category Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Image</label>
                        <input type="file" class="form-control" name="category_image" accept="image/*" required>
                        <div class="form-text">Supported formats: JPG, PNG, GIF. Max size: 2MB</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Image</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Sortable.js for drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
let sortable;
let reorderMode = false;

function editCategory(categoryId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/categories/get.php?id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const category = data.category;
                document.getElementById('editCategoryId').value = category.id;
                document.getElementById('editParentId').value = category.parent_id || '';
                document.getElementById('editNameAr').value = category.name_ar;
                document.getElementById('editNameEn').value = category.name_en;
                document.getElementById('editDescAr').value = category.description_ar || '';
                document.getElementById('editDescEn').value = category.description_en || '';
                document.getElementById('editStatus').value = category.status;
                
                new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load category data', 'error');
        });
}

function deleteCategory(categoryId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the category!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_id" value="${categoryId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function manageCategoryImage(categoryId) {
    document.getElementById('imageCategoryId').value = categoryId;
    new bootstrap.Modal(document.getElementById('imageUploadModal')).show();
}

function addSubcategory(parentId) {
    const modal = new bootstrap.Modal(document.getElementById('createCategoryModal'));
    document.querySelector('[name="parent_id"]').value = parentId;
    modal.show();
}

function toggleCategoryTree() {
    // Implement tree view toggle functionality
    Swal.fire('Info', 'Tree view functionality coming soon', 'info');
}

function toggleReorder() {
    reorderMode = !reorderMode;
    const dragHandles = document.querySelectorAll('.drag-handle');
    const reorderBtn = document.getElementById('reorderBtn');
    
    if (reorderMode) {
        // Enable reorder mode
        dragHandles.forEach(handle => handle.style.display = 'block');
        reorderBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Order';
        reorderBtn.classList.remove('btn-outline-warning');
        reorderBtn.classList.add('btn-warning');
        
        // Initialize sortable
        sortable = Sortable.create(document.getElementById('sortableCategories'), {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                // Optional: Show save button or auto-save
            }
        });
        
        Swal.fire('Reorder Mode', 'Drag categories to reorder them, then click "Save Order"', 'info');
    } else {
        // Save order and exit reorder mode
        saveOrder();
    }
}

function saveOrder() {
    const rows = document.querySelectorAll('#sortableCategories tr[data-category-id]');
    const orders = {};
    
    rows.forEach((row, index) => {
        const categoryId = row.dataset.categoryId;
        orders[categoryId] = index + 1;
    });
    
    // Send reorder request
    const form = document.createElement('form');
    form.method = 'POST';
    let formHtml = `
        <?php echo csrfToken(); ?>
        <input type="hidden" name="action" value="reorder_categories">
    `;
    
    Object.keys(orders).forEach(id => {
        formHtml += `<input type="hidden" name="category_orders[${id}]" value="${orders[id]}">`;
    });
    
    form.innerHTML = formHtml;
    document.body.appendChild(form);
    form.submit();
}

function exportCategories() {
    const params = new URLSearchParams(window.location.search);
    window.open(`${window.SITE_CONFIG.apiUrl}/categories/export.php?${params.toString()}`, '_blank');
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Show reorder button if there are categories
    const categoriesTable = document.getElementById('categoriesTable');
    const rows = categoriesTable.querySelectorAll('tbody tr[data-category-id]');
    if (rows.length > 1) {
        document.getElementById('reorderBtn').style.display = 'inline-block';
    }
});
</script>

<?php include '../includes/footer.php'; ?>