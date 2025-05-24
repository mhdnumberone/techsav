<?php
/**
 * Admin Notifications Management
 * TechSavvyGenLtd Project
 */

require_once '../config/config.php';

// Check if user is staff/admin
if (!isStaff()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize classes
$notificationClass = new Notification();
$userClass = new User();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_notification':
                $result = $notificationClass->create($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'send_to_user':
                $userId = (int)$_POST['user_id'];
                $titleAr = cleanInput($_POST['title_ar']);
                $titleEn = cleanInput($_POST['title_en']);
                $messageAr = cleanInput($_POST['message_ar']);
                $messageEn = cleanInput($_POST['message_en']);
                $type = cleanInput($_POST['type'] ?? NOTIFICATION_INFO);
                $link = cleanInput($_POST['link'] ?? '');
                
                $result = $notificationClass->sendToUser($userId, $titleAr, $titleEn, $messageAr, $messageEn, $type, $link);
                if ($result['success']) {
                    $message = 'Notification sent to user successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'send_to_all':
                $titleAr = cleanInput($_POST['title_ar']);
                $titleEn = cleanInput($_POST['title_en']);
                $messageAr = cleanInput($_POST['message_ar']);
                $messageEn = cleanInput($_POST['message_en']);
                $type = cleanInput($_POST['type'] ?? NOTIFICATION_INFO);
                $link = cleanInput($_POST['link'] ?? '');
                
                $result = $notificationClass->sendToAllUsers($titleAr, $titleEn, $messageAr, $messageEn, $type, $link);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'send_to_role':
                $role = cleanInput($_POST['role']);
                $titleAr = cleanInput($_POST['title_ar']);
                $titleEn = cleanInput($_POST['title_en']);
                $messageAr = cleanInput($_POST['message_ar']);
                $messageEn = cleanInput($_POST['message_en']);
                $type = cleanInput($_POST['type'] ?? NOTIFICATION_INFO);
                $link = cleanInput($_POST['link'] ?? '');
                
                $result = $notificationClass->sendToUsersByRole($role, $titleAr, $titleEn, $messageAr, $messageEn, $type, $link);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete_notification':
                $notificationId = (int)$_POST['notification_id'];
                $result = $notificationClass->delete($notificationId);
                if ($result['success']) {
                    $message = 'Notification deleted successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'cleanup_old':
                $daysOld = (int)($_POST['days_old'] ?? 90);
                $result = $notificationClass->cleanupOldNotifications($daysOld);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'send_system_notification':
                $titleAr = cleanInput($_POST['title_ar']);
                $titleEn = cleanInput($_POST['title_en']);
                $messageAr = cleanInput($_POST['message_ar']);
                $messageEn = cleanInput($_POST['message_en']);
                $type = cleanInput($_POST['type'] ?? NOTIFICATION_INFO);
                $link = cleanInput($_POST['link'] ?? '');
                
                $result = $notificationClass->sendSystemNotification($titleAr, $titleEn, $messageAr, $messageEn, $type, $link);
                if ($result['success']) {
                    $message = 'System notification created successfully';
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$is_read = $_GET['is_read'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

// Build filters array
$filters = array_filter([
    'search' => $search,
    'type' => $type,
    'is_read' => $is_read !== '' ? (bool)$is_read : null,
    'user_id' => $user_id,
    'date_from' => $date_from,
    'date_to' => $date_to
]);

// Get notifications
$notificationsData = $notificationClass->getAllNotifications($page, ADMIN_ITEMS_PER_PAGE, $filters);
$notifications = $notificationsData['notifications'];
$totalPages = $notificationsData['pages'];
$totalNotifications = $notificationsData['total'];

// Get notification statistics
$notificationStats = $notificationClass->getNotificationStatistics();

// Get system notifications
$systemNotifications = $notificationClass->getSystemNotifications(10);

$page_title = 'Notifications Management';
$body_class = 'admin-page notifications-page';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">Notifications Management</h1>
                <p class="admin-subtitle">Manage and send notifications to users</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendNotificationModal">
                    <i class="fas fa-paper-plane me-2"></i>Send Notification
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkNotificationModal">
                    <i class="fas fa-users me-2"></i>Bulk Send
                </button>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                    <i class="fas fa-broom me-2"></i>Cleanup
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
                                <i class="fas fa-bell fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($notificationStats['total_notifications'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Total Notifications</div>
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
                                <i class="fas fa-envelope fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($notificationStats['unread_notifications'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Unread Notifications</div>
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
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0"><?php echo number_format($notificationStats['read_notifications'] ?? 0); ?></div>
                                <div class="stat-label text-muted small">Read Notifications</div>
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
                                <i class="fas fa-percentage fa-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value h4 mb-0">
                                    <?php 
                                    $total = $notificationStats['total_notifications'] ?? 0;
                                    $read = $notificationStats['read_notifications'] ?? 0;
                                    echo $total > 0 ? number_format(($read / $total) * 100, 1) . '%' : '0%';
                                    ?>
                                </div>
                                <div class="stat-label text-muted small">Read Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Types Breakdown -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search notifications..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    <option value="info" <?php echo $type === 'info' ? 'selected' : ''; ?>>Info</option>
                                    <option value="success" <?php echo $type === 'success' ? 'selected' : ''; ?>>Success</option>
                                    <option value="warning" <?php echo $type === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                    <option value="error" <?php echo $type === 'error' ? 'selected' : ''; ?>>Error</option>
                                    <option value="order" <?php echo $type === 'order' ? 'selected' : ''; ?>>Order</option>
                                    <option value="payment" <?php echo $type === 'payment' ? 'selected' : ''; ?>>Payment</option>
                                    <option value="promo" <?php echo $type === 'promo' ? 'selected' : ''; ?>>Promo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="is_read">
                                    <option value="">All Status</option>
                                    <option value="1" <?php echo $is_read === '1' ? 'selected' : ''; ?>>Read</option>
                                    <option value="0" <?php echo $is_read === '0' ? 'selected' : ''; ?>>Unread</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Notification Types Chart -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Notification Types</h5>
                    </div>
                    <div class="card-body">
                        <div class="notification-types-chart">
                            <div class="type-item d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon bg-info bg-opacity-10 text-info rounded p-2 me-3">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <span>Info</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($notificationStats['info_notifications'] ?? 0); ?></span>
                            </div>
                            <div class="type-item d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon bg-success bg-opacity-10 text-success rounded p-2 me-3">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <span>Success</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($notificationStats['success_notifications'] ?? 0); ?></span>
                            </div>
                            <div class="type-item d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon bg-warning bg-opacity-10 text-warning rounded p-2 me-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <span>Warning</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($notificationStats['warning_notifications'] ?? 0); ?></span>
                            </div>
                            <div class="type-item d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon bg-danger bg-opacity-10 text-danger rounded p-2 me-3">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <span>Error</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($notificationStats['error_notifications'] ?? 0); ?></span>
                            </div>
                            <div class="type-item d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <span>Order</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($notificationStats['order_notifications'] ?? 0); ?></span>
                            </div>
                            <div class="type-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon bg-secondary bg-opacity-10 text-secondary rounded p-2 me-3">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <span>Payment</span>
                                </div>
                                <span class="fw-bold"><?php echo number_format($notificationStats['payment_notifications'] ?? 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Notifications (<?php echo $totalNotifications; ?>)</h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="refreshNotifications()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                    <button class="btn btn-outline-success" onclick="exportNotifications()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Notification</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notifications)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No notifications found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input notification-checkbox" value="<?php echo $notification['id']; ?>">
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($notification['title_' . CURRENT_LANGUAGE]); ?></div>
                                            <small class="text-muted"><?php echo truncateText($notification['message_' . CURRENT_LANGUAGE], 100); ?></small>
                                            <?php if ($notification['link']): ?>
                                            <br><a href="<?php echo htmlspecialchars($notification['link']); ?>" class="text-decoration-none small" target="_blank">
                                                <i class="fas fa-external-link-alt me-1"></i>View Link
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($notification['user_id']): ?>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($notification['email']); ?></small>
                                        </div>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                        echo $notification['type'] === 'success' ? 'success' : 
                                             ($notification['type'] === 'error' ? 'danger' : 
                                              ($notification['type'] === 'warning' ? 'warning' : 
                                               ($notification['type'] === 'order' ? 'primary' : 
                                                ($notification['type'] === 'payment' ? 'info' : 'secondary'))));
                                        ?>">
                                            <?php echo ucfirst($notification['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($notification['is_read']): ?>
                                        <span class="badge bg-success">Read</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning">Unread</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo formatDate($notification['created_at']); ?></div>
                                        <small class="text-muted"><?php echo formatTime($notification['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewNotification(<?php echo $notification['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($notification['user_id'] && !$notification['is_read']): ?>
                                            <button class="btn btn-outline-success" onclick="markAsRead(<?php echo $notification['id']; ?>)" title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger" onclick="deleteNotification(<?php echo $notification['id']; ?>)" title="Delete">
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
                $baseUrl = ADMIN_URL . '/notifications.php?' . http_build_query(array_filter([
                    'search' => $search,
                    'type' => $type,
                    'is_read' => $is_read,
                    'user_id' => $user_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ]));
                echo generatePagination($page, $totalPages, $baseUrl);
                ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- System Notifications Sidebar -->
        <?php if (!empty($systemNotifications)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent System Notifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($systemNotifications as $sysNotification): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card border-left-<?php echo $sysNotification['type'] === 'error' ? 'danger' : ($sysNotification['type'] === 'warning' ? 'warning' : 'primary'); ?>">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($sysNotification['title_' . CURRENT_LANGUAGE]); ?></h6>
                                        <p class="card-text small"><?php echo htmlspecialchars($sysNotification['message_' . CURRENT_LANGUAGE]); ?></p>
                                        <small class="text-muted"><?php echo formatDateTime($sysNotification['created_at']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Send Notification Modal -->
<div class="modal fade" id="sendNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="send_to_user">
                
                <div class="modal-header">
                    <h5 class="modal-title">Send Notification to User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select User</label>
                        <select class="form-select" name="user_id" id="notificationUserId" required>
                            <option value="">Select User</option>
                            <!-- Users will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title (Arabic)</label>
                            <input type="text" class="form-control" name="title_ar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title (English)</label>
                            <input type="text" class="form-control" name="title_en" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message (Arabic)</label>
                            <textarea class="form-control" name="message_ar" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message (English)</label>
                            <textarea class="form-control" name="message_en" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="info">Info</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                                <option value="order">Order</option>
                                <option value="payment">Payment</option>
                                <option value="promo">Promo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link (Optional)</label>
                            <input type="url" class="form-control" name="link" placeholder="https://...">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Notification Modal -->
<div class="modal fade" id="bulkNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Bulk Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="nav nav-tabs" id="bulkNotificationTab" role="tablist">
                    <button class="nav-link active" id="all-users-tab" data-bs-toggle="tab" data-bs-target="#all-users" type="button">All Users</button>
                    <button class="nav-link" id="by-role-tab" data-bs-toggle="tab" data-bs-target="#by-role" type="button">By Role</button>
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button">System</button>
                </div>
                
                <div class="tab-content mt-3">
                    <!-- Send to All Users -->
                    <div class="tab-pane fade show active" id="all-users">
                        <form method="POST">
                            <?php echo csrfToken(); ?>
                            <input type="hidden" name="action" value="send_to_all">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Title (Arabic)</label>
                                    <input type="text" class="form-control" name="title_ar" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Title (English)</label>
                                    <input type="text" class="form-control" name="title_en" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message (Arabic)</label>
                                    <textarea class="form-control" name="message_ar" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message (English)</label>
                                    <textarea class="form-control" name="message_en" rows="3"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type">
                                        <option value="info">Info</option>
                                        <option value="success">Success</option>
                                        <option value="warning">Warning</option>
                                        <option value="error">Error</option>
                                        <option value="promo">Promo</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Link (Optional)</label>
                                    <input type="url" class="form-control" name="link" placeholder="https://...">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success w-100">Send to All Users</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Send by Role -->
                    <div class="tab-pane fade" id="by-role">
                        <form method="POST">
                            <?php echo csrfToken(); ?>
                            <input type="hidden" name="action" value="send_to_role">
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">User Role</label>
                                    <select class="form-select" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="customer">Customers</option>
                                        <option value="staff">Staff</option>
                                        <option value="admin">Admins</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Title (Arabic)</label>
                                    <input type="text" class="form-control" name="title_ar" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Title (English)</label>
                                    <input type="text" class="form-control" name="title_en" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message (Arabic)</label>
                                    <textarea class="form-control" name="message_ar" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message (English)</label>
                                    <textarea class="form-control" name="message_en" rows="3"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type">
                                        <option value="info">Info</option>
                                        <option value="success">Success</option>
                                        <option value="warning">Warning</option>
                                        <option value="error">Error</option>
                                        <option value="promo">Promo</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Link (Optional)</label>
                                    <input type="url" class="form-control" name="link" placeholder="https://...">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success w-100">Send to Role</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- System Notification -->
                    <div class="tab-pane fade" id="system">
                        <form method="POST">
                            <?php echo csrfToken(); ?>
                            <input type="hidden" name="action" value="send_system_notification">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Title (Arabic)</label>
                                    <input type="text" class="form-control" name="title_ar" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Title (English)</label>
                                    <input type="text" class="form-control" name="title_en" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message (Arabic)</label>
                                    <textarea class="form-control" name="message_ar" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message (English)</label>
                                    <textarea class="form-control" name="message_en" rows="3"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type">
                                        <option value="info">Info</option>
                                        <option value="success">Success</option>
                                        <option value="warning">Warning</option>
                                        <option value="error">Error</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Link (Optional)</label>
                                    <input type="url" class="form-control" name="link" placeholder="https://...">
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        System notifications are not sent to specific users and appear in the admin dashboard.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-info w-100">Create System Notification</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cleanup Modal -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfToken(); ?>
                <input type="hidden" name="action" value="cleanup_old">
                
                <div class="modal-header">
                    <h5 class="modal-title">Cleanup Old Notifications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Delete notifications older than (days):</label>
                        <input type="number" class="form-control" name="days_old" value="90" min="1" max="365" required>
                        <div class="form-text">Only read notifications will be deleted</div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. Make sure to backup your data if needed.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Cleanup Notifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notification Details Modal -->
<div class="modal fade" id="notificationDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="notificationDetailsContent">
                <!-- Notification details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewNotification(notificationId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/notifications/get.php?action=single&id=${notificationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = data.notification;
                let notificationHtml = `
                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-sm">
                                <tr><td><strong>Title (AR):</strong></td><td>${notification.title}</td></tr>
                                <tr><td><strong>Title (EN):</strong></td><td>${notification.title}</td></tr>
                                <tr><td><strong>Type:</strong></td><td><span class="badge bg-primary">${notification.type}</span></td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-${notification.is_read ? 'success' : 'warning'}">${notification.is_read ? 'Read' : 'Unread'}</span></td></tr>
                                <tr><td><strong>Created:</strong></td><td>${notification.formatted_date}</td></tr>
                                ${notification.link ? `<tr><td><strong>Link:</strong></td><td><a href="${notification.link}" target="_blank">${notification.link}</a></td></tr>` : ''}
                            </table>
                        </div>
                        <div class="col-md-4">
                            <h6>Messages</h6>
                            <div class="card mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">Arabic</h6>
                                    <p class="card-text">${notification.message || 'No message'}</p>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">English</h6>
                                    <p class="card-text">${notification.message || 'No message'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('notificationDetailsContent').innerHTML = notificationHtml;
                new bootstrap.Modal(document.getElementById('notificationDetailsModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load notification details', 'error');
        });
}

function deleteNotification(notificationId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the notification!',
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
                <input type="hidden" name="action" value="delete_notification">
                <input type="hidden" name="notification_id" value="${notificationId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function markAsRead(notificationId) {
    fetch(`${window.SITE_CONFIG.apiUrl}/notifications/mark-read.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'mark_single',
            notification_id: notificationId,
            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success', 'Notification marked as read', 'success');
            window.location.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to mark notification as read', 'error');
    });
}

function refreshNotifications() {
    window.location.reload();
}

function exportNotifications() {
    const params = new URLSearchParams(window.location.search);
    window.open(`${window.SITE_CONFIG.apiUrl}/notifications/export.php?${params.toString()}`, '_blank');
}

// Load users for notification modal
document.addEventListener('DOMContentLoaded', function() {
    const sendNotificationModal = document.getElementById('sendNotificationModal');
    sendNotificationModal.addEventListener('show.bs.modal', function() {
        fetch(`${window.SITE_CONFIG.apiUrl}/users/list.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const userSelect = document.getElementById('notificationUserId');
                    userSelect.innerHTML = '<option value="">Select User</option>';
                    data.users.forEach(user => {
                        userSelect.innerHTML += `<option value="${user.id}">${user.first_name} ${user.last_name} (${user.email})</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });
    
    // Select all checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.notification-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
});

// Helper function to truncate text
function truncateText(text, length) {
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
}
</script>

<?php include '../includes/footer.php'; ?>