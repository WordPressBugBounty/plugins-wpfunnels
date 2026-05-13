<?php

namespace WPFunnels\Tracking;

use LinnoSDK\Telemetry\Client;

class Telemetry {

    private const POSTHOG_API_KEY = 'phc_lpaTTy4DiOGImfWtE6NK8puO5dZ9y3lum35BxUledi4';

    /** @var Telemetry|null */
    private static $instance;

    /** @var Client|null */
    private $client;

    /**
     * Initialize or return the singleton.
     *
     * @return Telemetry|null
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return the already-initialized singleton without creating one.
     *
     * @return Telemetry|null
     */
    public static function instance(): ?self {
        return self::$instance;
    }

    private function __construct() {
        if ( ! class_exists( '\\LinnoSDK\\Telemetry\\Client' ) ) {
            return;
        }

        try {
            $this->client = new Client( [
                'pluginFile'    => WPFNL_FILE,
                'slug'          => 'wpfunnels',
                'pluginName'    => 'WPFunnels',
                'version'       => WPFNL_VERSION,
                'driver'        => 'posthog',
                'driver_config' => [
                    'api_key' => self::POSTHOG_API_KEY,
                    'host'    => 'https://eu.i.posthog.com',
                ],
                'review_prompt' => [
                    'webhook'              => 'https://getwpfunnels.com/wp-json/mrm/v1/form/44/webhook/receive?token=b553ed8f2d6f9ff1612b4fe79c19ff9ee8a30c170eb547f1aba9376eb7ac8039',
                    'min_feedback_length'  => 50,
                    // Progressive snooze: first dismiss → 30 days, second → 90, third+ → 180.
                    'snooze_schedule'      => [ 30, 90, 180 ],
                    'nps_question'         => 'How likely are you to recommend WPFunnels to your friends or colleagues?',
                    'low_score_threshold'  => 7,
                    'review_url'           => 'https://wordpress.org/support/plugin/wpfunnels/reviews/#new-post',
                    'support_url'          => 'https://getwpfunnels.com/support/',
                    'privacy_url'          => 'https://getwpfunnels.com/privacy-policy/',
                    'installed_option_key' => 'wpfunnels_installed_time',
                    'position'             => 'bottom-right',
                    'allowed_screens'      => [
                        WPFNL_MAIN_PAGE_SLUG,
                        WPFNL_FUNNEL_PAGE_SLUG,
                        WPFNL_TEMPLATE_PAGE_SLUG,
                        WPFNL_SETTINGS_SLUG,
                        WPFNL_GLOBAL_SETTINGS_SLUG,
                        WPFNL_EDIT_FUNNEL_SLUG,
                        WPFNL_INTEGRATIONS_MAIN_PAGE_SLUG,
                    ],
                ],
            ] );
        } catch ( \Exception $e ) {
            return;
        }

        $this->register_triggers();
    }

