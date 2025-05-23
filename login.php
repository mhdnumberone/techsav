<?php
/**
 * Login Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? SITE_URL;
    redirect($redirect);
}

// Initialize User class
$userClass = new User();

// Handle form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_login'])) {
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
                $redirect = $_GET['redirect'] ?? SITE_URL;
                // Ensure redirect URL is safe
                if (!filter_var($redirect, FILTER_VALIDATE_URL) || strpos($redirect, SITE_URL) !== 0) {
                    $redirect = SITE_URL;
                }
                redirect($redirect);
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Page data
$page_title = __('login', 'Login');
$body_class = 'login-page';
?>

<?php include 'includes/header.php'; ?>

<section class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="auth-card">
                    <div class="auth-header text-center mb-4">
                        <div class="auth-logo mb-3">
                            <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="<?php echo SITE_NAME; ?>" height="60">
                        </div>
                        <h2 class="auth-title"><?php echo __('welcome_back', 'Welcome Back'); ?></h2>
                        <p class="auth-subtitle text-muted">
                            <?php echo __('login_subtitle', 'Sign in to your account to continue'); ?>
                        </p>
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
                    
                    <form method="POST" action="" class="auth-form" id="loginForm">
                        <?php echo csrfToken(); ?>
                        
                        <div class="mb-3">
                            <label for="identifier" class="form-label">
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
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
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
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3 d-flex justify-content-between align-items-center">
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
                        
                        <div class="d-grid mb-3">
                            <button type="submit" name="submit_login" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                <?php echo __('sign_in', 'Sign In'); ?>
                            </button>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">
                                <?php echo __('dont_have_account', 'Don\'t have an account?'); ?>
                                <a href="<?php echo SITE_URL; ?>/register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" class="text-decoration-none fw-bold">
                                    <?php echo __('sign_up', 'Sign Up'); ?>
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Demo Account Info (Remove in production) -->
<?php if (DEBUG_MODE): ?>
<section class="demo-info py-3 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="alert alert-info">
                    <h6 class="alert-heading"><?php echo __('demo_accounts', 'Demo Accounts'); ?></h6>
                    <p class="mb-2"><strong><?php echo __('admin', 'Admin'); ?>:</strong> admin / password</p>
                    <p class="mb-0"><strong><?php echo __('customer', 'Customer'); ?>:</strong> customer@example.com / password</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    
    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    // Form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const identifier = document.getElementById('identifier').value.trim();
            const password = document.getElementById('password').value;
            
            if (!identifier || !password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'Please fill in all required fields.'
                });
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + window.SITE_CONFIG.texts.loading;
            
            // Re-enable after 5 seconds (fallback)
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }, 5000);
        });
    }
    
    // Auto-focus on first input
    const firstInput = document.getElementById('identifier');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Handle redirect parameter
    const urlParams = new URLSearchParams(window.location.search);
    const redirect = urlParams.get('redirect');
    if (redirect) {
        // Display message about login requirement
        const message = 'Please log in to continue to your requested page.';
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-info alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const form = document.querySelector('.auth-form');
        form.parentNode.insertBefore(alertDiv, form);
    }
});
</script>

<?php include 'includes/footer.php'; ?>