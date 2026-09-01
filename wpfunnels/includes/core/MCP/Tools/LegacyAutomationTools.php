<?php
/**
 * LegacyAutomationTools — the free plugin's "Automation" CRUD
 * (includes/core/rest-api/Controllers/class-automation-controller.php,
 * REST namespace `wpfnl/v1`, `WpfnlAutomationController`).
 *
 * This is a DIFFERENT feature from the Mail Mint automation *canvas* already
 * covered by WPFunnels Pro's own `AutomationTools.php` (funnel-scoped CRM
 * triggers like Zapier/Pabbly/ActiveCampaign wired up per-funnel, backed by
 * wpfunnels-pro/includes/core/rest-api/controllers/AutomationController.php
 * and driven by admin/src/components/Automation/*.vue). This class does not
 * touch that feature or duplicate its abilities.
 *
 * `WpfnlAutomationController` itself is a thin CRUD proxy in front of Mail
 * Mint's own automation engine
 * (`MintMail\App\Internal\Automation\AutomationModel`) — every method here
 * requires Mail Mint active, exactly like the REST controller does, and every
 * write goes through the same controller methods the REST routes call so
 * this never re-implements Mail Mint's validation.
 *
 * A "Test Zapier" ability was considered (per Drawer.vue's `testZapier()`,
 * which POSTs to `wpfunnels/v1/automation/wpfnl-test-zapier/{funnelId}`) and
 * deliberately dropped: that endpoint, and the Zapier/Pabbly CRM automation
 * UI around it (Automation.vue, Drawer.vue, Trigger.vue — all of which read
 * `get_data_by_index`/`delete` from `wpfunnels/v1/automation`), belong to
 * WPFunnels Pro's `AutomationController.php`, not to anything in this free
 * plugin. A grep for "zapier" (case-insensitive) across this plugin's
 * `includes/` turned up no route registration and no handler — only UI
 * copy/labels and the marketing comparison table. There is no free-plugin
 * backend to wrap, so none is invented here.
 *
 * @package WPFunnels\MCP
 * @since 3.14.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;
use WPFunnels\Rest\Controllers\WpfnlAutomationController;

/**
 * Class LegacyAutomationTools
 */
class LegacyAutomationTools {

	/**
	 * Ability definitions for this domain.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/list-legacy-automations'          => [
				'label'               => __( 'List Legacy Automations', 'wpfnl' ),
				'description'         => 'List automations created through WPFunnels\' legacy Automation CRUD (a Mail Mint-backed feature, separate from the Automation Canvas). Requires Mail Mint active. Note: the underlying endpoint only returns automations tagged as Mail Mint-native — see the response\'s "note" field.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => array_merge(
						MCPHelper::paginationSchema(),
						[]
					),
				],
				'execute_callback'    => [ __CLASS__, 'listLegacyAutomations' ],
				'permission_callback' => MCPHelper::currentUserCan( 'manage_options' ),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/create-legacy-automation'         => [
				'label'               => __( 'Create Legacy Automation', 'wpfnl' ),
				'description'         => 'Create a legacy (Mail Mint-backed) automation. Requires Mail Mint active. `data` must include `name` and `trigger_name`; `status` defaults to "draft" ("active" to enable immediately) and `author` defaults to the current user.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'data' => [
							'type'        => 'object',
							'description' => 'Automation payload. At minimum: {name, trigger_name}. Optional: {status: "draft"|"active", author, steps: [...]} — see wpfunnels/list-legacy-automations output for the shape of an existing automation.',
						],
					],
					'required'   => [ 'data' ],
				],
				'execute_callback'    => [ __CLASS__, 'createLegacyAutomation' ],
				'permission_callback' => MCPHelper::currentUserCan( 'manage_options' ),
				'annotations'         => [],
			],
			'wpfunnels/update-legacy-automation'         => [
				'label'               => __( 'Update Legacy Automation', 'wpfnl' ),
				'description'         => 'Update a legacy (Mail Mint-backed) automation. Requires Mail Mint active. The underlying engine only accepts the write when `data` includes `name`, `trigger_name` and `status` together with any changed fields — a partial patch missing one of those three will fail. Read wpfunnels/list-legacy-automations first and send back the full record with your changes merged in.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'automation_id' => [
							'type'        => 'integer',
							'description' => 'Automation ID.',
						],
						'data'          => [
							'type'        => 'object',
							'description' => 'Full automation payload including name, trigger_name and status (see this ability\'s description).',
						],
					],
					'required'   => [ 'automation_id', 'data' ],
				],
				'execute_callback'    => [ __CLASS__, 'updateLegacyAutomation' ],
				'permission_callback' => MCPHelper::currentUserCan( 'manage_options' ),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/bulk-delete-legacy-automations'   => [
				'label'               => __( 'Bulk Delete Legacy Automations', 'wpfnl' ),
				'description'         => 'Permanently delete one or more legacy automations, including their steps, logs and scheduled emails. Requires Mail Mint active. This cannot be undone.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'automation_ids' => [
							'type'        => 'array',
							'description' => 'Automation IDs to delete.',
							'items'       => [ 'type' => 'integer' ],
						],
					],
					'required'   => [ 'automation_ids' ],
				],
				'execute_callback'    => [ __CLASS__, 'bulkDeleteLegacyAutomations' ],
				'permission_callback' => MCPHelper::currentUserCan( 'manage_options' ),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/toggle-legacy-automation'         => [
				'label'               => __( 'Toggle Legacy Automation', 'wpfnl' ),
				'description'         => 'Activate or deactivate a legacy automation by flipping its `status` between "active" and "draft" (the real active/inactive flag Mail Mint stores for it). Requires Mail Mint active.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'automation_id' => [
							'type'        => 'integer',
							'description' => 'Automation ID.',
						],
						'active'        => [
							'type'        => 'boolean',
							'description' => 'true to set status "active", false to set status "draft".',
						],
					],
					'required'   => [ 'automation_id', 'active' ],
				],
				'execute_callback'    => [ __CLASS__, 'toggleLegacyAutomation' ],
				'permission_callback' => MCPHelper::currentUserCan( 'manage_options' ),
				'annotations'         => [ 'destructive' ],
			],
		];
	}

	/**
	 * Whether Mail Mint's automation engine and this plugin's automation
	 * REST controller are both available.
	 *
	 * @return bool
	 */
	private static function mailMintAvailable() {
		return class_exists( '\MintMail\App\Internal\Automation\AutomationModel' )
			&& class_exists( '\WPFunnels\Rest\Controllers\WpfnlAutomationController' );
	}

