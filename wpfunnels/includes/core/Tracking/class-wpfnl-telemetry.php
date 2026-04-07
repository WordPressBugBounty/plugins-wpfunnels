<?php

namespace WPFunnels\Tracking;

use LinnoSDK\Telemetry\Client;

class Telemetry {

    /**
     * PostHog API key.
     *
     * @var string
     */
    private const POSTHOG_API_KEY = 'phc_lpaTTy4DiOGImfWtE6NK8puO5dZ9y3lum35BxUledi4';

    /**
     * Approved feature identifiers for usage tracking.
     *
     * @var string[]
     */
    private const APPROVED_FEATURES = [
        'template_library',
        'order_bumps',
        'upsells_downsells',
    ];

    /**
     * Singleton instance.
     *
     * @var Telemetry|null
     */
    private static $instance;

    /**
     * Linno telemetry client.
     *
     * @var Client|null
     */
    private $client;

    /**
     * Initialize telemetry singleton.
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
     * Boot the PostHog-backed telemetry client and register triggers.
     *
     * Failure-safe: any SDK exception is caught so telemetry never interrupts
     * plugin activation or other core flows.
     */
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
                    'host'  => 'https://eu.i.posthog.com',
                ],
            ] );
        } catch ( \Exception $e ) {
            return;
        }

        $this->register_triggers();
    }

    /**
     * Register all telemetry triggers: onboarding lifecycle, feature usage, and
     * per-funnel Aha milestone.
     *
     * Activation and deactivation lifecycle events are handled automatically by
     * the Linno SDK through register_activation_hook / register_deactivation_hook.
     * Onboarding completion is single-fire; the SDK guards repeat emissions via
     * has_sent_event( 'onboarding_completed' ).
     *
     * @return void
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
                        'callback' => [ $this, 'feature_usage_payload' ],
                    ],
                    'order_bumps'       => [
                        'hook'     => 'wpfunnels_order_bump_added',
                        'callback' => [ $this, 'feature_usage_payload' ],
                    ],
                    'upsells_downsells' => [
                        'hook'     => 'wpfunnels_upsell_configured',
                        'callback' => [ $this, 'feature_usage_payload' ],
                    ],
                ],
            ] );
        } catch ( \Exception $e ) {
            // Failure-safe: trigger registration errors must not surface to callers.
        }

        // Per-funnel Aha milestone wired directly; SDK KUI thresholding is
        // site-scoped and cannot enforce the funnel-level single-fire guarantee.
        add_action( 'wpfunnels/funnel_order_placed', [ $this, 'handle_aha_milestone' ], 10, 3 );
    }

    // -------------------------------------------------------------------------
    // Payload callbacks
    // -------------------------------------------------------------------------

    /**
     * Onboarding completion payload.
     *
     * @param int    $funnel_id Funnel id passed by the setup wizard hook.
     * @param string $action    Setup action label.
     *
     * @return array<string,mixed>
     */
    public function setup_payload( int $funnel_id = 0, string $action = '' ): array {
        return [
            'action'      => sanitize_text_field( $action ),
            '__timestamp' => $this->current_timestamp(),
        ];
    }

    /**
     * Feature usage payload.
     *
     * Accepts the arguments supplied by whichever product-action hook fired.
     * Only approved feature identifiers are passed (validated by the trigger map
     * itself), and no customer PII is included.
     *
     * @param int|string $funnel_id Funnel id.
     * @param int|string $step_id   Step id (0 when not applicable).
     * @param mixed      ...$_extra Additional hook args silently ignored.
     *
     * @return array<string,mixed>
     */
    public function feature_usage_payload( $funnel_id = 0, $step_id = 0, ...$_extra ): array {
        $payload = [
            '__timestamp' => $this->current_timestamp(),
        ];
        return $payload;
    }

    // -------------------------------------------------------------------------
    // Aha milestone (per-funnel first live sale)
    // -------------------------------------------------------------------------

    /**
     * Handle the funnel order placed hook for the per-funnel Aha milestone.
     *
     * Fires the Aha event exactly once per funnel. Duplicate, retried, or
     * replayed order events for the same funnel are silently skipped.
     *
     * @param int|string $order_id    Order id.
     * @param int|string $funnel_id   Funnel id.
     * @param int|string $checkout_id Checkout step id.
     *
     * @return void
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
     * Check whether the first-live-sale Aha milestone has been recorded for a funnel.
     *
     * @param int $funnel_id Funnel post ID.
     *
     * @return bool
     */
    public function has_aha_completed( int $funnel_id ): bool {
        return (bool) get_post_meta( $funnel_id, '_wpfnl_first_live_sale_completed', true );
    }

    /**
     * Persist the Aha milestone state for a funnel.
     *
     * Stores three meta keys so the milestone is traceable:
     * - _wpfnl_first_live_sale_completed  (boolean flag)
     * - _wpfnl_first_live_sale_at         (UTC ISO-8601 timestamp)
     * - _wpfnl_first_live_sale_order      (order ID)
     *
     * @param int        $funnel_id Funnel post ID.
     * @param int|string $order_id  Order that triggered the milestone.
     *
     * @return void
     */
    public function mark_aha_completed( int $funnel_id, $order_id ): void {
        update_post_meta( $funnel_id, '_wpfnl_first_live_sale_completed', '1' );
        update_post_meta( $funnel_id, '_wpfnl_first_live_sale_at', $this->current_timestamp() );
        update_post_meta( $funnel_id, '_wpfnl_first_live_sale_order', absint( $order_id ) );
    }

    /**
     * Build the Aha milestone payload.
     *
     * Excludes all customer PII. Only funnel context and the order ID are included.
     *
     * @param int|string $order_id  Order id.
     * @param int        $funnel_id Funnel id.
     *
     * @return array<string,mixed>
     */
    public function aha_payload( $order_id, int $funnel_id ): array {
        return [
            '__timestamp' => $this->current_timestamp(),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return current UTC timestamp for analytics ingestion.
     *
     * @return string ISO-8601 UTC timestamp.
     */
    private function current_timestamp(): string {
        return gmdate( 'c' );
    }
}
