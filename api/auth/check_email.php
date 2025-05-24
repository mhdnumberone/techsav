<?php
/**
 * Check Email Availability API
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
    'email' => ''
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

    $email = isset($input['email']) ? trim(strtolower($input['email'])) : '';
    $response['email'] = $email;

    // Validate email
    if (empty($email)) {
        $response['message'] = __('email_required', 'Email address is required');
        echo json_encode($response);
        exit;
    }

    // Basic email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = __('email_invalid_format', 'Please enter a valid email address');
        echo json_encode($response);
        exit;
    }

    // Additional email validation using regex
    if (!preg_match(REGEX_EMAIL, $email)) {
        $response['message'] = __('email_invalid_format', 'Please enter a valid email address');
        echo json_encode($response);
        exit;
    }

    // Check email length
    if (strlen($email) > 100) {
        $response['message'] = __('email_too_long', 'Email address is too long');
        echo json_encode($response);
        exit;
    }

    // Extract domain for additional validation
    $emailParts = explode('@', $email);
    if (count($emailParts) !== 2) {
        $response['message'] = __('email_invalid_format', 'Please enter a valid email address');
        echo json_encode($response);
        exit;
    }

    $domain = $emailParts[1];

    // Check for blocked domains (optional)
    $blockedDomains = [
        '10minutemail.com',
        'tempmail.org',
        'guerrillamail.com',
        'mailinator.com',
        'throwaway.email',
        'temp-mail.org',
        'yopmail.com'
    ];

    if (in_array(strtolower($domain), $blockedDomains)) {
        $response['message'] = __('email_domain_blocked', 'Temporary email addresses are not allowed');
        echo json_encode($response);
        exit;
    }

    // Check for common typos in popular domains
    $domainSuggestions = [
        'gmail.co' => 'gmail.com',
        'gmail.con' => 'gmail.com',
        'gmai.com' => 'gmail.com',
        'gmial.com' => 'gmail.com',
        'yahoo.co' => 'yahoo.com',
        'yahoo.con' => 'yahoo.com',
        'hotmai.com' => 'hotmail.com',
        'hotmal.com' => 'hotmail.com',
        'outlook.co' => 'outlook.com',
        'outlok.com' => 'outlook.com'
    ];

    $suggestedDomain = null;
    if (isset($domainSuggestions[strtolower($domain)])) {
        $suggestedDomain = $domainSuggestions[strtolower($domain)];
        $suggestedEmail = $emailParts[0] . '@' . $suggestedDomain;
        
        $response['suggestion'] = $suggestedEmail;
        $response['message'] = __('email_suggestion', 'Did you mean: ' . $suggestedEmail . '?');
        echo json_encode($response);
        exit;
    }

    // Basic domain validation (check if domain has MX record)
    if (function_exists('checkdnsrr')) {
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            $response['message'] = __('email_domain_invalid', 'Email domain does not exist');
            echo json_encode($response);
            exit;
        }
    }

    // Initialize User class
    $userClass = new User();

    // Check if email exists
    $exists = $userClass->getByEmail($email);

    if ($exists) {
        $response['available'] = false;
        $response['message'] = __('email_taken', 'This email address is already registered');
        
        // Optional: Check if the account is verified
        if (isset($exists['is_verified']) && !$exists['is_verified']) {
            $response['message'] = __('email_taken_unverified', 'This email is registered but not verified. Please check your inbox or contact support.');
            $response['unverified'] = true;
        }
    } else {
        $response['success'] = true;
        $response['available'] = true;
        $response['message'] = __('email_available', 'Email address is available');
    }

    // Log the check for security monitoring (optional)
    if (defined('LOG_EMAIL_CHECKS') && LOG_EMAIL_CHECKS) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $hashedEmail = hash('sha256', $email); // Log hashed email for privacy
        error_log("Email check: {$hashedEmail} from IP: {$ip}");
    }

} catch (Exception $e) {
    error_log("Email check API error: " . $e->getMessage());
    
    $response['message'] = __('server_error', 'Server error occurred. Please try again.');
    http_response_code(500);
}

// Return JSON response
echo json_encode($response);
exit;
?>