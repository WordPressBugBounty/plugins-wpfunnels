<?php

namespace WPFunnels\Tracking;

use Linno\Telemetry\Client;

class Telemetry {

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

    private const FIRST_STRIKE_HOOK = 'wpfunnels_telemetry_first_funnel_published';

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
     * Register telemetry hooks and triggers.
     */
    private function __construct() {
        if ( ! class_exists( '\\Linno\\Telemetry\\Client' ) ) {
            return;
        }

        Client::set_text_domain( 'wpfnl' );

        $this->client = new Client(
            'd1bd55fc-4258-48fa-a712-ab6a0f25f313',
            'sec_294ef7dfd3bf5b3bcdcf',
            'WPFunnels',
            WPFNL_FILE
        );

        $this->register_triggers();
        $this->client->init();

        add_action( 'transition_post_status', array( $this, 'maybe_track_first_strike' ), 10, 3 );
    }

    /**
     * Define telemetry triggers for setup, first strike, and KUI.
     *
     * @return void
     */
    private function register_triggers(): void {
        if ( ! $this->client ) {
            return;
        }

        $this->client->define_triggers(
            array(
                'setup'        => array(
                    'hook'     => 'wpfunnels_setup_wizard_complete',
                    'callback' => array( $this, 'setup_payload' ),
                ),
                'first_strike' => array(
                    'hook'     => self::FIRST_STRIKE_HOOK,
                    'callback' => array( $this, 'first_strike_payload' ),
                ),
                'kui' => array(
                    'order_received' => array(
                        'hook'      => 'wpfunnels/funnel_order_placed',
                        'threshold' => array(
                            'count'  => 2,
                            'period' => 'week',
                        ),
                        'callback'  => array( $this, 'kui_payload' ),
                    ),
                ),
            )
        );
    }

    /**
     * Track first strike on first funnel publish (setup wizard or manual).
     *
     * If setup has not yet been sent, this method emits setup first, then first_strike.
     * Linno SDK guarantees first_strike uses a delayed __timestamp (+1s) internally.
     *
     * @param string   $new_status New post status.
     * @param string   $old_status Old post status.
     * @param \WP_Post $post Post object.
     *
     * @return void
     */
    public function maybe_track_first_strike( string $new_status, string $old_status, $post ): void {
        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        if ( WPFNL_FUNNELS_POST_TYPE !== $post->post_type ) {
            return;
        }

        if ( 'publish' !== $new_status || 'publish' === $old_status ) {
            return;
        }

        if ( ! $this->client || $this->client->has_sent_event( 'first_strike' ) ) {
            return;
        }

        $counts = wp_count_posts( WPFNL_FUNNELS_POST_TYPE );
        $published_funnels = isset( $counts->publish ) ? (int) $counts->publish : 0;

        if ( 1 !== $published_funnels ) {
            return;
        }

		do_action(
			self::FIRST_STRIKE_HOOK,
			absint( $post->ID ),
			$this->current_timestamp()
		);
		if ( is_object( $this->client ) && method_exists( $this->client, 'has_sent_event' ) && $this->client->has_sent_event( 'first_strike' ) ) {
			update_option( 'wpfunnels_first_strike_completed', true );
		}
    }

    /**
     * Setup payload with event timestamp.
     *
     * @param int    $funnel_id Funnel id.
     * @param string $action Setup action.
     * @param string $timestamp ISO-8601 timestamp.
     *
     * @return array<string,mixed>
     */
    public function setup_payload( int $funnel_id = 0, string $action = '', string $timestamp = '' ): array {
        return array(
            'action'      => sanitize_text_field( $action ),
            '__timestamp' => $timestamp ?: $this->current_timestamp(),
        );
    }

    /**
     * First strike payload with event timestamp.
     *
     * @param int    $funnel_id Funnel id.
     * @param string $timestamp ISO-8601 timestamp.
     *
     * @return array<string,mixed>
     */
    public function first_strike_payload( int $funnel_id = 0, string $timestamp = '' ): array {
        return array(
            'funnel_id'    => absint( $funnel_id ),
            'funnel_state' => 'publish',
            '__timestamp'  => $timestamp ?: $this->current_timestamp(),
        );
    }

    /**
     * KUI payload callback.
     *
     * @param int|string $order_id Order id.
     * @param int|string $funnel_id Funnel id.
     * @param int|string $checkout_id Checkout id.
     *
     * @return array<string,mixed>
     */
    public function kui_payload( $order_id, $funnel_id, $checkout_id ): array {
        return array(
            'order_id'     => absint( $order_id ),
            'funnel_id'    => absint( $funnel_id ),
            'checkout_id'  => sanitize_text_field( (string) $checkout_id ),
            '__timestamp'  => $this->current_timestamp(),
        );
    }

    /**
     * Return UTC timestamp for analytics ingestion.
     *
     * @return string
     */
    private function current_timestamp(): string {
        return gmdate( 'c' );
    }
}
