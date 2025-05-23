<?php
/**
 * Contact Us Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    // Verify CSRF token
    if (!verifyCsrfToken()) {
        $error_message = __('invalid_token', 'Invalid security token. Please try again.');
    } else {
        // Validate required fields
        $required_fields = ['name', 'email', 'subject', 'message'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = sprintf(__('field_required', 'Field %s is required'), __($field, $field));
            }
        }
        
        // Validate email
        if (!empty($_POST['email']) && !validateEmail($_POST['email'])) {
            $errors[] = __('invalid_email', 'Please enter a valid email address');
        }
        
        if (empty($errors)) {
            // Prepare email data
            $name = cleanInput($_POST['name']);
            $email = cleanInput($_POST['email']);
            $phone = cleanInput($_POST['phone'] ?? '');
            $subject = cleanInput($_POST['subject']);
            $message = cleanInput($_POST['message']);
            $service = cleanInput($_POST['service'] ?? '');
            
            // Create email content
            $email_subject = "Contact Form: " . $subject;
            $email_body = "
Name: {$name}
Email: {$email}
Phone: {$phone}
Subject: {$subject}
Service Interest: {$service}

Message:
{$message}

---
Sent from: " . SITE_NAME . " Contact Form
Date: " . date('Y-m-d H:i:s');
            
            // Send email
            if (sendEmail(SITE_EMAIL, $email_subject, $email_body)) {
                // Send auto-reply to user
                $auto_reply_subject = __('contact_auto_reply_subject', 'Thank you for contacting us');
                $auto_reply_body = sprintf(
                    __('contact_auto_reply_body', 
                    'Dear %s,

Thank you for contacting %s. We have received your message and will get back to you within 24 hours.

Your message:
Subject: %s
%s

Best regards,
%s Team'),
                    $name,
                    SITE_NAME,
                    $subject,
                    $message,
                    SITE_NAME
                );
                
                sendEmail($email, $auto_reply_subject, $auto_reply_body);
                
                // Log contact form submission
                logActivity('contact_form_submitted', "Contact form submitted by {$name} ({$email})");
                
                $success_message = __('contact_success', 'Thank you for your message. We will get back to you soon!');
                
                // Clear form data on success
                $_POST = [];
            } else {
                $error_message = __('contact_error', 'Failed to send message. Please try again or contact us directly.');
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Get service from URL parameter
$selected_service = $_GET['service'] ?? '';

// Page data
$page_title = __('contact_us', 'Contact Us');
$body_class = 'contact-page';
?>

<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<section class="page-header bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="page-header-content text-center">
                    <h1 class="page-title display-4 fw-bold mb-3">
                        <?php echo __('contact_us', 'Contact Us'); ?>
                    </h1>
                    <p class="page-subtitle lead mb-4">
                        <?php echo __('contact_subtitle', 'Get in touch with us to discuss your project and learn how we can help your business grow'); ?>
                    </p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item">
                                <a href="<?php echo SITE_URL; ?>" class="text-white-50">
                                    <?php echo __('home', 'Home'); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                <?php echo __('contact', 'Contact'); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="contact-content py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="contact-form-wrapper">
                    <div class="form-header mb-4">
                        <h3 class="form-title"><?php echo __('send_message', 'Send us a Message'); ?></h3>
                        <p class="form-subtitle text-muted">
                            <?php echo __('form_desc', 'Fill out the form below and we\'ll get back to you as soon as possible.'); ?>
                        </p>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="contact-form" id="contactForm">
                        <?php echo csrfToken(); ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">
                                    <?php echo __('full_name', 'Full Name'); ?> <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    <?php echo __('email_address', 'Email Address'); ?> <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">
                                    <?php echo __('phone_number', 'Phone Number'); ?>
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="service" class="form-label">
                                    <?php echo __('service_interest', 'Service of Interest'); ?>
                                </label>
                                <select class="form-select" id="service" name="service">
                                    <option value=""><?php echo __('select_service', 'Select a Service'); ?></option>
                                    <option value="web-development" <?php echo ($selected_service === 'web-development' || ($_POST['service'] ?? '') === 'web-development') ? 'selected' : ''; ?>>
                                        <?php echo __('web_development', 'Web Development'); ?>
                                    </option>
                                    <option value="mobile-apps" <?php echo ($selected_service === 'mobile-apps' || ($_POST['service'] ?? '') === 'mobile-apps') ? 'selected' : ''; ?>>
                                        <?php echo __('mobile_apps', 'Mobile Applications'); ?>
                                    </option>
                                    <option value="python-scripts" <?php echo ($selected_service === 'python-scripts' || ($_POST['service'] ?? '') === 'python-scripts') ? 'selected' : ''; ?>>
                                        <?php echo __('python_scripts', 'Python Scripts'); ?>
                                    </option>
                                    <option value="digital-products" <?php echo ($selected_service === 'digital-products' || ($_POST['service'] ?? '') === 'digital-products') ? 'selected' : ''; ?>>
                                        <?php echo __('digital_products', 'Digital Products'); ?>
                                    </option>
                                    <option value="training-courses" <?php echo ($selected_service === 'training-courses' || ($_POST['service'] ?? '') === 'training-courses') ? 'selected' : ''; ?>>
                                        <?php echo __('training_courses', 'Training Courses'); ?>
                                    </option>
                                    <option value="consultation" <?php echo ($_POST['service'] ?? '') === 'consultation' ? 'selected' : ''; ?>>
                                        <?php echo __('consultation', 'Consultation'); ?>
                                    </option>
                                    <option value="other" <?php echo ($_POST['service'] ?? '') === 'other' ? 'selected' : ''; ?>>
                                        <?php echo __('other', 'Other'); ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label for="subject" class="form-label">
                                    <?php echo __('subject', 'Subject'); ?> <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="subject" 
                                       name="subject" 
                                       value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                                       required>
                            </div>
                            
                            <div class="col-12">
                                <label for="message" class="form-label">
                                    <?php echo __('message', 'Message'); ?> <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" 
                                          id="message" 
                                          name="message" 
                                          rows="6" 
                                          required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" name="submit_contact" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    <?php echo __('send_message', 'Send Message'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="col-lg-4">
                <div class="contact-info-wrapper">
                    <div class="contact-info-header mb-4">
                        <h4 class="info-title"><?php echo __('get_in_touch', 'Get in Touch'); ?></h4>
                        <p class="info-subtitle text-muted">
                            <?php echo __('contact_info_desc', 'We\'d love to hear from you. Here\'s how you can reach us.'); ?>
                        </p>
                    </div>
                    
                    <div class="contact-info-list">
                        <!-- Address -->
                        <div class="info-item mb-4">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                            </div>
                            <div class="info-content">
                                <h6 class="info-label"><?php echo __('our_address', 'Our Address'); ?></h6>
                                <p class="info-text text-muted">
                                    <?php echo getSetting('site_address', '123 Tech Street, Digital City, TC 12345'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="info-item mb-4">
                            <div class="info-icon">
                                <i class="fas fa-envelope text-success"></i>
                            </div>
                            <div class="info-content">
                                <h6 class="info-label"><?php echo __('email_us', 'Email Us'); ?></h6>
                                <p class="info-text">
                                    <a href="mailto:<?php echo getSetting('site_email', SITE_EMAIL); ?>" class="text-decoration-none">
                                        <?php echo getSetting('site_email', SITE_EMAIL); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Phone -->
                        <div class="info-item mb-4">
                            <div class="info-icon">
                                <i class="fas fa-phone text-info"></i>
                            </div>
                            <div class="info-content">
                                <h6 class="info-label"><?php echo __('call_us', 'Call Us'); ?></h6>
                                <p class="info-text">
                                    <a href="tel:<?php echo getSetting('site_phone', SITE_PHONE); ?>" class="text-decoration-none">
                                        <?php echo getSetting('site_phone', SITE_PHONE); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Working Hours -->
                        <div class="info-item mb-4">
                            <div class="info-icon">
                                <i class="fas fa-clock text-warning"></i>
                            </div>
                            <div class="info-content">
                                <h6 class="info-label"><?php echo __('working_hours', 'Working Hours'); ?></h6>
                                <p class="info-text text-muted">
                                    <?php echo __('working_hours_desc', 'Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="contact-social mt-4">
                        <h6 class="social-title mb-3"><?php echo __('follow_us', 'Follow Us'); ?></h6>
                        <div class="social-links">
                            <?php if (getSetting('facebook_url')): ?>
                            <a href="<?php echo getSetting('facebook_url'); ?>" target="_blank" class="social-link facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (getSetting('twitter_url')): ?>
                            <a href="<?php echo getSetting('twitter_url'); ?>" target="_blank" class="social-link twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (getSetting('linkedin_url')): ?>
                            <a href="<?php echo getSetting('linkedin_url'); ?>" target="_blank" class="social-link linkedin">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (getSetting('instagram_url')): ?>
                            <a href="<?php echo getSetting('instagram_url'); ?>" target="_blank" class="social-link instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (getSetting('youtube_url')): ?>
                            <a href="<?php echo getSetting('youtube_url'); ?>" target="_blank" class="social-link youtube">
                                <i class="fab fa-youtube"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (getSetting('github_url')): ?>
                            <a href="<?php echo getSetting('github_url'); ?>" target="_blank" class="social-link github">
                                <i class="fab fa-github"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section (Optional) -->
<section class="map-section">
    <div class="map-container">
        <!-- Google Maps Embed - Replace with actual coordinates -->
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.1!2d-74.0059413!3d40.7128267!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQyJzQ2LjEiTiA3NMKwMDAnMjEuNCJX!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                width="100%" 
                height="400" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h3 class="section-title"><?php echo __('frequently_asked', 'Frequently Asked Questions'); ?></h3>
                    <p class="section-subtitle text-muted">
                        <?php echo __('faq_desc', 'Find answers to common questions about our services and process'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                <?php echo __('faq1_q', 'What services do you offer?'); ?>
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo __('faq1_a', 'We offer a comprehensive range of digital services including web development, mobile applications, Python scripts, digital products, and training courses. Our team specializes in creating custom solutions tailored to your specific business needs.'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                <?php echo __('faq2_q', 'How long does a typical project take?'); ?>
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo __('faq2_a', 'Project timelines vary depending on the complexity and scope of work. Simple websites may take 2-4 weeks, while complex web applications can take 2-6 months. We provide detailed timelines during the initial consultation phase.'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                <?php echo __('faq3_q', 'Do you provide ongoing support?'); ?>
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo __('faq3_a', 'Yes, we provide comprehensive ongoing support and maintenance services. This includes bug fixes, security updates, feature enhancements, and technical support to ensure your solution continues to perform optimally.'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                <?php echo __('faq4_q', 'What is your pricing structure?'); ?>
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo __('faq4_a', 'Our pricing varies based on project requirements, complexity, and timeline. We offer both fixed-price packages for standard services and custom quotes for unique projects. Contact us for a detailed quote based on your specific needs.'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 5 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                <?php echo __('faq5_q', 'Do you work with international clients?'); ?>
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo __('faq5_a', 'Absolutely! We work with clients worldwide and have experience collaborating across different time zones. We use modern communication tools and project management systems to ensure smooth collaboration regardless of location.'); ?>
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
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            // Clear previous error states
            requiredFields.forEach(field => {
                field.classList.remove('is-invalid');
            });
            
            // Validate required fields
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            // Validate email format
            const emailField = this.querySelector('#email');
            if (emailField && emailField.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value)) {
                    emailField.classList.add('is-invalid');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                // Show validation message
                Swal.fire({
                    icon: 'error',
                    title: window.SITE_CONFIG.texts.error,
                    text: 'Please fill in all required fields correctly.'
                });
            }
        });
    }
    
    // Auto-fill service field if service is in URL
    const urlParams = new URLSearchParams(window.location.search);
    const serviceParam = urlParams.get('service');
    if (serviceParam) {
        const serviceSelect = document.getElementById('service');
        if (serviceSelect) {
            // Try to match the service parameter with select options
            for (let option of serviceSelect.options) {
                if (option.value === serviceParam) {
                    option.selected = true;
                    break;
                }
            }
        }
    }
    
    // Smooth scroll to form if hash is present
    if (window.location.hash === '#contact-form') {
        const form = document.getElementById('contactForm');
        if (form) {
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }
    
    // Add loading state to submit button
    const submitButton = contactForm?.querySelector('button[type="submit"]');
    if (submitButton) {
        contactForm.addEventListener('submit', function() {
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + window.SITE_CONFIG.texts.loading;
            
            // Re-enable after 3 seconds (fallback)
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }, 3000);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>