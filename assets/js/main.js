/**
 * Main JavaScript File - TechSavvyGenLtd
 * Core functionality and utilities for the entire website
 */

// Global application object
window.TechSavvyApp = {
    config: {},
    utils: {},
    ui: {},
    auth: {},
    cart: {},
    forms: {},
    notifications: {},
    initialized: false
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    TechSavvyApp.init();
});

/**
 * Main application initialization
 */
TechSavvyApp.init = function() {
    if (this.initialized) return;
    
    // Initialize all modules
    this.config.init();
    this.utils.init();
    this.ui.init();
    this.auth.init();
    this.cart.init();
    this.forms.init();
    this.notifications.init();
    
    // Global event listeners
    this.setupGlobalEventListeners();
    
    // Mark as initialized
    this.initialized = true;
    
    console.log('TechSavvyApp initialized successfully');
};

/**
 * Configuration Module
 */
TechSavvyApp.config = {
    init: function() {
        // Set up global configuration from server-side data
        this.siteUrl = window.SITE_CONFIG?.siteUrl || '';
        this.apiUrl = window.SITE_CONFIG?.apiUrl || '';
        this.language = window.SITE_CONFIG?.language || 'en';
        this.isRTL = window.SITE_CONFIG?.isRTL || false;
        this.isLoggedIn = window.SITE_CONFIG?.isLoggedIn || false;
        this.csrfToken = window.SITE_CONFIG?.csrfToken || '';
        this.currency = window.SITE_CONFIG?.currency || 'USD';
        this.currencySymbol = window.SITE_CONFIG?.currencySymbol || '$';
        this.texts = window.SITE_CONFIG?.texts || {};
        
        // Default texts if not provided
        this.texts = Object.assign({
            loading: 'Loading...',
            success: 'Success',
            error: 'Error',
            confirm: 'Confirm',
            cancel: 'Cancel',
            yes: 'Yes',
            no: 'No',
            deleteConfirm: 'Are you sure you want to delete this item?',
            networkError: 'Network error. Please try again.'
        }, this.texts);
    },
    
    get: function(key) {
        return this[key];
    },
    
    set: function(key, value) {
        this[key] = value;
    }
};

/**
 * Utilities Module
 */
