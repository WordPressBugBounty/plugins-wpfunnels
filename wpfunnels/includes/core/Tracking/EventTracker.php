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
     * Flag to indicate if tracking is enabled.
     *
     * @var bool $enabled Flag to indicate if tracking is enabled.
     * @since 1.17.10
     */
    private $enabled;

    /**
     * Private constructor to prevent instantiation from outside the class.
     *
     * @since 1.17.10
     */
    private function __construct(){
        $this->enabled = get_option('wpfunnels_allow_tracking') === 'yes';

        add_action( 'wpfunnels_after_accept_consent', array( $this, 'on_plugin_activated' ) );
        add_action( 'wpfunnels_after_funnel_created', array( $this, 'on_after_funnel_created' ), 10, 2 );
        add_action( 'wpfunnels_after_funnel_imported', array( $this, 'on_after_funnel_imported' ), 10, 3 );
        add_action( 'wpfunnels_plugin_deactivated', array( $this, 'on_plugin_deactivated' ) );
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
     * Sends a plugin activation event to OpenPanel.
     *
     * This method sends a plugin activation event to OpenPanel, including
     * the site URL, plugin slug, and plugin version.
     *
     * @access public
     *
     * @return void
     *
     * @since 1.17.10
     */
    public function on_plugin_activated(): void
    {
        coderex_telemetry_track(
            WPFNL_FILE,
            'plugin_activation',
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
                $funnel_type . '_funnel_created',
                array(
                    'funnel_id' => $funnel_id,
                    'time'      => current_time('mysql'),
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
                $funnel_type . '_funnel_imported',
                array(
                    'funnel_id' => $funnel_id,
                    'builder'   => $builder,
                    'time'      => current_time('mysql'),
                )
            );
            update_option('wpfunnels_funnel_imported', true);
        }
    }
}
