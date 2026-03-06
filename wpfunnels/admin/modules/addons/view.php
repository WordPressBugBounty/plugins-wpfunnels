<?php

/**
 * View Add-ons
 *
 * @package
 */

$lms_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="18" viewBox="0 0 24 18" fill="none"><path d="M10.3885 15.3899H1.87768C1.25728 15.3899 0.75 14.8826 0.75 14.2623V1.87762C0.75 1.25716 1.25734 0.75 1.87768 0.75H10.6213C11.2412 0.75 11.7489 1.25722 11.7489 1.87762V16.7503" stroke="#7a8b9a" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="M3.85913 3.86351H8.63964" stroke="#7a8b9a" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="M3.85913 6.56693H8.63964" stroke="#7a8b9a" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="M3.85913 9.26952H5.70269" stroke="#7a8b9a" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="M10.3884 15.3898C11.137 15.3898 11.7488 16.0017 11.7488 16.7502" stroke="#7a8b9a" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="M13.1093 15.3899H21.62C22.2405 15.3899 22.7478 14.8826 22.7478 14.2623V1.87761C22.7478 1.25716 22.2405 0.75 21.62 0.75H12.8766C12.2566 0.75 11.7488 1.25722 11.7488 1.87761" stroke="#7a8b9a" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="M13.1092 15.3898C12.3608 15.3898 11.7488 16.0017 11.7488 16.7502" stroke="#7a8b9a" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/></svg>';

$global_funnel_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="26" viewBox="0 0 18 26" fill="none"><path d="M2.29199 13.9297L7.73926 19.8359C7.87347 19.9819 7.95018 20.1773 7.9502 20.3818V23.7959L8.16895 23.6816L9.96973 22.7451L10.0498 22.7031V20.3818C10.0498 20.1774 10.1266 19.9819 10.2607 19.8359L15.708 13.9297L15.9404 13.6777H2.05957L2.29199 13.9297ZM9 4.61523C10.4003 4.61523 11.5498 5.80303 11.5498 7.27441C11.5497 8.74566 10.4002 9.93262 9 9.93262C7.59983 9.9326 6.45034 8.74565 6.4502 7.27441C6.4502 5.80305 7.59974 4.61525 9 4.61523ZM9 6.1875C8.41483 6.18752 7.9502 6.6804 7.9502 7.27441C7.95034 7.8683 8.41493 8.36033 9 8.36035C9.58509 8.36035 10.0497 7.86831 10.0498 7.27441C10.0498 6.68039 9.58518 6.1875 9 6.1875ZM6.4502 20.6992L6.41016 20.6562L0.489258 14.2373H0.488281C0.151996 13.8749 0.0561205 13.335 0.248047 12.8623C0.43684 12.3991 0.86569 12.1056 1.33594 12.1055H16.6641C17.134 12.1056 17.563 12.3986 17.752 12.8613V12.8623C17.9199 13.2759 17.8675 13.7408 17.625 14.0947L17.5098 14.2393L11.5898 20.6562L11.5498 20.6992V23.1904C11.5498 23.4538 11.4252 23.6951 11.2246 23.8398L11.1338 23.8955L7.53418 25.7676C7.3266 25.8745 7.09212 25.8751 6.89258 25.7803L6.80859 25.7344L6.72949 25.6758C6.55481 25.528 6.45027 25.3043 6.4502 25.0635V20.6992ZM14.5498 6.65625L14.417 6.64062L13.5391 6.54199C13.2458 6.5086 12.9957 6.29791 12.9053 5.99902C12.7173 5.38446 12.4306 4.87076 12.0186 4.40234C11.817 4.17275 11.7631 3.83802 11.8848 3.55176L12.291 2.59082L12.1797 2.52441L11.374 2.04004L11.2549 1.96777L11.1738 2.08203L10.6504 2.8252C10.4732 3.07536 10.1759 3.19048 9.88379 3.12598H9.88281C9.36264 3.00406 8.84115 2.9892 8.33008 3.07715L8.1123 3.12012C7.82439 3.18513 7.52389 3.07115 7.34668 2.81934L6.82715 2.08105L6.74609 1.96777L6.62695 2.03906L5.82129 2.52344L5.70996 2.58984L5.76074 2.70996L6.11523 3.54883V3.5498C6.22143 3.80016 6.19374 4.08789 6.0498 4.30957L5.98047 4.40039C5.57863 4.85632 5.28296 5.39406 5.10059 5.99805C5.02127 6.25965 4.81933 6.45388 4.57422 6.52051L4.4668 6.54102L3.58301 6.64062L3.4502 6.65625V7.8916L3.58301 7.90625L4.46094 8.00586C4.7542 8.03925 5.00427 8.24896 5.09473 8.54785V8.54883C5.18939 8.8572 5.3089 9.14032 5.44922 9.39453L5.59668 9.63867C5.8177 9.97859 5.74904 10.4313 5.45605 10.6865L5.39453 10.7344C5.0744 10.9592 4.64829 10.8923 4.40332 10.5879L4.35645 10.5244C4.16282 10.2264 4.00346 9.91994 3.86914 9.59668L3.83496 9.51465L3.74707 9.50488L2.61914 9.37695H2.61816C2.24313 9.33617 1.9502 9.00291 1.9502 8.59668V5.95117C1.9502 5.54535 2.24299 5.21314 2.61914 5.16992L3.75098 5.04004L3.83887 5.03027L3.87305 4.94824C4.04152 4.54031 4.25309 4.15768 4.50391 3.80371L4.55176 3.73535L4.51953 3.65918L4.0625 2.57812C3.90549 2.20754 4.0452 1.77612 4.37598 1.57715L6.57812 0.253906C6.90747 0.0561305 7.32782 0.147478 7.55469 0.469727L8.22461 1.4209L8.27637 1.49414L8.36523 1.4834C8.7839 1.43339 9.20718 1.43551 9.62988 1.48828L9.71973 1.49902L9.77148 1.42578L10.4453 0.469727C10.6742 0.147773 11.0918 0.0577129 11.4229 0.255859V0.254883L13.624 1.57812C13.9134 1.75219 14.0564 2.10403 13.9824 2.4375L13.9375 2.5791L13.4795 3.66211L13.4473 3.73926L13.4961 3.80762C13.7599 4.17597 13.9612 4.54612 14.1309 4.95117L14.165 5.03223L14.2529 5.04199L15.3818 5.1709C15.7571 5.21252 16.0498 5.54504 16.0498 5.95117V8.59668C16.0498 9.00251 15.757 9.33569 15.3809 9.37891L14.2471 9.50781L14.1592 9.51758L14.126 9.59961C13.9916 9.92311 13.8304 10.2314 13.6445 10.5215C13.4114 10.8847 12.9486 10.9744 12.6084 10.7383C12.2624 10.498 12.1659 10.0078 12.3994 9.64453C12.5599 9.39444 12.6944 9.12434 12.8018 8.83984L12.9004 8.55078C12.9796 8.28902 13.1816 8.09502 13.4268 8.02832L13.5342 8.00781L14.417 7.90723L14.5498 7.89258V6.65625Z" fill="#7a8b9a" stroke="#fff" stroke-width=".3"/></svg>';

