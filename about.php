<?php
/**
 * About Us Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Page data
$page_title = __('about_us', 'About Us');
$body_class = 'about-page';
?>

<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<section class="page-header bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="page-header-content text-center">
                    <h1 class="page-title display-4 fw-bold mb-3">
                        <?php echo __('about_us', 'About Us'); ?>
                    </h1>
                    <p class="page-subtitle lead mb-4">
                        <?php echo __('about_subtitle', 'Learn more about our company, mission, and the team behind our success'); ?>
                    </p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item">
                                <a href="<?php echo SITE_URL; ?>" class="text-white-50">
                                    <?php echo __('home', 'Home'); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                <?php echo __('about_us', 'About Us'); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Company Overview Section -->
<section class="company-overview py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="about-content">
                    <h2 class="section-title mb-4">
                        <?php echo __('who_we_are', 'Who We Are'); ?>
                    </h2>
                    <p class="lead text-muted mb-4">
                        <?php echo __('company_intro', 'TechSavvyGenLtd is a leading digital solutions company specializing in web development, mobile applications, and innovative digital products.'); ?>
                    </p>
                    <p class="mb-4">
                        <?php echo __('company_description', 'Founded with a passion for technology and innovation, we have been helping businesses transform their digital presence and achieve their goals through cutting-edge solutions. Our team of experienced developers, designers, and digital strategists work together to deliver exceptional results that exceed expectations.'); ?>
                    </p>
                    <p class="mb-4">
                        <?php echo __('company_mission_intro', 'We believe in the power of technology to transform businesses and create meaningful connections between companies and their customers. Our approach combines technical expertise with creative thinking to deliver solutions that are not only functional but also engaging and user-friendly.'); ?>
                    </p>
                    
                    <div class="about-highlights">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="highlight-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <span><?php echo __('professional_team', 'Professional Expert Team'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="highlight-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <span><?php echo __('quality_delivery', 'Quality & Timely Delivery'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="highlight-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <span><?php echo __('innovative_solutions', 'Innovative Solutions'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="highlight-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <span><?php echo __('customer_support', '24/7 Customer Support'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="about-image text-center">
                    <img src="<?php echo ASSETS_URL; ?>/images/about-us.jpg" 
                         alt="<?php echo __('about_us', 'About Us'); ?>" 
                         class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission, Vision, Values Section -->
<section class="mission-vision py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title"><?php echo __('our_foundation', 'Our Foundation'); ?></h2>
                    <p class="section-subtitle text-muted">
                        <?php echo __('foundation_desc', 'The principles and values that guide everything we do'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Mission -->
            <div class="col-lg-4">
                <div class="foundation-card h-100 text-center">
                    <div class="foundation-icon mb-4">
                        <i class="fas fa-bullseye text-primary fa-3x"></i>
                    </div>
                    <h4 class="foundation-title mb-3"><?php echo __('our_mission', 'Our Mission'); ?></h4>
                    <p class="foundation-description text-muted">
                        <?php echo __('mission_text', 'To empower businesses with innovative digital solutions that drive growth, enhance user experience, and create lasting value in the digital marketplace.'); ?>
                    </p>
                </div>
            </div>
            
            <!-- Vision -->
            <div class="col-lg-4">
                <div class="foundation-card h-100 text-center">
                    <div class="foundation-icon mb-4">
                        <i class="fas fa-eye text-success fa-3x"></i>
                    </div>
                    <h4 class="foundation-title mb-3"><?php echo __('our_vision', 'Our Vision'); ?></h4>
                    <p class="foundation-description text-muted">
                        <?php echo __('vision_text', 'To be the leading provider of digital solutions that transform how businesses operate and connect with their customers in the digital age.'); ?>
                    </p>
                </div>
            </div>
            
            <!-- Values -->
            <div class="col-lg-4">
                <div class="foundation-card h-100 text-center">
                    <div class="foundation-icon mb-4">
                        <i class="fas fa-heart text-danger fa-3x"></i>
                    </div>
                    <h4 class="foundation-title mb-3"><?php echo __('our_values', 'Our Values'); ?></h4>
                    <p class="foundation-description text-muted">
                        <?php echo __('values_text', 'Innovation, quality, integrity, and customer satisfaction are at the core of everything we do. We believe in building long-term partnerships.'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title"><?php echo __('meet_our_team', 'Meet Our Team'); ?></h2>
                    <p class="section-subtitle text-muted">
                        <?php echo __('team_desc', 'The talented professionals who make our success possible'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Team Member 1 -->
            <div class="col-lg-3 col-md-6">
                <div class="team-member text-center">
                    <div class="member-photo mb-4">
                        <img src="<?php echo ASSETS_URL; ?>/images/team/member-1.jpg" 
                             alt="<?php echo __('ceo_name', 'Ahmed Hassan'); ?>" 
                             class="img-fluid rounded-circle">
                    </div>
                    <h5 class="member-name mb-2"><?php echo __('ceo_name', 'Ahmed Hassan'); ?></h5>
                    <p class="member-role text-primary mb-3"><?php echo __('ceo_title', 'CEO & Founder'); ?></p>
                    <p class="member-bio text-muted small mb-3">
                        <?php echo __('ceo_bio', 'Experienced entrepreneur with 10+ years in tech industry, passionate about innovation and digital transformation.'); ?>
                    </p>
                    <div class="member-social">
                        <a href="#" class="social-link me-2"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Team Member 2 -->
            <div class="col-lg-3 col-md-6">
                <div class="team-member text-center">
                    <div class="member-photo mb-4">
                        <img src="<?php echo ASSETS_URL; ?>/images/team/member-2.jpg" 
                             alt="<?php echo __('cto_name', 'Sarah Ahmed'); ?>" 
                             class="img-fluid rounded-circle">
                    </div>
                    <h5 class="member-name mb-2"><?php echo __('cto_name', 'Sarah Ahmed'); ?></h5>
                    <p class="member-role text-primary mb-3"><?php echo __('cto_title', 'CTO & Lead Developer'); ?></p>
                    <p class="member-bio text-muted small mb-3">
                        <?php echo __('cto_bio', 'Full-stack developer with expertise in modern web technologies and mobile app development.'); ?>
                    </p>
                    <div class="member-social">
                        <a href="#" class="social-link me-2"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Team Member 3 -->
            <div class="col-lg-3 col-md-6">
                <div class="team-member text-center">
                    <div class="member-photo mb-4">
                        <img src="<?php echo ASSETS_URL; ?>/images/team/member-3.jpg" 
                             alt="<?php echo __('designer_name', 'Omar Khalil'); ?>" 
                             class="img-fluid rounded-circle">
                    </div>
                    <h5 class="member-name mb-2"><?php echo __('designer_name', 'Omar Khalil'); ?></h5>
                    <p class="member-role text-primary mb-3"><?php echo __('designer_title', 'UI/UX Designer'); ?></p>
                    <p class="member-bio text-muted small mb-3">
                        <?php echo __('designer_bio', 'Creative designer focused on user experience and creating beautiful, functional interfaces.'); ?>
                    </p>
                    <div class="member-social">
                        <a href="#" class="social-link me-2"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link me-2"><i class="fab fa-dribbble"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-behance"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Team Member 4 -->
            <div class="col-lg-3 col-md-6">
                <div class="team-member text-center">
                    <div class="member-photo mb-4">
                        <img src="<?php echo ASSETS_URL; ?>/images/team/member-4.jpg" 
                             alt="<?php echo __('manager_name', 'Fatima Ali'); ?>" 
                             class="img-fluid rounded-circle">
                    </div>
                    <h5 class="member-name mb-2"><?php echo __('manager_name', 'Fatima Ali'); ?></h5>
                    <p class="member-role text-primary mb-3"><?php echo __('manager_title', 'Project Manager'); ?></p>
                    <p class="member-bio text-muted small mb-3">
                        <?php echo __('manager_bio', 'Experienced project manager ensuring timely delivery and smooth communication with clients.'); ?>
                    </p>
                    <div class="member-social">
                        <a href="#" class="social-link me-2"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fas fa-envelope"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-us py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title"><?php echo __('why_choose_us', 'Why Choose Us'); ?></h2>
                    <p class="section-subtitle text-muted">
                        <?php echo __('why_choose_desc', 'Discover what makes us the right choice for your digital needs'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Reason 1 -->
            <div class="col-lg-6">
                <div class="reason-item d-flex">
                    <div class="reason-icon flex-shrink-0 me-4">
                        <i class="fas fa-rocket text-primary fa-2x"></i>
                    </div>
                    <div class="reason-content">
                        <h5 class="reason-title mb-3"><?php echo __('fast_delivery', 'Fast & Reliable Delivery'); ?></h5>
                        <p class="reason-description text-muted">
                            <?php echo __('fast_delivery_desc', 'We understand the importance of time in business. Our streamlined processes ensure quick turnaround times without compromising on quality.'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Reason 2 -->
            <div class="col-lg-6">
                <div class="reason-item d-flex">
                    <div class="reason-icon flex-shrink-0 me-4">
                        <i class="fas fa-shield-alt text-success fa-2x"></i>
                    </div>
                    <div class="reason-content">
                        <h5 class="reason-title mb-3"><?php echo __('security_first', 'Security First Approach'); ?></h5>
                        <p class="reason-description text-muted">
                            <?php echo __('security_desc', 'We implement the latest security best practices and protocols to ensure your data and applications are protected from threats.'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Reason 3 -->
            <div class="col-lg-6">
                <div class="reason-item d-flex">
                    <div class="reason-icon flex-shrink-0 me-4">
                        <i class="fas fa-headset text-info fa-2x"></i>
                    </div>
                    <div class="reason-content">
                        <h5 class="reason-title mb-3"><?php echo __('support_247', '24/7 Support'); ?></h5>
                        <p class="reason-description text-muted">
                            <?php echo __('support_desc', 'Our dedicated support team is available round the clock to assist you with any questions or issues you may have.'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Reason 4 -->
            <div class="col-lg-6">
                <div class="reason-item d-flex">
                    <div class="reason-icon flex-shrink-0 me-4">
                        <i class="fas fa-award text-warning fa-2x"></i>
                    </div>
                    <div class="reason-content">
                        <h5 class="reason-title mb-3"><?php echo __('proven_expertise', 'Proven Expertise'); ?></h5>
                        <p class="reason-description text-muted">
                            <?php echo __('expertise_desc', 'With years of experience and hundreds of successful projects, we have the expertise to handle projects of any complexity.'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Reason 5 -->
            <div class="col-lg-6">
                <div class="reason-item d-flex">
                    <div class="reason-icon flex-shrink-0 me-4">
                        <i class="fas fa-lightbulb text-danger fa-2x"></i>
                    </div>
                    <div class="reason-content">
                        <h5 class="reason-title mb-3"><?php echo __('innovative_approach', 'Innovative Approach'); ?></h5>
                        <p class="reason-description text-muted">
                            <?php echo __('innovative_desc', 'We stay ahead of technology trends and continuously adopt new tools and methodologies to deliver cutting-edge solutions.'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Reason 6 -->
            <div class="col-lg-6">
                <div class="reason-item d-flex">
                    <div class="reason-icon flex-shrink-0 me-4">
                        <i class="fas fa-handshake text-purple fa-2x"></i>
                    </div>
                    <div class="reason-content">
                        <h5 class="reason-title mb-3"><?php echo __('partnership_approach', 'Partnership Approach'); ?></h5>
                        <p class="reason-description text-muted">
                            <?php echo __('partnership_desc', 'We believe in building long-term partnerships with our clients, working closely together to achieve mutual success and growth.'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Company Timeline Section -->
<section class="company-timeline py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title"><?php echo __('our_journey', 'Our Journey'); ?></h2>
                    <p class="section-subtitle text-muted">
                        <?php echo __('journey_desc', 'Key milestones in our company\'s growth and development'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="timeline">
                    <!-- Timeline Item 1 -->
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <div class="timeline-year">2020</div>
                        </div>
                        <div class="timeline-content">
                            <h5 class="timeline-title"><?php echo __('company_founded', 'Company Founded'); ?></h5>
                            <p class="timeline-description text-muted">
                                <?php echo __('founded_desc', 'TechSavvyGenLtd was established with a vision to provide innovative digital solutions to businesses of all sizes.'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Timeline Item 2 -->
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <div class="timeline-year">2021</div>
                        </div>
                        <div class="timeline-content">
                            <h5 class="timeline-title"><?php echo __('first_major_project', 'First Major Project'); ?></h5>
                            <p class="timeline-description text-muted">
                                <?php echo __('major_project_desc', 'Successfully completed our first enterprise-level web application, establishing our reputation in the market.'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Timeline Item 3 -->
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <div class="timeline-year">2022</div>
                        </div>
                        <div class="timeline-content">
                            <h5 class="timeline-title"><?php echo __('team_expansion', 'Team Expansion'); ?></h5>
                            <p class="timeline-description text-muted">
                                <?php echo __('expansion_desc', 'Expanded our team to include specialized developers, designers, and project managers to better serve our growing client base.'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Timeline Item 4 -->
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <div class="timeline-year">2023</div>
                        </div>
                        <div class="timeline-content">
                            <h5 class="timeline-title"><?php echo __('mobile_focus', 'Mobile App Focus'); ?></h5>
                            <p class="timeline-description text-muted">
                                <?php echo __('mobile_desc', 'Launched our mobile application development division, creating innovative apps for iOS and Android platforms.'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Timeline Item 5 -->
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <div class="timeline-year">2024</div>
                        </div>
                        <div class="timeline-content">
                            <h5 class="timeline-title"><?php echo __('digital_products', 'Digital Products Launch'); ?></h5>
                            <p class="timeline-description text-muted">
                                <?php echo __('products_desc', 'Introduced our digital products marketplace, offering ready-to-use solutions and tools for businesses.'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Timeline Item 6 -->
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <div class="timeline-year">2025</div>
                        </div>
                        <div class="timeline-content">
                            <h5 class="timeline-title"><?php echo __('future_growth', 'Continued Growth'); ?></h5>
                            <p class="timeline-description text-muted">
                                <?php echo __('growth_desc', 'Continuing to innovate and expand our services while maintaining our commitment to quality and customer satisfaction.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5 bg-gradient-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="cta-title mb-3"><?php echo __('work_with_us', 'Ready to Work With Us?'); ?></h3>
                <p class="cta-subtitle mb-0">
                    <?php echo __('work_cta_desc', 'Join hundreds of satisfied clients who have transformed their businesses with our digital solutions.'); ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="cta-buttons">
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>
                        <?php echo __('contact_us', 'Contact Us'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate timeline items on scroll
    const timelineItems = document.querySelectorAll('.timeline-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    timelineItems.forEach(item => {
        observer.observe(item);
    });
});
</script>

<?php include 'includes/footer.php'; ?>