<?php
/**
 * Registration Page - TechSavvyGenLtd
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_register'])) {
    // Verify CSRF token
    if (!verifyCsrfToken()) {
        $error_message = __('invalid_token', 'Invalid security token. Please try again.');
    } else {
        // Validate required fields
        $required_fields = ['username', 'email', 'password', 'confirm_password', 'first_name', 'last_name'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = sprintf(__('field_required', 'Field %s is required'), __($field, $field));
            }
        }
        
        // Validate password confirmation
        if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
            if ($_POST['password'] !== $_POST['confirm_password']) {
                $errors[] = __('password_mismatch', 'Passwords do not match');
            }
        }
        
        // Validate terms acceptance
        if (empty($_POST['accept_terms'])) {
            $errors[] = __('terms_required', 'You must accept the terms and conditions');
        }
        
        if (empty($errors)) {
            // Prepare registration data
            $registrationData = [
                'username' => cleanInput($_POST['username']),
                'email' => cleanInput($_POST['email']),
                'password' => $_POST['password'],
                'first_name' => cleanInput($_POST['first_name']),
                'last_name' => cleanInput($_POST['last_name']),
                'phone' => cleanInput($_POST['phone'] ?? ''),
                'preferred_language' => $_POST['language'] ?? DEFAULT_LANGUAGE
            ];
            
            $result = $userClass->register($registrationData);
            
            if ($result['success']) {
                $success_message = $result['message'];
                // Clear form data on success
                $_POST = [];
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Page data
$page_title = __('register', 'Register');
$body_class = 'register-page';
?>

<?php include 'includes/header.php'; ?>

<section class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="auth-card">
                    <div class="auth-header text-center mb-4">
                        <div class="auth-logo mb-3">
                            <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="<?php echo SITE_NAME; ?>" height="60">
                        </div>
                        <h2 class="auth-title"><?php echo __('create_account', 'Create Account'); ?></h2>
                        <p class="auth-subtitle text-muted">
                            <?php echo __('register_subtitle', 'Join us today and start your digital journey'); ?>
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
                    
                    <form method="POST" action="" class="auth-form" id="registerForm">
                        <?php echo csrfToken(); ?>
                        
                        <div class="row g-3">
                            <!-- First Name -->
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">
                                    <?php echo __('first_name', 'First Name'); ?> <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                       placeholder="<?php echo __('enter_first_name', 'Enter your first name'); ?>"
                                       required>
                            </div>
                            
                            <!-- Last Name -->
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">
                                    <?php echo __('last_name', 'Last Name'); ?> <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                       placeholder="<?php echo __('enter_last_name', 'Enter your last name'); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <?php echo __('username', 'Username'); ?> <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       placeholder="<?php echo __('enter_username', 'Enter your username'); ?>"
                                       pattern="[a-zA-Z0-9_]{3,20}"
                                       title="<?php echo __('username_requirements', 'Username must be 3-20 characters and contain only letters, numbers, and underscores'); ?>"
                                       required>
                            </div>
                            <small class="form-text text-muted">
                                <?php echo __('username_help', '3-20 characters, letters, numbers, and underscores only'); ?>
                            </small>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <?php echo __('email_address', 'Email Address'); ?> <span class="text-danger">*</span>
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
                        
                        <!-- Phone (Optional) -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <?php echo __('phone_number', 'Phone Number'); ?> <span class="text-muted">(<?php echo __('optional', 'Optional'); ?>)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       placeholder="<?php echo __('enter_phone', 'Enter your phone number'); ?>">
                            </div>
                        </div>
                        
                        <!-- Language Preference -->
                        <div class="mb-3">
                            <label for="language" class="form-label">
                                <?php echo __('preferred_language', 'Preferred Language'); ?>
                            </label>
                            <select class="form-select" id="language" name="language">
                                <option value="ar" <?php echo ($_POST['language'] ?? DEFAULT_LANGUAGE) === 'ar' ? 'selected' : ''; ?>>
                                    العربية (Arabic)
                                </option>
                                <option value="en" <?php echo ($_POST['language'] ?? DEFAULT_LANGUAGE) === 'en' ? 'selected' : ''; ?>>
                                    English
                                </option>
                            </select>
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <?php echo __('password', 'Password'); ?> <span class="text-danger">*</span>
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
                                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$"
                                       title="<?php echo __('password_requirements', 'Password must be at least 8 characters with uppercase, lowercase, and number'); ?>"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                <?php echo __('password_help', 'At least 8 characters with uppercase, lowercase, and number'); ?>
                            </small>
                            <!-- Password Strength Indicator -->
                            <div class="password-strength mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="strength-text text-muted"></small>
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <?php echo __('confirm_password', 'Confirm Password'); ?> <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="<?php echo __('confirm_password_placeholder', 'Confirm your password'); ?>"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-match mt-2">
                                <small class="match-text"></small>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="accept_terms" name="accept_terms" required>
                                <label class="form-check-label" for="accept_terms">
                                    <?php echo __('accept_terms_pre', 'I agree to the'); ?>
                                    <a href="<?php echo SITE_URL; ?>/terms-of-service.php" target="_blank" class="text-decoration-none">
                                        <?php echo __('terms_of_service', 'Terms of Service'); ?>
                                    </a>
                                    <?php echo __('and', 'and'); ?>
                                    <a href="<?php echo SITE_URL; ?>/privacy-policy.php" target="_blank" class="text-decoration-none">
                                        <?php echo __('privacy_policy', 'Privacy Policy'); ?>
                                    </a>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Newsletter Subscription (Optional) -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="subscribe_newsletter" name="subscribe_newsletter" checked>
                                <label class="form-check-label" for="subscribe_newsletter">
                                    <?php echo __('subscribe_newsletter', 'Subscribe to our newsletter for updates and special offers'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" name="submit_register" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>
                                <?php echo __('create_account', 'Create Account'); ?>
                            </button>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">
                                <?php echo __('already_have_account', 'Already have an account?'); ?>
                                <a href="<?php echo SITE_URL; ?>/login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" class="text-decoration-none fw-bold">
                                    <?php echo __('sign_in', 'Sign In'); ?>
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
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
    
    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = [];
        
        if (password.length >= 8) strength += 1;
        else feedback.push('At least 8 characters');
        
        if (/[a-z]/.test(password)) strength += 1;
        else feedback.push('Lowercase letter');
        
        if (/[A-Z]/.test(password)) strength += 1;
        else feedback.push('Uppercase letter');
        
        if (/[0-9]/.test(password)) strength += 1;
        else feedback.push('Number');
        
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        return { strength, feedback };
    }
    
    // Update password strength indicator
    passwordField.addEventListener('input', function() {
        const password = this.value;
        const result = checkPasswordStrength(password);
        const progressBar = document.querySelector('.password-strength .progress-bar');
        const strengthText = document.querySelector('.strength-text');
        
        const percentage = (result.strength / 5) * 100;
        progressBar.style.width = percentage + '%';
        
        // Update color and text based on strength
        progressBar.className = 'progress-bar';
        if (result.strength <= 2) {
            progressBar.classList.add('bg-danger');
            strengthText.textContent = 'Weak';
            strengthText.className = 'strength-text text-danger';
        } else if (result.strength <= 3) {
            progressBar.classList.add('bg-warning');
            strengthText.textContent = 'Medium';
            strengthText.className = 'strength-text text-warning';
        } else if (result.strength <= 4) {
            progressBar.classList.add('bg-info');
            strengthText.textContent = 'Good';
            strengthText.className = 'strength-text text-info';
        } else {
            progressBar.classList.add('bg-success');
            strengthText.textContent = 'Strong';
            strengthText.className = 'strength-text text-success';
        }
        
        if (password === '') {
            progressBar.style.width = '0%';
            strengthText.textContent = '';
        }
    });
    
    // Password match checker
    function checkPasswordMatch() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        const matchText = document.querySelector('.match-text');
        
        if (confirmPassword === '') {
            matchText.textContent = '';
            return;
        }
        
        if (password === confirmPassword) {
            matchText.textContent = 'Passwords match';
            matchText.className = 'match-text text-success';
        } else {
            matchText.textContent = 'Passwords do not match';
            matchText.className = 'match-text text-danger';
        }
    }
    
    confirmPasswordField.addEventListener('input', checkPasswordMatch);
    passwordField.addEventListener('input', checkPasswordMatch);
    
    // Form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];
            
            // Check password match
            if (passwordField.value !== confirmPasswordField.value) {
                isValid = false;
                errors.push('Passwords do not match');
            }
            
            // Check password strength
            const strengthResult = checkPasswordStrength(passwordField.value);
            if (strengthResult.strength < 3) {
                isValid = false;
                errors.push('Password is too weak');
            }
            
            // Check terms acceptance
            if (!document.getElementById('accept_terms').checked) {
                isValid = false;
                errors.push('You must accept the terms and conditions');
            }
            
            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Error',
                    html: errors.join('<br>')
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
    
    // Username availability checker (optional)
    const usernameField = document.getElementById('username');
    let usernameTimeout;
    
    usernameField.addEventListener('input', function() {
        clearTimeout(usernameTimeout);
        const username = this.value;
        
        if (username.length >= 3) {
            usernameTimeout = setTimeout(() => {
                // Check username availability via AJAX
                fetch(window.SITE_CONFIG.apiUrl + '/check-username.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username: username })
                })
                .then(response => response.json())
                .then(data => {
                    // Update UI based on availability
                    // This is optional functionality
                })
                .catch(error => {
                    console.log('Username check failed:', error);
                });
            }, 500);
        }
    });
    
    // Auto-focus on first input
    const firstInput = document.getElementById('first_name');
    if (firstInput) {
        firstInput.focus();
    }
});
</script>

<?php include 'includes/footer.php'; ?>