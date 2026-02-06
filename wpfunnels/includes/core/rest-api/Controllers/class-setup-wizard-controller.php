<?php
/**
 * Setup Wizard controller
 *
 * @package WPFunnels\Rest\Controllers
 */
namespace WPFunnels\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Server;

class SetupWizardController extends Wpfnl_REST_Controller {

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
    protected $rest_base = 'setup-wizard';

    /**
     * Register REST routes.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/complete-step',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'complete_step' ),
                    'permission_callback' => function () {
                        return current_user_can( 'manage_options' );
                    },
                    'args' => array(
                        'funnelId' => array(
                            'description'       => __( 'Funnel ID.', 'wpfnl' ),
                            'type'              => 'integer',
                            'required'          => false,
                            'sanitize_callback' => 'absint',
                        ),
                        'action' => array(
                            'description'       => __( 'Completion action.', 'wpfnl' ),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Handle setup wizard completion step.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response
     */
    public function complete_step( WP_REST_Request $request ) {
        $funnel_id = isset( $request['funnelId'] ) ? absint( $request['funnelId'] ) : 0;
        $action    = isset( $request['action'] ) ? sanitize_text_field( $request['action'] ) : '';

        /**
         * Fires when setup wizard completes an action.
         *
         * @param int            $funnel_id Funnel ID.
         * @param string         $action    Completion action.
         */
        do_action( 'wpfunnels_setup_wizard_complete', $funnel_id, $action );

        return rest_ensure_response( array(
            'success' => true,
        ) );
    }
}