    /**
     * Wire up all WordPress hooks for event tracking.
     */
    private function register_triggers(): void {
        if ( ! $this->client ) {
            return;
        }

        try {
            $this->client->define_triggers( [
                'onboarding'   => [
                    'hook'     => 'wpfunnels_setup_wizard_complete',
                    'callback' => [ $this, 'setup_payload' ],
                ],
                'feature_used' => [
                    'template_library'  => [
                        'hook'     => 'wpfunnels_template_imported',
                        'callback' => [ $this, 'template_imported_payload' ],
                    ],
                    'order_bumps'       => [
                        'hook'     => 'wpfunnels_order_bump_added',
                        'callback' => [ $this, 'feature_usage_payload' ],
                    ],
                    'upsells_downsells' => [
                        'hook'     => 'wpfunnels_upsell_configured',
                        'callback' => [ $this, 'feature_usage_payload' ],
                    ],
                    'vertical_canvas_mode' => [
                        'hook'     => 'wpfunnels_vertical_mode_used',
                        'callback' => [ $this, 'feature_usage_payload' ],
                    ],
                ],
            ] );
        } catch ( \Exception $e ) {
            // Failure-safe: trigger registration errors must not surface to callers.
        }

        // Funnel lifecycle → PostHog.
        add_action( 'wpfunnels_after_funnel_created', [ $this, 'handle_funnel_created' ], 10, 2 );
        add_action( 'transition_post_status',          [ $this, 'handle_funnel_published' ], 10, 3 );

        // Onboarding step events dispatched from the REST track-step endpoint.
        // add_action( 'wpfunnels_onboarding_step_tracked', [ $this, 'handle_onboarding_step' ], 10, 1 );

        // Unified onboarding outcome event (single-event model).
        add_action( 'wpfunnels_onboarding_progress', [ $this, 'handle_onboarding_progress' ], 10, 1 );

        // NPS contextual triggers.
        add_action( 'wpfunnels_setup_wizard_complete',  [ $this, 'maybe_trigger_onboarding_nps' ],   20, 3 );
        add_action( 'wpfunnels_after_funnel_created',   [ $this, 'maybe_trigger_first_funnel_nps' ], 20, 2 );
        add_action( 'wpfunnels_template_imported',      [ $this, 'maybe_trigger_first_funnel_nps' ], 20, 1 );

        // Per-funnel Aha milestone — wired directly because SDK KUI thresholding
        // is site-scoped and cannot enforce the funnel-level single-fire guarantee.
        add_action( 'wpfunnels/funnel_order_placed', [ $this, 'handle_aha_milestone' ], 10, 3 );

        // Replace generic SDK deactivation reasons with WPFunnels-specific ones.
        add_filter( 'wpfunnels_telemetry_deactivation_reasons', [ $this, 'filter_deactivation_reasons' ] );

        // Swap the SDK's shared deactivation modal for WPFunnels' own isolated implementation.
        // The SDK's modal uses generic CSS class names (wd-dr-*, wd-de-*) that conflict with
        // mail-mint and wpvr when all plugins are active. WPFunnels' modal uses wpfnl-dr-/wpfnl-de-
        // prefixed classes and one-click behavior, keeping it fully independent.
        //
        // We cannot call $this->client->get_deactivation() because another plugin (mail-mint,
        // wpvr) may load its own Client.php first — only one class definition wins in PHP.
        // Instead we scan $wp_filter at priority 0 and identify WPFunnels' Deactivation
        // instance by the plugin_action_links hook it registers for WPFNL_FILE.
        add_action( 'admin_footer', [ $this, 'remove_sdk_deactivation_modal' ], 0 );
        add_action( 'admin_footer', [ $this, 'render_deactivation_modal' ] );
    }

    // -------------------------------------------------------------------------
    // Public API — called from REST controllers
    // -------------------------------------------------------------------------

    /**
     * Persist the user's consent decision and sync the telemetry client.
     *
     * Must be called as early as possible (Welcome step proceed) so subsequent
     * step-tracking calls pass the opt-in gate inside the SDK.
     *
     * @param bool $consented True when the user checked the data-sharing box.
     */
    public function save_consent( bool $consented ): void {
        if ( ! $this->client ) {
            return;
        }
        $this->client->set_optin_state( $consented ? 'yes' : 'no' );
    }

    /**
     * Dispatch a step-view or step-completion event from the REST layer.
     *
     * @param array $data Keys: event_type (viewed|completed), step_name, step_index, goal, time_on_step.
     */
    public function track_onboarding_step( array $data ): void {
        do_action( 'wpfunnels_onboarding_step_tracked', $data );
    }

    /**
     * Dispatch the unified onboarding outcome event from the REST layer.
     *
     * @param array $data Keys: outcome, last_step_name, last_step_index, total_steps, goal, funnel_id.
     */
    public function track_onboarding_progress( array $data ): void {
        do_action( 'wpfunnels_onboarding_progress', $data );
    }

    // -------------------------------------------------------------------------
    // Payload callbacks
    // -------------------------------------------------------------------------

    /**
     * Onboarding completion payload — sent once when the wizard finishes.
     *
     * @param int    $funnel_id Funnel ID created during setup.
     * @param string $action    Button clicked at the Complete step (e.g. goToDashboard).
     * @param string $goal      Goal the user chose (order-value | improve-checkout | sales).
     *
     * @return array<string,mixed>
     */
    public function setup_payload( int $_funnel_id = 0, string $action = '', string $goal = '' ): array {
        $start_time = (int) get_option( 'wpfunnels_onboarding_start_time', 0 );
        $elapsed    = $start_time ? ( time() - $start_time ) : 0;

        return [
            'action'                    => sanitize_text_field( $action ),
            'goal'                      => sanitize_text_field( $goal ),
            'time_to_complete_seconds'  => $elapsed,
            '__timestamp'               => $this->current_timestamp(),
        ];
    }

