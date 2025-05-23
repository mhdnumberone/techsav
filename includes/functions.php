<?php
/**
 * Core Functions
 * TechSavvyGenLtd Project
 */

/**
 * Get translation text
 */
function __($key, $default = '') {
    global $lang;
    return $lang[$key] ?? $default;
}

/**
 * Get current language
 */
function getCurrentLanguage() {
    return CURRENT_LANGUAGE;
}

/**
 * Check if current language is RTL
 */
function isRTL() {
    return CURRENT_LANGUAGE === 'ar';
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = DEFAULT_CURRENCY) {
    $formatted = number_format($amount, DECIMAL_PLACES);
    return CURRENCY_SYMBOL . $formatted;
}

/**
 * Format date
 */
function formatDate($date, $format = DATE_FORMAT_DISPLAY) {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = DATETIME_FORMAT_DISPLAY) {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate unique order number
 */
function generateOrderNumber() {
    return 'ORD-' . date('Y') . '-' . strtoupper(generateRandomString(8));
}

/**
 * Generate unique invoice number
 */
function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . strtoupper(generateRandomString(8));
}

/**
 * Generate unique ticket number
 */
function generateTicketNumber() {
    return 'TKT-' . date('Y') . '-' . strtoupper(generateRandomString(8));
}

/**
 * Generate unique custom service link
 */
function generateCustomServiceLink() {
    return generateRandomString(64);
}

/**
 * Create slug from text
 */
function createSlug($text) {
    $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
    $text = preg_replace('/\s+/', '-', trim($text));
    return strtolower($text);
}

/**
 * Upload file
 */
function uploadFile($file, $destination, $allowed_types = ALLOWED_IMAGE_TYPES) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file selected'];
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }

    $filename = generateRandomString(16) . '.' . $file_extension;
    $upload_path = UPLOADS_PATH . '/' . $destination;
    
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }

    $full_path = $upload_path . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $full_path)) {
        return [
            'success' => true, 
            'filename' => $filename,
            'path' => $destination . '/' . $filename
        ];
    }

    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Delete file
 */
function deleteFile($path) {
    $full_path = UPLOADS_PATH . '/' . $path;
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    return false;
}

/**
 * Resize image
 */
function resizeImage($source, $destination, $width, $height) {
    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    $resized = imagecreatetruecolor($width, $height);
    
    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $width, $height, $transparent);
    }

    imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($resized, $destination, 90);
            break;
        case 'image/png':
            imagepng($resized, $destination);
            break;
        case 'image/gif':
            imagegif($resized, $destination);
            break;
    }

    imagedestroy($image);
    imagedestroy($resized);
    
    return true;
}

/**
 * Send email
 */
function sendEmail($to, $subject, $body, $is_html = true) {
    // This is a basic implementation. In production, use PHPMailer or similar
    $headers = [
        'From: ' . SITE_EMAIL,
        'Reply-To: ' . SITE_EMAIL,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    if ($is_html) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Log system activity
 */
function logActivity($action, $description = '', $user_id = null) {
    global $pdo;
    
    $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO " . TBL_SYSTEM_LOGS . " 
            (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $action, $description, $ip_address, $user_agent]);
    } catch (PDOException $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Get setting value
 */
function getSetting($key, $default = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM " . TBL_SETTINGS . " WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Set setting value
 */
function setSetting($key, $value, $group = 'general') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO " . TBL_SETTINGS . " (setting_key, setting_value, setting_group) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$key, $value, $group, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Generate pagination
 */
function generatePagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center">';
    
    // Previous page
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page - 1) . '">' . __('previous', 'Previous') . '</a></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next page
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page + 1) . '">' . __('next', 'Next') . '</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Create notification
 */
function createNotification($user_id, $title_ar, $title_en, $message_ar = '', $message_en = '', $type = 'general', $link = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO " . TBL_NOTIFICATIONS . " 
            (user_id, title_ar, title_en, message_ar, message_en, type, link) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $title_ar, $title_en, $message_ar, $message_en, $type, $link]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send push notification (placeholder for future implementation)
 */
function sendPushNotification($user_id, $title, $message, $data = []) {
    // Implementation for push notifications would go here
    // This could integrate with Firebase Cloud Messaging, OneSignal, etc.
    return true;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    return preg_match(REGEX_PHONE, $phone);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate password reset token
 */
function generatePasswordResetToken() {
    return generateRandomString(64);
}

/**
 * Get user IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Return JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get cart count for user
 */
function getCartCount($user_id = null) {
    global $pdo;
    
    $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) return 0;
    
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM " . TBL_CART_ITEMS . " WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get unread notifications count
 */
function getUnreadNotificationsCount($user_id = null) {
    global $pdo;
    
    $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) return 0;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM " . TBL_NOTIFICATIONS . " WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Calculate discount amount
 */
function calculateDiscount($price, $discount_type, $discount_value) {
    if ($discount_type === DISCOUNT_TYPE_PERCENTAGE) {
        return ($price * $discount_value) / 100;
    } else {
        return min($discount_value, $price);
    }
}

/**
 * Apply discount to price
 */
function applyDiscount($price, $discount_type, $discount_value) {
    $discount = calculateDiscount($price, $discount_type, $discount_value);
    return max(0, $price - $discount);
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
}

/**
 * Get current URL
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Truncate text
 */
function truncateText($text, $length = 150, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate star rating HTML
 */
function generateStarRating($rating, $max_stars = 5) {
    $html = '<div class="star-rating">';
    for ($i = 1; $i <= $max_stars; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}

/**
 * Clean and validate input
 */
function cleanInput($input) {
    if (is_array($input)) {
        return array_map('cleanInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if string contains only Arabic characters
 */
function isArabic($string) {
    return preg_match('/^[\x{0600}-\x{06FF}\s\d\p{P}]+$/u', $string);
}

/**
 * Check if string contains only English characters
 */
function isEnglish($string) {
    return preg_match('/^[a-zA-Z\s\d\p{P}]+$/u', $string);
}

/**
 * Format file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>