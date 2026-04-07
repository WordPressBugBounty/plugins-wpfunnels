<?php
/**
 * View automations
 *
 * @package
 */

use WPFunnels\Wpfnl_functions;

if ( ! Wpfnl_functions::is_mail_mint_pro_license_active() || ! Wpfnl_functions::is_pro_license_activated() ) :
?>
    <div class="wpfnl">
        <div class="wpfnl-dashboard wpfnl-automations">
            <nav class="wpfnl-dashboard__nav">
                <?php require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>
            </nav>

            <div class="dashboard-nav__content">
                <div class="wpfnl-automation-content">
                    <div class="wpfnl-automation-hero">
                        <div class="wpfnl-automation-hero-left">
                            <h1 class="hero-title">Automate Your Funnels with Mail Mint</h1>
                            <p class="hero-description">Create automated email journeys, behavior-based workflows, and follow-up sequences that maximize your funnel conversions.</p>
                            
                            <div class="cta-buttons">
                                <?php if ( ! Wpfnl_functions::is_mail_mint_free_active() ) : ?>
                                    <a href="#" role="button" aria-label="Download Mail Mint" rel="noopener noreferrer" class="btn-default wpfnl-install-mailmint">
                                        <span class="btn-text">Download Mail Mint</span>
                                        <span class="loader" style="display:none;">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-opacity="0.25" stroke-width="2"/>
                                                <path d="M15 8a7 7 0 01-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                    <animateTransform attributeName="transform" type="rotate" from="0 8 8" to="360 8 8" dur="1s" repeatCount="indefinite"/>
                                                </path>
                                            </svg>
                                        </span>
                                    </a>
                                <?php endif; ?>
                                    <a href="https://getwpfunnels.com/pricing/" role="button" target="_blank" aria-label="Upgrade to Pro" rel="noopener noreferrer" class="btn-default">Upgrade to Pro</a>
                                <?php //endif; ?>
                            </div>
                        </div>

                        <div class="wpfnl-automation-hero-right">
                            <span class="rating">
                                <svg width="66" height="11" viewBox="0 0 66 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.35272 0.795459C5.37435 0.751757 5.40777 0.71497 5.44919 0.689251C5.49062 0.663531 5.53841 0.649902 5.58718 0.649902C5.63594 0.649902 5.68373 0.663531 5.72516 0.689251C5.76659 0.71497 5.8 0.751757 5.82163 0.795459L6.96182 3.10497C7.03694 3.25697 7.14781 3.38849 7.28494 3.48821C7.42206 3.58794 7.58134 3.65291 7.7491 3.67753L10.299 4.05068C10.3473 4.05768 10.3927 4.07806 10.43 4.10952C10.4674 4.14097 10.4952 4.18225 10.5102 4.22867C10.5253 4.2751 10.5272 4.32483 10.5155 4.37223C10.5038 4.41962 10.4791 4.46281 10.4441 4.49689L8.60005 6.29257C8.47844 6.41108 8.38744 6.55737 8.33491 6.71885C8.28237 6.88032 8.26987 7.05215 8.29846 7.21953L8.73381 9.75658C8.74234 9.80487 8.73712 9.85459 8.71876 9.90006C8.70039 9.94553 8.66961 9.98492 8.62993 10.0137C8.59025 10.0426 8.54327 10.0596 8.49435 10.063C8.44543 10.0665 8.39654 10.056 8.35325 10.033L6.07386 8.83455C5.92366 8.75569 5.75657 8.71449 5.58693 8.71449C5.41729 8.71449 5.25019 8.75569 5.1 8.83455L2.8211 10.033C2.77783 10.0559 2.72899 10.0662 2.68016 10.0627C2.63132 10.0593 2.58443 10.0422 2.54483 10.0134C2.50524 9.98456 2.47452 9.94522 2.45617 9.89983C2.43782 9.85444 2.43257 9.80481 2.44104 9.75658L2.87589 7.22002C2.90461 7.05256 2.89217 6.88063 2.83963 6.71905C2.78709 6.55747 2.69603 6.41111 2.5743 6.29257L0.730253 4.49738C0.695008 4.46334 0.670032 4.42008 0.658171 4.37254C0.646309 4.325 0.648039 4.27508 0.663164 4.22847C0.678288 4.18186 0.706199 4.14044 0.743717 4.10892C0.781234 4.0774 0.826851 4.05705 0.875369 4.05019L3.42476 3.67753C3.59271 3.6531 3.7522 3.58822 3.88952 3.48848C4.02683 3.38874 4.13785 3.25712 4.21302 3.10497L5.35272 0.795459Z" fill="#ee8134" stroke="#ee8134" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.8918 0.795459C18.9134 0.751757 18.9468 0.71497 18.9883 0.689251C19.0297 0.663531 19.0775 0.649902 19.1262 0.649902C19.175 0.649902 19.2228 0.663531 19.2642 0.689251C19.3056 0.71497 19.3391 0.751757 19.3607 0.795459L20.5009 3.10497C20.576 3.25697 20.6869 3.38849 20.824 3.48821C20.9611 3.58794 21.1204 3.65291 21.2882 3.67753L23.838 4.05068C23.8864 4.05768 23.9318 4.07806 23.9691 4.10952C24.0064 4.14097 24.0342 4.18225 24.0493 4.22867C24.0644 4.2751 24.0662 4.32483 24.0545 4.37223C24.0428 4.41962 24.0181 4.46281 23.9832 4.49689L22.1391 6.29257C22.0175 6.41108 21.9265 6.55737 21.874 6.71885C21.8214 6.88032 21.8089 7.05215 21.8375 7.21953L22.2729 9.75658C22.2814 9.80487 22.2762 9.85459 22.2578 9.90006C22.2395 9.94553 22.2087 9.98492 22.169 10.0137C22.1293 10.0426 22.0823 10.0596 22.0334 10.063C21.9845 10.0665 21.9356 10.056 21.8923 10.033L19.6129 8.83455C19.4627 8.75569 19.2956 8.71449 19.126 8.71449C18.9564 8.71449 18.7893 8.75569 18.6391 8.83455L16.3602 10.033C16.3169 10.0559 16.2681 10.0662 16.2192 10.0627C16.1704 10.0593 16.1235 10.0422 16.0839 10.0134C16.0443 9.98456 16.0136 9.94522 15.9952 9.89983C15.9769 9.85444 15.9716 9.80481 15.9801 9.75658L16.415 7.22002C16.4437 7.05256 16.4312 6.88063 16.3787 6.71905C16.3261 6.55747 16.2351 6.41111 16.1134 6.29257L14.2693 4.49738C14.2341 4.46334 14.2091 4.42008 14.1972 4.37254C14.1854 4.325 14.1871 4.27508 14.2022 4.22847C14.2174 4.18186 14.2453 4.14044 14.2828 4.10892C14.3203 4.0774 14.3659 4.05705 14.4144 4.05019L16.9638 3.67753C17.1318 3.6531 17.2913 3.58822 17.4286 3.48848C17.5659 3.38874 17.6769 3.25712 17.7521 3.10497L18.8918 0.795459Z" fill="#ee8134" stroke="#ee8134" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M32.4289 0.795459C32.4505 0.751757 32.4839 0.71497 32.5254 0.689251C32.5668 0.663531 32.6146 0.649902 32.6633 0.649902C32.7121 0.649902 32.7599 0.663531 32.8013 0.689251C32.8428 0.71497 32.8762 0.751757 32.8978 0.795459L34.038 3.10497C34.1131 3.25697 34.224 3.38849 34.3611 3.48821C34.4982 3.58794 34.6575 3.65291 34.8253 3.67753L37.3752 4.05068C37.4235 4.05768 37.4689 4.07806 37.5062 4.10952C37.5435 4.14097 37.5713 4.18225 37.5864 4.22867C37.6015 4.2751 37.6033 4.32483 37.5916 4.37223C37.5799 4.41962 37.5552 4.46281 37.5203 4.49689L35.6762 6.29257C35.5546 6.41108 35.4636 6.55737 35.4111 6.71885C35.3585 6.88032 35.346 7.05215 35.3746 7.21953L35.81 9.75658C35.8185 9.80487 35.8133 9.85459 35.7949 9.90006C35.7766 9.94553 35.7458 9.98492 35.7061 10.0137C35.6664 10.0426 35.6194 10.0596 35.5705 10.063C35.5216 10.0665 35.4727 10.056 35.4294 10.033L33.15 8.83455C32.9998 8.75569 32.8327 8.71449 32.6631 8.71449C32.4935 8.71449 32.3264 8.75569 32.1762 8.83455L29.8973 10.033C29.854 10.0559 29.8052 10.0662 29.7563 10.0627C29.7075 10.0593 29.6606 10.0422 29.621 10.0134C29.5814 9.98456 29.5507 9.94522 29.5323 9.89983C29.514 9.85444 29.5087 9.80481 29.5172 9.75658L29.9521 7.22002C29.9808 7.05256 29.9683 6.88063 29.9158 6.71905C29.8633 6.55747 29.7722 6.41111 29.6505 6.29257L27.8064 4.49738C27.7712 4.46334 27.7462 4.42008 27.7343 4.37254C27.7225 4.325 27.7242 4.27508 27.7393 4.22847C27.7545 4.18186 27.7824 4.14044 27.8199 4.10892C27.8574 4.0774 27.903 4.05705 27.9515 4.05019L30.5009 3.67753C30.6689 3.6531 30.8284 3.58822 30.9657 3.48848C31.103 3.38874 31.214 3.25712 31.2892 3.10497L32.4289 0.795459Z" fill="#ee8134" stroke="#ee8134" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M45.968 0.795459C45.9896 0.751757 46.023 0.71497 46.0644 0.689251C46.1059 0.663531 46.1536 0.649902 46.2024 0.649902C46.2512 0.649902 46.299 0.663531 46.3404 0.689251C46.3818 0.71497 46.4152 0.751757 46.4369 0.795459L47.5771 3.10497C47.6522 3.25697 47.763 3.38849 47.9002 3.48821C48.0373 3.58794 48.1966 3.65291 48.3643 3.67753L50.9142 4.05068C50.9625 4.05768 51.0079 4.07806 51.0453 4.10952C51.0826 4.14097 51.1104 4.18225 51.1255 4.22867C51.1406 4.2751 51.1424 4.32483 51.1307 4.37223C51.119 4.41962 51.0943 4.46281 51.0593 4.49689L49.2153 6.29257C49.0937 6.41108 49.0027 6.55737 48.9501 6.71885C48.8976 6.88032 48.8851 7.05215 48.9137 7.21953L49.349 9.75658C49.3576 9.80487 49.3524 9.85459 49.334 9.90006C49.3156 9.94553 49.2848 9.98492 49.2452 10.0137C49.2055 10.0426 49.1585 10.0596 49.1096 10.063C49.0607 10.0665 49.0118 10.056 48.9685 10.033L46.6891 8.83455C46.5389 8.75569 46.3718 8.71449 46.2022 8.71449C46.0325 8.71449 45.8654 8.75569 45.7152 8.83455L43.4363 10.033C43.3931 10.0559 43.3442 10.0662 43.2954 10.0627C43.2466 10.0593 43.1997 10.0422 43.1601 10.0134C43.1205 9.98456 43.0898 9.94522 43.0714 9.89983C43.0531 9.85444 43.0478 9.80481 43.0563 9.75658L43.4911 7.22002C43.5198 7.05256 43.5074 6.88063 43.4549 6.71905C43.4023 6.55747 43.3113 6.41111 43.1895 6.29257L41.3455 4.49738C41.3102 4.46334 41.2853 4.42008 41.2734 4.37254C41.2615 4.325 41.2633 4.27508 41.2784 4.22847C41.2935 4.18186 41.3214 4.14044 41.359 4.10892C41.3965 4.0774 41.4421 4.05705 41.4906 4.05019L44.04 3.67753C44.2079 3.6531 44.3674 3.58822 44.5048 3.48848C44.6421 3.38874 44.7531 3.25712 44.8283 3.10497L45.968 0.795459Z" fill="#ee8134" stroke="#ee8134" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M59.507 0.795459C59.5286 0.751757 59.5621 0.71497 59.6035 0.689251C59.6449 0.663531 59.6927 0.649902 59.7415 0.649902C59.7902 0.649902 59.838 0.663531 59.8795 0.689251C59.9209 0.71497 59.9543 0.751757 59.9759 0.795459L61.1161 3.10497C61.1912 3.25697 61.3021 3.38849 61.4392 3.48821C61.5764 3.58794 61.7356 3.65291 61.9034 3.67753L64.4533 4.05068C64.5016 4.05768 64.547 4.07806 64.5843 4.10952C64.6217 4.14097 64.6494 4.18225 64.6645 4.22867C64.6796 4.2751 64.6815 4.32483 64.6698 4.37223C64.6581 4.41962 64.6334 4.46281 64.5984 4.49689L62.7543 6.29257C62.6327 6.41108 62.5417 6.55737 62.4892 6.71885C62.4367 6.88032 62.4242 7.05215 62.4528 7.21953L62.8881 9.75658C62.8966 9.80487 62.8914 9.85459 62.8731 9.90006C62.8547 9.94553 62.8239 9.98492 62.7842 10.0137C62.7446 10.0426 62.6976 10.0596 62.6487 10.063C62.5997 10.0665 62.5508 10.056 62.5075 10.033L60.2282 8.83455C60.078 8.75569 59.9109 8.71449 59.7412 8.71449C59.5716 8.71449 59.4045 8.75569 59.2543 8.83455L56.9754 10.033C56.9321 10.0559 56.8833 10.0662 56.8345 10.0627C56.7856 10.0593 56.7387 10.0422 56.6991 10.0134C56.6595 9.98456 56.6288 9.94522 56.6105 9.89983C56.5921 9.85444 56.5869 9.80481 56.5953 9.75658L57.0302 7.22002C57.0589 7.05256 57.0465 6.88063 56.9939 6.71905C56.9414 6.55747 56.8503 6.41111 56.7286 6.29257L54.8846 4.49738C54.8493 4.46334 54.8243 4.42008 54.8125 4.37254C54.8006 4.325 54.8023 4.27508 54.8175 4.22847C54.8326 4.18186 54.8605 4.14044 54.898 4.10892C54.9355 4.0774 54.9811 4.05705 55.0297 4.05019L57.5791 3.67753C57.747 3.6531 57.9065 3.58822 58.0438 3.48848C58.1811 3.38874 58.2921 3.25712 58.3673 3.10497L59.507 0.795459Z" fill="#ee8134" stroke="#ee8134" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Rated 5 Stars by Users
                            </span>

                            <ul class="feature-list">
                                <li>360° Contact Profiles</li>
                                <li>Email Builder</li>
                                <li>Marketing Automations</li>
                                <li>Campaign Ferformance</li>
                                <li>Abandoned Cart Recovery</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Hero Image Section -->
                    <section class="hero-section">
                        <div class="hero-video-container">
                            <iframe
                            width="560"
                            height="315"
                            src="https://www.youtube.com/embed/lRKDasiv9O0?si=R_9sKePZq0yn7gCk"
                            title="Mail Mint Overview Video"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            ></iframe>
                        </div>
                    </section>

                    <!-- Features Section -->
                    <section class="features-section">

                        <div class="feature-container">

                            <h2 class="section-title">See how WPFunnels and Mail Mint work together</h2>
                            
                            <div class="features-grid">
                                <!-- Feature 1 -->
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/contact-management.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h3 class="feature-title">Email Marketing Automation</h3>
                                    <p class="feature-description">Automate email marketing campaigns based on customer actions, such as abandoned carts or completed purchases in WPFunnels.</p>
                                </div>

                                <!-- Feature 2 -->
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/email-builder.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h3 class="feature-title">Upsell and Cross-sell Automation</h3>
                                    <p class="feature-description">Automatically trigger personalized upsell and cross-sell offers in your WPFunnels based on each customer’s purchase behavior and product engagement in WooCommerce.</p>
                                </div>

                                <!-- Feature 3 -->
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/email-sequence.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h3 class="feature-title">Automated Coupon Code</h3>
                                    <p class="feature-description">Automatically create and send coupon codes based on customer actions or conditions in WooCommerce.</p>
                                </div>

                                <!-- Feature 4 -->
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/automation-flows.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h3 class="feature-title">Order Alerts & Automations</h3>
                                    <p class="feature-description">Trigger instant notifications and automate actions for new or updated WooCommerce orders in WPFunnels.</p>
                                </div>

                                <!-- Feature 5 -->
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/realtime-analytics.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h3 class="feature-title">Real-time Analytics</h3>
                                    <p class="feature-description">Actionable analytics to help you make data-driven decisions and optimize your campaigns.</p>
                                </div>

                                <!-- Feature 6 -->
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/dynamic-segmentation.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h3 class="feature-title">Customer Winback Automation</h3>
                                    <p class="feature-description">Re-engage inactive customers automatically with targeted offers and reminders in WPFunnels.</p>
                                </div>

                                <!-- Feature 7 -->
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/stunning-email-templates.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h3 class="feature-title">Stunning Email Templates</h3>
                                    <p class="feature-description">Impress subscribers with visually appealing email templates by Mail Mint to get more opens and clicks.</p>
                                </div>

                                <!-- Feature 8 -->
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/woocommerce-icon.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h3 class="feature-title">WooCommerce</h3>
                                    <p class="feature-description">Trigger personalized and automated email campaigns for your WooCommerce store based on customer actions, order status, etc.</p>
                                </div>
                            </div>

                            <p class="features-footer">These are just examples. The possibilities are truly endless.</p>
                        </div>
                    </section>

                    <!-- Bottom CTA Section -->
                    <section class="bottom-cta-section">
                        <div class="cta-content-container">
                            <div class="cta-text">
                                <h2 class="cta-title">Stay Focused On Reaching Your Business Goals With Mail Mint</h2>
                                <p class="cta-subtitle">Launch successful sales campaigns, run a profitable business & develop a trusting relationship with your customers.</p>
                            </div>
                            <div class="cta-btn-wrapper">
                                <a href="https://getwpfunnels.com/email-marketing-automation-mail-mint/" target="_blank" role="button" aria-label="Visit Mail Mint Website" rel="noopener noreferrer" class="btn btn-primary">Visit Mail Mint Website</a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {    
        // Handle Mail Mint plugin download
        $('.wpfnl-install-mailmint').on('click', function(e) {
            e.preventDefault();
            
            var btn = $(this);
            var loader = btn.find('.loader');
            var btnText = btn.find('.btn-text');
            
            // Disable button and show loader
            btn.prop('disabled', true);
            loader.show();
            btnText.text('Downloading...');
            
            // Get plugin information from WordPress.org API
            $.ajax({
                url: 'https://api.wordpress.org/plugins/info/1.0/mail-mint.json',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.download_link) {
                        // Create a temporary link to download the file
                        var downloadLink = document.createElement('a');
                        downloadLink.href = response.download_link;
                        downloadLink.download = 'mail-mint.zip';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        
                        // Update button state
                        btnText.text('Downloaded Successfully!');
                        loader.hide();
                        
                        // Reset button after 2 seconds
                        setTimeout(function() {
                            btnText.text('Download Mail Mint');
                            btn.prop('disabled', false);
                        }, 2000);
                    } else {
                        btnText.text('Download Failed');
                        loader.hide();
                        btn.prop('disabled', false);
                        alert('Could not get download link for Mail Mint plugin.');
                    }
                },
                error: function(xhr, status, error) {
                    btnText.text('Download Mail Mint');
                    loader.hide();
                    btn.prop('disabled', false);
                    alert('Failed to fetch plugin information. Please try again or download directly from WordPress.org');
                }
            });
        });
    });
    </script>