TechSavvyApp.utils = {
    init: function() {
        // Setup utility functions
    },
    
    // Format currency
    formatCurrency: function(amount) {
        const config = TechSavvyApp.config;
        return config.currencySymbol + parseFloat(amount).toFixed(2);
    },
    
    // Format date
    formatDate: function(dateString, format = 'short') {
        const date = new Date(dateString);
        const options = format === 'long' 
            ? { year: 'numeric', month: 'long', day: 'numeric' }
            : { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString(TechSavvyApp.config.language, options);
    },
    
    // Debounce function
    debounce: function(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    },
    
    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Generate random string
    generateRandomString: function(length = 10) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },
    
    // Validate email
    validateEmail: function(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    // Validate phone
    validatePhone: function(phone) {
        const regex = /^[\+]?[1-9][\d]{0,15}$/;
        return regex.test(phone.replace(/\s/g, ''));
    },
    
    // Truncate text
    truncateText: function(text, length = 100, suffix = '...') {
        if (text.length <= length) return text;
        return text.substring(0, length) + suffix;
    },
    
    // Get URL parameter
    getUrlParameter: function(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },
    
    // Set URL parameter
    setUrlParameter: function(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.pushState({}, '', url);
    },
    
    // Check if element is in viewport
    isInViewport: function(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },
    
    // Smooth scroll to element
    scrollToElement: function(element, offset = 0) {
        const targetPosition = element.offsetTop - offset;
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    },
    
    // Copy text to clipboard
    copyToClipboard: function(text) {
        return navigator.clipboard.writeText(text).then(() => {
            TechSavvyApp.notifications.show('Text copied to clipboard', 'success');
        }).catch(() => {
            TechSavvyApp.notifications.show('Failed to copy text', 'error');
        });
    }
};

/**
 * UI Module
 */
TechSavvyApp.ui = {
    init: function() {
        this.setupBackToTop();
        this.setupLoadingOverlay();
        this.setupTooltips();
        this.setupNavigation();
        this.setupAnimations();
        this.setupModals();
    },
    
    // Back to top button
    setupBackToTop: function() {
        const backToTopButton = document.getElementById('backToTop');
        if (!backToTopButton) return;
        
        window.addEventListener('scroll', TechSavvyApp.utils.throttle(() => {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        }, 100));
        
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    },
    
    // Loading overlay
    setupLoadingOverlay: function() {
        this.loadingOverlay = document.getElementById('loadingOverlay');
    },
    
    showLoading: function() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'flex';
        }
    },
    
    hideLoading: function() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'none';
        }
    },
    
    // Setup tooltips
    setupTooltips: function() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },
    
    // Navigation enhancements
    setupNavigation: function() {
        // Mobile menu enhancement
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbarToggler && navbarCollapse) {
            // Close mobile menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!navbarCollapse.contains(e.target) && !navbarToggler.contains(e.target)) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                    if (bsCollapse && navbarCollapse.classList.contains('show')) {
                        bsCollapse.hide();
                    }
                }
            });
        }
        
        // Navbar scroll behavior
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            let lastScrollTop = 0;
            window.addEventListener('scroll', TechSavvyApp.utils.throttle(() => {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > 100) {
                    navbar.classList.add('navbar-scrolled');
                } else {
                    navbar.classList.remove('navbar-scrolled');
                }
                
                lastScrollTop = scrollTop;
            }, 100));
        }
    },
    
    // Animations
    setupAnimations: function() {
        // Fade in animation for elements
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observe elements with animation class
        const animatedElements = document.querySelectorAll('.animate-on-scroll');
        animatedElements.forEach(el => observer.observe(el));
        
        // Counter animation
        this.setupCounterAnimation();
    },
    
    // Counter animation for statistics
    setupCounterAnimation: function() {
        const counters = document.querySelectorAll('.stat-number[data-count]');
        
        const animateCounter = (counter) => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000; // 2 seconds
            const start = performance.now();
            const startValue = parseInt(counter.textContent) || 0;
            
            const updateCounter = (currentTime) => {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function
                const easeOutQuad = 1 - (1 - progress) * (1 - progress);
                const currentValue = Math.floor(startValue + (target - startValue) * easeOutQuad);
                
                counter.textContent = currentValue;
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target;
                }
            };
            
            requestAnimationFrame(updateCounter);
        };
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        });
        
        counters.forEach(counter => counterObserver.observe(counter));
    },
    
    // Modal enhancements
    setupModals: function() {
        // Auto-focus first input in modals
        document.addEventListener('shown.bs.modal', (e) => {
            const firstInput = e.target.querySelector('input:not([type="hidden"]), textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        });
    },
    
    // Show confirmation dialog
    confirm: function(title, text, type = 'question') {
        return Swal.fire({
            title: title,
            text: text,
            icon: type,
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: TechSavvyApp.config.texts.yes,
            cancelButtonText: TechSavvyApp.config.texts.cancel
        });
    },
    
    // Show alert
    alert: function(title, text, type = 'info') {
        return Swal.fire({
            title: title,
            text: text,
            icon: type,
            confirmButtonText: 'OK'
        });
    }
};

/**
 * Authentication Module
 */
TechSavvyApp.auth = {
    init: function() {
        this.setupLoginForm();
        this.setupRegisterForm();
        this.setupPasswordToggles();
    },
    
    // Login form handling
    setupLoginForm: function() {
        const loginForm = document.getElementById('loginForm');
        if (!loginForm) return;
        
        loginForm.addEventListener('submit', this.handleLoginSubmit.bind(this));
    },
    
    handleLoginSubmit: function(e) {
        const form = e.target;
        const identifier = form.querySelector('#identifier')?.value;
        const password = form.querySelector('#password')?.value;
        
        if (!identifier || !password) {
            e.preventDefault();
            TechSavvyApp.notifications.show('Please fill in all required fields', 'error');
            return;
        }
        
        this.showSubmitLoading(form);
    },
    
    // Register form handling
    setupRegisterForm: function() {
        const registerForm = document.getElementById('registerForm');
        if (!registerForm) return;
        
        registerForm.addEventListener('submit', this.handleRegisterSubmit.bind(this));
        this.setupPasswordStrength(registerForm);
    },
    
    handleRegisterSubmit: function(e) {
        const form = e.target;
        const password = form.querySelector('#password')?.value;
        const confirmPassword = form.querySelector('#confirm_password')?.value;
        const termsAccepted = form.querySelector('#accept_terms')?.checked;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            TechSavvyApp.notifications.show('Passwords do not match', 'error');
            return;
        }
        
        if (!termsAccepted) {
            e.preventDefault();
            TechSavvyApp.notifications.show('You must accept the terms and conditions', 'error');
            return;
        }
        
        this.showSubmitLoading(form);
    },
    
    // Password strength indicator
    setupPasswordStrength: function(form) {
        const passwordField = form.querySelector('#password');
        const strengthMeter = form.querySelector('.password-strength');
        
        if (!passwordField || !strengthMeter) return;
        
        passwordField.addEventListener('input', (e) => {
            const password = e.target.value;
            const strength = this.calculatePasswordStrength(password);
            this.updatePasswordStrengthUI(strengthMeter, strength);
        });
    },
    
    calculatePasswordStrength: function(password) {
        let score = 0;
        let feedback = [];
        
        if (password.length >= 8) score++; else feedback.push('At least 8 characters');
        if (/[a-z]/.test(password)) score++; else feedback.push('Lowercase letter');
        if (/[A-Z]/.test(password)) score++; else feedback.push('Uppercase letter');
        if (/[0-9]/.test(password)) score++; else feedback.push('Number');
        if (/[^a-zA-Z0-9]/.test(password)) score++; else feedback.push('Special character');
        
        const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const colors = ['danger', 'warning', 'info', 'primary', 'success'];
        
        return {
            score: score,
            level: levels[Math.min(score, 4)],
            color: colors[Math.min(score, 4)],
            feedback: feedback
        };
    },
    
    updatePasswordStrengthUI: function(container, strength) {
        const progressBar = container.querySelector('.progress-bar');
        const strengthText = container.querySelector('.strength-text');
        
        if (progressBar) {
            const percentage = (strength.score / 5) * 100;
            progressBar.style.width = percentage + '%';
            progressBar.className = `progress-bar bg-${strength.color}`;
        }
        
        if (strengthText) {
            strengthText.textContent = strength.level;
            strengthText.className = `strength-text text-${strength.color}`;
        }
    },
    
    // Password toggle functionality
    setupPasswordToggles: function() {
        const toggleButtons = document.querySelectorAll('[id^="toggle"][id*="Password"]');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const targetId = button.id.replace('toggle', '').toLowerCase().replace('password', '_password');
                let passwordField = document.getElementById(targetId);
                
                // Fallback to common IDs
                if (!passwordField) {
                    const commonIds = ['password', 'new_password', 'confirm_password', 'current_password'];
                    for (let id of commonIds) {
                        if (button.id.toLowerCase().includes(id.replace('_', ''))) {
                            passwordField = document.getElementById(id);
                            break;
                        }
                    }
                }
                
                if (passwordField) {
                    const isPassword = passwordField.type === 'password';
                    passwordField.type = isPassword ? 'text' : 'password';
                    
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                }
            });
        });
    },
    
    // Show loading state on form submit
    showSubmitLoading: function(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton) return;
        
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${TechSavvyApp.config.texts.loading}`;
        
        // Restore after timeout (fallback)
        setTimeout(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }, 10000);
    }
};

/**
 * Shopping Cart Module
 */
TechSavvyApp.cart = {
    init: function() {
        this.setupAddToCartButtons();
        this.setupCartUpdates();
    },
    
    // Add to cart functionality
    setupAddToCartButtons: function() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[onclick*="addToCart"]') || e.target.closest('[onclick*="addToCart"]')) {
                e.preventDefault();
                // Handle add to cart - the onclick will be processed
            }
        });
    },
    
    // Add item to cart
    addToCart: function(type, id, quantity = 1) {
        if (!TechSavvyApp.config.isLoggedIn) {
            window.location.href = `${TechSavvyApp.config.siteUrl}/login.php?redirect=${encodeURIComponent(window.location.href)}`;
            return;
        }
        
        TechSavvyApp.ui.showLoading();
        
        return fetch(`${TechSavvyApp.config.apiUrl}/cart/add.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                type: type,
                id: id,
                quantity: quantity,
                csrf_token: TechSavvyApp.config.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            TechSavvyApp.ui.hideLoading();
            
            if (data.success) {
                TechSavvyApp.notifications.show(data.message || 'Item added to cart', 'success');
                this.updateCartCount(data.cart_count);
            } else {
                TechSavvyApp.notifications.show(data.message || 'Failed to add item to cart', 'error');
            }
            
            return data;
        })
        .catch(error => {
            TechSavvyApp.ui.hideLoading();
            console.error('Cart error:', error);
            TechSavvyApp.notifications.show('Network error. Please try again.', 'error');
        });
    },
    
    // Update cart item quantity
    updateCartItem: function(itemId, quantity) {
        return fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'update',
                item_id: itemId,
                quantity: quantity,
                csrf_token: TechSavvyApp.config.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to update totals
            } else {
                TechSavvyApp.notifications.show(data.message, 'error');
            }
        });
    },
    
    // Remove cart item
    removeCartItem: function(itemId) {
        return TechSavvyApp.ui.confirm(
            'Remove Item',
            'Are you sure you want to remove this item from your cart?'
        ).then((result) => {
            if (result.isConfirmed) {
                return fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        action: 'remove',
                        item_id: itemId,
                        csrf_token: TechSavvyApp.config.csrfToken
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        TechSavvyApp.notifications.show(data.message, 'error');
                    }
                });
            }
        });
    },
    
    // Update cart count in navbar
    updateCartCount: function(count) {
        const cartBadge = document.querySelector('.navbar .badge');
        if (cartBadge && count !== undefined) {
            cartBadge.textContent = count;
            
            // Add animation
            cartBadge.style.transform = 'scale(1.3)';
            setTimeout(() => {
                cartBadge.style.transform = 'scale(1)';
            }, 200);
        }
    },
    
    // Setup cart page functionality
    setupCartUpdates: function() {
        // Quantity controls
        window.updateQuantity = (itemId, change) => {
            const input = document.querySelector(`input[data-item-id="${itemId}"]`);
            if (!input) return;
            
            const currentQty = parseInt(input.value);
            const newQty = Math.max(1, currentQty + change);
            const maxQty = input.getAttribute('max');
            
            if (maxQty && newQty > parseInt(maxQty)) {
                TechSavvyApp.notifications.show('Quantity exceeds available stock', 'warning');
                return;
            }
            
            input.value = newQty;
            this.updateCartItem(itemId, newQty);
        };
        
        // Direct quantity update
        window.updateQuantityDirect = (input) => {
            const itemId = input.getAttribute('data-item-id');
            const quantity = parseInt(input.value);
            
            if (quantity < 1) {
                input.value = 1;
                return;
            }
            
            this.updateCartItem(itemId, quantity);
        };
        
        // Remove item
        window.removeItem = (itemId) => {
            this.removeCartItem(itemId);
        };
    }
};