$checkoutify_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="none"><path d="M10.0633 18.7895C8.76841 18.7895 7.71463 19.8433 7.71463 21.1382C7.71463 22.4331 8.76841 23.4868 10.0633 23.4868C11.3582 23.4868 12.412 22.4331 12.412 21.1382C12.412 19.8433 11.3582 18.7895 10.0633 18.7895ZM10.0633 21.9211C9.63116 21.9211 9.28042 21.5695 9.28042 21.1382C9.28042 20.7068 9.63116 20.3553 10.0633 20.3553C10.4955 20.3553 10.8462 20.7068 10.8462 21.1382C10.8462 21.5695 10.4955 21.9211 10.0633 21.9211ZM17.8923 18.7895C16.5974 18.7895 15.5436 19.8433 15.5436 21.1382C15.5436 22.4331 16.5974 23.4868 17.8923 23.4868C19.1872 23.4868 20.241 22.4331 20.241 21.1382C20.241 19.8433 19.1872 18.7895 17.8923 18.7895ZM17.8923 21.9211C17.4601 21.9211 17.1094 21.5695 17.1094 21.1382C17.1094 20.7068 17.4601 20.3553 17.8923 20.3553C18.3244 20.3553 18.6752 20.7068 18.6752 21.1382C18.6752 21.5695 18.3244 21.9211 17.8923 21.9211ZM23.1424 6.43384C22.6945 5.82788 22.0056 5.48027 21.2525 5.48027H6.27881L5.49669 2.70726C5.30802 2.03475 4.68796 1.5658 3.98884 1.5658H2.23437C1.80143 1.5658 1.45148 1.91653 1.45148 2.34869C1.45148 2.78085 1.80143 3.13159 2.23437 3.13159H3.98962L4.92988 6.46672L6.57161 13.5824C7.0664 15.726 8.94848 17.2237 11.1492 17.2237H17.3497C19.4244 17.2237 21.2297 15.8912 21.8396 13.9073L23.497 8.52025C23.7186 7.80077 23.5894 7.0398 23.1424 6.43384ZM22.0001 8.05912L20.3427 13.447C19.9356 14.7693 18.7331 15.6579 17.3497 15.6579H11.1492C9.68205 15.6579 8.42707 14.6597 8.09825 13.2309L6.67104 7.04606H21.2525C21.5038 7.04606 21.7331 7.16193 21.8827 7.36391C22.0314 7.5659 22.0745 7.81878 22.0001 8.05912Z" fill="#7a8b9a"/></svg>';

