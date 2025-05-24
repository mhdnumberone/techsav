<?php
/**
 * Admin Products Management
 * TechSavvyGenLtd Project
 */

require_once '../config/config.php';

// Check if user is staff/admin
if (!isStaff()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize classes
$productClass = new Product();
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
            case 'create_product':
                $result = $productClass->create($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_product':
                $productId = (int)$_POST['product_id'];
                $result = $productClass->update($productId, $_POST);
                if ($result['success']) {
                    $message = 'Product updated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete_product':
                $productId = (int)$_POST['product_id'];
                $result = $productClass->delete($productId);
                if ($result['success']) {
                    $message = 'Product deleted successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'upload_image':
                $productId = (int)$_POST['product_id'];
                $isFeatured = !empty($_POST['is_featured']);
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $result = $productClass->uploadImage($productId, $_FILES['product_image'], $isFeatured);
                    if ($result['success']) {
                        $message = 'Image uploaded successfully';
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Please select a valid image file';
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
$page = max(1, (int)($_GET['page'] ?? 1));

// Build filters array
$filters = array_filter([
    'search' => $search,
    'category_id' => $category_id,
    'status' => $status,
    'featured' => $featured
]);

// Get products
$productsData = $productClass->getAll($page, ADMIN_ITEMS_PER_PAGE, $filters);
$products = $productsData['products'];
$totalPages = $productsData['pages'];
$totalProducts = $productsData['total'];

// Get categories for dropdown
$categoriesData = $categoryClass->getAll(1, 100);
$categories = $categoriesData['categories'] ?? [];

$page_title = 'Products Management';
$body_class = 'admin-page products-page';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">Products Management</h1>
                <p class="admin-subtitle">Manage your product catalog</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProductModal">
                <i class="fas fa-plus me-2"></i>Add New Product
            </button>
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

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
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
                            <option value="out_of_stock" <?php echo $status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="featured">
                            <option value="">All Products</option>
                            <option value="1" <?php echo $featured === '1' ? 'selected' : ''; ?>>Featured Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-md-1">
                        <a href="products.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Products (<?php echo $totalProducts; ?>)</h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-success" onclick="exportProducts()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No products found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $product['featured_image'] ? UPLOADS_URL . '/' . UPLOAD_PATH_PRODUCTS . '/' . $product['featured_image'] : ASSETS_URL . '/images/placeholder.jpg'; ?>" 
                                                 class="rounded me-2" width="50" height="50" alt="Product" style="object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($product['name_' . CURRENT_LANGUAGE]); ?></div>
                                                <small class="text-muted"><?php echo truncateText($product['description_' . CURRENT_LANGUAGE], 50); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>
                                        <div>
                                            <?php if ($product['sale_price']): ?>
                                            <span class="text-decoration-line-through text-muted small"><?php echo formatCurrency($product['price']); ?></span>
                                            <div class="fw-bold text-success"><?php echo formatCurrency($product['sale_price']); ?></div>
                                            <?php else: ?>
                                            <div class="fw-bold"><?php echo formatCurrency($product['price']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($product['is_digital']): ?>
                                        <span class="badge bg-info">Digital</span>
                                        <?php else: ?>
                                        <span class="<?php echo $product['stock'] <= 5 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $product['stock']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['status'] === 'active' ? 'success' : ($product['status'] === 'out_of_stock' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product['is_featured']): ?>
                                        <i class="fas fa-star text-warning"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($product['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editProduct(<?php echo $product['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="manageImages(<?php echo $product['id']; ?>)" title="Images">
                                                <i class="fas fa-images"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)" title="Delete">
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
                $baseUrl = ADMIN_URL . '/products.php?' . http_build_query(array_filter([
                    'search' => $search,
                    'category_id' => $category_id,
                    'status' => $status,
                    'featured' => $featured
                ]));
                echo generatePagination($page, $totalPages, $baseUrl);
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Product Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="create_product">
                
                <div class="modal-header">
                    <h5 class="modal-title">Create New Product</h5>
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
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sale Price (Optional)</label>
                            <input type="number" class="form-control" name="sale_price" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" min="0" value="0">
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
                                <input class="form-check-input" type="checkbox" name="is_digital" id="is_digital">
                                <label class="form-check-label" for="is_digital">
                                    Digital Product
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                <label class="form-check-label" for="is_featured">
                                    Featured Product
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editProductForm">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="product_id" id="editProductId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
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
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" id="editPrice" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sale Price</label>
                            <input type="number" class="form-control" name="sale_price" id="editSalePrice" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" id="editStock" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="out_of_stock">Out of Stock</option>
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
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_digital" id="editIsDigital">
                                <label class="form-check-label" for="editIsDigital">
                                    Digital Product
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="editIsFeatured">
                                <label class="form-check-label" for="editIsFeatured">
                                    Featured Product
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Management Modal -->
<div class="modal fade" id="imageManagementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Product Images</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" class="mb-4">
                    <?php echo csrfToken(); ?>
                    <input type="hidden" name="action" value="upload_image">
                    <input type="hidden" name="product_id" id="imageProductId">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Select Image</label>
                            <input type="file" class="form-control" name="product_image" accept="image/*" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="uploadIsFeatured">
                                <label class="form-check-label" for="uploadIsFeatured">
                                    Featured Image
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-2">Upload</button>
                        </div>
                    </div>
                </form>
                
                <div id="productImages">
                    <!-- Images will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editProduct(productId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/products/get.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                document.getElementById('editProductId').value = product.id;
                document.getElementById('editNameAr').value = product.name_ar;
                document.getElementById('editNameEn').value = product.name_en;
                document.getElementById('editCategoryId').value = product.category_id;
                document.getElementById('editPrice').value = product.price;
                document.getElementById('editSalePrice').value = product.sale_price || '';
                document.getElementById('editStock').value = product.stock;
                document.getElementById('editStatus').value = product.status;
                document.getElementById('editShortDescAr').value = product.short_description_ar || '';
                document.getElementById('editShortDescEn').value = product.short_description_en || '';
                document.getElementById('editDescAr').value = product.description_ar || '';
                document.getElementById('editDescEn').value = product.description_en || '';
                document.getElementById('editIsDigital').checked = product.is_digital == 1;
                document.getElementById('editIsFeatured').checked = product.is_featured == 1;
                
                new bootstrap.Modal(document.getElementById('editProductModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load product data', 'error');
        });
}

function deleteProduct(productId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the product and all its images!',
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
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="product_id" value="${productId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function manageImages(productId) {
    document.getElementById('imageProductId').value = productId;
    
    // Load existing images
    fetch(`${window.SITE_CONFIG.apiUrl}/products/images.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const imagesContainer = document.getElementById('productImages');
                imagesContainer.innerHTML = '';
                
                if (data.images.length > 0) {
                    data.images.forEach(image => {
                        const imageDiv = document.createElement('div');
                        imageDiv.className = 'col-md-3 mb-3';
                        imageDiv.innerHTML = `
                            <div class="card">
                                <img src="${window.SITE_CONFIG.uploadsUrl}/${window.SITE_CONFIG.productPath}/${image.image_path}" 
                                     class="card-img-top" style="height: 150px; object-fit: cover;">
                                <div class="card-body p-2 text-center">
                                    <button class="btn btn-sm btn-danger" onclick="deleteImage(${image.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        imagesContainer.appendChild(imageDiv);
                    });
                } else {
                    imagesContainer.innerHTML = '<p class="text-center text-muted">No images uploaded yet</p>';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    
    new bootstrap.Modal(document.getElementById('imageManagementModal')).show();
}

function exportProducts() {
    const params = new URLSearchParams(window.location.search);
    window.open(`${window.SITE_CONFIG.apiUrl}/products/export.php?${params.toString()}`, '_blank');
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include '../includes/footer.php'; ?>