/**
 * Forms Module
 */
TechSavvyApp.forms = {
    init: function() {
        this.setupFormValidation();
        this.setupContactForm();
        this.setupNewsletterForm();
    },
    
    // General form validation
    setupFormValidation: function() {
        const forms = document.querySelectorAll('form[novalidate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
        
        // Real-time validation for specific fields
        const emailFields = document.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            field.addEventListener('blur', (e) => {
                const isValid = TechSavvyApp.utils.validateEmail(e.target.value);
                e.target.classList.toggle('is-valid', isValid && e.target.value !== '');
                e.target.classList.toggle('is-invalid', !isValid && e.target.value !== '');
            });
        });
    },
    
    // Contact form
    setupContactForm: function() {
        const contactForm = document.getElementById('contactForm');
        if (!contactForm) return;
        
        contactForm.addEventListener('submit', (e) => {
            const requiredFields = contactForm.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                field.classList.remove('is-invalid');
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            const emailField = contactForm.querySelector('#email');
            if (emailField && emailField.value && !TechSavvyApp.utils.validateEmail(emailField.value)) {
                emailField.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                TechSavvyApp.notifications.show('Please fill in all required fields correctly', 'error');
            }
        });
    },
    
    // Newsletter form
    setupNewsletterForm: function() {
        const newsletterForm = document.getElementById('newsletterForm');
        if (!newsletterForm) return;
        
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const email = newsletterForm.querySelector('input[type="email"]').value;
            
            if (!TechSavvyApp.utils.validateEmail(email)) {
                TechSavvyApp.notifications.show('Please enter a valid email address', 'error');
                return;
            }
            
            // Simulate newsletter subscription
            TechSavvyApp.ui.showLoading();
            
            setTimeout(() => {
                TechSavvyApp.ui.hideLoading();
                TechSavvyApp.notifications.show('Thank you for subscribing to our newsletter!', 'success');
                newsletterForm.reset();
            }, 1000);
        });
    }
};

