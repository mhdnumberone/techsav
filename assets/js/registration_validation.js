/**
 * Registration Form Real-time Validation
 * TechSavvyGenLtd Project
 */

class RegistrationValidator {
    constructor() {
        this.usernameTimeout = null;
        this.emailTimeout = null;
        this.debounceDelay = 500; // Wait 500ms after user stops typing
        
        this.init();
    }
    
    init() {
        // Initialize validation when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupValidation());
        } else {
            this.setupValidation();
        }
    }
    
    setupValidation() {
        const usernameField = document.getElementById('username');
        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        if (usernameField) {
            this.setupUsernameValidation(usernameField);
        }
        
        if (emailField) {
            this.setupEmailValidation(emailField);
        }
        
        if (passwordField) {
            this.setupPasswordValidation(passwordField);
        }
        
        if (confirmPasswordField && passwordField) {
            this.setupPasswordConfirmValidation(passwordField, confirmPasswordField);
        }
    }
    
    setupUsernameValidation(field) {
        const feedbackElement = this.createFeedbackElement(field, 'username-feedback');
        
        field.addEventListener('input', (e) => {
            const username = e.target.value.trim();
            
            // Clear previous timeout
            if (this.usernameTimeout) {
                clearTimeout(this.usernameTimeout);
            }
            
            // Clear feedback if empty
            if (!username) {
                this.clearValidation(field, feedbackElement);
                return;
            }
            
            // Show loading state
            this.showLoadingState(field, feedbackElement, 'Checking username...');
            
            // Set new timeout
            this.usernameTimeout = setTimeout(() => {
                this.validateUsername(username, field, feedbackElement);
            }, this.debounceDelay);
        });
        
        // Validate on blur as well
        field.addEventListener('blur', (e) => {
            const username = e.target.value.trim();
            if (username && !field.classList.contains('is-valid') && !field.classList.contains('is-invalid')) {
                this.validateUsername(username, field, feedbackElement);
            }
        });
    }
    
    setupEmailValidation(field) {
        const feedbackElement = this.createFeedbackElement(field, 'email-feedback');
        
        field.addEventListener('input', (e) => {
            const email = e.target.value.trim();
            
            // Clear previous timeout
            if (this.emailTimeout) {
                clearTimeout(this.emailTimeout);
            }
            
            // Clear feedback if empty
            if (!email) {
                this.clearValidation(field, feedbackElement);
                return;
            }
            
            // Basic format validation first
            if (!this.isValidEmailFormat(email)) {
                this.showInvalidState(field, feedbackElement, 'Please enter a valid email address');
                return;
            }
            
            // Show loading state
            this.showLoadingState(field, feedbackElement, 'Checking email...');
            
            // Set new timeout
            this.emailTimeout = setTimeout(() => {
                this.validateEmail(email, field, feedbackElement);
            }, this.debounceDelay);
        });
        
        // Validate on blur as well
        field.addEventListener('blur', (e) => {
            const email = e.target.value.trim();
            if (email && !field.classList.contains('is-valid') && !field.classList.contains('is-invalid')) {
                this.validateEmail(email, field, feedbackElement);
            }
        });
    }
    
    setupPasswordValidation(field) {
        const feedbackElement = this.createFeedbackElement(field, 'password-feedback');
        const strengthMeter = this.createPasswordStrengthMeter(field);
        
        field.addEventListener('input', (e) => {
            const password = e.target.value;
            this.validatePasswordStrength(password, field, feedbackElement, strengthMeter);
        });
    }
    
    setupPasswordConfirmValidation(passwordField, confirmField) {
        const feedbackElement = this.createFeedbackElement(confirmField, 'confirm-password-feedback');
        
        const validateMatch = () => {
            const password = passwordField.value;
            const confirmPassword = confirmField.value;
            
            if (!confirmPassword) {
                this.clearValidation(confirmField, feedbackElement);
                return;
            }
            
            if (password === confirmPassword) {
                this.showValidState(confirmField, feedbackElement, 'Passwords match');
            } else {
                this.showInvalidState(confirmField, feedbackElement, 'Passwords do not match');
            }
        };
        
        confirmField.addEventListener('input', validateMatch);
        passwordField.addEventListener('input', () => {
            if (confirmField.value) {
                validateMatch();
            }
        });
    }
    
    async validateUsername(username, field, feedbackElement) {
        try {
            const response = await fetch('/api/auth/check-username.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ username })
            });
            
            const data = await response.json();
            
            if (data.available) {
                this.showValidState(field, feedbackElement, data.message);
            } else {
                this.showInvalidState(field, feedbackElement, data.message);
            }
        } catch (error) {
            console.error('Username validation error:', error);
            this.showInvalidState(field, feedbackElement, 'Unable to check username availability');
        }
    }
    
    async validateEmail(email, field, feedbackElement) {
        try {
            const response = await fetch('/api/auth/check-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ email })
            });
            
            const data = await response.json();
            
            if (data.suggestion) {
                this.showSuggestionState(field, feedbackElement, data.message, data.suggestion);
            } else if (data.available) {
                this.showValidState(field, feedbackElement, data.message);
            } else {
                this.showInvalidState(field, feedbackElement, data.message);
                
                // Special handling for unverified accounts
                if (data.unverified) {
                    this.addResendVerificationOption(feedbackElement, email);
                }
            }
        } catch (error) {
            console.error('Email validation error:', error);
            this.showInvalidState(field, feedbackElement, 'Unable to check email availability');
        }
    }
    
    validatePasswordStrength(password, field, feedbackElement, strengthMeter) {
        if (!password) {
            this.clearValidation(field, feedbackElement);
            strengthMeter.style.display = 'none';
            return;
        }
        
        const strength = this.calculatePasswordStrength(password);
        this.updatePasswordStrengthMeter(strengthMeter, strength);
        
        if (strength.score >= 3) {
            this.showValidState(field, feedbackElement, strength.feedback);
        } else {
            this.showInvalidState(field, feedbackElement, strength.feedback);
        }
    }
    
    calculatePasswordStrength(password) {
        let score = 0;
        let feedback = '';
        
        // Length check
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        
        // Character variety checks
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        // Generate feedback
        if (score < 2) {
            feedback = 'Very weak password';
        } else if (score < 3) {
            feedback = 'Weak password';
        } else if (score < 4) {
            feedback = 'Fair password';
        } else if (score < 5) {
            feedback = 'Good password';
        } else {
            feedback = 'Strong password';
        }
        
        return { score, feedback };
    }
    
    updatePasswordStrengthMeter(meter, strength) {
        meter.style.display = 'block';
        
        const bar = meter.querySelector('.strength-bar') || meter;
        const text = meter.querySelector('.strength-text');
        
        // Update bar width and color
        const percentage = (strength.score / 5) * 100;
        bar.style.width = percentage + '%';
        
        // Remove existing strength classes
        bar.classList.remove('strength-very-weak', 'strength-weak', 'strength-fair', 'strength-good', 'strength-strong');
        
        // Add appropriate strength class
        if (strength.score < 2) {
            bar.classList.add('strength-very-weak');
        } else if (strength.score < 3) {
            bar.classList.add('strength-weak');
        } else if (strength.score < 4) {
            bar.classList.add('strength-fair');
        } else if (strength.score < 5) {
            bar.classList.add('strength-good');
        } else {
            bar.classList.add('strength-strong');
        }
        
        // Update text if element exists
        if (text) {
            text.textContent = strength.feedback;
        }
    }
    
    createFeedbackElement(field, id) {
        let feedback = document.getElementById(id);
        
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = id;
            feedback.className = 'feedback-message';
            field.parentNode.appendChild(feedback);
        }
        
        return feedback;
    }
    
    createPasswordStrengthMeter(field) {
        let meter = field.parentNode.querySelector('.password-strength-meter');
        
        if (!meter) {
            meter = document.createElement('div');
            meter.className = 'password-strength-meter';
            meter.style.display = 'none';
            meter.innerHTML = `
                <div class="strength-meter-bar">
                    <div class="strength-bar"></div>
                </div>
                <small class="strength-text"></small>
            `;
            field.parentNode.appendChild(meter);
        }
        
        return meter;
    }
    
    showLoadingState(field, feedback, message) {
        field.classList.remove('is-valid', 'is-invalid');
        field.classList.add('is-validating');
        feedback.className = 'feedback-message loading';
        feedback.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>${message}`;
    }
    
    showValidState(field, feedback, message) {
        field.classList.remove('is-invalid', 'is-validating');
        field.classList.add('is-valid');
        feedback.className = 'feedback-message valid';
        feedback.innerHTML = `<i class="fas fa-check me-1"></i>${message}`;
    }
    
    showInvalidState(field, feedback, message) {
        field.classList.remove('is-valid', 'is-validating');
        field.classList.add('is-invalid');
        feedback.className = 'feedback-message invalid';
        feedback.innerHTML = `<i class="fas fa-times me-1"></i>${message}`;
    }
    
    showSuggestionState(field, feedback, message, suggestion) {
        field.classList.remove('is-valid', 'is-validating');
        field.classList.add('is-invalid');
        feedback.className = 'feedback-message suggestion';
        feedback.innerHTML = `
            <i class="fas fa-exclamation-triangle me-1"></i>
            ${message}
            <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="registrationValidator.applySuggestion('${field.id}', '${suggestion}')">
                Use this
            </button>
        `;
    }
    
    clearValidation(field, feedback) {
        field.classList.remove('is-valid', 'is-invalid', 'is-validating');
        feedback.className = 'feedback-message';
        feedback.innerHTML = '';
    }
    
    applySuggestion(fieldId, suggestion) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = suggestion;
            field.focus();
            
            // Trigger validation
            const event = new Event('input', { bubbles: true });
            field.dispatchEvent(event);
        }
    }
    
    addResendVerificationOption(feedback, email) {
        const resendButton = document.createElement('button');
        resendButton.type = 'button';
        resendButton.className = 'btn btn-link btn-sm p-0 ms-2';
        resendButton.textContent = 'Resend verification';
        resendButton.onclick = () => this.resendVerification(email);
        
        feedback.appendChild(resendButton);
    }
    
    async resendVerification(email) {
        try {
            const response = await fetch('/api/auth/resend-verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ email })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Verification email sent! Please check your inbox.');
            } else {
                alert('Failed to send verification email. Please try again.');
            }
        } catch (error) {
            console.error('Resend verification error:', error);
            alert('An error occurred. Please try again.');
        }
    }
    
    isValidEmailFormat(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}

// Initialize validation when script loads
const registrationValidator = new RegistrationValidator();

// Export for global access
window.registrationValidator = registrationValidator;