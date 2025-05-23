<?php
/**
 * Notification Management Class
 * TechSavvyGenLtd Project
 */

class Notification {
    private $db;
    private $table = TBL_NOTIFICATIONS;
    private $usersTable = TBL_USERS;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new notification
     */
    public function create($data) {
        // Validate required fields
        $required = ['title_ar', 'title_en'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field {$field} is required"];
            }
        }
        
        try {
            // Prepare notification data
            $notificationData = [
                'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
                'title_ar' => cleanInput($data['title_ar']),
                'title_en' => cleanInput($data['title_en']),
                'message_ar' => cleanInput($data['message_ar'] ?? ''),
                'message_en' => cleanInput($data['message_en'] ?? ''),
                'type' => $data['type'] ?? NOTIFICATION_INFO,
                'is_read' => false,
                'link' => cleanInput($data['link'] ?? ''),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $notificationId = $this->db->insert($this->table, $notificationData);
            
            if ($notificationId) {
                logActivity('notification_created', "Notification created: {$data['title_en']}", $_SESSION['user_id'] ?? null);
                
                return [
                    'success' => true,
                    'message' => 'Notification created successfully',
                    'notification_id' => $notificationId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create notification'];
            
        } catch (Exception $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Notification creation failed. Please try again.'];
        }
    }
    
    /**
     * Send notification to specific user
     */
    public function sendToUser($userId, $titleAr, $titleEn, $messageAr = '', $messageEn = '', $type = NOTIFICATION_INFO, $link = '') {
        return $this->create([
            'user_id' => $userId,
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'type' => $type,
            'link' => $link
        ]);
    }
    
    /**
     * Send notification to all users
     */
    public function sendToAllUsers($titleAr, $titleEn, $messageAr = '', $messageEn = '', $type = NOTIFICATION_INFO, $link = '') {
        try {
            $this->db->beginTransaction();
            
            // Get all active users
            $users = $this->db->fetchAll(
                "SELECT id FROM {$this->usersTable} WHERE status = ?",
                [USER_STATUS_ACTIVE]
            );
            
            $successCount = 0;
            foreach ($users as $user) {
                $result = $this->sendToUser($user['id'], $titleAr, $titleEn, $messageAr, $messageEn, $type, $link);
                if ($result['success']) {
                    $successCount++;
                }
            }
            
            $this->db->commit();
            
            logActivity('bulk_notification_sent', "Bulk notification sent to {$successCount} users", $_SESSION['user_id'] ?? null);
            
            return [
                'success' => true,
                'message' => "Notification sent to {$successCount} users",
                'sent_count' => $successCount
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Bulk notification failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Bulk notification failed. Please try again.'];
        }
    }
    
    /**
     * Send notification to users by role
     */
    public function sendToUsersByRole($role, $titleAr, $titleEn, $messageAr = '', $messageEn = '', $type = NOTIFICATION_INFO, $link = '') {
        try {
            $this->db->beginTransaction();
            
            // Get users by role
            $users = $this->db->fetchAll(
                "SELECT id FROM {$this->usersTable} WHERE role = ? AND status = ?",
                [$role, USER_STATUS_ACTIVE]
            );
            
            $successCount = 0;
            foreach ($users as $user) {
                $result = $this->sendToUser($user['id'], $titleAr, $titleEn, $messageAr, $messageEn, $type, $link);
                if ($result['success']) {
                    $successCount++;
                }
            }
            
            $this->db->commit();
            
            logActivity('role_notification_sent', "Notification sent to {$successCount} users with role {$role}", $_SESSION['user_id'] ?? null);
            
            return [
                'success' => true,
                'message' => "Notification sent to {$successCount} users",
                'sent_count' => $successCount
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Role notification failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Role notification failed. Please try again.'];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId = null) {
        try {
            $conditions = ['id = ?'];
            $params = [$notificationId];
            
            // If userId provided, ensure user owns this notification
            if ($userId) {
                $conditions[] = 'user_id = ?';
                $params[] = $userId;
            }
            
            $updated = $this->db->update(
                $this->table,
                ['is_read' => true],
                implode(' AND ', $conditions),
                $params
            );
            
            if ($updated) {
                return ['success' => true, 'message' => 'Notification marked as read'];
            }
            
            return ['success' => false, 'message' => 'Notification not found or already read'];
            
        } catch (Exception $e) {
            error_log("Mark notification as read failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark notification as read'];
        }
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($userId) {
        try {
            $updated = $this->db->update(
                $this->table,
                ['is_read' => true],
                'user_id = ? AND is_read = ?',
                [$userId, false]
            );
            
            logActivity('notifications_marked_read', "All notifications marked as read", $userId);
            
            return [
                'success' => true,
                'message' => 'All notifications marked as read',
                'updated_count' => $updated
            ];
            
        } catch (Exception $e) {
            error_log("Mark all notifications as read failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark notifications as read'];
        }
    }
    
    /**
     * Delete notification
     */
    public function delete($notificationId, $userId = null) {
        try {
            $conditions = ['id = ?'];
            $params = [$notificationId];
            
            // If userId provided, ensure user owns this notification
            if ($userId) {
                $conditions[] = 'user_id = ?';
                $params[] = $userId;
            }
            
            $deleted = $this->db->delete(
                $this->table,
                implode(' AND ', $conditions),
                $params
            );
            
            if ($deleted) {
                logActivity('notification_deleted', "Notification ID {$notificationId} deleted", $_SESSION['user_id'] ?? null);
                return ['success' => true, 'message' => 'Notification deleted successfully'];
            }
            
            return ['success' => false, 'message' => 'Notification not found'];
            
        } catch (Exception $e) {
            error_log("Notification deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Notification deletion failed. Please try again.'];
        }
    }
    
    /**
     * Get notification by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT n.*, u.first_name, u.last_name
                    FROM {$this->table} n
                    LEFT JOIN {$this->usersTable} u ON n.user_id = u.id
                    WHERE n.id = ?";
            
            return $this->db->fetch($sql, [$id]);
            
        } catch (Exception $e) {
            error_log("Get notification by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $page = 1, $limit = 20, $unreadOnly = false) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = ['user_id = ?'];
            $params = [$userId];
            
            if ($unreadOnly) {
                $conditions[] = 'is_read = ?';
                $params[] = false;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            
            // Get total count
            $total = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} {$whereClause}",
                $params
            );
            
            // Get notifications
            $sql = "SELECT * FROM {$this->table} 
                    {$whereClause}
                    ORDER BY created_at DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $notifications = $this->db->fetchAll($sql, $params);
            
            return [
                'notifications' => $notifications,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get user notifications failed: " . $e->getMessage());
            return ['notifications' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount($userId) {
        try {
            return (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND is_read = ?",
                [$userId, false]
            );
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get recent unread notifications for user (for dropdown/popup)
     */
    public function getRecentUnread($userId, $limit = 5) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = ? AND is_read = ? 
                    ORDER BY created_at DESC 
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [$userId, false]);
            
        } catch (Exception $e) {
            error_log("Get recent unread notifications failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all notifications (admin function)
     */
    public function getAllNotifications($page = 1, $limit = ADMIN_ITEMS_PER_PAGE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            // Type filter
            if (!empty($filters['type'])) {
                $conditions[] = "n.type = ?";
                $params[] = $filters['type'];
            }
            
            // User filter
            if (!empty($filters['user_id'])) {
                $conditions[] = "n.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            // Read status filter
            if (isset($filters['is_read'])) {
                $conditions[] = "n.is_read = ?";
                $params[] = $filters['is_read'] ? 1 : 0;
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(n.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(n.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $searchTerm = "%{$filters['search']}%";
                $conditions[] = "(n.title_ar LIKE ? OR n.title_en LIKE ? OR n.message_ar LIKE ? OR n.message_en LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $totalQuery = "SELECT COUNT(*) FROM {$this->table} n 
                          LEFT JOIN {$this->usersTable} u ON n.user_id = u.id
                          {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get notifications
            $sql = "SELECT n.*, u.first_name, u.last_name, u.email
                    FROM {$this->table} n
                    LEFT JOIN {$this->usersTable} u ON n.user_id = u.id
                    {$whereClause}
                    ORDER BY n.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $notifications = $this->db->fetchAll($sql, $params);
            
            return [
                'notifications' => $notifications,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all notifications failed: " . $e->getMessage());
            return ['notifications' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStatistics() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_notifications,
                        COUNT(CASE WHEN is_read = 1 THEN 1 END) as read_notifications,
                        COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_notifications,
                        COUNT(CASE WHEN type = 'info' THEN 1 END) as info_notifications,
                        COUNT(CASE WHEN type = 'success' THEN 1 END) as success_notifications,
                        COUNT(CASE WHEN type = 'warning' THEN 1 END) as warning_notifications,
                        COUNT(CASE WHEN type = 'error' THEN 1 END) as error_notifications,
                        COUNT(CASE WHEN type = 'order' THEN 1 END) as order_notifications,
                        COUNT(CASE WHEN type = 'payment' THEN 1 END) as payment_notifications,
                        COUNT(CASE WHEN type = 'promo' THEN 1 END) as promo_notifications
                    FROM {$this->table}";
            
            return $this->db->fetch($sql);
            
        } catch (Exception $e) {
            error_log("Get notification statistics failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications($daysOld = 90) {
        try {
            $date = date('Y-m-d', strtotime("-{$daysOld} days"));
            
            $deleted = $this->db->delete(
                $this->table,
                'created_at < ? AND is_read = ?',
                [$date, true]
            );
            
            if ($deleted > 0) {
                logActivity('notifications_cleanup', "Cleaned up {$deleted} old notifications", $_SESSION['user_id'] ?? null);
            }
            
            return [
                'success' => true,
                'message' => "Cleaned up {$deleted} old notifications",
                'deleted_count' => $deleted
            ];
            
        } catch (Exception $e) {
            error_log("Notification cleanup failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Notification cleanup failed'];
        }
    }
    
    /**
     * Get notifications by type
     */
    public function getNotificationsByType($type, $limit = 10) {
        try {
            $sql = "SELECT n.*, u.first_name, u.last_name
                    FROM {$this->table} n
                    LEFT JOIN {$this->usersTable} u ON n.user_id = u.id
                    WHERE n.type = ?
                    ORDER BY n.created_at DESC
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [$type]);
            
        } catch (Exception $e) {
            error_log("Get notifications by type failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send system notification (no specific user)
     */
    public function sendSystemNotification($titleAr, $titleEn, $messageAr = '', $messageEn = '', $type = NOTIFICATION_INFO, $link = '') {
        return $this->create([
            'user_id' => null,
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'type' => $type,
            'link' => $link
        ]);
    }
    
    /**
     * Get system notifications (admin notifications)
     */
    public function getSystemNotifications($limit = 10) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id IS NULL 
                    ORDER BY created_at DESC 
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql);
            
        } catch (Exception $e) {
            error_log("Get system notifications failed: " . $e->getMessage());
            return [];
        }
    }
}
?>