	/**
	 * Standard error for when Mail Mint isn't available.
	 *
	 * @return \WP_Error
	 */
	private static function mailMintUnavailableError() {
		return MCPHelper::error(
			'mail_mint_required',
			'Legacy automations require Mail Mint. Install and activate it first — see wpfunnels/activate-mail-mint.'
		);
	}

	/**
	 * List legacy automations, reusing the real REST controller method.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function listLegacyAutomations( $input = [] ) {
		if ( ! self::mailMintAvailable() ) {
			return self::mailMintUnavailableError();
		}

		$page     = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;
		$per_page = MCPHelper::perPage( isset( $input['per_page'] ) ? $input['per_page'] : MCPHelper::DEFAULT_PER_PAGE );

		$controller = new WpfnlAutomationController();
		$request    = new \WP_REST_Request( 'GET', '/wpfnl/v1/automation' );
		$request->set_param( 'page', $page );
		$request->set_param( 'per_page', $per_page );

		$response = $controller->get_items( $request );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response->get_data();

		return [
			'automations' => isset( $data['data'] ) ? $data['data'] : [],
			'pagination'  => [
				'page'        => $page,
				'per_page'    => $per_page,
				'total'       => isset( $data['count'] ) ? (int) $data['count'] : 0,
				'total_pages' => isset( $data['total_pages'] ) ? (int) $data['total_pages'] : 0,
			],
			'note'        => 'The underlying endpoint only returns automations whose "source" meta is "mint". Automations created via wpfunnels/create-legacy-automation are tagged source="wpf" and may not appear in this list — this mirrors an existing mismatch in the underlying REST endpoint, not something this tool introduces.',
		];
	}

	/**
	 * Create a legacy automation, reusing the real REST controller method.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function createLegacyAutomation( $input = [] ) {
		if ( ! self::mailMintAvailable() ) {
			return self::mailMintUnavailableError();
		}

		$data = isset( $input['data'] ) && is_array( $input['data'] ) ? $input['data'] : [];

		if ( empty( $data['name'] ) || empty( $data['trigger_name'] ) ) {
			return MCPHelper::error( 'missing_fields', 'Provide at least data.name and data.trigger_name.' );
		}

		if ( empty( $data['status'] ) ) {
			$data['status'] = 'draft';
		}

		$controller = new WpfnlAutomationController();
		$request    = new \WP_REST_Request( 'POST', '/wpfnl/v1/automation' );
		$request->set_body( wp_json_encode( $data ) );

		$response = $controller->create_item( $request );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$result = $response->get_data();

		return [
			'success'       => true,
			'automation_id' => isset( $result['id'] ) ? (int) $result['id'] : null,
		];
	}

	/**
	 * Update a legacy automation, reusing the real REST controller method.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updateLegacyAutomation( $input = [] ) {
		if ( ! self::mailMintAvailable() ) {
			return self::mailMintUnavailableError();
		}

		$id = isset( $input['automation_id'] ) ? (int) $input['automation_id'] : 0;
		if ( ! $id ) {
			return MCPHelper::error( 'missing_automation_id', 'Provide automation_id.' );
		}

		$data = isset( $input['data'] ) && is_array( $input['data'] ) ? $input['data'] : [];
		if ( empty( $data ) ) {
			return MCPHelper::error( 'missing_data', 'Provide the automation fields to write in data.' );
		}

		$controller = new WpfnlAutomationController();
		$request    = new \WP_REST_Request( 'PUT', '/wpfnl/v1/automation/' . $id );
		$request->set_param( 'id', $id );
		$request->set_body( wp_json_encode( $data ) );

		$response = $controller->update_item( $request );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return [
			'success'       => true,
			'automation_id' => $id,
		];
	}

	/**
	 * Permanently delete one or more legacy automations.
	 *
	 * `WpfnlAutomationController` only exposes a single-item delete route, so
	 * this calls Mail Mint's own `AutomationModel::destroy_all()` directly —
	 * the same bulk-delete method Mail Mint's own admin list table uses,
	 * rather than looping single deletes through the REST controller.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function bulkDeleteLegacyAutomations( $input = [] ) {
		if ( ! self::mailMintAvailable() ) {
			return self::mailMintUnavailableError();
		}

		$ids = isset( $input['automation_ids'] ) && is_array( $input['automation_ids'] )
			? array_values( array_filter( array_map( 'intval', $input['automation_ids'] ) ) )
			: [];

		if ( empty( $ids ) ) {
			return MCPHelper::error( 'missing_automation_ids', 'Provide at least one automation_id.' );
		}

		$result = \MintMail\App\Internal\Automation\AutomationModel::destroy_all( $ids );

		return [
			'success'        => (bool) $result,
			'automation_ids' => $ids,
		];
	}

	/**
	 * Activate/deactivate a legacy automation by flipping its `status`.
	 *
	 * Mail Mint's `create_or_update()` requires `name`, `author`,
	 * `trigger_name` and `status` together on every write, so this reads the
	 * existing record first (via the real `get_item()` controller method),
	 * merges in the new status, and writes the full record back through
	 * `update_item()` — never inventing a lighter-weight "just flip a flag"
	 * path the underlying engine doesn't actually support.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function toggleLegacyAutomation( $input = [] ) {
		if ( ! self::mailMintAvailable() ) {
			return self::mailMintUnavailableError();
		}

		$id = isset( $input['automation_id'] ) ? (int) $input['automation_id'] : 0;
		if ( ! $id ) {
			return MCPHelper::error( 'missing_automation_id', 'Provide automation_id.' );
		}

		if ( ! isset( $input['active'] ) ) {
			return MCPHelper::error( 'missing_active', 'Provide active: true or false.' );
		}
		$active = (bool) $input['active'];

		$controller = new WpfnlAutomationController();

		$get_request = new \WP_REST_Request( 'GET', '/wpfnl/v1/automation/' . $id );
		$get_request->set_param( 'id', $id );
		$current = $controller->get_item( $get_request );
		if ( is_wp_error( $current ) ) {
			return $current;
		}

		$current_data = $current->get_data();
		$record       = isset( $current_data['data'][0] ) ? $current_data['data'][0] : null;
		if ( ! $record ) {
			return MCPHelper::error( 'automation_not_found', sprintf( 'No automation found with ID %d.', $id ) );
		}

		$status = $active ? 'active' : 'draft';

		$payload = [
			'id'           => $id,
			'name'         => isset( $record['name'] ) ? $record['name'] : '',
			'author'       => isset( $record['author'] ) ? $record['author'] : get_current_user_id(),
			'trigger_name' => isset( $record['trigger_name'] ) ? $record['trigger_name'] : '',
			'status'       => $status,
		];

		$update_request = new \WP_REST_Request( 'PUT', '/wpfnl/v1/automation/' . $id );
		$update_request->set_param( 'id', $id );
		$update_request->set_body( wp_json_encode( $payload ) );

		$response = $controller->update_item( $update_request );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return [
			'success'       => true,
			'automation_id' => $id,
			'active'        => $active,
			'status'        => $status,
		];
	}
}
