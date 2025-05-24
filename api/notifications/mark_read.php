<?php
/**
 * Mark Notifications as Read API
 * TechSavvyGenLtd Project
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Fallback to POST data if JSON decode fails
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }

    // Validate CSRF token if present
    if (isset($input['csrf_token']) && !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
        http_response_code(403);
        $response['message'] = 'Invalid security token';
        echo json_encode($response);
        exit;
    }

    // Initialize notification class
    $notificationClass = new Notification();
    $userId = $_SESSION['user_id'];

    // Handle different actions
    $action = $input['action'] ?? 'mark_single';

    switch ($action) {
        case 'mark_single':
            // Mark single notification as read
            $notificationId = (int)($input['notification_id'] ?? 0);
            
            if ($notificationId <= 0) {
                http_response_code(400);
                $response['message'] = 'Invalid notification ID';
                break;
            }

            $result = $notificationClass->markAsRead($notificationId, $userId);
            
            if ($result['success']) {
                $response['success'] = true;
                $response['message'] = $result['message'];
                $response['notification_id'] = $notificationId;
            } else {
                http_response_code(400);
                $response['message'] = $result['message'];
            }
            break;

        case 'mark_multiple':
            // Mark multiple notifications as read
            $notificationIds = $input['notification_ids'] ?? [];
            
            if (!is_array($notificationIds) || empty($notificationIds)) {
                http_response_code(400);
                $response['message'] = 'No notification IDs provided';
                break;
            }

            $successCount = 0;
            $errors = [];

            foreach ($notificationIds as $notificationId) {
                $notificationId = (int)$notificationId;
                if ($notificationId > 0) {
                    $result = $notificationClass->markAsRead($notificationId, $userId);
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errors[] = "Notification {$notificationId}: {$result['message']}";
                    }
                }
            }

            if ($successCount > 0) {
                $response['success'] = true;
                $response['message'] = "Marked {$successCount} notifications as read";
                $response['success_count'] = $successCount;
                
                if (!empty($errors)) {
                    $response['errors'] = $errors;
                }
            } else {
                http_response_code(400);
                $response['message'] = 'Failed to mark any notifications as read';
                $response['errors'] = $errors;
            }
            break;

        case 'mark_all':
            // Mark all user notifications as read
            $result = $notificationClass->markAllAsRead($userId);
            
            if ($result['success']) {
                $response['success'] = true;
                $response['message'] = $result['message'];
                $response['updated_count'] = $result['updated_count'];
            } else {
                http_response_code(500);
                $response['message'] = $result['message'];
            }
            break;

        case 'mark_by_type':
            // Mark all notifications of specific type as read
            $type = cleanInput($input['type'] ?? '');
            
            if (empty($type)) {
                http_response_code(400);
                $response['message'] = 'Notification type is required';
                break;
            }

            try {
                $db = Database::getInstance();
                $updated = $db->update(
                    TBL_NOTIFICATIONS,
                    ['is_read' => true],
                    'user_id = ? AND type = ? AND is_read = ?',
                    [$userId, $type, false]
                );

                if ($updated > 0) {
                    $response['success'] = true;
                    $response['message'] = "Marked {$updated} {$type} notifications as read";
                    $response['updated_count'] = $updated;
                } else {
                    $response['success'] = true;
                    $response['message'] = 'No unread notifications of this type found';
                    $response['updated_count'] = 0;
                }

                logActivity('notifications_marked_read_by_type', "Marked {$updated} {$type} notifications as read", $userId);

            } catch (Exception $e) {
                error_log("Mark notifications by type failed: " . $e->getMessage());
                http_response_code(500);
                $response['message'] = 'Failed to mark notifications as read';
            }
            break;

        default:
            http_response_code(400);
            $response['message'] = 'Invalid action specified';
            break;
    }

} catch (Exception $e) {
    error_log("Mark notifications API error: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Internal server error';
}

echo json_encode($response);
?>