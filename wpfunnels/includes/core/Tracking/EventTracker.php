<?php

/**
 * This class handles event tracking functionality using OpenPanel analytics.
 * This class implements a singleton pattern to ensure only one instance
 * of the tracker exists throughout the application lifecycle.
 *
 * @author WPFunnels Team
 * @email support@getwpfunnels.com
 * @create date 2025-05-12 09:30:00
 * @modify date 2024-05-12 11:03:17
 * @package Mint\App\Internal\Tracking
 */

namespace WPFunnels\Tracking;

/**
 * Class EventTracker
 * 
 * This class implements a singleton pattern to ensure only one instance
 * of the tracker exists throughout the application lifecycle.
 * 
 * @package Mint\MRM\Internal\Tracking
 * @since 1.17.10
 */
class EventTracker{

    /**
     * Holds the singleton instance of this class.
     * 
     * @var EventTracker|null $instance The singleton instance of this class.
     * @since 1.17.10
     */
    private static $instance;

    /**
     * Private constructor to prevent instantiation from outside the class.
     *
     * @since 1.17.10
     */
    private function __construct(){

        add_action( 'admin_init', array( $this, 'maybe_track_activation' ) );
        add_action( 'wpfunnels_after_funnel_created', array( $this, 'on_after_funnel_created' ), 10, 2 );
        add_action( 'wpfunnels_after_funnel_imported', array( $this, 'on_after_funnel_imported' ), 10, 3 );
        add_action( 'wpfunnels_plugin_deactivated', array( $this, 'on_plugin_deactivated' ) );
        add_action( 'wpfunnels_after_funnel_creation', array( $this, 'on_after_funnel_creation' ) );
        add_action( 'wpfunnels/funnel_order_placed', array( $this, 'funnel_order_placed' ), 10, 3 );
        add_action( 'wpfunnels_setup_wizard_complete', array( $this, 'on_setup_wizard_complete' ), 10, 2 );
    }

