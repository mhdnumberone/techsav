<?php
/**
 * Main Configuration File
 * TechSavvyGenLtd Project
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting configuration
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug mode - set to false in production
define('DEBUG_MODE', true);

// Site configuration
define('SITE_NAME', 'TechSavvyGenLtd');
define('SITE_URL', 'http://localhost/techsav');
define('SITE_EMAIL', 'info@techsavvygenltd.com');
define('SITE_PHONE', '+1234567890');

// Directory paths
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ASSETS_PATH . '/uploads');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('LANGUAGES_PATH', ROOT_PATH . '/languages');
define('ADMIN_PATH', ROOT_PATH . '/admin');

// URL paths
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', ASSETS_URL . '/uploads');
define('ADMIN_URL', SITE_URL . '/admin');
define('API_URL', SITE_URL . '/api');

// Security configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Upload configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'zip', 'rar']);

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 25);

// Currency and formatting
define('DEFAULT_CURRENCY', 'USD');
define('CURRENCY_SYMBOL', '$');
define('DECIMAL_PLACES', 2);

// Language configuration
define('DEFAULT_LANGUAGE', 'ar');
define('SUPPORTED_LANGUAGES', ['ar', 'en']);

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');

// Payment configuration
define('STRIPE_PUBLISHABLE_KEY', '');
define('STRIPE_SECRET_KEY', '');
define('PAYPAL_CLIENT_ID', '');
define('PAYPAL_CLIENT_SECRET', '');
define('PAYPAL_MODE', 'sandbox'); // sandbox or live

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour

// Notification configuration
define('NOTIFICATION_TYPES', [
    'info' => 'Info',
    'success' => 'Success', 
    'warning' => 'Warning',
    'error' => 'Error',
    'order' => 'Order Update',
    'payment' => 'Payment',
    'promo' => 'Promotion'
]);

// Order status configuration
define('ORDER_STATUSES', [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'refunded' => 'Refunded'
]);

// Payment status configuration
define('PAYMENT_STATUSES', [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'failed' => 'Failed',
    'refunded' => 'Refunded'
]);

// Include required files
require_once CONFIG_PATH . '/constants.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/functions.php';

// Autoload classes
spl_autoload_register(function ($class) {
    $file = CLASSES_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize language system
$language = $_SESSION['language'] ?? $_COOKIE['language'] ?? DEFAULT_LANGUAGE;
if (!in_array($language, SUPPORTED_LANGUAGES)) {
    $language = DEFAULT_LANGUAGE;
}
define('CURRENT_LANGUAGE', $language);

// Load language file
$lang = [];
$lang_file = LANGUAGES_PATH . '/' . CURRENT_LANGUAGE . '/main.php';
if (file_exists($lang_file)) {
    $lang = require $lang_file;
}
$GLOBALS['lang'] = $lang;

// Set timezone
date_default_timezone_set('UTC');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

// Function to check if user is staff
function isStaff() {
    return isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin', 'staff']);
}

// Function to redirect
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit();
}

// Function to sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to generate CSRF token field
function csrfToken() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $_SESSION['csrf_token'] . '">';
}

// Function to verify CSRF token
function verifyCsrfToken() {
    return isset($_POST[CSRF_TOKEN_NAME]) && 
           hash_equals($_SESSION['csrf_token'], $_POST[CSRF_TOKEN_NAME]);
}
?>