    /**
     * Feature usage payload for order bumps and upsells.
     *
     * @param int|string $funnel_id Funnel ID.
     * @param int|string $step_id   Step ID (0 when not applicable).
     * @param mixed      ...$_extra Additional hook args silently ignored.
     *
     * @return array<string,mixed>
     */
    public function feature_usage_payload( $funnel_id = 0, $step_id = 0, ...$_extra ): array {
        return [
            'funnel_id'   => absint( $funnel_id ),
            '__timestamp' => $this->current_timestamp(),
        ];
    }

    /**
     * Feature usage payload for template imports — includes template context.
     *
     * @param int|string $funnel_id   Funnel ID.
     * @param int|string $template_id Template ID.
     * @param mixed      ...$_extra   Additional hook args silently ignored.
     *
     * @return array<string,mixed>
     */
    public function template_imported_payload( $funnel_id = 0, $template_id = 0, ...$_extra ): array {
        return [
            'funnel_id'   => absint( $funnel_id ),
            'template_id' => absint( $template_id ),
            '__timestamp' => $this->current_timestamp(),
        ];
    }

    // -------------------------------------------------------------------------
    // Funnel lifecycle handlers
    // -------------------------------------------------------------------------

    /**
     * Track funnel creation in PostHog.
     *
     * Hooked on wpfunnels_after_funnel_created (same hook used by EventTracker
     * for the legacy first_strike event).
     *
     * @param int|string $funnel_id   Funnel ID.
     * @param string     $funnel_type Funnel type identifier.
     */
    public function handle_funnel_created( $funnel_id, $funnel_type ): void {
        if ( ! $this->client ) {
            return;
        }
        try {
            $this->client->track( 'activation/funnel_created', [
                'funnel_id'   => absint( $funnel_id ),
                'funnel_type' => sanitize_text_field( (string) $funnel_type ),
                '__timestamp' => $this->current_timestamp(),
            ] );
        } catch ( \Exception $e ) {
            // Failure-safe.
        }
    }

    /**
     * Track funnel publish transition and fire the wpfunnels_funnel_published hook.
     *
     * Listens on transition_post_status for all post types — returns early for
     * anything that is not a wpfunnels post moving into publish status.
     *
     * @param string   $new_status Post status after transition.
     * @param string   $old_status Post status before transition.
     * @param \WP_Post $post       The post being transitioned.
     */
    public function handle_funnel_published( string $new_status, string $old_status, \WP_Post $post ): void {
        if ( 'publish' !== $new_status || 'publish' === $old_status ) {
            return;
        }
        if ( 'wpfunnels' !== $post->post_type ) {
            return;
        }
        if ( ! $this->client ) {
            return;
        }

        $created_ts   = strtotime( $post->post_date_gmt );
        $days_created = $created_ts ? round( ( time() - $created_ts ) / DAY_IN_SECONDS, 1 ) : 0;

        try {
            $this->client->track( 'activation/funnel_published', [
                'funnel_id'          => $post->ID,
                'funnel_type'        => get_post_meta( $post->ID, '_wpfnl_funnel_type', true ) ?: 'unknown',
                'days_since_created' => $days_created,
                '__timestamp'        => $this->current_timestamp(),
            ] );
        } catch ( \Exception $e ) {
            // Failure-safe.
        }
    }

    // -------------------------------------------------------------------------
    // Onboarding step handlers
    // -------------------------------------------------------------------------

