<?php

namespace WPFunnels\Rest\Controllers;

use Error;
use WPFunnels\Export\Wpfnl_Export;
use WP_Error;
use WP_REST_Request;
use WPFunnels\Wpfnl_functions;


class ImportExportController extends Wpfnl_REST_Controller
{

    /**
     * Endpoint namespace.
     *
     * @var string
     * @since 1.9.3
     */
    protected $namespace = 'wpfunnels/v1';

    /**
     * Route base.
     *
     * @var string
     * @since 1.9.3
     */
    protected $rest_base = 'import-export';

    /**
     * check if user has valid permission
     *
     * @param $request
     * @return bool|WP_Error
     * @since 1.9.3
     */
    public function update_items_permissions_check($request)
    {
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'steps', 'edit' )) {
            return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }


    /**
     * register rest routes
     *
     * @since 1.9.3
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/bulk-export-funnel', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'bulk_export_funnel'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
            ],
        ]);
    }

    /**
     * Export selected funnels
     * 
     * @param array $payload Funnel ids and status.
     * 
     * @return array|\WP_Rest_Response|\WP_Error
     * @since 1.9.3
     */
    public function bulk_export_funnel ( $payload ){

        $controller_class = Wpfnl_Export::getInstance();

        return $controller_class->bulk_export_funnel( $payload );
    }


    /**
     * Prepare a single setting object for response.
     *
     * @param object $item Setting object.
     * @param WP_REST_Request $request Request object.
     * @return \WP_REST_Response $response Response data.
     * @since 1.9.3
     */
    public function prepare_item_for_response($item, $request)
    {
        $data = $this->add_additional_fields_to_object($item, $request);
        return rest_ensure_response($data);
    }
}
