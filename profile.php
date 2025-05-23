<?php
/**
 * User Profile Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize classes
$userClass = new User();
$orderClass = new Order();
$notificationClass = new Notification();

$userId = $_SESSION['user_id'];
$activeTab = $_GET['tab'] ?? 'profile';

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error_message = __('invalid_token', 'Invalid security token. Please try again.');
    } else {
        // Handle profile update
        if (isset($_POST['update_profile'])) {
            $updateData = [
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city' => $_POST['city'] ?? '',
                'country' => $_POST['country'] ?? '',
                'postal_code' => $_POST['postal_code'] ?? '',
                'preferred_language' => $_POST['preferred_language'] ?? DEFAULT_LANGUAGE
            ];
            
            $result = $userClass->updateProfile($userId, $updateData);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        }
        
        // Handle password change
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error_message = __('password_fields_required', 'All password fields are required.');
            } elseif ($newPassword !== $confirmPassword) {
                $error_message = __('password_mismatch', 'New passwords do not match.');
            } else {
                $result = $userClass->changePassword($userId, $currentPassword, $newPassword);
                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
            }
        }
        
        // Handle profile image upload
        if (isset($_POST['upload_image']) && isset($_FILES['profile_image'])) {
            $result = $userClass->uploadProfileImage($userId, $_FILES['profile_image']);
            if ($result['success']) {
                $success_message = __('image_uploaded', 'Profile image updated successfully.');
            } else {
                $error_message = $result['message'];
            }
        }
        
        // Handle mark all notifications as read
        if (isset($_POST['mark_all_read'])) {
            $result = $notificationClass->markAllAsRead($userId);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Get user data
$user = $userClass->getById($userId);

// Get user orders
$ordersData = $orderClass->getUserOrders($userId, 1, 10);
$orders = $ordersData['orders'];

// Get user notifications
$notificationsData = $notificationClass->getUserNotifications($userId, 1, 10);
$notifications = $notificationsData['notifications'];

// Page data
$page_title = __('profile', 'Profile');
$body_class = 'profile-page';
?>

<?php include 'includes/header.php'; ?>

<section class="profile-section py-5">
    <div class="container">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="profile-sidebar">
                    <!-- User Info Card -->
                    <div class="user-info-card text-center mb-4">
                        <div class="user-avatar mb-3">
                            <img src="<?php echo ASSETS_URL; ?>/images/users/<?php echo $user['profile_image'] ?? 'default.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" 
                                 class="rounded-circle" width="100" height="100">
                        </div>
                        <h5 class="user-name mb-1">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </h5>
                        <p class="user-email text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'warning' : 'primary'); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <div class="profile-nav">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/profile.php?tab=profile">
                                    <i class="fas fa-user me-2"></i>
                                    <?php echo __('personal_info', 'Personal Information'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab === 'orders' ? 'active' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/profile.php?tab=orders">
                                    <i class="fas fa-shopping-bag me-2"></i>
                                    <?php echo __('my_orders', 'My Orders'); ?>
                                    <?php if (count($orders) > 0): ?>
                                    <span class="badge bg-secondary"><?php echo count($orders); ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab === 'wallet' ? 'active' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/profile.php?tab=wallet">
                                    <i class="fas fa-wallet me-2"></i>
                                    <?php echo __('wallet', 'Wallet'); ?>
                                    <small class="text-muted">(<?php echo formatCurrency($user['wallet_balance']); ?>)</small>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab === 'notifications' ? 'active' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/profile.php?tab=notifications">
                                    <i class="fas fa-bell me-2"></i>
                                    <?php echo __('notifications', 'Notifications'); ?>
                                    <?php $unreadCount = getUnreadNotificationsCount($userId); ?>
                                    <?php if ($unreadCount > 0): ?>
                                    <span class="badge bg-danger"><?php echo $unreadCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab === 'security' ? 'active' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/profile.php?tab=security">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    <?php echo __('security', 'Security'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Profile Content -->
                <div class="profile-content">
                    <?php if ($activeTab === 'profile'): ?>
                    <!-- Personal Information Tab -->
                    <div class="content-section">
                        <div class="section-header d-flex justify-content-between align-items-center mb-4">
                            <h4 class="section-title"><?php echo __('personal_information', 'Personal Information'); ?></h4>
                        </div>
                        
                        <!-- Profile Image Upload -->
                        <div class="profile-image-section mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo __('profile_image', 'Profile Image'); ?></h6>
                                    <form method="POST" action="" enctype="multipart/form-data">
                                        <?php echo csrfToken(); ?>
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <img src="<?php echo ASSETS_URL; ?>/images/users/<?php echo $user['profile_image'] ?? 'default.png'; ?>" 
                                                     alt="Profile" class="rounded-circle" width="80" height="80">
                                            </div>
                                            <div class="col">
                                                <input type="file" class="form-control" name="profile_image" accept="image/*">
                                                <small class="form-text text-muted">
                                                    <?php echo __('image_upload_help', 'Upload JPG, PNG or GIF image. Max size: 2MB'); ?>
                                                </small>
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" name="upload_image" class="btn btn-primary">
                                                    <?php echo __('upload', 'Upload'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personal Information Form -->
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="">
                                    <?php echo csrfToken(); ?>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label"><?php echo __('first_name', 'First Name'); ?></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label"><?php echo __('last_name', 'Last Name'); ?></label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="username" class="form-label"><?php echo __('username', 'Username'); ?></label>
                                            <input type="text" class="form-control" id="username" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                            <small class="form-text text-muted"><?php echo __('username_readonly', 'Username cannot be changed'); ?></small>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="email" class="form-label"><?php echo __('email', 'Email'); ?></label>
                                            <input type="email" class="form-control" id="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                            <small class="form-text text-muted"><?php echo __('email_readonly', 'Email cannot be changed'); ?></small>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label"><?php echo __('phone', 'Phone'); ?></label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="preferred_language" class="form-label"><?php echo __('preferred_language', 'Preferred Language'); ?></label>
                                            <select class="form-select" id="preferred_language" name="preferred_language">
                                                <option value="ar" <?php echo $user['preferred_language'] === 'ar' ? 'selected' : ''; ?>>العربية</option>
                                                <option value="en" <?php echo $user['preferred_language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label for="address" class="form-label"><?php echo __('address', 'Address'); ?></label>
                                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="city" class="form-label"><?php echo __('city', 'City'); ?></label>
                                            <input type="text" class="form-control" id="city" name="city" 
                                                   value="<?php echo htmlspecialchars($user['city']); ?>">
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="country" class="form-label"><?php echo __('country', 'Country'); ?></label>
                                            <input type="text" class="form-control" id="country" name="country" 
                                                   value="<?php echo htmlspecialchars($user['country']); ?>">
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="postal_code" class="form-label"><?php echo __('postal_code', 'Postal Code'); ?></label>
                                            <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                                   value="<?php echo htmlspecialchars($user['postal_code']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            <?php echo __('save_changes', 'Save Changes'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($activeTab === 'orders'): ?>
                    <!-- Orders Tab -->
                    <div class="content-section">
                        <div class="section-header d-flex justify-content-between align-items-center mb-4">
                            <h4 class="section-title"><?php echo __('my_orders', 'My Orders'); ?></h4>
                        </div>
                        
                        <?php if (!empty($orders)): ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1"><?php echo __('order', 'Order'); ?> #<?php echo $order['order_number']; ?></h6>
                                            <p class="text-muted mb-1">
                                                <?php echo formatDateTime($order['created_at']); ?>
                                            </p>
                                            <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'primary'); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                            <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?> ms-2">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <h6 class="mb-1"><?php echo formatCurrency($order['total_amount']); ?></h6>
                                            <a href="<?php echo SITE_URL; ?>/order-details.php?order=<?php echo $order['order_number']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <?php echo __('view_details', 'View Details'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($ordersData['total'] > count($orders)): ?>
                        <div class="text-center mt-4">
                            <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-outline-primary">
                                <?php echo __('view_all_orders', 'View All Orders'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <div class="empty-state text-center py-5">
                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                            <h5><?php echo __('no_orders', 'No Orders Yet'); ?></h5>
                            <p class="text-muted"><?php echo __('no_orders_desc', 'You haven\'t placed any orders yet. Start shopping to see your orders here.'); ?></p>
                            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                                <?php echo __('start_shopping', 'Start Shopping'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php elseif ($activeTab === 'wallet'): ?>
                    <!-- Wallet Tab -->
                    <div class="content-section">
                        <div class="section-header d-flex justify-content-between align-items-center mb-4">
                            <h4 class="section-title"><?php echo __('wallet', 'Wallet'); ?></h4>
                        </div>
                        
                        <div class="wallet-overview mb-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo __('current_balance', 'Current Balance'); ?></h5>
                                    <h2 class="card-text"><?php echo formatCurrency($user['wallet_balance']); ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wallet-actions mb-4">
                            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addFundsModal">
                                <i class="fas fa-plus me-2"></i>
                                <?php echo __('add_funds', 'Add Funds'); ?>
                            </button>
                            <a href="<?php echo SITE_URL; ?>/wallet-history.php" class="btn btn-outline-primary">
                                <i class="fas fa-history me-2"></i>
                                <?php echo __('transaction_history', 'Transaction History'); ?>
                            </a>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo __('recent_transactions', 'Recent Transactions'); ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted"><?php echo __('wallet_history_note', 'View your complete wallet transaction history by clicking the Transaction History button above.'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($activeTab === 'notifications'): ?>
                    <!-- Notifications Tab -->
                    <div class="content-section">
                        <div class="section-header d-flex justify-content-between align-items-center mb-4">
                            <h4 class="section-title"><?php echo __('notifications', 'Notifications'); ?></h4>
                            <?php if (getUnreadNotificationsCount($userId) > 0): ?>
                            <form method="POST" action="" class="d-inline">
                                <?php echo csrfToken(); ?>
                                <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                                    <?php echo __('mark_all_read', 'Mark All as Read'); ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($notifications)): ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                            <div class="card mb-3 <?php echo !$notification['is_read'] ? 'border-primary' : ''; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($notification['title_' . CURRENT_LANGUAGE]); ?>
                                                <?php if (!$notification['is_read']): ?>
                                                <span class="badge bg-primary ms-2"><?php echo __('new', 'New'); ?></span>
                                                <?php endif; ?>
                                            </h6>
                                            <?php if ($notification['message_' . CURRENT_LANGUAGE]): ?>
                                            <p class="text-muted mb-2">
                                                <?php echo htmlspecialchars($notification['message_' . CURRENT_LANGUAGE]); ?>
                                            </p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <?php echo formatDateTime($notification['created_at']); ?>
                                            </small>
                                        </div>
                                        <?php if ($notification['link']): ?>
                                        <a href="<?php echo $notification['link']; ?>" class="btn btn-sm btn-outline-primary">
                                            <?php echo __('view', 'View'); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($notificationsData['total'] > count($notifications)): ?>
                        <div class="text-center mt-4">
                            <a href="<?php echo SITE_URL; ?>/notifications.php" class="btn btn-outline-primary">
                                <?php echo __('view_all_notifications', 'View All Notifications'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <div class="empty-state text-center py-5">
                            <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                            <h5><?php echo __('no_notifications', 'No Notifications'); ?></h5>
                            <p class="text-muted"><?php echo __('no_notifications_desc', 'You don\'t have any notifications yet.'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php elseif ($activeTab === 'security'): ?>
                    <!-- Security Tab -->
                    <div class="content-section">
                        <div class="section-header mb-4">
                            <h4 class="section-title"><?php echo __('security_settings', 'Security Settings'); ?></h4>
                        </div>
                        
                        <!-- Change Password -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo __('change_password', 'Change Password'); ?></h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <?php echo csrfToken(); ?>
                                    
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="current_password" class="form-label"><?php echo __('current_password', 'Current Password'); ?></label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label"><?php echo __('new_password', 'New Password'); ?></label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$" required>
                                            <small class="form-text text-muted">
                                                <?php echo __('password_requirements', 'At least 8 characters with uppercase, lowercase, and number'); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label"><?php echo __('confirm_password', 'Confirm Password'); ?></label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" name="change_password" class="btn btn-primary">
                                            <i class="fas fa-shield-alt me-2"></i>
                                            <?php echo __('change_password', 'Change Password'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Account Information -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo __('account_information', 'Account Information'); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong><?php echo __('registration_date', 'Registration Date'); ?>:</strong></p>
                                        <p class="text-muted"><?php echo formatDateTime($user['registration_date']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><?php echo __('last_login', 'Last Login'); ?>:</strong></p>
                                        <p class="text-muted">
                                            <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : __('never', 'Never'); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><?php echo __('email_verified', 'Email Verified'); ?>:</strong></p>
                                        <p class="text-muted">
                                            <?php if ($user['is_verified']): ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                <?php echo __('verified', 'Verified'); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <?php echo __('not_verified', 'Not Verified'); ?>
                                            </span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><?php echo __('account_status', 'Account Status'); ?>:</strong></p>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add Funds Modal -->
<div class="modal fade" id="addFundsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('add_funds', 'Add Funds'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3"><?php echo __('add_funds_desc', 'Add funds to your wallet to make payments faster and easier.'); ?></p>
                <form id="addFundsForm">
                    <div class="mb-3">
                        <label for="amount" class="form-label"><?php echo __('amount', 'Amount'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                            <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label"><?php echo __('payment_method', 'Payment Method'); ?></label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value=""><?php echo __('select_payment_method', 'Select Payment Method'); ?></option>
                            <option value="stripe"><?php echo __('credit_card', 'Credit Card'); ?></option>
                            <option value="paypal">PayPal</option>
                            <option value="bank"><?php echo __('bank_transfer', 'Bank Transfer'); ?></option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
                <button type="button" class="btn btn-primary" onclick="processAddFunds()"><?php echo __('add_funds', 'Add Funds'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword && confirmPassword) {
        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    }
    
    // Profile image preview
    const profileImageInput = document.querySelector('input[name="profile_image"]');
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.profile-image-section img');
                    if (img) {
                        img.src = e.target.result;
                    }
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
});

// Add funds functionality
function processAddFunds() {
    const form = document.getElementById('addFundsForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // This would integrate with your payment processing system
    Swal.fire({
        icon: 'info',
        title: 'Payment Processing',
        text: 'This feature will be integrated with your payment processor.',
        confirmButtonText: 'OK'
    }).then(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addFundsModal'));
        modal.hide();
    });
}
</script>

<?php include 'includes/footer.php'; ?>