<?php
/**
 * WPFunnels rest api controllers
 *
 * @package WPFunnels\Rest\Controller
 */
namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Controller;

abstract class Wpfnl_REST_Controller extends WP_REST_Controller {
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wpfunnels/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = '';


    /**
     * Prepare links for the request.
     *
     * @param string $setting_id Setting ID.
     * @param string $group_id Group ID.
     *
     * @return array Links for the given setting.
     * @since  3.0.0
     */
    protected function prepare_links( $setting_id ) {
        $base  = str_replace( '(?P<settings_id>[\w-]+)', $setting_id, $this->rest_base );
        $links = array(
            'self'       => array(
                'href' => get_rest_url( sprintf( '/%s/%s/%s', $this->namespace, $base, $setting_id ) ),
            ),
            'collection' => array(
                'href' => get_rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
            ),
        );
        return $links;
    }

    /**
     * Create a WP_Error object with the specified parameters.
     *
     * @param string $error_code    The error code.
     * @param string $error_message The error message.
     * @param int    $status        The HTTP status code.
     *
     * @return WP_Error The created WP_Error object.
     *
     * @since 2.7.9
     */
    protected function prepare_wp_error_response( $error_code, $error_message, $data ) {
        return new WP_Error(
            $error_code,
            $error_message,
            $data
        );
    }

     /**
     * Create a Success response with the specified parameters.
     *
     * @param string $message  Success message.
     * @param int    $code     Success coed.
     *
     * @return WP_Rest_Response The created WP_Rest_response object.
     *
     * @since 2.7.9
     */
    protected function prepare_wp_success_response( $message, $code ) {
        $response = array(
            'success'  => true,
            'message'  => $message,
            'status'   => $code
		);
		return rest_ensure_response( $response );
    }


	/**
	 * Returns default 'before' parameter for the reports.
	 *
	 * @return \WC_DateTime
	 * @throws \Exception
	 * @since 3.1.7
	 */
	public function default_before() {
		$datetime = new \DateTime();
		$datetime->setTimezone( new \DateTimeZone( wpf_timezone_string() ) );
		return $datetime;
	}


	/**
	 * Returns default 'after' parameter for the reports.
	 *
	 * @return \Wpfnl_DateTime
	 * @throws \Exception
	 * @since 3.1.7
	 */
	public function default_after() {
		$now       = time();
		$week_back = $now - YEAR_IN_SECONDS;

		$datetime = new \DateTime();
		$datetime->setTimestamp( $week_back );
		$datetime->setTimezone( new \DateTimeZone( wpf_timezone_string() ) );
		return $datetime;
	}
}