    /**
     * Send a step_viewed or step_completed PostHog event.
     *
     * Fires when wpfunnels_onboarding_step_tracked is dispatched from the REST
     * track-step endpoint.
     *
     * @param array $data {
     *     @type string $event_type   'viewed' or 'completed'
     *     @type string $step_name    Step slug (e.g. welcome, choose_goal)
     *     @type int    $step_index   1-based step number
     *     @type string $goal         Selected goal or empty string
     *     @type int    $time_on_step Seconds spent on the step before proceeding
     * }
     */
    public function handle_onboarding_step( array $data ): void {
        if ( ! $this->client ) {
            return;
        }

        $event_type = sanitize_key( $data['event_type'] ?? 'viewed' );
        $event_map  = [
            'viewed'    => 'onboarding/step_viewed',
            'completed' => 'onboarding/step_completed',
        ];

        $event = $event_map[ $event_type ] ?? null;
        if ( ! $event ) {
            return;
        }

        try {
            $this->client->track( $event, [
                'step_name'    => sanitize_key( $data['step_name'] ?? '' ),
                'step_index'   => absint( $data['step_index'] ?? 0 ),
                'goal'         => sanitize_text_field( $data['goal'] ?? '' ),
                'time_on_step' => absint( $data['time_on_step'] ?? 0 ),
                '__timestamp'  => $this->current_timestamp(),
            ] );
        } catch ( \Exception $e ) {
            // Failure-safe.
        }

        // Stamp the wizard start time on first step view so setup_payload can
        // compute time_to_complete_seconds.
        if ( 1 === absint( $data['step_index'] ?? 0 ) && 'viewed' === $event_type ) {
            if ( ! get_option( 'wpfunnels_onboarding_start_time' ) ) {
                update_option( 'wpfunnels_onboarding_start_time', time(), false );
            }
        }
    }

    /**
     * Send the unified onboarding_progress PostHog event.
     *
     * Single-event model that captures the full onboarding outcome in one event.
     * Fires for all three terminal outcomes: completed, skipped, and exited.
     * Consent is enforced by the SDK's opt-in gate on client->track().
     *
     * Fires once per wizard session — guarded by a wp_options flag to prevent
     * duplicate events from redirects or re-renders.
     *
     * @param array $data {
     *     @type string $outcome          Terminal outcome: 'completed', 'skipped', or 'exited'
     *     @type string $last_step_name   Slug of the last step reached (e.g. 'choose_goal').
     *                                    Aliases: 'step_name' (from the abandoned path).
     *     @type int    $last_step_index  1-based index of the last step.
     *                                    Aliases: 'step_index' (from the abandoned path).
     *     @type int    $total_steps      Total steps in the wizard for this goal path
     *     @type string $goal             Selected goal slug, or '' if not yet chosen
     *     @type int    $funnel_id        ID of funnel created (0 if none created yet)
     * }
     */
    public function handle_onboarding_progress( array $data ): void {
        if ( ! $this->client ) {
            return;
        }

        $outcome = sanitize_key( $data['outcome'] ?? '' );
        if ( ! in_array( $outcome, [ 'completed', 'skipped', 'exited' ], true ) ) {
            return;
        }

        // Prevent duplicate fires within the same wizard session.
        if ( get_option( 'wpfunnels_onboarding_progress_tracked' ) ) {
            return;
        }

        // Support both field name conventions: abandoned path uses step_name/step_index,
        // completion path uses last_step_name/last_step_index.
        $last_step_name  = sanitize_key( $data['last_step_name'] ?? $data['step_name'] ?? '' );
        $last_step_index = absint( $data['last_step_index'] ?? $data['step_index'] ?? 0 );

        $start_time = (int) get_option( 'wpfunnels_onboarding_start_time', 0 );

        try {
            $this->client->track( 'onboarding_progress', [
                'outcome'              => $outcome,
                'last_step_name'       => $last_step_name,
                'last_step_index'      => $last_step_index,
                'total_steps'          => absint( $data['total_steps'] ?? 0 ),
                'goal'                 => sanitize_text_field( $data['goal'] ?? '' ),
                'time_to_exit_seconds' => $start_time ? ( time() - $start_time ) : 0,
                'funnel_id'            => absint( $data['funnel_id'] ?? 0 ),
                'plugin_version'       => WPFNL_VERSION,
                '__timestamp'          => $this->current_timestamp(),
            ] );

            // Mark as fired so re-renders / redirects cannot double-fire.
            update_option( 'wpfunnels_onboarding_progress_tracked', '1', false );
        } catch ( \Exception $e ) {
            // Failure-safe.
        }
    }

