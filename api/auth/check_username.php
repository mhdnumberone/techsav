<?php
/**
 * Check Username Availability API
 * TechSavvyGenLtd Project
 */

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include configuration
require_once '../../config/config.php';

// Initialize response
$response = [
    'success' => false,
    'available' => false,
    'message' => '',
    'username' => ''
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = __('invalid_request_method', 'Invalid request method');
        http_response_code(405);
        echo json_encode($response);
        exit;
    }

    // Check if request is AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $response['message'] = __('invalid_request', 'Invalid request');
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Fallback to $_POST if JSON decode fails
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }

    $username = isset($input['username']) ? trim($input['username']) : '';
    $response['username'] = $username;

    // Validate username
    if (empty($username)) {
        $response['message'] = __('username_required', 'Username is required');
        echo json_encode($response);
        exit;
    }

    // Check username length
    if (strlen($username) < 3) {
        $response['message'] = __('username_too_short', 'Username must be at least 3 characters');
        echo json_encode($response);
        exit;
    }

    if (strlen($username) > 20) {
        $response['message'] = __('username_too_long', 'Username must be no more than 20 characters');
        echo json_encode($response);
        exit;
    }

    // Validate username format
    if (!preg_match(REGEX_USERNAME, $username)) {
        $response['message'] = __('username_invalid_format', 'Username can only contain letters, numbers, and underscores');
        echo json_encode($response);
        exit;
    }

    // Check for reserved usernames
    $reservedUsernames = [
        'admin', 'administrator', 'root', 'system', 'support', 'help',
        'info', 'contact', 'service', 'staff', 'team', 'api', 'www',
        'mail', 'email', 'noreply', 'no-reply', 'webmaster', 'hostmaster',
        'test', 'guest', 'anonymous', 'user', 'users', 'member', 'members',
        'techsavvy', 'techsavvygenltd', 'null', 'undefined', 'false', 'true'
    ];

    if (in_array(strtolower($username), $reservedUsernames)) {
        $response['message'] = __('username_reserved', 'This username is reserved and cannot be used');
        echo json_encode($response);
        exit;
    }

    // Initialize User class
    $userClass = new User();

    // Check if username exists
    $exists = $userClass->getByUsername($username);

    if ($exists) {
        $response['available'] = false;
        $response['message'] = __('username_taken', 'This username is already taken');
    } else {
        $response['success'] = true;
        $response['available'] = true;
        $response['message'] = __('username_available', 'Username is available');
    }

    // Log the check for security monitoring (optional)
    if (defined('LOG_USERNAME_CHECKS') && LOG_USERNAME_CHECKS) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        error_log("Username check: {$username} from IP: {$ip}");
    }

} catch (Exception $e) {
    error_log("Username check API error: " . $e->getMessage());
    
    $response['message'] = __('server_error', 'Server error occurred. Please try again.');
    http_response_code(500);
}

// Return JSON response
echo json_encode($response);
exit;
?>