<?php
/**
 * Application Constants
 * TechSavvyGenLtd Project
 */

// Application version
define('APP_VERSION', '1.0.0');
define('APP_NAME', 'TechSavvyGenLtd');
define('APP_DESCRIPTION', 'Professional web development services and digital solutions');

// Database table names
define('TBL_USERS', 'users');
define('TBL_CATEGORIES', 'categories');
define('TBL_PRODUCTS', 'products');
define('TBL_PRODUCT_IMAGES', 'product_images');
define('TBL_SERVICES', 'services');
define('TBL_CUSTOM_SERVICES', 'custom_services');
define('TBL_ORDERS', 'orders');
define('TBL_ORDER_ITEMS', 'order_items');
define('TBL_PAYMENTS', 'payments');
define('TBL_INVOICES', 'invoices');
define('TBL_REVIEWS', 'reviews');
define('TBL_ACHIEVEMENTS', 'achievements');
define('TBL_SUPPORT_TICKETS', 'support_tickets');
define('TBL_SUPPORT_REPLIES', 'support_replies');
define('TBL_NOTIFICATIONS', 'notifications');
define('TBL_OFFERS', 'offers');
define('TBL_OFFER_ITEMS', 'offer_items');
define('TBL_CART_ITEMS', 'cart_items');
define('TBL_SETTINGS', 'settings');
define('TBL_SYSTEM_LOGS', 'system_logs');

// User roles
define('USER_ROLE_ADMIN', 'admin');
define('USER_ROLE_STAFF', 'staff');
define('USER_ROLE_CUSTOMER', 'customer');

// User statuses
define('USER_STATUS_ACTIVE', 'active');
define('USER_STATUS_INACTIVE', 'inactive');
define('USER_STATUS_BANNED', 'banned');

// Order statuses
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_PROCESSING', 'processing');
define('ORDER_STATUS_COMPLETED', 'completed');
define('ORDER_STATUS_CANCELLED', 'cancelled');
define('ORDER_STATUS_REFUNDED', 'refunded');

// Payment statuses
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PAID', 'paid');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// Payment methods
define('PAYMENT_METHOD_STRIPE', 'stripe');
define('PAYMENT_METHOD_PAYPAL', 'paypal');
define('PAYMENT_METHOD_WALLET', 'wallet');
define('PAYMENT_METHOD_BANK', 'bank_transfer');

// Item types
define('ITEM_TYPE_PRODUCT', 'product');
define('ITEM_TYPE_SERVICE', 'service');
define('ITEM_TYPE_CUSTOM_SERVICE', 'custom_service');

// Product/Service statuses
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_OUT_OF_STOCK', 'out_of_stock');

// Custom service statuses
define('CUSTOM_SERVICE_PENDING', 'pending');
define('CUSTOM_SERVICE_PAID', 'paid');
define('CUSTOM_SERVICE_EXPIRED', 'expired');
define('CUSTOM_SERVICE_CANCELLED', 'cancelled');

// Review statuses
define('REVIEW_STATUS_PENDING', 'pending');
define('REVIEW_STATUS_APPROVED', 'approved');
define('REVIEW_STATUS_REJECTED', 'rejected');

// Support ticket statuses
define('TICKET_STATUS_OPEN', 'open');
define('TICKET_STATUS_IN_PROGRESS', 'in_progress');
define('TICKET_STATUS_CLOSED', 'closed');
define('TICKET_STATUS_ANSWERED', 'answered');

// Support ticket priorities
define('TICKET_PRIORITY_LOW', 'low');
define('TICKET_PRIORITY_MEDIUM', 'medium');
define('TICKET_PRIORITY_HIGH', 'high');

// Notification types
define('NOTIFICATION_INFO', 'info');
define('NOTIFICATION_SUCCESS', 'success');
define('NOTIFICATION_WARNING', 'warning');
define('NOTIFICATION_ERROR', 'error');
define('NOTIFICATION_ORDER', 'order');
define('NOTIFICATION_PAYMENT', 'payment');
define('NOTIFICATION_PROMO', 'promo');

// Offer statuses
define('OFFER_STATUS_ACTIVE', 'active');
define('OFFER_STATUS_INACTIVE', 'inactive');
define('OFFER_STATUS_EXPIRED', 'expired');

// Discount types
define('DISCOUNT_TYPE_PERCENTAGE', 'percentage');
define('DISCOUNT_TYPE_FIXED', 'fixed');

// Invoice statuses
define('INVOICE_STATUS_PAID', 'paid');
define('INVOICE_STATUS_UNPAID', 'unpaid');
define('INVOICE_STATUS_CANCELLED', 'cancelled');

// Image sizes
define('IMAGE_SIZE_THUMBNAIL', [150, 150]);
define('IMAGE_SIZE_MEDIUM', [300, 300]);
define('IMAGE_SIZE_LARGE', [800, 600]);
define('IMAGE_SIZE_XLARGE', [1200, 900]);

