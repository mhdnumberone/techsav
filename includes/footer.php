<?php
/**
 * Site Footer Template
 * TechSavvyGenLtd Project
 */

$is_admin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
?>

</main>

<?php if (!$is_admin): ?>
<!-- Main Site Footer -->
<footer class="site-footer bg-dark text-white">
    <!-- Main Footer Content -->
    <div class="footer-main py-5">
        <div class="container">
            <div class="row">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-widget">
                        <img src="<?php echo ASSETS_URL; ?>/images/logo-white.png" alt="<?php echo SITE_NAME; ?>" class="footer-logo mb-3" height="60">
                        <p class="footer-description">
                            <?php echo getSetting('site_description', __('footer_description', 'Professional web development services, mobile applications, and digital solutions for your business needs.')); ?>
                        </p>
                        <div class="contact-info mt-3">
                            <div class="contact-item mb-2">
                                <i class="fas fa-envelope me-2"></i>
                                <a href="mailto:<?php echo getSetting('site_email', SITE_EMAIL); ?>" class="text-white-50">
                                    <?php echo getSetting('site_email', SITE_EMAIL); ?>
                                </a>
                            </div>
                            <div class="contact-item mb-2">
                                <i class="fas fa-phone me-2"></i>
                                <a href="tel:<?php echo getSetting('site_phone', SITE_PHONE); ?>" class="text-white-50">
                                    <?php echo getSetting('site_phone', SITE_PHONE); ?>
                                </a>
                            </div>
                            <?php if (getSetting('site_address')): ?>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <span class="text-white-50"><?php echo getSetting('site_address'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-widget">
                        <h5 class="footer-widget-title mb-3"><?php echo __('quick_links', 'Quick Links'); ?></h5>
                        <ul class="footer-links list-unstyled">
                            <li><a href="<?php echo SITE_URL; ?>" class="text-white-50"><?php echo __('home', 'Home'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/about.php" class="text-white-50"><?php echo __('about', 'About Us'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/services.php" class="text-white-50"><?php echo __('services', 'Services'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/products.php" class="text-white-50"><?php echo __('products', 'Products'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/achievements.php" class="text-white-50"><?php echo __('achievements', 'Achievements'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/contact.php" class="text-white-50"><?php echo __('contact', 'Contact Us'); ?></a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Services -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-widget">
                        <h5 class="footer-widget-title mb-3"><?php echo __('our_services', 'Our Services'); ?></h5>
                        <ul class="footer-links list-unstyled">
                            <li><a href="<?php echo SITE_URL; ?>/services.php?category=web-development" class="text-white-50"><?php echo __('web_development', 'Web Development'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/services.php?category=mobile-apps" class="text-white-50"><?php echo __('mobile_apps', 'Mobile Apps'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/services.php?category=python-scripts" class="text-white-50"><?php echo __('python_scripts', 'Python Scripts'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/products.php?category=digital-products" class="text-white-50"><?php echo __('digital_products', 'Digital Products'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/products.php?category=training-courses" class="text-white-50"><?php echo __('training_courses', 'Training Courses'); ?></a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Newsletter & Social -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-widget">
                        <h5 class="footer-widget-title mb-3"><?php echo __('stay_connected', 'Stay Connected'); ?></h5>
                        <p class="text-white-50 mb-3">
                            <?php echo __('newsletter_text', 'Subscribe to our newsletter for updates and special offers.'); ?>
                        </p>
                        
                        <!-- Newsletter Subscription -->
                        <form class="newsletter-form mb-4" id="newsletterForm">
                            <div class="input-group">
                                <input type="email" class="form-control" placeholder="<?php echo __('enter_email', 'Enter your email'); ?>" name="email" required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Social Media Links -->
                        <div class="social-links">
                            <h6 class="mb-3"><?php echo __('follow_us', 'Follow Us'); ?></h6>
                            <div class="social-icons">
                                <?php if (getSetting('facebook_url')): ?>
                                <a href="<?php echo getSetting('facebook_url'); ?>" target="_blank" class="social-icon facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (getSetting('twitter_url')): ?>
                                <a href="<?php echo getSetting('twitter_url'); ?>" target="_blank" class="social-icon twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (getSetting('linkedin_url')): ?>
                                <a href="<?php echo getSetting('linkedin_url'); ?>" target="_blank" class="social-icon linkedin">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (getSetting('instagram_url')): ?>
                                <a href="<?php echo getSetting('instagram_url'); ?>" target="_blank" class="social-icon instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (getSetting('youtube_url')): ?>
                                <a href="<?php echo getSetting('youtube_url'); ?>" target="_blank" class="social-icon youtube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (getSetting('github_url')): ?>
                                <a href="<?php echo getSetting('github_url'); ?>" target="_blank" class="social-icon github">
                                    <i class="fab fa-github"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer Bottom -->
    <div class="footer-bottom py-3 border-top border-secondary">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright-text mb-0 text-white-50">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. <?php echo __('all_rights_reserved', 'All rights reserved.'); ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="footer-bottom-links text-md-end">
                        <a href="<?php echo SITE_URL; ?>/privacy-policy.php" class="text-white-50 me-3"><?php echo __('privacy_policy', 'Privacy Policy'); ?></a>
                        <a href="<?php echo SITE_URL; ?>/terms-of-service.php" class="text-white-50"><?php echo __('terms_of_service', 'Terms of Service'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php endif; ?>

<!-- Back to Top Button -->
<button id="backToTop" class="btn btn-primary back-to-top" style="display: none;">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden"><?php echo __('loading', 'Loading...'); ?></span>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>

<?php if (!$is_admin): ?>
<!-- Main Site JavaScript -->
<script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
<?php else: ?>
<!-- Admin JavaScript -->
<script src="<?php echo ASSETS_URL; ?>/js/admin.js"></script>

<!-- Chart.js for Admin Dashboard -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<!-- DataTables for Admin Tables -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap5.min.js"></script>
<?php endif; ?>

<!-- Additional JavaScript for specific pages -->
<?php if (isset($additional_js)): ?>
    <?php foreach ($additional_js as $js): ?>
    <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Global JavaScript Variables -->
<script>
window.SITE_CONFIG = {
    siteUrl: '<?php echo SITE_URL; ?>',
    assetsUrl: '<?php echo ASSETS_URL; ?>',
    apiUrl: '<?php echo API_URL; ?>',
    language: '<?php echo CURRENT_LANGUAGE; ?>',
    isRTL: <?php echo isRTL() ? 'true' : 'false'; ?>,
    isLoggedIn: <?php echo isLoggedIn() ? 'true' : 'false'; ?>,
    csrfToken: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
    currency: '<?php echo DEFAULT_CURRENCY; ?>',
    currencySymbol: '<?php echo CURRENCY_SYMBOL; ?>',
    texts: {
        loading: '<?php echo __('loading', 'Loading...'); ?>',
        success: '<?php echo __('success', 'Success'); ?>',
        error: '<?php echo __('error', 'Error'); ?>',
        confirm: '<?php echo __('confirm', 'Confirm'); ?>',
        cancel: '<?php echo __('cancel', 'Cancel'); ?>',
        yes: '<?php echo __('yes', 'Yes'); ?>',
        no: '<?php echo __('no', 'No'); ?>',
        deleteConfirm: '<?php echo __('delete_confirm', 'Are you sure you want to delete this item?'); ?>',
        networkError: '<?php echo __('network_error', 'Network error. Please try again.'); ?>'
    }
};
</script>

<!-- Custom JavaScript for current page -->
<?php if (isset($page_js)): ?>
<script>
<?php echo $page_js; ?>
</script>
<?php endif; ?>

</body>
</html><?php echo "\n"; ?>