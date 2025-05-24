<?php
/**
 * Get Notifications API
 * TechSavvyGenLtd Project
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => 'Unknown error occurred'
];

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        $response['message'] = 'Authentication required';
        echo json_encode($response);
        exit;
    }

    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    // Initialize notification class
    $notificationClass = new Notification();
    $userId = $_SESSION['user_id'];

    // Get parameters
    $action = $_GET['action'] ?? 'list';
    $notificationId = (int)($_GET['id'] ?? 0);

    switch ($action) {
        case 'single':
            // Get single notification
            if ($notificationId <= 0) {
                http_response_code(400);
                $response['message'] = 'Invalid notification ID';
                break;
            }

            $notification = $notificationClass->getById($notificationId);
            
            if ($notification && $notification['user_id'] == $userId) {
                $response['success'] = true;
                $response['notification'] = [
                    'id' => $notification['id'],
                    'title' => $notification['title_' . CURRENT_LANGUAGE],
                    'message' => $notification['message_' . CURRENT_LANGUAGE],
                    'type' => $notification['type'],
                    'is_read' => (bool)$notification['is_read'],
                    'link' => $notification['link'],
                    'created_at' => $notification['created_at'],
                    'formatted_date' => formatDateTime($notification['created_at'])
                ];
            } else {
                http_response_code(404);
                $response['message'] = 'Notification not found';
            }
            break;

        case 'list':
            // Get user notifications with pagination and filters
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            $unreadOnly = filter_var($_GET['unread_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $type = cleanInput($_GET['type'] ?? '');

            // Get notifications
            $notificationsData = $notificationClass->getUserNotifications($userId, $page, $limit, $unreadOnly);
            
            // Filter by type if specified
            if (!empty($type) && !empty($notificationsData['notifications'])) {
                $notificationsData['notifications'] = array_filter(
                    $notificationsData['notifications'],
                    function($notification) use ($type) {
                        return $notification['type'] === $type;
                    }
                );
                $notificationsData['notifications'] = array_values($notificationsData['notifications']);
            }

            // Format notifications for response
            $formattedNotifications = [];
            foreach ($notificationsData['notifications'] as $notification) {
                $formattedNotifications[] = [
                    'id' => $notification['id'],
                    'title' => $notification['title_' . CURRENT_LANGUAGE],
                    'message' => $notification['message_' . CURRENT_LANGUAGE],
                    'type' => $notification['type'],
                    'is_read' => (bool)$notification['is_read'],
                    'link' => $notification['link'],
                    'created_at' => $notification['created_at'],
                    'formatted_date' => formatDateTime($notification['created_at']),
                    'time_ago' => timeAgo($notification['created_at'])
                ];
            }

            $response['success'] = true;
            $response['notifications'] = $formattedNotifications;
            $response['pagination'] = [
                'current_page' => $notificationsData['current_page'],
                'total_pages' => $notificationsData['pages'],
                'total_notifications' => $notificationsData['total'],
                'per_page' => $limit
            ];
            break;

        case 'recent':
            // Get recent unread notifications (for dropdown/popup)
            $limit = min(10, max(1, (int)($_GET['limit'] ?? 5)));
            $recentNotifications = $notificationClass->getRecentUnread($userId, $limit);

            $formattedNotifications = [];
            foreach ($recentNotifications as $notification) {
                $formattedNotifications[] = [
                    'id' => $notification['id'],
                    'title' => $notification['title_' . CURRENT_LANGUAGE],
                    'message' => truncateText($notification['message_' . CURRENT_LANGUAGE], 100),
                    'type' => $notification['type'],
                    'link' => $notification['link'],
                    'created_at' => $notification['created_at'],
                    'time_ago' => timeAgo($notification['created_at'])
                ];
            }

            $response['success'] = true;
            $response['notifications'] = $formattedNotifications;
            break;

        case 'count':
            // Get unread notifications count
            $unreadCount = $notificationClass->getUnreadCount($userId);
            
            $response['success'] = true;
            $response['unread_count'] = $unreadCount;
            break;

        case 'stats':
            // Get notification statistics for user
            try {
                $db = Database::getInstance();
                
                $stats = $db->fetch(
                    "SELECT 
                        COUNT(*) as total_notifications,
                        COUNT(CASE WHEN is_read = 1 THEN 1 END) as read_notifications,
                        COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_notifications,
                        COUNT(CASE WHEN type = ? THEN 1 END) as info_notifications,
                        COUNT(CASE WHEN type = ? THEN 1 END) as success_notifications,
                        COUNT(CASE WHEN type = ? THEN 1 END) as warning_notifications,
                        COUNT(CASE WHEN type = ? THEN 1 END) as error_notifications,
                        COUNT(CASE WHEN type = ? THEN 1 END) as order_notifications,
                        COUNT(CASE WHEN type = ? THEN 1 END) as payment_notifications,
                        COUNT(CASE WHEN type = ? THEN 1 END) as promo_notifications
                    FROM " . TBL_NOTIFICATIONS . " 
                    WHERE user_id = ?",
                    [
                        NOTIFICATION_INFO,
                        NOTIFICATION_SUCCESS,
                        NOTIFICATION_WARNING,
                        NOTIFICATION_ERROR,
                        NOTIFICATION_ORDER,
                        NOTIFICATION_PAYMENT,
                        NOTIFICATION_PROMO,
                        $userId
                    ]
                );

                $response['success'] = true;
                $response['stats'] = $stats;

            } catch (Exception $e) {
                error_log("Get notification stats failed: " . $e->getMessage());
                http_response_code(500);
                $response['message'] = 'Failed to get notification statistics';
            }
            break;

        case 'types':
            // Get available notification types with counts
            try {
                $db = Database::getInstance();
                
                $typesData = $db->fetchAll(
                    "SELECT 
                        type,
                        COUNT(*) as total_count,
                        COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_count
                    FROM " . TBL_NOTIFICATIONS . " 
                    WHERE user_id = ?
                    GROUP BY type
                    ORDER BY total_count DESC",
                    [$userId]
                );

                $types = [];
                foreach ($typesData as $typeData) {
                    $types[] = [
                        'type' => $typeData['type'],
                        'name' => ucfirst(str_replace('_', ' ', $typeData['type'])),
                        'total_count' => (int)$typeData['total_count'],
                        'unread_count' => (int)$typeData['unread_count']
                    ];
                }

                $response['success'] = true;
                $response['types'] = $types;

            } catch (Exception $e) {
                error_log("Get notification types failed: " . $e->getMessage());
                http_response_code(500);
                $response['message'] = 'Failed to get notification types';
            }
            break;

        default:
            http_response_code(400);
            $response['message'] = 'Invalid action specified';
            break;
    }

} catch (Exception $e) {
    error_log("Get notifications API error: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Internal server error';
}

echo json_encode($response);

// Helper functions
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function formatDateTime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}
?>