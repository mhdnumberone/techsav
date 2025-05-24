# Real-time Validation Implementation Guide

## 📁 Directory Structure
```
/api/
  /auth/
    ├── check-username.php
    └── check-email.php
/assets/
  /js/
    └── registration-validation.js
  /css/
    └── registration-validation.css
```

## 🚀 Quick Setup

### 1. Include the files in your registration form:

```php
<!-- In your register.php or similar registration form -->
<?php include 'includes/header.php'; ?>

<!-- Include validation CSS -->
<link href="<?php echo ASSETS_URL; ?>/css/registration-validation.css" rel="stylesheet">

<!-- Your registration form HTML -->
<form method="POST" action="" id="registrationForm">
    <?php echo csrfToken(); ?>
    
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
        <!-- Feedback will be automatically inserted here -->
    </div>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
        <!-- Feedback will be automatically inserted here -->
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <!-- Password strength meter will be automatically inserted here -->
    </div>
    
    <div class="mb-3">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        <!-- Password match feedback will be automatically inserted here -->
    </div>
    
    <button type="submit" class="btn btn-primary">Register</button>
</form>

<!-- Include validation JavaScript -->
<script src="<?php echo ASSETS_URL; ?>/js/registration-validation.js"></script>

<?php include 'includes/footer.php'; ?>
```

### 2. That's it! The validation will work automatically.

## ⚙️ Configuration Options

### Optional Configuration Constants (add to config/constants.php):
```php
// Enable logging of username/email checks for security monitoring
define('LOG_USERNAME_CHECKS', true);
define('LOG_EMAIL_CHECKS', true);
```

### Customizing Debounce Delay:
```javascript
// In your custom JavaScript
registrationValidator.debounceDelay = 1000; // Wait 1 second instead of 500ms
```

## 🎨 Customization

### Custom Validation Messages:
Add these to your language files (`languages/en/main.php`, `languages/ar/main.php`):

```php
// Username validation
'username_required' => 'Username is required',
'username_too_short' => 'Username must be at least 3 characters',
'username_too_long' => 'Username must be no more than 20 characters',
'username_invalid_format' => 'Username can only contain letters, numbers, and underscores',
'username_reserved' => 'This username is reserved and cannot be used',
'username_taken' => 'This username is already taken',
'username_available' => 'Username is available',

// Email validation
'email_required' => 'Email address is required',
'email_invalid_format' => 'Please enter a valid email address',
'email_too_long' => 'Email address is too long',
'email_domain_blocked' => 'Temporary email addresses are not allowed',
'email_domain_invalid' => 'Email domain does not exist',
'email_taken' => 'This email address is already registered',
'email_taken_unverified' => 'This email is registered but not verified',
'email_available' => 'Email address is available',
'email_suggestion' => 'Did you mean',

// General
'server_error' => 'Server error occurred. Please try again',
'invalid_request_method' => 'Invalid request method',
'invalid_request' => 'Invalid request'
```

### Custom Styling:
Override the CSS variables in your theme:

```css
:root {
    --validation-success-color: #28a745;
    --validation-error-color: #dc3545;
    --validation-warning-color: #ffc107;
    --validation-info-color: #17a2b8;
}
```

## 🔒 Security Features

1. **CSRF Protection**: API endpoints check for proper AJAX requests
2. **Rate Limiting**: Built-in debouncing prevents spam
3. **Input Validation**: Server-side validation of all inputs
4. **Reserved Names**: Prevents registration of system usernames
5. **Domain Validation**: Blocks temporary email services
6. **Logging**: Optional security monitoring

## 📊 API Response Format

### Username Check Response:
```json
{
    "success": true,
    "available": true,
    "message": "Username is available",
    "username": "john_doe"
}
```

### Email Check Response:
```json
{
    "success": true,
    "available": true,
    "message": "Email address is available",
    "email": "john@example.com"
}
```

### Error Response:
```json
{
    "success": false,
    "available": false,
    "message": "Username is already taken",
    "username": "admin"
}
```

### Email Suggestion Response:
```json
{
    "success": false,
    "available": false,
    "message": "Did you mean: john@gmail.com?",
    "email": "john@gmail.co",
    "suggestion": "john@gmail.com"
}
```

## 🐛 Troubleshooting

### Common Issues:

1. **API endpoints return 404**: Make sure the `/api/auth/` directory exists and files are in the correct location
2. **CORS errors**: The API includes CORS headers, but check your server configuration
3. **JavaScript not loading**: Verify the script path and check browser console for errors
4. **Validation not triggering**: Ensure input fields have the correct IDs (`username`, `email`, `password`, `confirm_password`)

### Debug Mode:
Add this to see what's happening:

```javascript
// Enable debug logging
registrationValidator.debug = true;
```

## 🔧 Advanced Usage

### Manual Validation:
```javascript
// Manually validate a username
registrationValidator.validateUsername('testuser', field, feedbackElement);

// Manually validate an email
registrationValidator.validateEmail('test@example.com', field, feedbackElement);
```

### Custom Callbacks:
```javascript
// Add custom success callback
registrationValidator.onUsernameValid = function(username) {
    console.log('Username ' + username + ' is available!');
};

// Add custom error callback
registrationValidator.onEmailInvalid = function(email, message) {
    console.log('Email ' + email + ' error: ' + message);
};
```

## 🌐 Browser Support

- Chrome 60+
- Firefox 55+
- Safari 10+
- Edge 79+
- Internet Explorer 11+ (with polyfills)

## 📱 Mobile Compatibility

The validation is fully responsive and works on all mobile devices. The CSS includes specific mobile optimizations for smaller screens.
