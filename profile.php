<?php
/**
 * User Profile Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Initialize classes
$userClass = new User();
$orderClass = new Order();

// Get current user data
$user = $userClass->getById($_SESSION['user_id']);
if (!$user) {
    redirect(SITE_URL . '/logout.php');
}

// Initialize variables
$error_message = '';
$success_message = '';
$active_tab = $_GET['tab'] ?? 'profile';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error_message = __('invalid_token', 'Invalid security token. Please try again.');
    } else {
        // Handle profile update
        if (isset($_POST['update_profile'])) {
            $profileData = [
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city' => $_POST['city'] ?? '',
                'country' => $_POST['country'] ?? '',
                'postal_code' => $_POST['postal_code'] ?? '',
                'preferred_language' => $_POST['preferred_language'] ?? DEFAULT_LANGUAGE
            ];
            
            $result = $userClass->updateProfile($_SESSION['user_id'], $profileData);
            
            if ($result['success']) {
                $success_message = __('profile_updated', 'Profile updated successfully');
                $user = $userClass->getById($_SESSION['user_id']); // Refresh user data
            } else {
                $error_message = $result['message'];
            }
        }
        
        // Handle password change
        elseif (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error_message = __('password_fields_required', 'All password fields are required');
            } elseif ($newPassword !== $confirmPassword) {
                $error_message = __('passwords_dont_match', 'New passwords do not match');
            } else {
                $result = $userClass->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                
                if ($result['success']) {
                    $success_message = __('password_changed', 'Password changed successfully');
                } else {
                    $error_message = $result['message'];
                }
            }
        }
        
        // Handle profile image upload
        elseif (isset($_POST['upload_image']) && isset($_FILES['profile_image'])) {
            $result = $userClass->uploadProfileImage($_SESSION['user_id'], $_FILES['profile_image']);
            
            if ($result['success']) {
                $success_message = __('image_uploaded', 'Profile image updated successfully');
                $user = $userClass->getById($_SESSION['user_id']); // Refresh user data
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Get user orders
$userOrders = $orderClass->getUserOrders($_SESSION['user_id'], 1, 10);

// Page data
$page_title = __('my_profile', 'My Profile');
$body_class = 'profile-page';
?>

<?php include 'includes/header.php'; ?>

<section class="profile-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="profile-header mb-4">
                    <h2 class="profile-title"><?php echo __('my_profile', 'My Profile'); ?></h2>
                    <p class="profile-subtitle text-muted">
                        <?php echo __('profile_subtitle', 'Manage your account settings and preferences'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="profile-sidebar">
                    <div class="profile-card text-center mb-4">
                        <div class="profile-avatar position-relative">
                            <?php if ($user['profile_image']): ?>
                            <img src="<?php echo UPLOADS_URL . '/' . UPLOAD_PATH_USERS . '/' . $user['profile_image']; ?>" 
                                 alt="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                 class="rounded-circle img-fluid" width="120" height="120">
                            <?php else: ?>
                            <div class="avatar-placeholder rounded-circle mx-auto d-flex align-items-center justify-content-center">
                                <i class="fas fa-user fa-2x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" 
                                    data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        
                        <h5 class="profile-name mt-3 mb-1">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </h5>
                        <p class="profile-email text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'warning' : 'primary'); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                        
                        <?php if ($user['wallet_balance'] > 0): ?>
                        <div class="wallet-balance mt-3">
                            <div class="card bg-light">
                                <div class="card-body p-2">
                                    <small class="text-muted d-block"><?php echo __('wallet_balance', 'Wallet Balance'); ?></small>
                                    <strong class="text-success"><?php echo formatCurrency($user['wallet_balance']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="profile-nav">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" 
                                   href="?tab=profile">
                                    <i class="fas fa-user me-2"></i><?php echo __('profile_info', 'Profile Information'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_tab === 'orders' ? 'active' : ''; ?>" 
                                   href="?tab=orders">
                                    <i class="fas fa-shopping-bag me-2"></i><?php echo __('my_orders', 'My Orders'); ?>
                                    <?php if ($userOrders['total'] > 0): ?>
                                    <span class="badge bg-secondary ms-1"><?php echo $userOrders['total']; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_tab === 'security' ? 'active' : ''; ?>" 
                                   href="?tab=security">
                                    <i class="fas fa-shield-alt me-2"></i><?php echo __('security', 'Security'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i><?php echo __('logout', 'Logout'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Profile Content -->
            <div class="col-lg-9 col-md-8">
                <!-- Error/Success Messages -->
                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Profile Information Tab -->
                <?php if ($active_tab === 'profile'): ?>
                <div class="profile-content">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i><?php echo __('profile_information', 'Profile Information'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="profileForm">
                                <?php echo csrfToken(); ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">
                                            <?php echo __('first_name', 'First Name'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">
                                            <?php echo __('last_name', 'Last Name'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <?php echo __('email', 'Email'); ?>
                                        </label>
                                        <input type="email" class="form-control" id="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        <div class="form-text">
                                            <?php echo __('email_cannot_change', 'Email address cannot be changed'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">
                                            <?php echo __('phone', 'Phone'); ?>
                                        </label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">
                                        <?php echo __('address', 'Address'); ?>
                                    </label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="city" class="form-label">
                                            <?php echo __('city', 'City'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo htmlspecialchars($user['city']); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="country" class="form-label">
                                            <?php echo __('country', 'Country'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="country" name="country" 
                                               value="<?php echo htmlspecialchars($user['country']); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="postal_code" class="form-label">
                                            <?php echo __('postal_code', 'Postal Code'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                               value="<?php echo htmlspecialchars($user['postal_code']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="preferred_language" class="form-label">
                                        <?php echo __('preferred_language', 'Preferred Language'); ?>
                                    </label>
                                    <select class="form-select" id="preferred_language" name="preferred_language">
                                        <option value="ar" <?php echo $user['preferred_language'] === 'ar' ? 'selected' : ''; ?>>
                                            العربية
                                        </option>
                                        <option value="en" <?php echo $user['preferred_language'] === 'en' ? 'selected' : ''; ?>>
                                            English
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?php echo __('save_changes', 'Save Changes'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <?php elseif ($active_tab === 'orders'): ?>
                <div class="profile-content">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shopping-bag me-2"></i><?php echo __('my_orders', 'My Orders'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($userOrders['orders'])): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('order_number', 'Order #'); ?></th>
                                            <th><?php echo __('date', 'Date'); ?></th>
                                            <th><?php echo __('amount', 'Amount'); ?></th>
                                            <th><?php echo __('status', 'Status'); ?></th>
                                            <th><?php echo __('payment_status', 'Payment'); ?></th>
                                            <th><?php echo __('actions', 'Actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($userOrders['orders'] as $order): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo formatDate($order['created_at']); ?></td>
                                            <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/order-details.php?order=<?php echo $order['order_number']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <?php echo __('view_details', 'View Details'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($userOrders['pages'] > 1): ?>
                            <nav aria-label="Orders pagination">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $userOrders['pages']; $i++): ?>
                                    <li class="page-item <?php echo $i === $userOrders['current_page'] ? 'active' : ''; ?>">
                                        <a class="page-link" href="?tab=orders&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                            
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted"><?php echo __('no_orders_yet', 'No orders yet'); ?></h5>
                                <p class="text-muted"><?php echo __('no_orders_desc', 'When you place orders, they will appear here.'); ?></p>
                                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                                    <?php echo __('start_shopping', 'Start Shopping'); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <?php elseif ($active_tab === 'security'): ?>
                <div class="profile-content">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt me-2"></i><?php echo __('security_settings', 'Security Settings'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="passwordForm">
                                <?php echo csrfToken(); ?>
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">
                                        <?php echo __('current_password', 'Current Password'); ?>
                                    </label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">
                                        <?php echo __('new_password', 'New Password'); ?>
                                    </label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">
                                        <?php echo __('password_requirements', 'Password must be at least 8 characters with uppercase, lowercase, and number'); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <?php echo __('confirm_password', 'Confirm New Password'); ?>
                                    </label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="change_password" class="btn btn-danger">
                                        <i class="fas fa-key me-2"></i><?php echo __('change_password', 'Change Password'); ?>
                                    </button>
                                </div>
                            </form>
                            
                            <hr class="my-4">
                            
                            <!-- Account Information -->
                            <div class="account-info">
                                <h6 class="mb-3"><?php echo __('account_information', 'Account Information'); ?></h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong><?php echo __('member_since', 'Member Since'); ?>:</strong> 
                                           <?php echo formatDate($user['registration_date']); ?></p>
                                        <p><strong><?php echo __('last_login', 'Last Login'); ?>:</strong> 
                                           <?php echo $user['last_login'] ? formatDate($user['last_login']) : __('never', 'Never'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><?php echo __('account_status', 'Account Status'); ?>:</strong> 
                                           <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                               <?php echo ucfirst($user['status']); ?>
                                           </span>
                                        </p>
                                        <p><strong><?php echo __('email_verified', 'Email Verified'); ?>:</strong> 
                                           <span class="badge bg-<?php echo $user['is_verified'] ? 'success' : 'warning'; ?>">
                                               <?php echo $user['is_verified'] ? __('yes', 'Yes') : __('no', 'No'); ?>
                                           </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Upload Image Modal -->
<div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadImageModalLabel">
                    <?php echo __('upload_profile_image', 'Upload Profile Image'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrfToken(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">
                            <?php echo __('choose_image', 'Choose Image'); ?>
                        </label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" 
                               accept="image/*" required>
                        <div class="form-text">
                            <?php echo __('image_requirements', 'Maximum file size: 2MB. Supported formats: JPG, PNG, GIF'); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo __('cancel', 'Cancel'); ?>
                    </button>
                    <button type="submit" name="upload_image" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i><?php echo __('upload', 'Upload'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength indicator
    const newPasswordField = document.getElementById('new_password');
    if (newPasswordField) {
        newPasswordField.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            updatePasswordStrength(strength);
        });
    }
    
    // Confirm password validation
    const confirmPasswordField = document.getElementById('confirm_password');
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});

function checkPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    return score;
}

function updatePasswordStrength(score) {
    // Implementation for password strength indicator
    // This would update a visual indicator based on the score
}
</script>

<style>
.profile-avatar {
    width: 120px;
    height: 120px;
    margin: 0 auto;
}

.avatar-placeholder {
    width: 120px;
    height: 120px;
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
}

.profile-nav .nav-link {
    color: #6c757d;
    border: none;
    border-radius: 8px;
    margin-bottom: 5px;
}

.profile-nav .nav-link:hover {
    background-color: #f8f9fa;
    color: #495057;
}

.profile-nav .nav-link.active {
    background-color: #0d6efd;
    color: white;
}

.profile-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
}

.wallet-balance .card {
    background-color: rgba(255, 255, 255, 0.1);
    border: none;
}
</style>

<?php include 'includes/footer.php'; ?>