// Include plugin functions if not already loaded
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Load integration icon via output buffering
ob_start();
include WPFNL_DIR . '/admin/partials/icons/integration-icon.php';
$integration_addon_icon = ob_get_clean();

// Load storewide checkout icon via output buffering
ob_start();
include WPFNL_DIR . '/admin/partials/icons/storewide-checkout-icon.php';
$storewide_checkout_icon = ob_get_clean();

// Define addons array
$addons = array(
    array(
        'title'       => __('Integrations', 'wpfnl'),
        'description' => __('Connect WPFunnels with marketing tools, automate customer journey, sync contacts, and trigger campaigns — all without leaving your funnel.', 'wpfnl'),
        'badge'       => __('Hot', 'wpfnl'),
        'guide_link'  => 'https://getwpfunnels.com/docs/funnel-integrations/',
        'cta_link'    => 'https://getwpfunnels.com/pricing/',
        'cta_text'    => __('Buy Now', 'wpfnl'),
        'icon'        => $integration_addon_icon,
        'plugin_file' => 'wpfunnels-pro-integrations/wpfunnels-pro-integrations.php',
    ),
    array(
        'title'       => __('Storewide Checkout (Global Funnel)', 'wpfnl'),
        'description' => __('Apply a single funnel flow across your entire store and replace the default WooCommerce checkout - no need to create funnels individually.', 'wpfnl'),
        'badge'       => __('Hot', 'wpfnl'),
        'guide_link'  => 'https://getwpfunnels.com/docs/global-funnels-for-woocommerce/',
        'cta_link'    => 'https://getwpfunnels.com/pricing/',
        'cta_text'    => __('Buy Now', 'wpfnl'),
        'icon'        => $storewide_checkout_icon,
        'plugin_file' => 'wpfunnels-pro-gbf/wpfnl-pro-gb.php',
    ),
    array(
        'title'       => __('LMS', 'wpfnl'),
        'description' => __('Seamlessly sell and deliver online courses, manage students, and automate access after purchase — all inside your funnel ecosystem.', 'wpfnl'),
        'badge'       => '',
        'guide_link'  => 'https://getwpfunnels.com/docs/sales-funnels-for-courses/',
        'cta_link'    => 'https://getwpfunnels.com/pricing/',
        'cta_text'    => __('Buy Now', 'wpfnl'),
        'icon'        => $lms_icon,
        'plugin_file' => 'wpfunnels-pro-lms/wpfunnels-pro-lms.php',
    ),
    array(
        'title'       => __('Checkoutify', 'wpfnl'),
        'description' => __('Use the most unique checkout field editor for WooCommerce to design and optimize your checkout form with full control.', 'wpfnl'),
        'badge'       => '',
        'guide_link'  => 'https://getwpfunnels.com/docs/checkoutify-documentations-guides/',
        'cta_link'    => 'https://getwpfunnels.com/pricing/',
        'cta_text'    => __('Buy Now', 'wpfnl'),
        'icon'        => $checkoutify_icon,
        'plugin_file' => 'checkoutify/checkoutify.php',
    ),
);

// Allow plugins to add their own addons
$addons = apply_filters('wpfnl_recommended_addons', $addons);
?>

