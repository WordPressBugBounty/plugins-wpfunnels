<?php

namespace WPFunnels\Admin\Banner;

/**
 * SetupReminderBanner Class
 *
 * This class is responsible for displaying setup reminder banners in the WordPress admin
 * based on user behavior events such as plugin installation without funnel creation.
 *
 * @package WPFunnels\Admin\Banner
 * @since 3.9.3
 */
class SetupReminderBanner{
    /**
     * The banner identifier.
     *
     * @var string
     */
    private $banner_id;

    /**
     * The option key for dismissing the banner.
     *
     * @var string
     */
    private $dismiss_option_key;

    /**
     * Hours to wait before showing the banner.
     *
     * @var int
     */
    private $hours_delay;

    /**
     * Constructor method for SetupReminderBanner class.
     *
     * @since 3.9.3
     */
    public function __construct()
    {
        $this->banner_id = 'no_funnel_48h';
        $this->dismiss_option_key = '_wpfnl_dismiss_no_funnel_banner';
        $this->hours_delay = 48;

        // Register hooks - actual condition check happens in display_banner
        add_action('admin_notices', [$this, 'display_banner']);
        add_action('admin_head', [$this, 'add_styles']);

        // Register AJAX handler for dismissing the banner
        $validations = [
            'logged_in' => true,
            'user_can'  => 'wpf_manage_funnels',
        ];

        wp_ajax_helper()->handle('dismiss_first_funnel_setup_reminder_banner')
            ->with_callback([$this, 'dismiss_banner'])
            ->with_validation($validations);
    }