// File upload paths
define('UPLOAD_PATH_PRODUCTS', 'products');
define('UPLOAD_PATH_SERVICES', 'services');
define('UPLOAD_PATH_USERS', 'users');
define('UPLOAD_PATH_ACHIEVEMENTS', 'achievements');
define('UPLOAD_PATH_OFFERS', 'offers');
define('UPLOAD_PATH_INVOICES', 'invoices');

// API response codes
define('API_SUCCESS', 200);
define('API_CREATED', 201);
define('API_BAD_REQUEST', 400);
define('API_UNAUTHORIZED', 401);
define('API_FORBIDDEN', 403);
define('API_NOT_FOUND', 404);
define('API_METHOD_NOT_ALLOWED', 405);
define('API_VALIDATION_ERROR', 422);
define('API_INTERNAL_ERROR', 500);

// Cache keys
define('CACHE_KEY_SETTINGS', 'settings');
define('CACHE_KEY_CATEGORIES', 'categories');
define('CACHE_KEY_FEATURED_PRODUCTS', 'featured_products');
define('CACHE_KEY_FEATURED_SERVICES', 'featured_services');
define('CACHE_KEY_ACHIEVEMENTS', 'achievements');

// Log levels
define('LOG_LEVEL_INFO', 'info');
define('LOG_LEVEL_WARNING', 'warning');
define('LOG_LEVEL_ERROR', 'error');
define('LOG_LEVEL_DEBUG', 'debug');

// Email templates
define('EMAIL_TEMPLATE_WELCOME', 'welcome');
define('EMAIL_TEMPLATE_ORDER_CONFIRMATION', 'order_confirmation');
define('EMAIL_TEMPLATE_PAYMENT_CONFIRMATION', 'payment_confirmation');
define('EMAIL_TEMPLATE_PASSWORD_RESET', 'password_reset');
define('EMAIL_TEMPLATE_CUSTOM_SERVICE', 'custom_service');
define('EMAIL_TEMPLATE_SUPPORT_REPLY', 'support_reply');

// Date formats
define('DATE_FORMAT_DISPLAY', 'F j, Y');
define('DATE_FORMAT_SHORT', 'M j, Y');
define('DATETIME_FORMAT_DISPLAY', 'F j, Y g:i A');
define('DATETIME_FORMAT_SHORT', 'M j, Y g:i A');

// Regex patterns
define('REGEX_EMAIL', '/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
define('REGEX_PHONE', '/^[\+]?[1-9][\d]{0,15}$/');
define('REGEX_USERNAME', '/^[a-zA-Z0-9_]{3,20}$/');
define('REGEX_PASSWORD', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/');

// Default values
define('DEFAULT_PAGINATION_LIMIT', 12);
define('DEFAULT_ADMIN_PAGINATION_LIMIT', 25);
define('DEFAULT_CURRENCY_CODE', 'USD');
define('DEFAULT_LANGUAGE_CODE', 'ar');
define('DEFAULT_TIMEZONE', 'UTC');
define('DEFAULT_TAX_RATE', 0.00);

// Social media platforms
define('SOCIAL_FACEBOOK', 'facebook');
define('SOCIAL_TWITTER', 'twitter');
define('SOCIAL_INSTAGRAM', 'instagram');
define('SOCIAL_LINKEDIN', 'linkedin');
define('SOCIAL_YOUTUBE', 'youtube');
define('SOCIAL_GITHUB', 'github');

// Feature flags
define('FEATURE_REVIEWS_ENABLED', true);
define('FEATURE_WALLET_ENABLED', true);
define('FEATURE_OFFERS_ENABLED', true);
define('FEATURE_NOTIFICATIONS_ENABLED', true);
define('FEATURE_SUPPORT_ENABLED', true);
define('FEATURE_ACHIEVEMENTS_ENABLED', true);
define('FEATURE_ANALYTICS_ENABLED', true);

// System limits
define('MAX_CART_ITEMS', 50);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_IMAGES_PER_PRODUCT', 10);
define('MAX_CUSTOM_SERVICES_PER_USER', 5);
define('MAX_SUPPORT_TICKETS_PER_DAY', 10);

// Success and error messages
define('MSG_SUCCESS_CREATED', 'Item created successfully');
define('MSG_SUCCESS_UPDATED', 'Item updated successfully');
define('MSG_SUCCESS_DELETED', 'Item deleted successfully');
define('MSG_ERROR_NOT_FOUND', 'Item not found');
define('MSG_ERROR_PERMISSION_DENIED', 'Permission denied');
define('MSG_ERROR_VALIDATION_FAILED', 'Validation failed');
define('MSG_ERROR_DATABASE', 'Database error occurred');
?>