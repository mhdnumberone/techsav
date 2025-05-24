<?php
/**
 * Forgot Password Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL);
}

// Initialize User class
$userClass = new User();

// Handle form submission
$error_message = '';
$success_message = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_forgot_password'])) {
    // Verify CSRF token
    if (!verifyCsrfToken()) {
        $error_message = __('invalid_token', 'Invalid security token. Please try again.');
    } else {
        $email = cleanInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error_message = __('email_required', 'Email address is required.');
        } elseif (!validateEmail($email)) {
            $error_message = __('invalid_email', 'Please enter a valid email address.');
        } else {
            $result = $userClass->requestPasswordReset($email);
            
            if ($result['success']) {
                $success_message = $result['message'];
                $email_sent = true;
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Page data
$page_title = __('forgot_password', 'Forgot Password');
$body_class = 'forgot-password-page';
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
                        
                        <?php if (!$email_sent): ?>
                        <h2 class="auth-title"><?php echo __('forgot_password', 'Forgot Password'); ?></h2>
                        <p class="auth-subtitle text-muted">
                            <?php echo __('forgot_password_subtitle', 'Enter your email address and we\'ll send you a link to reset your password'); ?>
                        </p>
                        <?php else: ?>
                        <div class="auth-success">
                            <div class="success-icon mb-3">
                                <i class="fas fa-envelope fa-3x text-success"></i>
                            </div>
                            <h2 class="auth-title text-success"><?php echo __('email_sent', 'Email Sent!'); ?></h2>
                            <p class="auth-subtitle text-muted">
                                <?php echo __('reset_email_sent_desc', 'If an account with that email exists, we\'ve sent you a password reset link.'); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$email_sent): ?>
                    <!-- Error/Success Messages -->
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message && !$email_sent): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Forgot Password Form -->
                    <form method="POST" action="" class="auth-form" id="forgotPasswordForm">
                        <?php echo csrfToken(); ?>
                        
                        <div class="mb-4">
                            <label for="email" class="form-label">
                                <?php echo __('email_address', 'Email Address'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="<?php echo __('enter_email', 'Enter your email address'); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" name="submit_forgot_password" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                <?php echo __('send_reset_link', 'Send Reset Link'); ?>
                            </button>
                        </div>
                    </form>
                    
                    <?php else: ?>
                    <!-- Success State Actions -->
                    <div class="auth-actions">
                        <div class="d-grid gap-2">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                <?php echo __('back_to_login', 'Back to Login'); ?>
                            </a>
                            
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="window.location.reload()">
                                <i class="fas fa-redo me-2"></i>
                                <?php echo __('send_another_email', 'Send Another Email'); ?>
                            </button>
                        </div>
                        
                        <div class="email-check-notice mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                <?php echo __('didnt_receive_email', 'Didn\'t receive the email?'); ?>
                            </h6>
                            <ul class="small text-muted mb-0">
                                <li><?php echo __('check_spam_folder', 'Check your spam or junk mail folder'); ?></li>
                                <li><?php echo __('wait_few_minutes', 'Wait a few minutes for the email to arrive'); ?></li>
                                <li><?php echo __('verify_email_address', 'Make sure you entered the correct email address'); ?></li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    
                    <!-- Navigation Links -->
                    <div class="auth-links text-center">
                        <p class="mb-2">
                            <?php echo __('remember_password', 'Remember your password?'); ?>
                            <a href="<?php echo SITE_URL; ?>/login.php" class="text-decoration-none fw-bold">
                                <?php echo __('back_to_login', 'Back to Login'); ?>
                            </a>
                        </p>
                        
                        <p class="mb-0">
                            <?php echo __('dont_have_account', 'Don\'t have an account?'); ?>
                            <a href="<?php echo SITE_URL; ?>/register.php" class="text-decoration-none fw-bold">
                                <?php echo __('sign_up', 'Sign Up'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Information Section -->
<section class="info-section py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="info-content text-center">
                    <h3 class="mb-4"><?php echo __('password_reset_help', 'Password Reset Help'); ?></h3>
                    
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-icon mb-3">
                                    <i class="fas fa-shield-alt fa-2x text-primary"></i>
                                </div>
                                <h5><?php echo __('secure_process', 'Secure Process'); ?></h5>
                                <p class="text-muted small">
                                    <?php echo __('secure_process_desc', 'Our password reset process is completely secure and encrypted'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-icon mb-3">
                                    <i class="fas fa-clock fa-2x text-success"></i>
                                </div>
                                <h5><?php echo __('quick_delivery', 'Quick Delivery'); ?></h5>
                                <p class="text-muted small">
                                    <?php echo __('quick_delivery_desc', 'Reset links are delivered within minutes to your inbox'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-icon mb-3">
                                    <i class="fas fa-user-shield fa-2x text-info"></i>
                                </div>
                                <h5><?php echo __('privacy_protected', 'Privacy Protected'); ?></h5>
                                <p class="text-muted small">
                                    <?php echo __('privacy_protected_desc', 'Your personal information remains completely private'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            
            if (!email) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'Please enter your email address.'
                });
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'Please enter a valid email address.'
                });
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + window.SITE_CONFIG.texts.sending;
            
            // Re-enable after 10 seconds (fallback)
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }, 10000);
        });
    }
    
    // Auto-focus on email input
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.focus();
    }
    
    // Animate success state
    <?php if ($email_sent): ?>
    const successIcon = document.querySelector('.success-icon i');
    if (successIcon) {
        successIcon.style.animation = 'bounce 1s ease-in-out';
    }
    <?php endif; ?>
});

// CSS Animation for success icon
const style = document.createElement('style');
style.textContent = `
    @keyframes bounce {
        0%, 20%, 60%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-20px);
        }
        80% {
            transform: translateY(-10px);
        }
    }
    
    .auth-card {
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        border-radius: 15px;
        border: none;
        padding: 2rem;
    }
    
    .info-item {
        padding: 1.5rem;
        border-radius: 10px;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        height: 100%;
    }
    
    .email-check-notice {
        border: 1px solid #e9ecef;
    }
    
    .auth-success {
        padding: 2rem 0;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>