<?php
/**
 * Admin Settings Management
 * TechSavvyGenLtd Project
 */

require_once '../config/config.php';

// Check if user is admin (only admins can modify settings)
if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize database
$db = Database::getInstance();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_general_settings':
                $result = updateGeneralSettings($_POST);
                if ($result['success']) {
                    $message = 'General settings updated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_payment_settings':
                $result = updatePaymentSettings($_POST);
                if ($result['success']) {
                    $message = 'Payment settings updated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_email_settings':
                $result = updateEmailSettings($_POST);
                if ($result['success']) {
                    $message = 'Email settings updated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_seo_settings':
                $result = updateSeoSettings($_POST);
                if ($result['success']) {
                    $message = 'SEO settings updated successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'backup_database':
                $result = createDatabaseBackup();
                if ($result['success']) {
                    $message = 'Database backup created successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'clear_cache':
                $result = clearSystemCache();
                if ($result['success']) {
                    $message = 'System cache cleared successfully';
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get current settings
$settings = getAllSettings();

$page_title = 'System Settings';
$body_class = 'admin-page settings-page';

// Helper functions
function getAllSettings() {
    global $db;
    try {
        $settings = [];
        $result = $db->fetchAll("SELECT setting_key, setting_value, setting_group FROM " . TBL_SETTINGS);
        foreach ($result as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

function updateSetting($key, $value, $group = 'general') {
    global $db;
    try {
        $exists = $db->exists(TBL_SETTINGS, 'setting_key = ?', [$key]);
        if ($exists) {
            return $db->update(
                TBL_SETTINGS,
                ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')],
                'setting_key = ?',
                [$key]
            );
        } else {
            return $db->insert(TBL_SETTINGS, [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_group' => $group,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    } catch (Exception $e) {
        return false;
    }
}

function updateGeneralSettings($data) {
    $settings = [
        'site_name' => cleanInput($data['site_name'] ?? ''),
        'site_description' => cleanInput($data['site_description'] ?? ''),
        'site_email' => cleanInput($data['site_email'] ?? ''),
        'site_phone' => cleanInput($data['site_phone'] ?? ''),
        'site_address' => cleanInput($data['site_address'] ?? ''),
        'default_language' => cleanInput($data['default_language'] ?? 'en'),
        'timezone' => cleanInput($data['timezone'] ?? 'UTC'),
        'currency' => cleanInput($data['currency'] ?? 'USD'),
        'tax_rate' => cleanInput($data['tax_rate'] ?? '0.00'),
        'items_per_page' => (int)($data['items_per_page'] ?? 10),
        'maintenance_mode' => !empty($data['maintenance_mode']) ? '1' : '0',
        'user_registration' => !empty($data['user_registration']) ? '1' : '0',
        'email_verification' => !empty($data['email_verification']) ? '1' : '0'
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value, 'general')) {
            $success = false;
        }
    }
    
    if ($success) {
        logActivity('settings_updated', 'General settings updated', $_SESSION['user_id'] ?? null);
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to update some settings'];
    }
}

function updatePaymentSettings($data) {
    $settings = [
        'stripe_public_key' => cleanInput($data['stripe_public_key'] ?? ''),
        'stripe_secret_key' => cleanInput($data['stripe_secret_key'] ?? ''),
        'stripe_webhook_secret' => cleanInput($data['stripe_webhook_secret'] ?? ''),
        'paypal_client_id' => cleanInput($data['paypal_client_id'] ?? ''),
        'paypal_client_secret' => cleanInput($data['paypal_client_secret'] ?? ''),
        'paypal_sandbox' => !empty($data['paypal_sandbox']) ? '1' : '0',
        'wallet_enabled' => !empty($data['wallet_enabled']) ? '1' : '0',
        'bank_transfer_enabled' => !empty($data['bank_transfer_enabled']) ? '1' : '0',
        'bank_name' => cleanInput($data['bank_name'] ?? ''),
        'bank_account' => cleanInput($data['bank_account'] ?? ''),
        'bank_iban' => cleanInput($data['bank_iban'] ?? '')
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value, 'payment')) {
            $success = false;
        }
    }
    
    if ($success) {
        logActivity('payment_settings_updated', 'Payment settings updated', $_SESSION['user_id'] ?? null);
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to update some payment settings'];
    }
}

function updateEmailSettings($data) {
    $settings = [
        'mail_driver' => cleanInput($data['mail_driver'] ?? 'mail'),
        'smtp_host' => cleanInput($data['smtp_host'] ?? ''),
        'smtp_port' => cleanInput($data['smtp_port'] ?? '587'),
        'smtp_username' => cleanInput($data['smtp_username'] ?? ''),
        'smtp_password' => cleanInput($data['smtp_password'] ?? ''),
        'smtp_encryption' => cleanInput($data['smtp_encryption'] ?? 'tls'),
        'mail_from_address' => cleanInput($data['mail_from_address'] ?? ''),
        'mail_from_name' => cleanInput($data['mail_from_name'] ?? ''),
        'admin_notifications' => !empty($data['admin_notifications']) ? '1' : '0',
        'user_notifications' => !empty($data['user_notifications']) ? '1' : '0'
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value, 'email')) {
            $success = false;
        }
    }
    
    if ($success) {
        logActivity('email_settings_updated', 'Email settings updated', $_SESSION['user_id'] ?? null);
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to update some email settings'];
    }
}

function updateSeoSettings($data) {
    $settings = [
        'meta_title' => cleanInput($data['meta_title'] ?? ''),
        'meta_description' => cleanInput($data['meta_description'] ?? ''),
        'meta_keywords' => cleanInput($data['meta_keywords'] ?? ''),
        'google_analytics_id' => cleanInput($data['google_analytics_id'] ?? ''),
        'google_tag_manager_id' => cleanInput($data['google_tag_manager_id'] ?? ''),
        'facebook_pixel_id' => cleanInput($data['facebook_pixel_id'] ?? ''),
        'social_facebook' => cleanInput($data['social_facebook'] ?? ''),
        'social_twitter' => cleanInput($data['social_twitter'] ?? ''),
        'social_instagram' => cleanInput($data['social_instagram'] ?? ''),
        'social_linkedin' => cleanInput($data['social_linkedin'] ?? ''),
        'social_youtube' => cleanInput($data['social_youtube'] ?? '')
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value, 'seo')) {
            $success = false;
        }
    }
    
    if ($success) {
        logActivity('seo_settings_updated', 'SEO settings updated', $_SESSION['user_id'] ?? null);
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to update some SEO settings'];
    }
}

function createDatabaseBackup() {
    global $db;
    try {
        $backupFile = $db->backup();
        if ($backupFile) {
            logActivity('database_backup', 'Database backup created: ' . basename($backupFile), $_SESSION['user_id'] ?? null);
            return ['success' => true, 'file' => $backupFile];
        } else {
            return ['success' => false, 'message' => 'Failed to create database backup'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()];
    }
}

function clearSystemCache() {
    try {
        $cacheCleared = true;
        
        // Clear template cache
        $templateCacheDir = ROOT_PATH . '/cache/templates/';
        if (is_dir($templateCacheDir)) {
            $files = glob($templateCacheDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        // Clear other cache directories
        $cacheDirs = [
            ROOT_PATH . '/cache/data/',
            ROOT_PATH . '/cache/images/',
            ROOT_PATH . '/cache/assets/'
        ];
        
        foreach ($cacheDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
        
        if ($cacheCleared) {
            logActivity('cache_cleared', 'System cache cleared', $_SESSION['user_id'] ?? null);
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to clear some cache files'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Cache clearing failed: ' . $e->getMessage()];
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="admin-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-title">System Settings</h1>
                <p class="admin-subtitle">Configure system-wide settings and preferences</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-warning" onclick="backupDatabase()">
                    <i class="fas fa-database me-2"></i>Backup Database
                </button>
                <button class="btn btn-info" onclick="clearCache()">
                    <i class="fas fa-broom me-2"></i>Clear Cache
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

        <div class="row">
            <!-- Settings Navigation -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Settings Categories</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                            <i class="fas fa-cog me-2"></i>General Settings
                        </a>
                        <a href="#payment" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="fas fa-credit-card me-2"></i>Payment Settings
                        </a>
                        <a href="#email" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="fas fa-envelope me-2"></i>Email Settings
                        </a>
                        <a href="#seo" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="fas fa-search me-2"></i>SEO & Social
                        </a>
                        <a href="#system" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="fas fa-server me-2"></i>System Info
                        </a>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">General Settings</h5>
                            </div>
                            <form method="POST">
                                <?php echo csrfToken(); ?>
                                <input type="hidden" name="action" value="update_general_settings">
                                
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Site Name</label>
                                            <input type="text" class="form-control" name="site_name" 
                                                   value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Site Email</label>
                                            <input type="email" class="form-control" name="site_email" 
                                                   value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Site Description</label>
                                            <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Site Phone</label>
                                            <input type="text" class="form-control" name="site_phone" 
                                                   value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Default Language</label>
                                            <select class="form-select" name="default_language">
                                                <option value="en" <?php echo ($settings['default_language'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                                                <option value="ar" <?php echo ($settings['default_language'] ?? '') === 'ar' ? 'selected' : ''; ?>>العربية</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Site Address</label>
                                            <textarea class="form-control" name="site_address" rows="2"><?php echo htmlspecialchars($settings['site_address'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Timezone</label>
                                            <select class="form-select" name="timezone">
                                                <option value="UTC" <?php echo ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                <option value="America/New_York" <?php echo ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                                <option value="America/Chicago" <?php echo ($settings['timezone'] ?? '') === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                                <option value="America/Denver" <?php echo ($settings['timezone'] ?? '') === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                                <option value="America/Los_Angeles" <?php echo ($settings['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                                <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                                <option value="Asia/Dubai" <?php echo ($settings['timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : ''; ?>>Dubai</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Currency</label>
                                            <select class="form-select" name="currency">
                                                <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                                <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                                <option value="GBP" <?php echo ($settings['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                                <option value="AED" <?php echo ($settings['currency'] ?? '') === 'AED' ? 'selected' : ''; ?>>AED (د.إ)</option>
                                                <option value="SAR" <?php echo ($settings['currency'] ?? '') === 'SAR' ? 'selected' : ''; ?>>SAR (ر.س)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tax Rate (%)</label>
                                            <input type="number" class="form-control" name="tax_rate" step="0.01" min="0" max="100"
                                                   value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0.00'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Items Per Page</label>
                                            <input type="number" class="form-control" name="items_per_page" min="5" max="100"
                                                   value="<?php echo htmlspecialchars($settings['items_per_page'] ?? '10'); ?>">
                                        </div>
                                        <div class="col-12">
                                            <hr>
                                            <h6>System Options</h6>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                                       <?php echo !empty($settings['maintenance_mode']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Maintenance Mode</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="user_registration" 
                                                       <?php echo !empty($settings['user_registration']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">User Registration</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="email_verification" 
                                                       <?php echo !empty($settings['email_verification']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Email Verification</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save General Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Payment Settings -->
                    <div class="tab-pane fade" id="payment">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Payment Settings</h5>
                            </div>
                            <form method="POST">
                                <?php echo csrfToken(); ?>
                                <input type="hidden" name="action" value="update_payment_settings">
                                
                                <div class="card-body">
                                    <!-- Stripe Settings -->
                                    <h6>Stripe Configuration</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Stripe Public Key</label>
                                            <input type="text" class="form-control" name="stripe_public_key" 
                                                   value="<?php echo htmlspecialchars($settings['stripe_public_key'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Stripe Secret Key</label>
                                            <input type="password" class="form-control" name="stripe_secret_key" 
                                                   value="<?php echo htmlspecialchars($settings['stripe_secret_key'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Stripe Webhook Secret</label>
                                            <input type="password" class="form-control" name="stripe_webhook_secret" 
                                                   value="<?php echo htmlspecialchars($settings['stripe_webhook_secret'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- PayPal Settings -->
                                    <h6>PayPal Configuration</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">PayPal Client ID</label>
                                            <input type="text" class="form-control" name="paypal_client_id" 
                                                   value="<?php echo htmlspecialchars($settings['paypal_client_id'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">PayPal Client Secret</label>
                                            <input type="password" class="form-control" name="paypal_client_secret" 
                                                   value="<?php echo htmlspecialchars($settings['paypal_client_secret'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="paypal_sandbox" 
                                                       <?php echo !empty($settings['paypal_sandbox']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">PayPal Sandbox Mode</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bank Transfer Settings -->
                                    <h6>Bank Transfer Configuration</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="bank_transfer_enabled" 
                                                       <?php echo !empty($settings['bank_transfer_enabled']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Enable Bank Transfer</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bank Name</label>
                                            <input type="text" class="form-control" name="bank_name" 
                                                   value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Account Number</label>
                                            <input type="text" class="form-control" name="bank_account" 
                                                   value="<?php echo htmlspecialchars($settings['bank_account'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">IBAN</label>
                                            <input type="text" class="form-control" name="bank_iban" 
                                                   value="<?php echo htmlspecialchars($settings['bank_iban'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- Other Payment Options -->
                                    <h6>Payment Options</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="wallet_enabled" 
                                                       <?php echo !empty($settings['wallet_enabled']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Enable Wallet Payments</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Payment Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Email Settings -->
                    <div class="tab-pane fade" id="email">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Email Settings</h5>
                            </div>
                            <form method="POST">
                                <?php echo csrfToken(); ?>
                                <input type="hidden" name="action" value="update_email_settings">
                                
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Mail Driver</label>
                                            <select class="form-select" name="mail_driver">
                                                <option value="mail" <?php echo ($settings['mail_driver'] ?? '') === 'mail' ? 'selected' : ''; ?>>PHP Mail</option>
                                                <option value="smtp" <?php echo ($settings['mail_driver'] ?? '') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">From Address</label>
                                            <input type="email" class="form-control" name="mail_from_address" 
                                                   value="<?php echo htmlspecialchars($settings['mail_from_address'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">From Name</label>
                                            <input type="text" class="form-control" name="mail_from_name" 
                                                   value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-12">
                                            <hr>
                                            <h6>SMTP Configuration (if SMTP is selected)</h6>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" name="smtp_host" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Port</label>
                                            <input type="number" class="form-control" name="smtp_port" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Username</label>
                                            <input type="text" class="form-control" name="smtp_username" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" name="smtp_password" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Encryption</label>
                                            <select class="form-select" name="smtp_encryption">
                                                <option value="tls" <?php echo ($settings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                <option value="" <?php echo empty($settings['smtp_encryption']) ? 'selected' : ''; ?>>None</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12">
                                            <hr>
                                            <h6>Notification Settings</h6>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="admin_notifications" 
                                                       <?php echo !empty($settings['admin_notifications']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Admin Notifications</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="user_notifications" 
                                                       <?php echo !empty($settings['user_notifications']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">User Notifications</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Email Settings
                                    </button>
                                    <button type="button" class="btn btn-outline-info" onclick="testEmail()">
                                        <i class="fas fa-paper-plane me-2"></i>Test Email
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- SEO & Social -->
                    <div class="tab-pane fade" id="seo">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">SEO & Social Media Settings</h5>
                            </div>
                            <form method="POST">
                                <?php echo csrfToken(); ?>
                                <input type="hidden" name="action" value="update_seo_settings">
                                
                                <div class="card-body">
                                    <h6>SEO Settings</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <label class="form-label">Meta Title</label>
                                            <input type="text" class="form-control" name="meta_title" 
                                                   value="<?php echo htmlspecialchars($settings['meta_title'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Meta Description</label>
                                            <textarea class="form-control" name="meta_description" rows="3"><?php echo htmlspecialchars($settings['meta_description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Meta Keywords</label>
                                            <input type="text" class="form-control" name="meta_keywords" 
                                                   value="<?php echo htmlspecialchars($settings['meta_keywords'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <h6>Analytics & Tracking</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Google Analytics ID</label>
                                            <input type="text" class="form-control" name="google_analytics_id" 
                                                   value="<?php echo htmlspecialchars($settings['google_analytics_id'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Google Tag Manager ID</label>
                                            <input type="text" class="form-control" name="google_tag_manager_id" 
                                                   value="<?php echo htmlspecialchars($settings['google_tag_manager_id'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Facebook Pixel ID</label>
                                            <input type="text" class="form-control" name="facebook_pixel_id" 
                                                   value="<?php echo htmlspecialchars($settings['facebook_pixel_id'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <h6>Social Media Links</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Facebook URL</label>
                                            <input type="url" class="form-control" name="social_facebook" 
                                                   value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Twitter URL</label>
                                            <input type="url" class="form-control" name="social_twitter" 
                                                   value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Instagram URL</label>
                                            <input type="url" class="form-control" name="social_instagram" 
                                                   value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">LinkedIn URL</label>
                                            <input type="url" class="form-control" name="social_linkedin" 
                                                   value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">YouTube URL</label>
                                            <input type="url" class="form-control" name="social_youtube" 
                                                   value="<?php echo htmlspecialchars($settings['social_youtube'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save SEO Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="tab-pane fade" id="system">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">System Information</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $db = Database::getInstance();
                                $dbStats = $db->getStatistics();
                                ?>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <h6>Server Information</h6>
                                        <table class="table table-sm">
                                            <tr><td><strong>PHP Version:</strong></td><td><?php echo phpversion(); ?></td></tr>
                                            <tr><td><strong>Server Software:</strong></td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td></tr>
                                            <tr><td><strong>Memory Limit:</strong></td><td><?php echo ini_get('memory_limit'); ?></td></tr>
                                            <tr><td><strong>Max Upload Size:</strong></td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
                                            <tr><td><strong>Time Limit:</strong></td><td><?php echo ini_get('max_execution_time'); ?>s</td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Database Information</h6>
                                        <table class="table table-sm">
                                            <tr><td><strong>Database:</strong></td><td><?php echo $dbStats['database']; ?></td></tr>
                                            <tr><td><strong>Version:</strong></td><td><?php echo $dbStats['version']; ?></td></tr>
                                            <tr><td><strong>Size:</strong></td><td><?php echo $dbStats['size_mb']; ?> MB</td></tr>
                                            <tr><td><strong>Tables:</strong></td><td><?php echo $dbStats['tables']; ?></td></tr>
                                            <tr><td><strong>Connection:</strong></td><td>
                                                <span class="badge bg-<?php echo $dbStats['connection_status'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $dbStats['connection_status'] ? 'Connected' : 'Disconnected'; ?>
                                                </span>
                                            </td></tr>
                                        </table>
                                    </div>
                                    <div class="col-12">
                                        <h6>System Actions</h6>
                                        <div class="btn-group">
                                            <button class="btn btn-outline-info" onclick="clearCache()">
                                                <i class="fas fa-broom me-2"></i>Clear Cache
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="backupDatabase()">
                                                <i class="fas fa-database me-2"></i>Backup Database
                                            </button>
                                            <button class="btn btn-outline-success" onclick="optimizeDatabase()">
                                                <i class="fas fa-tools me-2"></i>Optimize Database
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function backupDatabase() {
    if (confirm('Are you sure you want to create a database backup?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <?php echo csrfToken(); ?>
            <input type="hidden" name="action" value="backup_database">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function clearCache() {
    if (confirm('Are you sure you want to clear the system cache?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <?php echo csrfToken(); ?>
            <input type="hidden" name="action" value="clear_cache">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function optimizeDatabase() {
    Swal.fire({
        title: 'Optimize Database',
        text: 'This will optimize all database tables. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, optimize!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Call optimize API endpoint
            fetch(`${window.SITE_CONFIG.apiUrl}/system/optimize.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', 'Database optimized successfully', 'success');
                } else {
                    Swal.fire('Error', 'Failed to optimize database', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Network error occurred', 'error');
            });
        }
    });
}

function testEmail() {
    const email = prompt('Enter email address to send test email:');
    if (email && validateEmail(email)) {
        fetch(`${window.SITE_CONFIG.apiUrl}/system/test-email.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Success', 'Test email sent successfully', 'success');
            } else {
                Swal.fire('Error', 'Failed to send test email: ' + data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Network error occurred', 'error');
        });
    } else if (email) {
        Swal.fire('Error', 'Please enter a valid email address', 'error');
    }
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Auto-save functionality for settings
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                // Optional: Add auto-save functionality here
                // For now, just show that changes have been made
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.classList.contains('btn-warning')) {
                    submitBtn.classList.remove('btn-primary');
                    submitBtn.classList.add('btn-warning');
                    submitBtn.innerHTML = submitBtn.innerHTML.replace('Save', 'Save Changes');
                }
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>