<?php else : ?>

    <div class="wpfnl">
        <div class="wpfnl-dashboard">
            <nav class="wpfnl-dashboard__nav">
                <?php require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>
            </nav>

            <div class="dashboard-nav__content">
                <div class="wpfnl-dashboard__header overview-header">
                    <div class="wpfnl-dashboard-header-left">
                        <h2 class="wpfnl-dashboard-title"><?php echo __('Automations', 'wpfnl'); ?></h2>
                    </div>
                    <div class="wpfnl-dashboard-header-right">
                        <form class="funnel-search" method="get">
                            <div class="search-group">
                                <input name="page" type="hidden" value="wpfnl_automations">
                                <?php require_once WPFNL_DIR . '/admin/partials/icons/search-icon.php'; ?>
                                <input name="s" type="text" value="" placeholder="<?php echo __('Search funnel...', 'wpfnl'); ?>">
                            </div>
                        </form>
                        <!-- <a href="#" class="import-export wpfnl-import-funnels">
                            <?php
                                require WPFNL_DIR . '/admin/partials/icons/import-icon.php';
                                echo __('Import', 'wpfnl');
                            ?>
                        </a> -->
                        <a href="#" class="btn-default add-new-funnel-btn">
                            <?php
                                require WPFNL_DIR . '/admin/partials/icons/plus-icon.php';
                                echo esc_html__('Add New Automation', 'wpfnl');
                            ?>
                        </a>
                    </div>
                </div>

                <div class="wpfnl-dashboard__inner-content">
                    <?php
                    // Get pagination parameters
                    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                    $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
                    
                    $result = Wpfnl_functions::retrieve_all_automations($current_page, $per_page);
                    $automations = isset($result['data']) ? $result['data'] : array();
                    $total = isset($result['total']) ? $result['total'] : 0;
                    $total_pages = isset($result['total_pages']) ? $result['total_pages'] : 0;
                    $per_page = isset($result['per_page']) ? $result['per_page'] : 10;
                    if (!empty($automations)) :
                    ?>
                    <div class="funnel-list__wrapper">
                        <div class="funnel__single-list list-header">
                            <!-- <div class="funnel-list__bulk-action">
                                <div class="funnel-list__bulk-select select-all-funnels" >
                                    <span class="wpfnl-checkbox no-title">
                                        <input type="checkbox" name="funnel-list__bulk-select" id="funnel-list__bulk-select">
                                        <label for="funnel-list__bulk-select"></label>
                                    </span>
                                </div>
                            </div> -->
                            <div class="list-cell wpfnl-name"><?php echo __('Automation Name', 'wpfnl'); ?></div>
                            <div class="list-cell wpfnl-customers"><?php echo __('Customers', 'wpfnl'); ?></div>
                            <div class="list-cell wpfnl-status"><?php echo __('Status', 'wpfnl'); ?></div>
                            <div class="list-cell list-action"><?php echo __('Action', 'wpfnl'); ?></div>
                        </div>
                        <?php
                        foreach ($automations as $automation) :
                        ?>
                        <div class="funnel__single-list list-body">
                            <!-- <div class="funnel-list__bulk-action">
                                <span class="wpfnl-checkbox no-title">
                                    <input type="checkbox" name="funnel-list-select" id="funnel-list<?php echo $automation['id']; ?>-select" data-id="<?php echo $automation['id']; ?>">
                                    <label for="funnel-list<?php echo $automation['id']; ?>-select"></label>
                                </span>
                            </div> -->
                            <div class="list-cell wpfnl-name">
                                <span class="builder-logo">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/automation-list-icon.php'; ?>
                                </span>
                                <a href="#" class="name" data-id="<?php echo esc_attr( $automation['id'] ); ?>"><?php echo esc_html( $automation['name'] ); ?></a>
                                <span class="steps"><?php echo $automation['created_at']; ?></span>
                            </div>
                            <div class="list-cell wpfnl-customers">
                                <div class="customers-count active-customers">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/calender-icon.php'; ?>
                                    <span><?php echo $automation['enterance']; ?></span>
                                </div>
                                <div class="customers-count paused-customers">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/pause-icon.php'; ?>
                                    <span><?php echo $automation['processing']; ?></span>
                                </div>
                                <div class="customers-count completed-customers">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/automation-check-icon.php'; ?>
                                    <span><?php echo $automation['completed']; ?></span>
                                </div>
                            </div>
                            <div class="list-cell wpfnl-status">
                                <span class="wpfnl-switcher extra-sm no-title">
                                    <input type="checkbox" name="enable-log-status" id="enable-log-status-<?php echo $automation['id']; ?>" data-id="<?php echo esc_attr( $automation['id'] ); ?>" <?php echo ($automation['status'] === 'active') ? 'checked' : ''; ?>>
                                    <label for="enable-log-status-<?php echo $automation['id']; ?>"></label>
                                </span>
                                <span class="wpfnl-status-loader" style="color: #6E42D3; display:none;margin-left:6px;vertical-align:middle; position: absolute; top: 1px;">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation:wpfnl-spin 1s linear infinite;">
                                        <circle cx="7" cy="7" r="6" stroke="currentColor" stroke-opacity="0.25" stroke-width="2"/>
                                        <path d="M13 7a6 6 0 01-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </div>
                            <div class="list-cell list-action">
                                <span class="more-action funnel-list__more-action">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/dot-icon.php'; ?>
                                    <ul class="more-actions wpfnl-dropdown">
                                        <li>
                                            <a href="#" class="wpfnl-edit-automation edit" data-id="<?php echo esc_attr( $automation['id'] ); ?>"><?php echo __('Edit', 'wpfnl'); ?>
                                            </a>
                                            <a href="#" class="wpfnl-delete-automation delete" data-id="<?php echo esc_attr( $automation['id'] ); ?>"><?php echo __('Delete', 'wpfnl'); ?>
                                            </a>
                                        </li>
                                    </ul>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="list-footer">
                            <div class="pagination-number">
                                <p>
                                    <strong><?php echo __('Showing', 'wpfnl'); ?></strong>
                                    <select name="wpfnl_listing_page_offset" id="wpfnl_listing_page_offset">
                                        <option value="10" <?php selected($per_page, 10); ?>>10</option>
                                        <option value="20" <?php selected($per_page, 20); ?>>20</option>
                                        <option value="30" <?php selected($per_page, 30); ?>>30</option>
                                    </select>
                                    <?php
                                    $start = ($current_page - 1) * $per_page + 1;
                                    $end = min($current_page * $per_page, $total);
                                    echo sprintf('%d-%d of %d items', $start, $end, $total);
                                    ?>
                                </p>
                            </div>
                            <div class="pagination">
                                <div class="wpfnl-pagination">
                                    <?php
                                    $base_url = add_query_arg(array(
                                        'page' => 'wpfnl_automations',
                                        'per_page' => $per_page,
                                    ), admin_url('admin.php'));
                                    
                                    // Previous button
                                    $prev_class = ($current_page <= 1) ? 'nav-link prev disabled' : 'nav-link prev';
                                    $prev_url = ($current_page <= 1) ? '#' : add_query_arg('paged', $current_page - 1, $base_url);
                                    ?>
                                    <a href="<?php echo esc_url($prev_url); ?>" class="<?php echo esc_attr($prev_class); ?>">
                                        <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><path fill="#7A8B9A" d="M6.002 12a.856.856 0 01-.609-.25L.25 6.586a.863.863 0 010-1.214L5.393.207a.855.855 0 011.415.62.863.863 0 01-.206.594L2.067 5.974l4.535 4.554a.862.862 0 01-.6 1.472z"/><path fill="#7A8B9A" d="M11.147 12a.856.856 0 01-.61-.25L5.395 6.586a.863.863 0 010-1.214L10.538.207a.855.855 0 011.414.62.862.862 0 01-.205.594L7.21 5.974l4.536 4.554a.862.862 0 01-.6 1.472z"/></svg>
                                    </a>
                                    <?php
                                    // Page numbers
                                    $range = 2;
                                    for ($i = 1; $i <= $total_pages; $i++) {
                                        if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
                                            $active_class = ($i == $current_page) ? 'nav-link active' : 'nav-link';
                                            $page_url = add_query_arg('paged', $i, $base_url);
                                            echo '<a href="' . esc_url($page_url) . '" class="' . esc_attr($active_class) . '">' . $i . '</a>';
                                        } elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1) {
                                            echo '<span class="nav-link dots">...</span>';
                                        }
                                    }
                                    
                                    // Next button
                                    $next_class = ($current_page >= $total_pages) ? 'nav-link next disabled' : 'nav-link next';
                                    $next_url = ($current_page >= $total_pages) ? '#' : add_query_arg('paged', $current_page + 1, $base_url);
                                    ?>
                                    <a href="<?php echo esc_url($next_url); ?>" class="<?php echo esc_attr($next_class); ?>">
                                        <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><path fill="#7A8B9A" d="M5.998 12a.856.856 0 00.609-.25l5.144-5.164a.863.863 0 000-1.214L6.607.207a.855.855 0 00-1.415.62.863.863 0 00.206.594l4.535 4.553-4.535 4.554a.862.862 0 00.6 1.472z"/><path fill="#7A8B9A" d="M.853 12a.856.856 0 00.61-.25l5.143-5.164a.863.863 0 000-1.214L1.462.207a.855.855 0 00-1.414.62.863.863 0 00.205.594L4.79 5.974.253 10.528A.862.862 0 00.853 12z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>


                    <?php else: ?>
                    <div class="wpfnl-funnel-automation-pro-settings-wrapper">
                        <div class="feature-content-wrapper">
                            <div class="feature-card-wrapper">
                                <div class="single-card">
                                    <div class="card-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/welcome-email.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h2 class="card-title">Opt-in Welcome Email</h2>
                                    <div class="logo-wrapper">
                                        <img src="<?php echo esc_url( WPFNL_URL . 'admin/assets/images/automation-loader.png' ); ?>" alt="<?php esc_attr_e('Loader', 'wpfnl'); ?>">
                                    </div>
                                </div>

                                <div class="single-card">
                                    <div class="card-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/login-notification.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h2 class="card-title">User Login Notification</h2>
                                    <div class="logo-wrapper">
                                        <img src="<?php echo esc_url( WPFNL_URL . 'admin/assets/images/automation-loader.png' ); ?>" alt="<?php esc_attr_e('Loader', 'wpfnl'); ?>">
                                    </div>
                                </div>

                                <div class="single-card">
                                    <div class="card-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/first-order-in-store.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h2 class="card-title">WC - First Order In Store</h2>
                                    <div class="logo-wrapper">
                                        <img src="<?php echo esc_url( WPFNL_URL . 'admin/assets/images/automation-loader.png' ); ?>" alt="<?php esc_attr_e('Loader', 'wpfnl'); ?>">
                                    </div>
                                </div>

                                <div class="single-card">
                                    <div class="card-icon">
                                        <?php
                                        $svg_path = WPFNL_PATH . '/admin/assets/images/new-user-welcome-email.svg';
                                        if (file_exists($svg_path)) {
                                            echo file_get_contents($svg_path);
                                        }
                                        ?>
                                    </div>
                                    <h2 class="card-title">New User Welcome Email</h2>
                                    <div class="logo-wrapper">
                                        <img src="<?php echo esc_url( WPFNL_URL . 'admin/assets/images/automation-loader.png' ); ?>" alt="<?php esc_attr_e('Loader', 'wpfnl'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="feature-text-wrapper">
                                <h2 class="feature-heading">Quick-start with ready-made recipes</h2>
                                <p class="feature-des">Save time by importing and editing our ever-growing library of practical automation recipes to fit your needs</p>
                            </div>
                        </div>
                        <!--  -->
                        <div class="feature-content-wrapper">
                            <div class="feature-card-wrapper feature-card-img">
                                <img src="<?php echo esc_url( WPFNL_URL . 'admin/assets/images/build-automation.webp' ); ?>" alt="<?php esc_attr_e('Mail Mint logo', 'wpfnl'); ?>">
                            </div>
                            <div class="feature-text-wrapper">
                                <h2 class="feature-heading">Build any automation you imagine</h2>
                                <p class="feature-des">save time by importing and editing our ever-growing library of practical automation recipes to fit your needs</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {    
        // Handle per page dropdown change
        $('#wpfnl_listing_page_offset').on('change', function() {
            var perPage = $(this).val();
            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('per_page', perPage);
            currentUrl.searchParams.set('paged', 1); // Reset to first page
            window.location.href = currentUrl.toString();
        });

        // wpfnl-admin.js calls e.stopPropagation() on every .wpfnl-dropdown click,
        // which prevents events from bubbling to document-level listeners.
        // Using direct element binding fires BEFORE stopPropagation is called on the
        // parent .wpfnl-dropdown element, so both Edit and Delete work correctly.

        // Handle automation name click - open the canvas editor
        document.querySelectorAll('.wpfnl-name .name').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = link.getAttribute('data-id');
                if (typeof window.wpfnlOpenAutomationModal === 'function') {
                    window.wpfnlOpenAutomationModal(id);
                }
            });
        });

        // Handle status toggle - PUT to REST API
        document.querySelectorAll('.wpfnl-status input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var automationId = checkbox.getAttribute('data-id');
                var newStatus    = checkbox.checked ? 'active' : 'pause';
                var restUrl      = wpfnlAutomationVars.restUrl + 'wpfunnels/v1/automation-canvas/' + automationId;
                var loader       = checkbox.closest('.list-cell.wpfnl-status').querySelector('.wpfnl-status-loader');

                checkbox.disabled    = true;
                loader.style.display = 'inline-block';

                $.ajax({
                    url: restUrl,
                    type: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({ status: newStatus }),
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', wpfnlAutomationVars.restNonce);
                    },
                    success: function() {
                        checkbox.disabled    = false;
                        loader.style.display = 'none';
                    },
                    error: function() {
                        checkbox.checked     = !checkbox.checked; // revert
                        checkbox.disabled    = false;
                        loader.style.display = 'none';
                        alert('Failed to update automation status. Please try again.');
                    }
                });
            });
        });

        // Keyframe animation for the status loader spinner (injected once)
        if (!document.getElementById('wpfnl-spin-style')) {
            var style = document.createElement('style');
            style.id  = 'wpfnl-spin-style';
            style.textContent = '@keyframes wpfnl-spin { to { transform: rotate(360deg); } }';
            document.head.appendChild(style);
        }

        // Handle edit automation - open the React modal via the exposed global
        document.querySelectorAll('.wpfnl-edit-automation').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                btn.closest('.funnel-list__more-action').classList.remove('show-actions');
                var id = btn.getAttribute('data-id');
                if (typeof window.wpfnlOpenAutomationModal === 'function') {
                    window.wpfnlOpenAutomationModal(id);
                }
            });
        });

        // Handle delete automation - direct REST API call
        document.querySelectorAll('.wpfnl-delete-automation').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                btn.closest('.funnel-list__more-action').classList.remove('show-actions');

                var automationId = btn.getAttribute('data-id');

                if ( ! confirm('Are you sure you want to delete this automation? This action cannot be undone.') ) {
                    return;
                }

                var restUrl = wpfnlAutomationVars.restUrl + 'wpfunnels/v1/automation-canvas/' + automationId;
                var row = btn.closest('.funnel__single-list');
                row.style.opacity = '0.5';

                $.ajax({
                    url: restUrl,
                    type: 'DELETE',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', wpfnlAutomationVars.restNonce);
                    },
                    success: function() {
                        $(row).remove();
                    },
                    error: function() {
                        row.style.opacity = '1';
                        alert('Failed to delete automation. Please try again.');
                    }
                });
            });
        });
    });
    </script>

<?php endif; ?>

<!-- Automation Canvas Root -->
<div id="wpfnl-automation-canvas-root"></div>
