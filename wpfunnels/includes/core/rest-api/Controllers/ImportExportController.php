<?php

namespace WPFunnels\Rest\Controllers;

use Error;
use WPFunnels\Export\Wpfnl_Export;
use WPFunnels\Import\Wpfnl_Import;
use WP_Error;
use WP_REST_Request;
use WPFunnels\Wpfnl;
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
     * Check read permission for this resource.
     *
     * @param WP_REST_Request $request
     *
     * @return bool|WP_Error
     * @since 2.5.16
     */
    public function get_items_permissions_check($request)
    {
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'funnels' )) {
            return new WP_Error('wpfunnels_rest_cannot_view', __('Sorry, you cannot list resources.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
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

        register_rest_route($this->namespace, '/' . $this->rest_base . '/import', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [
                    $this,
                    'import_funnels'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/import-status', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_import_status'
                ],
                'permission_callback' => [
                    $this,
                    'get_items_permissions_check'
                ],
                'args' => [
                    'run_id' => [
                        'type'              => 'string',
                        'required'          => false,
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/import-reset', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'reset_import'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
            ],
        ]);
    }


    /**
     * Queue an uploaded funnel export for import.
     *
     * The REST twin of the `wpfnl_import_funnels` admin-ajax action, added for the React
     * funnel list — the file rides in as multipart form data under `uploaded_file`, exactly
     * the field the shared uploader reads, and the response carries the run id the import
     * modal then polls `import-status` with.
     *
     * @param WP_REST_Request $request
     *
     * @return \WP_REST_Response|WP_Error
     * @since 3.13.4
     */
    public function import_funnels($request)
    {
        $importer = Wpfnl::$instance->import_funnel;

        if (!$importer instanceof Wpfnl_Import) {
            $importer = new Wpfnl_Import();
        }

        $result = $importer->schedule_import();

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array_merge(['success' => true], $result));
    }


    /**
     * Report the progress of the running (or most recent) funnel import.
     *
     * Polled by the import modal. The heavy lifting — including stall detection, which has to
     * be derived by a reader because the process that died cannot report on itself — lives in
     * Wpfnl_Import so the MCP tools and the admin UI agree on one definition of "running".
     *
     * @param WP_REST_Request $request
     *
     * @return \WP_REST_Response
     * @since 2.5.16
     */
    public function get_import_status($request)
    {
        $run_id = $request->get_param('run_id');

        return rest_ensure_response(Wpfnl_Import::get_status_payload(is_string($run_id) ? $run_id : ''));
    }


    /**
     * Clear a stuck import.
     *
     * Before this existed, one dead job left `as_has_scheduled_action()` returning true forever
     * and every later import was refused with "Import already scheduled", with no way out from
     * the UI.
     *
     * @param WP_REST_Request $request
     *
     * @return \WP_REST_Response
     * @since 2.5.16
     */
    public function reset_import($request)
    {
        return rest_ensure_response(Wpfnl_Import::reset_import());
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
