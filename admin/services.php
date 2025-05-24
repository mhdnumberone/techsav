<?php
/**
 * Admin Services Management
 * TechSavvyGenLtd Project
 */

require_once '../config/config.php';

// Check if user is staff/admin
if (!isStaff()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize classes
$serviceClass = new Service();
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
            case 'create_service':
                $result = $serviceClass->create($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_service':
                $serviceId = (int)$_POST['service_id'];
                $result = $serviceClass->update($serviceId, $_POST);
                if ($result['success']) {
                    $message = 'Service updated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete_service':
                $serviceId = (int)$_POST['service_id'];
                $result = $serviceClass->delete($serviceId);
                if ($result['success']) {
                    $message = 'Service deleted successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'upload_image':
                $serviceId = (int)$_POST['service_id'];
                if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {
                    $result = $serviceClass->uploadImage($serviceId, $_FILES['service_image']);
                    if ($result['success']) {
                        $message = 'Image uploaded successfully';
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Please select a valid image file';
                }
                break;
                
            case 'create_custom_service':
                $result = $serviceClass->createCustomService($_POST);
                if ($result['success']) {
                    $message = 'Custom service created successfully';
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';
$featured = $_GET['featured'] ?? '';
$has_fixed_price = $_GET['has_fixed_price'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

// Build filters array
$filters = array_filter([
    'search' => $search,
    'category_id' => $category_id,
    'status' => $status,
    'featured' => $featured,
    'has_fixed_price' => $has_fixed_price
]);

// Get services
$servicesData = $serviceClass->getAll($page, ADMIN_ITEMS_PER_PAGE, $filters);
$services = $servicesData['services'];
$totalPages = $servicesData['pages'];
$totalServices = $servicesData['total'];

// Get categories for dropdown
$categoriesData = $categoryClass->getAll(1, 100);
$categories = $categoriesData['categories'] ?? [];

// Get recent custom services
$recentCustomServices = $serviceClass->getRecentCustomServices(10);

$page_title = 'Services Management';
$body_class = 'admin-page services-page';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">Services Management</h1>
                <p class="admin-subtitle">Manage your service offerings</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                    <i class="fas fa-plus me-2"></i>Add Service
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCustomServiceModal">
                    <i class="fas fa-cog me-2"></i>Create Custom Service
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
                                <i class="fas fa-cogs fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo $totalServices; ?></div>
                                <div class="stat-label text-muted small">Total Services</div>
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
                                <i class="fas fa-star fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0">
                                    <?php echo count(array_filter($services, fn($s) => $s['is_featured'])); ?>
                                </div>
                                <div class="stat-label text-muted small">Featured Services</div>
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
                                <i class="fas fa-tools fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo count($recentCustomServices); ?></div>
                                <div class="stat-label text-muted small">Custom Services</div>
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
                                <i class="fas fa-dollar-sign fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0">
                                    <?php echo count(array_filter($services, fn($s) => $s['has_fixed_price'])); ?>
                                </div>
                                <div class="stat-label text-muted small">Fixed Price Services</div>
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
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search services..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="category_id">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name_' . CURRENT_LANGUAGE]); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="has_fixed_price">
                            <option value="">All Pricing</option>
                            <option value="1" <?php echo $has_fixed_price === '1' ? 'selected' : ''; ?>>Fixed Price</option>
                            <option value="0" <?php echo $has_fixed_price === '0' ? 'selected' : ''; ?>>Quote Based</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-md-1">
                        <a href="services.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Services Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Services (<?php echo $totalServices; ?>)</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-success" onclick="exportServices()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Featured</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($services)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No services found</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $service['featured_image'] ? UPLOADS_URL . '/' . UPLOAD_PATH_SERVICES . '/' . $service['featured_image'] : ASSETS_URL . '/images/placeholder.jpg'; ?>" 
                                                         class="rounded me-2" width="50" height="50" alt="Service" style="object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($service['name_' . CURRENT_LANGUAGE]); ?></div>
                                                        <small class="text-muted"><?php echo truncateText($service['description_' . CURRENT_LANGUAGE], 50); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($service['category_name'] ?? 'Uncategorized'); ?></td>
                                            <td>
                                                <?php if ($service['has_fixed_price'] && $service['price']): ?>
                                                <div class="fw-bold text-success"><?php echo formatCurrency($service['price']); ?></div>
                                                <?php else: ?>
                                                <span class="badge bg-info">Quote Based</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $service['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($service['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($service['is_featured']): ?>
                                                <i class="fas fa-star text-warning"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($service['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editService(<?php echo $service['id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-info" onclick="manageServiceImage(<?php echo $service['id']; ?>)" title="Image">
                                                        <i class="fas fa-image"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success" onclick="createCustomService(<?php echo $service['id']; ?>)" title="Custom Service">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteService(<?php echo $service['id']; ?>)" title="Delete">
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
                        $baseUrl = ADMIN_URL . '/services.php?' . http_build_query(array_filter([
                            'search' => $search,
                            'category_id' => $category_id,
                            'status' => $status,
                            'featured' => $featured,
                            'has_fixed_price' => $has_fixed_price
                        ]));
                        echo generatePagination($page, $totalPages, $baseUrl);
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Custom Services Sidebar -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Custom Services</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentCustomServices)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentCustomServices as $customService): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($customService['name_' . CURRENT_LANGUAGE]); ?></h6>
                                        <p class="mb-1 small text-muted"><?php echo htmlspecialchars($customService['user_name'] ?? 'Unknown User'); ?></p>
                                        <small class="text-muted"><?php echo formatCurrency($customService['price']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?php echo $customService['status'] === 'pending' ? 'warning' : ($customService['status'] === 'paid' ? 'success' : 'secondary'); ?>">
                                            <?php echo ucfirst($customService['status']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo formatDate($customService['created_at']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-cog fa-2x mb-2"></i>
                            <p>No custom services yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="custom-services.php" class="btn btn-outline-primary w-100">
                            View All Custom Services
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Service Modal -->
<div class="modal fade" id="createServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="create_service">
                
                <div class="modal-header">
                    <h5 class="modal-title">Create New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name (Arabic)</label>
                            <input type="text" class="form-control" name="name_ar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (English)</label>
                            <input type="text" class="form-control" name="name_en" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name_' . CURRENT_LANGUAGE]); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="has_fixed_price" id="has_fixed_price" checked>
                                <label class="form-check-label" for="has_fixed_price">
                                    Has Fixed Price
                                </label>
                            </div>
                            <input type="number" class="form-control" name="price" id="service_price" step="0.01" min="0" placeholder="Service Price">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Short Description (Arabic)</label>
                            <textarea class="form-control" name="short_description_ar" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Short Description (English)</label>
                            <textarea class="form-control" name="short_description_en" rows="2"></textarea>
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
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                <label class="form-check-label" for="is_featured">
                                    Featured Service
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editServiceForm">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="update_service">
                <input type="hidden" name="service_id" id="editServiceId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name (Arabic)</label>
                            <input type="text" class="form-control" name="name_ar" id="editNameAr" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (English)</label>
                            <input type="text" class="form-control" name="name_en" id="editNameEn" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="editCategoryId" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name_' . CURRENT_LANGUAGE]); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="has_fixed_price" id="editHasFixedPrice">
                                <label class="form-check-label" for="editHasFixedPrice">
                                    Has Fixed Price
                                </label>
                            </div>
                            <input type="number" class="form-control" name="price" id="editPrice" step="0.01" min="0" placeholder="Service Price">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Short Description (Arabic)</label>
                            <textarea class="form-control" name="short_description_ar" id="editShortDescAr" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Short Description (English)</label>
                            <textarea class="form-control" name="short_description_en" id="editShortDescEn" rows="2"></textarea>
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
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="editIsFeatured">
                                <label class="form-check-label" for="editIsFeatured">
                                    Featured Service
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Custom Service Modal -->
<div class="modal fade" id="createCustomServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="create_custom_service">
                <input type="hidden" name="service_id" id="customServiceId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Create Custom Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Select User</label>
                            <select class="form-select" name="user_id" id="customServiceUserId" required>
                                <option value="">Select User</option>
                                <!-- Users will be loaded dynamically -->
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
                        <div class="col-md-6">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Days</label>
                            <input type="number" class="form-control" name="expiry_days" min="1" value="30">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Custom Service</button>
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
                <input type="hidden" name="service_id" id="imageServiceId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Upload Service Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Image</label>
                        <input type="file" class="form-control" name="service_image" accept="image/*" required>
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

<script>
// Handle fixed price checkbox
document.getElementById('has_fixed_price').addEventListener('change', function() {
    const priceField = document.getElementById('service_price');
    if (this.checked) {
        priceField.required = true;
        priceField.style.display = 'block';
    } else {
        priceField.required = false;
        priceField.style.display = 'none';
        priceField.value = '';
    }
});

// Handle edit fixed price checkbox
document.getElementById('editHasFixedPrice').addEventListener('change', function() {
    const priceField = document.getElementById('editPrice');
    if (this.checked) {
        priceField.required = true;
        priceField.style.display = 'block';
    } else {
        priceField.required = false;
        priceField.style.display = 'none';
        priceField.value = '';
    }
});

function editService(serviceId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/services/get.php?id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const service = data.service;
                document.getElementById('editServiceId').value = service.id;
                document.getElementById('editNameAr').value = service.name_ar;
                document.getElementById('editNameEn').value = service.name_en;
                document.getElementById('editCategoryId').value = service.category_id;
                document.getElementById('editHasFixedPrice').checked = service.has_fixed_price == 1;
                document.getElementById('editPrice').value = service.price || '';
                document.getElementById('editStatus').value = service.status;
                document.getElementById('editShortDescAr').value = service.short_description_ar || '';
                document.getElementById('editShortDescEn').value = service.short_description_en || '';
                document.getElementById('editDescAr').value = service.description_ar || '';
                document.getElementById('editDescEn').value = service.description_en || '';
                document.getElementById('editIsFeatured').checked = service.is_featured == 1;
                
                // Trigger price field visibility
                document.getElementById('editHasFixedPrice').dispatchEvent(new Event('change'));
                
                new bootstrap.Modal(document.getElementById('editServiceModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load service data', 'error');
        });
}

function deleteService(serviceId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the service!',
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
                <input type="hidden" name="action" value="delete_service">
                <input type="hidden" name="service_id" value="${serviceId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function manageServiceImage(serviceId) {
    document.getElementById('imageServiceId').value = serviceId;
    new bootstrap.Modal(document.getElementById('imageUploadModal')).show();
}

function createCustomService(serviceId = null) {
    if (serviceId) {
        document.getElementById('customServiceId').value = serviceId;
    }
    
    // Load users for dropdown
    fetch(`${window.SITE_CONFIG.apiUrl}/users/list.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const userSelect = document.getElementById('customServiceUserId');
                userSelect.innerHTML = '<option value="">Select User</option>';
                data.users.forEach(user => {
                    userSelect.innerHTML += `<option value="${user.id}">${user.first_name} ${user.last_name} (${user.email})</option>`;
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    
    new bootstrap.Modal(document.getElementById('createCustomServiceModal')).show();
}

function exportServices() {
    const params = new URLSearchParams(window.location.search);
    window.open(`${window.SITE_CONFIG.apiUrl}/services/export.php?${params.toString()}`, '_blank');
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize price field visibility
    document.getElementById('has_fixed_price').dispatchEvent(new Event('change'));
});
</script>

<?php include '../includes/footer.php'; ?>