    /**
     * Queue an NPS prompt after the setup wizard completes.
     *
     * Overrides the default NPS copy with setup-specific messaging so the
     * question is contextually relevant to the just-finished onboarding.
     * Runs at priority 20 on wpfunnels_setup_wizard_complete (after telemetry
     * tracking at priority 10).
     *
     * @param int    $funnel_id Funnel ID.
     * @param string $action    Completion action.
     * @param string $goal      Selected goal.
     */
    public function maybe_trigger_onboarding_nps( int $funnel_id = 0, string $action = '', string $goal = '' ): void {
        if ( get_option( 'wpfunnels_wizard_nps_triggered' ) ) {
            return;
        }
        $funnel_tyep = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true ) ?: 'woo';
        $nps_question = 'How easy was it to set up your first funnel with WPFunnels?';
        $feedback_msg = "We're sorry setup was difficult. What got in your way?";
        if ( 'store_checkout' === $funnel_tyep ) {
            $nps_question = 'How likely are you to recommend WPFunnels to your friends or colleagues based on your checkout setup experience?';
        }
            
        $review_prompt = $this->get_review_prompt_instance();
        if ( ! $review_prompt ) {
            return;
        }
        update_option( 'wpfunnels_wizard_nps_triggered', '1' );
        $review_prompt->trigger_prompt( 'onboarding_complete', [
            'modal_title'  => 'How was your setup experience?',
            'nps_question' => $nps_question,
            'feedback_msg' => $feedback_msg,
        ] );
    }

    /**
     * Queue an NPS prompt after the user creates their first funnel (wizard or non-wizard).
     *
     * Fires once — guarded by a wp_options flag. For wizard users the wizard-complete
     * NPS (maybe_trigger_onboarding_nps) will overwrite this trigger context on the
     * same page-load cycle, so wizard users see the more contextual wizard copy.
     * Non-wizard users see the first-funnel copy.
     *
     * @param int|string $funnel_id   Newly created funnel ID.
     * @param string     $funnel_type Funnel type identifier.
     */
    public function maybe_trigger_first_funnel_nps( $funnel_id, $funnel_type = '' ): void {
        if ( get_option( 'wpfunnels_first_funnel_nps_triggered' ) ) {
            return;
        }
        $review_prompt = $this->get_review_prompt_instance();
        if ( ! $review_prompt ) {
            return;
        }
        $funnel_tyep = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true ) ?: 'woo';
        $nps_question = 'How easy was it to create your first funnel with WPFunnels?';
        $feedback_msg = "We're sorry to hear that. What got in your way?";
        if ( 'store_checkout' === $funnel_tyep ) {
            $nps_question = 'How likely are you to recommend WPFunnels to your friends or colleagues based on your checkout setup experience?';
        }

        update_option( 'wpfunnels_first_funnel_nps_triggered', '1' );
        $review_prompt->trigger_prompt( 'first_funnel_created', [
            'modal_title'  => 'You created your first funnel!',
            'nps_question' => $nps_question,
            'feedback_msg' => $feedback_msg,
        ] );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the ReviewPrompt instance safely, guarding against stale PHP opcache
     * that may not yet have the get_review_prompt() method available.
     *
     * @return \LinnoSDK\Telemetry\ReviewPrompt|null
     */
    private function get_review_prompt_instance(): ?\LinnoSDK\Telemetry\ReviewPrompt {
        if ( ! $this->client ) {
            return null;
        }
        try {
            return $this->client->get_review_prompt();
        } catch ( \Throwable $e) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Deactivation feedback
    // -------------------------------------------------------------------------

    /**
     * Find and remove the SDK's admin_footer deactivation modal for WPFunnels.
     *
     * Runs at priority 0 so it fires before the SDK's default priority-10 callback.
     * We cannot use get_deactivation() on Client because another plugin may have
     * loaded its own Client.php first (PHP only keeps the first class definition).
     * Instead we identify WPFunnels' Deactivation instance by the plugin_action_links
     * hook it uniquely registers for WPFNL_FILE, then remove its admin_footer callback.
     */
    public function remove_sdk_deactivation_modal() {
        global $wp_filter;

        if ( ! isset( $wp_filter['admin_footer'] ) ) {
            return;
        }

        $links_hook = 'plugin_action_links_' . plugin_basename( WPFNL_FILE );

        foreach ( $wp_filter['admin_footer']->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $cb ) {
                $fn = $cb['function'];
                if ( ! is_array( $fn ) || ! ( $fn[0] instanceof \LinnoSDK\Telemetry\Deactivation ) ) {
                    continue;
                }
                if ( has_filter( $links_hook, [ $fn[0], 'filter_plugin_action_links' ] ) ) {
                    remove_action( 'admin_footer', [ $fn[0], 'add_deactivation_feedback_modal' ], $priority );
                    return;
                }
            }
        }
    }

    /**
     * Render WPFunnels' own deactivation modal on the plugins.php page.
     *
     * This replaces the SDK's generic modal (unhooked via remove_sdk_deactivation_modal()) so
     * WPFunnels uses isolated wpfnl-dr-/wpfnl-de- CSS class names and one-click
     * UX without conflicting with mail-mint or wpvr modals on the same page.
     * The SDK's plugin_action_links filter and AJAX handler remain active.
     */
    public function render_deactivation_modal() {
        global $pagenow;
        if ( 'plugins.php' !== $pagenow ) {
            return;
        }

        $nonce   = wp_create_nonce( 'wpfunnels-deactivation-nonce' );
        $reasons = apply_filters( 'wpfunnels_telemetry_deactivation_reasons', [] );

        if ( empty( $reasons ) ) {
            return;
        }
        ?>
        <style type="text/css">
            .wpfnl-dr-modal {
                position: fixed;
                z-index: 99999;
                top: 0; right: 0; bottom: 0; left: 0;
                background: rgba(0,0,0,0.5);
                display: none;
                box-sizing: border-box;
                overflow: scroll;
            }
            .wpfnl-dr-modal * { box-sizing: border-box; }
            .wpfnl-dr-modal.modal-active { display: block; }
            .wpfnl-dr-modal-wrap {
                max-width: 780px;
                width: 100%;
                position: relative;
                margin: 8% auto;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            }
            .wpfnl-dr-modal-header {
                border-bottom: 1px solid #E8E8E8;
                padding: 24px 24px 16px 24px;
            }
            .wpfnl-dr-modal-header h3 {
                line-height: 1.5;
                margin: 0 0 4px 0;
                color: #1a1a2e;
                font-size: 16px;
                font-weight: 600;
            }
            .wpfnl-dr-modal-subheading { margin: 0; color: #718096; font-size: 13px; }
            .wpfnl-dr-modal-body { padding: 20px 24px 8px 24px; }
            .wpfnl-dr-modal-footer {
                border-top: 1px solid #E8E8E8;
                padding: 16px 24px;
                display: flex;
                align-items: center;
                justify-content: flex-end;
            }
            ul.wpfnl-de-reasons {
                display: flex;
                flex-wrap: wrap;
                margin: 0 -6px 12px -6px;
                padding: 0;
                list-style: none;
            }
            ul.wpfnl-de-reasons li { padding: 0 6px; margin: 0 0 12px 0; width: 25%; }
            .wpfnl-de-reason-btn {
                width: 100%;
                border: 1.5px solid #E8E8E8;
                border-radius: 6px;
                background: #fff;
                cursor: pointer;
                text-align: center;
                padding: 14px 8px 12px 8px;
                transition: border-color 0.15s, background 0.15s, transform 0.1s;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                height: 100%;
            }
            .wpfnl-de-reason-btn:hover {
                border-color: #7B68EE;
                background: #f5f3ff;
                transform: translateY(-1px);
            }
            .wpfnl-de-reason-btn:active { transform: translateY(0); }
            .wpfnl-de-reason-btn:disabled { cursor: default; opacity: 0.6; }
            .wpfnl-de-reason-text { color: #4A5568; font-size: 12px; line-height: 1.4; }
            .wpfnl-de-reason-icon { display: flex; align-items: center; justify-content: center; }
            ul.wpfnl-de-reasons li.wpfnl-de-reason-selected .wpfnl-de-reason-btn {
                background: #7B68EE;
                border-color: #7B68EE;
            }
            ul.wpfnl-de-reasons li.wpfnl-de-reason-selected .wpfnl-de-reason-icon svg,
            ul.wpfnl-de-reasons li.wpfnl-de-reason-selected .wpfnl-de-reason-icon svg g { fill: #fff; }
            ul.wpfnl-de-reasons li.wpfnl-de-reason-selected .wpfnl-de-reason-text { color: #fff; }
            .wpfnl-dr-btn-cancel,
            .wpfnl-dr-btn-cancel:hover {
                border: 1px solid #EBEBEB;
                border-radius: 4px;
                font-size: 13px;
                line-height: 1.5;
                color: #718096;
                padding: 6px 14px;
                cursor: pointer;
                background-color: transparent;
                text-decoration: none;
            }
        </style>

        <div class="wpfnl-dr-modal" id="wpfunnels-dr-modal">
            <div class="wpfnl-dr-modal-wrap">
                <div class="wpfnl-dr-modal-header">
                    <h3><?php esc_html_e( "Quick question before you go — what's the main reason?", 'wpfnl' ); ?></h3>
                    <p class="wpfnl-dr-modal-subheading"><?php esc_html_e( 'One click is all it takes. Your feedback helps us improve.', 'wpfnl' ); ?></p>
                </div>
                <div class="wpfnl-dr-modal-body">
                    <ul class="wpfnl-de-reasons">
                        <?php foreach ( $reasons as $reason ) : ?>
                            <li data-reason-id="<?php echo esc_attr( $reason['id'] ); ?>">
                                <button type="button" class="wpfnl-de-reason-btn">
                                    <div class="wpfnl-de-reason-icon"><?php echo $reason['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                                    <div class="wpfnl-de-reason-text"><?php echo esc_html( $reason['text'] ); ?></div>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="wpfnl-dr-modal-footer">
                    <button type="button" class="wpfnl-dr-btn-cancel"><?php esc_html_e( 'Cancel', 'wpfnl' ); ?></button>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            (function($) {
                $(function() {
                    var modal          = $('#wpfunnels-dr-modal');
                    var deactivateLink = '';
                    var submitting     = false;

                    $('#the-list').on('click', 'a.wpfunnels-deactivation-link', function(e) {
                        e.preventDefault();
                        deactivateLink = $(this).attr('href');
                        submitting     = false;
                        modal.find('.wpfnl-de-reason-btn').prop('disabled', false);
                        modal.find('li').removeClass('wpfnl-de-reason-selected');
                        modal.addClass('modal-active');
                    });

                    modal.on('click', 'button.wpfnl-dr-btn-cancel', function(e) {
                        e.preventDefault();
                        modal.removeClass('modal-active');
                    });

                    modal.on('click', '.wpfnl-de-reason-btn', function() {
                        if (submitting || !deactivateLink) return;
                        submitting = true;

                        var reasonId = $(this).closest('li').data('reason-id');
                        $(this).closest('li').addClass('wpfnl-de-reason-selected');
                        modal.find('.wpfnl-de-reason-btn').prop('disabled', true);

                        $.ajax({
                            url:  ajaxurl,
                            type: 'POST',
                            data: {
                                nonce:       '<?php echo esc_js( $nonce ); ?>',
                                action:      'wpfunnels_submit_deactivation_reason',
                                reason_id:   reasonId,
                                reason_info: ''
                            },
                            complete: function() {
                                window.location.href = deactivateLink;
                            }
                        });
                    });
                });
            }(jQuery));
        </script>
        <?php
    }

    /**
     * Replace the generic SDK deactivation reasons with WPFunnels-specific ones.
     *
     * Hooked on wpfunnels_telemetry_deactivation_reasons. Called by render_deactivation_modal()
     * via apply_filters to populate WPFunnels' own modal.
     *
     * @param array $reasons Default reasons from the SDK.
     * @return array
     */
    public function filter_deactivation_reasons( array $reasons ): array {
        return [
            
            [
                'id'   => 'plugin-conflict',
                'text' => __( 'Plugin conflict', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="#3B86FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            ],
            [
                'id'   => 'payment-not-working',
                'text' => __( 'Payment not working', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#3B86FF" stroke-width="2"/><path d="M12 7v5l3 3" stroke="#3B86FF" stroke-width="2" stroke-linecap="round"/></svg>',
            ],
            [
                'id'   => 'funnel-builder-hard-to-use',
                'text' => __( 'Funnel builder too complex', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="3" stroke="#3B86FF" stroke-width="2"/><path d="M9 9h6M9 12h4" stroke="#3B86FF" stroke-width="2" stroke-linecap="round"/></svg>',
            ],
            [
                'id'   => 'checkout-not-working',
                'text' => __( 'Checkout not working', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#3B86FF" stroke-width="2"/><path d="M12 7v5l3 3" stroke="#3B86FF" stroke-width="2" stroke-linecap="round"/></svg>',
            ],
            [
                'id'   => 'upsell-downsell-not-working',
                'text' => __( 'Upsell / downsell issues', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 17l4-4 4 4 4-8 4 4" stroke="#3B86FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            ],
            [
                'id'   => 'missing-feature',
                'text' => __( 'Missing a feature I need', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#3B86FF" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#3B86FF" stroke-width="2" stroke-linecap="round"/></svg>',
            ],
            [
                'id'   => 'template-not-good-enough',
                'text' => __( 'Templates are not good enough', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="#3B86FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            ],
            [
                'id'   => 'lacking-payment-gateway',
                'text' => __( 'Lack of payment gateway options', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#3B86FF" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#3B86FF" stroke-width="2" stroke-linecap="round"/></svg>',
            ],
            [
                'id'   => 'found-better-plugin',
                'text' => __( 'Switching to another plugin', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="#3B86FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            ],
            [
                'id'   => 'temporary',
                'text' => __( 'Temporarily deactivating', 'wpfnl' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#3B86FF" stroke-width="2"/><path d="M12 6v6l4 2" stroke="#3B86FF" stroke-width="2" stroke-linecap="round"/></svg>',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Aha milestone (per-funnel first live sale)
    // -------------------------------------------------------------------------

    /**
     * Fire the Aha milestone event exactly once per funnel on the first order.
     *
     * @param int|string $order_id    Order ID.
     * @param int|string $funnel_id   Funnel ID.
     * @param int|string $checkout_id Checkout step ID.
     */
    public function handle_aha_milestone( $order_id, $funnel_id, $checkout_id ): void {
        $funnel_id = absint( $funnel_id );
        if ( ! $funnel_id ) {
            return;
        }
        if ( $this->has_aha_completed( $funnel_id ) ) {
            return;
        }
        try {
            if ( $this->client ) {
                $this->client->track_kui( 'first_live_sale', $this->aha_payload( $order_id, $funnel_id ) );
            }
        } catch ( \Exception $e ) {
            // Failure-safe: telemetry errors must never block order processing.
        }
        $this->mark_aha_completed( $funnel_id, $order_id );
    }

    /**
     * @param int $funnel_id Funnel post ID.
     * @return bool
     */
    public function has_aha_completed( int $funnel_id ): bool {
        return (bool) get_post_meta( $funnel_id, '_wpfnl_first_live_sale_completed', true );
    }

    /**
     * @param int        $funnel_id Funnel post ID.
     * @param int|string $order_id  Order that triggered the milestone.
     */
    public function mark_aha_completed( int $funnel_id, $order_id ): void {
        update_post_meta( $funnel_id, '_wpfnl_first_live_sale_completed', '1' );
        update_post_meta( $funnel_id, '_wpfnl_first_live_sale_at', $this->current_timestamp() );
        update_post_meta( $funnel_id, '_wpfnl_first_live_sale_order', absint( $order_id ) );
    }

    /**
     * @param int|string $order_id  Order ID.
     * @param int        $funnel_id Funnel ID.
     * @return array<string,mixed>
     */
    public function aha_payload( $order_id, int $funnel_id ): array {
        return [
            'funnel_id'   => $funnel_id,
            '__timestamp' => $this->current_timestamp(),
        ];
    }

    /** @return string ISO-8601 UTC timestamp. */
    private function current_timestamp(): string {
        return gmdate( 'c' );
    }
}
