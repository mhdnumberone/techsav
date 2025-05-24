<?php
/**
 * Resend Email Verification API
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

    // Rate limiting check
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitKey = 'resend_verification_' . md5($ip);
    $rateLimitFile = sys_get_temp_dir() . '/' . $rateLimitKey;
    
    // Check if rate limit file exists and is recent (within last 5 minutes)
    if (file_exists($rateLimitFile) && (time() - filemtime($rateLimitFile)) < 300) {
        $response['message'] = __('rate_limit_exceeded', 'Please wait before requesting another verification email');
        http_response_code(429);
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

    // Initialize User class
    $userClass = new User();

    // Check if email exists and is unverified
    $user = $userClass->getByEmail($email);

    if (!$user) {
        // Don't reveal that email doesn't exist for security
        $response['success'] = true;
        $response['message'] = __('verification_email_sent', 'If the email exists and is unverified, a verification email has been sent');
        echo json_encode($response);
        exit;
    }

    // Check if user is already verified
    if ($user['is_verified']) {
        $response['message'] = __('email_already_verified', 'This email address is already verified');
        echo json_encode($response);
        exit;
    }

    // Check if user account is active
    if ($user['status'] !== USER_STATUS_ACTIVE) {
        $response['message'] = __('account_inactive', 'This account is not active. Please contact support');
        echo json_encode($response);
        exit;
    }

    // Generate new verification token
    $verificationToken = generateRandomString(64);
    
    // Update user with new verification token
    $db = Database::getInstance();
    $updated = $db->update(
        TBL_USERS,
        ['verification_token' => $verificationToken],
        'id = ?',
        [$user['id']]
    );

    if (!$updated) {
        $response['message'] = __('server_error', 'Failed to generate verification token. Please try again');
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    // Send verification email
    $emailSent = sendVerificationEmail($user['id'], $email, $verificationToken);

    if ($emailSent) {
        // Create rate limit file
        file_put_contents($rateLimitFile, time());
        
        // Log the resend action
        logActivity('verification_email_resent', "Verification email resent to {$email}", $user['id']);
        
        $response['success'] = true;
        $response['message'] = __('verification_email_sent', 'Verification email has been sent to your inbox');
    } else {
        $response['message'] = __('email_send_failed', 'Failed to send verification email. Please try again later');
        http_response_code(500);
    }

} catch (Exception $e) {
    error_log("Resend verification API error: " . $e->getMessage());
    
    $response['message'] = __('server_error', 'Server error occurred. Please try again');
    http_response_code(500);
}

// Return JSON response
echo json_encode($response);
exit;

/**
 * Send verification email
 */
function sendVerificationEmail($userId, $email, $token) {
    try {
        $verificationUrl = SITE_URL . "/verify-email.php?token={$token}";
        
        $subject = __('email_verification_subject', 'Please verify your email address') . ' - ' . SITE_NAME;
        
        $body = generateVerificationEmailBody($email, $verificationUrl);
        
        return sendEmail($email, $subject, $body);
        
    } catch (Exception $e) {
        error_log("Verification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate verification email body
 */
function generateVerificationEmailBody($email, $verificationUrl) {
    $siteName = SITE_NAME;
    $siteUrl = SITE_URL;
    
    // HTML email template
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f8f9fa; }
            .button { display: inline-block; padding: 12px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$siteName}</h1>
                <h2>Email Verification</h2>
            </div>
            <div class='content'>
                <h3>Welcome to {$siteName}!</h3>
                <p>Thank you for creating an account with us. To complete your registration and verify your email address, please click the button below:</p>
                
                <div style='text-align: center;'>
                    <a href='{$verificationUrl}' class='button'>Verify Email Address</a>
                </div>
                
                <p>If the button above doesn't work, you can copy and paste the following link into your browser:</p>
                <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 3px;'>{$verificationUrl}</p>
                
                <p><strong>Important:</strong> This verification link will expire in 24 hours for security reasons.</p>
                
                <p>If you didn't create an account with {$siteName}, you can safely ignore this email.</p>
            </div>
            <div class='footer'>
                <p>This email was sent to {$email}</p>
                <p>&copy; " . date('Y') . " {$siteName}. All rights reserved.</p>
                <p><a href='{$siteUrl}'>Visit our website</a></p>
            </div>
        </div>
    </body>
    </html>";
    
    return $htmlBody;
}

/**
 * Generate random string for tokens
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
?>