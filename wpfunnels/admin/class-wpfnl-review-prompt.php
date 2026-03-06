<?php
namespace WPFunnels\Admin;

class Wpfnl_Review_Prompt
{

    const STATUS_OPTION = 'wpfnl_review_prompt_status'; // 'completed' or 'snoozed'
    const SNOOZE_OPTION = 'wpfnl_review_prompt_snooze_time';
    const PRODUCT_SLUG = 'wpfunnels';
    const LARK_WEBHOOK = 'https://linnoglobal.sg.larksuite.com/base/automation/webhook/event/HTJ5anoXFwDUvxhmMbOltaNRgVc';
    const MIN_FEEDBACK_LENGTH = 50;

    /**
     * Cached decision on whether the prompt should show for this request.
     *
     * @var bool|null
     */
    private $should_show_cache = null;

    /**
     * Hook WordPress actions required for the review prompt lifecycle.
     */
    public function __construct()
    {
        add_action('admin_footer', [$this, 'render_prompt']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_wpfnl_review_action', [$this, 'handle_ajax']);
    }

    /**
     * Determine whether the review prompt should be rendered in the current context.
     *
     * @return bool
     */
    public function should_show()
    {
        if (null !== $this->should_show_cache) {
            return $this->should_show_cache;
        }

        if (!current_user_can('manage_options')) {
            $this->should_show_cache = false;
            return false;
        }

        if (!$this->is_wpfnl_admin_screen()) {
            $this->should_show_cache = false;
            return false;
        }

        if (isset($_GET['wpfnl_test_review']) && '1' === $_GET['wpfnl_test_review']) {
            $this->should_show_cache = true;
            return true;
        }

        $status = get_option(self::STATUS_OPTION);
        if ('completed' === $status) {
            $this->should_show_cache = false;
            return false;
        }

        $snooze_time = get_option(self::SNOOZE_OPTION);
        if ($snooze_time && current_time('timestamp') < $snooze_time + (30 * DAY_IN_SECONDS)) {
            $this->should_show_cache = false;
            return false;
        }

        $installed_time = get_option('wpfunnels_installed_time');
        if (!$installed_time || current_time('timestamp') < $installed_time + (3 * DAY_IN_SECONDS)) {
            $this->should_show_cache = false;
            return false;
        }

        $has_funnel = false;
        $count_funnels = wp_count_posts('wpfunnels');
        if (isset($count_funnels->publish) && $count_funnels->publish > 0) {
            $has_funnel = true;
        }

        $has_order = false;
        if (!$has_funnel) {
            global $wpdb;
            $order_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wpfnl_stats WHERE status = %s LIMIT 1", 'completed'));
            if ($order_id) {
                $has_order = true;
            }
        }

        if (!$has_funnel && !$has_order) {
            $this->should_show_cache = false;
            return false;
        }

        $this->should_show_cache = true;
        return true;
    }

    /**
     * Output the review prompt markup inside the admin footer.
     *
     * @return void
     */
    public function render_prompt()
    {
        if (!$this->should_show()) {
            return;
        }
?>
<div id="wpfnl-review-prompt">
    <div class="wpfnl-review-header">
        <span>Share Your Feedback</span>
        <span class="close-review-prompt" id="wpfnl-review-close">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M15 5L5 15" stroke="#99a1af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 5L15 15" stroke="#99a1af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
    </div>
    <div class="wpfnl-review-body">
        <div class="wpfnl-review-text" id="wpfnl-review-text">
            Is WPFunnels helping you grow your WooCommerce store?
        </div>

        <div class="wpfnl-review-options" id="wpfnl-review-options">
            <a href="https://wordpress.org/support/plugin/wpfunnels/reviews/#new-post" target="_blank"
                class="wpfnl-review-btn" id="wpfnl-review-yes">
                <span class="emoji">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><g clip-path="url(#a)"><path d="M9.99984 18.3333C14.6022 18.3333 18.3332 14.6023 18.3332 9.99996C18.3332 5.39759 14.6022 1.66663 9.99984 1.66663C5.39746 1.66663 1.6665 5.39759 1.6665 9.99996C1.6665 14.6023 5.39746 18.3333 9.99984 18.3333Z" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.6665 11.6666C6.6665 11.6666 7.9165 13.3333 9.99984 13.3333C12.0832 13.3333 13.3332 11.6666 13.3332 11.6666" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 7.5H7.50833" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.5 7.5H12.5083" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="a"><rect width="20" height="20" fill="#fff"/></clipPath></defs></svg>
                </span> 
                Yes, it's great!
            </a>

            <button type="button" class="wpfnl-review-btn" id="wpfnl-review-okay" data-feedback-type="neutral">
                <span class="emoji">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><g clip-path="url(#a)"><path d="M9.99984 18.3333C14.6022 18.3333 18.3332 14.6023 18.3332 9.99996C18.3332 5.39759 14.6022 1.66663 9.99984 1.66663C5.39746 1.66663 1.6665 5.39759 1.6665 9.99996C1.6665 14.6023 5.39746 18.3333 9.99984 18.3333Z" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.6665 12.5H13.3332" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 7.5H7.50833" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.5 7.5H12.5083" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="a"><rect width="20" height="20" fill="#fff"/></clipPath></defs></svg>
                </span> 
                It's okay
            </button>

            <button type="button" class="wpfnl-review-btn" id="wpfnl-review-no" data-feedback-type="negative">
                <span class="emoji">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><g clip-path="url(#a)"><path d="M9.99984 18.3333C14.6022 18.3333 18.3332 14.6023 18.3332 9.99996C18.3332 5.39759 14.6022 1.66663 9.99984 1.66663C5.39746 1.66663 1.6665 5.39759 1.6665 9.99996C1.6665 14.6023 5.39746 18.3333 9.99984 18.3333Z" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.3332 13.3333C13.3332 13.3333 12.0832 11.6666 9.99984 11.6666C7.9165 11.6666 6.6665 13.3333 6.6665 13.3333" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 7.5H7.50833" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.5 7.5H12.5083" stroke="#4a5565" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="a"><rect width="20" height="20" fill="#fff"/></clipPath></defs></svg>
                </span> 
                Not really
            </button>
        </div>

        <div class="wpfnl-feedback-form" id="wpfnl-feedback-form">
            <label class="wpfnl-feedback-label">How can we improve? <span>(Optional)</span></label>
            <textarea class="wpfnl-feedback-textarea" id="wpfnl-feedback-text" placeholder="Share your thoughts"></textarea>
            <span class="wpfnl-char-counter" id="wpfnl-char-counter"><span id="wpfnl-char-count">0</span> / <?php echo esc_html(self::MIN_FEEDBACK_LENGTH); ?> characters minimum</span>

            <div class="wpfnl-review-footer">
                <button class="wpfnl-cancel-feedback" id="wpfnl-feedback-cancel">Cancel</button>
                <button class="wpfnl-submit-feedback" id="wpfnl-feedback-submit">Submit</button>
            </div>

            <div class="wpfnl-privacy-policy">
                By submitting, you agree to our <a href="https://getwpfunnels.com/privacy-policy/" target="_blank" title="Privacy Policy">Privacy Policy.</a>
            </div>
        </div>
    </div>
</div>

<?php
    }

    /**
     * Enqueue the standalone assets required to power the prompt UI.
     *
     * @param string $hook_suffix Current admin page hook (unused, but provided for completeness).
     * @return void
     */
    public function enqueue_assets($hook_suffix = '')
    {
        if (!$this->should_show()) {
            return;
        }

        wp_enqueue_style(
            'wpfnl-review-prompt',
            WPFNL_DIR_URL . 'admin/assets/css/review-prompt.css',
            [],
            WPFNL_VERSION
        );

        wp_enqueue_script(
            'wpfnl-review-prompt',
            WPFNL_DIR_URL . 'admin/assets/js/review-prompt.js',
            ['jquery'],
            WPFNL_VERSION,
            true
        );

        wp_localize_script(
            'wpfnl-review-prompt',
            'wpfnlReviewPromptData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpfnl_review_nonce'),
                'minChars' => self::MIN_FEEDBACK_LENGTH,
                'errorMessage' => sprintf(
                    'Please share at least %d characters so we can understand what needs improvement and make things better for you and other users.',
                    self::MIN_FEEDBACK_LENGTH
                ),
            ]
        );
    }

    /**
     * Persist the feedback prompt status or forward submitted feedback via AJAX.
     *
     * @return void
     */
    public function handle_ajax()
    {
        check_ajax_referer('wpfnl_review_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $type = isset($_POST['wpfnl_action_type']) ? sanitize_text_field(wp_unslash($_POST['wpfnl_action_type'])) : '';

        if ('snooze' === $type) {
            update_option(self::SNOOZE_OPTION, current_time('timestamp'));
        }
        elseif ('completed' === $type) {
            update_option(self::STATUS_OPTION, 'completed');
        }
        elseif ('feedback' === $type) {
            update_option(self::STATUS_OPTION, 'completed');
            
            $feedback = isset($_POST['feedback']) ? sanitize_textarea_field(wp_unslash($_POST['feedback'])) : '';
            $feedback_type = isset($_POST['feedback_type']) ? sanitize_text_field(wp_unslash($_POST['feedback_type'])) : '';
            if (!empty($feedback)) {
                $current_user = wp_get_current_user();
                $user_email = ($current_user instanceof \WP_User) ? $current_user->user_email : '';
                $user_name = ($current_user instanceof \WP_User) ? $current_user->display_name : '';
                $site_url = site_url();
                $this->send_feedback_to_lark([
                    'feedback' => $feedback,
                    'site_url' => $site_url,
                    'user_email' => $user_email,
                    'user_name' => $user_name,
                    'feedback_type' => $feedback_type,
                ]);
            }
        }

        wp_send_json_success();
    }

    /**
     * Determine if the current admin page belongs to the WPFunnels experience.
     *
     * @return bool
     */
    private function is_wpfnl_admin_screen()
    {
        $allowed_pages = [
            WPFNL_MAIN_PAGE_SLUG,
            WPFNL_FUNNEL_PAGE_SLUG,
            WPFNL_TEMPLATE_PAGE_SLUG,
            WPFNL_SETTINGS_SLUG,
            WPFNL_GLOBAL_SETTINGS_SLUG,
            WPFNL_ADDONS_SLUG,
            WPFNL_EDIT_FUNNEL_SLUG,
            WPFNL_TRASH_FUNNEL_SLUG,
            WPFNL_CREATE_FUNNEL_SLUG,
            'email-builder',
            'wpfunnels_integrations',
        ];

        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if ($page && in_array($page, $allowed_pages, true)) {
            return true;
        }

        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        $allowed_screen_ids = ['toplevel_page_' . WPFNL_MAIN_PAGE_SLUG];
        foreach ($allowed_pages as $allowed_page) {
            $allowed_screen_ids[] = 'wpfunnels_page_' . $allowed_page;
        }

        if (in_array($screen->id, $allowed_screen_ids, true)) {
            return true;
        }

        if (isset($screen->post_type) && in_array($screen->post_type, [WPFNL_FUNNELS_POST_TYPE, WPFNL_STEPS_POST_TYPE], true)) {
            return true;
        }

        return false;
    }

    /**
     * Dispatch the collected feedback payload to the configured Lark webhook.
     *
     * @param array $data Feedback context (message, metadata, and user info).
     * @return void
     */
    private function send_feedback_to_lark($data)
    {
        $payload = [
            'productSlug' => self::PRODUCT_SLUG,
            'feedback' => $data['feedback'],
            'feedbackType' => isset($data['feedback_type']) ? $data['feedback_type'] : '',
            'siteUrl' => $data['site_url'],
            'userEmail' => isset($data['user_email']) ? $data['user_email'] : '',
            'userName' => isset($data['user_name']) ? $data['user_name'] : '',
            'submittedAt' => current_time('mysql'),
        ];

        $response = wp_remote_post(
            self::LARK_WEBHOOK,
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($payload),
                'timeout' => 8,
            ]
        );

        if (is_wp_error($response)) {
            error_log('WPFunnels feedback Lark webhook failed: ' . $response->get_error_message());
        }
    }
}