/**
 * Notifications Module
 */
TechSavvyApp.notifications = {
    init: function() {
        this.container = this.createContainer();
    },
    
    createContainer: function() {
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    },
    
    show: function(message, type = 'info', duration = 4000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icon = this.getIcon(type);
        notification.innerHTML = `
            <div class="notification-content">
                <i class="${icon} me-2"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        this.container.appendChild(notification);
        
        // Animate in
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto remove
        const autoRemove = setTimeout(() => this.remove(notification), duration);
        
        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            clearTimeout(autoRemove);
            this.remove(notification);
        });
        
        return notification;
    },
    
    remove: function(notification) {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    },
    
    getIcon: function(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-triangle',
            warning: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }
};

/**
 * Global Event Listeners
 */
TechSavvyApp.setupGlobalEventListeners = function() {
    // Handle AJAX errors globally
    window.addEventListener('unhandledrejection', (e) => {
        console.error('Unhandled promise rejection:', e.reason);
    });
    
    // Handle clicks on elements with data-confirm attribute
    document.addEventListener('click', (e) => {
        const target = e.target.closest('[data-confirm]');
        if (!target) return;
        
        e.preventDefault();
        const message = target.getAttribute('data-confirm');
        
        TechSavvyApp.ui.confirm('Confirm Action', message).then((result) => {
            if (result.isConfirmed) {
                // If it's a link, navigate to it
                if (target.tagName === 'A') {
                    window.location.href = target.href;
                }
                // If it's a button in a form, submit the form
                else if (target.type === 'submit') {
                    target.closest('form')?.submit();
                }
            }
        });
    });
    
    // Handle external links
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href^="http"]:not([href*="' + window.location.hostname + '"])');
        if (link && !link.hasAttribute('target')) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });
    
    // Handle image lazy loading
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Handle print functionality
    document.addEventListener('click', (e) => {
        if (e.target.matches('[data-print]') || e.target.closest('[data-print]')) {
            e.preventDefault();
            window.print();
        }
    });
};

// Global functions for backward compatibility
window.addToCart = function(type, id, quantity = 1) {
    return TechSavvyApp.cart.addToCart(type, id, quantity);
};

// Export for use in other files
window.TechSavvyApp = TechSavvyApp;