<div class="wpfnl">
    <div class="wpfunnels-addons">
        <div class="wpfnl-dashboard">
            <nav class="wpfnl-dashboard__nav">
                <?php use WPFunnels\Wpfnl_functions;
                require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>
            </nav>

            <div class="dashboard-nav__content">
                <div class="addons-content">
                    <h2 class="section-title"><?php echo __('Recommended Plugins', 'wpfnl'); ?></h2>
                    
                    <div class="wpfunnels-addons-grid">
                        <?php foreach ($addons as $addon) {
                            // Check plugin status
                            $is_installed = false;
                            $is_active = false;
                            
                            if (!empty($addon['plugin_file'])) {
                                $plugin_path = WP_PLUGIN_DIR . '/' . $addon['plugin_file'];
                                $is_installed = file_exists($plugin_path);
                                $is_active = is_plugin_active($addon['plugin_file']);
                            }
                        ?>
                            <div class="wpfunnels-addons-card">
                                <?php if (!empty($addon['badge'])) : ?>
                                    <span class="badge hot"><?php echo esc_html($addon['badge']); ?></span>
                                <?php endif; ?>
                                
                                <div class="addons-icon">
                                    <?php echo $addon['icon']; ?>
                                </div>
                                
                                <h3 class="addons-title"><?php echo esc_html($addon['title']); ?></h3>
                                
                                <p class="addons-description">
                                    <?php echo esc_html($addon['description']); ?>
                                    <?php if (!empty($addon['guide_link'])) { ?>
                                        <a href="<?php echo esc_url($addon['guide_link']); ?>" class="guide-link" target="_blank">
                                            <?php echo __('Guide', 'wpfnl'); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M0.799762 8.35028C1.04384 8.59436 1.43956 8.59436 1.68365 8.35028L8.76699 1.26693C9.01107 1.02284 9.01107 0.627178 8.76699 0.383095C8.5229 0.139011 8.12715 0.139011 7.88307 0.383095L0.799762 7.46639C0.555678 7.71048 0.555678 8.1062 0.799762 8.35028Z" fill="#6e42d3" stroke="#6e42d3" stroke-width=".4"/><path d="M8.32498 8.95003C8.67015 8.95003 8.94998 8.67021 8.94998 8.32503V0.824994C8.94998 0.479828 8.67015 0.199994 8.32498 0.199994H0.824951C0.479768 0.199994 0.199951 0.479828 0.199951 0.824994C0.199951 1.17024 0.479768 1.44999 0.824951 1.44999H7.69998V8.32503C7.69998 8.67021 7.97973 8.95003 8.32498 8.95003Z" fill="#6e42d3" stroke="#6e42d3" stroke-width=".4"/></svg>
                                        </a>
                                    <?php } ?>
                                </p>
                                
                                <?php if ($is_active) : ?>
                                    <!-- Active Status -->
                                    <button class="addons-cta-button active-status" disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="9" viewBox="0 0 12 9" fill="none"><path d="M11.7212 0.278745C11.3499 -0.093032 10.747 -0.0927976 10.3752 0.278745L4.31744 6.33674L1.62503 3.64435C1.25325 3.27257 0.650609 3.27257 0.278832 3.64435C-0.0929441 4.01613 -0.0929441 4.61877 0.278832 4.99055L3.6442 8.35592C3.82998 8.54169 4.07357 8.63481 4.31719 8.63481C4.5608 8.63481 4.80463 8.54192 4.9904 8.35592L11.7212 1.62492C12.0929 1.2534 12.0929 0.650498 11.7212 0.278745Z" fill="#2fcf5c"/></svg>
                                        <?php echo __('Activated', 'wpfnl'); ?>
                                    </button>
                                <?php elseif ($is_installed) : ?>
                                    <!-- Activate Button -->
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('plugins.php?action=activate&plugin=' . urlencode($addon['plugin_file'])), 'activate-plugin_' . $addon['plugin_file'])); ?>" class="addons-cta-button activate-button" title="<?php echo esc_attr__('Activate', 'wpfnl'); ?>">
                                        <?php echo __('Activate', 'wpfnl'); ?>
                                    </a>
                                <?php else : ?>
                                    <!-- Buy Now Button -->
                                    <a href="<?php echo esc_url($addon['cta_link']); ?>" class="addons-cta-button" target="_blank" title="<?php echo esc_attr($addon['cta_text']); ?>" aria-label="<?php echo esc_attr($addon['cta_text']); ?>">
                                        <?php echo esc_html($addon['cta_text']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
