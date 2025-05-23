<?php
/**
 * Admin Login Page - TechSavvyGenLtd
 * Administrative panel authentication
 */

require_once '../config/config.php';

// Redirect if already logged in and is admin/staff
if (isLoggedIn() && isStaff()) {
    redirect(ADMIN_URL);
}

// Redirect non-admin users to main site if logged in
if (isLoggedIn() && !isStaff()) {
    redirect(SITE_URL);
}

// Initialize User class
$userClass = new User();

// Handle form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_admin_login'])) {
    // Verify CSRF token
    if (!verifyCsrfToken()) {
        $error_message = __('invalid_token', 'Invalid security token. Please try again.');
    } else {
        $identifier = cleanInput($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);
        
        if (empty($identifier) || empty($password)) {
            $error_message = __('login_fields_required', 'Email/Username and password are required.');
        } else {
            $result = $userClass->login($identifier, $password, $remember);
            
            if ($result['success']) {
                // Check if user has admin/staff privileges
                if (isStaff()) {
                    // Log admin access
                    logActivity('admin_login', "Admin user {$result['user']['username']} logged into admin panel", $result['user']['id']);
                    
                    // Create admin login notification
                    createNotification(
                        null, // System notification
                        'دخول المدير',
                        'Admin Login',
                        "المستخدم {$result['user']['username']} دخل إلى لوحة التحكم",
                        "User {$result['user']['username']} logged into admin panel",
                        NOTIFICATION_INFO
                    );
                    
                    redirect(ADMIN_URL);
                } else {
                    // User authenticated but not admin/staff
                    $userClass->logout();
                    $error_message = __('access_denied', 'Access denied. Insufficient privileges for admin panel.');
                }
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Page data
$page_title = __('admin_login', 'Admin Login');
$body_class = 'admin-login-page';
?>
<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANGUAGE; ?>" dir="<?php echo isRTL() ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <?php if (isRTL()): ?>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    
    <!-- SweetAlert2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    <link href="<?php echo ASSETS_URL; ?>/css/admin.css" rel="stylesheet">
    <?php if (isRTL()): ?>
    <link href="<?php echo ASSETS_URL; ?>/css/rtl.css" rel="stylesheet">
    <?php endif; ?>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: <?php echo isRTL() ? 'Cairo' : 'Inter'; ?>, sans-serif;
        }
        
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 450px;
            width: 100%;
            padding: 40px;
        }
        
        .admin-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .admin-logo img {
            max-height: 80px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        
        .admin-title {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 28px;
        }
        
        .admin-subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        
        .input-group-text {
            border: 2px solid #e2e8f0;
            border-right: none;
            background: rgba(247, 250, 252, 0.9);
            border-radius: 12px 0 0 12px;
            color: #718096;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        .btn-admin-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-admin-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-admin-login:active {
            transform: translateY(0);
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .back-to-site {
            position: absolute;
            top: 30px;
            left: 30px;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .back-to-site:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(-5px);
        }
        
        .security-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #856404;
        }
        
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 20%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 15%;
            left: 20%;
            animation-delay: 4s;
        }
        
        .floating-element:nth-child(4) {
            width: 100px;
            height: 100px;
            bottom: 30%;
            right: 10%;
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        @media (max-width: 576px) {
            .admin-login-card {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .back-to-site {
                top: 15px;
                left: 15px;
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>

    <!-- Back to Site Link -->
    <a href="<?php echo SITE_URL; ?>" class="back-to-site">
        <i class="fas fa-arrow-left me-2"></i>
        <?php echo __('back_to_site', 'Back to Site'); ?>
    </a>

    <div class="admin-login-container">
        <div class="admin-login-card">
            <!-- Admin Logo & Title -->
            <div class="admin-logo">
                <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="<?php echo SITE_NAME; ?>">
            </div>
            
            <div class="text-center mb-4">
                <h2 class="admin-title"><?php echo __('admin_panel', 'Admin Panel'); ?></h2>
                <p class="admin-subtitle"><?php echo __('admin_login_subtitle', 'Secure access to administrative functions'); ?></p>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Admin Login Form -->
            <form method="POST" action="" class="admin-login-form" id="adminLoginForm">
                <?php echo csrfToken(); ?>
                
                <!-- Username/Email Field -->
                <div class="mb-3">
                    <label for="identifier" class="form-label fw-semibold">
                        <?php echo __('email_or_username', 'Email or Username'); ?>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="identifier" 
                               name="identifier" 
                               value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                               placeholder="<?php echo __('enter_email_username', 'Enter your email or username'); ?>"
                               required
                               autocomplete="username">
                    </div>
                </div>
                
                <!-- Password Field -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">
                        <?php echo __('password', 'Password'); ?>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="<?php echo __('enter_password', 'Enter your password'); ?>"
                               required
                               autocomplete="current-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me -->
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            <?php echo __('remember_me', 'Remember me'); ?>
                        </label>
                    </div>
                    <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="text-decoration-none">
                        <?php echo __('forgot_password', 'Forgot Password?'); ?>
                    </a>
                </div>
                
                <!-- Login Button -->
                <div class="d-grid mb-3">
                    <button type="submit" name="submit_admin_login" class="btn btn-primary btn-admin-login">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        <?php echo __('admin_login', 'Admin Login'); ?>
                    </button>
                </div>
            </form>

            <!-- Security Notice -->
            <div class="security-notice">
                <div class="d-flex align-items-center">
                    <i class="fas fa-shield-alt me-2"></i>
                    <small>
                        <?php echo __('admin_security_notice', 'This is a secure administrative area. All access attempts are logged and monitored.'); ?>
                    </small>
                </div>
            </div>

            <!-- Debug Info (Only in Development) -->
            <?php if (DEBUG_MODE): ?>
            <div class="mt-3">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo __('demo_credentials', 'Demo Credentials'); ?>
                    </h6>
                    <p class="mb-2"><strong><?php echo __('admin', 'Admin'); ?>:</strong> admin / password</p>
                    <p class="mb-0"><strong><?php echo __('staff', 'Staff'); ?>:</strong> staff@example.com / password</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>

    <script>
    // Global configuration
    window.SITE_CONFIG = {
        siteUrl: '<?php echo SITE_URL; ?>',
        adminUrl: '<?php echo ADMIN_URL; ?>',
        language: '<?php echo CURRENT_LANGUAGE; ?>',
        isRTL: <?php echo isRTL() ? 'true' : 'false'; ?>,
        texts: {
            loading: '<?php echo __('loading', 'Loading...'); ?>',
            error: '<?php echo __('error', 'Error'); ?>',
            networkError: '<?php echo __('network_error', 'Network error. Please try again.'); ?>'
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
        
        // Form validation and submission
        const adminLoginForm = document.getElementById('adminLoginForm');
        if (adminLoginForm) {
            adminLoginForm.addEventListener('submit', function(e) {
                const identifier = document.getElementById('identifier').value.trim();
                const password = document.getElementById('password').value;
                
                if (!identifier || !password) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: window.SITE_CONFIG.texts.error,
                        text: 'Please fill in all required fields.',
                        confirmButtonColor: '#667eea'
                    });
                    return;
                }
                
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + window.SITE_CONFIG.texts.loading;
                
                // Re-enable after 10 seconds (fallback)
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }, 10000);
            });
        }
        
        // Auto-focus on first input
        const firstInput = document.getElementById('identifier');
        if (firstInput) {
            firstInput.focus();
        }
        
        // Enhanced security monitoring
        let failedAttempts = 0;
        const maxAttempts = 3;
        
        adminLoginForm?.addEventListener('submit', function() {
            // This would be enhanced with actual security monitoring
            console.log('Admin login attempt logged');
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + H to go back to site
            if (e.altKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = window.SITE_CONFIG.siteUrl;
            }
        });
        
        // Add subtle animations
        const card = document.querySelector('.admin-login-card');
        if (card) {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }
    });
    </script>
</body>
</html>