    /**
     * Check if the banner should be displayed.
     *
     * Conditions:
     * 1. Banner has not been dismissed
     * 2. At least 48 hours have passed since installation
     * 3. No funnels have been created
     *
     * @return bool
     * @since 3.9.3
     */
    private function should_show_banner(){
        // Check if banner was dismissed
        if ('yes' === get_option($this->dismiss_option_key, 'no')) {
            return false;
        }

        // Check if 48 hours have passed since installation
        $installed_time = get_option('wpfunnels_installed_time', 0);
        if (!$installed_time) {
            return false;
        }

        $hours_since_install = (time() - $installed_time) / 3600;
        if ($hours_since_install < $this->hours_delay) {
            return false;
        }

        // Check if any funnels exist
        $funnel_count = $this->get_funnel_count();
        if ($funnel_count > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the total count of funnels.
     *
     * @return int
     * @since 3.9.3
     */
    private function get_funnel_count()
    {
        $counts = wp_count_posts('wpfunnels');
        return (int) $counts->publish + (int) $counts->draft;
    }

    /**
     * Displays the setup reminder banner.
     *
     * @since 3.9.3
     */
    public function display_banner(){
        // Check conditions before displaying
        if (!$this->should_show_banner()) {
            return;
        }

        $screen = get_current_screen();
        // Pages where the banner should NOT be displayed
        $disabled_notice_pages = array(
            'toplevel_page_wpfunnels',
            'wpfunnels_page_wpfnl_settings',
            'wpfunnels_page_wp_funnels',
            'wpfunnels_page_wpf_templates',
            'wpfunnels_page_trash_funnels',
        );

        if (in_array($screen->id, $disabled_notice_pages)) {
            return;
        }

        // Get the URL to create a new funnel
        $create_funnel_url = admin_url('admin.php?page=wp_funnels');
        $learn_more_url = 'https://getwpfunnels.com/docs/getting-started-with-wpfunnels/create-your-first-sales-funnel/';
        $dismiss_nonce = wp_create_nonce('dismiss_first_funnel_setup_reminder_banner');

        $general_settings = get_option('_wpfunnels_general_settings', []);
        $funnel_type = isset($general_settings['funnel_type']) ? $general_settings['funnel_type'] : 'sales';

        if ('lead' === $funnel_type) {
            $banner_title = esc_html__('Let’s launch your first lead funnel in 5 minutes!', 'wpfnl');
            $banner_description = esc_html__('Finish setting up your first lead funnel and start collecting more leads with better conversions.', 'wpfnl');
        } else {
            $banner_title = esc_html__('Let’s launch your first WooCommerce funnel in 5 minutes!', 'wpfnl');
            $banner_description = esc_html__('Finish setting up your first funnel and start increasing your WooCommerce revenue with upsells, order bumps & better conversions.', 'wpfnl');
        }
        ?>
        <div class="notice wpfnl-setup-reminder-notice" data-notice-id="<?php echo esc_attr($this->banner_id); ?>">
            <div class="wpfnl-notice-icon">
                <svg width="36" height="28" viewBox="0 0 38 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7.01532 18H31.9847L34 11H5L7.01532 18Z" fill="#EE8134"></path>
                    <path d="M11.9621 27.2975C12.0923 27.7154 12.4792 28 12.9169 28H26.0831C26.5208 28 26.9077 27.7154 27.0379 27.2975L29 21H10L11.9621 27.2975Z" fill="#6E42D3"></path>
                    <path d="M37.8161 0.65986C37.61 0.247888 37.2609 0 36.8867 0H1.11326C0.739128 0 0.390003 0.247888 0.183972 0.65986C-0.0220592 1.07193 -0.0573873 1.59277 0.0898627 2.04655L1.69781 7H36.3022L37.9102 2.04655C38.0574 1.59287 38.022 1.07193 37.8161 0.65986Z" fill="#6E42D3"></path>
                </svg>
            </div>
            <div class="wpfnl-notice-content">
                <h3 class="wpfnl-notice-title"><?php echo $banner_title; ?></h3>
                <p class="wpfnl-notice-description"><?php echo $banner_description; ?></p>
                <div class="wpfnl-notice-actions">
                    <a href="<?php echo esc_url($create_funnel_url); ?>" class="wpfnl-notice-btn wpfnl-notice-btn-primary">
                        <?php echo esc_html__('Resume setup', 'wpfnl'); ?>
                    </a>
                    <a href="<?php echo esc_url($learn_more_url); ?>" class="wpfnl-notice-btn wpfnl-notice-btn-secondary" target="_blank">
                        <?php echo esc_html__('Show me how', 'wpfnl'); ?>
                    </a>
                </div>
            </div>
            <button type="button" class="wpfnl-notice-dismiss" aria-label="<?php echo esc_attr__('Dismiss this notice', 'wpfnl'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $(document).on('click', '.wpfnl-notice-dismiss', function() {
                    $(this).closest('.wpfnl-setup-reminder-notice').fadeOut(200);
                    wp.ajax.send('dismiss_first_funnel_setup_reminder_banner', {
                        data: {
                            nonce: '<?php echo esc_js($dismiss_nonce); ?>',
                            payload: {}
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Adds internal CSS styles for the setup reminder banner.
     *
     * @since 3.9.3
     */
    public function add_styles()
    {
        // Only add styles if banner should be shown
        if (!$this->should_show_banner()) {
            return;
        }
        ?>
        <style type="text/css">
            .notice.wpfnl-setup-reminder-notice {
                display: flex;
                align-items: flex-start;
                gap: 16px;
                background: #fff;
                border: 1px solid #e0e0e0;
                border-left: 4px solid #6E42D3;
                border-radius: 4px;
                padding: 16px 20px;
                margin: 5px 0 15px;
                position: relative;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-icon {
                flex-shrink: 0;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-icon svg {
                width: 36px;
                height: auto;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-content {
                flex: 1;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-title {
                font-size: 14px;
                font-weight: 600;
                color: #1e1e1e;
                margin: 0 0 4px 0;
                padding: 0;
                line-height: 1.4;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-description {
                font-size: 13px;
                color: #50575e;
                margin: 0 0 12px 0;
                padding: 0;
                line-height: 1.5;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-btn {
                display: inline-flex;
                align-items: center;
                padding: 8px 16px;
                font-size: 13px;
                font-weight: 500;
                line-height: 1;
                text-decoration: none;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-btn-primary {
                background: #6E42D3;
                color: #fff;
                border: 1px solid #6E42D3;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-btn-primary:hover {
                background: #5a35b0;
                border-color: #5a35b0;
                color: #fff;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-btn-secondary {
                background: #fff;
                color: #6E42D3;
                border: 1px solid #6E42D3;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-btn-secondary:hover {
                background: #f3f0fa;
                color: #5a35b0;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-dismiss {
                position: absolute;
                top: 12px;
                right: 12px;
                background: none;
                border: none;
                padding: 0;
                cursor: pointer;
                color: #787c82;
                line-height: 1;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-dismiss:hover {
                color: #1e1e1e;
            }

            .wpfnl-setup-reminder-notice .wpfnl-notice-dismiss .dashicons {
                font-size: 20px;
                width: 20px;
                height: 20px;
            }

            @media screen and (max-width: 782px) {
                .wpfnl-setup-reminder-notice {
                    flex-direction: column;
                    gap: 12px;
                    margin: 10px;
                }

                .wpfnl-setup-reminder-notice .wpfnl-notice-actions {
                    flex-direction: column;
                }

                .wpfnl-setup-reminder-notice .wpfnl-notice-btn {
                    justify-content: center;
                }
            }
        </style>
        <?php
    }

    /**
     * AJAX handler to dismiss the banner.
     *
     * @param array $payload The AJAX payload.
     * @return array
     * @since 3.9.3
     */
    public function dismiss_banner($payload)
    {
        update_option($this->dismiss_option_key, 'yes');
        return [
            'success' => true,
        ];
    }
}