    /**
     * Returns the singleton instance of this class.
     *
     * If the instance does not exist, it creates a new instance.
     * 
     * @access public
     *
     * @return EventTracker The singleton instance of this class.
     *
     * @since 1.17.10
     */
    public static function init(): void
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
    }

    /**
     * Checks if the plugin was just activated and sends the tracking event.
     * This runs on admin_init to ensure the full environment is available.
     * Fires regardless of the consent checkbox value.
     *
     * @access public
     * @return void
     * @since 1.17.10
     */
    public function maybe_track_activation(): void
    {
        if ( get_transient( 'wpfunnels_just_activated' ) ) {
            delete_transient( 'wpfunnels_just_activated' );
            $this->on_plugin_activated();
        }
    }

    public function on_plugin_activated(): void
    {
        coderex_telemetry_track(
            WPFNL_FILE,
            'signup',
            array(
                'site_url'        => get_site_url(),
                'plugin_version'  => WPFNL_VERSION,
            )
        );
    }

    /**
     * Sends a plugin deactivation event to OpenPanel.
     *
     * This method sends a plugin deactivation event to OpenPanel, including
     * the site URL and plugin version.
     *
     * @access public
     *
     * @return void
     *
     * @since 1.17.10
     */
    public function on_plugin_deactivated(): void
    {
        coderex_telemetry_track(
            WPFNL_FILE,
            'plugin_deactivation',
            array(
                'site_url'        => get_site_url(),
                'plugin_version'  => WPFNL_VERSION,
            )
        );
    }

    /**
     * Sends an event to OpenPanel when a funnel is created.
     *
     * This method sends an event to OpenPanel when a funnel is created,
     * including the funnel ID and funnel type.
     *
     * @access public
     *
     * @param int $funnel_id The ID of the created funnel.
     * @param string $funnel_type The type of the created funnel.
     *
     * @return void
     *
     * @since 1.17.10
     */
    public function on_after_funnel_created($funnel_id, $funnel_type): void
    {
        $has_funnel_created = get_option('wpfunnels_funnel_created', false);
        if (!$has_funnel_created) {
            coderex_telemetry_track(
                WPFNL_FILE,
                'first_strike',
                array(
                    'funnel_id'   => $funnel_id,
                    'funnel_type' => $funnel_type,
                    'time'        => current_time('mysql'),
                )
            );
            update_option('wpfunnels_funnel_created', true);
        }
    }

    /**
     * Sends an event to OpenPanel when a funnel is imported.
     *
     * This method sends an event to OpenPanel when a funnel is imported,
     * including the funnel type and builder name.
     *
     * @access public
     *
     * @param int $funnel_id The ID of the imported funnel.
     * @param string $funnel_type The type of the imported funnel.
     * @param string $builder The builder used for the imported funnel.
     * @return void
     *
     * @since 1.17.10
     */
    public function on_after_funnel_imported($funnel_id, $funnel_type, $builder): void
    {
        $has_funnel_imported = get_option('wpfunnels_funnel_imported', false);
        if (!$has_funnel_imported) {
            coderex_telemetry_track(
                WPFNL_FILE,
                'first_strike',
                array(
                    'funnel_id'   => $funnel_id,
                    'builder'     => $builder,
                    'funnel_type' => $funnel_type,
                    'time'        => current_time('mysql'),
                )
            );
            update_option('wpfunnels_funnel_imported', true);
        }
    }

    /**
     * Tracks funnel creation event.
     *
     * This method sends an event to OpenPanel when a funnel is created,
     * including the funnel ID.
     *
     * @access public
     *
     * @param int $funnel_id The ID of the created funnel.
     *
     * @return void
     *
     * @since 1.17.10
     */
    public function on_after_funnel_creation($funnel_id): void
    {
        $funnel_id = absint( $funnel_id );
        if ( ! $funnel_id ) {
            return;
        }
        $post = get_post( $funnel_id );
        if ( empty( $post ) ) {
            return;
        }

        // Check if this is the first funnel created by the user
        $funnel_count  = wp_count_posts( 'wpfunnels' );
        $total_funnels = $funnel_count->publish + $funnel_count->draft + $funnel_count->pending;
        
        // Only send webhook if this is the first funnel (total count is 1)
        if ( $total_funnels === 1 ) {
            $current_user = wp_get_current_user();
            $email = $current_user->user_email;
            $name = $current_user->display_name;
            
            $funnel_data = array(
                'funnel_id'     => $funnel_id,
                'funnel_title'  => $post->post_title,
                'funnel_status' => $post->post_status,
                'created_date'  => $post->post_date,
            );
            
            $createContactInstance = new \WPFunnels\Admin\SetupWizard\CreateContact($email, $name);
            $createContactInstance->update_contact_via_webhook($funnel_data);
        }
    }

    /**
     * Tracks funnel order placed event.
     *
     * This method sends an event to OpenPanel when a funnel order is placed,
     * including the order ID, amount, and currency.
     *
     * @access public
     *
     * @param int $order_id The ID of the placed order.
     * @param float $funnel_id The amount of the placed order.
     * @param string $checkout_id The currency of the placed order.
     *
     * @return void
     *
     * @since 1.17.10
     */
    public function funnel_order_placed($order_id, $funnel_id, $checkout_id){
        $email = get_option('wpfunnels_activation_email');
        if ( empty($email) ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'wpfnl_stats';

        $stats = $wpdb->get_row("
            SELECT 
                COUNT(id) as total_orders,
                SUM(total_sales) as total_revenue,
                MAX(date_created) as last_order_date
            FROM $table
            WHERE status IN ('processing','completed')
        ");

        $payload = array(
            'total_orders'      => (int) $stats->total_orders,
            'total_revenue'     => (float) $stats->total_revenue,
            'last_order_date'   => $stats->last_order_date,
        );

        coderex_telemetry_track(
            WPFNL_FILE,
            'user_received_funnel_order',
            array(
                'total_orders'    => (int) $stats->total_orders,
                'total_revenue'   => (float) $stats->total_revenue,
                'last_order_date' => $stats->last_order_date,
                'time'            => current_time('mysql'),
            )
        );

        $createContactInstance = new \WPFunnels\Admin\SetupWizard\CreateContact($email, '');
        $createContactInstance->update_contact_order_via_webhook($payload);
    }

    /**
     * Sends an event to OpenPanel when the setup wizard is completed.
     *
     * This method sends an event to OpenPanel when the setup wizard is completed,
     * including the funnel ID and action.
     *
     * @access public
     *
     * @param int $funnel_id The ID of the funnel associated with the setup wizard.
     * @param string $action The action taken during the setup wizard.
     *
     * @return void
     *
     * @since 1.17.10
     */    
    public function on_setup_wizard_complete($funnel_id, $action): void
    {
        coderex_telemetry_track(
            WPFNL_FILE,
            'setup',
            array(
                'funnel_id' => $funnel_id,
                'action'    => $action,
                'time'      => current_time('mysql'),
            )
        );
    }
}
