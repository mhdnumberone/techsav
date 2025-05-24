<?php
/**
 * Reset Password Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL);
}

// Initialize User class
$userClass = new User();

// Get token from URL
$token = $_GET['token'] ?? '';

// Initialize variables
$error_message = '';
$success_message = '';
$password_reset = false;
$valid_token = false;

// Validate token
if (empty($token)) {
    $error_message = __('invalid_reset_link', 'Invalid or missing reset link. Please request a new password reset.');
} else {
    // Check if token is valid (this would typically query the database)
    // For now, we'll assume the User class has a method to validate token
    try {
        $valid_token = !empty($token) && strlen($token) > 10; // Basic validation
    } catch (Exception $e) {
        $error_message = __('invalid_reset_link', 'Invalid or expired reset link. Please request a new password reset.');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reset_password']) && $valid_token) {
    // Verify CSRF token
    if (!verifyCsrfToken()) {
        $error_message = __('invalid_token', 'Invalid security token. Please try again.');
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if (empty($newPassword) || empty($confirmPassword)) {
            $error_message = __('password_fields_required', 'Both password fields are required.');
        } elseif ($newPassword !== $confirmPassword) {
            $error_message = __('passwords_dont_match', 'Passwords do not match.');
        } elseif (!preg_match(REGEX_PASSWORD, $newPassword)) {
            $error_message = __('password_requirements', 'Password must be at least 8 characters with uppercase, lowercase, and number.');
        } else {
            // Attempt to reset password
            $result = $userClass->resetPassword($token, $newPassword);
            
            if ($result['success']) {
                $success_message = $result['message'];
                $password_reset = true;
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Page data
$page_title = __('reset_password', 'Reset Password');
$body_class = 'reset-password-page';
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
                        
                        <?php if ($password_reset): ?>
                        <div class="auth-success">
                            <div class="success-icon mb-3">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h2 class="auth-title text-success"><?php echo __('password_reset_success', 'Password Reset Successful!'); ?></h2>
                            <p class="auth-subtitle text-muted">
                                <?php echo __('password_reset_success_desc', 'Your password has been successfully reset. You can now log in with your new password.'); ?>
                            </p>
                        </div>
                        <?php elseif (!$valid_token): ?>
                        <div class="auth-error">
                            <div class="error-icon mb-3">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                            </div>
                            <h2 class="auth-title text-danger"><?php echo __('invalid_link', 'Invalid Link'); ?></h2>
                            <p class="auth-subtitle text-muted">
                                <?php echo __('invalid_link_desc', 'This password reset link is invalid or has expired. Please request a new password reset.'); ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <h2 class="auth-title"><?php echo __('reset_password', 'Reset Password'); ?></h2>
                        <p class="auth-subtitle text-muted">
                            <?php echo __('reset_password_subtitle', 'Enter your new password below'); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($password_reset): ?>
                    <!-- Success State Actions -->
                    <div class="auth-actions">
                        <div class="d-grid gap-2 mb-4">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                <?php echo __('login_now', 'Login Now'); ?>
                            </a>
                            
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>
                                <?php echo __('go_to_homepage', 'Go to Homepage'); ?>
                            </a>
                        </div>
                        
                        <div class="security-notice p-3 bg-light rounded">
                            <h6 class="mb-2">
                                <i class="fas fa-shield-alt text-success me-2"></i>
                                <?php echo __('security_tips', 'Security Tips'); ?>
                            </h6>
                            <ul class="small text-muted mb-0">
                                <li><?php echo __('use_strong_password', 'Use a strong, unique password'); ?></li>
                                <li><?php echo __('dont_share_password', 'Don\'t share your password with anyone'); ?></li>
                                <li><?php echo __('logout_public_computers', 'Always log out from public computers'); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <?php elseif (!$valid_token): ?>
                    <!-- Invalid Token State -->
                    <div class="auth-actions">
                        <div class="d-grid gap-2">
                            <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-redo me-2"></i>
                                <?php echo __('request_new_reset', 'Request New Password Reset'); ?>
                            </a>
                            
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                <?php echo __('back_to_login', 'Back to Login'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- Error/Success Messages -->
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message && !$password_reset): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Reset Password Form -->
                    <form method="POST" action="" class="auth-form" id="resetPasswordForm">
                        <?php echo csrfToken(); ?>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <?php echo __('new_password', 'New Password'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="<?php echo __('enter_new_password', 'Enter your new password'); ?>"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                <?php echo __('password_requirements', 'Password must be at least 8 characters with uppercase, lowercase, and number'); ?>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="strength-meter">
                                    <div class="strength-bar" id="strengthBar"></div>
                                </div>
                                <small class="strength-text text-muted" id="strengthText"></small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <?php echo __('confirm_password', 'Confirm New Password'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="<?php echo __('confirm_new_password', 'Confirm your new password'); ?>"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-match mt-2" id="passwordMatch"></div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" name="submit_reset_password" class="btn btn-primary btn-lg">
                                <i class="fas fa-key me-2"></i>
                                <?php echo __('reset_password', 'Reset Password'); ?>
                            </button>
                        </div>
                    </form>
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
                            <?php echo __('need_help', 'Need help?'); ?>
                            <a href="<?php echo SITE_URL; ?>/contact.php" class="text-decoration-none fw-bold">
                                <?php echo __('contact_support', 'Contact Support'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Security Information Section -->
<section class="info-section py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="info-content text-center">
                    <h3 class="mb-4"><?php echo __('password_security', 'Password Security'); ?></h3>
                    
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="security-tip">
                                <div class="tip-icon mb-3">
                                    <i class="fas fa-key fa-2x text-primary"></i>
                                </div>
                                <h6><?php echo __('strong_password', 'Strong Password'); ?></h6>
                                <p class="text-muted small">
                                    <?php echo __('strong_password_desc', 'Use at least 8 characters with mixed case, numbers, and symbols'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="security-tip">
                                <div class="tip-icon mb-3">
                                    <i class="fas fa-user-secret fa-2x text-success"></i>
                                </div>
                                <h6><?php echo __('unique_password', 'Unique Password'); ?></h6>
                                <p class="text-muted small">
                                    <?php echo __('unique_password_desc', 'Don\'t reuse passwords from other accounts'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="security-tip">
                                <div class="tip-icon mb-3">
                                    <i class="fas fa-shield-alt fa-2x text-info"></i>
                                </div>
                                <h6><?php echo __('keep_private', 'Keep Private'); ?></h6>
                                <p class="text-muted small">
                                    <?php echo __('keep_private_desc', 'Never share your password with anyone'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="security-tip">
                                <div class="tip-icon mb-3">
                                    <i class="fas fa-sync-alt fa-2x text-warning"></i>
                                </div>
                                <h6><?php echo __('regular_updates', 'Regular Updates'); ?></h6>
                                <p class="text-muted small">
                                    <?php echo __('regular_updates_desc', 'Update your password regularly for better security'); ?>
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
    // Password visibility toggles
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (toggleNewPassword && newPasswordField) {
        toggleNewPassword.addEventListener('click', function() {
            const type = newPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            newPasswordField.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    if (toggleConfirmPassword && confirmPasswordField) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordField.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    // Password strength indicator
    if (newPasswordField) {
        newPasswordField.addEventListener('input', function() {
            const password = this.value;
            updatePasswordStrength(password);
        });
    }
    
    // Password match indicator
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            const newPassword = newPasswordField.value;
            const confirmPassword = this.value;
            updatePasswordMatch(newPassword, confirmPassword);
        });
    }
    
    // Form validation
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (!newPassword || !confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'Please fill in both password fields.'
                });
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'Passwords do not match.'
                });
                return;
            }
            
            // Password strength validation
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
            if (!passwordRegex.test(newPassword)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'Password must be at least 8 characters with uppercase, lowercase, and number.'
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
    if (newPasswordField) {
        newPasswordField.focus();
    }
    
    // Animate success state
    <?php if ($password_reset): ?>
    const successIcon = document.querySelector('.success-icon i');
    if (successIcon) {
        successIcon.style.animation = 'bounce 1s ease-in-out';
    }
    <?php endif; ?>
});

function updatePasswordStrength(password) {
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    if (!strengthBar || !strengthText) return;
    
    let score = 0;
    let feedback = '';
    
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    
    switch (score) {
        case 0:
        case 1:
            strengthBar.style.width = '20%';
            strengthBar.className = 'strength-bar bg-danger';
            feedback = 'Very Weak';
            break;
        case 2:
            strengthBar.style.width = '40%';
            strengthBar.className = 'strength-bar bg-warning';
            feedback = 'Weak';
            break;
        case 3:
            strengthBar.style.width = '60%';
            strengthBar.className = 'strength-bar bg-info';
            feedback = 'Fair';
            break;
        case 4:
            strengthBar.style.width = '80%';
            strengthBar.className = 'strength-bar bg-primary';
            feedback = 'Good';
            break;
        case 5:
            strengthBar.style.width = '100%';
            strengthBar.className = 'strength-bar bg-success';
            feedback = 'Strong';
            break;
    }
    
    strengthText.textContent = feedback;
}

function updatePasswordMatch(newPassword, confirmPassword) {
    const passwordMatch = document.getElementById('passwordMatch');
    
    if (!passwordMatch || !confirmPassword) return;
    
    if (newPassword === confirmPassword) {
        passwordMatch.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Passwords match</small>';
    } else {
        passwordMatch.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Passwords do not match</small>';
    }
}
</script>

<style>
.auth-card {
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 15px;
    border: none;
    padding: 2rem;
}

.auth-success, .auth-error {
    padding: 2rem 0;
}

.strength-meter {
    height: 5px;
    background-color: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0%;
    transition: width 0.3s ease;
    border-radius: 3px;
}

.security-tip {
    padding: 1.5rem;
    border-radius: 10px;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    height: 100%;
}

.security-notice {
    border: 1px solid #e9ecef;
}

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
</style>

<?php include 'includes/footer.php'; ?>